<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AdminPushNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ContactController extends Controller
{
    public function __construct(
        protected AdminPushNotificationService $adminPushService
    ) {}

    /**
     * Handle contact form submission
     */
    public function submit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'message' => 'required|string|max:2000',
        ], [
            'name.required' => 'نام الزامی است.',
            'name.string' => 'نام باید متن باشد.',
            'name.max' => 'نام نمی‌تواند بیشتر از 255 کاراکتر باشد.',
            'email.required' => 'ایمیل الزامی است.',
            'email.email' => 'ایمیل معتبر نیست.',
            'email.max' => 'ایمیل نمی‌تواند بیشتر از 255 کاراکتر باشد.',
            'phone.string' => 'شماره تماس باید متن باشد.',
            'phone.max' => 'شماره تماس نمی‌تواند بیشتر از 20 کاراکتر باشد.',
            'message.required' => 'پیام الزامی است.',
            'message.string' => 'پیام باید متن باشد.',
            'message.max' => 'پیام نمی‌تواند بیشتر از 2000 کاراکتر باشد.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در اعتبارسنجی داده‌ها',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $sent = $this->adminPushService->sendContactFormNotification(
                $request->name,
                $request->email,
                $request->phone,
                $request->message
            );

            if ($sent > 0) {
                Log::info('Contact form submitted and admins notified', [
                    'name' => $request->name,
                    'email' => $request->email,
                    'phone' => $request->phone,
                    'admin_count' => $sent,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'پیام شما با موفقیت ارسال شد. به زودی با شما تماس خواهیم گرفت.'
                ], 200);
            }

            Log::error('Contact form submitted but no admins were notified', [
                'name' => $request->name,
                'email' => $request->email,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در ارسال پیام. لطفاً دوباره تلاش کنید.'
            ], 500);
        } catch (\Exception $e) {
            Log::error('Contact form submission error: ' . $e->getMessage(), [
                'name' => $request->name,
                'email' => $request->email,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در ارسال پیام. لطفاً دوباره تلاش کنید.'
            ], 500);
        }
    }
}
