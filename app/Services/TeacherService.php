<?php

namespace App\Services;

use App\Models\TeacherAccount;
use App\Models\StudentLicense;
use App\Models\User;
use App\Models\Subscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TeacherService
{
    /**
     * Create teacher account
     */
    public function createTeacherAccount(int $userId, array $teacherData): array
    {
        try {
            DB::beginTransaction();

            // Check if user already has a teacher account
            if (TeacherAccount::where('user_id', $userId)->exists()) {
                return [
                    'success' => false,
                    'message' => 'حساب معلمی قبلاً ایجاد شده است'
                ];
            }

            $teacherAccount = TeacherAccount::create([
                'user_id' => $userId,
                'institution_name' => $teacherData['institution_name'],
                'institution_type' => $teacherData['institution_type'],
                'teaching_subject' => $teacherData['teaching_subject'],
                'years_of_experience' => $teacherData['years_of_experience'],
                'certification_number' => $teacherData['certification_number'] ?? null,
                'certification_authority' => $teacherData['certification_authority'] ?? null,
                'certification_date' => $teacherData['certification_date'] ?? null,
                'max_student_licenses' => $teacherData['max_student_licenses'] ?? 100,
                'discount_rate' => $teacherData['discount_rate'] ?? 50.00,
                'verification_documents' => $teacherData['verification_documents'] ?? null,
            ]);

            DB::commit();

            return [
                'success' => true,
                'message' => 'حساب معلمی با موفقیت ایجاد شد',
                'data' => [
                    'teacher_account_id' => $teacherAccount->id,
                    'institution_name' => $teacherAccount->institution_name,
                    'institution_type' => $teacherAccount->institution_type,
                    'teaching_subject' => $teacherAccount->teaching_subject,
                    'status' => $teacherAccount->status,
                    'max_student_licenses' => $teacherAccount->max_student_licenses,
                    'discount_rate' => $teacherAccount->discount_rate,
                ]
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Teacher account creation failed', [
                'user_id' => $userId,
                'teacher_data' => $teacherData,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در ایجاد حساب معلمی: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Verify teacher account
     */
    public function verifyTeacherAccount(int $teacherAccountId): array
    {
        try {
            $teacherAccount = TeacherAccount::findOrFail($teacherAccountId);
            $teacherAccount->verify();

            return [
                'success' => true,
                'message' => 'حساب معلمی با موفقیت تأیید شد',
                'data' => [
                    'teacher_account_id' => $teacherAccount->id,
                    'status' => $teacherAccount->status,
                    'verified_at' => $teacherAccount->verified_at,
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Teacher account verification failed', [
                'teacher_account_id' => $teacherAccountId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در تأیید حساب معلمی: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Create student license
     */
    public function createStudentLicense(int $teacherAccountId, int $studentUserId, array $licenseData): array
    {
        try {
            DB::beginTransaction();

            $teacherAccount = TeacherAccount::findOrFail($teacherAccountId);
            
            // Check if teacher can create student licenses
            if (!$teacherAccount->canCreateStudentLicense()) {
                return [
                    'success' => false,
                    'message' => 'امکان ایجاد لایسنس دانش‌آموزی وجود ندارد'
                ];
            }

            // Check if student already has a license from this teacher
            if (StudentLicense::where('teacher_account_id', $teacherAccountId)
                ->where('student_user_id', $studentUserId)
                ->where('status', 'active')
                ->exists()) {
                return [
                    'success' => false,
                    'message' => 'دانش‌آموز قبلاً لایسنس فعال دارد'
                ];
            }

            // Calculate pricing
            $originalPrice = $licenseData['original_price'];
            $pricing = $teacherAccount->calculateDiscountedPrice($originalPrice);

            // Create student license
            $studentLicense = StudentLicense::create([
                'teacher_account_id' => $teacherAccountId,
                'student_user_id' => $studentUserId,
                'license_type' => $licenseData['license_type'] ?? 'individual',
                'original_price' => $pricing['original_price'],
                'discounted_price' => $pricing['discounted_price'],
                'discount_amount' => $pricing['discount_amount'],
                'start_date' => $licenseData['start_date'],
                'end_date' => $licenseData['end_date'],
                'status' => 'active',
                'activated_at' => now(),
            ]);

            DB::commit();

            return [
                'success' => true,
                'message' => 'لایسنس دانش‌آموزی با موفقیت ایجاد شد',
                'data' => [
                    'license_id' => $studentLicense->id,
                    'teacher_account_id' => $teacherAccountId,
                    'student_user_id' => $studentUserId,
                    'original_price' => $pricing['original_price'],
                    'discounted_price' => $pricing['discounted_price'],
                    'discount_amount' => $pricing['discount_amount'],
                    'start_date' => $studentLicense->start_date,
                    'end_date' => $studentLicense->end_date,
                ]
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Student license creation failed', [
                'teacher_account_id' => $teacherAccountId,
                'student_user_id' => $studentUserId,
                'license_data' => $licenseData,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در ایجاد لایسنس دانش‌آموزی: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get teacher account details
     */
    public function getTeacherAccount(int $userId): array
    {
        $teacherAccount = TeacherAccount::where('user_id', $userId)->first();
        
        if (!$teacherAccount) {
            return [
                'success' => false,
                'message' => 'حساب معلمی یافت نشد'
            ];
        }

        return [
            'success' => true,
            'message' => 'حساب معلمی دریافت شد',
            'data' => [
                'id' => $teacherAccount->id,
                'institution_name' => $teacherAccount->institution_name,
                'institution_type' => $teacherAccount->institution_type,
                'teaching_subject' => $teacherAccount->teaching_subject,
                'years_of_experience' => $teacherAccount->years_of_experience,
                'student_count' => $teacherAccount->student_count,
                'max_student_licenses' => $teacherAccount->max_student_licenses,
                'available_slots' => $teacherAccount->getAvailableSlots(),
                'discount_rate' => $teacherAccount->discount_rate,
                'status' => $teacherAccount->status,
                'is_verified' => $teacherAccount->is_verified,
                'verified_at' => $teacherAccount->verified_at,
                'expires_at' => $teacherAccount->expires_at,
                'statistics' => $teacherAccount->getStatistics(),
            ]
        ];
    }

    /**
     * Get teacher's student licenses
     */
    public function getTeacherStudentLicenses(int $teacherAccountId, int $limit = 20, int $offset = 0): array
    {
        $licenses = StudentLicense::byTeacher($teacherAccountId)
            ->with(['studentUser:id,name,email'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->offset($offset)
            ->get()
            ->map(function($license) {
                return [
                    'id' => $license->id,
                    'student' => [
                        'id' => $license->studentUser->id,
                        'name' => $license->studentUser->name,
                        'email' => $license->studentUser->email,
                    ],
                    'license_type' => $license->license_type,
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
            'message' => 'لیست لایسنس‌های دانش‌آموزی دریافت شد',
            'data' => [
                'licenses' => $licenses,
                'total' => StudentLicense::byTeacher($teacherAccountId)->count(),
                'limit' => $limit,
                'offset' => $offset,
            ]
        ];
    }

    /**
     * Get student's licenses
     */
    public function getStudentLicenses(int $studentUserId): array
    {
        $licenses = StudentLicense::byStudent($studentUserId)
            ->with(['teacherAccount.user:id,name'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($license) {
                return [
                    'id' => $license->id,
                    'teacher' => [
                        'id' => $license->teacherAccount->user->id,
                        'name' => $license->teacherAccount->user->name,
                        'institution_name' => $license->teacherAccount->institution_name,
                    ],
                    'license_type' => $license->license_type,
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
            'message' => 'لیست لایسنس‌های دانش‌آموزی دریافت شد',
            'data' => [
                'licenses' => $licenses,
                'statistics' => StudentLicense::getStudentStatistics($studentUserId),
            ]
        ];
    }

    /**
     * Get teacher program benefits
     */
    public function getProgramBenefits(): array
    {
        return [
            'success' => true,
            'message' => 'مزایای برنامه معلمی دریافت شد',
            'data' => TeacherAccount::getProgramBenefits()
        ];
    }

    /**
     * Get institution types
     */
    public function getInstitutionTypes(): array
    {
        return [
            'success' => true,
            'message' => 'انواع مؤسسات دریافت شد',
            'data' => TeacherAccount::getInstitutionTypes()
        ];
    }

    /**
     * Get teaching subjects
     */
    public function getTeachingSubjects(): array
    {
        return [
            'success' => true,
            'message' => 'موضوعات تدریس دریافت شد',
            'data' => TeacherAccount::getTeachingSubjects()
        ];
    }

    /**
     * Get global teacher statistics
     */
    public function getGlobalStatistics(): array
    {
        $cacheKey = 'global_teacher_statistics';
        
        $stats = Cache::remember($cacheKey, 1800, function() {
            $teachers = TeacherAccount::verified();
            $licenses = StudentLicense::query();
            
            return [
                'total_teachers' => $teachers->count(),
                'active_teachers' => TeacherAccount::active()->count(),
                'total_student_licenses' => $licenses->count(),
                'active_student_licenses' => $licenses->active()->count(),
                'expired_student_licenses' => $licenses->expired()->count(),
                'total_revenue' => $licenses->sum('discounted_price'),
                'total_discount_provided' => $licenses->sum('discount_amount'),
                'by_institution_type' => $teachers->groupBy('institution_type')->count(),
                'by_teaching_subject' => $teachers->groupBy('teaching_subject')->count(),
            ];
        });

        return [
            'success' => true,
            'message' => 'آمار کلی برنامه معلمی دریافت شد',
            'data' => $stats
        ];
    }

    /**
     * Process expired licenses
     */
    public function processExpiredLicenses(): array
    {
        try {
            $expiredLicenses = StudentLicense::where('status', 'active')
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
            Log::error('Expired licenses processing failed', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در پردازش لایسنس‌های منقضی: ' . $e->getMessage()
            ];
        }
    }
}
