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

$link = $basePath . '/public/storage';
$target = $basePath . '/storage/app/public';

if (is_link($link)) {
    @unlink($link);
    $results[] = 'Removed old storage symlink';
}

if (!file_exists($link)) {
    $saved = getcwd();
    chdir($basePath . '/public');
    if (@symlink('../storage/app/public', 'storage')) {
        $results[] = 'Storage symlink created';
    } elseif (@symlink($target, $link)) {
        $results[] = 'Storage symlink created (absolute)';
    } else {
        $results[] = 'Symlink failed - run manually: php artisan storage:link';
    }
    chdir($saved);
} else {
    $results[] = 'public/storage already exists';
}

if (function_exists('opcache_reset')) {
    @opcache_reset();
    $results[] = 'OPcache cleared';
}

echo json_encode([
    'status' => 'success',
    'results' => $results,
    'time' => date('c'),
]);
