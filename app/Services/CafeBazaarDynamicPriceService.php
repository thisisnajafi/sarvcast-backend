<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Issues CafeBazaar dynamic-price (تخفیف پویا) tokens.
 *
 * Token format follows CafeBazaar guidance: HS256 JWT whose payload contains
 * the final charge amount in Rials. Confirm claim name against the official
 * docs if Bazaar rejects tokens in production.
 *
 * @see https://developers.cafebazaar.ir/fa/guidelines/in-app-billing/dynamic-discount/
 */
class CafeBazaarDynamicPriceService
{
    public function isConfigured(): bool
    {
        $key = (string) config('services.cafebazaar.dynamic_price_key', '');

        return $key !== '';
    }

    /**
     * Build a short-lived dynamicPriceToken for Poolakey.
     *
     * @param  int  $amountRials  Final price CafeBazaar should charge (Rials)
     */
    public function createToken(int $amountRials, ?string $productId = null, int $ttlSeconds = 900): string
    {
        if ($amountRials < 1) {
            throw new RuntimeException('Dynamic price amount must be at least 1 Rial');
        }

        $key = (string) config('services.cafebazaar.dynamic_price_key', '');
        if ($key === '') {
            throw new RuntimeException('CAFEBAZAAR_DYNAMIC_PRICE_KEY is not configured');
        }

        $now = time();
        $payload = [
            'amount' => $amountRials,
            'iat' => $now,
            'exp' => $now + max(60, $ttlSeconds),
        ];

        if ($productId !== null && $productId !== '') {
            $payload['productId'] = $productId;
        }

        $token = $this->encodeHs256Jwt($payload, $key);

        Log::info('CafeBazaar dynamic price token created', [
            'amount_rials' => $amountRials,
            'product_id' => $productId,
            'expires_at' => date('c', $payload['exp']),
        ]);

        return $token;
    }

    /**
     * Convert a Manji plan amount into the integer Rials value Bazaar expects.
     */
    public function toRials(float $amount): int
    {
        $factor = (float) config('services.cafebazaar.dynamic_price_amount_factor', 1);

        return (int) max(1, round($amount * $factor));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function encodeHs256Jwt(array $payload, string $secret): string
    {
        $header = ['alg' => 'HS256', 'typ' => 'JWT'];
        $segments = [
            $this->base64UrlEncode(json_encode($header, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)),
            $this->base64UrlEncode(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)),
        ];
        $signingInput = implode('.', $segments);
        $signature = hash_hmac('sha256', $signingInput, $secret, true);
        $segments[] = $this->base64UrlEncode($signature);

        return implode('.', $segments);
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
