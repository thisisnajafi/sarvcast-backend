<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class TwoFactorAuthController extends Controller
{
    protected $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    /**
     * Show 2FA verification form
     */
    public function showVerifyForm(): View
    {
        $user = Auth::user();
        
        return view('admin.auth.2fa-verify', compact('user'));
    }

    /**
     * Send 2FA code
     */
    public function sendCode(): RedirectResponse
    {
        $user = Auth::user();
        
        // Check rate limiting
        if ($this->smsService->hasTooManyAttempts($user->phone_number, 'admin_2fa')) {
            return redirect()->back()
                ->with('error', 'تعداد درخواست‌های شما بیش از حد مجاز است. لطفاً کمی صبر کنید.');
        }

        // Send OTP code
        $result = $this->smsService->sendOtp($user->phone_number, 'admin_2fa');

        if ($result['success']) {
            return redirect()->back()
                ->with('success', 'کد تایید دو مرحله‌ای به شماره شما ارسال شد');
        } else {
            return redirect()->back()
                ->with('error', 'خطا در ارسال کد تایید. لطفاً مجدداً تلاش کنید.');
        }
    }

    /**
     * Verify 2FA code
     */
    public function verify(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => 'required|string|size:6'
        ]);

        $user = Auth::user();
        $code = $request->code;

        // Verify OTP code
        $verified = $this->smsService->verifyOtp($user->phone_number, $code, 'admin_2fa');

        if ($verified) {
            // Set 2FA verified in session
            session(['2fa_verified' => true]);
            
            return redirect()->intended(route('admin.stories.index'))
                ->with('success', 'تایید دو مرحله‌ای با موفقیت انجام شد');
        } else {
            return redirect()->back()
                ->with('error', 'کد تایید نامعتبر یا منقضی شده است');
        }
    }

    /**
     * Skip 2FA (for development/testing)
     */
    public function skip(): RedirectResponse
    {
        // Only allow skipping in development
        if (app()->environment('local', 'development')) {
            session(['2fa_verified' => true]);
            return redirect()->route('admin.stories.index')
                ->with('warning', 'تایید دو مرحله‌ای رد شد (فقط در محیط توسعه)');
        }

        return redirect()->back()
            ->with('error', 'این عملیات در محیط تولید مجاز نیست');
    }
}
