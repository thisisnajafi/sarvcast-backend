<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SmsService;
use App\Events\NewUserRegistrationEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    protected $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    /**
     * Send SMS verification code for registration/login
     * Detects if user is new or existing and returns appropriate response
     */
    public function sendVerificationCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string|regex:/^09[0-9]{9}$/',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'شماره تلفن نامعتبر است',
                'errors' => $validator->errors()
            ], 422);
        }

        $phoneNumber = $request->phone_number;

        // Check rate limiting
        if ($this->smsService->hasTooManyAttempts($phoneNumber, 'login')) {
            return response()->json([
                'success' => false,
                'message' => 'تعداد درخواست‌های شما بیش از حد مجاز است. لطفاً کمی صبر کنید.'
            ], 429);
        }

        // Check if user exists
        $user = User::where('phone_number', $phoneNumber)->first();
        $isNewUser = !$user;

        // Send OTP code
        $result = $this->smsService->sendOtp($phoneNumber, 'login');

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => 'کد تایید به شماره شما ارسال شد',
                'data' => [
                    'is_new_user' => $isNewUser,
                    'expires_in' => 300, // 5 minutes
                    'next_step' => $isNewUser ? 'registration' : 'login'
                ]
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'خطا در ارسال کد تایید. لطفاً مجدداً تلاش کنید.'
            ], 400);
        }
    }

    /**
     * Register a new user with SMS verification
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string|regex:/^09[0-9]{9}$/',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'verification_code' => 'required|string|size:6',
            'role' => 'nullable|in:parent,child,basic',
            'parent_id' => 'nullable|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'اطلاعات وارد شده نامعتبر است',
                'errors' => $validator->errors()
            ], 422);
        }

        $phoneNumber = $request->phone_number;
        $verificationCode = $request->verification_code;

        // Verify SMS code
        $verification = $this->smsService->verifyOtp($phoneNumber, $verificationCode, 'login');

        if (!$verification) {
            return response()->json([
                'success' => false,
                'message' => 'کد تایید نامعتبر یا منقضی شده است'
            ], 400);
        }

        // Check if user already exists
        $existingUser = User::where('phone_number', $phoneNumber)->first();
        if ($existingUser) {
            return response()->json([
                'success' => false,
                'message' => 'کاربری با این شماره تلفن قبلاً ثبت شده است'
            ], 409);
        }

        // Create user
        $user = User::create([
            'phone_number' => $phoneNumber,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'role' => $request->role ?? 'basic', // Default to 'basic' if no role provided
            'parent_id' => $request->parent_id,
            'status' => 'active',
            'phone_verified_at' => now(),
        ]);

        // Dispatch new user registration event for Telegram notification
        event(new NewUserRegistrationEvent($user));

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'ثبت‌نام با موفقیت انجام شد',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'phone_number' => $user->phone_number,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'role' => $user->role,
                    'status' => $user->status,
                ],
                'token' => $token
            ]
        ], 201);
    }

    /**
     * Login user with SMS verification
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string|regex:/^09[0-9]{9}$/',
            'verification_code' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'اطلاعات وارد شده نامعتبر است',
                'errors' => $validator->errors()
            ], 422);
        }

        $phoneNumber = $request->phone_number;
        $verificationCode = $request->verification_code;

        // Verify SMS code
        $verification = $this->smsService->verifyOtp($phoneNumber, $verificationCode, 'login');

        if (!$verification) {
            return response()->json([
                'success' => false,
                'message' => 'کد تایید نامعتبر یا منقضی شده است'
            ], 400);
        }

        // Find user by phone number
        $user = User::where('phone_number', $phoneNumber)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'کاربری با این شماره تلفن یافت نشد'
            ], 404);
        }

        if ($user->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'حساب کاربری شما غیرفعال است'
            ], 403);
        }

        // Update last login
        $user->update(['last_login_at' => now()]);

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'ورود با موفقیت انجام شد',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'phone_number' => $user->phone_number,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'role' => $user->role,
                    'status' => $user->status,
                ],
                'token' => $token
            ]
        ]);
    }

    /**
     * Send OTP code to admin phone number (API)
     */
    public function sendAdminOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string|regex:/^09[0-9]{9}$/',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'اطلاعات وارد شده نامعتبر است',
                'errors' => $validator->errors()
            ], 422);
        }

        $phoneNumber = $request->phone_number;

        // Check if admin or super admin user exists
        $user = User::where('phone_number', $phoneNumber)
                   ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN])
                   ->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'شماره تلفن مدیر یافت نشد'
            ], 404);
        }

        if ($user->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'حساب کاربری شما غیرفعال است'
            ], 403);
        }

        // Check rate limiting
        if ($this->smsService->hasTooManyAttempts($phoneNumber, 'admin_login')) {
            return response()->json([
                'success' => false,
                'message' => 'تعداد درخواست‌های شما بیش از حد مجاز است. لطفاً کمی صبر کنید.'
            ], 429);
        }

        // Send OTP code
        $result = $this->smsService->sendOtp($phoneNumber, 'admin_login');

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => 'کد تایید به شماره شما ارسال شد',
                'data' => [
                    'expires_in' => 300, // 5 minutes
                ]
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'خطا در ارسال کد تایید. لطفاً مجدداً تلاش کنید.'
            ], 400);
        }
    }

    /**
     * Admin login with phone number and OTP verification (API)
     */
    public function adminLogin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string|regex:/^09[0-9]{9}$/',
            'verification_code' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'اطلاعات وارد شده نامعتبر است',
                'errors' => $validator->errors()
            ], 422);
        }

        $phoneNumber = $request->phone_number;
        $verificationCode = $request->verification_code;

        // Find admin or super admin user by phone number
        $user = User::where('phone_number', $phoneNumber)
                   ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN])
                   ->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'شماره تلفن مدیر یافت نشد'
            ], 404);
        }

        if ($user->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'حساب کاربری شما غیرفعال است'
            ], 403);
        }

        // Verify OTP code
        $verification = $this->smsService->verifyOtp($phoneNumber, $verificationCode, 'admin_login');

        if (!$verification) {
            return response()->json([
                'success' => false,
                'message' => 'کد تایید نامعتبر یا منقضی شده است'
            ], 400);
        }

        // Update last login
        $user->update(['last_login_at' => now()]);

        $token = $user->createToken('admin-auth-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'ورود مدیر با موفقیت انجام شد',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'phone_number' => $user->phone_number,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'role' => $user->role,
                    'status' => $user->status,
                ],
                'token' => $token
            ]
        ]);
    }

    /**
     * Logout user
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'خروج با موفقیت انجام شد'
        ]);
    }

    /**
     * Get user profile
     */
    public function profile(Request $request)
    {
        $user = $request->user();

        // Get premium information with enhanced debugging
        $activeSubscription = $user->activeSubscription;

        // Enhanced premium detection with fallback
        $isPremium = false;
        $subscriptionStatus = 'none';
        $subscriptionType = null;
        $subscriptionEndDate = null;
        $daysRemaining = 0;

        if ($activeSubscription) {
            $isPremium = true;
            $subscriptionStatus = 'active';
            $subscriptionType = $activeSubscription->type;
            $subscriptionEndDate = $activeSubscription->end_date;
            $daysRemaining = max(0, now()->diffInDays($activeSubscription->end_date, false));
        } else {
            // Fallback: Check for any active subscription manually
            $manualActiveSubscription = \App\Models\Subscription::where('user_id', $user->id)
                ->where('status', 'active')
                ->where('end_date', '>', now())
                ->first();

            if ($manualActiveSubscription) {
                $isPremium = true;
                $subscriptionStatus = 'active';
                $subscriptionType = $manualActiveSubscription->type;
                $subscriptionEndDate = $manualActiveSubscription->end_date;
                $daysRemaining = max(0, now()->diffInDays($manualActiveSubscription->end_date, false));

                // Log the issue for debugging
                \Log::warning('Premium detection fallback used', [
                    'user_id' => $user->id,
                    'subscription_id' => $manualActiveSubscription->id,
                    'reason' => 'activeSubscription relationship returned null but manual query found active subscription'
                ]);
            }
        }

        $premiumInfo = [
            'is_premium' => $isPremium,
            'subscription_status' => $subscriptionStatus,
            'subscription_type' => $subscriptionType,
            'subscription_end_date' => $subscriptionEndDate,
            'days_remaining' => $daysRemaining,
        ];

        $jsonResponse = response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'phone_number' => $user->phone_number,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'role' => $user->role,
                'status' => $user->status,
                'profile_image_url' => $user->profile_image_url,
                'parent_id' => $user->parent_id,
                'timezone' => $user->timezone,
                'preferences' => $user->preferences,
                'phone_verified_at' => $user->phone_verified_at,
                'last_login_at' => $user->last_login_at,
                'created_at' => $user->created_at,
                'premium' => $premiumInfo,
            ]
        ]);
        // Disable caching for user profile - it needs to be retrieved immediately
        $jsonResponse->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate, max-age=0');
        $jsonResponse->headers->set('Pragma', 'no-cache');
        $jsonResponse->headers->set('Expires', '0');
        return $jsonResponse;
    }

    /**
     * Update user profile
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'first_name' => 'sometimes|required|string|max:100',
            'last_name' => 'sometimes|required|string|max:100',
            'profile_image_url' => 'sometimes|nullable|url|max:500',
            'timezone' => 'sometimes|string|max:50',
            'preferences' => 'sometimes|nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'اطلاعات وارد شده نامعتبر است',
                'errors' => $validator->errors()
            ], 422);
        }

        $user->update($request->only([
            'first_name',
            'last_name',
            'profile_image_url',
            'timezone',
            'preferences'
        ]));

        $jsonResponse = response()->json([
            'success' => true,
            'message' => 'پروفایل با موفقیت به‌روزرسانی شد',
            'data' => [
                'id' => $user->id,
                'phone_number' => $user->phone_number,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'role' => $user->role,
                'status' => $user->status,
                'profile_image_url' => $user->profile_image_url,
                'timezone' => $user->timezone,
                'preferences' => $user->preferences,
            ]
        ]);
        // Disable caching for profile updates - they need to be retrieved immediately
        $jsonResponse->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate, max-age=0');
        $jsonResponse->headers->set('Pragma', 'no-cache');
        $jsonResponse->headers->set('Expires', '0');
        return $jsonResponse;
    }

    /**
     * Upload profile photo
     */
    public function uploadProfilePhoto(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'photo' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048', // 2MB max
        ], [
            'photo.required' => 'تصویر پروفایل الزامی است',
            'photo.image' => 'فایل باید یک تصویر باشد',
            'photo.mimes' => 'فرمت تصویر باید یکی از موارد زیر باشد: jpeg, png, jpg, webp',
            'photo.max' => 'حجم تصویر نمی‌تواند بیش از 2 مگابایت باشد',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'اطلاعات وارد شده نامعتبر است',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $file = $request->file('photo');
            $filename = time() . '_' . $user->id . '_profile.' . $file->getClientOriginalExtension();

            // Ensure directory exists
            $directory = public_path('images/users/profiles');
            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }

            // Delete old profile photo if exists
            if ($user->profile_image_url && !filter_var($user->profile_image_url, FILTER_VALIDATE_URL)) {
                $oldImagePath = public_path('images/' . $user->profile_image_url);
                if (file_exists($oldImagePath)) {
                    @unlink($oldImagePath);
                }
            }

            // Move image to public/images/users/profiles
            $file->move($directory, $filename);

            // Store relative path
            $imagePath = 'users/profiles/' . $filename;
            $user->update(['profile_image_url' => $imagePath]);

            // Get full URL using HasImageUrl trait
            $updatedUser = $user->fresh();
            $url = $updatedUser->profile_image_url;

            $jsonResponse = response()->json([
                'success' => true,
                'message' => 'تصویر پروفایل با موفقیت آپلود شد.',
                'data' => [
                    'profile_image_url' => $url,
                    'user' => [
                        'id' => $updatedUser->id,
                        'phone_number' => $updatedUser->phone_number,
                        'first_name' => $updatedUser->first_name,
                        'last_name' => $updatedUser->last_name,
                        'role' => $updatedUser->role,
                        'status' => $updatedUser->status,
                        'profile_image_url' => $url,
                        'timezone' => $updatedUser->timezone,
                    ]
                ]
            ]);
            // Disable caching for profile photo uploads - they need to be retrieved immediately
            $jsonResponse->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate, max-age=0');
            $jsonResponse->headers->set('Pragma', 'no-cache');
            $jsonResponse->headers->set('Expires', '0');
            return $jsonResponse;

        } catch (\Exception $e) {
            \Log::error('Profile photo upload failed', [
                'error' => $e->getMessage(),
                'user_id' => $user->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در آپلود تصویر پروفایل: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Change admin password
     */
    public function changePassword(Request $request)
    {
        $user = $request->user();

        // Only admins can change password
        if ($user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'دسترسی غیرمجاز'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'اطلاعات وارد شده نامعتبر است',
                'errors' => $validator->errors()
            ], 422);
        }

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'رمز عبور فعلی اشتباه است'
            ], 400);
        }

        $user->update([
            'password' => Hash::make($request->new_password)
        ]);

        return response()->json([
            'success' => true,
            'message' => 'رمز عبور با موفقیت تغییر کرد'
        ]);
    }

    /**
     * Debug premium status for troubleshooting
     */
    public function debugPremium(Request $request)
    {
        $user = $request->user();

        // Get all subscriptions for debugging
        $allSubscriptions = \App\Models\Subscription::where('user_id', $user->id)->get();

        // Test activeSubscription relationship
        $activeSubscription = $user->activeSubscription;

        // Test hasActiveSubscription method
        $hasActiveSubscription = $user->hasActiveSubscription();

        // Manual query for active subscriptions
        $manualActiveSubscriptions = \App\Models\Subscription::where('user_id', $user->id)
            ->where('status', 'active')
            ->where('end_date', '>', now())
            ->get();

        // Test AccessControlService
        $accessControlService = app(\App\Services\AccessControlService::class);
        $hasPremiumAccess = $accessControlService->hasPremiumAccess($user->id);
        $accessLevel = $accessControlService->getUserAccessLevel($user->id);

        $debugInfo = [
            'user_id' => $user->id,
            'current_time' => now(),
            'timezone' => config('app.timezone'),
            'all_subscriptions' => $allSubscriptions->map(function($sub) {
                return [
                    'id' => $sub->id,
                    'type' => $sub->type,
                    'status' => $sub->status,
                    'start_date' => $sub->start_date,
                    'end_date' => $sub->end_date,
                    'is_status_active' => $sub->status === 'active',
                    'is_end_date_future' => $sub->end_date ? $sub->end_date > now() : false,
                    'should_be_active' => $sub->status === 'active' && $sub->end_date && $sub->end_date > now(),
                    'created_at' => $sub->created_at,
                    'updated_at' => $sub->updated_at
                ];
            }),
            'active_subscription_relationship' => $activeSubscription ? [
                'id' => $activeSubscription->id,
                'type' => $activeSubscription->type,
                'status' => $activeSubscription->status,
                'end_date' => $activeSubscription->end_date
            ] : null,
            'has_active_subscription_method' => $hasActiveSubscription,
            'manual_active_subscriptions' => $manualActiveSubscriptions->map(function($sub) {
                return [
                    'id' => $sub->id,
                    'type' => $sub->type,
                    'status' => $sub->status,
                    'end_date' => $sub->end_date
                ];
            }),
            'access_control_service' => [
                'has_premium_access' => $hasPremiumAccess,
                'access_level' => $accessLevel
            ]
        ];

        return response()->json([
            'success' => true,
            'debug_info' => $debugInfo
        ]);
    }

    /**
     * Refresh token
     */
    public function refresh(Request $request)
    {
        $user = $request->user();

        // Revoke current token
        $request->user()->currentAccessToken()->delete();

        // Create new token
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'توکن با موفقیت به‌روزرسانی شد',
            'data' => [
                'token' => $token
            ]
        ]);
    }
}
