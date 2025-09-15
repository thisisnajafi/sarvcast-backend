<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class SmsController extends Controller
{
    protected $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    /**
     * Send SMS
     */
    public function send(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string|max:15',
            'message' => 'required|string|max:1000',
            'provider' => 'nullable|string|in:kavenegar,melipayamak,smsir'
        ], [
            'phone_number.required' => 'شماره تلفن الزامی است',
            'phone_number.max' => 'شماره تلفن نمی‌تواند بیشتر از 15 کاراکتر باشد',
            'message.required' => 'متن پیامک الزامی است',
            'message.max' => 'متن پیامک نمی‌تواند بیشتر از 1000 کاراکتر باشد',
            'provider.in' => 'ارائه‌دهنده نامعتبر است'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در اعتبارسنجی داده‌ها',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $phoneNumber = $request->input('phone_number');
            $message = $request->input('message');
            $provider = $request->input('provider');

            $result = $this->smsService->sendSms($phoneNumber, $message, $provider);

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Failed to send SMS', [
                'phone_number' => $request->input('phone_number'),
                'message' => $request->input('message'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در ارسال پیامک'
            ], 500);
        }
    }

    /**
     * Send verification code
     */
    public function sendVerificationCode(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string|max:15',
            'code' => 'required|string|size:5',
            'provider' => 'nullable|string|in:kavenegar,melipayamak,smsir'
        ], [
            'phone_number.required' => 'شماره تلفن الزامی است',
            'phone_number.max' => 'شماره تلفن نمی‌تواند بیشتر از 15 کاراکتر باشد',
            'code.required' => 'کد تایید الزامی است',
            'code.size' => 'کد تایید باید 5 رقم باشد',
            'provider.in' => 'ارائه‌دهنده نامعتبر است'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در اعتبارسنجی داده‌ها',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $phoneNumber = $request->input('phone_number');
            $code = $request->input('code');
            $provider = $request->input('provider');

            $result = $this->smsService->sendVerificationCode($phoneNumber, $code, $provider);

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Failed to send verification code', [
                'phone_number' => $request->input('phone_number'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در ارسال کد تایید'
            ], 500);
        }
    }

    /**
     * Send template SMS
     */
    public function sendTemplate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string|max:15',
            'template_key' => 'required|string',
            'variables' => 'nullable|array',
            'provider' => 'nullable|string|in:kavenegar,melipayamak,smsir'
        ], [
            'phone_number.required' => 'شماره تلفن الزامی است',
            'phone_number.max' => 'شماره تلفن نمی‌تواند بیشتر از 15 کاراکتر باشد',
            'template_key.required' => 'کلید قالب الزامی است',
            'variables.array' => 'متغیرها باید آرایه باشند',
            'provider.in' => 'ارائه‌دهنده نامعتبر است'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در اعتبارسنجی داده‌ها',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $phoneNumber = $request->input('phone_number');
            $templateKey = $request->input('template_key');
            $variables = $request->input('variables', []);
            $provider = $request->input('provider');

            $result = $this->smsService->sendTemplateSms($phoneNumber, $templateKey, $variables, $provider);

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Failed to send template SMS', [
                'phone_number' => $request->input('phone_number'),
                'template_key' => $request->input('template_key'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در ارسال پیامک قالب‌دار'
            ], 500);
        }
    }

    /**
     * Send bulk SMS
     */
    public function sendBulk(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone_numbers' => 'required|array|min:1|max:100',
            'phone_numbers.*' => 'string|max:15',
            'message' => 'required|string|max:1000',
            'provider' => 'nullable|string|in:kavenegar,melipayamak,smsir'
        ], [
            'phone_numbers.required' => 'شماره‌های تلفن الزامی است',
            'phone_numbers.array' => 'شماره‌های تلفن باید آرایه باشند',
            'phone_numbers.min' => 'حداقل یک شماره تلفن الزامی است',
            'phone_numbers.max' => 'حداکثر 100 شماره تلفن مجاز است',
            'phone_numbers.*.string' => 'هر شماره تلفن باید رشته باشد',
            'phone_numbers.*.max' => 'هر شماره تلفن نمی‌تواند بیشتر از 15 کاراکتر باشد',
            'message.required' => 'متن پیامک الزامی است',
            'message.max' => 'متن پیامک نمی‌تواند بیشتر از 1000 کاراکتر باشد',
            'provider.in' => 'ارائه‌دهنده نامعتبر است'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در اعتبارسنجی داده‌ها',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $phoneNumbers = $request->input('phone_numbers');
            $message = $request->input('message');
            $provider = $request->input('provider');

            $result = $this->smsService->sendBulkSms($phoneNumbers, $message, $provider);

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Failed to send bulk SMS', [
                'phone_numbers' => $request->input('phone_numbers'),
                'message' => $request->input('message'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در ارسال پیامک‌های گروهی'
            ], 500);
        }
    }

    /**
     * Send welcome message
     */
    public function sendWelcome(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string|max:15',
            'provider' => 'nullable|string|in:kavenegar,melipayamak,smsir'
        ], [
            'phone_number.required' => 'شماره تلفن الزامی است',
            'phone_number.max' => 'شماره تلفن نمی‌تواند بیشتر از 15 کاراکتر باشد',
            'provider.in' => 'ارائه‌دهنده نامعتبر است'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در اعتبارسنجی داده‌ها',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $phoneNumber = $request->input('phone_number');
            $provider = $request->input('provider');

            $result = $this->smsService->sendWelcomeMessage($phoneNumber, $provider);

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Failed to send welcome message', [
                'phone_number' => $request->input('phone_number'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در ارسال پیام خوش‌آمدگویی'
            ], 500);
        }
    }

    /**
     * Send subscription notification
     */
    public function sendSubscriptionNotification(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string|max:15',
            'type' => 'required|string|in:activated,expiring,expired',
            'data' => 'nullable|array',
            'provider' => 'nullable|string|in:kavenegar,melipayamak,smsir'
        ], [
            'phone_number.required' => 'شماره تلفن الزامی است',
            'phone_number.max' => 'شماره تلفن نمی‌تواند بیشتر از 15 کاراکتر باشد',
            'type.required' => 'نوع اعلان الزامی است',
            'type.in' => 'نوع اعلان نامعتبر است',
            'data.array' => 'داده‌ها باید آرایه باشند',
            'provider.in' => 'ارائه‌دهنده نامعتبر است'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در اعتبارسنجی داده‌ها',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $phoneNumber = $request->input('phone_number');
            $type = $request->input('type');
            $data = $request->input('data', []);
            $provider = $request->input('provider');

            $result = $this->smsService->sendSubscriptionNotification($phoneNumber, $type, $data, $provider);

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Failed to send subscription notification', [
                'phone_number' => $request->input('phone_number'),
                'type' => $request->input('type'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در ارسال اعلان اشتراک'
            ], 500);
        }
    }

    /**
     * Send payment notification
     */
    public function sendPaymentNotification(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string|max:15',
            'type' => 'required|string|in:success,failed',
            'data' => 'nullable|array',
            'provider' => 'nullable|string|in:kavenegar,melipayamak,smsir'
        ], [
            'phone_number.required' => 'شماره تلفن الزامی است',
            'phone_number.max' => 'شماره تلفن نمی‌تواند بیشتر از 15 کاراکتر باشد',
            'type.required' => 'نوع اعلان الزامی است',
            'type.in' => 'نوع اعلان نامعتبر است',
            'data.array' => 'داده‌ها باید آرایه باشند',
            'provider.in' => 'ارائه‌دهنده نامعتبر است'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در اعتبارسنجی داده‌ها',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $phoneNumber = $request->input('phone_number');
            $type = $request->input('type');
            $data = $request->input('data', []);
            $provider = $request->input('provider');

            $result = $this->smsService->sendPaymentNotification($phoneNumber, $type, $data, $provider);

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Failed to send payment notification', [
                'phone_number' => $request->input('phone_number'),
                'type' => $request->input('type'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در ارسال اعلان پرداخت'
            ], 500);
        }
    }

    /**
     * Send content notification
     */
    public function sendContentNotification(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string|max:15',
            'type' => 'required|string|in:episode,story',
            'data' => 'required|array',
            'provider' => 'nullable|string|in:kavenegar,melipayamak,smsir'
        ], [
            'phone_number.required' => 'شماره تلفن الزامی است',
            'phone_number.max' => 'شماره تلفن نمی‌تواند بیشتر از 15 کاراکتر باشد',
            'type.required' => 'نوع اعلان الزامی است',
            'type.in' => 'نوع اعلان نامعتبر است',
            'data.required' => 'داده‌ها الزامی است',
            'data.array' => 'داده‌ها باید آرایه باشند',
            'provider.in' => 'ارائه‌دهنده نامعتبر است'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در اعتبارسنجی داده‌ها',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $phoneNumber = $request->input('phone_number');
            $type = $request->input('type');
            $data = $request->input('data');
            $provider = $request->input('provider');

            $result = $this->smsService->sendContentNotification($phoneNumber, $type, $data, $provider);

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Failed to send content notification', [
                'phone_number' => $request->input('phone_number'),
                'type' => $request->input('type'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در ارسال اعلان محتوا'
            ], 500);
        }
    }

    /**
     * Get SMS statistics
     */
    public function getStatistics(): JsonResponse
    {
        try {
            $result = $this->smsService->getSmsStatistics();
            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Failed to get SMS statistics', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت آمار پیامک‌ها'
            ], 500);
        }
    }

    /**
     * Get available templates
     */
    public function getTemplates(): JsonResponse
    {
        try {
            $result = $this->smsService->getTemplates();
            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Failed to get SMS templates', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت قالب‌های پیامک'
            ], 500);
        }
    }

    /**
     * Get provider configurations
     */
    public function getProviders(): JsonResponse
    {
        try {
            $result = $this->smsService->getProviders();
            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Failed to get SMS providers', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت ارائه‌دهندگان پیامک'
            ], 500);
        }
    }
}
