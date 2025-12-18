# راهنمای عیب‌یابی پنل مدیریت SarvCast

## فهرست مطالب

1. [مشکلات رایج](#مشکلات-رایج)
2. [مشکلات احراز هویت](#مشکلات-احراز-هویت)
3. [مشکلات عملکرد](#مشکلات-عملکرد)
4. [مشکلات امنیتی](#مشکلات-امنیتی)
5. [مشکلات پایگاه داده](#مشکلات-پایگاه-داده)
6. [مشکلات فایل](#مشکلات-فایل)
7. [مشکلات شبکه](#مشکلات-شبکه)
8. [مشکلات مرورگر](#مشکلات-مرورگر)
9. [لاگ‌ها و تشخیص](#لاگ‌ها-و-تشخیص)
10. [تماس با پشتیبانی](#تماس-با-پشتیبانی)

## مشکلات رایج

### صفحه سفید (White Screen)

**علائم:**
- صفحه کاملاً سفید نمایش داده می‌شود
- هیچ محتوایی نمایش داده نمی‌شود

**علل احتمالی:**
- خطای PHP
- مشکل در فایل‌های CSS/JS
- مشکل در پایگاه داده

**راه‌حل:**
1. بررسی لاگ‌های خطا:
```bash
tail -f storage/logs/laravel.log
```

2. فعال‌سازی نمایش خطاها:
```php
// در فایل .env
APP_DEBUG=true
```

3. پاک کردن کش:
```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

### خطای 500 (Internal Server Error)

**علائم:**
- پیام "خطای داخلی سرور" نمایش داده می‌شود
- کد خطای 500 در کنسول مرورگر

**راه‌حل:**
1. بررسی لاگ‌های خطا:
```bash
tail -f storage/logs/laravel.log
```

2. بررسی مجوزهای فایل:
```bash
chmod -R 755 storage/
chmod -R 755 bootstrap/cache/
```

3. بررسی اتصال پایگاه داده:
```bash
php artisan migrate:status
```

### خطای 404 (Not Found)

**علائم:**
- پیام "صفحه یافت نشد" نمایش داده می‌شود
- کد خطای 404 در کنسول مرورگر

**راه‌حل:**
1. بررسی مسیرهای تعریف شده:
```bash
php artisan route:list
```

2. پاک کردن کش مسیرها:
```bash
php artisan route:clear
```

3. بررسی فایل .htaccess:
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
```

## مشکلات احراز هویت

### عدم امکان ورود

**علائم:**
- پیام "اطلاعات ورود نادرست" نمایش داده می‌شود
- عدم امکان ورود به پنل مدیریت

**راه‌حل:**
1. بررسی اطلاعات ورود:
```bash
php artisan tinker
>>> User::where('email', 'admin@example.com')->first()
```

2. بازنشانی رمز عبور:
```bash
php artisan tinker
>>> $user = User::where('email', 'admin@example.com')->first()
>>> $user->password = bcrypt('newpassword')
>>> $user->save()
```

3. بررسی وضعیت حساب:
```bash
php artisan tinker
>>> User::where('email', 'admin@example.com')->first()->status
```

### خطای "دسترسی غیرمجاز"

**علائم:**
- پیام "دسترسی غیرمجاز" نمایش داده می‌شود
- عدم امکان دسترسی به بخش‌های خاص

**راه‌حل:**
1. بررسی نقش کاربر:
```bash
php artisan tinker
>>> User::where('email', 'admin@example.com')->first()->role
```

2. بررسی مجوزهای نقش:
```bash
php artisan tinker
>>> Role::where('name', 'admin')->first()->permissions
```

3. بررسی میدل‌ویر:
```php
// در فایل routes/web.php
Route::middleware(['auth', 'admin'])->group(function () {
    // مسیرهای ادمین
});
```

## مشکلات عملکرد

### کندی بارگذاری صفحات

**علائم:**
- صفحات به کندی بارگذاری می‌شوند
- زمان پاسخ‌دهی بالا

**راه‌حل:**
1. فعال‌سازی کش:
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

2. بهینه‌سازی پایگاه داده:
```bash
php artisan migrate:status
php artisan db:seed
```

3. بررسی استفاده از حافظه:
```bash
php artisan tinker
>>> memory_get_usage(true)
```

### خطای حافظه (Memory Error)

**علائم:**
- پیام "Fatal error: Allowed memory size exhausted"
- قطع شدن اجرای اسکریپت

**راه‌حل:**
1. افزایش حافظه PHP:
```ini
; در فایل php.ini
memory_limit = 512M
```

2. بهینه‌سازی کوئری‌ها:
```php
// استفاده از pagination
$users = User::paginate(20);

// استفاده از eager loading
$users = User::with('transactions')->get();
```

3. بررسی استفاده از حافظه:
```php
echo memory_get_usage(true) / 1024 / 1024 . ' MB';
```

## مشکلات امنیتی

### خطای CSRF

**علائم:**
- پیام "CSRF token mismatch" نمایش داده می‌شود
- عدم امکان ارسال فرم‌ها

**راه‌حل:**
1. بررسی توکن CSRF:
```html
<meta name="csrf-token" content="{{ csrf_token() }}">
```

2. افزودن توکن به درخواست‌های AJAX:
```javascript
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});
```

3. بررسی میدل‌ویر CSRF:
```php
// در فایل app/Http/Kernel.php
protected $middleware = [
    \App\Http\Middleware\VerifyCsrfToken::class,
];
```

### خطای Rate Limiting

**علائم:**
- پیام "تعداد درخواست‌های شما بیش از حد مجاز است"
- کد خطای 429

**راه‌حل:**
1. بررسی تنظیمات Rate Limiting:
```php
// در فایل app/Http/Middleware/AdminMiddleware.php
$maxAttempts = 100; // تعداد درخواست‌های مجاز
$decayMinutes = 1; // مدت زمان محدودیت
```

2. پاک کردن کش Rate Limiting:
```bash
php artisan cache:clear
```

3. بررسی IP کاربر:
```bash
php artisan tinker
>>> request()->ip()
```

## مشکلات پایگاه داده

### خطای اتصال پایگاه داده

**علائم:**
- پیام "Connection refused" یا "Access denied"
- عدم امکان اتصال به پایگاه داده

**راه‌حل:**
1. بررسی تنظیمات پایگاه داده:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sarvcast
DB_USERNAME=root
DB_PASSWORD=
```

2. بررسی اتصال:
```bash
php artisan migrate:status
```

3. بررسی مجوزهای پایگاه داده:
```sql
GRANT ALL PRIVILEGES ON sarvcast.* TO 'username'@'localhost';
FLUSH PRIVILEGES;
```

### خطای Migration

**علائم:**
- پیام "Table already exists" یا "Column already exists"
- عدم امکان اجرای Migration

**راه‌حل:**
1. بررسی وضعیت Migration:
```bash
php artisan migrate:status
```

2. بازگردانی Migration:
```bash
php artisan migrate:rollback
```

3. اجرای مجدد Migration:
```bash
php artisan migrate
```

4. پاک کردن جدول و اجرای مجدد:
```bash
php artisan migrate:fresh
```

## مشکلات فایل

### خطای آپلود فایل

**علائم:**
- پیام "File upload failed" یا "File too large"
- عدم امکان آپلود فایل

**راه‌حل:**
1. بررسی تنظیمات PHP:
```ini
; در فایل php.ini
upload_max_filesize = 100M
post_max_size = 100M
max_execution_time = 300
```

2. بررسی مجوزهای پوشه:
```bash
chmod -R 755 storage/app/public/
chmod -R 755 public/uploads/
```

3. بررسی تنظیمات Laravel:
```php
// در فایل config/filesystems.php
'disks' => [
    'public' => [
        'driver' => 'local',
        'root' => storage_path('app/public'),
        'url' => env('APP_URL').'/storage',
        'visibility' => 'public',
    ],
],
```

### خطای دسترسی به فایل

**علائم:**
- پیام "Permission denied" یا "File not found"
- عدم امکان دسترسی به فایل‌ها

**راه‌حل:**
1. بررسی مجوزهای فایل:
```bash
ls -la storage/
ls -la public/
```

2. تنظیم مجوزهای صحیح:
```bash
chown -R www-data:www-data storage/
chown -R www-data:www-data public/
chmod -R 755 storage/
chmod -R 755 public/
```

3. بررسی لینک نمادین:
```bash
php artisan storage:link
```

## مشکلات شبکه

### خطای اتصال به API

**علائم:**
- پیام "Connection timeout" یا "Network error"
- عدم امکان اتصال به API خارجی

**راه‌حل:**
1. بررسی تنظیمات cURL:
```php
// در فایل config/app.php
'curl' => [
    'timeout' => 30,
    'verify' => false,
],
```

2. بررسی فایروال:
```bash
sudo ufw status
sudo ufw allow 80
sudo ufw allow 443
```

3. بررسی DNS:
```bash
nslookup api.example.com
ping api.example.com
```

### خطای SSL

**علائم:**
- پیام "SSL certificate error" یا "Certificate verify failed"
- عدم امکان اتصال HTTPS

**راه‌حل:**
1. بررسی گواهی SSL:
```bash
openssl s_client -connect example.com:443
```

2. به‌روزرسانی گواهی:
```bash
sudo certbot renew
```

3. بررسی تنظیمات Apache/Nginx:
```apache
# Apache
SSLEngine on
SSLCertificateFile /path/to/certificate.crt
SSLCertificateKeyFile /path/to/private.key
```

## مشکلات مرورگر

### خطای JavaScript

**علائم:**
- پیام "JavaScript error" در کنسول مرورگر
- عدم عملکرد صحیح JavaScript

**راه‌حل:**
1. بررسی کنسول مرورگر:
```javascript
console.log('Debug message');
console.error('Error message');
```

2. بررسی فایل‌های JavaScript:
```bash
ls -la public/js/
```

3. پاک کردن کش مرورگر:
- Ctrl+Shift+R (Windows/Linux)
- Cmd+Shift+R (Mac)

### خطای CSS

**علائم:**
- استایل‌های نادرست نمایش داده می‌شوند
- عدم بارگذاری CSS

**راه‌حل:**
1. بررسی فایل‌های CSS:
```bash
ls -la public/css/
```

2. پاک کردن کش CSS:
```bash
php artisan view:clear
```

3. بررسی مسیرهای CSS:
```html
<link rel="stylesheet" href="{{ asset('css/app.css') }}">
```

## لاگ‌ها و تشخیص

### بررسی لاگ‌های Laravel

```bash
# مشاهده لاگ‌های اخیر
tail -f storage/logs/laravel.log

# جستجو در لاگ‌ها
grep "ERROR" storage/logs/laravel.log
grep "Exception" storage/logs/laravel.log

# پاک کردن لاگ‌ها
> storage/logs/laravel.log
```

### بررسی لاگ‌های Apache/Nginx

```bash
# Apache
tail -f /var/log/apache2/error.log
tail -f /var/log/apache2/access.log

# Nginx
tail -f /var/log/nginx/error.log
tail -f /var/log/nginx/access.log
```

### بررسی لاگ‌های پایگاه داده

```bash
# MySQL
tail -f /var/log/mysql/error.log
tail -f /var/log/mysql/mysql.log

# PostgreSQL
tail -f /var/log/postgresql/postgresql.log
```

## تماس با پشتیبانی

### اطلاعات مورد نیاز

هنگام تماس با پشتیبانی، لطفاً اطلاعات زیر را آماده کنید:

1. **نسخه سیستم:**
```bash
php -v
composer show laravel/framework
```

2. **لاگ‌های خطا:**
```bash
tail -n 100 storage/logs/laravel.log
```

3. **تنظیمات محیط:**
```bash
cat .env | grep -E "(APP_|DB_|MAIL_)"
```

4. **وضعیت Migration:**
```bash
php artisan migrate:status
```

5. **وضعیت Route:**
```bash
php artisan route:list | grep admin
```

### کانال‌های پشتیبانی

- **ایمیل**: support@sarvcast.com
- **تلفن**: 021-12345678
- **تلگرام**: @SarvCastSupport
- **تیکت سیستم**: https://support.sarvcast.com

### ساعات کاری پشتیبانی

- **شنبه تا چهارشنبه**: 9:00 - 18:00
- **پنج‌شنبه**: 9:00 - 14:00
- **جمعه**: تعطیل

---

**نکته**: این راهنما به صورت مداوم به‌روزرسانی می‌شود. برای آخرین نسخه، به بخش مستندات مراجعه کنید.
