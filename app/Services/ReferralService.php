<?php

namespace App\Services;

use App\Models\ReferralCode;
use App\Models\Referral;
use App\Models\User;
use App\Models\PlayHistory;
use App\Services\CoinService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ReferralService
{
    protected $coinService;

    public function __construct(CoinService $coinService)
    {
        $this->coinService = $coinService;
    }

    /**
     * Generate referral code for user
     */
    public function generateReferralCode(int $userId): array
    {
        try {
            $user = User::findOrFail($userId);
            
            // Check if user already has a referral code
            $existingCode = ReferralCode::where('user_id', $userId)->first();
            
            if ($existingCode && $existingCode->isValid()) {
                return [
                    'success' => true,
                    'message' => 'کد معرفی موجود دریافت شد',
                    'data' => [
                        'code' => $existingCode->code,
                        'successful_referrals' => $existingCode->successful_referrals,
                        'total_coins_earned' => $existingCode->total_coins_earned,
                        'referral_url' => $this->generateReferralUrl($existingCode->code),
                    ]
                ];
            }

            // Generate new referral code
            $referralCode = ReferralCode::createForUser($userId);

            return [
                'success' => true,
                'message' => 'کد معرفی جدید ایجاد شد',
                'data' => [
                    'code' => $referralCode->code,
                    'successful_referrals' => 0,
                    'total_coins_earned' => 0,
                    'referral_url' => $this->generateReferralUrl($referralCode->code),
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Referral code generation failed', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در ایجاد کد معرفی: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Process referral code usage during registration
     */
    public function processReferralCode(string $referralCode, int $newUserId): array
    {
        try {
            DB::beginTransaction();

            // Validate referral code
            $code = ReferralCode::where('code', $referralCode)->first();
            
            if (!$code || !$code->isValid()) {
                return [
                    'success' => false,
                    'message' => 'کد معرفی نامعتبر یا منقضی شده است'
                ];
            }

            // Check if user is trying to refer themselves
            if ($code->user_id === $newUserId) {
                return [
                    'success' => false,
                    'message' => 'نمی‌توانید از کد معرفی خود استفاده کنید'
                ];
            }

            // Check if user has already been referred
            if (Referral::where('referred_id', $newUserId)->exists()) {
                return [
                    'success' => false,
                    'message' => 'شما قبلاً با کد معرفی ثبت‌نام کرده‌اید'
                ];
            }

            // Create referral record
            $referral = Referral::create([
                'referrer_id' => $code->user_id,
                'referred_id' => $newUserId,
                'referral_code' => $referralCode,
                'status' => 'pending',
            ]);

            DB::commit();

            return [
                'success' => true,
                'message' => 'کد معرفی با موفقیت اعمال شد',
                'data' => [
                    'referral_id' => $referral->id,
                    'referrer_id' => $code->user_id,
                    'status' => 'pending',
                    'requirements' => [
                        'listen_to_story' => 'گوش دادن به حداقل یک داستان کامل',
                        'active_for_7_days' => 'فعال بودن به مدت 7 روز',
                    ]
                ]
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Referral code processing failed', [
                'referral_code' => $referralCode,
                'new_user_id' => $newUserId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در پردازش کد معرفی: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Check and complete pending referrals
     */
    public function checkReferralCompletion(int $userId): array
    {
        try {
            $referral = Referral::where('referred_id', $userId)
                ->where('status', 'pending')
                ->first();

            if (!$referral) {
                return [
                    'success' => false,
                    'message' => 'هیچ معرفی در انتظار تکمیل یافت نشد'
                ];
            }

            // Check completion requirements
            if ($this->checkCompletionRequirements($userId)) {
                $referral->markAsCompleted();
                
                return [
                    'success' => true,
                    'message' => 'معرفی تکمیل شد و سکه‌ها اعطا شد',
                    'data' => [
                        'referral_id' => $referral->id,
                        'coins_awarded' => $referral->coins_awarded,
                        'completed_at' => $referral->completed_at,
                    ]
                ];
            }

            return [
                'success' => false,
                'message' => 'شرایط تکمیل معرفی هنوز برقرار نیست',
                'data' => [
                    'referral_id' => $referral->id,
                    'status' => 'pending',
                    'requirements' => $this->getCompletionRequirements($userId),
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Referral completion check failed', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در بررسی تکمیل معرفی: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Check if referral completion requirements are met
     */
    private function checkCompletionRequirements(int $userId): bool
    {
        $user = User::find($userId);
        if (!$user) return false;

        // Check if user has listened to at least 1 complete story
        $hasListened = PlayHistory::where('user_id', $userId)
            ->where('completed', true)
            ->exists();

        // Check if user has been active for 7+ days
        $isActive = $user->created_at->diffInDays(now()) >= 7;

        return $hasListened && $isActive;
    }

    /**
     * Get completion requirements status
     */
    private function getCompletionRequirements(int $userId): array
    {
        $user = User::find($userId);
        if (!$user) return [];

        $hasListened = PlayHistory::where('user_id', $userId)
            ->where('completed', true)
            ->exists();

        $daysActive = $user->created_at->diffInDays(now());
        $isActive = $daysActive >= 7;

        return [
            'listen_to_story' => [
                'required' => 'گوش دادن به حداقل یک داستان کامل',
                'completed' => $hasListened,
            ],
            'active_for_7_days' => [
                'required' => 'فعال بودن به مدت 7 روز',
                'completed' => $isActive,
                'current_days' => $daysActive,
            ],
        ];
    }

    /**
     * Get user's referral statistics
     */
    public function getUserReferralStatistics(int $userId): array
    {
        $stats = Referral::getUserStatistics($userId);
        
        return [
            'success' => true,
            'message' => 'آمار معرفی کاربر دریافت شد',
            'data' => $stats
        ];
    }

    /**
     * Get user's referrals list
     */
    public function getUserReferrals(int $userId, int $limit = 20, int $offset = 0): array
    {
        $referrals = Referral::byReferrer($userId)
            ->with(['referred:id,name,created_at'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->offset($offset)
            ->get()
            ->map(function($referral) {
                return [
                    'id' => $referral->id,
                    'referred_user' => [
                        'id' => $referral->referred->id,
                        'name' => $referral->referred->name,
                        'joined_at' => $referral->referred->created_at,
                    ],
                    'referral_code' => $referral->referral_code,
                    'status' => $referral->status,
                    'coins_awarded' => $referral->coins_awarded,
                    'completed_at' => $referral->completed_at,
                    'created_at' => $referral->created_at,
                ];
            });

        return [
            'success' => true,
            'message' => 'لیست معرفی‌ها دریافت شد',
            'data' => [
                'referrals' => $referrals,
                'total' => Referral::byReferrer($userId)->count(),
                'limit' => $limit,
                'offset' => $offset,
            ]
        ];
    }

    /**
     * Get global referral statistics
     */
    public function getGlobalReferralStatistics(): array
    {
        $cacheKey = 'global_referral_statistics';
        
        $stats = Cache::remember($cacheKey, 1800, function() {
            return Referral::getGlobalStatistics();
        });

        return [
            'success' => true,
            'message' => 'آمار کلی معرفی دریافت شد',
            'data' => $stats
        ];
    }

    /**
     * Get top referrers
     */
    public function getTopReferrers(int $limit = 10): array
    {
        $topReferrers = ReferralCode::topReferrers($limit)
            ->with(['user:id,name'])
            ->get()
            ->map(function($referralCode) {
                return [
                    'user_id' => $referralCode->user_id,
                    'user_name' => $referralCode->user->name,
                    'referral_code' => $referralCode->code,
                    'successful_referrals' => $referralCode->successful_referrals,
                    'total_coins_earned' => $referralCode->total_coins_earned,
                    'statistics' => $referralCode->getStatistics(),
                ];
            });

        return [
            'success' => true,
            'message' => 'برترین معرف‌ها دریافت شد',
            'data' => [
                'top_referrers' => $topReferrers,
                'limit' => $limit,
            ]
        ];
    }

    /**
     * Generate referral URL
     */
    private function generateReferralUrl(string $code): string
    {
        return url('/register?ref=' . $code);
    }

    /**
     * Validate referral code
     */
    public function validateReferralCode(string $code): array
    {
        $referralCode = ReferralCode::where('code', $code)->first();
        
        if (!$referralCode) {
            return [
                'success' => false,
                'message' => 'کد معرفی یافت نشد'
            ];
        }

        if (!$referralCode->isValid()) {
            return [
                'success' => false,
                'message' => 'کد معرفی منقضی شده است'
            ];
        }

        return [
            'success' => true,
            'message' => 'کد معرفی معتبر است',
            'data' => [
                'code' => $referralCode->code,
                'referrer_name' => $referralCode->user->name,
                'successful_referrals' => $referralCode->successful_referrals,
            ]
        ];
    }

    /**
     * Process automatic referral completion checks
     */
    public function processAutomaticReferralChecks(): array
    {
        try {
            $pendingReferrals = Referral::pending()
                ->where('created_at', '<=', now()->subDays(7))
                ->get();

            $completedCount = 0;
            $failedCount = 0;

            foreach ($pendingReferrals as $referral) {
                if ($this->checkCompletionRequirements($referral->referred_id)) {
                    $referral->markAsCompleted();
                    $completedCount++;
                } else {
                    // Mark as expired if requirements not met after 30 days
                    if ($referral->created_at->diffInDays(now()) >= 30) {
                        $referral->update(['status' => 'expired']);
                        $failedCount++;
                    }
                }
            }

            return [
                'success' => true,
                'message' => 'بررسی خودکار معرفی‌ها تکمیل شد',
                'data' => [
                    'processed_referrals' => $pendingReferrals->count(),
                    'completed_referrals' => $completedCount,
                    'expired_referrals' => $failedCount,
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Automatic referral check failed', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در بررسی خودکار معرفی‌ها: ' . $e->getMessage()
            ];
        }
    }
}
