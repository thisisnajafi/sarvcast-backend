<?php

namespace App\Services;

use App\Models\AppVersion;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class VersionCheckService
{
    /**
     * Check for available updates for a given platform and current version.
     */
    public function checkForUpdates(string $platform, string $currentVersion): array
    {
        try {
            // Get the latest version for the platform
            $latestVersion = $this->getLatestVersion($platform);
            
            if (!$latestVersion) {
                return [
                    'has_update' => false,
                    'is_forced' => false,
                    'message' => 'هیچ به‌روزرسانی در دسترس نیست',
                ];
            }

            // Compare versions
            $versionComparison = version_compare($latestVersion->version, $currentVersion);
            
            if ($versionComparison <= 0) {
                return [
                    'has_update' => false,
                    'is_forced' => false,
                    'message' => 'نسخه شما به‌روز است',
                    'current_version' => $currentVersion,
                    'latest_version' => $latestVersion->version,
                ];
            }

            // Check if there are any forced updates
            $forcedUpdate = $this->getForcedUpdate($platform, $currentVersion);
            
            if ($forcedUpdate) {
                return [
                    'has_update' => true,
                    'is_forced' => true,
                    'message' => 'به‌روزرسانی اجباری در دسترس است',
                    'current_version' => $currentVersion,
                    'latest_version' => $latestVersion->version,
                    'forced_version' => $forcedUpdate->version,
                    'update_info' => $this->formatUpdateInfo($forcedUpdate),
                ];
            }

            // Check for optional updates
            $optionalUpdate = $this->getOptionalUpdate($platform, $currentVersion);
            
            if ($optionalUpdate) {
                return [
                    'has_update' => true,
                    'is_forced' => false,
                    'message' => 'به‌روزرسانی اختیاری در دسترس است',
                    'current_version' => $currentVersion,
                    'latest_version' => $latestVersion->version,
                    'update_info' => $this->formatUpdateInfo($optionalUpdate),
                ];
            }

            return [
                'has_update' => false,
                'is_forced' => false,
                'message' => 'نسخه شما به‌روز است',
                'current_version' => $currentVersion,
                'latest_version' => $latestVersion->version,
            ];

        } catch (\Exception $e) {
            Log::error('Version check failed', [
                'platform' => $platform,
                'current_version' => $currentVersion,
                'error' => $e->getMessage(),
            ]);

            return [
                'has_update' => false,
                'is_forced' => false,
                'message' => 'خطا در بررسی نسخه',
                'error' => true,
            ];
        }
    }

    /**
     * Get the latest version for a specific platform.
     */
    public function getLatestVersion(string $platform): ?AppVersion
    {
        return Cache::remember("latest_version_{$platform}", 300, function () use ($platform) {
            return AppVersion::active()
                ->latest()
                ->forPlatform($platform)
                ->orderBy('priority', 'desc')
                ->orderBy('release_date', 'desc')
                ->first();
        });
    }

    /**
     * Get forced update for a specific platform and current version.
     */
    public function getForcedUpdate(string $platform, string $currentVersion): ?AppVersion
    {
        return Cache::remember("forced_update_{$platform}_{$currentVersion}", 300, function () use ($platform, $currentVersion) {
            return AppVersion::active()
                ->forced()
                ->forPlatform($platform)
                ->where(function ($query) use ($currentVersion) {
                    $query->whereRaw('version > ?', [$currentVersion])
                          ->orWhere(function ($q) use ($currentVersion) {
                              $q->where('force_update_date', '<=', now())
                                ->whereRaw('version > ?', [$currentVersion]);
                          });
                })
                ->orderBy('priority', 'desc')
                ->orderBy('release_date', 'desc')
                ->first();
        });
    }

    /**
     * Get optional update for a specific platform and current version.
     */
    public function getOptionalUpdate(string $platform, string $currentVersion): ?AppVersion
    {
        return Cache::remember("optional_update_{$platform}_{$currentVersion}", 300, function () use ($platform, $currentVersion) {
            return AppVersion::active()
                ->optional()
                ->forPlatform($platform)
                ->whereRaw('version > ?', [$currentVersion])
                ->orderBy('priority', 'desc')
                ->orderBy('release_date', 'desc')
                ->first();
        });
    }

    /**
     * Get all available updates for a specific platform and current version.
     */
    public function getAllUpdates(string $platform, string $currentVersion): array
    {
        $updates = AppVersion::active()
            ->forPlatform($platform)
            ->whereRaw('version > ?', [$currentVersion])
            ->orderBy('priority', 'desc')
            ->orderBy('release_date', 'desc')
            ->get();

        return $updates->map(function ($update) {
            return $this->formatUpdateInfo($update);
        })->toArray();
    }

    /**
     * Format update information for API response.
     */
    public function formatUpdateInfo(AppVersion $version): array
    {
        return [
            'version' => $version->version,
            'build_number' => $version->build_number,
            'platform' => $version->platform,
            'platform_label' => $version->platform_label,
            'update_type' => $version->update_type,
            'update_type_label' => $version->update_type_label,
            'title' => $version->title,
            'description' => $version->description,
            'changelog' => $version->changelog,
            'update_notes' => $version->update_notes,
            'download_url' => $version->download_url,
            'minimum_os_version' => $version->minimum_os_version,
            'compatibility' => $version->compatibility,
            'release_date' => $version->release_date?->toISOString(),
            'force_update_date' => $version->force_update_date?->toISOString(),
            'priority' => $version->priority,
            'metadata' => $version->metadata,
            'is_forced' => $version->update_type === 'forced',
            'is_optional' => $version->update_type === 'optional',
            'is_maintenance' => $version->update_type === 'maintenance',
        ];
    }

    /**
     * Check if a version is compatible with the current system.
     */
    public function isCompatible(string $platform, string $version, array $systemInfo = []): bool
    {
        $appVersion = AppVersion::where('version', $version)
            ->forPlatform($platform)
            ->first();

        if (!$appVersion) {
            return false;
        }

        // Check minimum OS version if provided
        if (isset($systemInfo['os_version']) && $appVersion->minimum_os_version) {
            if (version_compare($systemInfo['os_version'], $appVersion->minimum_os_version, '<')) {
                return false;
            }
        }

        // Check compatibility requirements
        if ($appVersion->compatibility) {
            foreach ($appVersion->compatibility as $requirement => $value) {
                if (isset($systemInfo[$requirement]) && $systemInfo[$requirement] !== $value) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Get version statistics.
     */
    public function getVersionStatistics(): array
    {
        return Cache::remember('version_statistics', 600, function () {
            $totalVersions = AppVersion::count();
            $activeVersions = AppVersion::active()->count();
            $latestVersions = AppVersion::latest()->count();
            $forcedUpdates = AppVersion::forced()->active()->count();
            $optionalUpdates = AppVersion::optional()->active()->count();
            $maintenanceUpdates = AppVersion::maintenance()->active()->count();

            $platformStats = AppVersion::active()
                ->selectRaw('platform, COUNT(*) as count')
                ->groupBy('platform')
                ->get()
                ->mapWithKeys(function ($item) {
                    return [$item->platform => $item->count];
                });

            return [
                'total_versions' => $totalVersions,
                'active_versions' => $activeVersions,
                'latest_versions' => $latestVersions,
                'forced_updates' => $forcedUpdates,
                'optional_updates' => $optionalUpdates,
                'maintenance_updates' => $maintenanceUpdates,
                'platform_distribution' => $platformStats,
            ];
        });
    }

    /**
     * Clear version cache.
     */
    public function clearCache(): void
    {
        Cache::forget('version_statistics');
        
        $platforms = ['android', 'ios', 'web', 'all'];
        foreach ($platforms as $platform) {
            Cache::forget("latest_version_{$platform}");
        }
    }

    /**
     * Set a version as the latest for its platform.
     */
    public function setAsLatest(AppVersion $version): void
    {
        // Remove latest flag from other versions of the same platform
        AppVersion::where('platform', $version->platform)
            ->orWhere('platform', 'all')
            ->update(['is_latest' => false]);

        // Set this version as latest
        $version->update(['is_latest' => true]);

        // Clear cache
        $this->clearCache();
    }

    /**
     * Activate a version.
     */
    public function activateVersion(AppVersion $version): void
    {
        $version->update(['is_active' => true]);
        $this->clearCache();
    }

    /**
     * Deactivate a version.
     */
    public function deactivateVersion(AppVersion $version): void
    {
        $version->update(['is_active' => false]);
        $this->clearCache();
    }
}
