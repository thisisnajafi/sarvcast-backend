<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AppVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'platform',
        'version',
        'build_number',
        'update_type',
        'title',
        'description',
        'changelog',
        'update_notes',
        'download_url',
        'website_update_url',
        'cafebazaar_update_url',
        'myket_update_url',
        'minimum_os_version',
        'compatibility',
        'is_active',
        'is_latest',
        'release_date',
        'force_update_date',
        'priority',
        'metadata',
        'release_notes',
        'update_message',
        'min_supported_version_code',
        'target_version_code',
        'compatibility_requirements',
        'effective_date',
        'expiry_date',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_latest' => 'boolean',
        'compatibility' => 'array',
        'compatibility_requirements' => 'array',
        'metadata' => 'array',
        'release_date' => 'datetime',
        'force_update_date' => 'datetime',
        'effective_date' => 'datetime',
        'expiry_date' => 'datetime',
    ];

    /**
     * Get the admin who created this version
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope for active versions
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for latest versions
     */
    public function scopeLatest($query)
    {
        return $query->where('is_latest', true);
    }

    /**
     * Scope for platform
     */
    public function scopeForPlatform($query, $platform)
    {
        return $query->where(function($q) use ($platform) {
            $q->where('platform', $platform)
              ->orWhere('platform', 'both');
        });
    }

    /**
     * Scope for force updates
     */
    public function scopeForceUpdate($query)
    {
        return $query->where('update_type', 'force');
    }

    /**
     * Scope for forced updates (alias for forceUpdate)
     */
    public function scopeForced($query)
    {
        return $query->where('update_type', 'force');
    }

    /**
     * Scope for optional updates
     */
    public function scopeOptionalUpdate($query)
    {
        return $query->where('update_type', 'optional');
    }

    /**
     * Scope for optional updates (alias for optionalUpdate)
     */
    public function scopeOptional($query)
    {
        return $query->where('update_type', 'optional');
    }

    /**
     * Scope for maintenance updates
     */
    public function scopeMaintenance($query)
    {
        return $query->where('update_type', 'maintenance');
    }

    /**
     * Scope for currently effective versions
     */
    public function scopeCurrentlyEffective($query)
    {
        $now = now();
        return $query->where(function($q) use ($now) {
            $q->whereNull('effective_date')
              ->orWhere('effective_date', '<=', $now);
        })->where(function($q) use ($now) {
            $q->whereNull('expiry_date')
              ->orWhere('expiry_date', '>=', $now);
        });
    }

    /**
     * Get the latest version for a platform
     */
    public static function getLatestForPlatform($platform)
    {
        return static::active()
            ->latest()
            ->forPlatform($platform)
            ->currentlyEffective()
            ->orderBy('created_at', 'desc')
            ->first();
    }

    /**
     * Check if an update is required for a given version
     */
    public static function checkUpdateRequired($platform, $currentVersion, $currentBuildNumber = null)
    {
        $latestVersion = static::getLatestForPlatform($platform);

        if (!$latestVersion) {
            return [
                'update_required' => false,
                'force_update' => false,
                'latest_version' => null,
            ];
        }

        // Compare versions
        $versionComparison = version_compare($currentVersion, $latestVersion->version);

        // If current version is older
        if ($versionComparison < 0) {
            return [
                'update_required' => true,
                'force_update' => $latestVersion->update_type === 'force',
                'latest_version' => $latestVersion,
            ];
        }

        // If versions are equal, check build number if provided
        if ($versionComparison === 0 && $currentBuildNumber && $latestVersion->build_number) {
            if ((int)$currentBuildNumber < (int)$latestVersion->build_number) {
                return [
                    'update_required' => true,
                    'force_update' => $latestVersion->update_type === 'force',
                    'latest_version' => $latestVersion,
                ];
            }
        }

        return [
            'update_required' => false,
            'force_update' => false,
            'latest_version' => $latestVersion,
        ];
    }

    /**
     * Get version comparison result
     */
    public function compareWith($version, $buildNumber = null)
    {
        $versionComparison = version_compare($this->version, $version);

        if ($versionComparison !== 0) {
            return $versionComparison;
        }

        // If versions are equal, compare build numbers
        if ($buildNumber && $this->build_number) {
            return (int)$this->build_number <=> (int)$buildNumber;
        }

        return 0;
    }

    /**
     * Check if this version is compatible with current app version
     */
    public function isCompatibleWith($currentVersion, $currentBuildNumber = null)
    {
        if ($this->min_supported_version_code && $currentBuildNumber) {
            return (int)$currentBuildNumber >= $this->min_supported_version_code;
        }

        return true;
    }

    /**
     * Get formatted version string
     */
    public function getFormattedVersionAttribute()
    {
        $version = $this->version;
        if ($this->build_number) {
            $version .= " ({$this->build_number})";
        }
        return $version;
    }

    /**
     * Get platform display name
     */
    public function getPlatformDisplayNameAttribute()
    {
        $platforms = [
            'android' => 'Android',
            'ios' => 'iOS',
            'both' => 'Both Platforms',
        ];

        return $platforms[$this->platform] ?? $this->platform;
    }

    /**
     * Get update type display name
     */
    public function getUpdateTypeDisplayNameAttribute()
    {
        return $this->update_type === 'force' ? 'Force Update' : 'Optional Update';
    }

    /**
     * Check if this version is currently effective
     */
    public function isCurrentlyEffective()
    {
        $now = now();

        $effective = !$this->effective_date || $this->effective_date <= $now;
        $notExpired = !$this->expiry_date || $this->expiry_date >= $now;

        return $effective && $notExpired;
    }

    /**
     * Get status display name
     */
    public function getStatusDisplayNameAttribute()
    {
        if (!$this->is_active) {
            return 'Inactive';
        }

        if (!$this->isCurrentlyEffective()) {
            return 'Not Effective';
        }

        if ($this->is_latest) {
            return 'Latest';
        }

        return 'Active';
    }
}
