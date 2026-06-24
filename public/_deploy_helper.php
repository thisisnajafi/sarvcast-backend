<?php

set_time_limit(0);

header('Content-Type: application/json');

$token = isset($_GET['token']) ? $_GET['token'] : '';
$expected = 'sarvcast-ftp-deploy-x7k9m2';

if ($token !== $expected) {
    http_response_code(403);
    die(json_encode(['status' => 'error', 'message' => 'Unauthorized']));
}

$basePath = dirname(__DIR__);
$results = [];

function extractVendorArchive(string $basePath): array
{
    $zipPath = $basePath . '/vendor.zip';
    $vendorDir = $basePath . '/vendor';
    $hashFile = $vendorDir . '/.deploy-package-hash';

    if (!file_exists($zipPath)) {
        if (is_file($vendorDir . '/autoload.php')) {
            return ['vendor archive skipped (using existing vendor/)'];
        }

        return ['vendor extract FAILED: vendor.zip missing and vendor/ is not installed'];
    }

    if (!class_exists('ZipArchive')) {
        if (is_file($vendorDir . '/autoload.php')) {
            return ['ZipArchive unavailable; using existing vendor/'];
        }

        return ['vendor extract FAILED: ZipArchive extension is not enabled'];
    }

    $zipHash = @md5_file($zipPath);
    if ($zipHash === false) {
        return ['vendor extract FAILED: unable to read vendor.zip'];
    }

    if (
        is_file($vendorDir . '/autoload.php')
        && is_file($hashFile)
        && trim((string) file_get_contents($hashFile)) === $zipHash
    ) {
        return ['vendor archive unchanged; skipped extraction'];
    }

    if (!is_dir($vendorDir) && !@mkdir($vendorDir, 0755, true)) {
        return ['vendor extract FAILED: unable to create vendor directory'];
    }

    $zip = new ZipArchive();
    $opened = $zip->open($zipPath);

    if ($opened !== true) {
        return ['vendor extract FAILED: unable to open vendor.zip (code ' . $opened . ')'];
    }

    if (!$zip->extractTo($vendorDir)) {
        $zip->close();

        return ['vendor extract FAILED: ZipArchive::extractTo failed'];
    }

    $zip->close();

    if (!is_file($vendorDir . '/autoload.php')) {
        return ['vendor extract FAILED: vendor/autoload.php missing after extraction'];
    }

    @file_put_contents($hashFile, $zipHash);

    return ['vendor extract OK'];
}

// 1. Create necessary directories
$dirs = [
    'storage/app/public',
    'storage/app/public/audio',
    'storage/app/public/audio/episodes',
    'storage/app/public/images',
    'storage/app/public/images/stories',
    'storage/app/public/images/categories',
    'storage/app/public/images/episodes',
    'storage/framework/cache',
    'storage/framework/sessions',
    'storage/framework/views',
    'storage/logs',
    'bootstrap/cache',
];

foreach ($dirs as $dir) {
    $full = $basePath . '/' . $dir;
    if (!is_dir($full)) {
        if (@mkdir($full, 0755, true)) {
            $results[] = 'Created ' . $dir;
        } else {
            $results[] = 'Failed to create ' . $dir;
        }
    }
}

// 2. Extract vendor.zip uploaded by CI (shared hosting disables shell/composer over HTTP)
$results = array_merge($results, extractVendorArchive($basePath));

// 3. Delete stale cache files before bootstrapping Laravel
$staleFiles = [
    'bootstrap/cache/config.php',
    'bootstrap/cache/routes-v7.php',
];

foreach ($staleFiles as $file) {
    $full = $basePath . '/' . $file;
    if (file_exists($full)) {
        @unlink($full);
        $results[] = 'Deleted stale ' . $file;
    }
}

$viewsDir = $basePath . '/storage/framework/views';
if (is_dir($viewsDir)) {
    $count = 0;
    foreach (glob($viewsDir . '/*.php') as $file) {
        @unlink($file);
        $count++;
    }
    if ($count > 0) {
        $results[] = 'Cleared ' . $count . ' compiled views';
    }
}

// 4. Bootstrap Laravel and run artisan commands
try {
    define('LARAVEL_START', microtime(true));

    require $basePath . '/vendor/autoload.php';

    $app = require $basePath . '/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();

    $commands = [
        'clear-compiled'  => [],
        'migrate:status'  => [],
        'migrate'         => ['--force' => true],
        'config:cache'    => [],
        'route:cache'     => [],
        'view:cache'      => [],
        'optimize'        => [],
        'storage:link'    => ['--force' => true],
    ];

    foreach ($commands as $cmd => $args) {
        try {
            $exitCode = $kernel->call($cmd, $args);
            $output = trim($kernel->output());

            if ($exitCode !== 0) {
                $results[] = $cmd . ' FAILED (exit ' . $exitCode . ')'
                    . ($output ? ': ' . $output : '');

                continue;
            }

            $suffix = $output !== '' ? ': ' . $output : '';
            $results[] = $cmd . ' OK' . $suffix;
        } catch (Throwable $e) {
            $results[] = $cmd . ' FAILED: ' . $e->getMessage();
        }
    }

} catch (Throwable $e) {
    $results[] = 'Laravel bootstrap failed: ' . $e->getMessage();

    $link = $basePath . '/public/storage';
    if (is_link($link)) {
        @unlink($link);
    }
    if (!file_exists($link)) {
        $saved = getcwd();
        chdir($basePath . '/public');
        if (@symlink('../storage/app/public', 'storage')) {
            $results[] = 'Storage symlink created (manual fallback)';
        }
        chdir($saved);
    }
}

// 5. Remove vendor.zip after successful extraction to save disk space
$zipPath = $basePath . '/vendor.zip';
if (is_file($zipPath) && is_file($basePath . '/vendor/autoload.php')) {
    if (@unlink($zipPath)) {
        $results[] = 'Removed vendor.zip after extraction';
    }
}

// 6. Clear OPcache
if (function_exists('opcache_reset')) {
    @opcache_reset();
    $results[] = 'OPcache cleared';
}

$hasFailure = false;
foreach ($results as $line) {
    if (stripos($line, 'FAILED') !== false) {
        $hasFailure = true;
        break;
    }
}

http_response_code($hasFailure ? 500 : 200);

echo json_encode([
    'status' => $hasFailure ? 'error' : 'success',
    'results' => $results,
    'time' => date('c'),
]);
