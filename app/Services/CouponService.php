<?php

namespace App\Services;

use App\Models\CouponCode;
use App\Models\CouponUsage;
use App\Models\CommissionPayment;
use App\Models\AffiliatePartner;
use App\Models\User;
use App\Models\Subscription;
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

    public function validateCouponCode(string $code, User $user, float $amount): array
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

            if ($coupon->minimum_amount && $amount < $coupon->minimum_amount) {
                return [
                    'success' => false,
                    'message' => "حداقل مبلغ برای استفاده از این کد کوپن {$coupon->minimum_amount} تومان است"
                ];
            }

            $discount = $coupon->calculateDiscount($amount);
            $finalAmount = $amount - $discount;

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

    public function useCouponCode(string $code, User $user, Subscription $subscription): array
    {
        try {
            DB::beginTransaction();

            $coupon = CouponCode::where('code', $code)->first();
            if (!$coupon) {
                throw new \Exception('کد کوپن یافت نشد');
            }

            // Validate coupon
            $validation = $this->validateCouponCode($code, $user, $subscription->amount);
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

            DB::commit();

            return [
                'success' => true,
                'message' => 'کد کوپن با موفقیت استفاده شد',
                'data' => $usage->toApiResponse()
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error using coupon code: " . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'خطا در استفاده از کد کوپن: ' . $e->getMessage()
            ];
        }
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
