<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppVersion;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use App\Helpers\FlavorHelper;

class VersionController extends Controller
{
    public function __construct()
    {
        // Constructor for dependency injection if needed
    }

    /**
     * Check for available updates.
     */
    public function checkForUpdates(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'platform' => 'required|string|in:android,ios',
            'current_version' => 'required|string|max:20',
            'current_build_number' => 'nullable|string|max:20',
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
        $currentBuildNumber = $request->input('current_build_number');
        $flavor = FlavorHelper::getFlavor($request);

        $updateCheck = AppVersion::checkUpdateRequired($platform, $currentVersion, $currentBuildNumber);

        if ($updateCheck['update_required']) {
            $latestVersion = $updateCheck['latest_version'];
            $links = [
                'website' => $latestVersion->website_update_url,
                'cafebazaar' => $latestVersion->cafebazaar_update_url,
                'myket' => $latestVersion->myket_update_url,
            ];
            $currentFlavorLink = $links[$flavor] ?? $latestVersion->download_url;

            return response()->json([
                'success' => true,
                'update_required' => true,
                'force_update' => $updateCheck['force_update'],
                'data' => [
                    'latest_version' => $latestVersion->version,
                    'latest_build_number' => $latestVersion->build_number,
                    'download_url' => $latestVersion->download_url,
                    'update_links' => $links,
                    'current_flavor' => $flavor,
                    'current_flavor_link' => $currentFlavorLink,
                    'update_message' => $latestVersion->update_message,
                    'release_notes' => $latestVersion->release_notes,
                    'update_type' => $latestVersion->update_type,
                    'platform' => $latestVersion->platform,
                ]
            ]);
        }

        return response()->json([
            'success' => true,
            'update_required' => false,
            'force_update' => false,
            'data' => [
                'latest_version' => $updateCheck['latest_version']?->version,
                'latest_build_number' => $updateCheck['latest_version']?->build_number,
            ]
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
            'platform' => 'required|string|in:android,ios',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'اطلاعات ورودی نامعتبر است',
                'errors' => $validator->errors(),
            ], 400);
        }

        $platform = $request->input('platform');
        $flavor = FlavorHelper::getFlavor($request);
        $latestVersion = AppVersion::getLatestForPlatform($platform);

        if (!$latestVersion) {
            return response()->json([
                'success' => false,
                'message' => 'هیچ نسخه‌ای یافت نشد',
            ], 404);
        }

        $links = [
            'website' => $latestVersion->website_update_url,
            'cafebazaar' => $latestVersion->cafebazaar_update_url,
            'myket' => $latestVersion->myket_update_url,
        ];
        $currentFlavorLink = $links[$flavor] ?? $latestVersion->download_url;

        return response()->json([
            'success' => true,
            'data' => [
                'version' => $latestVersion->version,
                'build_number' => $latestVersion->build_number,
                'platform' => $latestVersion->platform,
                'download_url' => $latestVersion->download_url,
                'update_links' => $links,
                'current_flavor' => $flavor,
                'current_flavor_link' => $currentFlavorLink,
                'update_message' => $latestVersion->update_message,
                'release_notes' => $latestVersion->release_notes,
                'update_type' => $latestVersion->update_type,
                'release_date' => $latestVersion->release_date?->toISOString(),
                'effective_date' => $latestVersion->effective_date?->toISOString(),
            ]
        ]);
    }

    /**
     * Get version statistics (for admin use).
     */
    public function getStatistics(): JsonResponse
    {
        $statistics = [
            'total_versions' => AppVersion::count(),
            'active_versions' => AppVersion::active()->count(),
            'latest_versions' => AppVersion::latest()->count(),
            'force_updates' => AppVersion::forceUpdate()->count(),
            'optional_updates' => AppVersion::optionalUpdate()->count(),
            'platforms' => [
                'android' => AppVersion::where('platform', 'android')->orWhere('platform', 'both')->count(),
                'ios' => AppVersion::where('platform', 'ios')->orWhere('platform', 'both')->count(),
            ],
        ];

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

    /**
     * Get latest app version code (simple GET endpoint).
     */
    public function check(): JsonResponse
    {
        try {
            // Get the latest active version
            $latestVersion = AppVersion::active()
                ->latest()
                ->orderBy('created_at', 'desc')
                ->first();

            if (!$latestVersion) {
                return response()->json([
                    'success' => false,
                    'message' => 'هیچ نسخه فعالی یافت نشد',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'version_code' => $latestVersion->version,
                    'build_number' => $latestVersion->build_number,
                    'platform' => $latestVersion->platform,
                    'update_type' => $latestVersion->update_type,
                    'is_latest' => $latestVersion->is_latest,
                    'release_date' => $latestVersion->release_date?->toISOString(),
                    'download_url' => $latestVersion->download_url,
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error getting latest version', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت اطلاعات نسخه',
            ], 500);
        }
    }
}
