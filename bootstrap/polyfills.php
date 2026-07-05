<?php

declare(strict_types=1);

$vendorDir = dirname(__DIR__) . '/vendor';

foreach ([
    'symfony/polyfill-iconv/bootstrap.php',
    'symfony/polyfill-mbstring/bootstrap.php',
] as $bootstrap) {
    $file = $vendorDir . '/' . $bootstrap;
    if (is_file($file)) {
        require_once $file;
    }
}
