<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeoRedirect extends Model
{
    protected $fillable = [
        'source',
        'destination',
        'status_code',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status_code' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public static function normalizePath(string $path): string
    {
        $trimmed = trim($path);
        if ($trimmed === '') {
            return '/';
        }

        if (str_starts_with($trimmed, 'http://') || str_starts_with($trimmed, 'https://')) {
            $parts = parse_url($trimmed);
            $pathOnly = ($parts['path'] ?? '/').(isset($parts['query']) ? '?'.$parts['query'] : '');
            $trimmed = $pathOnly;
        }

        return '/'.ltrim($trimmed, '/');
    }
}
