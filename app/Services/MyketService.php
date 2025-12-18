<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Subscription;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MyketService
{
    protected $packageName;
    protected $apiKey;
    protected $apiUrl;

    public function __construct()
    {
        $this->packageName = config('services.myket.package_name', 'ir.sarvcast.app');
        $this->apiKey = config('services.myket.api_key');
        $this->apiUrl = config('services.myket.api_url', 'https://developer.myket.ir/api/applications/validatePurchase');
    }

    /**
     * Verify Myket in-app purchase
     * 
     * @param string $purchaseToken Purchase token from Myket
     * @param string $productId Product/SKU ID
     * @return array
     */
    public function verifyPurchase(string $purchaseToken, string $productId): array
    {
        try {
            Log::info('Verifying Myket purchase', [
                'package_name' => $this->packageName,
                'product_id' => $productId,
                'purchase_token' => substr($purchaseToken, 0, 20) . '...'
            ]);

            $response = Http::withHeaders([
                'X-Api-Key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->apiUrl, [
                'packageName' => $this->packageName,
                'productId' => $productId,
                'purchaseToken' => $purchaseToken,
            ]);

            if ($response->successful()) {
                $result = $response->json();

                Log::info('Myket verification response', [
                    'product_id' => $productId,
                    'purchase_state' => $result['purchaseState'] ?? null,
                    'order_id' => $result['orderId'] ?? null
                ]);

                // Myket returns purchaseState: 0 = purchased
                if (isset($result['purchaseState']) && $result['purchaseState'] == 0) {
                    return [
                        'success' => true,
                        'purchase_state' => 'purchased',
                        'order_id' => $result['orderId'] ?? null,
                        'purchase_time' => isset($result['purchaseTime']) 
                            ? date('Y-m-d H:i:s', $result['purchaseTime'] / 1000) 
                            : null,
                        'developer_payload' => $result['developerPayload'] ?? null,
                        'purchase_type' => $result['purchaseType'] ?? null,
                        'raw_response' => $result
                    ];
                } else {
                    $errorMessage = $result['errorMessage'] ?? 'Unknown error';
                    Log::error('Myket purchase verification failed', [
                        'product_id' => $productId,
                        'purchase_state' => $result['purchaseState'] ?? 'unknown',
                        'error_message' => $errorMessage
                    ]);

                    return [
                        'success' => false,
                        'message' => 'تایید خرید ناموفق: ' . $errorMessage,
                        'error_code' => $result['purchaseState'] ?? null
                    ];
                }
            } else {
                Log::error('Myket API request failed', [
                    'product_id' => $productId,
                    'status_code' => $response->status(),
                    'response_body' => $response->body()
                ]);

                return [
                    'success' => false,
                    'message' => 'خطا در ارتباط با مایکت',
                    'status_code' => $response->status()
                ];
            }
        } catch (\Exception $e) {
            Log::error('Myket verification exception', [
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
     * Verify Myket subscription
     * 
     * @param string $purchaseToken Purchase token
     * @param string $subscriptionId Subscription ID
     * @return array
     */
    public function verifySubscription(string $purchaseToken, string $subscriptionId): array
    {
        try {
            Log::info('Verifying Myket subscription', [
                'package_name' => $this->packageName,
                'subscription_id' => $subscriptionId
            ]);

            $subscriptionUrl = config('services.myket.subscription_api_url',
                'https://developer.myket.ir/api/applications/validateSubscription');

            $response = Http::withHeaders([
                'X-Api-Key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($subscriptionUrl, [
                'packageName' => $this->packageName,
                'subscriptionId' => $subscriptionId,
                'purchaseToken' => $purchaseToken,
            ]);

            if ($response->successful()) {
                $result = $response->json();

                if (isset($result['subscriptionState']) && $result['subscriptionState'] == 0) {
                    return [
                        'success' => true,
                        'subscription_state' => 'active',
                        'expiry_time' => isset($result['expiryTimeMillis']) 
                            ? date('Y-m-d H:i:s', $result['expiryTimeMillis'] / 1000) 
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

            return [
                'success' => false,
                'message' => 'خطا در ارتباط با مایکت'
            ];
        } catch (\Exception $e) {
            Log::error('Myket subscription verification exception', [
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
     * Map product ID to subscription type
     * 
     * @param string $productId Product ID from Myket
     * @return string|null Subscription type (1month, 3months, etc.)
     */
    public function mapProductIdToSubscriptionType(string $productId): ?string
    {
        $mapping = config('services.myket.product_mapping', [
            'subscription_1month' => '1month',
            'subscription_3months' => '3months',
            'subscription_6months' => '6months',
            'subscription_1year' => '1year',
        ]);

        return $mapping[$productId] ?? null;
    }
}

