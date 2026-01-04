<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class UserAuthController extends Controller
{
    public function __construct(protected SmsService $smsService)
    {
    }

    /**
     * Show the user login form (phone + SMS code).
     */
    public function showLoginForm(Request $request)
    {
        // If user is already authenticated, redirect to dashboard or intended page
        if (Auth::check()) {
            $user = Auth::user();

            // If user is admin, redirect to admin dashboard
            if (in_array($user->role, [User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN])) {
                return redirect()->route('admin.dashboard');
            }

            // For regular users, redirect to checkout or home
            return redirect()->intended(route('checkout'));
        }

        $step = $request->session()->get('user_login_step', 'phone');
        $phone = $request->session()->get('user_login_phone');

        return view('auth.user-login', [
            'step' => $step,
            'phone' => old('phone_number', $phone),
        ]);
    }

    /**
     * Handle user login flow:
     *  - step=phone: validate phone and send SMS code
     *  - step=verify: validate phone + code, verify OTP, log user in (web guard)
     */
    public function login(Request $request)
    {
        $step = $request->input('step', 'phone');

        if ($step === 'phone') {
            $validator = Validator::make($request->all(), [
                'phone_number' => 'required|string|regex:/^09[0-9]{9}$/',
            ], [
                'phone_number.required' => 'شماره موبایل را وارد کنید',
                'phone_number.regex' => 'شماره موبایل نامعتبر است',
            ]);

            if ($validator->fails()) {
                return back()
                    ->withErrors($validator)
                    ->withInput()
                    ->with('user_login_step', 'phone');
            }

            $phoneNumber = $request->phone_number;

            // Send OTP code (same channel as API login)
            $result = $this->smsService->sendOtp($phoneNumber, 'login');

            if (!($result['success'] ?? false)) {
                return back()
                    ->withErrors(['phone_number' => 'خطا در ارسال کد تأیید، لطفاً دوباره تلاش کنید.'])
                    ->withInput()
                    ->with('user_login_step', 'phone');
            }

            $request->session()->put('user_login_step', 'verify');
            $request->session()->put('user_login_phone', $phoneNumber);

            return back()->with('status', 'کد تأیید به شماره شما ارسال شد.');
        }

        // Verify step
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string|regex:/^09[0-9]{9}$/',
            'verification_code' => 'required|string|size:6',
        ], [
            'phone_number.required' => 'شماره موبایل را وارد کنید',
            'phone_number.regex' => 'شماره موبایل نامعتبر است',
            'verification_code.required' => 'کد تأیید را وارد کنید',
            'verification_code.size' => 'کد تأیید باید ۶ رقم باشد',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput()
                ->with('user_login_step', 'verify');
        }

        $phoneNumber = $request->phone_number;
        $code = $request->verification_code;

        // Verify OTP using same service as mobile API
        $verification = $this->smsService->verifyOtp($phoneNumber, $code, 'login');
        if (!$verification) {
            return back()
                ->withErrors(['verification_code' => 'کد تأیید نامعتبر یا منقضی شده است'])
                ->withInput()
                ->with('user_login_step', 'verify');
        }

        $user = User::where('phone_number', $phoneNumber)->first();
        if (!$user) {
            return back()
                ->withErrors(['phone_number' => 'کاربری با این شماره تلفن یافت نشد'])
                ->withInput()
                ->with('user_login_step', 'verify');
        }

        if ($user->status !== 'active') {
            return back()
                ->withErrors(['phone_number' => 'حساب کاربری شما غیرفعال است'])
                ->withInput()
                ->with('user_login_step', 'verify');
        }

        // Update last login and log in via web session
        $user->update(['last_login_at' => now()]);
        Auth::login($user);

        // Clear temporary login state
        $request->session()->forget(['user_login_step', 'user_login_phone']);

        // Redirect to originally intended URL (e.g. /checkout)
        return redirect()->intended(route('checkout'));
    }
}


