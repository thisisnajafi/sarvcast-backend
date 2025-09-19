<?php

namespace App\Services;

use App\Models\AffiliatePartner;
use App\Models\Commission;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AffiliateService
{
    /**
     * Create new affiliate partner
     */
    public function createAffiliatePartner(array $partnerData): array
    {
        try {
            DB::beginTransaction();

            $partner = AffiliatePartner::create([
                'name' => $partnerData['name'],
                'email' => $partnerData['email'],
                'phone' => $partnerData['phone'] ?? null,
                'type' => $partnerData['type'],
                'tier' => $partnerData['tier'] ?? $this->determineTier($partnerData['type'], $partnerData),
                'status' => 'pending',
                'commission_rate' => $this->getTierCommissionRate($partnerData['tier'] ?? $this->determineTier($partnerData['type'], $partnerData)),
                'follower_count' => $partnerData['follower_count'] ?? null,
                'social_media_handle' => $partnerData['social_media_handle'] ?? null,
                'bio' => $partnerData['bio'] ?? null,
                'website' => $partnerData['website'] ?? null,
                'verification_documents' => $partnerData['verification_documents'] ?? null,
            ]);

            DB::commit();

            return [
                'success' => true,
                'message' => 'شریک وابسته با موفقیت ایجاد شد',
                'data' => [
                    'partner_id' => $partner->id,
                    'name' => $partner->name,
                    'type' => $partner->type,
                    'tier' => $partner->tier,
                    'status' => $partner->status,
                    'commission_rate' => $partner->commission_rate,
                ]
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Affiliate partner creation failed', [
                'partner_data' => $partnerData,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در ایجاد شریک وابسته: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Verify affiliate partner
     */
    public function verifyAffiliatePartner(int $partnerId): array
    {
        try {
            $partner = AffiliatePartner::findOrFail($partnerId);
            $partner->verify();

            return [
                'success' => true,
                'message' => 'شریک وابسته با موفقیت تأیید شد',
                'data' => [
                    'partner_id' => $partner->id,
                    'status' => $partner->status,
                    'verified_at' => $partner->verified_at,
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Affiliate partner verification failed', [
                'partner_id' => $partnerId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در تأیید شریک وابسته: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Suspend affiliate partner
     */
    public function suspendAffiliatePartner(int $partnerId, string $reason = null): array
    {
        try {
            $partner = AffiliatePartner::findOrFail($partnerId);
            $partner->suspend($reason);

            return [
                'success' => true,
                'message' => 'شریک وابسته با موفقیت تعلیق شد',
                'data' => [
                    'partner_id' => $partner->id,
                    'status' => $partner->status,
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Affiliate partner suspension failed', [
                'partner_id' => $partnerId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در تعلیق شریک وابسته: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Create commission for subscription
     */
    public function createCommission(int $partnerId, int $userId, int $subscriptionId): array
    {
        try {
            DB::beginTransaction();

            $partner = AffiliatePartner::findOrFail($partnerId);
            $subscription = Subscription::findOrFail($subscriptionId);
            $user = User::findOrFail($userId);

            // Check if partner is active
            if ($partner->status !== 'active') {
                return [
                    'success' => false,
                    'message' => 'شریک وابسته فعال نیست'
                ];
            }

            // Check if commission already exists for this subscription
            if (Commission::where('subscription_id', $subscriptionId)->exists()) {
                return [
                    'success' => false,
                    'message' => 'کمیسیون برای این اشتراک قبلاً ایجاد شده است'
                ];
            }

            // Calculate commission
            $subscriptionAmount = $subscription->amount;
            $commissionRate = $partner->commission_rate;
            $commissionAmount = Commission::calculateCommissionAmount($subscriptionAmount, $commissionRate);

            // Create commission
            $commission = Commission::create([
                'affiliate_partner_id' => $partnerId,
                'user_id' => $userId,
                'subscription_id' => $subscriptionId,
                'subscription_amount' => $subscriptionAmount,
                'commission_rate' => $commissionRate,
                'commission_amount' => $commissionAmount,
                'status' => 'pending',
                'commission_period_start' => $subscription->start_date,
                'commission_period_end' => $subscription->end_date,
            ]);

            DB::commit();

            return [
                'success' => true,
                'message' => 'کمیسیون با موفقیت ایجاد شد',
                'data' => [
                    'commission_id' => $commission->id,
                    'partner_id' => $partnerId,
                    'user_id' => $userId,
                    'subscription_id' => $subscriptionId,
                    'commission_amount' => $commissionAmount,
                    'commission_rate' => $commissionRate,
                ]
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Commission creation failed', [
                'partner_id' => $partnerId,
                'user_id' => $userId,
                'subscription_id' => $subscriptionId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در ایجاد کمیسیون: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Approve commission
     */
    public function approveCommission(int $commissionId): array
    {
        try {
            $commission = Commission::findOrFail($commissionId);
            $commission->approve();

            return [
                'success' => true,
                'message' => 'کمیسیون با موفقیت تأیید شد',
                'data' => [
                    'commission_id' => $commission->id,
                    'status' => $commission->status,
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Commission approval failed', [
                'commission_id' => $commissionId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در تأیید کمیسیون: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Mark commission as paid
     */
    public function markCommissionAsPaid(int $commissionId): array
    {
        try {
            $commission = Commission::findOrFail($commissionId);
            $commission->markAsPaid();

            return [
                'success' => true,
                'message' => 'کمیسیون با موفقیت پرداخت شد',
                'data' => [
                    'commission_id' => $commission->id,
                    'status' => $commission->status,
                    'paid_at' => $commission->paid_at,
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Commission payment failed', [
                'commission_id' => $commissionId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در پرداخت کمیسیون: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get affiliate partner statistics
     */
    public function getPartnerStatistics(int $partnerId, int $months = 12): array
    {
        $stats = Commission::getPartnerStatistics($partnerId, $months);
        
        return [
            'success' => true,
            'message' => 'آمار شریک وابسته دریافت شد',
            'data' => $stats
        ];
    }

    /**
     * Get global affiliate statistics
     */
    public function getGlobalStatistics(int $months = 12): array
    {
        $cacheKey = "global_affiliate_stats_{$months}";
        
        $stats = Cache::remember($cacheKey, 1800, function() use ($months) {
            return Commission::getGlobalStatistics($months);
        });

        return [
            'success' => true,
            'message' => 'آمار کلی وابسته دریافت شد',
            'data' => $stats
        ];
    }

    /**
     * Get partners by type
     */
    public function getPartnersByType(string $type, int $limit = 20, int $offset = 0): array
    {
        $partners = AffiliatePartner::byType($type)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->offset($offset)
            ->get()
            ->map(function($partner) {
                return [
                    'id' => $partner->id,
                    'name' => $partner->name,
                    'email' => $partner->email,
                    'type' => $partner->type,
                    'tier' => $partner->tier,
                    'status' => $partner->status,
                    'commission_rate' => $partner->commission_rate,
                    'is_verified' => $partner->is_verified,
                    'created_at' => $partner->created_at,
                ];
            });

        return [
            'success' => true,
            'message' => 'لیست شرکای وابسته دریافت شد',
            'data' => [
                'partners' => $partners,
                'total' => AffiliatePartner::byType($type)->count(),
                'limit' => $limit,
                'offset' => $offset,
            ]
        ];
    }

    /**
     * Get pending commissions
     */
    public function getPendingCommissions(int $limit = 20, int $offset = 0): array
    {
        $commissions = Commission::pending()
            ->with(['affiliatePartner:id,name,type', 'user:id,name', 'subscription:id,type,amount'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->offset($offset)
            ->get()
            ->map(function($commission) {
                return [
                    'id' => $commission->id,
                    'partner' => [
                        'id' => $commission->affiliatePartner->id,
                        'name' => $commission->affiliatePartner->name,
                        'type' => $commission->affiliatePartner->type,
                    ],
                    'user' => [
                        'id' => $commission->user->id,
                        'name' => $commission->user->name,
                    ],
                    'subscription' => [
                        'id' => $commission->subscription->id,
                        'type' => $commission->subscription->type,
                        'amount' => $commission->subscription->amount,
                    ],
                    'commission_amount' => $commission->commission_amount,
                    'commission_rate' => $commission->commission_rate,
                    'status' => $commission->status,
                    'created_at' => $commission->created_at,
                ];
            });

        return [
            'success' => true,
            'message' => 'لیست کمیسیون‌های در انتظار دریافت شد',
            'data' => [
                'commissions' => $commissions,
                'total' => Commission::pending()->count(),
                'limit' => $limit,
                'offset' => $offset,
            ]
        ];
    }

    /**
     * Determine partner tier based on type and data
     */
    private function determineTier(string $type, array $data): string
    {
        switch ($type) {
            case 'teacher':
                return 'micro'; // Default for teachers
            case 'influencer':
                $followers = $data['follower_count'] ?? 0;
                if ($followers >= 100000) return 'macro';
                if ($followers >= 10000) return 'mid';
                return 'micro';
            case 'school':
                return 'enterprise'; // Schools are enterprise level
            case 'corporate':
                return 'enterprise'; // Corporate partners are enterprise level
            default:
                return 'micro';
        }
    }

    /**
     * Get commission rate for tier
     */
    private function getTierCommissionRate(string $tier): float
    {
        $rates = AffiliatePartner::getTierCommissionRates();
        return $rates[$tier] ?? 20.00; // Default 20%
    }

    /**
     * Process bulk commission approvals
     */
    public function processBulkCommissionApprovals(array $commissionIds): array
    {
        try {
            DB::beginTransaction();

            $approvedCount = 0;
            $failedCount = 0;

            foreach ($commissionIds as $commissionId) {
                try {
                    $commission = Commission::findOrFail($commissionId);
                    $commission->approve();
                    $approvedCount++;
                } catch (\Exception $e) {
                    $failedCount++;
                    Log::warning('Bulk commission approval failed', [
                        'commission_id' => $commissionId,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            DB::commit();

            return [
                'success' => true,
                'message' => 'پردازش دسته‌ای کمیسیون‌ها تکمیل شد',
                'data' => [
                    'total_processed' => count($commissionIds),
                    'approved_count' => $approvedCount,
                    'failed_count' => $failedCount,
                ]
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk commission approval failed', [
                'commission_ids' => $commissionIds,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در پردازش دسته‌ای کمیسیون‌ها: ' . $e->getMessage()
            ];
        }
    }
}
