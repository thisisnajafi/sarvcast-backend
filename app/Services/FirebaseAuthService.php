<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class FirebaseAuthService
{
    /**
     * Get OAuth2 access token for Firebase
     * Tokens are cached for 50 minutes (tokens expire after 1 hour)
     */
    public function getAccessToken(): ?string
    {
        $cacheKey = 'firebase_access_token';

        // Try to get from cache first
        $token = Cache::get($cacheKey);
        if ($token) {
            return $token;
        }

        // Generate new token
        $token = $this->generateAccessToken();

        if ($token) {
            // Cache for 50 minutes (tokens expire after 1 hour)
            Cache::put($cacheKey, $token, now()->addMinutes(50));
        }

        return $token;
    }

    /**
     * Generate OAuth2 access token using service account
     */
    protected function generateAccessToken(): ?string
    {
        try {
            $serviceAccountPath = config('notification.firebase.service_account_path');

            if (!$serviceAccountPath || !file_exists($serviceAccountPath)) {
                Log::error('Firebase service account file not found', [
                    'path' => $serviceAccountPath
                ]);
                return null;
            }

            $serviceAccount = json_decode(file_get_contents($serviceAccountPath), true);

            if (!$serviceAccount) {
                Log::error('Failed to parse Firebase service account JSON');
                return null;
            }

            // Create JWT for OAuth2
            $jwt = $this->createJWT($serviceAccount);

            // Exchange JWT for access token
            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['access_token'] ?? null;
            }

            Log::error('Failed to get Firebase access token', [
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('Error generating Firebase access token: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Create JWT for OAuth2 authentication
     */
    protected function createJWT(array $serviceAccount): string
    {
        $now = time();
        $exp = $now + 3600; // Token expires in 1 hour

        // JWT Header
        $header = [
            'alg' => 'RS256',
            'typ' => 'JWT'
        ];

        // JWT Payload
        $payload = [
            'iss' => $serviceAccount['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'exp' => $exp,
            'iat' => $now,
        ];

        // Encode header and payload
        $headerEncoded = $this->base64UrlEncode(json_encode($header));
        $payloadEncoded = $this->base64UrlEncode(json_encode($payload));

        // Create signature
        $signatureInput = $headerEncoded . '.' . $payloadEncoded;
        $signature = '';

        $privateKey = $serviceAccount['private_key'];
        openssl_sign($signatureInput, $signature, $privateKey, OPENSSL_ALGO_SHA256);

        $signatureEncoded = $this->base64UrlEncode($signature);

        return $headerEncoded . '.' . $payloadEncoded . '.' . $signatureEncoded;
    }

    /**
     * Base64 URL encode (RFC 4648)
     */
    protected function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}

