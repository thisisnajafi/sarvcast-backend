<?php

declare(strict_types=1);

function deployRunShellCommand(string $command): array
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

/**
 * @return array{ok: bool, method: string, error: ?string}
 */
function deployExtractZipToDirectory(string $zipPath, string $destDir): array
{
    if (! is_file($zipPath)) {
        return ['ok' => false, 'method' => 'none', 'error' => 'zip file missing'];
    }

    if (! is_dir($destDir) && ! @mkdir($destDir, 0755, true)) {
        return ['ok' => false, 'method' => 'none', 'error' => 'unable to create destination directory'];
    }

    if (class_exists('ZipArchive')) {
        $zip = new ZipArchive();
        $opened = $zip->open($zipPath);
        if ($opened === true) {
            $extracted = $zip->extractTo($destDir);
            $zip->close();
            if ($extracted) {
                return ['ok' => true, 'method' => 'ZipArchive', 'error' => null];
            }

            return ['ok' => false, 'method' => 'ZipArchive', 'error' => 'ZipArchive::extractTo failed'];
        }

        return ['ok' => false, 'method' => 'ZipArchive', 'error' => 'ZipArchive::open failed (code ' . $opened . ')'];
    }

    if (class_exists('PharData')) {
        try {
            $phar = new PharData($zipPath);
            $phar->extractTo($destDir, null, true);

            return ['ok' => true, 'method' => 'PharData', 'error' => null];
        } catch (Throwable $e) {
            $pharError = $e->getMessage();
        }
    } else {
        $pharError = 'PharData unavailable';
    }

    $command = 'unzip -o ' . escapeshellarg($zipPath) . ' -d ' . escapeshellarg($destDir);
    $shell = deployRunShellCommand($command);
    if ($shell['code'] === 0) {
        return ['ok' => true, 'method' => 'unzip', 'error' => null];
    }

    $shellError = $shell['output'] !== '' ? $shell['output'] : 'unzip command failed';

    return [
        'ok' => false,
        'method' => 'none',
        'error' => trim('ZipArchive unavailable; ' . ($pharError ?? 'Phar failed') . '; ' . $shellError),
    ];
}

function deployExtractVendorZipToDirectory(string $zipPath, string $vendorDir): ?string
{
    $result = deployExtractZipToDirectory($zipPath, $vendorDir);
    if (! $result['ok']) {
        return $result['error'] ?? 'vendor zip extraction failed';
    }

    if (! is_file($vendorDir . '/autoload.php')) {
        return 'vendor/autoload.php missing after ' . $result['method'] . ' extraction';
    }

    return null;
}
