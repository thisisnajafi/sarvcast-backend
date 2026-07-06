<?php

declare(strict_types=1);

/**
 * Best-effort load of missing PHP extensions on shared hosting (LiteSpeed web SAPI).
 * CLI ea-php82 often has pdo_mysql; web PHP may not — dl() works only when enable_dl is on.
 */
function hostingTryLoadExtension(string $extension): bool
{
    if (extension_loaded($extension)) {
        return true;
    }

    if (! function_exists('dl')) {
        return false;
    }

    if (ini_get('enable_dl') != '1' && ini_get('enable_dl') !== true && ini_get('enable_dl') !== 'On') {
        return false;
    }

    $dir = ini_get('extension_dir');
    if (! is_string($dir) || $dir === '') {
        return false;
    }

    $dir = rtrim($dir, '/\\');
    foreach ([$extension . '.so', 'php_' . $extension . '.so'] as $file) {
        $path = $dir . DIRECTORY_SEPARATOR . $file;
        if (! is_file($path)) {
            continue;
        }

        @dl($path);

        if (extension_loaded($extension)) {
            return true;
        }
    }

    return false;
}

foreach ([
    'mysqlnd',
    'pdo',
    'pdo_mysql',
    'nd_pdo_mysql',
    'mbstring',
    'dom',
    'xml',
    'curl',
    'fileinfo',
    'tokenizer',
] as $extension) {
    hostingTryLoadExtension($extension);
}
