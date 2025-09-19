# راهنمای بهینه‌سازی عملکرد پنل مدیریت SarvCast

## فهرست مطالب

1. [معرفی بهینه‌سازی عملکرد](#معرفی-بهینه‌سازی-عملکرد)
2. [بهینه‌سازی پایگاه داده](#بهینه‌سازی-پایگاه-داده)
3. [بهینه‌سازی کوئری‌ها](#بهینه‌سازی-کوئری‌ها)
4. [بهینه‌سازی کش](#بهینه‌سازی-کش)
5. [بهینه‌سازی فایل‌ها](#بهینه‌سازی-فایل‌ها)
6. [بهینه‌سازی شبکه](#بهینه‌سازی-شبکه)
7. [بهینه‌سازی سرور](#بهینه‌سازی-سرور)
8. [نظارت بر عملکرد](#نظارت-بر-عملکرد)
9. [تحلیل عملکرد](#تحلیل-عملکرد)
10. [بهینه‌سازی مداوم](#بهینه‌سازی-مداوم)

## معرفی بهینه‌سازی عملکرد

بهینه‌سازی عملکرد فرآیند بهبود سرعت، کارایی و پاسخ‌دهی سیستم است که منجر به تجربه کاربری بهتر می‌شود.

### اهداف بهینه‌سازی
- **کاهش زمان بارگذاری**: بهبود سرعت بارگذاری صفحات
- **کاهش استفاده از منابع**: بهینه‌سازی استفاده از CPU، حافظه و دیسک
- **بهبود مقیاس‌پذیری**: قابلیت مدیریت ترافیک بیشتر
- **بهبود تجربه کاربری**: پاسخ‌دهی سریع‌تر و روان‌تر

### شاخص‌های عملکرد
- **زمان پاسخ**: Time to First Byte (TTFB)
- **زمان بارگذاری**: Page Load Time
- **استفاده از حافظه**: Memory Usage
- **استفاده از CPU**: CPU Utilization
- **تعداد درخواست‌ها**: Request Count

## بهینه‌سازی پایگاه داده

### تنظیمات MySQL

#### تنظیمات my.cnf
```ini
# فایل: /etc/mysql/mysql.conf.d/mysqld.cnf

[mysqld]
# تنظیمات حافظه
innodb_buffer_pool_size = 1G
innodb_log_file_size = 256M
innodb_log_buffer_size = 16M
innodb_flush_log_at_trx_commit = 2

# تنظیمات کوئری
query_cache_size = 64M
query_cache_type = 1
query_cache_limit = 2M

# تنظیمات اتصال
max_connections = 200
max_connect_errors = 1000
connect_timeout = 10
wait_timeout = 600

# تنظیمات جدول
table_open_cache = 2000
table_definition_cache = 1400

# تنظیمات موقت
tmp_table_size = 64M
max_heap_table_size = 64M

# تنظیمات لاگ
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow.log
long_query_time = 2
```

#### بهینه‌سازی جداول
```sql
-- تحلیل جداول
ANALYZE TABLE users, stories, episodes, categories;

-- بهینه‌سازی جداول
OPTIMIZE TABLE users, stories, episodes, categories;

-- بررسی وضعیت جداول
SHOW TABLE STATUS LIKE 'users';
SHOW TABLE STATUS LIKE 'stories';
SHOW TABLE STATUS LIKE 'episodes';
```

### ایندکس‌گذاری

#### ایجاد ایندکس‌های بهینه
```sql
-- ایندکس برای جستجو
CREATE INDEX idx_users_name ON users(name);
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_status ON users(status);

-- ایندکس برای فیلتر
CREATE INDEX idx_stories_status ON stories(status);
CREATE INDEX idx_stories_category_id ON stories(category_id);
CREATE INDEX idx_episodes_story_id ON episodes(story_id);

-- ایندکس ترکیبی
CREATE INDEX idx_users_status_created ON users(status, created_at);
CREATE INDEX idx_stories_category_status ON stories(category_id, status);
CREATE INDEX idx_episodes_story_status ON episodes(story_id, status);

-- ایندکس برای مرتب‌سازی
CREATE INDEX idx_stories_created_at ON stories(created_at);
CREATE INDEX idx_episodes_created_at ON episodes(created_at);
CREATE INDEX idx_users_created_at ON users(created_at);
```

#### بررسی ایندکس‌ها
```sql
-- نمایش ایندکس‌های موجود
SHOW INDEX FROM users;
SHOW INDEX FROM stories;
SHOW INDEX FROM episodes;

-- بررسی استفاده از ایندکس‌ها
EXPLAIN SELECT * FROM users WHERE status = 'active';
EXPLAIN SELECT * FROM stories WHERE category_id = 1 AND status = 'published';
```

## بهینه‌سازی کوئری‌ها

### کوئری‌های بهینه

#### استفاده از Eager Loading
```php
// در فایل app/Http/Controllers/Admin/UserController.php
public function index()
{
    // بهینه: استفاده از eager loading
    $users = User::with(['stories', 'episodes', 'subscriptions'])
        ->where('status', 'active')
        ->orderBy('created_at', 'desc')
        ->paginate(20);
    
    return view('admin.users.index', compact('users'));
}

// غیربهینه: N+1 problem
public function indexBad()
{
    $users = User::where('status', 'active')->get();
    foreach ($users as $user) {
        $user->stories; // کوئری اضافی برای هر کاربر
        $user->episodes; // کوئری اضافی برای هر کاربر
    }
}
```

#### استفاده از Select
```php
// بهینه: انتخاب فیلدهای مورد نیاز
$users = User::select(['id', 'name', 'email', 'status', 'created_at'])
    ->where('status', 'active')
    ->get();

// غیربهینه: انتخاب تمام فیلدها
$users = User::where('status', 'active')->get();
```

#### استفاده از Where
```php
// بهینه: استفاده از where با ایندکس
$stories = Story::where('status', 'published')
    ->where('category_id', 1)
    ->where('created_at', '>=', now()->subDays(30))
    ->get();

// غیربهینه: استفاده از whereRaw
$stories = Story::whereRaw('status = "published" AND category_id = 1')
    ->get();
```

### کوئری‌های پیچیده

#### استفاده از Join
```php
// بهینه: استفاده از join
$stories = DB::table('stories')
    ->join('categories', 'stories.category_id', '=', 'categories.id')
    ->join('users', 'stories.user_id', '=', 'users.id')
    ->select('stories.*', 'categories.name as category_name', 'users.name as user_name')
    ->where('stories.status', 'published')
    ->get();

// غیربهینه: استفاده از whereHas
$stories = Story::whereHas('category', function($query) {
    $query->where('name', 'like', '%داستان%');
})->get();
```

#### استفاده از Subquery
```php
// بهینه: استفاده از subquery
$users = User::whereIn('id', function($query) {
    $query->select('user_id')
        ->from('stories')
        ->where('status', 'published');
})->get();

// غیربهینه: استفاده از whereHas
$users = User::whereHas('stories', function($query) {
    $query->where('status', 'published');
})->get();
```

## بهینه‌سازی کش

### کش Laravel

#### تنظیمات کش
```php
// در فایل config/cache.php
'default' => env('CACHE_DRIVER', 'redis'),

'stores' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => 'cache',
        'lock_connection' => 'default',
    ],
    
    'memcached' => [
        'driver' => 'memcached',
        'persistent_id' => env('MEMCACHED_PERSISTENT_ID'),
        'sasl' => [
            env('MEMCACHED_USERNAME'),
            env('MEMCACHED_PASSWORD'),
        ],
        'options' => [
            Memcached::OPT_DISTRIBUTION => Memcached::DISTRIBUTION_CONSISTENT,
            Memcached::OPT_REMOVE_FAILED_SERVERS => true,
            Memcached::OPT_RETRY_TIMEOUT => 2,
        ],
        'servers' => [
            [
                'host' => env('MEMCACHED_HOST', '127.0.0.1'),
                'port' => env('MEMCACHED_PORT', 11211),
                'weight' => 100,
            ],
        ],
    ],
],
```

#### استفاده از کش در کنترلرها
```php
// در فایل app/Http/Controllers/Admin/DashboardController.php
public function index()
{
    $cacheKey = 'admin_dashboard_stats_' . auth()->id();
    
    $stats = Cache::remember($cacheKey, 300, function () {
        return [
            'total_users' => User::count(),
            'total_stories' => Story::count(),
            'total_episodes' => Episode::count(),
            'total_categories' => Category::count(),
            'active_users' => User::where('status', 'active')->count(),
            'published_stories' => Story::where('status', 'published')->count(),
        ];
    });
    
    return view('admin.dashboard.index', compact('stats'));
}
```

#### کش مدل‌ها
```php
// در فایل app/Models/User.php
class User extends Model
{
    protected $fillable = ['name', 'email', 'password', 'status'];
    
    public function getCachedStories()
    {
        return Cache::remember("user_{$this->id}_stories", 3600, function () {
            return $this->stories()->where('status', 'published')->get();
        });
    }
    
    public function getCachedEpisodes()
    {
        return Cache::remember("user_{$this->id}_episodes", 3600, function () {
            return $this->episodes()->where('status', 'published')->get();
        });
    }
}
```

### کش Redis

#### تنظیمات Redis
```bash
# فایل: /etc/redis/redis.conf

# تنظیمات حافظه
maxmemory 1gb
maxmemory-policy allkeys-lru

# تنظیمات persistence
save 900 1
save 300 10
save 60 10000

# تنظیمات اتصال
tcp-keepalive 300
timeout 0

# تنظیمات لاگ
loglevel notice
logfile /var/log/redis/redis-server.log
```

#### استفاده از Redis
```php
// در فایل app/Services/CacheService.php
class CacheService
{
    public function cacheUserStats($userId)
    {
        $key = "user_stats_{$userId}";
        $stats = [
            'stories_count' => Story::where('user_id', $userId)->count(),
            'episodes_count' => Episode::where('user_id', $userId)->count(),
            'subscriptions_count' => Subscription::where('user_id', $userId)->count(),
        ];
        
        Redis::setex($key, 3600, json_encode($stats));
        return $stats;
    }
    
    public function getCachedUserStats($userId)
    {
        $key = "user_stats_{$userId}";
        $cached = Redis::get($key);
        
        if ($cached) {
            return json_decode($cached, true);
        }
        
        return $this->cacheUserStats($userId);
    }
}
```

## بهینه‌سازی فایل‌ها

### فشرده‌سازی فایل‌ها

#### تنظیمات Apache
```apache
# فایل: /etc/apache2/mods-available/deflate.conf

<IfModule mod_deflate.c>
    # فشرده‌سازی فایل‌های HTML
    AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript application/json
    
    # فشرده‌سازی فایل‌های CSS
    AddOutputFilterByType DEFLATE text/css
    
    # فشرده‌سازی فایل‌های JavaScript
    AddOutputFilterByType DEFLATE application/javascript text/javascript
    
    # فشرده‌سازی فایل‌های JSON
    AddOutputFilterByType DEFLATE application/json
    
    # تنظیمات فشرده‌سازی
    DeflateCompressionLevel 6
    DeflateBufferSize 8096
    DeflateMemLevel 9
    DeflateWindowSize 15
</IfModule>
```

#### تنظیمات Nginx
```nginx
# فایل: /etc/nginx/nginx.conf

http {
    # فشرده‌سازی
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_proxied any;
    gzip_comp_level 6;
    gzip_types
        text/plain
        text/css
        text/xml
        text/javascript
        application/json
        application/javascript
        application/xml+rss
        application/atom+xml
        image/svg+xml;
}
```

### بهینه‌سازی تصاویر

#### فشرده‌سازی تصاویر
```bash
#!/bin/bash
# فایل: /opt/scripts/optimize_images.sh

IMAGE_DIR="/var/www/sarvcast/public/images"

echo "شروع بهینه‌سازی تصاویر..."

# فشرده‌سازی تصاویر JPEG
find $IMAGE_DIR -name "*.jpg" -o -name "*.jpeg" | while read file; do
    jpegoptim --max=85 --strip-all "$file"
done

# فشرده‌سازی تصاویر PNG
find $IMAGE_DIR -name "*.png" | while read file; do
    pngquant --force --ext .png --quality=85-95 "$file"
done

# فشرده‌سازی تصاویر WebP
find $IMAGE_DIR -name "*.jpg" -o -name "*.jpeg" | while read file; do
    cwebp -q 85 "$file" -o "${file%.*}.webp"
done

echo "بهینه‌سازی تصاویر تکمیل شد"
```

#### استفاده از WebP
```php
// در فایل app/Services/ImageService.php
class ImageService
{
    public function optimizeImage($imagePath)
    {
        $webpPath = str_replace(['.jpg', '.jpeg', '.png'], '.webp', $imagePath);
        
        if (!file_exists($webpPath)) {
            $command = "cwebp -q 85 '$imagePath' -o '$webpPath'";
            exec($command);
        }
        
        return $webpPath;
    }
    
    public function getOptimizedImage($imagePath)
    {
        $webpPath = $this->optimizeImage($imagePath);
        
        // بررسی پشتیبانی مرورگر از WebP
        if (request()->header('Accept') && strpos(request()->header('Accept'), 'image/webp') !== false) {
            return $webpPath;
        }
        
        return $imagePath;
    }
}
```

## بهینه‌سازی شبکه

### CDN

#### تنظیمات CloudFlare
```bash
# فایل: /opt/scripts/setup_cloudflare.sh

DOMAIN="admin.sarvcast.com"
EMAIL="admin@sarvcast.com"
API_KEY="your_api_key"

# تنظیمات DNS
curl -X POST "https://api.cloudflare.com/client/v4/zones" \
  -H "X-Auth-Email: $EMAIL" \
  -H "X-Auth-Key: $API_KEY" \
  -H "Content-Type: application/json" \
  --data '{
    "name": "'$DOMAIN'",
    "type": "A",
    "content": "your_server_ip"
  }'

# تنظیمات کش
curl -X PATCH "https://api.cloudflare.com/client/v4/zones/zone_id/settings/cache_level" \
  -H "X-Auth-Email: $EMAIL" \
  -H "X-Auth-Key: $API_KEY" \
  -H "Content-Type: application/json" \
  --data '{"value": "aggressive"}'
```

#### استفاده از CDN در Laravel
```php
// در فایل config/filesystems.php
'disks' => [
    'cdn' => [
        'driver' => 's3',
        'key' => env('CDN_ACCESS_KEY'),
        'secret' => env('CDN_SECRET_KEY'),
        'region' => env('CDN_REGION'),
        'bucket' => env('CDN_BUCKET'),
        'url' => env('CDN_URL'),
    ],
],
```

### HTTP/2

#### تنظیمات Apache
```apache
# فایل: /etc/apache2/mods-available/http2.conf

LoadModule http2_module modules/mod_http2.so

<VirtualHost *:443>
    Protocols h2 http/1.1
    H2Push on
    H2PushPriority * after
    H2PushPriority text/css before
    H2PushPriority application/javascript before
</VirtualHost>
```

#### تنظیمات Nginx
```nginx
# فایل: /etc/nginx/nginx.conf

http {
    # HTTP/2
    listen 443 ssl http2;
    
    # Server Push
    location / {
        http2_push /css/app.css;
        http2_push /js/app.js;
    }
}
```

## بهینه‌سازی سرور

### تنظیمات PHP

#### تنظیمات php.ini
```ini
# فایل: /etc/php/8.1/fpm/php.ini

; تنظیمات حافظه
memory_limit = 512M
max_execution_time = 300
max_input_time = 300

; تنظیمات آپلود
upload_max_filesize = 100M
post_max_size = 100M
max_file_uploads = 20

; تنظیمات OPcache
opcache.enable = 1
opcache.memory_consumption = 256
opcache.interned_strings_buffer = 16
opcache.max_accelerated_files = 20000
opcache.validate_timestamps = 0
opcache.save_comments = 1
opcache.fast_shutdown = 1

; تنظیمات JIT
opcache.jit_buffer_size = 100M
opcache.jit = 1235
```

#### تنظیمات PHP-FPM
```ini
# فایل: /etc/php/8.1/fpm/pool.d/www.conf

[www]
user = www-data
group = www-data
listen = /run/php/php8.1-fpm.sock
listen.owner = www-data
listen.group = www-data
listen.mode = 0660

; تنظیمات فرآیند
pm = dynamic
pm.max_children = 50
pm.start_servers = 10
pm.min_spare_servers = 5
pm.max_spare_servers = 20
pm.max_requests = 1000

; تنظیمات عملکرد
pm.process_idle_timeout = 10s
request_terminate_timeout = 300s
```

### تنظیمات Apache

#### تنظیمات httpd.conf
```apache
# فایل: /etc/apache2/apache2.conf

# تنظیمات عملکرد
KeepAlive On
MaxKeepAliveRequests 100
KeepAliveTimeout 5

# تنظیمات ماژول
LoadModule rewrite_module modules/mod_rewrite.so
LoadModule deflate_module modules/mod_deflate.so
LoadModule expires_module modules/mod_expires.so
LoadModule headers_module modules/mod_headers.so

# تنظیمات امنیت
ServerTokens Prod
ServerSignature Off
```

#### تنظیمات VirtualHost
```apache
# فایل: /etc/apache2/sites-available/admin.sarvcast.com.conf

<VirtualHost *:443>
    ServerName admin.sarvcast.com
    DocumentRoot /var/www/sarvcast/public
    
    # تنظیمات عملکرد
    <Directory /var/www/sarvcast/public>
        AllowOverride All
        Require all granted
        
        # کش فایل‌های استاتیک
        <FilesMatch "\.(css|js|png|jpg|jpeg|gif|ico|svg)$">
            ExpiresActive On
            ExpiresDefault "access plus 1 month"
            Header set Cache-Control "public, immutable"
        </FilesMatch>
        
        # کش فایل‌های HTML
        <FilesMatch "\.(html|htm)$">
            ExpiresActive On
            ExpiresDefault "access plus 1 hour"
            Header set Cache-Control "public, must-revalidate"
        </FilesMatch>
    </Directory>
    
    # فشرده‌سازی
    <Location />
        SetOutputFilter DEFLATE
        SetEnvIfNoCase Request_URI \
            \.(?:gif|jpe?g|png)$ no-gzip dont-vary
        SetEnvIfNoCase Request_URI \
            \.(?:exe|t?gz|zip|bz2|sit|rar)$ no-gzip dont-vary
    </Location>
</VirtualHost>
```

## نظارت بر عملکرد

### مانیتورینگ Laravel

#### استفاده از Laravel Telescope
```bash
# نصب Laravel Telescope
composer require laravel/telescope --dev

# انتشار و اجرای migration
php artisan telescope:install
php artisan migrate

# تنظیمات Telescope
php artisan telescope:publish
```

#### مانیتورینگ دستی
```php
// در فایل app/Http/Middleware/PerformanceMiddleware.php
class PerformanceMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage();
        
        $response = $next($request);
        
        $endTime = microtime(true);
        $endMemory = memory_get_usage();
        
        $executionTime = $endTime - $startTime;
        $memoryUsage = $endMemory - $startMemory;
        
        // لاگ‌گیری عملکرد
        Log::info('Performance Metrics', [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'execution_time' => $executionTime,
            'memory_usage' => $memoryUsage,
            'peak_memory' => memory_get_peak_usage(),
        ]);
        
        // هشدار در صورت کندی
        if ($executionTime > 2.0) {
            Log::warning('Slow Request Detected', [
                'url' => $request->fullUrl(),
                'execution_time' => $executionTime,
            ]);
        }
        
        return $response;
    }
}
```

### مانیتورینگ سیستم

#### استفاده از htop
```bash
# نصب htop
sudo apt install htop

# اجرای htop
htop
```

#### استفاده از iotop
```bash
# نصب iotop
sudo apt install iotop

# اجرای iotop
sudo iotop
```

#### استفاده از netstat
```bash
# نمایش اتصالات فعال
netstat -tuln

# نمایش اتصالات با فرآیندها
netstat -tulnp

# نمایش آمار شبکه
netstat -s
```

## تحلیل عملکرد

### تحلیل کوئری‌ها

#### استفاده از Laravel Debugbar
```bash
# نصب Laravel Debugbar
composer require barryvdh/laravel-debugbar --dev

# تنظیمات Debugbar
php artisan vendor:publish --provider="Barryvdh\Debugbar\ServiceProvider"
```

#### تحلیل کوئری‌های کند
```sql
-- نمایش کوئری‌های کند
SELECT 
    query_time,
    lock_time,
    rows_sent,
    rows_examined,
    sql_text
FROM mysql.slow_log
ORDER BY query_time DESC
LIMIT 10;
```

#### تحلیل استفاده از ایندکس‌ها
```sql
-- نمایش استفاده از ایندکس‌ها
SELECT 
    table_name,
    index_name,
    cardinality,
    sub_part,
    packed,
    nullable,
    index_type
FROM information_schema.statistics
WHERE table_schema = 'sarvcast'
ORDER BY table_name, index_name;
```

### تحلیل حافظه

#### مانیتورینگ حافظه PHP
```php
// در فایل app/Services/MemoryService.php
class MemoryService
{
    public function getMemoryUsage()
    {
        return [
            'current' => memory_get_usage(true),
            'peak' => memory_get_peak_usage(true),
            'limit' => ini_get('memory_limit'),
            'usage_percentage' => $this->getUsagePercentage(),
        ];
    }
    
    private function getUsagePercentage()
    {
        $current = memory_get_usage(true);
        $limit = $this->parseMemoryLimit(ini_get('memory_limit'));
        
        return ($current / $limit) * 100;
    }
    
    private function parseMemoryLimit($limit)
    {
        $unit = strtolower(substr($limit, -1));
        $value = (int) $limit;
        
        switch ($unit) {
            case 'g':
                return $value * 1024 * 1024 * 1024;
            case 'm':
                return $value * 1024 * 1024;
            case 'k':
                return $value * 1024;
            default:
                return $value;
        }
    }
}
```

## بهینه‌سازی مداوم

### اتوماسیون بهینه‌سازی

#### اسکریپت بهینه‌سازی روزانه
```bash
#!/bin/bash
# فایل: /opt/scripts/daily_optimization.sh

echo "شروع بهینه‌سازی روزانه..."

# پاک کردن کش Laravel
php /var/www/sarvcast/artisan cache:clear
php /var/www/sarvcast/artisan config:clear
php /var/www/sarvcast/artisan view:clear
php /var/www/sarvcast/artisan route:clear

# بهینه‌سازی پایگاه داده
mysql -u root -p -e "OPTIMIZE TABLE sarvcast.users, sarvcast.stories, sarvcast.episodes, sarvcast.categories;"

# پاک کردن لاگ‌های قدیمی
find /var/log -name "*.log" -mtime +30 -delete

# بهینه‌سازی تصاویر
/opt/scripts/optimize_images.sh

echo "بهینه‌سازی روزانه تکمیل شد"
```

#### تنظیم Cron Job
```bash
# ویرایش crontab
sudo crontab -e

# اجرای بهینه‌سازی روزانه در ساعت 3 صبح
0 3 * * * /opt/scripts/daily_optimization.sh >> /var/log/optimization.log 2>&1
```

### نظارت مداوم

#### مانیتورینگ خودکار
```php
// در فایل app/Console/Commands/PerformanceMonitorCommand.php
class PerformanceMonitorCommand extends Command
{
    protected $signature = 'performance:monitor';
    protected $description = 'نظارت بر عملکرد سیستم';

    public function handle()
    {
        $this->info('شروع نظارت بر عملکرد...');
        
        // بررسی عملکرد پایگاه داده
        $this->checkDatabasePerformance();
        
        // بررسی عملکرد حافظه
        $this->checkMemoryPerformance();
        
        // بررسی عملکرد دیسک
        $this->checkDiskPerformance();
        
        // بررسی عملکرد شبکه
        $this->checkNetworkPerformance();
        
        $this->info('نظارت بر عملکرد تکمیل شد');
    }
    
    private function checkDatabasePerformance()
    {
        $slowQueries = DB::select("
            SELECT COUNT(*) as count 
            FROM mysql.slow_log 
            WHERE start_time > DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ");
        
        if ($slowQueries[0]->count > 10) {
            $this->warn("تعداد کوئری‌های کند بالا است: {$slowQueries[0]->count}");
        }
    }
    
    private function checkMemoryPerformance()
    {
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = $this->parseMemoryLimit(ini_get('memory_limit'));
        $usagePercentage = ($memoryUsage / $memoryLimit) * 100;
        
        if ($usagePercentage > 80) {
            $this->warn("استفاده از حافظه بالا است: {$usagePercentage}%");
        }
    }
    
    private function checkDiskPerformance()
    {
        $diskUsage = disk_free_space('/');
        $diskTotal = disk_total_space('/');
        $usagePercentage = (($diskTotal - $diskUsage) / $diskTotal) * 100;
        
        if ($usagePercentage > 80) {
            $this->warn("استفاده از دیسک بالا است: {$usagePercentage}%");
        }
    }
    
    private function checkNetworkPerformance()
    {
        $connections = exec('netstat -an | grep :80 | wc -l');
        
        if ($connections > 1000) {
            $this->warn("تعداد اتصالات شبکه بالا است: {$connections}");
        }
    }
}
```

---

**نکته**: این راهنما به صورت مداوم به‌روزرسانی می‌شود. برای آخرین نسخه، به بخش مستندات بهینه‌سازی عملکرد مراجعه کنید.
