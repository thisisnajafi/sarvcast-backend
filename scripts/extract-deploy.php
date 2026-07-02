<?php

declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');

const DEPLOY_TOKEN = '__DEPLOY_EXTRACT_TOKEN__';

if (!hash_equals(DEPLOY_TOKEN, (string) ($_GET['token'] ?? ''))) {
    http_response_code(403);
    echo "Forbidden\n";
    exit;
}

$publicRoot = __DIR__;
$root = dirname($publicRoot);
$archive = $root . '/deploy.tar.gz';
$htaccessDeploy = $root . '/htaccess.deploy';
$publicHtaccessDeploy = $publicRoot . '/htaccess.deploy';

if (!is_file($archive)) {
    http_response_code(404);
    echo "deploy.tar.gz not found at {$archive}\n";
    exit;
}

function extractWithExec(string $archive, string $root): ?string
{
    if (!function_exists('exec')) {
        return 'exec() disabled';
    }

    $cmd = 'tar -xzf ' . escapeshellarg($archive) . ' -C ' . escapeshellarg($root) . ' 2>&1';
    $output = [];
    $code = 1;
    @exec($cmd, $output, $code);

    if ($code === 0) {
        return null;
    }

    return trim(implode("\n", $output)) ?: "tar exit code {$code}";
}

function extractWithPhar(string $archive, string $root): ?string
{
    if (!class_exists(PharData::class)) {
        return 'PharData unavailable';
    }

    $tarPath = preg_replace('/\.gz$/', '', $archive);

    try {
        $phar = new PharData($archive);

        if (is_file($tarPath)) {
            @unlink($tarPath);
        }

        $phar->decompress();
        $tar = new PharData($tarPath);
        $tar->extractTo($root, null, true);
        @unlink($tarPath);

        return null;
    } catch (Throwable $e) {
        @unlink($tarPath);

        return $e->getMessage();
    }
}

$execError = extractWithExec($archive, $root);
if ($execError !== null) {
    echo "tar exec failed: {$execError}\n";
    echo "Trying Phar fallback...\n";
    $pharError = extractWithPhar($archive, $root);
    if ($pharError !== null) {
        http_response_code(500);
        echo 'Extract failed: ' . $pharError . "\n";
        exit;
    }
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
