<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    /**
     * Display the admin profile page
     */
    public function index()
    {
        $user = auth('web')->user();
        
        return view('admin.profile.index', compact('user'));
    }

    /**
     * Update admin password
     */
    public function updatePassword(Request $request)
    {
        $user = auth('web')->user();

        $validator = Validator::make($request->all(), [
            'current_password' => ['required', 'current_password:web'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
            'password_confirmation' => ['required'],
        ], [
            'current_password.current_password' => 'رمز عبور فعلی اشتباه است.',
            'password.confirmed' => 'تأیید رمز عبور مطابقت ندارد.',
            'password.min' => 'رمز عبور باید حداقل 8 کاراکتر باشد.',
            'password.mixed_case' => 'رمز عبور باید شامل حروف بزرگ و کوچک باشد.',
            'password.numbers' => 'رمز عبور باید شامل اعداد باشد.',
            'password.symbols' => 'رمز عبور باید شامل نمادهای خاص باشد.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput()
                ->with('error', 'خطا در تغییر رمز عبور');
        }

        try {
            $user->update([
                'password' => Hash::make($request->password)
            ]);

            return redirect()->back()
                ->with('success', 'رمز عبور با موفقیت تغییر یافت.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'خطا در تغییر رمز عبور. لطفاً دوباره تلاش کنید.');
        }
    }

    /**
     * Update admin profile information
     */
    public function updateInfo(Request $request)
    {
        $user = auth('web')->user();

        $validator = Validator::make($request->all(), [
            'first_name' => ['required', 'string', 'max:50'],
            'last_name' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:100', 'unique:users,email,' . $user->id],
            'phone_number' => ['required', 'string', 'max:20', 'unique:users,phone_number,' . $user->id],
        ], [
            'first_name.required' => 'نام الزامی است.',
            'first_name.max' => 'نام نمی‌تواند بیش از 50 کاراکتر باشد.',
            'last_name.required' => 'نام خانوادگی الزامی است.',
            'last_name.max' => 'نام خانوادگی نمی‌تواند بیش از 50 کاراکتر باشد.',
            'email.required' => 'ایمیل الزامی است.',
            'email.email' => 'فرمت ایمیل صحیح نیست.',
            'email.unique' => 'این ایمیل قبلاً استفاده شده است.',
            'phone_number.required' => 'شماره تلفن الزامی است.',
            'phone_number.unique' => 'این شماره تلفن قبلاً استفاده شده است.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput()
                ->with('error', 'خطا در به‌روزرسانی اطلاعات');
        }

        try {
            $user->update([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'phone_number' => $request->phone_number,
            ]);

            return redirect()->back()
                ->with('success', 'اطلاعات با موفقیت به‌روزرسانی شد.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'خطا در به‌روزرسانی اطلاعات. لطفاً دوباره تلاش کنید.');
        }
    }
}
