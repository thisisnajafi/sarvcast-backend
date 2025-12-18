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

        // Generate full URL using the app's base URL for public images
        $baseUrl = rtrim(config('app.url'), '/');
        
        // If path already starts with 'images/', don't add it again
        if (str_starts_with($path, 'images/')) {
            return $baseUrl . '/' . ltrim($path, '/');
        }
        
        return $baseUrl . '/images/' . ltrim($path, '/');
    }

    /**
     * Store only the relative path in database
     */
    public function storeImagePath(string $fullPath): string
    {
        // Extract relative path from public path
        $relativePath = str_replace(public_path('images/'), '', $fullPath);
        return ltrim($relativePath, '/');
    }
}
