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

function deployExtractVendorZipToDirectory(string $zipPath, string $vendorDir): ?string
{
    if (! is_file($zipPath)) {
        return 'vendor.zip not found';
    }

    if (! is_dir($vendorDir) && ! @mkdir($vendorDir, 0755, true)) {
        return 'unable to create vendor directory';
    }

    if (class_exists(ZipArchive::class)) {
        $zip = new ZipArchive();
        $opened = $zip->open($zipPath);
        if ($opened === true) {
            if ($zip->extractTo($vendorDir)) {
                $zip->close();

                return is_file($vendorDir . '/autoload.php')
                    ? null
                    : 'vendor/autoload.php missing after ZipArchive extraction';
            }

            $zip->close();

            return 'ZipArchive::extractTo failed';
        }

        return 'ZipArchive::open failed (code ' . $opened . ')';
    }

    if (class_exists(Phar::class) && class_exists(PharData::class)) {
        try {
            $phar = new PharData($zipPath);
            $phar->extractTo($vendorDir, null, true);

            return is_file($vendorDir . '/autoload.php')
                ? null
                : 'vendor/autoload.php missing after PharData extraction';
        } catch (Throwable $e) {
            $pharError = $e->getMessage();
        }
    } else {
        $pharError = 'PharData unavailable';
    }

    $command = 'unzip -o ' . escapeshellarg($zipPath) . ' -d ' . escapeshellarg($vendorDir);
    $shell = deployRunShellCommand($command);
    if ($shell['code'] === 0 && is_file($vendorDir . '/autoload.php')) {
        return null;
    }

    $details = $shell['output'] !== '' ? $shell['output'] : 'shell unzip failed';

    return trim('ZipArchive unavailable; Phar: ' . ($pharError ?? 'n/a') . '; unzip: ' . $details);
}
