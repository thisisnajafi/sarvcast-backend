<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Myket in-app purchase verification (server-to-server).
 *
 * Partners API (Myket panel):
 *   POST https://developer.myket.ir/api/partners/applications/{PACKAGE_NAME}/purchases/products/{SKU_ID}/verify
 *   Header: X-Access-Token
 *   Body: { "tokenId": "<purchase_token>" }
 *
 * @see https://myket.ir/kb/pages/server-to-server-payment-validation-api/
 */
class MyketService
{
    public const ERROR_API_KEY_NOT_CONFIGURED = 'myket_config_missing';

    protected string $packageName;
    protected ?string $accessToken;
    protected string $partnersApiBaseUrl;
    protected SubscriptionService $subscriptionService;

    public function __construct(SubscriptionService $subscriptionService)
    {
        $this->packageName = config('services.myket.package_name', 'com.sarvabi.sarvcast');
        $this->accessToken = config('services.myket.api_key');
        $this->partnersApiBaseUrl = rtrim(
            config('services.myket.api_base_url', 'https://developer.myket.ir/api/partners/applications'),
            '/'
        );
        $this->subscriptionService = $subscriptionService;
    }

    /**
     * Verify Myket in-app purchase via partners API.
     *
     * @param string $purchaseToken Purchase token (tokenId) from Myket client SDK
     * @param string $productId Product/SKU ID configured in Myket panel
     * @return array
     */
    public function verifyPurchase(string $purchaseToken, string $productId): array
    {
        try {
            if (empty($this->accessToken)) {
                Log::error('Myket verifyPurchase: X-Access-Token not configured');
                return [
                    'success' => false,
                    'message' => 'پیکربندی مایکت ناقص است (X-Access-Token)',
                    'error_code' => self::ERROR_API_KEY_NOT_CONFIGURED,
                ];
            }

            $url = $this->buildProductVerifyUrl($productId);

            Log::info('Verifying Myket purchase', [
                'package_name' => $this->packageName,
                'product_id' => $productId,
                'verify_url' => $url,
                'purchase_token' => substr($purchaseToken, 0, 20) . '...',
            ]);

            $response = Http::timeout(25)
                ->connectTimeout(10)
                ->withHeaders([
                    'X-Access-Token' => $this->accessToken,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post($url, [
                    'tokenId' => $purchaseToken,
                ]);

            if ($response->successful()) {
                $result = $response->json();

                Log::info('Myket verification response', [
                    'product_id' => $productId,
                    'purchase_state' => $result['purchaseState'] ?? null,
                    'consumption_state' => $result['consumptionState'] ?? null,
                ]);

                // purchaseState: 0 = purchased, 1 = failed
                if (isset($result['purchaseState']) && (int) $result['purchaseState'] === 0) {
                    return [
                        'success' => true,
                        'purchase_state' => 'purchased',
                        'order_id' => $result['orderId'] ?? null,
                        'purchase_time' => isset($result['purchaseTime'])
                            ? date('Y-m-d H:i:s', (int) ($result['purchaseTime'] / 1000))
                            : null,
                        'developer_payload' => $result['developerPayload'] ?? null,
                        'consumption_state' => $result['consumptionState'] ?? null,
                        'kind' => $result['kind'] ?? null,
                        'raw_response' => $result,
                    ];
                }

                $errorMessage = $result['translatedMessage']
                    ?? $result['message']
                    ?? $result['errorMessage']
                    ?? 'Unknown error';

                Log::error('Myket purchase verification failed', [
                    'product_id' => $productId,
                    'purchase_state' => $result['purchaseState'] ?? 'unknown',
                    'error_message' => $errorMessage,
                ]);

                return [
                    'success' => false,
                    'message' => 'تایید خرید ناموفق: ' . $errorMessage,
                    'error_code' => $result['purchaseState'] ?? null,
                ];
            }

            $body = $response->json();
            $errorMessage = is_array($body)
                ? ($body['translatedMessage'] ?? $body['message'] ?? $body['messageCode'] ?? 'خطا در ارتباط با مایکت')
                : 'خطا در ارتباط با مایکت';

            Log::error('Myket API request failed', [
                'product_id' => $productId,
                'status_code' => $response->status(),
                'response_body' => $response->body(),
            ]);

            return [
                'success' => false,
                'message' => $errorMessage,
                'status_code' => $response->status(),
            ];
        } catch (\Exception $e) {
            Log::error('Myket verification exception', [
                'product_id' => $productId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'خطا در تایید خرید: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Build partners product verify URL for a SKU.
     */
    public function buildProductVerifyUrl(string $productId): string
    {
        $encodedProductId = rawurlencode($productId);

        return "{$this->partnersApiBaseUrl}/{$this->packageName}/purchases/products/{$encodedProductId}/verify";
    }

    /**
     * Map product ID to subscription type slug.
     *
     * @param string $productId Product ID from Myket
     * @return string|null Subscription type (1month, 3months, etc.)
     */
    public function mapProductIdToSubscriptionType(string $productId): ?string
    {
        $mapping = config('services.myket.product_mapping', []);

        return $mapping[$productId] ?? null;
    }

    /**
     * Verify with Myket partners API, then create/update unified payment + subscription.
     * Mirrors CafeBazaarService::verifyAndFulfillSubscription for the myket flavor.
     *
     * @return array{success: bool, message?: string, payment?: Payment, subscription?: Subscription, is_duplicate?: bool, error_code?: string}
     */
    public function verifyAndFulfillSubscription(User $user, string $purchaseToken, string $productId, ?string $orderId = null): array
    {
        Log::info('Myket verifyAndFulfillSubscription: start', [
            'user_id' => $user->id,
            'product_id' => $productId,
            'order_id' => $orderId,
        ]);

        $lockKey = 'myket_verify_' . md5($purchaseToken);
        $lock = Cache::lock($lockKey, 30);

        if (!$lock->get()) {
            return [
                'success' => false,
                'message' => 'در حال پردازش این خرید هستیم. لطفاً چند ثانیه صبر کنید.',
            ];
        }

        try {
            return $this->verifyAndFulfillSubscriptionUnderLock($user, $purchaseToken, $productId, $orderId);
        } finally {
            $lock->release();
        }
    }

    private function verifyAndFulfillSubscriptionUnderLock(User $user, string $purchaseToken, string $productId, ?string $orderId): array
    {
        $existingPayment = Payment::where('purchase_token', $purchaseToken)
            ->where('billing_platform', 'myket')
            ->first();

        if ($existingPayment) {
            $existingPayment->load('subscription');
            if ($existingPayment->subscription && $existingPayment->subscription->id) {
                $sub = $this->ensureSubscriptionGrantsAccess($existingPayment->subscription);
                return [
                    'success' => true,
                    'payment' => $existingPayment->fresh()->load('subscription'),
                    'subscription' => $sub->fresh(),
                    'is_duplicate' => true,
                ];
            }
        }

        $verification = $this->verifyPurchase($purchaseToken, $productId);
        if (!$verification['success']) {
            return [
                'success' => false,
                'message' => $verification['message'] ?? 'تایید خرید ناموفق بود',
                'error_code' => $verification['error_code'] ?? null,
            ];
        }

        $plan = SubscriptionPlan::where('myket_product_id', $productId)->first();
        if (!$plan) {
            $subscriptionType = $this->mapProductIdToSubscriptionType($productId);
            if ($subscriptionType) {
                $plan = SubscriptionPlan::where('slug', $subscriptionType)->first();
            }
        }
        if (!$plan) {
            return [
                'success' => false,
                'message' => 'شناسه محصول نامعتبر است: ' . $productId,
            ];
        }

        $planPrice = $plan->getFinalPriceForFlavor('myket') ?? (float) $plan->final_price;
        $transactionId = $orderId ?? $verification['order_id'] ?? 'MK_' . time() . '_' . rand(1000, 9999);

        DB::beginTransaction();
        try {
            if (!$existingPayment) {
                $payment = Payment::create([
                    'user_id' => $user->id,
                    'amount' => $planPrice,
                    'currency' => $plan->currency ?? 'IRR',
                    'payment_method' => 'in_app_purchase',
                    'payment_gateway' => 'myket',
                    'billing_platform' => 'myket',
                    'status' => 'completed',
                    'transaction_id' => $transactionId,
                    'purchase_token' => $purchaseToken,
                    'order_id' => $verification['order_id'] ?? $orderId,
                    'product_id' => $productId,
                    'package_name' => $this->packageName,
                    'purchase_state' => $verification['purchase_state'] ?? 'purchased',
                    'purchase_time' => $verification['purchase_time'] ?? now(),
                    'store_response' => $verification['raw_response'] ?? null,
                    'is_acknowledged' => true,
                    'acknowledged_at' => now(),
                    'processed_at' => now(),
                    'payment_metadata' => [
                        'verification_time' => now()->toISOString(),
                        'developer_payload' => $verification['developer_payload'] ?? null,
                        'consumption_state' => $verification['consumption_state'] ?? null,
                        'billing_platform' => 'myket',
                    ],
                ]);
            } else {
                $payment = $existingPayment;
            }

            $existingSubscription = $this->subscriptionService->getActiveSubscription($user->id);
            if ($existingSubscription && $existingSubscription->billing_platform === 'myket') {
                $currentEndDate = Carbon::parse($existingSubscription->end_date);
                $newEndDate = $currentEndDate->copy()->addDays($plan->duration_days);
                $existingSubscription->update([
                    'end_date' => $newEndDate,
                    'price' => $planPrice,
                    'currency' => $plan->currency ?? 'IRR',
                    'status' => 'active',
                    'store_subscription_id' => $verification['order_id'] ?? $orderId,
                    'store_metadata' => array_merge($existingSubscription->store_metadata ?? [], [
                        'last_purchase_token' => $purchaseToken,
                        'last_product_id' => $productId,
                        'last_verification_time' => now()->toISOString(),
                    ]),
                ]);
                $subscription = $existingSubscription->fresh();
            } else {
                $startDate = now();
                $endDate = Carbon::parse($startDate)->addDays($plan->duration_days);
                $subscription = Subscription::create([
                    'user_id' => $user->id,
                    'type' => $plan->slug,
                    'status' => 'active',
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'price' => $planPrice,
                    'currency' => $plan->currency ?? 'IRR',
                    'auto_renew' => false,
                    'payment_method' => 'in_app_purchase',
                    'transaction_id' => $payment->transaction_id,
                    'billing_platform' => 'myket',
                    'store_subscription_id' => $verification['order_id'] ?? $orderId,
                    'store_metadata' => [
                        'purchase_token' => $purchaseToken,
                        'product_id' => $productId,
                        'verification_time' => now()->toISOString(),
                    ],
                ]);
            }

            $payment->update(['subscription_id' => $subscription->id]);

            DB::commit();

            Log::info('Myket verifyAndFulfillSubscription: success', [
                'user_id' => $user->id,
                'payment_id' => $payment->id,
                'subscription_id' => $subscription->id,
            ]);

            return [
                'success' => true,
                'payment' => $payment->fresh()->load('subscription'),
                'subscription' => $subscription->fresh(),
                'is_duplicate' => false,
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Myket verifyAndFulfillSubscription: exception', [
                'user_id' => $user->id,
                'product_id' => $productId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    private function ensureSubscriptionGrantsAccess(Subscription $subscription): Subscription
    {
        if ($subscription->end_date
            && Carbon::parse($subscription->end_date)->isFuture()
            && !in_array($subscription->status, ['active', 'trial'], true)) {
            $subscription->update(['status' => 'active']);
            return $subscription->fresh();
        }

        return $subscription;
    }
}
