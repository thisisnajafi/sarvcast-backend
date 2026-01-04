<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    protected $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    /**
     * Show admin login form
     */
    public function showLoginForm()
    {
        // If user is already authenticated as admin, redirect to dashboard
        if (Auth::guard('web')->check()) {
            $user = Auth::guard('web')->user();
            if (in_array($user->role, [User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN])) {
                return redirect()->route('admin.dashboard');
            }
        }
        
        return view('admin.auth.login');
    }

    /**
     * Send OTP code to admin phone number
     */
    public function sendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => [
                'required',
                'string',
                'regex:/^0[0-9]{10}$/'
            ],
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput($request->only('phone_number'));
        }

        $phoneNumber = $request->phone_number;

        // Check if admin or super admin user exists
        $user = User::where('phone_number', $phoneNumber)
                   ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN])
                   ->first();

        if (!$user) {
            return redirect()->back()
                ->withErrors(['phone_number' => 'شماره تلفن مدیر یافت نشد'])
                ->withInput($request->only('phone_number'));
        }

        if ($user->status !== 'active') {
            return redirect()->back()
                ->withErrors(['phone_number' => 'حساب کاربری شما غیرفعال است'])
                ->withInput($request->only('phone_number'));
        }

        // Check rate limiting
        if ($this->smsService->hasTooManyAttempts($phoneNumber, 'admin_login')) {
            return redirect()->back()
                ->withErrors(['phone_number' => 'تعداد درخواست‌های شما بیش از حد مجاز است. لطفاً کمی صبر کنید.'])
                ->withInput($request->only('phone_number'));
        }

        // Send OTP code
        $result = $this->smsService->sendOtp($phoneNumber, 'admin_login');

        if ($result['success']) {
            return redirect()->back()
                ->with('otp_sent', true)
                ->with('phone_number', $phoneNumber)
                ->with('success', 'کد تایید به شماره شما ارسال شد');
        } else {
            return redirect()->back()
                ->withErrors(['phone_number' => 'خطا در ارسال کد تایید. لطفاً مجدداً تلاش کنید.'])
                ->withInput($request->only('phone_number'));
        }
    }

    /**
     * Handle admin login with OTP verification
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => [
                'required',
                'string',
                'regex:/^0[0-9]{10}$/'
            ],
            'verification_code' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput($request->only('phone_number'))
                ->with('otp_sent', true);
        }

        $phoneNumber = $request->phone_number;
        $verificationCode = $request->verification_code;

        // Find admin or super admin user by phone number
        $user = User::where('phone_number', $phoneNumber)
                   ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN])
                   ->first();

        if (!$user) {
            return redirect()->back()
                ->withErrors(['phone_number' => 'شماره تلفن مدیر یافت نشد'])
                ->withInput($request->only('phone_number'))
                ->with('otp_sent', true);
        }

        if ($user->status !== 'active') {
            return redirect()->back()
                ->withErrors(['phone_number' => 'حساب کاربری شما غیرفعال است'])
                ->withInput($request->only('phone_number'))
                ->with('otp_sent', true);
        }

        // Verify OTP code
        $verification = $this->smsService->verifyOtp($phoneNumber, $verificationCode, 'admin_login');
        
        if (!$verification) {
            return redirect()->back()
                ->withErrors(['verification_code' => 'کد تایید نامعتبر یا منقضی شده است'])
                ->withInput($request->only('phone_number'))
                ->with('otp_sent', true);
        }

        // Update last login
        $user->update(['last_login_at' => now()]);

        // Login the user using web guard
        Auth::guard('web')->login($user);

        return redirect()->route('admin.dashboard')
            ->with('success', 'ورود با موفقیت انجام شد');
    }

    /**
     * Handle admin logout
     */
    public function logout(Request $request)
    {
        // Logout the user
        Auth::guard('web')->logout();

        // Clear session
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.auth.login')
            ->with('success', 'خروج با موفقیت انجام شد');
    }
}
