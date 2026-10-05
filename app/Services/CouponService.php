<?php

namespace App\Services;

use App\Models\CouponCode;
use App\Models\CouponUsage;
use App\Models\CommissionPayment;
use App\Models\AffiliatePartner;
use App\Models\User;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CouponService
{
    public function createCouponCode(array $data): array
    {
        try {
            DB::beginTransaction();

            // Generate unique code if not provided
            if (empty($data['code'])) {
                $data['code'] = $this->generateUniqueCode($data['partner_type'] ?? 'promotional');
            }

            // Validate partner if provided
            if (!empty($data['partner_id'])) {
                $partner = AffiliatePartner::find($data['partner_id']);
                if (!$partner) {
                    throw new \Exception('Partner not found');
                }
                $data['partner_type'] = $partner->type;
            }

            $coupon = CouponCode::create($data);

            DB::commit();

            return [
                'success' => true,
                'message' => 'کد کوپن با موفقیت ایجاد شد',
                'data' => $coupon->toApiResponse()
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error creating coupon code: " . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'خطا در ایجاد کد کوپن: ' . $e->getMessage()
            ];
        }
    }

    public function validateCouponCode(string $code, User $user, float $amount, ?string $planSlug = null): array
    {
        try {
            $coupon = CouponCode::where('code', $code)->first();

            if (!$coupon) {
                return [
                    'success' => false,
                    'message' => 'کد کوپن یافت نشد'
                ];
            }

            if (!$coupon->isValid()) {
                return [
                    'success' => false,
                    'message' => 'کد کوپن منقضی شده یا غیرفعال است'
                ];
            }

            if (!$coupon->canBeUsedByUser($user)) {
                return [
                    'success' => false,
                    'message' => 'شما قبلاً از این کد کوپن استفاده کرده‌اید'
                ];
            }

            if ($planSlug !== null && !$this->couponAppliesToPlan($coupon, $planSlug)) {
                return [
                    'success' => false,
                    'message' => 'این کد کوپن برای پلن انتخاب‌شده قابل استفاده نیست'
                ];
            }

            if ($coupon->minimum_amount && $amount < $coupon->minimum_amount) {
                return [
                    'success' => false,
                    'message' => "حداقل مبلغ برای استفاده از این کد کوپن {$coupon->minimum_amount} تومان است"
                ];
            }

            $discount = $coupon->calculateDiscount($amount);
            $finalAmount = max(0, $amount - $discount);

            return [
                'success' => true,
                'message' => 'کد کوپن معتبر است',
                'data' => [
                    'coupon' => $coupon->toApiResponse(),
                    'original_amount' => $amount,
                    'discount_amount' => $discount,
                    'final_amount' => $finalAmount,
                    'commission_amount' => $coupon->calculateCommission($finalAmount)
                ]
            ];
        } catch (\Exception $e) {
            Log::error("Error validating coupon code: " . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'خطا در اعتبارسنجی کد کوپن'
            ];
        }
    }

    /**
     * Validate a coupon for a CafeBazaar SKU and issue a dynamicPriceToken.
     */
    public function prepareCafeBazaarCoupon(User $user, string $code, string $productId, ?string $planSlug = null): array
    {
        try {
            $dynamicPrice = app(CafeBazaarDynamicPriceService::class);
            if (!$dynamicPrice->isConfigured()) {
                return [
                    'success' => false,
                    'message' => 'تخفیف پویای کافه‌بازار روی سرور پیکربندی نشده است',
                    'error_code' => 'DYNAMIC_PRICE_NOT_CONFIGURED',
                ];
            }

            $plan = SubscriptionPlan::where('cafebazaar_product_id', $productId)->first();
            if (!$plan && $planSlug) {
                $plan = SubscriptionPlan::findBySlug($planSlug);
            }
            if (!$plan) {
                $mappedSlug = config("services.cafebazaar.product_mapping.{$productId}");
                if ($mappedSlug) {
                    $plan = SubscriptionPlan::findBySlug($mappedSlug);
                }
            }
            if (!$plan) {
                return [
                    'success' => false,
                    'message' => 'پلن مرتبط با این محصول کافه‌بازار یافت نشد',
                ];
            }

            $resolvedSlug = $plan->slug;
            $originalAmount = (float) $plan->getFinalPriceForFlavor('cafebazaar');
            if ($originalAmount <= 0) {
                $originalAmount = (float) $plan->final_price;
            }

            $validation = $this->validateCouponCode($code, $user, $originalAmount, $resolvedSlug);
            if (!$validation['success']) {
                return $validation;
            }

            $finalAmount = (float) $validation['data']['final_amount'];
            $discountAmount = (float) $validation['data']['discount_amount'];
            [$finalAmount, $discountAmount] = $dynamicPrice->floorZeroCharge(
                $originalAmount,
                $finalAmount,
                $discountAmount
            );

            $amountRials = $dynamicPrice->toRials($finalAmount, $plan->currency ?? null);
            if ($amountRials < CafeBazaarDynamicPriceService::MIN_CHARGE_RIALS) {
                $amountRials = CafeBazaarDynamicPriceService::MIN_CHARGE_RIALS;
            }
            $ttl = (int) config('services.cafebazaar.dynamic_price_token_ttl', 900);
            $token = $dynamicPrice->createToken($amountRials, $productId, $ttl);

            $prepareId = (string) Str::uuid();
            $prepareTtl = (int) config('services.cafebazaar.coupon_prepare_ttl', 900);
            $expiresAt = now()->addSeconds($prepareTtl);

            Cache::put($this->cafeBazaarPrepareCacheKey($prepareId), [
                'user_id' => $user->id,
                'coupon_code' => strtoupper(trim($code)),
                'product_id' => $productId,
                'plan_slug' => $resolvedSlug,
                'original_amount' => $originalAmount,
                'discount_amount' => $discountAmount,
                'final_amount' => $finalAmount,
                'amount_rials' => $amountRials,
            ], $expiresAt);

            return [
                'success' => true,
                'message' => 'کد تخفیف برای کافه‌بازار آماده شد',
                'data' => [
                    'prepare_id' => $prepareId,
                    'coupon_code' => strtoupper(trim($code)),
                    'product_id' => $productId,
                    'plan_slug' => $resolvedSlug,
                    'original_amount' => $originalAmount,
                    'discount_amount' => $discountAmount,
                    'final_amount' => $finalAmount,
                    'final_amount_rials' => $amountRials,
                    'package_name' => (string) config(
                        'services.cafebazaar.package_name',
                        'com.avinpishtazan.manji.cafebazaar'
                    ),
                    'sku' => $productId,
                    'dynamic_price_token' => $token,
                    'expires_at' => $expiresAt->toISOString(),
                    'coupon' => $validation['data']['coupon'],
                ],
            ];
        } catch (\Throwable $e) {
            Log::error('CafeBazaar coupon prepare failed', [
                'user_id' => $user->id,
                'code' => $code,
                'product_id' => $productId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'خطا در آماده‌سازی تخفیف کافه‌بازار',
            ];
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getCafeBazaarPrepare(string $prepareId): ?array
    {
        $data = Cache::get($this->cafeBazaarPrepareCacheKey($prepareId));

        return is_array($data) ? $data : null;
    }

    public function forgetCafeBazaarPrepare(string $prepareId): void
    {
        Cache::forget($this->cafeBazaarPrepareCacheKey($prepareId));
    }

    public function useCouponCode(
        string $code,
        User $user,
        Subscription $subscription,
        ?float $originalAmount = null,
        bool $manageTransaction = true
    ): array {
        try {
            if ($manageTransaction) {
                DB::beginTransaction();
            }

            $coupon = CouponCode::where('code', $code)->first();
            if (!$coupon) {
                throw new \Exception('کد کوپن یافت نشد');
            }

            $existing = CouponUsage::where('coupon_code_id', $coupon->id)
                ->where('user_id', $user->id)
                ->where('subscription_id', $subscription->id)
                ->first();
            if ($existing) {
                if ($manageTransaction) {
                    DB::commit();
                }

                return [
                    'success' => true,
                    'message' => 'کد کوپن قبلاً برای این اشتراک ثبت شده است',
                    'data' => $existing->toApiResponse(),
                ];
            }

            $amount = $originalAmount ?? (float) ($subscription->price ?? 0);

            // Validate coupon
            $validation = $this->validateCouponCode($code, $user, $amount, $subscription->type);
            if (!$validation['success']) {
                throw new \Exception($validation['message']);
            }

            $validationData = $validation['data'];

            // Create coupon usage record
            $usage = CouponUsage::create([
                'coupon_code_id' => $coupon->id,
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
                'original_amount' => $validationData['original_amount'],
                'discount_amount' => $validationData['discount_amount'],
                'final_amount' => $validationData['final_amount'],
                'commission_amount' => $validationData['commission_amount'],
                'status' => 'completed',
                'used_at' => now(),
            ]);

            // Increment coupon usage count
            $coupon->incrementUsage();

            // Create commission payment if applicable
            if ($validationData['commission_amount'] > 0 && $coupon->partner) {
                $this->createCommissionPayment($coupon->partner, $usage);
            }

            if ($manageTransaction) {
                DB::commit();
            }

            return [
                'success' => true,
                'message' => 'کد کوپن با موفقیت استفاده شد',
                'data' => $usage->toApiResponse()
            ];
        } catch (\Exception $e) {
            if ($manageTransaction) {
                DB::rollBack();
            }
            Log::error("Error using coupon code: " . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'خطا در استفاده از کد کوپن: ' . $e->getMessage()
            ];
        }
    }

    private function couponAppliesToPlan(CouponCode $coupon, string $planSlug): bool
    {
        $plans = $coupon->applicable_plans;
        if (!is_array($plans) || $plans === []) {
            return true;
        }

        $normalized = array_map(static fn ($p) => strtolower((string) $p), $plans);

        return in_array(strtolower($planSlug), $normalized, true);
    }

    private function cafeBazaarPrepareCacheKey(string $prepareId): string
    {
        return "cafebazaar_coupon_prep_{$prepareId}";
    }

    public function getCouponCodes(array $filters = []): array
    {
        try {
            $query = CouponCode::with(['partner', 'creator']);

            // Apply filters
            if (!empty($filters['partner_type'])) {
                $query->where('partner_type', $filters['partner_type']);
            }

            if (!empty($filters['partner_id'])) {
                $query->where('partner_id', $filters['partner_id']);
            }

            if (!empty($filters['is_active'])) {
                $query->where('is_active', $filters['is_active']);
            }

            if (!empty($filters['search'])) {
                $query->where(function ($q) use ($filters) {
                    $q->where('code', 'like', '%' . $filters['search'] . '%')
                      ->orWhere('name', 'like', '%' . $filters['search'] . '%');
                });
            }

            $coupons = $query->orderBy('created_at', 'desc')->get();

            return [
                'success' => true,
                'message' => 'کدهای کوپن با موفقیت دریافت شد',
                'data' => $coupons->map->toApiResponse()
            ];
        } catch (\Exception $e) {
            Log::error("Error getting coupon codes: " . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'خطا در دریافت کدهای کوپن'
            ];
        }
    }

    public function getCouponUsage(array $filters = []): array
    {
        try {
            $query = CouponUsage::with(['couponCode', 'user', 'subscription']);

            // Apply filters
            if (!empty($filters['coupon_code_id'])) {
                $query->where('coupon_code_id', $filters['coupon_code_id']);
            }

            if (!empty($filters['partner_id'])) {
                $query->forPartner($filters['partner_id']);
            }

            if (!empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            if (!empty($filters['date_from'])) {
                $query->where('used_at', '>=', $filters['date_from']);
            }

            if (!empty($filters['date_to'])) {
                $query->where('used_at', '<=', $filters['date_to']);
            }

            $usages = $query->orderBy('used_at', 'desc')->get();

            return [
                'success' => true,
                'message' => 'استفاده از کدهای کوپن با موفقیت دریافت شد',
                'data' => $usages->map->toApiResponse()
            ];
        } catch (\Exception $e) {
            Log::error("Error getting coupon usage: " . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'خطا در دریافت استفاده از کدهای کوپن'
            ];
        }
    }

    public function getCouponStatistics(): array
    {
        try {
            $stats = [
                'total_coupons' => CouponCode::count(),
                'active_coupons' => CouponCode::active()->count(),
                'total_usage' => CouponUsage::count(),
                'total_discount_given' => CouponUsage::sum('discount_amount'),
                'total_commission_paid' => CouponUsage::sum('commission_amount'),
                'usage_by_type' => CouponCode::select('partner_type', DB::raw('COUNT(*) as count'))
                    ->groupBy('partner_type')
                    ->get()
                    ->pluck('count', 'partner_type'),
                'recent_usage' => CouponUsage::recent(30)->count(),
            ];

            return [
                'success' => true,
                'message' => 'آمار کدهای کوپن با موفقیت دریافت شد',
                'data' => $stats
            ];
        } catch (\Exception $e) {
            Log::error("Error getting coupon statistics: " . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'خطا در دریافت آمار کدهای کوپن'
            ];
        }
    }

    private function generateUniqueCode(string $prefix = 'PROMO'): string
    {
        $prefixes = [
            'influencer' => 'INF',
            'teacher' => 'TCH',
            'partner' => 'PRT',
            'promotional' => 'PROMO'
        ];

        $codePrefix = $prefixes[$prefix] ?? 'PROMO';
        
        do {
            $code = $codePrefix . strtoupper(Str::random(6));
        } while (CouponCode::where('code', $code)->exists());

        return $code;
    }

    private function createCommissionPayment(AffiliatePartner $partner, CouponUsage $usage): void
    {
        CommissionPayment::create([
            'affiliate_partner_id' => $partner->id,
            'coupon_usage_id' => $usage->id,
            'amount' => $usage->commission_amount,
            'currency' => 'IRR',
            'payment_type' => 'coupon_commission',
            'status' => 'pending',
            'payment_method' => 'bank_transfer',
            'payment_details' => [
                'bank_name' => $partner->bank_name,
                'account_number' => $partner->account_number,
                'iban' => $partner->iban,
            ],
        ]);
    }
}
