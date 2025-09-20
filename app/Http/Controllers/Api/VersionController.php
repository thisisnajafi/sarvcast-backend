<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\VersionCheckService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class VersionController extends Controller
{
    protected VersionCheckService $versionCheckService;

    public function __construct(VersionCheckService $versionCheckService)
    {
        $this->versionCheckService = $versionCheckService;
    }

    /**
     * Check for available updates.
     */
    public function checkForUpdates(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'platform' => 'required|string|in:android,ios,web',
            'current_version' => 'required|string|max:20',
            'system_info' => 'sometimes|array',
            'system_info.os_version' => 'sometimes|string',
            'system_info.device_model' => 'sometimes|string',
            'system_info.app_build' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'اطلاعات ورودی نامعتبر است',
                'errors' => $validator->errors(),
            ], 400);
        }

        $platform = $request->input('platform');
        $currentVersion = $request->input('current_version');
        $systemInfo = $request->input('system_info', []);

        $result = $this->versionCheckService->checkForUpdates($platform, $currentVersion);

        // Add compatibility check if system info is provided
        if (!empty($systemInfo) && $result['has_update']) {
            $updateInfo = $result['update_info'] ?? null;
            if ($updateInfo) {
                $isCompatible = $this->versionCheckService->isCompatible(
                    $platform,
                    $updateInfo['version'],
                    $systemInfo
                );
                $result['is_compatible'] = $isCompatible;
                
                if (!$isCompatible) {
                    $result['message'] = 'نسخه جدید با سیستم شما سازگار نیست';
                }
            }
        }

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * Get all available updates for a platform.
     */
    public function getAllUpdates(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'platform' => 'required|string|in:android,ios,web',
            'current_version' => 'required|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'اطلاعات ورودی نامعتبر است',
                'errors' => $validator->errors(),
            ], 400);
        }

        $platform = $request->input('platform');
        $currentVersion = $request->input('current_version');

        $updates = $this->versionCheckService->getAllUpdates($platform, $currentVersion);

        return response()->json([
            'success' => true,
            'data' => [
                'updates' => $updates,
                'total_count' => count($updates),
            ],
        ]);
    }

    /**
     * Get the latest version information.
     */
    public function getLatestVersion(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'platform' => 'required|string|in:android,ios,web',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'اطلاعات ورودی نامعتبر است',
                'errors' => $validator->errors(),
            ], 400);
        }

        $platform = $request->input('platform');
        $latestVersion = $this->versionCheckService->getLatestVersion($platform);

        if (!$latestVersion) {
            return response()->json([
                'success' => false,
                'message' => 'هیچ نسخه‌ای یافت نشد',
            ], 404);
        }

        $versionInfo = $this->versionCheckService->formatUpdateInfo($latestVersion);

        return response()->json([
            'success' => true,
            'data' => $versionInfo,
        ]);
    }

    /**
     * Get version statistics (for admin use).
     */
    public function getStatistics(): JsonResponse
    {
        $statistics = $this->versionCheckService->getVersionStatistics();

        return response()->json([
            'success' => true,
            'data' => $statistics,
        ]);
    }

    /**
     * Check compatibility of a specific version.
     */
    public function checkCompatibility(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'platform' => 'required|string|in:android,ios,web',
            'version' => 'required|string|max:20',
            'system_info' => 'required|array',
            'system_info.os_version' => 'sometimes|string',
            'system_info.device_model' => 'sometimes|string',
            'system_info.app_build' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'اطلاعات ورودی نامعتبر است',
                'errors' => $validator->errors(),
            ], 400);
        }

        $platform = $request->input('platform');
        $version = $request->input('version');
        $systemInfo = $request->input('system_info');

        $isCompatible = $this->versionCheckService->isCompatible($platform, $version, $systemInfo);

        return response()->json([
            'success' => true,
            'data' => [
                'is_compatible' => $isCompatible,
                'platform' => $platform,
                'version' => $version,
                'system_info' => $systemInfo,
            ],
        ]);
    }

    /**
     * Get app configuration including current version.
     */
    public function getAppConfig(): JsonResponse
    {
        $config = [
            'app_name' => config('app.name', 'SarvCast'),
            'app_version' => config('app.version', '1.0.0'),
            'app_build' => config('app.build', '1'),
            'min_supported_version' => config('app.min_supported_version', '1.0.0'),
            'update_check_url' => route('api.version.check'),
            'support_email' => config('mail.support_email', 'support@sarvcast.com'),
            'support_phone' => config('app.support_phone', '021-12345678'),
            'website_url' => config('app.website_url', 'https://sarvcast.com'),
            'privacy_policy_url' => config('app.privacy_policy_url', 'https://sarvcast.com/privacy'),
            'terms_of_service_url' => config('app.terms_of_service_url', 'https://sarvcast.com/terms'),
        ];

        return response()->json([
            'success' => true,
            'data' => $config,
        ]);
    }

    /**
     * Report app version usage (for analytics).
     */
    public function reportUsage(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'platform' => 'required|string|in:android,ios,web',
            'version' => 'required|string|max:20',
            'build_number' => 'sometimes|string|max:50',
            'system_info' => 'sometimes|array',
            'user_id' => 'sometimes|integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'اطلاعات ورودی نامعتبر است',
                'errors' => $validator->errors(),
            ], 400);
        }

        // Log version usage for analytics
        \Log::info('App version usage reported', [
            'platform' => $request->input('platform'),
            'version' => $request->input('version'),
            'build_number' => $request->input('build_number'),
            'system_info' => $request->input('system_info'),
            'user_id' => $request->input('user_id'),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toISOString(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'اطلاعات نسخه ثبت شد',
        ]);
    }
}