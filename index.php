<?php

/**
 * Fallback front controller when the host document root is the Laravel project root.
 * Apache/LiteSpeed should prefer this via DirectoryIndex before hitting public/ as a directory.
 */
require __DIR__ . '/public/index.php';
