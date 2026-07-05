<?php

declare(strict_types=1);

/**
 * iconv must exist before Composer autoload — symfony/polyfill-mbstring calls iconv()
 * from a namespace; without global iconv(), PHP fatals or recurses through mb polyfill.
 *
 * Do NOT load polyfill-mbstring here; Composer autoload handles it after iconv is ready.
 */
$vendorDir = dirname(__DIR__) . '/vendor';

foreach ([
    'symfony/polyfill-iconv/bootstrap.php',
] as $bootstrap) {
    $file = $vendorDir . '/' . $bootstrap;
    if (is_file($file)) {
        require_once $file;
    }
}

if (! extension_loaded('iconv') && ! function_exists('iconv')) {
    /**
     * Use only the native mbstring extension — never polyfilled mb_* (those call iconv() again).
     */
    function iconv(?string $from_encoding, ?string $to_encoding, $string): string|false
    {
        if ($string === null || $string === false) {
            return false;
        }

        $from = preg_replace('#//.*$#', '', (string) $from_encoding);
        $to = preg_replace('#//.*$#', '', (string) $to_encoding);
        $payload = (string) $string;

        if (extension_loaded('mbstring')) {
            $result = @mb_convert_encoding($payload, $to, $from);

            return $result === false ? false : $result;
        }

        if (strcasecmp($from, $to) === 0) {
            return $payload;
        }

        if (strcasecmp($from, 'UTF-8') === 0 && strcasecmp($to, 'UTF-8') === 0) {
            return $payload;
        }

        return $payload;
    }

    if (! function_exists('iconv_strlen')) {
        function iconv_strlen(?string $string, ?string $encoding = null): int|false
        {
            if ($string === null) {
                return false;
            }

            if (extension_loaded('mbstring')) {
                return mb_strlen($string, $encoding ?: 'UTF-8');
            }

            return strlen($string);
        }
    }

    if (! function_exists('iconv_strpos')) {
        function iconv_strpos(?string $haystack, ?string $needle, int $offset = 0, ?string $encoding = null): int|false
        {
            if ($haystack === null || $needle === null) {
                return false;
            }

            if (extension_loaded('mbstring')) {
                return mb_strpos($haystack, $needle, $offset, $encoding ?: 'UTF-8');
            }

            return strpos($haystack, $needle, $offset);
        }
    }

    if (! function_exists('iconv_strrpos')) {
        function iconv_strrpos(?string $haystack, ?string $needle, int $offset = 0, ?string $encoding = null): int|false
        {
            if ($haystack === null || $needle === null) {
                return false;
            }

            if (extension_loaded('mbstring')) {
                return mb_strrpos($haystack, $needle, $offset, $encoding ?: 'UTF-8');
            }

            return strrpos($haystack, $needle, $offset);
        }
    }

    if (! function_exists('iconv_substr')) {
        function iconv_substr(?string $string, int $offset, ?int $length = null, ?string $encoding = null): string|false
        {
            if ($string === null) {
                return false;
            }

            if (extension_loaded('mbstring')) {
                return mb_substr($string, $offset, $length, $encoding ?: 'UTF-8');
            }

            return substr($string, $offset, $length ?? PHP_INT_MAX);
        }
    }

    if (! function_exists('iconv_mime_decode')) {
        function iconv_mime_decode(?string $string, int $mode = 0, ?string $encoding = null): string|false
        {
            if ($string === null) {
                return false;
            }

            if (extension_loaded('mbstring')) {
                return mb_decode_mimeheader($string);
            }

            return $string;
        }
    }
}
