<?php

namespace App\Services;

use App\Models\SchoolPartnership;
use App\Models\SchoolLicense;
use App\Models\AffiliatePartner;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SchoolService
{
    /**
     * Create school partnership
     */
    public function createPartnership(int $partnerId, array $partnershipData): array
    {
        try {
            DB::beginTransaction();

            $partner = AffiliatePartner::findOrFail($partnerId);
            
            // Check if partner is a school
            if ($partner->type !== 'school') {
                return [
                    'success' => false,
                    'message' => 'فقط مدارس می‌توانند مشارکت ایجاد کنند'
                ];
            }

            // Check if partner is active
            if ($partner->status !== 'active') {
                return [
                    'success' => false,
                    'message' => 'مدرسه فعال نیست'
                ];
            }

            // Check if school already has a partnership
            if (SchoolPartnership::where('affiliate_partner_id', $partnerId)->exists()) {
                return [
                    'success' => false,
                    'message' => 'مدرسه قبلاً مشارکت دارد'
                ];
            }

            // Set partnership parameters based on model
            $partnershipModels = SchoolPartnership::getPartnershipModels();
            $model = $partnershipData['partnership_model'];
            $modelConfig = $partnershipModels[$model];

            $partnership = SchoolPartnership::create([
                'affiliate_partner_id' => $partnerId,
                'school_name' => $partnershipData['school_name'],
                'school_type' => $partnershipData['school_type'],
                'school_level' => $partnershipData['school_level'],
                'location' => $partnershipData['location'],
                'contact_person' => $partnershipData['contact_person'],
                'contact_email' => $partnershipData['contact_email'],
                'contact_phone' => $partnershipData['contact_phone'],
                'student_count' => $partnershipData['student_count'],
                'teacher_count' => $partnershipData['teacher_count'],
                'partnership_model' => $model,
                'discount_rate' => $modelConfig['discount_rate'] ?? 60.00,
                'revenue_share_rate' => $modelConfig['revenue_share_rate'] ?? null,
                'annual_license_fee' => $modelConfig['annual_license_fee'] ?? null,
                'max_student_capacity' => $modelConfig['max_capacity'] ?? 500,
                'partnership_start_date' => $partnershipData['partnership_start_date'],
                'partnership_end_date' => $partnershipData['partnership_end_date'],
                'verification_documents' => $partnershipData['verification_documents'] ?? null,
                'notes' => $partnershipData['notes'] ?? null,
            ]);

            DB::commit();

            return [
                'success' => true,
                'message' => 'مشارکت مدرسه با موفقیت ایجاد شد',
                'data' => [
                    'partnership_id' => $partnership->id,
                    'school_name' => $partnership->school_name,
                    'partnership_model' => $partnership->partnership_model,
                    'discount_rate' => $partnership->discount_rate,
                    'revenue_share_rate' => $partnership->revenue_share_rate,
                    'annual_license_fee' => $partnership->annual_license_fee,
                    'max_student_capacity' => $partnership->max_student_capacity,
                    'partnership_start_date' => $partnership->partnership_start_date,
                    'partnership_end_date' => $partnership->partnership_end_date,
                    'status' => $partnership->status,
                ]
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('School partnership creation failed', [
                'partner_id' => $partnerId,
                'partnership_data' => $partnershipData,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در ایجاد مشارکت مدرسه: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Create school license
     */
    public function createLicense(int $partnershipId, int $userId, array $licenseData): array
    {
        try {
            DB::beginTransaction();

            $partnership = SchoolPartnership::findOrFail($partnershipId);
            
            // Check if partnership can create licenses
            if (!$partnership->canCreateLicense()) {
                return [
                    'success' => false,
                    'message' => 'امکان ایجاد لایسنس وجود ندارد'
                ];
            }

            // Check if user already has a license from this school
            if (SchoolLicense::where('school_partnership_id', $partnershipId)
                ->where('user_id', $userId)
                ->where('status', 'active')
                ->exists()) {
                return [
                    'success' => false,
                    'message' => 'کاربر قبلاً لایسنس فعال دارد'
                ];
            }

            // Calculate pricing
            $originalPrice = $licenseData['original_price'];
            $pricing = $partnership->calculateDiscountedPrice($originalPrice);

            // Create school license
            $license = SchoolLicense::create([
                'school_partnership_id' => $partnershipId,
                'user_id' => $userId,
                'license_type' => $licenseData['license_type'],
                'user_role' => $licenseData['user_role'],
                'original_price' => $pricing['original_price'],
                'discounted_price' => $pricing['discounted_price'],
                'discount_amount' => $pricing['discount_amount'],
                'start_date' => $licenseData['start_date'],
                'end_date' => $licenseData['end_date'],
                'status' => 'active',
                'is_activated' => true,
                'activated_at' => now(),
            ]);

            DB::commit();

            return [
                'success' => true,
                'message' => 'لایسنس مدرسه با موفقیت ایجاد شد',
                'data' => [
                    'license_id' => $license->id,
                    'school_partnership_id' => $partnershipId,
                    'user_id' => $userId,
                    'license_type' => $license->license_type,
                    'user_role' => $license->user_role,
                    'original_price' => $pricing['original_price'],
                    'discounted_price' => $pricing['discounted_price'],
                    'discount_amount' => $pricing['discount_amount'],
                    'start_date' => $license->start_date,
                    'end_date' => $license->end_date,
                ]
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('School license creation failed', [
                'partnership_id' => $partnershipId,
                'user_id' => $userId,
                'license_data' => $licenseData,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در ایجاد لایسنس مدرسه: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Verify school partnership
     */
    public function verifyPartnership(int $partnershipId): array
    {
        try {
            $partnership = SchoolPartnership::findOrFail($partnershipId);
            $partnership->verify();

            return [
                'success' => true,
                'message' => 'مشارکت مدرسه با موفقیت تأیید شد',
                'data' => [
                    'partnership_id' => $partnership->id,
                    'status' => $partnership->status,
                    'verified_at' => $partnership->verified_at,
                ]
            ];
        } catch (\Exception $e) {
            Log::error('School partnership verification failed', [
                'partnership_id' => $partnershipId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در تأیید مشارکت مدرسه: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get partnership details
     */
    public function getPartnership(int $partnershipId): array
    {
        $partnership = SchoolPartnership::with(['affiliatePartner.user:id,name'])
            ->findOrFail($partnershipId);

        return [
            'success' => true,
            'message' => 'جزئیات مشارکت مدرسه دریافت شد',
            'data' => [
                'id' => $partnership->id,
                'school_name' => $partnership->school_name,
                'school_type' => $partnership->school_type,
                'school_level' => $partnership->school_level,
                'location' => $partnership->location,
                'contact_person' => $partnership->contact_person,
                'contact_email' => $partnership->contact_email,
                'contact_phone' => $partnership->contact_phone,
                'student_count' => $partnership->student_count,
                'teacher_count' => $partnership->teacher_count,
                'partnership_model' => $partnership->partnership_model,
                'discount_rate' => $partnership->discount_rate,
                'revenue_share_rate' => $partnership->revenue_share_rate,
                'annual_license_fee' => $partnership->annual_license_fee,
                'max_student_capacity' => $partnership->max_student_capacity,
                'available_slots' => $partnership->getAvailableSlots(),
                'partnership_start_date' => $partnership->partnership_start_date,
                'partnership_end_date' => $partnership->partnership_end_date,
                'status' => $partnership->status,
                'is_verified' => $partnership->is_verified,
                'verified_at' => $partnership->verified_at,
                'remaining_days' => $partnership->getRemainingDays(),
                'is_active' => $partnership->isActive(),
                'partner' => [
                    'id' => $partnership->affiliatePartner->id,
                    'name' => $partnership->affiliatePartner->user->name,
                ],
                'statistics' => $partnership->getStatistics(),
            ]
        ];
    }

    /**
     * Get partnership licenses
     */
    public function getPartnershipLicenses(int $partnershipId, int $limit = 20, int $offset = 0): array
    {
        $licenses = SchoolLicense::bySchoolPartnership($partnershipId)
            ->with(['user:id,name,email'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->offset($offset)
            ->get()
            ->map(function($license) {
                return [
                    'id' => $license->id,
                    'user' => [
                        'id' => $license->user->id,
                        'name' => $license->user->name,
                        'email' => $license->user->email,
                    ],
                    'license_type' => $license->license_type,
                    'user_role' => $license->user_role,
                    'original_price' => $license->original_price,
                    'discounted_price' => $license->discounted_price,
                    'discount_amount' => $license->discount_amount,
                    'start_date' => $license->start_date,
                    'end_date' => $license->end_date,
                    'status' => $license->status,
                    'remaining_days' => $license->getRemainingDays(),
                    'activated_at' => $license->activated_at,
                ];
            });

        return [
            'success' => true,
            'message' => 'لیست لایسنس‌های مدرسه دریافت شد',
            'data' => [
                'licenses' => $licenses,
                'total' => SchoolLicense::bySchoolPartnership($partnershipId)->count(),
                'limit' => $limit,
                'offset' => $offset,
            ]
        ];
    }

    /**
     * Get user's school licenses
     */
    public function getUserLicenses(int $userId): array
    {
        $licenses = SchoolLicense::byUser($userId)
            ->with(['schoolPartnership.affiliatePartner.user:id,name'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($license) {
                return [
                    'id' => $license->id,
                    'school' => [
                        'id' => $license->schoolPartnership->affiliatePartner->id,
                        'name' => $license->schoolPartnership->affiliatePartner->user->name,
                        'school_name' => $license->schoolPartnership->school_name,
                        'partnership_model' => $license->schoolPartnership->partnership_model,
                    ],
                    'license_type' => $license->license_type,
                    'user_role' => $license->user_role,
                    'original_price' => $license->original_price,
                    'discounted_price' => $license->discounted_price,
                    'discount_amount' => $license->discount_amount,
                    'start_date' => $license->start_date,
                    'end_date' => $license->end_date,
                    'status' => $license->status,
                    'remaining_days' => $license->getRemainingDays(),
                    'is_active' => $license->isActive(),
                ];
            });

        return [
            'success' => true,
            'message' => 'لیست لایسنس‌های مدرسه کاربر دریافت شد',
            'data' => [
                'licenses' => $licenses,
                'statistics' => SchoolLicense::getUserStatistics($userId),
            ]
        ];
    }

    /**
     * Get partnership models
     */
    public function getPartnershipModels(): array
    {
        return [
            'success' => true,
            'message' => 'مدل‌های مشارکت دریافت شد',
            'data' => SchoolPartnership::getPartnershipModels()
        ];
    }

    /**
     * Get school types
     */
    public function getSchoolTypes(): array
    {
        return [
            'success' => true,
            'message' => 'انواع مدارس دریافت شد',
            'data' => SchoolPartnership::getSchoolTypes()
        ];
    }

    /**
     * Get school levels
     */
    public function getSchoolLevels(): array
    {
        return [
            'success' => true,
            'message' => 'سطوح مدارس دریافت شد',
            'data' => SchoolPartnership::getSchoolLevels()
        ];
    }

    /**
     * Get partnership benefits
     */
    public function getPartnershipBenefits(): array
    {
        return [
            'success' => true,
            'message' => 'مزایای مشارکت دریافت شد',
            'data' => SchoolPartnership::getPartnershipBenefits()
        ];
    }

    /**
     * Get global school statistics
     */
    public function getGlobalStatistics(): array
    {
        $cacheKey = 'global_school_statistics';
        
        $stats = Cache::remember($cacheKey, 1800, function() {
            $partnerships = SchoolPartnership::query();
            $licenses = SchoolLicense::query();
            
            return [
                'total_partnerships' => $partnerships->count(),
                'active_partnerships' => $partnerships->active()->count(),
                'expired_partnerships' => $partnerships->expired()->count(),
                'total_licenses' => $licenses->count(),
                'active_licenses' => $licenses->active()->count(),
                'expired_licenses' => $licenses->expired()->count(),
                'student_licenses' => $licenses->byType('student')->count(),
                'teacher_licenses' => $licenses->byType('teacher')->count(),
                'admin_licenses' => $licenses->byType('admin')->count(),
                'total_revenue' => $licenses->sum('discounted_price'),
                'total_discount_provided' => $licenses->sum('discount_amount'),
                'by_partnership_model' => $partnerships->groupBy('partnership_model')->count(),
                'by_school_type' => $partnerships->groupBy('school_type')->count(),
                'by_school_level' => $partnerships->groupBy('school_level')->count(),
            ];
        });

        return [
            'success' => true,
            'message' => 'آمار کلی مدارس دریافت شد',
            'data' => $stats
        ];
    }

    /**
     * Process expired licenses
     */
    public function processExpiredLicenses(): array
    {
        try {
            $expiredLicenses = SchoolLicense::where('status', 'active')
                ->where('end_date', '<', now())
                ->get();

            $processedCount = 0;
            foreach ($expiredLicenses as $license) {
                $license->expire();
                $processedCount++;
            }

            return [
                'success' => true,
                'message' => 'پردازش لایسنس‌های منقضی تکمیل شد',
                'data' => [
                    'processed_count' => $processedCount,
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Expired school licenses processing failed', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در پردازش لایسنس‌های منقضی: ' . $e->getMessage()
            ];
        }
    }
}
