<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TelegramNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ContactController extends Controller
{
    protected $telegramService;

    public function __construct(TelegramNotificationService $telegramService)
    {
        $this->telegramService = $telegramService;
    }

    /**
     * Handle contact form submission
     */
    public function submit(Request $request)
    {
        // Validate the request
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
            // Format the message for Telegram
            $telegramMessage = $this->formatContactMessage(
                $request->name,
                $request->email,
                $request->phone,
                $request->message
            );

            // Send to Telegram
            $sent = $this->telegramService->sendMessage($telegramMessage);

            if ($sent) {
                Log::info('Contact form submitted successfully', [
                    'name' => $request->name,
                    'email' => $request->email,
                    'phone' => $request->phone,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'پیام شما با موفقیت ارسال شد. به زودی با شما تماس خواهیم گرفت.'
                ], 200);
            } else {
                Log::error('Failed to send contact form to Telegram', [
                    'name' => $request->name,
                    'email' => $request->email,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'خطا در ارسال پیام. لطفاً دوباره تلاش کنید.'
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('Contact form submission error: ' . $e->getMessage(), [
                'name' => $request->name,
                'email' => $request->email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در ارسال پیام. لطفاً دوباره تلاش کنید.'
            ], 500);
        }
    }

    /**
     * Format contact form data for Telegram message
     */
    private function formatContactMessage(string $name, string $email, ?string $phone, string $message): string
    {
        $telegramMessage = "📧 <b>پیام جدید از فرم تماس</b>\n\n";
        $telegramMessage .= "👤 <b>نام:</b> " . htmlspecialchars($name) . "\n";
        $telegramMessage .= "📧 <b>ایمیل:</b> " . htmlspecialchars($email) . "\n";
        
        if ($phone) {
            $telegramMessage .= "📱 <b>شماره تماس:</b> " . htmlspecialchars($phone) . "\n";
        } else {
            $telegramMessage .= "📱 <b>شماره تماس:</b> ارائه نشده\n";
        }
        
        $telegramMessage .= "\n💬 <b>پیام:</b>\n";
        $telegramMessage .= htmlspecialchars($message) . "\n\n";
        
        $telegramMessage .= "🕐 <b>زمان:</b> " . $this->formatJalaliDate(now()) . "\n";
        
        return $telegramMessage;
    }

    /**
     * Format Jalali date
     */
    private function formatJalaliDate($date): string
    {
        return \App\Helpers\JalaliHelper::formatForDisplay($date, 'Y/m/d H:i');
    }
}

