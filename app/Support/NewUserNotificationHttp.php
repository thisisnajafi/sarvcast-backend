<?php

namespace App\Support;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/**
 * Short HTTP deadline for the synchronous new-user admin push.
 * A hung FCM or OAuth call must not stall registration.
 */
final class NewUserNotificationHttp
{
    public const TIMEOUT_SECONDS = 5;

    public static function client(): PendingRequest
    {
        return Http::timeout(self::TIMEOUT_SECONDS)
            ->connectTimeout(self::TIMEOUT_SECONDS);
    }
}
