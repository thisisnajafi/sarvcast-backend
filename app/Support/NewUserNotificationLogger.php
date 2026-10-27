<?php

namespace App\Support;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Dedicated trace log for new-user notification delivery.
 * Writes only to the new_user_notifications channel and never includes secrets.
 */
class NewUserNotificationLogger
{
    public const CHANNEL = 'new_user_notifications';

    private const SENSITIVE_KEY_FRAGMENTS = [
        'password',
        'secret',
        'authorization',
        'api_key',
        'apikey',
        'phone',
        'email',
        'verification_code',
        'otp',
        'credential',
        'fcm_token',
        'access_token',
        'server_key',
    ];

    private static int $traceDepth = 0;

    public static function within(callable $callback): mixed
    {
        self::$traceDepth++;
        try {
            return $callback();
        } finally {
            self::$traceDepth--;
        }
    }

    public static function isTracing(): bool
    {
        return self::$traceDepth > 0;
    }

    /**
     * @param array<string, mixed> $context
     */
    public static function debug(string $step, array $context = []): void
    {
        self::write('debug', $step, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    public static function warning(string $step, array $context = []): void
    {
        self::write('warning', $step, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    public static function error(string $step, array $context = []): void
    {
        self::write('error', $step, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    public static function write(string $level, string $step, array $context = []): void
    {
        try {
            $payload = self::sanitize(array_merge($context, [
                'step' => $step,
            ]));
            Log::channel(self::CHANNEL)->{$level}($step, $payload);
        } catch (Throwable $throwable) {
            // A logging failure must not change notification delivery.
        }
    }

    /**
     * @return array{queued: bool, queue_connection: string|null, queue: string|null}
     */
    public static function queueContext(object $listener): array
    {
        $queued = $listener instanceof ShouldQueue;
        if (! $queued) {
            return [
                'queued' => false,
                'queue_connection' => null,
                'queue' => null,
            ];
        }
        $connection = property_exists($listener, 'connection') ? $listener->connection : null;
        $queue = property_exists($listener, 'queue') ? $listener->queue : null;

        return [
            'queued' => true,
            'queue_connection' => is_string($connection) && $connection !== ''
                ? $connection
                : (string) config('queue.default'),
            'queue' => is_string($queue) && $queue !== '' ? $queue : null,
        ];
    }

    /**
     * @return array{exception_class: class-string<Throwable>, exception_message: string}
     */
    public static function exceptionContext(Throwable $throwable): array
    {
        return [
            'exception_class' => $throwable::class,
            'exception_message' => self::redact($throwable->getMessage()),
        ];
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    private static function sanitize(array $context): array
    {
        $clean = [];
        foreach ($context as $key => $value) {
            if (is_string($key) && self::isSensitiveKey($key)) {
                continue;
            }
            if (is_array($value)) {
                $clean[$key] = self::sanitize($value);
                continue;
            }
            if (is_string($value)) {
                $clean[$key] = self::redact($value);
                continue;
            }
            $clean[$key] = $value;
        }

        return $clean;
    }

    private static function isSensitiveKey(string $key): bool
    {
        $normalized = strtolower($key);
        if (in_array($normalized, ['token_count', 'queued'], true)) {
            return false;
        }
        if ($normalized === 'token' || str_ends_with($normalized, '_token') || str_contains($normalized, 'password')) {
            return true;
        }
        foreach (self::SENSITIVE_KEY_FRAGMENTS as $fragment) {
            if (str_contains($normalized, $fragment)) {
                return true;
            }
        }

        return false;
    }

    private static function redact(string $value): string
    {
        $redacted = preg_replace('/bot\d+:[A-Za-z0-9_-]+/', 'bot[redacted]', $value) ?? $value;
        $redacted = preg_replace('/Bearer\s+\S+/i', 'Bearer [redacted]', $redacted) ?? $redacted;
        $redacted = preg_replace('/key=\S+/i', 'key=[redacted]', $redacted) ?? $redacted;
        $redacted = preg_replace('/(password|secret|token)\s*[=:]\s*\S+/i', '$1=[redacted]', $redacted) ?? $redacted;

        return $redacted;
    }
}
