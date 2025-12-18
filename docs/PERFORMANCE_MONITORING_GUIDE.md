# راهنمای مانیتورینگ عملکرد - ویژگی‌های جدید سروکست

## مقدمه

این راهنما شامل روش‌های مانیتورینگ عملکرد ویژگی‌های جدید سروکست شامل سیستم تایم‌لاین تصاویر و سیستم نظرات است.

## ویژگی‌های مانیتورینگ

### 1. سیستم تایم‌لاین تصاویر
- زمان بارگذاری تصاویر
- استفاده از حافظه برای تایم‌لاین
- عملکرد API endpoints
- کش کردن داده‌های تایم‌لاین

### 2. سیستم نظرات
- زمان بارگذاری نظرات
- عملکرد جستجو در نظرات
- کش کردن نظرات
- عملکرد API endpoints

### 3. احراز هویت با شماره تلفن
- زمان ارسال کد تأیید
- عملکرد تأیید کد
- کش کردن جلسات
- عملکرد API endpoints

## مانیتورینگ پایگاه داده

### 1. کوئری‌های تایم‌لاین

```php
// مانیتورینگ کوئری‌های تایم‌لاین
class TimelinePerformanceMonitor
{
    public function monitorTimelineQueries()
    {
        DB::listen(function ($query) {
            if (str_contains($query->sql, 'image_timelines')) {
                Log::info('Timeline Query Performance', [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'time' => $query->time,
                    'timestamp' => now()
                ]);
                
                // هشدار برای کوئری‌های کند
                if ($query->time > 1000) {
                    Log::warning('Slow Timeline Query', [
                        'sql' => $query->sql,
                        'time' => $query->time
                    ]);
                }
            }
        });
    }
    
    public function getTimelineStats()
    {
        return [
            'total_timelines' => ImageTimeline::count(),
            'avg_timeline_entries' => ImageTimeline::avg('image_order'),
            'largest_timeline' => ImageTimeline::max('image_order'),
            'timeline_usage' => Episode::where('use_image_timeline', true)->count()
        ];
    }
}
```

### 2. کوئری‌های نظرات

```php
// مانیتورینگ کوئری‌های نظرات
class CommentPerformanceMonitor
{
    public function monitorCommentQueries()
    {
        DB::listen(function ($query) {
            if (str_contains($query->sql, 'story_comments')) {
                Log::info('Comment Query Performance', [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'time' => $query->time,
                    'timestamp' => now()
                ]);
                
                // هشدار برای کوئری‌های کند
                if ($query->time > 500) {
                    Log::warning('Slow Comment Query', [
                        'sql' => $query->sql,
                        'time' => $query->time
                    ]);
                }
            }
        });
    }
    
    public function getCommentStats()
    {
        return [
            'total_comments' => StoryComment::count(),
            'approved_comments' => StoryComment::where('status', 'approved')->count(),
            'pending_comments' => StoryComment::where('status', 'pending')->count(),
            'avg_comments_per_story' => StoryComment::selectRaw('AVG(comment_count) as avg')
                ->fromSub(
                    StoryComment::selectRaw('story_id, COUNT(*) as comment_count')
                        ->groupBy('story_id'),
                    'story_comments'
                )->value('avg')
        ];
    }
}
```

## مانیتورینگ API

### 1. مانیتورینگ Endpoints

```php
// مانیتورینگ عملکرد API
class ApiPerformanceMonitor
{
    public function monitorApiRequests()
    {
        return [
            'timeline_endpoints' => [
                'get_timeline' => $this->getEndpointStats('/api/v1/episodes/{id}/image-timeline'),
                'save_timeline' => $this->getEndpointStats('/api/v1/episodes/{id}/image-timeline', 'POST'),
                'delete_timeline' => $this->getEndpointStats('/api/v1/episodes/{id}/image-timeline', 'DELETE'),
                'get_image_for_time' => $this->getEndpointStats('/api/v1/episodes/{id}/image-for-time')
            ],
            'comment_endpoints' => [
                'get_comments' => $this->getEndpointStats('/api/v1/stories/{id}/comments'),
                'add_comment' => $this->getEndpointStats('/api/v1/stories/{id}/comments', 'POST'),
                'delete_comment' => $this->getEndpointStats('/api/v1/comments/{id}', 'DELETE'),
                'get_user_comments' => $this->getEndpointStats('/api/v1/comments/my-comments')
            ],
            'auth_endpoints' => [
                'send_verification_code' => $this->getEndpointStats('/api/v1/auth/send-verification-code', 'POST'),
                'verify_code' => $this->getEndpointStats('/api/v1/auth/verify-code', 'POST'),
                'register' => $this->getEndpointStats('/api/v1/auth/register', 'POST'),
                'login' => $this->getEndpointStats('/api/v1/auth/login', 'POST')
            ]
        ];
    }
    
    private function getEndpointStats($endpoint, $method = 'GET')
    {
        $logs = Log::where('message', 'like', "%$endpoint%")
            ->where('context->method', $method)
            ->where('created_at', '>=', now()->subHour())
            ->get();
            
        if ($logs->isEmpty()) {
            return [
                'requests' => 0,
                'avg_response_time' => 0,
                'error_rate' => 0
            ];
        }
        
        return [
            'requests' => $logs->count(),
            'avg_response_time' => $logs->avg('context.response_time'),
            'error_rate' => $logs->where('level', 'error')->count() / $logs->count() * 100
        ];
    }
}
```

### 2. مانیتورینگ Response Time

```php
// مانیتورینگ زمان پاسخ
class ResponseTimeMonitor
{
    public function monitorResponseTime()
    {
        return [
            'timeline_endpoints' => [
                'get_timeline' => $this->getResponseTimeStats('get_timeline'),
                'save_timeline' => $this->getResponseTimeStats('save_timeline'),
                'delete_timeline' => $this->getResponseTimeStats('delete_timeline'),
                'get_image_for_time' => $this->getResponseTimeStats('get_image_for_time')
            ],
            'comment_endpoints' => [
                'get_comments' => $this->getResponseTimeStats('get_comments'),
                'add_comment' => $this->getResponseTimeStats('add_comment'),
                'delete_comment' => $this->getResponseTimeStats('delete_comment'),
                'get_user_comments' => $this->getResponseTimeStats('get_user_comments')
            ]
        ];
    }
    
    private function getResponseTimeStats($endpoint)
    {
        $logs = Log::where('message', 'like', "%$endpoint%")
            ->where('created_at', '>=', now()->subHour())
            ->get();
            
        if ($logs->isEmpty()) {
            return [
                'min' => 0,
                'max' => 0,
                'avg' => 0,
                'p95' => 0,
                'p99' => 0
            ];
        }
        
        $responseTimes = $logs->pluck('context.response_time')->sort();
        
        return [
            'min' => $responseTimes->min(),
            'max' => $responseTimes->max(),
            'avg' => $responseTimes->avg(),
            'p95' => $responseTimes->percentile(95),
            'p99' => $responseTimes->percentile(99)
        ];
    }
}
```

## مانیتورینگ حافظه

### 1. مانیتورینگ استفاده از حافظه

```php
// مانیتورینگ استفاده از حافظه
class MemoryMonitor
{
    public function getMemoryUsage()
    {
        return [
            'current_usage' => memory_get_usage(true),
            'peak_usage' => memory_get_peak_usage(true),
            'current_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'peak_usage_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2)
        ];
    }
    
    public function monitorTimelineMemoryUsage()
    {
        $startMemory = memory_get_usage(true);
        
        // بارگذاری تایم‌لاین
        $timeline = ImageTimeline::where('episode_id', 1)->get();
        
        $endMemory = memory_get_usage(true);
        $memoryUsed = $endMemory - $startMemory;
        
        Log::info('Timeline Memory Usage', [
            'episode_id' => 1,
            'timeline_entries' => $timeline->count(),
            'memory_used' => $memoryUsed,
            'memory_used_mb' => round($memoryUsed / 1024 / 1024, 2)
        ]);
        
        return [
            'timeline_entries' => $timeline->count(),
            'memory_used' => $memoryUsed,
            'memory_used_mb' => round($memoryUsed / 1024 / 1024, 2)
        ];
    }
    
    public function monitorCommentMemoryUsage()
    {
        $startMemory = memory_get_usage(true);
        
        // بارگذاری نظرات
        $comments = StoryComment::where('story_id', 1)->get();
        
        $endMemory = memory_get_usage(true);
        $memoryUsed = $endMemory - $startMemory;
        
        Log::info('Comment Memory Usage', [
            'story_id' => 1,
            'comment_count' => $comments->count(),
            'memory_used' => $memoryUsed,
            'memory_used_mb' => round($memoryUsed / 1024 / 1024, 2)
        ]);
        
        return [
            'comment_count' => $comments->count(),
            'memory_used' => $memoryUsed,
            'memory_used_mb' => round($memoryUsed / 1024 / 1024, 2)
        ];
    }
}
```

## مانیتورینگ کش

### 1. مانیتورینگ عملکرد کش

```php
// مانیتورینگ عملکرد کش
class CachePerformanceMonitor
{
    public function monitorCachePerformance()
    {
        return [
            'timeline_cache' => [
                'hit_rate' => $this->getCacheHitRate('timeline'),
                'miss_rate' => $this->getCacheMissRate('timeline'),
                'avg_response_time' => $this->getCacheResponseTime('timeline')
            ],
            'comment_cache' => [
                'hit_rate' => $this->getCacheHitRate('comment'),
                'miss_rate' => $this->getCacheMissRate('comment'),
                'avg_response_time' => $this->getCacheResponseTime('comment')
            ],
            'auth_cache' => [
                'hit_rate' => $this->getCacheHitRate('auth'),
                'miss_rate' => $this->getCacheMissRate('auth'),
                'avg_response_time' => $this->getCacheResponseTime('auth')
            ]
        ];
    }
    
    private function getCacheHitRate($type)
    {
        $hits = Cache::get("cache_hits_$type", 0);
        $misses = Cache::get("cache_misses_$type", 0);
        $total = $hits + $misses;
        
        return $total > 0 ? round(($hits / $total) * 100, 2) : 0;
    }
    
    private function getCacheMissRate($type)
    {
        $hits = Cache::get("cache_hits_$type", 0);
        $misses = Cache::get("cache_misses_$type", 0);
        $total = $hits + $misses;
        
        return $total > 0 ? round(($misses / $total) * 100, 2) : 0;
    }
    
    private function getCacheResponseTime($type)
    {
        return Cache::get("cache_response_time_$type", 0);
    }
    
    public function trackCacheHit($type, $responseTime)
    {
        Cache::increment("cache_hits_$type");
        Cache::put("cache_response_time_$type", $responseTime);
    }
    
    public function trackCacheMiss($type)
    {
        Cache::increment("cache_misses_$type");
    }
}
```

## مانیتورینگ تصاویر

### 1. مانیتورینگ بارگذاری تصاویر

```php
// مانیتورینگ بارگذاری تصاویر
class ImagePerformanceMonitor
{
    public function monitorImageLoading()
    {
        return [
            'total_images' => $this->getTotalImages(),
            'avg_image_size' => $this->getAvgImageSize(),
            'largest_image' => $this->getLargestImage(),
            'image_loading_times' => $this->getImageLoadingTimes()
        ];
    }
    
    private function getTotalImages()
    {
        return ImageTimeline::distinct('image_url')->count();
    }
    
    private function getAvgImageSize()
    {
        $images = ImageTimeline::distinct('image_url')->get();
        $totalSize = 0;
        
        foreach ($images as $image) {
            $size = $this->getImageSize($image->image_url);
            $totalSize += $size;
        }
        
        return $images->count() > 0 ? round($totalSize / $images->count() / 1024, 2) : 0; // KB
    }
    
    private function getLargestImage()
    {
        $images = ImageTimeline::distinct('image_url')->get();
        $largestSize = 0;
        $largestUrl = '';
        
        foreach ($images as $image) {
            $size = $this->getImageSize($image->image_url);
            if ($size > $largestSize) {
                $largestSize = $size;
                $largestUrl = $image->image_url;
            }
        }
        
        return [
            'url' => $largestUrl,
            'size' => round($largestSize / 1024, 2), // KB
            'size_mb' => round($largestSize / 1024 / 1024, 2)
        ];
    }
    
    private function getImageSize($url)
    {
        $headers = get_headers($url, 1);
        return isset($headers['Content-Length']) ? (int)$headers['Content-Length'] : 0;
    }
    
    private function getImageLoadingTimes()
    {
        $logs = Log::where('message', 'like', '%image_loading%')
            ->where('created_at', '>=', now()->subHour())
            ->get();
            
        if ($logs->isEmpty()) {
            return [
                'min' => 0,
                'max' => 0,
                'avg' => 0
            ];
        }
        
        $loadingTimes = $logs->pluck('context.loading_time');
        
        return [
            'min' => $loadingTimes->min(),
            'max' => $loadingTimes->max(),
            'avg' => $loadingTimes->avg()
        ];
    }
}
```

## مانیتورینگ Real-time

### 1. مانیتورینگ Real-time

```php
// مانیتورینگ Real-time
class RealTimeMonitor
{
    public function getRealTimeStats()
    {
        return [
            'active_users' => $this->getActiveUsers(),
            'current_requests' => $this->getCurrentRequests(),
            'system_load' => $this->getSystemLoad(),
            'memory_usage' => $this->getMemoryUsage(),
            'disk_usage' => $this->getDiskUsage()
        ];
    }
    
    private function getActiveUsers()
    {
        return Cache::get('active_users', 0);
    }
    
    private function getCurrentRequests()
    {
        return Cache::get('current_requests', 0);
    }
    
    private function getSystemLoad()
    {
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            return [
                '1min' => $load[0],
                '5min' => $load[1],
                '15min' => $load[2]
            ];
        }
        
        return [
            '1min' => 0,
            '5min' => 0,
            '15min' => 0
        ];
    }
    
    private function getMemoryUsage()
    {
        return [
            'used' => memory_get_usage(true),
            'peak' => memory_get_peak_usage(true),
            'used_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2)
        ];
    }
    
    private function getDiskUsage()
    {
        $total = disk_total_space('/');
        $free = disk_free_space('/');
        $used = $total - $free;
        
        return [
            'total' => $total,
            'used' => $used,
            'free' => $free,
            'usage_percentage' => round(($used / $total) * 100, 2)
        ];
    }
}
```

## گزارش‌گیری عملکرد

### 1. گزارش عملکرد روزانه

```php
// گزارش عملکرد روزانه
class DailyPerformanceReport
{
    public function generateDailyReport()
    {
        $report = [
            'date' => now()->format('Y-m-d'),
            'database_performance' => $this->getDatabasePerformance(),
            'api_performance' => $this->getApiPerformance(),
            'memory_usage' => $this->getMemoryUsage(),
            'cache_performance' => $this->getCachePerformance(),
            'image_performance' => $this->getImagePerformance(),
            'errors' => $this->getErrors(),
            'recommendations' => $this->getRecommendations()
        ];
        
        // ذخیره گزارش
        $this->saveReport($report);
        
        return $report;
    }
    
    private function getDatabasePerformance()
    {
        return [
            'slow_queries' => Log::where('message', 'like', '%Slow Query%')
                ->where('created_at', '>=', now()->subDay())
                ->count(),
            'avg_query_time' => $this->getAvgQueryTime(),
            'total_queries' => $this->getTotalQueries()
        ];
    }
    
    private function getApiPerformance()
    {
        return [
            'total_requests' => $this->getTotalRequests(),
            'avg_response_time' => $this->getAvgResponseTime(),
            'error_rate' => $this->getErrorRate()
        ];
    }
    
    private function getMemoryUsage()
    {
        return [
            'peak_usage' => memory_get_peak_usage(true),
            'avg_usage' => $this->getAvgMemoryUsage()
        ];
    }
    
    private function getCachePerformance()
    {
        return [
            'hit_rate' => $this->getCacheHitRate(),
            'miss_rate' => $this->getCacheMissRate()
        ];
    }
    
    private function getImagePerformance()
    {
        return [
            'total_images' => ImageTimeline::distinct('image_url')->count(),
            'avg_loading_time' => $this->getAvgImageLoadingTime()
        ];
    }
    
    private function getErrors()
    {
        return Log::where('level', 'error')
            ->where('created_at', '>=', now()->subDay())
            ->count();
    }
    
    private function getRecommendations()
    {
        $recommendations = [];
        
        // بررسی کوئری‌های کند
        if ($this->getSlowQueriesCount() > 10) {
            $recommendations[] = 'بهینه‌سازی کوئری‌های پایگاه داده';
        }
        
        // بررسی استفاده از حافظه
        if ($this->getMemoryUsage() > 80) {
            $recommendations[] = 'بهینه‌سازی استفاده از حافظه';
        }
        
        // بررسی عملکرد کش
        if ($this->getCacheHitRate() < 70) {
            $recommendations[] = 'بهبود استراتژی کش';
        }
        
        return $recommendations;
    }
    
    private function saveReport($report)
    {
        $filename = 'performance-report-' . now()->format('Y-m-d') . '.json';
        $path = storage_path('app/reports/' . $filename);
        
        file_put_contents($path, json_encode($report, JSON_PRETTY_PRINT));
    }
}
```

## هشدارهای عملکرد

### 1. سیستم هشدار

```php
// سیستم هشدار عملکرد
class PerformanceAlertSystem
{
    public function checkAlerts()
    {
        $alerts = [];
        
        // هشدار کوئری‌های کند
        if ($this->getSlowQueriesCount() > 5) {
            $alerts[] = [
                'type' => 'warning',
                'message' => 'تعداد کوئری‌های کند بیش از حد مجاز',
                'count' => $this->getSlowQueriesCount()
            ];
        }
        
        // هشدار استفاده از حافظه
        if ($this->getMemoryUsage() > 90) {
            $alerts[] = [
                'type' => 'critical',
                'message' => 'استفاده از حافظه بیش از 90%',
                'usage' => $this->getMemoryUsage()
            ];
        }
        
        // هشدار عملکرد API
        if ($this->getAvgResponseTime() > 2000) {
            $alerts[] = [
                'type' => 'warning',
                'message' => 'زمان پاسخ API بیش از 2 ثانیه',
                'avg_time' => $this->getAvgResponseTime()
            ];
        }
        
        // هشدار عملکرد کش
        if ($this->getCacheHitRate() < 50) {
            $alerts[] = [
                'type' => 'warning',
                'message' => 'نرخ hit کش کمتر از 50%',
                'hit_rate' => $this->getCacheHitRate()
            ];
        }
        
        return $alerts;
    }
    
    public function sendAlerts($alerts)
    {
        foreach ($alerts as $alert) {
            if ($alert['type'] === 'critical') {
                $this->sendCriticalAlert($alert);
            } else {
                $this->sendWarningAlert($alert);
            }
        }
    }
    
    private function sendCriticalAlert($alert)
    {
        // ارسال ایمیل
        Mail::to('admin@sarvcast.com')->send(new CriticalAlertMail($alert));
        
        // ارسال SMS
        $this->sendSMS('09123456789', $alert['message']);
        
        // ارسال تلگرام
        $this->sendTelegram($alert['message']);
    }
    
    private function sendWarningAlert($alert)
    {
        // ارسال ایمیل
        Mail::to('admin@sarvcast.com')->send(new WarningAlertMail($alert));
    }
}
```

## ابزارهای مانیتورینگ

### 1. Prometheus Metrics

```php
// Prometheus Metrics
class PrometheusMetrics
{
    public function getMetrics()
    {
        return [
            'timeline_requests_total' => $this->getTimelineRequestsTotal(),
            'timeline_response_time_seconds' => $this->getTimelineResponseTime(),
            'comment_requests_total' => $this->getCommentRequestsTotal(),
            'comment_response_time_seconds' => $this->getCommentResponseTime(),
            'auth_requests_total' => $this->getAuthRequestsTotal(),
            'auth_response_time_seconds' => $this->getAuthResponseTime(),
            'memory_usage_bytes' => memory_get_usage(true),
            'cache_hits_total' => $this->getCacheHitsTotal(),
            'cache_misses_total' => $this->getCacheMissesTotal()
        ];
    }
    
    private function getTimelineRequestsTotal()
    {
        return Log::where('message', 'like', '%timeline%')
            ->where('created_at', '>=', now()->subHour())
            ->count();
    }
    
    private function getTimelineResponseTime()
    {
        $logs = Log::where('message', 'like', '%timeline%')
            ->where('created_at', '>=', now()->subHour())
            ->get();
            
        return $logs->avg('context.response_time') / 1000; // Convert to seconds
    }
    
    private function getCommentRequestsTotal()
    {
        return Log::where('message', 'like', '%comment%')
            ->where('created_at', '>=', now()->subHour())
            ->count();
    }
    
    private function getCommentResponseTime()
    {
        $logs = Log::where('message', 'like', '%comment%')
            ->where('created_at', '>=', now()->subHour())
            ->get();
            
        return $logs->avg('context.response_time') / 1000; // Convert to seconds
    }
    
    private function getAuthRequestsTotal()
    {
        return Log::where('message', 'like', '%auth%')
            ->where('created_at', '>=', now()->subHour())
            ->count();
    }
    
    private function getAuthResponseTime()
    {
        $logs = Log::where('message', 'like', '%auth%')
            ->where('created_at', '>=', now()->subHour())
            ->get();
            
        return $logs->avg('context.response_time') / 1000; // Convert to seconds
    }
    
    private function getCacheHitsTotal()
    {
        return Cache::get('cache_hits_total', 0);
    }
    
    private function getCacheMissesTotal()
    {
        return Cache::get('cache_misses_total', 0);
    }
}
```

## نتیجه‌گیری

### توصیه‌های کلی
1. **مانیتورینگ مداوم**: پیاده‌سازی مانیتورینگ مداوم برای تمام ویژگی‌ها
2. **هشدارهای هوشمند**: تنظیم هشدارهای مناسب برای مشکلات عملکرد
3. **بهینه‌سازی مداوم**: بررسی و بهینه‌سازی عملکرد به طور مداوم
4. **گزارش‌گیری منظم**: تولید گزارش‌های عملکرد به طور منظم
5. **آموزش تیم**: آموزش تیم توسعه در زمینه مانیتورینگ عملکرد

### ابزارهای پیشنهادی
- **Prometheus**: برای جمع‌آوری metrics
- **Grafana**: برای نمایش metrics
- **New Relic**: برای مانیتورینگ APM
- **DataDog**: برای مانیتورینگ کامل
- **Laravel Telescope**: برای مانیتورینگ Laravel

---

**نکته مهم**: این راهنما برای مانیتورینگ عملکرد ویژگی‌های جدید سروکست تهیه شده است. برای سوالات فنی، با تیم DevOps تماس بگیرید.

**تماس با تیم DevOps**:
- ایمیل: devops@sarvcast.com
- تلفن: 021-12345678
- تلگرام: @SarvCastDevOps
