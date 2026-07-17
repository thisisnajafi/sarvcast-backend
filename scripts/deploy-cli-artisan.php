<?php

declare(strict_types=1);

require_once __DIR__ . '/zip-extract-utils.php';

function deployFindCliPhpBinaries(): array
{
    $candidates = [
        '/usr/local/bin/ea-php82',
        '/opt/cpanel/ea-php82/root/usr/bin/php',
        '/usr/local/bin/php82',
        '/usr/local/bin/php',
        'php82',
        'php',
    ];

    $found = [];
    foreach ($candidates as $bin) {
        if (in_array($bin, ['php', 'php82'], true)) {
            $found[] = $bin;

            continue;
        }

        if (is_executable($bin)) {
            $found[] = $bin;
        }
    }

    return array_values(array_unique($found));
}

function deployCliPhpExtensionLoaded(string $phpBinary, array $extraFlags, string $extension): bool
{
    $cmd = implode(' ', array_merge(
        [escapeshellarg($phpBinary)],
        $extraFlags,
        ['-r', escapeshellarg("echo extension_loaded('{$extension}') ? 'yes' : 'no';")]
    ));

    $result = deployRunShellCommand($cmd);

    return $result['code'] === 0 && trim($result['output']) === 'yes';
}

function deployCliGetExtensionDir(string $phpBinary): ?string
{
    $result = deployRunShellCommand(
        escapeshellarg($phpBinary) . ' -r "echo ini_get(\'extension_dir\');"'
    );

    $dir = rtrim(trim($result['output']), '/\\');

    return $dir !== '' ? $dir : null;
}

/**
 * @return string[] php -d flags
 */
function deployCliBuildExtensionFlags(string $phpBinary): array
{
    $extensionDir = deployCliGetExtensionDir($phpBinary);
    if ($extensionDir === null) {
        return [];
    }

    $flags = [];
    $loadOrder = ['xml', 'dom', 'tokenizer', 'mbstring', 'pdo', 'pdo_mysql'];

    foreach ($loadOrder as $extension) {
        if (deployCliPhpExtensionLoaded($phpBinary, $flags, $extension)) {
            continue;
        }

        foreach ([$extension . '.so', 'php_' . $extension . '.so'] as $file) {
            $path = $extensionDir . DIRECTORY_SEPARATOR . $file;
            if (! is_file($path)) {
                continue;
            }

            $candidateFlags = array_merge($flags, ['-d', 'extension=' . $path]);
            if (deployCliPhpExtensionLoaded($phpBinary, $candidateFlags, $extension)) {
                $flags = $candidateFlags;
                break;
            }
        }
    }

    return $flags;
}

function deployCliPhpSupportsMysql(string $phpBinary): bool
{
    $flags = deployCliBuildExtensionFlags($phpBinary);
    $cmd = implode(' ', array_merge(
        [escapeshellarg($phpBinary)],
        $flags,
        ['-r', escapeshellarg("echo (class_exists('PDO') && in_array('mysql', PDO::getAvailableDrivers(), true)) ? 'yes' : 'no';")]
    ));

    $result = deployRunShellCommand($cmd);

    return $result['code'] === 0 && trim($result['output']) === 'yes';
}

function deployResolveCliPhpWithMysql(): ?string
{
    foreach (deployFindCliPhpBinaries() as $binary) {
        if (deployCliPhpSupportsMysql($binary)) {
            return $binary;
        }
    }

    return null;
}

function deployResolveCliPhp(): ?string
{
    foreach (deployFindCliPhpBinaries() as $binary) {
        $result = deployRunShellCommand(escapeshellarg($binary) . ' -r "echo PHP_VERSION;"');

        if ($result['code'] === 0 && trim($result['output']) !== '') {
            return $binary;
        }
    }

    return null;
}

/**
 * @param  array<int|string, mixed>  $args
 * @return array{code: int, output: string}
 */
function deployRunArtisanViaCli(string $basePath, string $phpBinary, string $command, array $args = []): array
{
    $extensionFlags = deployCliBuildExtensionFlags($phpBinary);

    $parts = [
        'cd ' . escapeshellarg($basePath),
        '&&',
        'APP_DEBUG=false',
        'COLUMNS=120',
    ];

    $parts[] = escapeshellarg($phpBinary);
    $parts = array_merge($parts, $extensionFlags);
    $parts[] = escapeshellarg($basePath . '/artisan');
    $parts[] = $command;
    $parts[] = '--no-ansi';

    foreach ($args as $key => $value) {
        if (is_int($key)) {
            $parts[] = escapeshellarg((string) $value);

            continue;
        }

        $option = str_starts_with((string) $key, '--') ? substr((string) $key, 2) : (string) $key;

        if ($value === true) {
            $parts[] = '--' . $option;

            continue;
        }

        if ($value !== false && $value !== null) {
            $parts[] = '--' . $option . '=' . escapeshellarg((string) $value);
        }
    }

    return deployRunShellCommand(implode(' ', $parts) . ' 2>&1');
}

/**
 * @return array{ok: bool, lines: string[]}
 */
function deployRunOnlyModeViaCli(string $basePath, string $phpBinary, string $only, bool $runSeed): array
{
    $lines = [];
    $ok = true;

    $commandSets = [
        'migrate' => array_merge(
            [
                ['migrate:status', []],
                ['migrate', ['force' => true]],
                ['stories:sync-episode-status', []],
            ],
            $runSeed ? [
                ['db:seed', ['class' => 'Database\\Seeders\\RolePermissionSeeder', 'force' => true]],
            ] : []
        ),
        'seed' => [
            ['db:seed', ['class' => 'Database\\Seeders\\RolePermissionSeeder', 'force' => true]],
        ],
        'cache_rebuild' => [
            ['config:clear', []],
            ['clear-compiled', []],
            ['config:cache', []],
            ['route:cache', []],
            ['view:cache', []],
            ['optimize', []],
            ['storage:link', ['force' => true]],
        ],
    ];

    foreach ($commandSets[$only] ?? [] as [$command, $args]) {
        $result = deployRunArtisanViaCli($basePath, $phpBinary, $command, $args);
        $label = $command === 'migrate' ? 'php artisan migrate --force' : 'php artisan ' . $command;

        if ($result['code'] !== 0) {
            $ok = false;
            $lines[] = $label . ' FAILED (CLI exit ' . $result['code'] . ')'
                . ($result['output'] !== '' ? ': ' . $result['output'] : '');

            if ($command === 'migrate') {
                break;
            }

            continue;
        }

        $suffix = $result['output'] !== '' ? ': ' . $result['output'] : '';
        $lines[] = $label . ' OK (CLI)' . $suffix;
    }

    return ['ok' => $ok, 'lines' => $lines];
}

/**
 * @return array{ok: bool, lines: string[]}
 */
function deployFileOnlyCacheRebuild(string $basePath): array
{
    $lines = [];
    $link = $basePath . '/public/storage';

    if (is_link($link) || is_dir($link)) {
        $lines[] = 'storage:link skipped (link already exists)';
    } elseif (@symlink('../storage/app/public', $link)) {
        $lines[] = 'storage:link OK (manual symlink)';
    } else {
        $saved = getcwd();
        @chdir($basePath . '/public');
        if (@symlink('../storage/app/public', 'storage')) {
            $lines[] = 'storage:link OK (manual symlink fallback)';
        } else {
            $lines[] = 'Warn: could not create public/storage symlink';
        }
        @chdir($saved ?: $basePath);
    }

    $lines[] = 'Cache rebuild OK (file-only fallback — bootstrap cache already cleared; skipped config/route/view cache)';
    $lines[] = 'Enable dom + xml in cPanel PHP extensions for full artisan cache rebuild';

    return ['ok' => true, 'lines' => $lines];
}
