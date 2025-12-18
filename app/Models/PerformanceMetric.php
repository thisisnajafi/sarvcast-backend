<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class PerformanceMetric extends Model
{
    use HasFactory;

    protected $fillable = [
        'feature',
        'data',
        'response_time',
        'memory_usage',
        'database_queries',
    ];

    protected $casts = [
        'data' => 'array',
        'response_time' => 'decimal:2',
        'memory_usage' => 'integer',
        'database_queries' => 'integer',
    ];

    /**
     * Scope for specific feature
     */
    public function scopeForFeature($query, string $feature)
    {
        return $query->where('feature', $feature);
    }

    /**
     * Scope for date range
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope for recent metrics
     */
    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('created_at', '>=', Carbon::now()->subHours($hours));
    }

    /**
     * Scope for slow responses
     */
    public function scopeSlowResponses($query, float $threshold = 1000)
    {
        return $query->where('response_time', '>', $threshold);
    }

    /**
     * Scope for high memory usage
     */
    public function scopeHighMemoryUsage($query, int $threshold = 100 * 1024 * 1024) // 100MB
    {
        return $query->where('memory_usage', '>', $threshold);
    }

    /**
     * Scope for many database queries
     */
    public function scopeManyQueries($query, int $threshold = 50)
    {
        return $query->where('database_queries', '>', $threshold);
    }

    /**
     * Get formatted memory usage
     */
    public function getFormattedMemoryUsageAttribute(): string
    {
        $bytes = $this->memory_usage;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get formatted response time
     */
    public function getFormattedResponseTimeAttribute(): string
    {
        if ($this->response_time < 1000) {
            return round($this->response_time, 2) . ' ms';
        }
        
        return round($this->response_time / 1000, 2) . ' s';
    }

    /**
     * Check if response is slow
     */
    public function isSlowResponse(float $threshold = 1000): bool
    {
        return $this->response_time > $threshold;
    }

    /**
     * Check if memory usage is high
     */
    public function isHighMemoryUsage(int $threshold = 100 * 1024 * 1024): bool
    {
        return $this->memory_usage > $threshold;
    }

    /**
     * Check if has many database queries
     */
    public function hasManyQueries(int $threshold = 50): bool
    {
        return $this->database_queries > $threshold;
    }

    /**
     * Get performance status
     */
    public function getPerformanceStatusAttribute(): string
    {
        $issues = [];
        
        if ($this->isSlowResponse()) {
            $issues[] = 'slow_response';
        }
        
        if ($this->isHighMemoryUsage()) {
            $issues[] = 'high_memory';
        }
        
        if ($this->hasManyQueries()) {
            $issues[] = 'many_queries';
        }
        
        if (empty($issues)) {
            return 'good';
        }
        
        return implode(',', $issues);
    }

    /**
     * Get performance status label in Persian
     */
    public function getPerformanceStatusLabelAttribute(): string
    {
        return match($this->performance_status) {
            'good' => 'خوب',
            'slow_response' => 'پاسخ کند',
            'high_memory' => 'مصرف حافظه بالا',
            'many_queries' => 'پرس‌وجوهای زیاد',
            'slow_response,high_memory' => 'پاسخ کند و مصرف حافظه بالا',
            'slow_response,many_queries' => 'پاسخ کند و پرس‌وجوهای زیاد',
            'high_memory,many_queries' => 'مصرف حافظه بالا و پرس‌وجوهای زیاد',
            'slow_response,high_memory,many_queries' => 'مشکلات متعدد',
            default => 'نامشخص'
        };
    }

    /**
     * Get performance status color class
     */
    public function getPerformanceStatusColorAttribute(): string
    {
        return match($this->performance_status) {
            'good' => 'bg-green-100 text-green-800',
            'slow_response' => 'bg-yellow-100 text-yellow-800',
            'high_memory' => 'bg-orange-100 text-orange-800',
            'many_queries' => 'bg-red-100 text-red-800',
            default => 'bg-red-100 text-red-800'
        };
    }

    /**
     * Get feature label in Persian
     */
    public function getFeatureLabelAttribute(): string
    {
        return match($this->feature) {
            'timeline' => 'جدول زمانی تصاویر',
            'comment' => 'نظرات',
            'story' => 'داستان‌ها',
            'episode' => 'قسمت‌ها',
            'user' => 'کاربران',
            'admin' => 'پنل مدیریت',
            'api' => 'API',
            'database' => 'پایگاه داده',
            'cache' => 'کش',
            'queue' => 'صف',
            default => $this->feature
        };
    }

    /**
     * Static method to create performance metric
     */
    public static function record(string $feature, array $data, float $responseTime, int $memoryUsage, int $databaseQueries): self
    {
        return self::create([
            'feature' => $feature,
            'data' => $data,
            'response_time' => $responseTime,
            'memory_usage' => $memoryUsage,
            'database_queries' => $databaseQueries,
        ]);
    }

    /**
     * Get average metrics for a feature
     */
    public static function getAverageMetrics(string $feature, int $hours = 24): array
    {
        $query = self::forFeature($feature)->recent($hours);
        
        return [
            'avg_response_time' => $query->avg('response_time'),
            'avg_memory_usage' => $query->avg('memory_usage'),
            'avg_database_queries' => $query->avg('database_queries'),
            'max_response_time' => $query->max('response_time'),
            'max_memory_usage' => $query->max('memory_usage'),
            'max_database_queries' => $query->max('database_queries'),
            'count' => $query->count(),
        ];
    }

    /**
     * Get performance trends for a feature
     */
    public static function getPerformanceTrends(string $feature, int $days = 7): array
    {
        return self::forFeature($feature)
            ->where('created_at', '>=', Carbon::now()->subDays($days))
            ->selectRaw('DATE(created_at) as date, AVG(response_time) as avg_response_time, AVG(memory_usage) as avg_memory_usage, AVG(database_queries) as avg_database_queries')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();
    }
}