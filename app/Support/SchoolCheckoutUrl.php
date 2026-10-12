<?php

namespace App\Support;

/**
 * Return URLs for a school-manager Zarinpal checkout.
 * Only the admin dashboard origin is allowed, so the callback cannot be sent elsewhere.
 */
final class SchoolCheckoutUrl
{
    public static function base(): string
    {
        $base = rtrim((string) config('app.admin_dashboard_url'), '/');

        return $base !== '' ? $base : 'https://admin.manjiapp.ir';
    }

    public static function memberUrl(int $userId, string $result): string
    {
        $flag = $result === 'ok' ? 'ok' : 'failed';

        return self::base().'/school-panel/members/'.$userId.'?payment='.$flag;
    }

    public static function sanitize(?string $url, string $fallback): string
    {
        if ($url === null || $url === '') {
            return $fallback;
        }

        $parsed = parse_url($url);
        if (! is_array($parsed) || empty($parsed['scheme']) || empty($parsed['host'])) {
            return $fallback;
        }

        $scheme = strtolower((string) $parsed['scheme']);
        $host = strtolower((string) $parsed['host']);
        $local = in_array($host, ['localhost', '127.0.0.1'], true);

        if ($local && in_array($scheme, ['http', 'https'], true)) {
            return $url;
        }

        if ($scheme !== 'https') {
            return $fallback;
        }

        $allowedHost = strtolower((string) parse_url(self::base(), PHP_URL_HOST));
        if ($allowedHost === '' || $host !== $allowedHost) {
            return $fallback;
        }

        return $url;
    }

    public static function paymentUrl(string $authority): string
    {
        $sandbox = (bool) config('services.zarinpal.sandbox', false);
        $base = $sandbox
            ? 'https://sandbox.zarinpal.com/pg/StartPay/'
            : 'https://www.zarinpal.com/pg/StartPay/';

        return $base.$authority;
    }
}
