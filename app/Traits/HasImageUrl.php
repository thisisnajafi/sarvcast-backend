<?php

namespace App\Traits;

use Illuminate\Support\Facades\Storage;

trait HasImageUrl
{
    /**
     * Get the full image URL from a relative path
     */
    public function getImageUrlFromPath(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        // If it's already a full URL, return as is
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        // Generate full URL using the app's base URL
        $baseUrl = rtrim(config('app.url'), '/');
        return $baseUrl . '/storage/' . ltrim($path, '/');
    }

    /**
     * Store only the relative path in database
     */
    public function storeImagePath(string $fullPath): string
    {
        // Extract relative path from full path
        $relativePath = str_replace(storage_path('app/public/'), '', $fullPath);
        return ltrim($relativePath, '/');
    }
}
