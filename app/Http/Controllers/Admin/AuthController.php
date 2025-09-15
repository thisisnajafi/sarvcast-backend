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
            'phone_number' => 'required|string|regex:/^(\+98|0)?9[0-9]{9}$/',
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

        // Create token and store in session
        $token = $user->createToken('admin-web-token')->plainTextToken;
        session(['admin_token' => $token]);

        return redirect()->route('admin.dashboard')
            ->with('success', 'ورود با موفقیت انجام شد');
    }

    /**
     * Handle admin logout
     */
    public function logout(Request $request)
    {
        $token = session('admin_token');
        
        if ($token) {
            // Revoke token
            $user = $request->user();
            if ($user) {
                $user->tokens()->where('name', 'admin-web-token')->delete();
            }
        }

        // Clear session
        session()->forget('admin_token');
        session()->flush();

        return redirect()->route('admin.auth.login')
            ->with('success', 'خروج با موفقیت انجام شد');
    }
}