<?php

declare(strict_types=1);

@ini_set('memory_limit', '768M');
@set_time_limit(0);

header('Content-Type: text/plain; charset=utf-8');

const DEPLOY_TOKEN = '__DEPLOY_EXTRACT_TOKEN__';

if (!hash_equals(DEPLOY_TOKEN, (string) ($_GET['token'] ?? ''))) {
    http_response_code(403);
    echo "Forbidden\n";
    exit;
}

$publicRoot = __DIR__;
$root = dirname($publicRoot);
$htaccessDeploy = $root . '/htaccess.deploy';
$publicHtaccessDeploy = $publicRoot . '/htaccess.deploy';

$archive = null;
foreach ([$root . '/deploy.zip', $root . '/deploy.tar.gz'] as $candidate) {
    if (is_file($candidate)) {
        $archive = $candidate;
        break;
    }
}

if ($archive === null) {
    http_response_code(404);
    echo "deploy.zip (or deploy.tar.gz) not found under {$root}\n";
    exit;
}

function runShellCommand(string $command): array
{
    if (function_exists('proc_open')) {
        $descriptors = [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $process = @proc_open($command, $descriptors, $pipes);
        if (is_resource($process)) {
            $stdout = stream_get_contents($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $code = proc_close($process);

            return [
                'code' => $code,
                'output' => trim($stdout . "\n" . $stderr),
            ];
        }
    }

    if (function_exists('exec')) {
        $output = [];
        $code = 1;
        @exec($command . ' 2>&1', $output, $code);

        return [
            'code' => $code,
            'output' => trim(implode("\n", $output)),
        ];
    }

    if (function_exists('shell_exec')) {
        $output = @shell_exec($command . ' 2>&1');

        return [
            'code' => $output === null ? 1 : 0,
            'output' => trim((string) $output),
        ];
    }

    return [
        'code' => 1,
        'output' => 'No shell execution functions available',
    ];
}

function deployExtractPathIsProtected(string $relativePath): bool
{
    $normalized = str_replace('\\', '/', $relativePath);

    $protectedPrefixes = [
        'storage/app/public/',
        'storage/app/manji-stories/',
        'storage/logs/',
        'storage/framework/',
        '.env',
        'database/database.sqlite',
    ];

    foreach ($protectedPrefixes as $prefix) {
        if ($normalized === rtrim($prefix, '/') || str_starts_with($normalized, $prefix)) {
            return true;
        }
    }

    return false;
}

function extractWithZipArchive(string $archive, string $root): ?string
{
    if (!class_exists(ZipArchive::class)) {
        return 'ZipArchive unavailable';
    }

    $zip = new ZipArchive();
    $opened = $zip->open($archive);
    if ($opened !== true) {
        return "ZipArchive::open failed (code {$opened})";
    }

    $extracted = 0;
    $skipped = 0;

    for ($index = 0; $index < $zip->numFiles; $index++) {
        $name = $zip->getNameIndex($index);
        if ($name === false || str_ends_with($name, '/')) {
            continue;
        }

        if (deployExtractPathIsProtected($name)) {
            $skipped++;
            continue;
        }

        if (!$zip->extractTo($root, [$name])) {
            $zip->close();

            return 'ZipArchive::extractTo failed for ' . $name;
        }

        $extracted++;
    }

    $zip->close();

    echo "Extracted {$extracted} file(s)";
    if ($skipped > 0) {
        echo ", skipped {$skipped} protected path(s) (storage/.env preserved on server)";
    }
    echo "\n";

    return null;
}

function extractWithShell(string $archive, string $root): ?string
{
    if (str_ends_with($archive, '.zip')) {
        $command = 'unzip -o ' . escapeshellarg($archive) . ' -d ' . escapeshellarg($root);
    } else {
        $command = 'tar -xzf ' . escapeshellarg($archive) . ' -C ' . escapeshellarg($root);
    }

    $result = runShellCommand($command);
    if ($result['code'] === 0) {
        return null;
    }

    return $result['output'] !== '' ? $result['output'] : 'shell extract failed';
}

function extractArchive(string $archive, string $root): ?string
{
    echo 'Archive: ' . basename($archive) . "\n";

    if (str_ends_with($archive, '.zip')) {
        $zipError = extractWithZipArchive($archive, $root);
        if ($zipError === null) {
            echo "Extracted with ZipArchive\n";

            return null;
        }

        echo "ZipArchive failed: {$zipError}\n";
        echo "Trying unzip command...\n";
        $shellError = extractWithShell($archive, $root);
        if ($shellError === null) {
            echo "Extracted with unzip\n";

            return null;
        }

        return $shellError;
    }

    echo "Trying tar command...\n";
    $shellError = extractWithShell($archive, $root);
    if ($shellError === null) {
        echo "Extracted with tar\n";

        return null;
    }

    return $shellError;
}

$extractError = extractArchive($archive, $root);
if ($extractError !== null) {
    http_response_code(500);
    echo 'Extract failed: ' . $extractError . "\n";
    exit;
}

@unlink($archive);

if (is_file($htaccessDeploy)) {
    if (@copy($htaccessDeploy, $root . '/.htaccess')) {
        echo "OK: root .htaccess updated\n";
    } else {
        echo "Warning: could not copy htaccess.deploy to .htaccess\n";
    }
}

if (is_file($publicHtaccessDeploy)) {
    if (@copy($publicHtaccessDeploy, $publicRoot . '/.htaccess')) {
        echo "OK: public/.htaccess updated\n";
    } else {
        echo "Warning: could not copy public htaccess.deploy\n";
    }
}

$firebasePath = $root . '/storage/app/firebase-service-account.json';
if (is_file($firebasePath)) {
    echo "OK: firebase-service-account.json present after extract\n";
} else {
    echo "Warning: firebase-service-account.json missing — set FIREBASE_SERVICE_ACCOUNT_JSON in GitHub secrets\n";
}

if (function_exists('opcache_reset')) {
    @opcache_reset();
    echo "OK: OPcache cleared\n";
}

echo "Laravel deploy extracted successfully\n";
