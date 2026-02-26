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
 * Cafe Bazaar in-app purchase and subscription verification.
 *
 * Subscription (بررسی وضعیت اشتراک): GET
 *   https://pardakht.cafebazaar.ir/devapi/v2/api/applications/<package_name>/subscriptions/<subscription_id>/purchases/<purchase_token>
 * One-time purchase: POST https://pardakht.cafebazaar.ir/devapi/v2/api/validate/inapp/purchases/
 *   Body: packageName, productId, purchaseToken. Auth: Header CAFEBAZAAR-PISHKHAN-API-SECRET (Pishkhan).
 * order_id: When Bazaar does not return orderId, we use the client-supplied order_id.
 * Acknowledge: If the acknowledge endpoint returns 404, we log and treat verification as success (non-fatal).
 */
class CafeBazaarService
{
    /** Error code when API key is not configured (distinct from Bazaar rejection). */
    public const ERROR_API_KEY_NOT_CONFIGURED = 'cafebazaar_config_missing';

    /** Error code when Cafe Bazaar rejects the access token (expired or invalid). */
    public const ERROR_INVALID_CREDENTIALS = 'invalid_credentials';

    protected $packageName;
    protected $apiKey;
    protected $apiUrl;
    protected $subscriptionService;

    public function __construct(SubscriptionService $subscriptionService)
    {
        $this->packageName = config('services.cafebazaar.package_name', 'com.sarvabi.sarvcast');
        $this->apiKey = config('services.cafebazaar.api_key');
        // One-time in-app purchase: POST to validate/inapp/purchases/ (correct path; ignore wrong .env like /api/validate)
        $defaultPurchaseUrl = 'https://pardakht.cafebazaar.ir/devapi/v2/api/validate/inapp/purchases/';
        $configured = config('services.cafebazaar.api_url', $defaultPurchaseUrl);
        $this->apiUrl = str_contains($configured, 'inapp/purchases') ? rtrim($configured, '/') . '/' : $defaultPurchaseUrl;
        $this->subscriptionService = $subscriptionService;
    }

    /**
     * Verify CafeBazaar in-app purchase
     * 
     * @param string $purchaseToken Purchase token from CafeBazaar
     * @param string $productId Product/SKU ID
     * @return array
     */
    public function verifyPurchase(string $purchaseToken, string $productId): array
    {
        try {
            if (empty($this->apiKey)) {
                Log::error('CafeBazaar API key not configured');
                return [
                    'success' => false,
                    'message' => 'پیکربندی کافه‌بازار ناقص است (API key)',
                    'error_code' => self::ERROR_API_KEY_NOT_CONFIGURED,
                ];
            }

            $url = $this->apiUrl;
            Log::info('Verifying CafeBazaar purchase', [
                'package_name' => $this->packageName,
                'product_id' => $productId,
                'api_url' => $url,
                'purchase_token' => substr($purchaseToken, 0, 20) . '...'
            ]);

            $response = Http::timeout(25)->connectTimeout(10)->withHeaders([
                config('services.cafebazaar.api_header_name', 'CAFEBAZAAR-PISHKHAN-API-SECRET') => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($url, [
                'packageName' => $this->packageName,
                'productId' => $productId,
                'purchaseToken' => $purchaseToken,
            ]);

            if ($response->successful()) {
                $result = $response->json();
                // Documented response: consumptionState, purchaseState (0=normal, 1=refunded), purchaseTime (ms), developerPayload, kind
                $purchaseState = $result['purchaseState'] ?? 0;
                Log::info('CafeBazaar verification response', [
                    'product_id' => $productId,
                    'purchaseState' => $purchaseState,
                    'consumptionState' => $result['consumptionState'] ?? null,
                ]);

                if ($purchaseState === 0 || $purchaseState === '0') {
                    return [
                        'success' => true,
                        'purchase_state' => 'purchased',
                        'order_id' => $result['orderId'] ?? null,
                        'purchase_time' => isset($result['purchaseTime']) ? date('Y-m-d H:i:s', (int) $result['purchaseTime'] / 1000) : null,
                        'developer_payload' => $result['developerPayload'] ?? null,
                        'consumption_state' => $result['consumptionState'] ?? null,
                        'raw_response' => $result
                    ];
                }
                $errorMessage = $purchaseState === 1 || $purchaseState === '1'
                    ? 'خرید بازگشت داده شده است (Refund)'
                    : ('وضعیت خرید نامعتبر: ' . ($result['error_description'] ?? 'purchaseState=' . $purchaseState));
                Log::error('CafeBazaar purchase verification failed', [
                    'product_id' => $productId,
                    'purchaseState' => $purchaseState,
                    'error' => $result['error'] ?? null,
                ]);
                return [
                    'success' => false,
                    'message' => $errorMessage,
                    'error_code' => $purchaseState
                ];
            }

            $body = is_string($response->body()) && str_starts_with(trim($response->body()), '{')
                ? $response->json()
                : [];
            $errorMsg = is_array($body) ? ($body['error_description'] ?? $body['error'] ?? $body['message'] ?? null) : null;
            if (!is_string($errorMsg) || $errorMsg === '') {
                $errorMsg = $response->status() === 404
                    ? 'آدرس تایید خرید در کافه‌بازار یافت نشد (404). برای اشتراک از endpoint وضعیت اشتراک استفاده می‌شود.'
                    : 'خطا در ارتباط با کافه‌بازار';
            }
            Log::error('CafeBazaar API request failed', [
                'product_id' => $productId,
                'status_code' => $response->status(),
                'error' => $body['error'] ?? null,
                'response_body' => substr($response->body(), 0, 500),
            ]);
            return [
                'success' => false,
                'message' => $errorMsg,
                'status_code' => $response->status(),
                'error_code' => is_array($body) ? ($body['error'] ?? null) : null,
            ];
        } catch (\Exception $e) {
            Log::error('CafeBazaar verification exception', [
                'product_id' => $productId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در تایید خرید: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Verify CafeBazaar subscription (subscription status / purchase token).
     *
     * Documented: برای بررسی وضعیت اشتراک و خرید‌های اشتراک برنامه خود از این متد استفاده کنید:
     * GET https://pardakht.cafebazaar.ir/devapi/v2/api/applications/<package_name>/subscriptions/<subscription_id>/purchases/<purchase_token>
     * subscription_id = SKU اشتراک (e.g. 1-month-sub). purchase_token = token from Bazaar.
     *
     * @param string $purchaseToken Purchase token from Bazaar (or from a renewal)
     * @param string $subscriptionId Subscription SKU (e.g. 1-month-sub)
     * @return array
     */
    public function verifySubscription(string $purchaseToken, string $subscriptionId): array
    {
        try {
            if (empty($this->apiKey)) {
                Log::error('CafeBazaar API key not configured');
                return [
                    'success' => false,
                    'message' => 'پیکربندی کافه‌بازار ناقص است (API key)',
                    'error_code' => self::ERROR_API_KEY_NOT_CONFIGURED,
                ];
            }

            $base = rtrim(config('services.cafebazaar.api_base_url', 'https://pardakht.cafebazaar.ir/devapi/v2/api'), '/');
            $subscriptionUrl = $base . '/applications/'
                . rawurlencode($this->packageName) . '/subscriptions/'
                . rawurlencode($subscriptionId) . '/purchases/'
                . rawurlencode($purchaseToken);

            Log::info('Verifying CafeBazaar subscription', [
                'package_name' => $this->packageName,
                'subscription_id' => $subscriptionId,
                'api_url' => $base . '/applications/***/subscriptions/***/purchases/***',
            ]);

            $response = Http::timeout(25)->connectTimeout(10)->withHeaders([
                config('services.cafebazaar.api_header_name', 'CAFEBAZAAR-PISHKHAN-API-SECRET') => $this->apiKey,
                'Accept' => 'application/json',
            ])->get($subscriptionUrl);

            if ($response->successful()) {
                $result = $response->json();
                if (!is_array($result)) {
                    return [
                        'success' => false,
                        'message' => 'پاسخ نامعتبر از کافه‌بازار',
                    ];
                }
                $purchaseState = $result['purchaseState'] ?? $result['state'] ?? 0;
                Log::info('CafeBazaar subscription verification response', [
                    'subscription_id' => $subscriptionId,
                    'purchaseState' => $purchaseState,
                ]);

                if ($purchaseState === 0 || $purchaseState === '0' || $purchaseState === 'active' || $purchaseState === 'Active') {
                    $purchaseTime = $result['purchaseTime'] ?? $result['purchaseTimeMillis'] ?? null;
                    $expiryMillis = $result['expiryTimeMillis'] ?? $result['expiryTime'] ?? null;
                    return [
                        'success' => true,
                        'subscription_state' => 'active',
                        'purchase_state' => 'active',
                        'order_id' => $result['orderId'] ?? $result['order_id'] ?? null,
                        'purchase_time' => $purchaseTime ? date('Y-m-d H:i:s', (int) $purchaseTime / 1000) : null,
                        'expiry_time' => $expiryMillis ? date('Y-m-d H:i:s', (int) $expiryMillis / 1000) : null,
                        'auto_renewing' => $result['autoRenewing'] ?? $result['auto_renewing'] ?? false,
                        'raw_response' => $result,
                    ];
                }
                return [
                    'success' => false,
                    'message' => $result['error_description'] ?? $result['errorMessage'] ?? $result['message'] ?? 'تایید اشتراک ناموفق بود',
                ];
            }

            $body = is_string($response->body()) && str_starts_with(trim($response->body()), '{')
                ? $response->json()
                : [];
            $errorMsg = is_array($body) ? ($body['error_description'] ?? $body['error'] ?? $body['message'] ?? null) : null;
            if (!$errorMsg && $response->status() === 404) {
                $errorMsg = 'اشتراک یا توکن در کافه‌بازار یافت نشد (404)';
            }
            $errorCode = is_array($body) && isset($body['error']) ? $body['error'] : null;
            if ($response->status() === 403 && $errorCode === 'invalid_credentials') {
                $errorMsg = 'توکن دسترسی کافه‌بازار نامعتبر یا منقضی است. از پیشخوان توسعه‌دهنده (developers.cafebazaar.ir) توکن جدید بگیرید و CAFEBAZAAR_API_KEY را به‌روز کنید.';
                $errorCode = self::ERROR_INVALID_CREDENTIALS;
            }
            Log::error('CafeBazaar subscription API request failed', [
                'subscription_id' => $subscriptionId,
                'status_code' => $response->status(),
                'error' => $body['error'] ?? null,
                'response_body' => substr($response->body(), 0, 500),
            ]);
            return [
                'success' => false,
                'message' => is_string($errorMsg) ? $errorMsg : 'خطا در ارتباط با کافه‌بازار',
                'error_code' => $errorCode,
            ];
        } catch (\Exception $e) {
            Log::error('CafeBazaar subscription verification exception', [
                'subscription_id' => $subscriptionId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'خطا در تایید اشتراک: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Verify purchase with CafeBazaar: try subscription endpoint first, then one-time purchase.
     * Use this for subscription products so expiry and auto_renewing are set correctly.
     *
     * @param string $purchaseToken Purchase token from CafeBazaar
     * @param string $productId Product/Subscription ID
     * @return array Unified verification result (success, order_id, purchase_state, purchase_time, expiry_time, auto_renewing, raw_response, ...)
     */
    public function verifyPurchaseOrSubscription(string $purchaseToken, string $productId): array
    {
        $sub = $this->verifySubscription($purchaseToken, $productId);
        if ($sub['success']) {
            return $sub;
        }
        // Do not fall back to purchase endpoint when the failure is token/config (user must fix token).
        if (isset($sub['error_code']) && in_array($sub['error_code'], [self::ERROR_INVALID_CREDENTIALS, self::ERROR_API_KEY_NOT_CONFIGURED], true)) {
            return $sub;
        }
        $purchase = $this->verifyPurchase($purchaseToken, $productId);
        return $purchase;
    }

    /**
     * Acknowledge purchase (required for CafeBazaar)
     * 
     * @param string $purchaseToken Purchase token
     * @param string $productId Product ID
     * @return array
     */
    public function acknowledgePurchase(string $purchaseToken, string $productId): array
    {
        try {
            $acknowledgeUrl = config('services.cafebazaar.acknowledge_url',
                'https://pardakht.cafebazaar.ir/devapi/v2/api/acknowledge');

            $response = Http::timeout(15)->connectTimeout(8)->withHeaders([
                config('services.cafebazaar.api_header_name', 'CAFEBAZAAR-PISHKHAN-API-SECRET') => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($acknowledgeUrl, [
                'packageName' => $this->packageName,
                'productId' => $productId,
                'purchaseToken' => $purchaseToken,
            ]);

            if ($response->successful()) {
                $result = $response->json();
                if (isset($result['status']) && $result['status'] == 0) {
                    return ['success' => true];
                }
            }

            // Acknowledge endpoint may not exist or return different format; log but don't fail verification.
            // Support: 404 here means "acknowledge not called"; verification still succeeded.
            if ($response->status() === 404) {
                Log::warning('CafeBazaar acknowledge endpoint returned 404 (may be optional)', [
                    'acknowledge_url' => $acknowledgeUrl
                ]);
                return ['success' => true];
            }

            return ['success' => false, 'message' => 'خطا در تایید خرید'];
        } catch (\Exception $e) {
            Log::error('CafeBazaar acknowledge exception', [
                'error' => $e->getMessage()
            ]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Map product ID to subscription plan slug.
     * 
     * Looks up the cafebazaar_product_id in the subscription_plans table first,
     * then falls back to a hardcoded mapping for legacy product IDs.
     * 
     * @param string $productId Product ID from CafeBazaar (e.g. the cafebazaar_product_id value)
     * @return string|null Plan slug (1month, 3months, etc.)
     */
    public function mapProductIdToSubscriptionType(string $productId): ?string
    {
        // 1. Dynamic lookup: find plan by cafebazaar_product_id in the database
        $plan = \App\Models\SubscriptionPlan::where('cafebazaar_product_id', $productId)->first();
        if ($plan) {
            return $plan->slug;
        }

        // 2. Fallback: hardcoded mapping for legacy product IDs (subscription_xxx format)
        $mapping = config('services.cafebazaar.product_mapping', [
            'subscription_1month' => '1month',
            'subscription_3months' => '3months',
            'subscription_6months' => '6months',
            'subscription_1year' => '1year',
        ]);

        return $mapping[$productId] ?? null;
    }

    /**
     * Single flow: verify with Bazaar, find plan (DB cafebazaar_product_id first, then mapping), create/update payment and subscription, acknowledge.
     * Used by both CafeBazaarSubscriptionController and InAppPurchaseController so behavior stays in sync.
     *
     * @param User $user
     * @param string $purchaseToken
     * @param string $productId
     * @param string|null $orderId Client-supplied order_id; used when Bazaar does not return orderId (see class doc).
     * @return array{success: bool, message?: string, payment?: Payment, subscription?: Subscription, is_duplicate?: bool, acknowledged?: bool, error_code?: string}
     */
    public function verifyAndFulfillSubscription(User $user, string $purchaseToken, string $productId, ?string $orderId = null): array
    {
        $lockKey = 'cafebazaar_verify_' . md5($purchaseToken);
        $lock = Cache::lock($lockKey, 30);

        if (!$lock->get()) {
            Log::warning('CafeBazaar verifyAndFulfill: could not acquire lock', ['purchase_token_preview' => substr($purchaseToken, 0, 12) . '...']);
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

    /**
     * Core verification and fulfillment logic; must be called while holding the purchase_token lock.
     */
    private function verifyAndFulfillSubscriptionUnderLock(User $user, string $purchaseToken, string $productId, ?string $orderId): array
    {
        // 1. Idempotency (re-check inside lock; another request may have created it)
            $existingPayment = Payment::where('purchase_token', $purchaseToken)
                ->where('billing_platform', 'cafebazaar')
                ->first();

            if ($existingPayment) {
                Log::info('CafeBazaar verifyAndFulfill: idempotent return', [
                    'user_id' => $user->id,
                    'payment_id' => $existingPayment->id,
                    'purchase_token_preview' => substr($purchaseToken, 0, 12) . '...',
                ]);
                return [
                    'success' => true,
                    'payment' => $existingPayment->load('subscription'),
                    'subscription' => $existingPayment->subscription,
                    'is_duplicate' => true,
                ];
            }

            // 2. Verify with Bazaar (subscription first, then one-time purchase)
            $verification = $this->verifyPurchaseOrSubscription($purchaseToken, $productId);
            if (!$verification['success']) {
                return [
                    'success' => false,
                    'message' => $verification['message'] ?? 'تایید خرید ناموفق بود',
                    'error_code' => $verification['error_code'] ?? null,
                ];
            }
            Log::info('CafeBazaar verifyAndFulfill: Bazaar verification OK', ['user_id' => $user->id, 'product_id' => $productId]);

            // 3. Find plan: cafebazaar_product_id first, then mapping + slug
            $plan = SubscriptionPlan::where('cafebazaar_product_id', $productId)->first();
            if (!$plan) {
                $subscriptionType = $this->mapProductIdToSubscriptionType($productId);
                if ($subscriptionType) {
                    $plan = SubscriptionPlan::where('slug', $subscriptionType)->first();
                }
            }
            if (!$plan) {
                Log::warning('CafeBazaar verifyAndFulfill: no plan for product_id', ['product_id' => $productId, 'user_id' => $user->id]);
                return [
                    'success' => false,
                    'message' => 'شناسه محصول نامعتبر است: ' . $productId,
                ];
            }
            Log::info('CafeBazaar verifyAndFulfill: plan found', ['user_id' => $user->id, 'plan_id' => $plan->id, 'slug' => $plan->slug]);

            $subscriptionType = $plan->slug;
            $transactionId = $orderId ?? $verification['order_id'] ?? 'CB_' . time() . '_' . rand(1000, 9999);

            DB::beginTransaction();
            try {
                // 4. Create payment
                Log::info('CafeBazaar verifyAndFulfill: creating payment', ['user_id' => $user->id, 'product_id' => $productId]);
                $payment = Payment::create([
                'user_id' => $user->id,
                'amount' => $plan->final_price,
                'currency' => $plan->currency ?? 'IRR',
                'payment_method' => 'in_app_purchase',
                'payment_gateway' => 'cafebazaar',
                'billing_platform' => 'cafebazaar',
                'status' => 'completed',
                'transaction_id' => $transactionId,
                'purchase_token' => $purchaseToken,
                'order_id' => $verification['order_id'] ?? $orderId,
                'product_id' => $productId,
                'package_name' => config('services.cafebazaar.package_name'),
                'purchase_state' => $verification['purchase_state'] ?? 'purchased',
                'purchase_time' => $verification['purchase_time'] ?? now(),
                'store_response' => $verification['raw_response'] ?? null,
                'is_acknowledged' => false,
                'processed_at' => now(),
                'payment_metadata' => [
                    'verification_time' => now()->toISOString(),
                    'developer_payload' => $verification['developer_payload'] ?? null,
                    'expiry_time' => $verification['expiry_time'] ?? null,
                    'auto_renewing' => $verification['auto_renewing'] ?? null,
                    'billing_platform' => 'cafebazaar',
                ],
            ]);
            Log::info('CafeBazaar verifyAndFulfill: payment created', ['user_id' => $user->id, 'payment_id' => $payment->id]);

            // 5. Get or create subscription (extend only if existing is CafeBazaar)
            $existingSubscription = $this->subscriptionService->getActiveSubscription($user->id);
            if ($existingSubscription && $existingSubscription->billing_platform === 'cafebazaar') {
                $currentEndDate = Carbon::parse($existingSubscription->end_date);
                $newEndDate = $currentEndDate->copy()->addDays($plan->duration_days);
                $existingSubscription->update([
                    'end_date' => $newEndDate,
                    'price' => $plan->final_price,
                    'currency' => $plan->currency ?? 'IRR',
                    'status' => 'active',
                    'auto_renew' => true,
                    'store_subscription_id' => $verification['order_id'] ?? $orderId,
                    'store_expiry_time' => $verification['expiry_time'] ?? $newEndDate->toISOString(),
                    'store_metadata' => array_filter([
                        'last_purchase_token' => $purchaseToken,
                        'last_product_id' => $productId,
                        'last_verification_time' => now()->toISOString(),
                        'expiry_time' => $verification['expiry_time'] ?? null,
                        'auto_renewing' => $verification['auto_renewing'] ?? null,
                    ]),
                ]);
                $subscription = $existingSubscription->fresh();
            } else {
                $startDate = now();
                $endDate = Carbon::parse($startDate)->addDays($plan->duration_days);
                $subscription = Subscription::create([
                    'user_id' => $user->id,
                    'type' => $subscriptionType,
                    'status' => 'active',
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'price' => $plan->final_price,
                    'currency' => $plan->currency ?? 'IRR',
                    'auto_renew' => true,
                    'payment_method' => 'in_app_purchase',
                    'transaction_id' => $payment->transaction_id,
                    'billing_platform' => 'cafebazaar',
                    'store_subscription_id' => $verification['order_id'] ?? $orderId,
                    'auto_renew_enabled' => true,
                    'store_expiry_time' => $verification['expiry_time'] ?? $endDate->toISOString(),
                    'store_metadata' => array_filter([
                        'purchase_token' => $purchaseToken,
                        'product_id' => $productId,
                        'verification_time' => now()->toISOString(),
                        'purchase_state' => $verification['purchase_state'] ?? 'purchased',
                        'expiry_time' => $verification['expiry_time'] ?? null,
                        'auto_renewing' => $verification['auto_renewing'] ?? null,
                    ]),
                ]);
            }
            Log::info('CafeBazaar verifyAndFulfill: subscription created/updated', ['user_id' => $user->id, 'subscription_id' => $subscription->id]);

            $payment->update(['subscription_id' => $subscription->id]);

            // 6. Acknowledge (404 is non-fatal; see acknowledgePurchase)
            $acknowledgement = $this->acknowledgePurchase($purchaseToken, $productId);
            if ($acknowledgement['success']) {
                $payment->update(['is_acknowledged' => true, 'acknowledged_at' => now()]);
            }

            DB::commit();
            Log::info('CafeBazaar verifyAndFulfill: transaction committed', [
                'user_id' => $user->id,
                'payment_id' => $payment->id,
                'subscription_id' => $subscription->id,
            ]);

            $this->notifyTelegramPayment($user, $payment->fresh(), $subscription->fresh(), $productId, false);

            return [
                'success' => true,
                'payment' => $payment->fresh()->load('subscription'),
                'subscription' => $subscription->fresh(),
                'is_duplicate' => false,
                'acknowledged' => $acknowledgement['success'],
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('CafeBazaar verifyAndFulfillSubscription failed', [
                'user_id' => $user->id,
                'product_id' => $productId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Send a payment notification to the configured Telegram group. No-op if Telegram is not configured or on failure.
     */
    private function notifyTelegramPayment(User $user, Payment $payment, Subscription $subscription, string $productId, bool $isDuplicate): void
    {
        $token = config('services.telegram.bot_token');
        $chatId = config('services.telegram.chat_id');
        if (empty($token) || empty($chatId) || config('services.telegram.enabled', true) === false) {
            return;
        }

        $label = $isDuplicate ? ' (تکرار)' : '';
        $amount = number_format((float) $payment->amount);
        $plan = $subscription ? $subscription->type : $productId;
        $endDate = $subscription && $subscription->end_date
            ? $subscription->end_date->format('Y-m-d H:i')
            : '-';
        $text = "🛒 پرداخت کافه‌بازار{$label}\n"
            . "کاربر: {$user->id} | مبلغ: {$amount} ریال\n"
            . "پلن: {$plan} | تا: {$endDate}\n"
            . "تراکنش: {$payment->transaction_id}";

        try {
            Http::timeout(5)
                ->post("https://api.telegram.org/bot{$token}/sendMessage", [
                    'chat_id' => $chatId,
                    'text' => $text,
                    'disable_web_page_preview' => true,
                ]);
        } catch (\Throwable $e) {
            Log::warning('CafeBazaar Telegram notification failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

