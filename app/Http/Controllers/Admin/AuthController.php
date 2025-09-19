<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * Show admin login form
     */
    public function showLoginForm()
    {
        return view('admin.auth.login');
    }

    /**
     * Handle admin login
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => [
                'required',
                'string',
                'regex:/^0[0-9]{10}$/'
            ],
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput($request->except('password'));
        }

        $phoneNumber = $request->phone_number;
        $password = $request->password;

        // Find admin user by phone number
        $user = User::where('phone_number', $phoneNumber)
                   ->where('role', 'admin')
                   ->first();

        if (!$user || !Hash::check($password, $user->password)) {
            return redirect()->back()
                ->withErrors(['phone_number' => 'شماره تلفن یا رمز عبور اشتباه است'])
                ->withInput($request->except('password'));
        }

        if ($user->status !== 'active') {
            return redirect()->back()
                ->withErrors(['phone_number' => 'حساب کاربری شما غیرفعال است'])
                ->withInput($request->except('password'));
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
