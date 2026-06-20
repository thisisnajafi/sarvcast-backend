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

function runShellCommand(string $command, string $cwd): array
{
    if (function_exists('proc_open')) {
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = @proc_open($command, $descriptors, $pipes, $cwd);

        if (is_resource($process)) {
            fclose($pipes[0]);
            $stdout = stream_get_contents($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);

            $exitCode = proc_close($process);
            $output = trim($stdout . "\n" . $stderr);

            return [
                'ok' => $exitCode === 0,
                'output' => $output !== '' ? $output : '(no output)',
            ];
        }
    }

    if (function_exists('shell_exec')) {
        $output = (string) shell_exec('cd ' . escapeshellarg($cwd) . ' && ' . $command);
        $output = trim($output);

        return [
            'ok' => is_file($cwd . '/vendor/autoload.php'),
            'output' => $output !== '' ? $output : '(no output)',
        ];
    }

    return ['ok' => false, 'output' => 'Shell execution is disabled on this server'];
}

function ensureComposerPhar(string $basePath): ?string
{
    $composerPhar = $basePath . '/composer.phar';

    if (file_exists($composerPhar)) {
        return $composerPhar;
    }

    $installer = @file_get_contents('https://getcomposer.org/installer');
    if ($installer === false) {
        return null;
    }

    $saved = getcwd();
    chdir($basePath);

    $installerPath = $basePath . '/composer-setup.php';
    if (@file_put_contents($installerPath, $installer) === false) {
        chdir($saved);
        return null;
    }

    $installResult = runShellCommand('php composer-setup.php --quiet', $basePath);
    @unlink($installerPath);

    chdir($saved);

    return ($installResult['ok'] && file_exists($composerPhar)) ? $composerPhar : null;
}

function runComposerInstall(string $basePath): array
{
    $results = [];
    $installArgs = 'install --no-dev --optimize-autoloader --no-interaction --no-ansi 2>&1';

    $commands = [
        'composer ' . $installArgs,
        'php composer.phar ' . $installArgs,
        '/usr/local/bin/composer ' . $installArgs,
        '/usr/bin/composer ' . $installArgs,
    ];

    $composerPhar = ensureComposerPhar($basePath);
    if ($composerPhar !== null) {
        array_unshift($commands, 'php ' . escapeshellarg($composerPhar) . ' ' . $installArgs);
    }

    $lastOutput = 'No composer command succeeded';

    foreach ($commands as $command) {
        $result = runShellCommand($command, $basePath);
        if ($result['ok']) {
            $results[] = 'composer install OK';
            return $results;
        }
        $lastOutput = $result['output'];
    }

    $results[] = 'composer install FAILED: ' . substr($lastOutput, 0, 500);

    return $results;
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

// 2. Install / update Composer dependencies on the server (vendor is not uploaded via FTP)
$results = array_merge($results, runComposerInstall($basePath));

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
        'clear-compiled' => [],
        'config:cache'   => [],
        'route:cache'    => [],
        'view:cache'     => [],
        'optimize'       => [],
        'storage:link'   => ['--force' => true],
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

// 5. Clear OPcache
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

echo json_encode([
    'status' => $hasFailure ? 'error' : 'success',
    'results' => $results,
    'time' => date('c'),
]);
