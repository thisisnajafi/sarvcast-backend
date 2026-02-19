<?php

header('Content-Type: application/json');

$token = isset($_GET['token']) ? $_GET['token'] : '';
$expected = 'sarvcast-ftp-deploy-x7k9m2';

if ($token !== $expected) {
    http_response_code(403);
    die(json_encode(['status' => 'error', 'message' => 'Unauthorized']));
}

$basePath = dirname(__DIR__);
$results = [];

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

// 2. Delete stale cache files before bootstrapping Laravel
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

// 3. Bootstrap Laravel and run artisan commands
try {
    define('LARAVEL_START', microtime(true));

    require $basePath . '/vendor/autoload.php';

    $app = require $basePath . '/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();

    $commands = [
        'config:cache'  => [],
        'route:cache'   => [],
        'view:cache'    => [],
        'storage:link'  => ['--force' => true],
    ];

    foreach ($commands as $cmd => $args) {
        try {
            $kernel->call($cmd, $args);
            $results[] = $cmd . ' OK';
        } catch (Throwable $e) {
            $results[] = $cmd . ' FAILED: ' . $e->getMessage();
        }
    }

} catch (Throwable $e) {
    $results[] = 'Laravel bootstrap failed: ' . $e->getMessage();

    // Fallback: create storage symlink manually
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

// 4. Clear OPcache
if (function_exists('opcache_reset')) {
    @opcache_reset();
    $results[] = 'OPcache cleared';
}

echo json_encode([
    'status' => 'success',
    'results' => $results,
    'time' => date('c'),
]);
