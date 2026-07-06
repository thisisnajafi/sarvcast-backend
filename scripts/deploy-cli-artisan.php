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

function deployCliPhpSupportsMysql(string $phpBinary): bool
{
    $cmd = escapeshellarg($phpBinary)
        . " -r \"echo (class_exists('PDO') && in_array('mysql', PDO::getAvailableDrivers(), true)) ? 'yes' : 'no';\"";

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
    $parts = [
        'cd ' . escapeshellarg($basePath),
        '&&',
        escapeshellarg($phpBinary),
        escapeshellarg($basePath . '/artisan'),
        $command,
    ];

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
