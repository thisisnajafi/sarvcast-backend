<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class AppVersion extends Model
{
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'version',
        'build_number',
        'platform',
        'update_type',
        'title',
        'description',
        'changelog',
        'update_notes',
        'download_url',
        'minimum_os_version',
        'compatibility',
        'is_active',
        'is_latest',
        'release_date',
        'force_update_date',
        'priority',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'compatibility' => 'array',
        'metadata' => 'array',
        'is_active' => 'boolean',
        'is_latest' => 'boolean',
        'release_date' => 'datetime',
        'force_update_date' => 'datetime',
        'priority' => 'integer',
    ];

    /**
     * Scope a query to only include active versions.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include latest versions.
     */
    public function scopeLatest(Builder $query): Builder
    {
        return $query->where('is_latest', true);
    }

    /**
     * Scope a query to only include versions for a specific platform.
     */
    public function scopeForPlatform(Builder $query, string $platform): Builder
    {
        return $query->where(function ($q) use ($platform) {
            $q->where('platform', $platform)
              ->orWhere('platform', 'all');
        });
    }

    /**
     * Scope a query to only include forced updates.
     */
    public function scopeForced(Builder $query): Builder
    {
        return $query->where('update_type', 'forced');
    }

    /**
     * Scope a query to only include optional updates.
     */
    public function scopeOptional(Builder $query): Builder
    {
        return $query->where('update_type', 'optional');
    }

    /**
     * Scope a query to only include maintenance updates.
     */
    public function scopeMaintenance(Builder $query): Builder
    {
        return $query->where('update_type', 'maintenance');
    }

    /**
     * Get the version comparison result with another version.
     */
    public function compareVersion(string $version): int
    {
        return version_compare($this->version, $version);
    }

    /**
     * Check if this version is newer than the given version.
     */
    public function isNewerThan(string $version): bool
    {
        return $this->compareVersion($version) > 0;
    }

    /**
     * Check if this version is older than the given version.
     */
    public function isOlderThan(string $version): bool
    {
        return $this->compareVersion($version) < 0;
    }

    /**
     * Check if this version is the same as the given version.
     */
    public function isSameAs(string $version): bool
    {
        return $this->compareVersion($version) === 0;
    }

    /**
     * Check if this update should be forced for the given version.
     */
    public function shouldForceUpdate(string $currentVersion): bool
    {
        if ($this->update_type !== 'forced') {
            return false;
        }

        // If force_update_date is set, check if it's time to force the update
        if ($this->force_update_date && $this->force_update_date->isFuture()) {
            return false;
        }

        // Force update if current version is older than this version
        return $this->isNewerThan($currentVersion);
    }

    /**
     * Get the update type label.
     */
    public function getUpdateTypeLabelAttribute(): string
    {
        return match ($this->update_type) {
            'forced' => 'اجباری',
            'optional' => 'اختیاری',
            'maintenance' => 'تعمیرات',
            default => 'نامشخص',
        };
    }

    /**
     * Get the platform label.
     */
    public function getPlatformLabelAttribute(): string
    {
        return match ($this->platform) {
            'android' => 'اندروید',
            'ios' => 'iOS',
            'web' => 'وب',
            'all' => 'همه پلتفرم‌ها',
            default => 'نامشخص',
        };
    }

    /**
     * Get formatted release date.
     */
    public function getFormattedReleaseDateAttribute(): string
    {
        return $this->release_date ? $this->release_date->format('Y/m/d H:i') : 'نامشخص';
    }

    /**
     * Get formatted force update date.
     */
    public function getFormattedForceUpdateDateAttribute(): string
    {
        return $this->force_update_date ? $this->force_update_date->format('Y/m/d H:i') : 'نامشخص';
    }

    /**
     * Get the download URL with fallback.
     */
    public function getDownloadUrlAttribute($value): string
    {
        if ($value) {
            return $value;
        }

        // Fallback URLs based on platform
        return match ($this->platform) {
            'android' => 'https://play.google.com/store/apps/details?id=com.sarvcast.app',
            'ios' => 'https://apps.apple.com/app/sarvcast/id123456789',
            'web' => url('/'),
            default => url('/'),
        };
    }

    /**
     * Get the status badge class.
     */
    public function getStatusBadgeClassAttribute(): string
    {
        if (!$this->is_active) {
            return 'bg-gray-100 text-gray-800';
        }

        if ($this->is_latest) {
            return 'bg-green-100 text-green-800';
        }

        return match ($this->update_type) {
            'forced' => 'bg-red-100 text-red-800',
            'optional' => 'bg-blue-100 text-blue-800',
            'maintenance' => 'bg-yellow-100 text-yellow-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Get the status label.
     */
    public function getStatusLabelAttribute(): string
    {
        if (!$this->is_active) {
            return 'غیرفعال';
        }

        if ($this->is_latest) {
            return 'آخرین نسخه';
        }

        return $this->update_type_label;
    }
}