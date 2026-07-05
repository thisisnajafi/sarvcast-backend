<?php

set_time_limit(0);
@ini_set('memory_limit', '1024M');

header('Content-Type: application/json');

$token = isset($_GET['token']) ? $_GET['token'] : '';
$expected = 'manji-ftp-deploy-x7k9m2';

if ($token !== $expected) {
    http_response_code(403);
    die(json_encode(['status' => 'error', 'message' => 'Unauthorized']));
}

$basePath = dirname(__DIR__);
$results = [];
$only = isset($_GET['only']) ? trim((string) $_GET['only']) : '';
$runSeed = isset($_GET['seed']) && $_GET['seed'] === '1';
$allowedOnlyModes = ['config_clear', 'firebase_verify', 'migrate', 'cache_rebuild'];

if ($only !== '' && ! in_array($only, $allowedOnlyModes, true)) {
    http_response_code(400);
    die(json_encode([
        'status' => 'error',
        'message' => 'Unknown only mode. Allowed: config_clear, firebase_verify, migrate, cache_rebuild',
        'only' => $only,
    ]));
}

function artisanCommandLabel(string $cmd): string
{
    return match ($cmd) {
        'migrate' => 'php artisan migrate --force',
        'firebase:verify' => 'php artisan firebase:verify --clear-cache',
        'config:clear' => 'php artisan config:clear',
        default => $cmd,
    };
}

function deployResultLineIsFailure(string $line): bool
{
    // Case-sensitive: avoid matching benign "Failed to ..." htaccess/dir warnings.
    return str_contains($line, ' FAILED')
        || str_contains($line, 'FAILED:')
        || str_contains($line, 'bootstrap failed:');
}

function activateHtaccessFiles(string $basePath): array
{
    $activated = [];
    $pairs = [
        [$basePath . '/htaccess', $basePath . '/.htaccess'],
        [$basePath . '/public/htaccess', $basePath . '/public/.htaccess'],
    ];

    foreach ($pairs as [$source, $destination]) {
        if (!is_file($source)) {
            continue;
        }

        if (!is_file($destination) || @md5_file($source) !== @md5_file($destination)) {
            if (@copy($source, $destination)) {
                $activated[] = 'Activated ' . str_replace($basePath . '/', '', $destination);
            } else {
                $activated[] = 'Warn: could not activate ' . str_replace($basePath . '/', '', $destination);
            }
        }
    }

    return $activated;
}

$results = array_merge($results, activateHtaccessFiles($basePath));

require_once dirname(__DIR__) . '/scripts/deploy-firebase-verify.php';

if ($only === 'firebase_verify') {
    $results = array_merge($results, deployStandaloneFirebaseVerify($basePath));

    if (function_exists('opcache_reset')) {
        @opcache_reset();
        $results[] = 'OPcache cleared';
    }

    $hasFailure = false;
    foreach ($results as $line) {
        if (deployResultLineIsFailure($line)) {
            $hasFailure = true;
            break;
        }
    }

    http_response_code($hasFailure ? 500 : 200);
    echo json_encode([
        'status' => $hasFailure ? 'error' : 'success',
        'only' => 'firebase_verify',
        'results' => $results,
        'local_import' => null,
        'time' => date('c'),
    ]);
    exit;
}

function deployLoadComposerAutoload(string $basePath): void
{
    $polyfills = $basePath . '/bootstrap/polyfills.php';
    if (is_file($polyfills)) {
        require_once $polyfills;
    }

    $autoload = $basePath . '/vendor/autoload.php';
    if (! is_file($autoload)) {
        throw new RuntimeException('vendor/autoload.php missing on server');
    }

    require $autoload;

    if (! extension_loaded('iconv') && ! function_exists('iconv') && ! is_file($basePath . '/vendor/symfony/polyfill-iconv/bootstrap.php')) {
        throw new RuntimeException(
            'iconv unavailable: enable ext-iconv in cPanel or deploy vendor with symfony/polyfill-iconv'
        );
    }
}

function clearConfigCacheFiles(string $basePath): array
{
    $cleared = [];
    $cacheDir = $basePath . '/bootstrap/cache';

    if (is_dir($cacheDir)) {
        foreach (glob($cacheDir . '/*.php') ?: [] as $file) {
            if (@unlink($file)) {
                $cleared[] = 'Deleted bootstrap/cache/' . basename($file);
            }
        }
    }

    return $cleared;
}

function clearFrameworkCacheFiles(string $basePath): array
{
    $cleared = [];

    $viewDir = $basePath . '/storage/framework/views';
    if (is_dir($viewDir)) {
        foreach (glob($viewDir . '/*.php') ?: [] as $file) {
            if (@unlink($file)) {
                $cleared[] = 'Deleted storage/framework/views/' . basename($file);
            }
        }
    }

    $cacheDataDir = $basePath . '/storage/framework/cache/data';
    if (is_dir($cacheDataDir)) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($cacheDataDir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isFile() && @unlink($item->getPathname())) {
                $cleared[] = 'Deleted ' . str_replace($basePath . '/', '', $item->getPathname());
            }
        }
    }

    return $cleared;
}

function ensureProductionEnvFile(string $basePath): ?string
{
    $envPath = $basePath . '/.env';
    if (! is_file($envPath)) {
        return '.env missing on server — CI must upload PRODUCTION_DOTENV before migrate';
    }

    $env = @file_get_contents($envPath);
    if ($env === false || trim($env) === '') {
        return '.env exists but is empty';
    }

    if (! preg_match('/^DB_CONNECTION=mysql/m', $env)) {
        return '.env must set DB_CONNECTION=mysql (found sqlite or missing DB settings)';
    }

    if (! preg_match('/^APP_KEY=.+$/m', $env)) {
        return '.env is missing APP_KEY';
    }

    return null;
}

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

    $forceVendor = isset($_GET['force_vendor']) && $_GET['force_vendor'] === '1';
    if ($forceVendor && is_file($hashFile)) {
        @unlink($hashFile);
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
    'storage/app/public/sponsors/logos',
    'storage/app/public/images/episodes',
    'storage/app/public/media',
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
            $results[] = 'Warn: could not create ' . $dir;
        }
    }
}

// 2. Extract vendor.zip uploaded by CI (shared hosting disables shell/composer over HTTP)
$results = array_merge($results, extractVendorArchive($basePath));

// 3. Delete stale cache files before bootstrapping Laravel
$results = array_merge($results, clearConfigCacheFiles($basePath));
$results = array_merge($results, clearFrameworkCacheFiles($basePath));

// config:clear only needs to drop cached config files — no full Laravel bootstrap
// (avoids SmsService and other services failing when artisan loads commands).
if ($only === 'config_clear') {
    if (function_exists('opcache_reset')) {
        @opcache_reset();
        $results[] = 'OPcache cleared';
    }

    $results[] = 'php artisan config:clear OK (file cache removed without Laravel bootstrap)';

    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'only' => 'config_clear',
        'results' => $results,
        'local_import' => null,
        'time' => date('c'),
    ]);
    exit;
}

// 4. Bootstrap Laravel and run artisan commands
$localImportPayload = null;

if ($only === '') {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing only mode. Use config_clear, firebase_verify, migrate, or cache_rebuild',
        'results' => $results,
        'local_import' => null,
        'time' => date('c'),
    ]);
    exit;
}

$envError = ensureProductionEnvFile($basePath);
if ($envError !== null) {
    $results[] = 'bootstrap failed: ' . $envError;

    if (function_exists('opcache_reset')) {
        @opcache_reset();
        $results[] = 'OPcache cleared';
    }

    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'only' => $only !== '' ? $only : 'full',
        'results' => $results,
        'local_import' => null,
        'time' => date('c'),
    ]);
    exit;
}

try {
    define('LARAVEL_START', microtime(true));

    deployLoadComposerAutoload($basePath);

    $app = require $basePath . '/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();

    // firebase:verify only checks service account + FCM — no database required.
    $requiresDatabase = $only !== 'firebase_verify';

    if ($requiresDatabase) {
        try {
            \Illuminate\Support\Facades\DB::connection()->getPdo();
            $results[] = 'Database connection OK';
        } catch (Throwable $dbError) {
            $results[] = 'Database connection FAILED: ' . $dbError->getMessage()
                . ' — upload correct production .env (PRODUCTION_DOTENV / PRODUCTION_DB_PASSWORD in CI)';

            if (function_exists('opcache_reset')) {
                @opcache_reset();
                $results[] = 'OPcache cleared';
            }

            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'only' => $only !== '' ? $only : 'full',
                'results' => $results,
                'local_import' => null,
                'time' => date('c'),
            ]);
            exit;
        }
    }

    $onlyCommands = [
        'config_clear' => [
            'config:clear' => [],
        ],
        'migrate' => array_merge(
            [
                'migrate:status' => [],
                'migrate' => ['--force' => true],
                'stories:sync-episode-status' => [],
            ],
            $runSeed ? [
                'db:seed' => ['--class' => 'Database\\Seeders\\RolePermissionSeeder', '--force' => true],
            ] : []
        ),
        'cache_rebuild' => [
            'config:clear' => [],
            'clear-compiled' => [],
            'config:cache' => [],
            'route:cache' => [],
            'view:cache' => [],
            'optimize' => [],
        ],
    ];

    $commands = $onlyCommands[$only] ?? [];
    $deployBlocked = false;
    $skipAfterMigrateFail = [
        'stories:sync-episode-status',
        'db:seed',
        'config:cache',
        'route:cache',
        'view:cache',
        'optimize',
    ];

    $forbiddenArtisanCommands = [
        'migrate:fresh',
        'migrate:refresh',
        'migrate:reset',
        'db:wipe',
    ];

    foreach ($commands as $cmd => $args) {
        if (in_array($cmd, $forbiddenArtisanCommands, true)) {
            $results[] = $cmd . ' BLOCKED: destructive database command is never allowed during deploy';

            continue;
        }

        if ($deployBlocked && in_array($cmd, $skipAfterMigrateFail, true)) {
            $results[] = artisanCommandLabel($cmd) . ' skipped: migrate step failed';

            continue;
        }

        try {
            $exitCode = $kernel->call($cmd, $args);
            $output = trim($kernel->output());

            if ($exitCode !== 0) {
                $label = artisanCommandLabel($cmd);
                $results[] = $label . ' FAILED (exit ' . $exitCode . ')'
                    . ($output ? ': ' . $output : '');

                if ($cmd === 'migrate') {
                    $deployBlocked = true;
                }

                continue;
            }

            $label = artisanCommandLabel($cmd);
            $suffix = $output !== '' ? ': ' . $output : '';
            $results[] = $label . ' OK' . $suffix;
        } catch (Throwable $e) {
            $results[] = $cmd . ' FAILED: ' . $e->getMessage();

            if ($cmd === 'migrate') {
                $deployBlocked = true;
            }
        }
    }

    if ($only === 'cache_rebuild') {
        $storageLink = $basePath . '/public/storage';
        if (is_link($storageLink) || file_exists($storageLink)) {
            $results[] = 'storage:link skipped (link already exists)';
        } else {
            try {
                $exitCode = $kernel->call('storage:link', ['--force' => true]);
                $output = trim($kernel->output());
                if ($exitCode !== 0) {
                    $results[] = 'storage:link FAILED (exit ' . $exitCode . ')'
                        . ($output ? ': ' . $output : '');
                } else {
                    $results[] = 'storage:link OK' . ($output !== '' ? ': ' . $output : '');
                }
            } catch (Throwable $e) {
                $results[] = 'storage:link FAILED: ' . $e->getMessage();
            }
        }
    }

    if ($only === 'migrate' && isset($_GET['issue_local_import_token']) && $_GET['issue_local_import_token'] === '1') {
        try {
            $phone = isset($_GET['phone']) ? trim((string) $_GET['phone']) : null;
            $issued = app(\App\Services\LocalImportAccessService::class)->issueToken(
                userId: null,
                phone: ($phone !== null && $phone !== '') ? $phone : null,
                revokeExisting: true,
            );
            $localImportPayload = [
                'token_name' => $issued['token_name'],
                'token' => $issued['plain_text_token'],
                'user_id' => $issued['user']->id,
                'phone_number' => $issued['user']->phone_number,
                'api_base_url' => rtrim((string) config('app.url'), '/') . '/api/admin',
                'local_env' => [
                    'LOCAL_IMPORT_API_BASE_URL' => rtrim((string) config('app.url'), '/') . '/api/admin',
                    'LOCAL_IMPORT_API_TOKEN' => $issued['plain_text_token'],
                ],
            ];
            $results[] = 'local import API token issued for user #' . $issued['user']->id;
        } catch (Throwable $e) {
            $results[] = 'local import API token FAILED: ' . $e->getMessage();
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

// 5. Remove vendor.zip only after the final deploy step (cache_rebuild)
$zipPath = $basePath . '/vendor.zip';
if ($only === 'cache_rebuild' && is_file($zipPath) && is_file($basePath . '/vendor/autoload.php')) {
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
    if (deployResultLineIsFailure($line)) {
        $hasFailure = true;
        break;
    }
}

http_response_code($hasFailure ? 500 : 200);

echo json_encode([
    'status' => $hasFailure ? 'error' : 'success',
    'only' => $only !== '' ? $only : 'full',
    'results' => $results,
    'local_import' => $localImportPayload ?? null,
    'time' => date('c'),
]);
