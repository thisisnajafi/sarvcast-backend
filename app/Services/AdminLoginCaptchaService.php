<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Self-hosted admin-login captcha. The answer stays in cache, expires, and is single use.
 * No Google reCAPTCHA. An image is sent when GD is available; otherwise a math question.
 */
class AdminLoginCaptchaService
{
    public const TTL_SECONDS = 180;

    public const IP_LIMIT = 30;

    public const PHONE_LIMIT = 8;

    public const LIMIT_DECAY_SECONDS = 600;

    /**
     * @return array{token: string, answer: string, image: ?string, question: ?string, prompt: string, expires_in: int}
     */
    public function createChallenge(): array
    {
        $left = random_int(2, 9);
        $right = random_int(2, 9);
        $answer = (string) ($left + $right);
        $token = Str::random(40);
        $image = $this->renderImage($left, $right);

        Cache::put($this->cacheKey($token), [
            'answer_hash' => hash('sha256', $answer),
            'expires_at' => now()->addSeconds(self::TTL_SECONDS)->getTimestamp(),
        ], self::TTL_SECONDS);

        return [
            'token' => $token,
            'answer' => $answer,
            'image' => $image,
            'question' => $image === null ? $this->toPersianDigits($left).' + '.$this->toPersianDigits($right) : null,
            'prompt' => $image === null
                ? 'حاصل جمع را وارد کنید.'
                : 'حاصل عبارت تصویر را وارد کنید.',
            'expires_in' => self::TTL_SECONDS,
        ];
    }

    /**
     * @return array{limited: true}|array{limited: false, token: string, answer: string, image: ?string, question: ?string, prompt: string, expires_in: int}
     */
    public function issue(string $ip, string $phone): array
    {
        if ($this->tooManyAttempts($ip, $phone)) {
            return ['limited' => true];
        }

        $this->hit($ip, $phone);

        return ['limited' => false] + $this->createChallenge();
    }

    public function tooManyAttempts(string $ip, string $phone): bool
    {
        return RateLimiter::tooManyAttempts($this->ipKey($ip), self::IP_LIMIT)
            || RateLimiter::tooManyAttempts($this->phoneKey($phone), self::PHONE_LIMIT);
    }

    public function hit(string $ip, string $phone): void
    {
        RateLimiter::hit($this->ipKey($ip), self::LIMIT_DECAY_SECONDS);
        RateLimiter::hit($this->phoneKey($phone), self::LIMIT_DECAY_SECONDS);
    }

    /**
     * Pulls the token so a second try fails even when the first answer was wrong.
     */
    public function consume(string $token, string $answer): bool
    {
        $payload = Cache::pull($this->cacheKey($token));
        if (! is_array($payload)) {
            return false;
        }

        $expiresAt = (int) ($payload['expires_at'] ?? 0);
        if ($expiresAt < now()->getTimestamp()) {
            return false;
        }

        $expected = (string) ($payload['answer_hash'] ?? '');
        $given = hash('sha256', $this->normalizeAnswer($answer));

        return $expected !== '' && hash_equals($expected, $given);
    }

    public function cacheKey(string $token): string
    {
        return 'admin_login_captcha:'.$token;
    }

    public function normalizeAnswer(string $answer): string
    {
        $map = [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ];

        return strtr(trim($answer), $map);
    }

    private function ipKey(string $ip): string
    {
        return 'admin-login-captcha:ip:'.$ip;
    }

    private function phoneKey(string $phone): string
    {
        return 'admin-login-captcha:phone:'.$phone;
    }

    private function toPersianDigits(int $value): string
    {
        return strtr((string) $value, [
            '0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴',
            '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹',
        ]);
    }

    private function renderImage(int $left, int $right): ?string
    {
        if (! function_exists('imagecreatetruecolor')) {
            return null;
        }

        $image = imagecreatetruecolor(220, 72);
        if ($image === false) {
            return null;
        }

        $background = imagecolorallocate($image, 245, 247, 250);
        $ink = imagecolorallocate($image, 24, 24, 27);
        $noise = imagecolorallocate($image, 160, 160, 170);
        imagefilledrectangle($image, 0, 0, 219, 71, $background);

        for ($i = 0; $i < 6; $i++) {
            imageline(
                $image,
                random_int(0, 220),
                random_int(0, 72),
                random_int(0, 220),
                random_int(0, 72),
                $noise,
            );
        }

        $text = $left.' + '.$right.' = ?';
        imagestring($image, 5, 48, 28, $text, $ink);

        ob_start();
        imagepng($image);
        $binary = ob_get_clean();
        imagedestroy($image);

        if (! is_string($binary) || $binary === '') {
            return null;
        }

        return 'data:image/png;base64,'.base64_encode($binary);
    }
}
