<?php

set_time_limit(0);
@ini_set('memory_limit', '1024M');

header('Content-Type: application/json');

$deployHelperFatal = static function (string $message, int $code = 500): void {
    if (! headers_sent()) {
        http_response_code($code);
    }

    echo json_encode([
        'status' => 'error',
        'message' => $message,
        'only' => isset($_GET['only']) ? trim((string) $_GET['only']) : '',
        'results' => [],
        'local_import' => null,
        'time' => date('c'),
    ]);
    exit;
};

register_shutdown_function(static function () use ($deployHelperFatal): void {
    $error = error_get_last();
    if ($error === null) {
        return;
    }

    $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR];
    if (! in_array($error['type'], $fatalTypes, true)) {
        return;
    }

    $deployHelperFatal($error['message'] . ' in ' . $error['file'] . ':' . $error['line']);
});

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
$allowedOnlyModes = ['config_clear', 'firebase_verify', 'migrate', 'migrate_verify', 'cache_rebuild', 'php_extensions', 'db_probe'];

if ($only !== '' && ! in_array($only, $allowedOnlyModes, true)) {
    http_response_code(400);
    die(json_encode([
        'status' => 'error',
        'message' => 'Unknown only mode. Allowed: config_clear, firebase_verify, migrate, migrate_verify, cache_rebuild, php_extensions, db_probe',
        'only' => $only,
    ]));
}

if ($only === 'php_extensions') {
    $pdoDrivers = class_exists('PDO') ? PDO::getAvailableDrivers() : [];
    $extDir = ini_get('extension_dir') ?: '/opt/cpanel/ea-php82/root/usr/lib64/php/modules';
    $extDir = rtrim((string) $extDir, '/\\');

    $probeModules = ['mysqlnd', 'pdo_mysql', 'nd_pdo_mysql', 'mbstring', 'dom', 'xml', 'curl', 'tokenizer', 'fileinfo'];
    $moduleFiles = [];
    foreach ($probeModules as $module) {
        $moduleFiles[$module] = [
            'so_exists' => is_file($extDir . '/' . $module . '.so'),
            'loaded' => extension_loaded($module),
        ];
    }

    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'only' => 'php_extensions',
        'php' => [
            'version' => PHP_VERSION,
            'sapi' => PHP_SAPI,
            'ini_loaded_file' => php_ini_loaded_file() ?: null,
            'ini_scanned_files' => php_ini_scanned_files() ?: null,
            'extension_dir' => $extDir,
            'user_ini_filename' => ini_get('user_ini.filename') ?: null,
            'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? null,
            'script_filename' => $_SERVER['SCRIPT_FILENAME'] ?? null,
        ],
        'checks' => [
            'class_exists_PDO' => class_exists('PDO'),
            'extension_loaded_pdo' => extension_loaded('pdo'),
            'extension_loaded_pdo_mysql' => extension_loaded('pdo_mysql'),
            'extension_loaded_nd_pdo_mysql' => extension_loaded('nd_pdo_mysql'),
            'class_exists_DOMDocument' => class_exists('DOMDocument'),
            'extension_loaded_dom' => extension_loaded('dom'),
            'extension_loaded_xml' => extension_loaded('xml'),
            'extension_loaded_mbstring' => extension_loaded('mbstring'),
            'extension_loaded_curl' => extension_loaded('curl'),
            'extension_loaded_fileinfo' => extension_loaded('fileinfo'),
            'class_exists_finfo' => class_exists('finfo'),
            'pdo_mysql_driver' => in_array('mysql', $pdoDrivers, true),
            'pdo_drivers' => $pdoDrivers,
        ],
        'module_files' => $moduleFiles,
        'hosting_hint' => deployDatabaseExtensionCheck() === null
            ? null
            : 'pdo_mysql.so exists on disk but is not loaded in web PHP. extension= in .user.ini is ignored by PHP. '
                . 'In cPanel go to Domains → my.manjiapp.ir → set PHP 8.2 and Save (not only account-level Select PHP Version). '
                . 'If still broken, open a ticket: LiteSpeed LSAPI must load pdo_mysql for domain my.manjiapp.ir.',
        'loaded_extensions' => get_loaded_extensions(),
        'time' => date('c'),
    ], JSON_PRETTY_PRINT);
    exit;
}

if ($only === 'db_probe') {
    require_once dirname(__DIR__) . '/bootstrap/hosting-extensions.php';

    $pdoDrivers = class_exists('PDO') ? PDO::getAvailableDrivers() : [];
    $payload = [
        'status' => 'success',
        'only' => 'db_probe',
        'php' => [
            'version' => PHP_VERSION,
            'sapi' => PHP_SAPI,
            'handler_note' => 'Must match Laravel web requests (litespeed/cgi). CLI "Connected!" does not prove web PHP has pdo_mysql.',
        ],
        'checks' => [
            'pdo_drivers' => $pdoDrivers,
            'mysql_driver' => in_array('mysql', $pdoDrivers, true),
            'extension_loaded_pdo_mysql' => extension_loaded('pdo_mysql'),
            'enable_dl' => ini_get('enable_dl'),
        ],
        'time' => date('c'),
    ];

    if (! in_array('mysql', $pdoDrivers, true)) {
        $payload['db_connect'] = 'skipped_no_mysql_driver';
        $payload['hosting_hint'] = deployDatabaseExtensionCheck();
        http_response_code(200);
        echo json_encode($payload, JSON_PRETTY_PRINT);
        exit;
    }

    $envPath = $basePath . '/.env';
    $envVars = [];
    if (is_file($envPath)) {
        foreach (file($envPath, FILE_IGNORE_NEW_LINES) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, '=')) {
                continue;
            }
            [$key, $value] = explode('=', $line, 2);
            $envVars[trim($key)] = trim($value, " \t\"'");
        }
    }

    $host = $envVars['DB_HOST'] ?? '127.0.0.1';
    $port = $envVars['DB_PORT'] ?? '3306';
    $database = $envVars['DB_DATABASE'] ?? '';
    $username = $envVars['DB_USERNAME'] ?? '';
    $password = $envVars['DB_PASSWORD'] ?? '';
    $socket = $envVars['DB_SOCKET'] ?? '';

    $attempts = [];
    if ($socket !== '') {
        $attempts[] = ['host' => 'localhost', 'port' => $port, 'socket' => $socket];
    }
    $attempts[] = ['host' => $host, 'port' => $port, 'socket' => null];
    if ($host === '127.0.0.1') {
        $attempts[] = ['host' => 'localhost', 'port' => $port, 'socket' => null];
    }

    $seen = [];
    $lastError = null;
    foreach ($attempts as $attempt) {
        $key = ($attempt['socket'] ?? '') . '|' . $attempt['host'];
        if (isset($seen[$key])) {
            continue;
        }
        $seen[$key] = true;

        $probe = deployTryPdoConnection(
            $attempt['host'],
            $attempt['port'],
            $database,
            $username,
            $password,
            $attempt['socket']
        );
        $payload['db_attempts'][] = [
            'host' => $probe['host'],
            'ok' => $probe['ok'],
            'error' => $probe['error'],
        ];

        if ($probe['ok']) {
            $payload['db_connect'] = 'ok';
            $payload['db_host'] = $probe['host'];
            $payload['db_database'] = $database;
            if ($host === '127.0.0.1' && $probe['host'] === 'localhost') {
                $payload['hosting_hint'] = 'DB_HOST=127.0.0.1 failed; localhost (Unix socket) works — update .env to DB_HOST=localhost';
            }
            http_response_code(200);
            echo json_encode($payload, JSON_PRETTY_PRINT);
            exit;
        }

        $lastError = $probe['error'];
    }

    $payload['db_connect'] = 'failed';
    $payload['db_error'] = $lastError;
    if ($host === '127.0.0.1' && is_string($lastError) && str_contains($lastError, 'Connection refused')) {
        $payload['hosting_hint'] = 'Set DB_HOST=localhost in .env — cPanel MySQL does not accept TCP on 127.0.0.1:3306';
    }

    http_response_code(200);
    echo json_encode($payload, JSON_PRETTY_PRINT);
    exit;
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
require_once dirname(__DIR__) . '/scripts/zip-extract-utils.php';
require_once dirname(__DIR__) . '/scripts/deploy-cli-artisan.php';

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

function deployPdoMysqlAvailable(): bool
{
    if (! class_exists('PDO')) {
        return false;
    }

    if (in_array('mysql', PDO::getAvailableDrivers(), true)) {
        return true;
    }

    return extension_loaded('pdo_mysql') || extension_loaded('nd_pdo_mysql');
}

function deployDatabaseExtensionCheck(): ?string
{
    $phpVersion = PHP_VERSION;
    $iniFile = php_ini_loaded_file() ?: 'unknown';

    if (deployPdoMysqlAvailable()) {
        return null;
    }

    $hints = [];

    if (! class_exists('PDO') && ! extension_loaded('pdo')) {
        $hints[] = 'pdo is not loaded';
    }

    if (! deployPdoMysqlAvailable()) {
        $hints[] = 'pdo_mysql / mysql PDO driver is not loaded';
    }

    return 'PHP MySQL driver unavailable: ' . implode('; ', $hints)
        . " (PHP {$phpVersion} via " . PHP_SAPI . ", ini: {$iniFile})"
        . ' — in cPanel set MultiPHP Manager: assign PHP 8.2 to my.manjiapp.ir, then MultiPHP INI Editor for that domain and enable pdo + pdo_mysql'
        . ' — after saving, wait 2 minutes or run deploy with only=php_extensions to verify';
}

function deployFormatDatabaseConnectionError(Throwable $dbError): string
{
    $message = $dbError->getMessage();

    if (str_contains($message, 'Class "PDO" not found') || str_contains($message, "Class 'PDO' not found")) {
        return deployDatabaseExtensionCheck()
            ?? 'PDO extension is not enabled for web PHP — enable pdo and pdo_mysql in cPanel MultiPHP INI Editor';
    }

    if (str_contains($message, 'could not find driver') || str_contains($message, 'PDOException')) {
        if (! extension_loaded('pdo_mysql')) {
            return deployDatabaseExtensionCheck()
                ?? 'pdo_mysql extension is not enabled — enable it in cPanel MultiPHP INI Editor';
        }
    }

    if (str_contains($message, 'Connection refused') && str_contains($message, '127.0.0.1')) {
        return $message
            . ' — on cPanel/LiteSpeed shared hosting MySQL uses a Unix socket; set DB_HOST=localhost in .env (not 127.0.0.1)';
    }

    return $message . ' — verify PRODUCTION_DB_* secrets and server .env DB_* values';
}

/**
 * @return array{ok: bool, dsn: string, host: string, error: ?string}
 */
function deployTryPdoConnection(string $host, string $port, string $database, string $username, string $password, ?string $socket = null): array
{
    if ($socket !== null && $socket !== '') {
        $dsn = sprintf('mysql:unix_socket=%s;dbname=%s', $socket, $database);
        $effectiveHost = 'socket:' . $socket;
    } elseif ($host === 'localhost') {
        $dsn = sprintf('mysql:host=localhost;dbname=%s', $database);
        $effectiveHost = 'localhost';
    } else {
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s', $host, $port, $database);
        $effectiveHost = $host;
    }

    try {
        $pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_TIMEOUT => 5,
        ]);
        $pdo->query('SELECT 1');

        return ['ok' => true, 'dsn' => $dsn, 'host' => $effectiveHost, 'error' => null];
    } catch (Throwable $e) {
        return ['ok' => false, 'dsn' => $dsn, 'host' => $effectiveHost, 'error' => $e->getMessage()];
    }
}

function extractVendorArchive(string $basePath): array
{
    $zipPath = $basePath . '/vendor.zip';
    $vendorDir = $basePath . '/vendor';
    $hashFile = $vendorDir . '/.deploy-package-hash';

    if (! file_exists($zipPath)) {
        if (is_file($vendorDir . '/autoload.php')) {
            return ['vendor archive skipped (using existing vendor/)'];
        }

        return ['vendor extract FAILED: vendor.zip missing and vendor/ is not installed'];
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

    $error = deployExtractVendorZipToDirectory($zipPath, $vendorDir);
    if ($error !== null) {
        if ($forceVendor || ! is_file($vendorDir . '/autoload.php')) {
            return ['vendor extract FAILED: ' . $error];
        }

        return ['vendor extract WARN: ' . $error . ' — using existing vendor/'];
    }

    if (! is_file($vendorDir . '/autoload.php')) {
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

// 2. Extract vendor.zip (skip for config_clear — cache-only step; migrate uses force_vendor)
if ($only !== 'config_clear') {
    $results = array_merge($results, extractVendorArchive($basePath));
}

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
        'message' => 'Missing only mode. Use config_clear, firebase_verify, migrate, migrate_verify, or cache_rebuild',
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

$webDatabaseUnavailable = deployDatabaseExtensionCheck();
if ($webDatabaseUnavailable !== null && in_array($only, ['migrate', 'cache_rebuild'], true)) {
    $cliPhp = $only === 'migrate'
        ? deployResolveCliPhpWithMysql()
        : deployResolveCliPhp();

    if ($cliPhp !== null) {
        $results[] = 'Web PHP: ' . $webDatabaseUnavailable;
        $results[] = 'Using CLI PHP fallback: ' . $cliPhp;

        $cliRun = deployRunOnlyModeViaCli($basePath, $cliPhp, $only, $runSeed);
        $results = array_merge($results, $cliRun['lines']);

        if ($only === 'cache_rebuild' && ! $cliRun['ok']) {
            $fileOnly = deployFileOnlyCacheRebuild($basePath);
            $results = array_merge($results, $fileOnly['lines']);
            $cliRun['ok'] = $fileOnly['ok'];
        }

        if ($only === 'cache_rebuild') {
            $zipPath = $basePath . '/vendor.zip';
            if (is_file($zipPath) && is_file($basePath . '/vendor/autoload.php') && @unlink($zipPath)) {
                $results[] = 'Removed vendor.zip after extraction';
            }
        }

        if (function_exists('opcache_reset')) {
            @opcache_reset();
            $results[] = 'OPcache cleared';
        }

        http_response_code($cliRun['ok'] ? 200 : 500);
        echo json_encode([
            'status' => $cliRun['ok'] ? 'success' : 'error',
            'only' => $only,
            'results' => $results,
            'local_import' => null,
            'time' => date('c'),
        ]);
        exit;
    }

    if ($only === 'migrate') {
        $results[] = 'Database connection FAILED: ' . $webDatabaseUnavailable
            . ' — CLI PHP with PDO also unavailable on server';
    } else {
        $results[] = 'Cache rebuild FAILED: web PHP missing extensions and CLI PHP unavailable';
    }

    if (function_exists('opcache_reset')) {
        @opcache_reset();
        $results[] = 'OPcache cleared';
    }

    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'only' => $only,
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

    // firebase:verify and cache_rebuild do not require a live database connection.
    $requiresDatabase = ! in_array($only, ['firebase_verify', 'cache_rebuild'], true);

    if ($requiresDatabase) {
        try {
            \Illuminate\Support\Facades\DB::connection()->getPdo();
            $results[] = 'Database connection OK';
        } catch (Throwable $dbError) {
            $results[] = 'Database connection FAILED: ' . deployFormatDatabaseConnectionError($dbError);

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

    if ($only === 'migrate_verify') {
        $deployVerifyOk = true;
        $exitCode = $kernel->call('migrate:status', ['--no-ansi' => true]);
        $statusOutput = trim($kernel->output());
        $results[] = $exitCode === 0
            ? 'migrate:status OK'
            : 'migrate:status FAILED (exit ' . $exitCode . ')';

        $pendingCount = 0;
        foreach (explode("\n", $statusOutput) as $line) {
            if (stripos($line, 'Pending') !== false) {
                $pendingCount++;
            }
        }

        if ($exitCode !== 0) {
            $deployVerifyOk = false;
        } elseif ($pendingCount > 0) {
            $results[] = 'migrate:verify FAILED: ' . $pendingCount . ' pending migration(s)';
            $deployVerifyOk = false;
        } else {
            $results[] = 'migrate:verify OK (no pending migrations)';
        }

        $verifyColumns = isset($_GET['verify_columns'])
            ? array_filter(array_map('trim', explode(',', (string) $_GET['verify_columns'])))
            : [];

        foreach ($verifyColumns as $tableColumn) {
            if (! str_contains($tableColumn, '.')) {
                $results[] = 'schema:verify skipped invalid column spec: ' . $tableColumn;
                $deployVerifyOk = false;

                continue;
            }

            [$table, $column] = explode('.', $tableColumn, 2);
            $hasColumn = \Illuminate\Support\Facades\Schema::hasColumn($table, $column);
            if ($hasColumn) {
                $results[] = 'schema:verify OK ' . $tableColumn;
            } else {
                $results[] = 'schema:verify FAILED missing column ' . $tableColumn;
                $deployVerifyOk = false;
            }
        }

        if (function_exists('opcache_reset')) {
            @opcache_reset();
            $results[] = 'OPcache cleared';
        }

        http_response_code($deployVerifyOk ? 200 : 500);
        echo json_encode([
            'status' => $deployVerifyOk ? 'success' : 'error',
            'only' => 'migrate_verify',
            'results' => $results,
            'local_import' => null,
            'time' => date('c'),
        ]);
        exit;
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
