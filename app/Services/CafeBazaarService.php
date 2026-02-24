<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Subscription;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CafeBazaarService
{
    protected $packageName;
    protected $apiKey;
    protected $apiUrl;

    public function __construct()
    {
        $this->packageName = config('services.cafebazaar.package_name', 'com.sarvabi.sarvcast');
        $this->apiKey = config('services.cafebazaar.api_key');
        $this->apiUrl = config('services.cafebazaar.api_url', 'https://pardakht.cafebazaar.ir/devapi/v2/validate');
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
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($url, [
                'packageName' => $this->packageName,
                'productId' => $productId,
                'purchaseToken' => $purchaseToken,
            ]);

            // If 404, try alternate path (some setups use /devapi/v2/api/validate)
            if ($response->status() === 404 && str_contains($url, '/devapi/v2/validate') && !str_contains($url, '/api/validate')) {
                $altUrl = str_replace('/devapi/v2/validate', '/devapi/v2/api/validate', $url);
                Log::info('CafeBazaar 404 on first URL, retrying with alternate', ['alt_url' => $altUrl]);
                $response = Http::timeout(25)->connectTimeout(10)->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ])->post($altUrl, [
                    'packageName' => $this->packageName,
                    'productId' => $productId,
                    'purchaseToken' => $purchaseToken,
                ]);
            }

            if ($response->successful()) {
                $result = $response->json();

                Log::info('CafeBazaar verification response', [
                    'product_id' => $productId,
                    'response_status' => $result['status'] ?? 'unknown',
                    'purchase_state' => $result['purchaseState'] ?? null
                ]);

                // CafeBazaar returns status: 0 = success
                if (isset($result['status']) && $result['status'] == 0) {
                    return [
                        'success' => true,
                        'purchase_state' => $result['purchaseState'] ?? 'purchased',
                        'order_id' => $result['orderId'] ?? null,
                        'purchase_time' => isset($result['purchaseTime']) ? date('Y-m-d H:i:s', $result['purchaseTime'] / 1000) : null,
                        'developer_payload' => $result['developerPayload'] ?? null,
                        'purchase_type' => $result['purchaseType'] ?? null,
                        'acknowledgement_state' => $result['acknowledgementState'] ?? null,
                        'raw_response' => $result
                    ];
                } else {
                    $errorMessage = $result['errorMessage'] ?? 'Unknown error';
                    Log::error('CafeBazaar purchase verification failed', [
                        'product_id' => $productId,
                        'status' => $result['status'] ?? 'unknown',
                        'error_message' => $errorMessage
                    ]);

                    return [
                        'success' => false,
                        'message' => 'تایید خرید ناموفق: ' . $errorMessage,
                        'error_code' => $result['status'] ?? null
                    ];
                }
            } else {
                Log::error('CafeBazaar API request failed', [
                    'product_id' => $productId,
                    'status_code' => $response->status(),
                    'response_body' => $response->body()
                ]);

                return [
                    'success' => false,
                    'message' => 'خطا در ارتباط با کافه‌بازار',
                    'status_code' => $response->status()
                ];
            }
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
     * Verify CafeBazaar subscription
     * 
     * @param string $purchaseToken Purchase token
     * @param string $subscriptionId Subscription ID
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
                ];
            }

            $subscriptionUrl = config('services.cafebazaar.subscription_api_url',
                'https://pardakht.cafebazaar.ir/devapi/v2/validate/subscription');

            Log::info('Verifying CafeBazaar subscription', [
                'package_name' => $this->packageName,
                'subscription_id' => $subscriptionId,
                'api_url' => $subscriptionUrl,
            ]);

            $response = Http::timeout(25)->connectTimeout(10)->withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($subscriptionUrl, [
                'packageName' => $this->packageName,
                'subscriptionId' => $subscriptionId,
                'purchaseToken' => $purchaseToken,
            ]);

            if ($response->status() === 404 && str_contains($subscriptionUrl, '/devapi/v2/validate/') && !str_contains($subscriptionUrl, '/api/validate/')) {
                $altUrl = str_replace('/devapi/v2/validate/', '/devapi/v2/api/validate/', $subscriptionUrl);
                Log::info('CafeBazaar subscription 404, retrying with alternate', ['alt_url' => $altUrl]);
                $response = Http::timeout(25)->connectTimeout(10)->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ])->post($altUrl, [
                    'packageName' => $this->packageName,
                    'subscriptionId' => $subscriptionId,
                    'purchaseToken' => $purchaseToken,
                ]);
            }

            if ($response->successful()) {
                $result = $response->json();

                Log::info('CafeBazaar subscription verification response', [
                    'subscription_id' => $subscriptionId,
                    'status' => $result['status'] ?? 'unknown',
                ]);

                if (isset($result['status']) && $result['status'] == 0) {
                    return [
                        'success' => true,
                        'subscription_state' => $result['subscriptionState'] ?? 'active',
                        'purchase_state' => $result['subscriptionState'] ?? 'active',
                        'order_id' => $result['orderId'] ?? null,
                        'purchase_time' => isset($result['purchaseTime']) ? date('Y-m-d H:i:s', (int) $result['purchaseTime'] / 1000) : (isset($result['purchaseTimeMillis']) ? date('Y-m-d H:i:s', (int) $result['purchaseTimeMillis'] / 1000) : null),
                        'expiry_time' => isset($result['expiryTimeMillis']) 
                            ? date('Y-m-d H:i:s', (int) $result['expiryTimeMillis'] / 1000) 
                            : null,
                        'auto_renewing' => $result['autoRenewing'] ?? false,
                        'raw_response' => $result
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => $result['errorMessage'] ?? 'تایید اشتراک ناموفق بود'
                    ];
                }
            }

            Log::error('CafeBazaar subscription API request failed', [
                'subscription_id' => $subscriptionId,
                'status_code' => $response->status(),
                'response_body' => $response->body()
            ]);
            return [
                'success' => false,
                'message' => 'خطا در ارتباط با کافه‌بازار'
            ];
        } catch (\Exception $e) {
            Log::error('CafeBazaar subscription verification exception', [
                'subscription_id' => $subscriptionId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در تایید اشتراک: ' . $e->getMessage()
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
                'Authorization' => 'Bearer ' . $this->apiKey,
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
}

