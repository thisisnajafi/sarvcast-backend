<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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
     */
    public function sendVerificationCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string|regex:/^(\+98|0)?9[0-9]{9}$/',
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
        $rateLimit = $this->smsService->getRateLimitInfo($phoneNumber);
        if (!$rateLimit['can_send']) {
            return response()->json([
                'success' => false,
                'message' => 'تعداد درخواست‌های شما بیش از حد مجاز است. لطفاً کمی صبر کنید.'
            ], 429);
        }

        // Record attempt
        $this->smsService->recordAttempt($phoneNumber);

        // Send verification code
        $result = $this->smsService->sendVerificationCode($phoneNumber);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => [
                    'expires_in' => $result['expires_in']
                ]
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => $result['message']
            ], 400);
        }
    }

    /**
     * Register a new user with SMS verification
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string|regex:/^(\+98|0)?9[0-9]{9}$/|unique:users',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'verification_code' => 'required|string|size:6',
            'role' => 'required|in:parent,child',
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
        $verification = $this->smsService->verifyCode($phoneNumber, $verificationCode);
        
        if (!$verification['success']) {
            return response()->json([
                'success' => false,
                'message' => $verification['message']
            ], 400);
        }

        // Create user
        $user = User::create([
            'phone_number' => $phoneNumber,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'role' => $request->role,
            'parent_id' => $request->parent_id,
            'status' => 'active',
            'phone_verified_at' => now(),
        ]);

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
            'phone_number' => 'required|string|regex:/^(\+98|0)?9[0-9]{9}$/',
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
        $verification = $this->smsService->verifyCode($phoneNumber, $verificationCode);
        
        if (!$verification['success']) {
            return response()->json([
                'success' => false,
                'message' => $verification['message']
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
     * Admin login with phone number and password
     */
    public function adminLogin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string|regex:/^(\+98|0)?9[0-9]{9}$/',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'اطلاعات وارد شده نامعتبر است',
                'errors' => $validator->errors()
            ], 422);
        }

        $phoneNumber = $request->phone_number;
        $password = $request->password;

        // Find admin user by phone number
        $user = User::where('phone_number', $phoneNumber)
                   ->where('role', 'admin')
                   ->first();

        if (!$user || !Hash::check($password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'شماره تلفن یا رمز عبور اشتباه است'
            ], 401);
        }

        if ($user->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'حساب کاربری شما غیرفعال است'
            ], 403);
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

        return response()->json([
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
            ]
        ]);
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

        return response()->json([
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