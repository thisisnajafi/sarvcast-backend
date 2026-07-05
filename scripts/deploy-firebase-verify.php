<?php

declare(strict_types=1);

function deployReadEnvValues(string $envPath): array
{
    if (! is_file($envPath)) {
        return [];
    }

    $values = [];
    $lines = file($envPath, FILE_IGNORE_NEW_LINES);

    if ($lines === false) {
        return [];
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);

        if (
            (str_starts_with($value, '"') && str_ends_with($value, '"'))
            || (str_starts_with($value, "'") && str_ends_with($value, "'"))
        ) {
            $value = substr($value, 1, -1);
        }

        $values[$key] = $value;
    }

    return $values;
}

function deployResolveServiceAccountPath(string $basePath, string $configuredPath): string
{
    $configuredPath = trim($configuredPath);

    if ($configuredPath === '') {
        $configuredPath = 'storage/app/firebase-service-account.json';
    }

    if (str_starts_with($configuredPath, '/') || preg_match('/^[A-Za-z]:[\\\\\\/]/', $configuredPath)) {
        return $configuredPath;
    }

    if (str_starts_with($configuredPath, 'storage/')) {
        $configuredPath = substr($configuredPath, strlen('storage/'));
    }

    if (str_starts_with($configuredPath, 'app/')) {
        return $basePath . '/storage/' . $configuredPath;
    }

    return $basePath . '/storage/app/' . ltrim($configuredPath, '/');
}

function deployFindServiceAccountPath(string $basePath, string $configuredPath): ?string
{
    $primary = deployResolveServiceAccountPath($basePath, $configuredPath);
    if (is_file($primary)) {
        return $primary;
    }

    foreach (glob($basePath . '/storage/app/*-firebase-adminsdk*.json') ?: [] as $candidate) {
        if (is_file($candidate)) {
            return $candidate;
        }
    }

    $fallback = $basePath . '/storage/app/firebase-service-account.json';
    if (is_file($fallback)) {
        return $fallback;
    }

    return null;
}

function deployBase64UrlEncode(string $data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function deployFirebaseCreateJwt(array $serviceAccount): ?string
{
    if (empty($serviceAccount['private_key']) || empty($serviceAccount['client_email'])) {
        return null;
    }

    $now = time();
    $header = deployBase64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
    $payload = deployBase64UrlEncode(json_encode([
        'iss' => $serviceAccount['client_email'],
        'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
        'aud' => 'https://oauth2.googleapis.com/token',
        'exp' => $now + 3600,
        'iat' => $now,
    ]));

    $signatureInput = $header . '.' . $payload;
    $signature = '';
    $signed = openssl_sign(
        $signatureInput,
        $signature,
        $serviceAccount['private_key'],
        OPENSSL_ALGO_SHA256
    );

    if (! $signed) {
        return null;
    }

    return $signatureInput . '.' . deployBase64UrlEncode($signature);
}

function deployHttpRequest(string $method, string $url, array $options = []): array
{
    $headers = $options['headers'] ?? [];
    $body = $options['body'] ?? null;

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        if ($headers !== []) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $response = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        return [
            'status' => $status,
            'body' => is_string($response) ? $response : '',
            'error' => $error,
        ];
    }

    $headerLines = implode("\r\n", $headers);
    $contextOptions = [
        'http' => [
            'method' => strtoupper($method),
            'header' => $headerLines,
            'content' => $body ?? '',
            'timeout' => 30,
            'ignore_errors' => true,
        ],
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
        ],
    ];

    $response = @file_get_contents($url, false, stream_context_create($contextOptions));
    $status = 0;
    if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $matches)) {
        $status = (int) $matches[1];
    }

    return [
        'status' => $status,
        'body' => is_string($response) ? $response : '',
        'error' => is_string($response) ? '' : 'HTTP request failed (enable curl extension or allow_url_fopen)',
    ];
}

function deployHttpPostForm(string $url, array $fields): array
{
    return deployHttpRequest('POST', $url, [
        'headers' => ['Content-Type: application/x-www-form-urlencoded'],
        'body' => http_build_query($fields),
    ]);
}

function deployHttpPostJson(string $url, array $payload, string $accessToken): array
{
    $body = json_encode($payload);

    return deployHttpRequest('POST', $url, [
        'headers' => [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json',
        ],
        'body' => is_string($body) ? $body : '{}',
    ]);
}

function deployFormatHttpFailure(string $label, array $response): string
{
    $message = $label . ' HTTP ' . $response['status'];
    if (($response['error'] ?? '') !== '') {
        $message .= ' (' . $response['error'] . ')';
    }
    if (($response['body'] ?? '') !== '') {
        $message .= ': ' . $response['body'];
    }

    return $message;
}

function deployStandaloneFirebaseVerify(string $basePath): array
{
    $results = [];

    if (! function_exists('openssl_sign')) {
        return ['php artisan firebase:verify --clear-cache FAILED: openssl extension is required'];
    }

    $env = deployReadEnvValues($basePath . '/.env');
    $envProjectId = trim($env['FIREBASE_PROJECT_ID'] ?? '');
    $configuredPath = $env['FIREBASE_SERVICE_ACCOUNT_PATH'] ?? 'storage/app/firebase-service-account.json';
    $serviceAccountPath = deployFindServiceAccountPath($basePath, $configuredPath);

    if ($serviceAccountPath === null) {
        return [
            'php artisan firebase:verify --clear-cache FAILED: service account JSON not found'
            . ' (expected ' . deployResolveServiceAccountPath($basePath, $configuredPath) . ')',
        ];
    }

    $results[] = 'Firebase service account OK: ' . str_replace($basePath . '/', '', $serviceAccountPath);

    $serviceAccount = json_decode((string) file_get_contents($serviceAccountPath), true);
    if (! is_array($serviceAccount)) {
        return array_merge($results, ['php artisan firebase:verify --clear-cache FAILED: invalid service account JSON']);
    }

    $fileProjectId = trim((string) ($serviceAccount['project_id'] ?? ''));
    if ($fileProjectId === '') {
        return array_merge($results, ['php artisan firebase:verify --clear-cache FAILED: service account JSON missing project_id']);
    }

    $projectId = $fileProjectId;
    $results[] = 'Firebase project ID OK: ' . $projectId;

    if ($envProjectId !== '' && $envProjectId !== $projectId) {
        $results[] = 'Warn: .env FIREBASE_PROJECT_ID=' . $envProjectId
            . ' will be corrected to ' . $projectId . ' on next .env upload';
    } elseif ($envProjectId === '') {
        $results[] = 'Warn: FIREBASE_PROJECT_ID missing in .env (using service account project_id)';
    }

    $jwt = deployFirebaseCreateJwt($serviceAccount);
    if ($jwt === null) {
        return array_merge($results, ['php artisan firebase:verify --clear-cache FAILED: could not sign Firebase JWT']);
    }

    $tokenResponse = deployHttpPostForm('https://oauth2.googleapis.com/token', [
        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        'assertion' => $jwt,
    ]);

    if ($tokenResponse['status'] !== 200) {
        return array_merge($results, [
            'php artisan firebase:verify --clear-cache FAILED: '
            . deployFormatHttpFailure('OAuth token request', $tokenResponse),
        ]);
    }

    $tokenData = json_decode($tokenResponse['body'], true);
    $accessToken = is_array($tokenData) ? ($tokenData['access_token'] ?? null) : null;
    if (! is_string($accessToken) || $accessToken === '') {
        return array_merge($results, ['php artisan firebase:verify --clear-cache FAILED: OAuth response missing access_token']);
    }

    $results[] = 'Firebase OAuth token OK';

    $probeUrl = 'https://fcm.googleapis.com/v1/projects/' . rawurlencode($projectId) . '/messages:send';
    $probeResponse = deployHttpPostJson($probeUrl, [
        'message' => [
            'token' => 'permission-probe-invalid-token',
            'notification' => [
                'title' => 'probe',
                'body' => 'probe',
            ],
        ],
    ], $accessToken);

    if ($probeResponse['status'] === 403 && str_contains($probeResponse['body'], 'cloudmessaging.messages.create')) {
        return array_merge($results, [
            'php artisan firebase:verify --clear-cache FAILED: FCM permission denied (grant Firebase Cloud Messaging API Admin to service account)',
        ]);
    }

    if (in_array($probeResponse['status'], [400, 404], true)) {
        $results[] = 'php artisan firebase:verify --clear-cache OK';

        return $results;
    }

    return array_merge($results, [
        'php artisan firebase:verify --clear-cache FAILED: '
        . deployFormatHttpFailure('FCM probe', $probeResponse),
    ]);
}
