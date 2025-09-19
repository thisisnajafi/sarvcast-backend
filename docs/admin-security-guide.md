# راهنمای امنیت پنل مدیریت SarvCast

## فهرست مطالب

1. [معرفی امنیت](#معرفی-امنیت)
2. [احراز هویت و مجوزها](#احراز-هویت-و-مجوزها)
3. [محافظت از داده‌ها](#محافظت-از-داده‌ها)
4. [امنیت شبکه](#امنیت-شبکه)
5. [امنیت سرور](#امنیت-سرور)
6. [امنیت پایگاه داده](#امنیت-پایگاه-داده)
7. [امنیت فایل](#امنیت-فایل)
8. [نظارت و لاگ‌گیری](#نظارت-و-لاگ‌گیری)
9. [پشتیبان‌گیری امن](#پشتیبان‌گیری-امن)
10. [واکنش به حوادث](#واکنش-به-حوادث)

## معرفی امنیت

امنیت پنل مدیریت SarvCast شامل مجموعه‌ای از اقدامات و تدابیر امنیتی است که از سیستم، داده‌ها و کاربران در برابر تهدیدات مختلف محافظت می‌کند.

### اصول امنیت
- **محرمانگی (Confidentiality)**: دسترسی فقط به کاربران مجاز
- **یکپارچگی (Integrity)**: حفظ صحت و کامل بودن داده‌ها
- **در دسترس بودن (Availability)**: دسترسی مداوم به سیستم

### تهدیدات رایج
- **حملات SQL Injection**: تزریق کد مخرب به پایگاه داده
- **حملات XSS**: اجرای کد JavaScript مخرب
- **حملات CSRF**: جعل درخواست بین سایت‌ها
- **حملات Brute Force**: تلاش برای حدس رمز عبور
- **حملات DDoS**: حملات انکار سرویس

## احراز هویت و مجوزها

### مدیریت کاربران

#### ایجاد کاربر ادمین
```bash
php artisan tinker
>>> $admin = new User();
>>> $admin->name = 'Admin User';
>>> $admin->email = 'admin@sarvcast.com';
>>> $admin->password = bcrypt('StrongPassword123!');
>>> $admin->role = 'super_admin';
>>> $admin->status = 'active';
>>> $admin->email_verified_at = now();
>>> $admin->save();
```

#### بررسی نقش‌ها و مجوزها
```bash
php artisan tinker
>>> User::where('role', 'admin')->get();
>>> Role::with('permissions')->get();
>>> Permission::all();
```

### احراز هویت دو مرحله‌ای

#### فعال‌سازی 2FA
```php
// در فایل app/Http/Controllers/Auth/TwoFactorController.php
public function enableTwoFactor(Request $request)
{
    $user = auth()->user();
    
    // تولید کلید مخفی
    $secretKey = $this->generateSecretKey();
    
    // تولید کد QR
    $qrCode = $this->generateQRCode($user->email, $secretKey);
    
    // ذخیره کلید مخفی
    $user->two_factor_secret = encrypt($secretKey);
    $user->two_factor_enabled = false; // تا تایید فعال نشود
    $user->save();
    
    return view('auth.two-factor-setup', compact('qrCode'));
}
```

#### تایید کد 2FA
```php
public function verifyTwoFactor(Request $request)
{
    $request->validate([
        'code' => 'required|string|size:6'
    ]);
    
    $user = auth()->user();
    $secretKey = decrypt($user->two_factor_secret);
    
    if ($this->verifyCode($secretKey, $request->code)) {
        $user->two_factor_enabled = true;
        $user->save();
        
        return redirect()->route('admin.dashboard')
            ->with('success', 'احراز هویت دو مرحله‌ای فعال شد.');
    }
    
    return back()->withErrors(['code' => 'کد وارد شده نامعتبر است.']);
}
```

### مدیریت نشست‌ها

#### تنظیمات نشست
```php
// در فایل config/session.php
'lifetime' => 120, // 2 ساعت
'expire_on_close' => true,
'encrypt' => true,
'secure' => true, // فقط HTTPS
'http_only' => true,
'same_site' => 'strict',
```

#### نظارت بر نشست‌ها
```php
// در فایل app/Http/Controllers/Admin/SessionController.php
public function index()
{
    $sessions = DB::table('sessions')
        ->where('user_id', auth()->id())
        ->orderBy('last_activity', 'desc')
        ->get();
    
    return view('admin.sessions.index', compact('sessions'));
}

public function destroy($sessionId)
{
    DB::table('sessions')->where('id', $sessionId)->delete();
    
    return redirect()->back()
        ->with('success', 'نشست با موفقیت خاتمه یافت.');
}
```

## محافظت از داده‌ها

### رمزگذاری داده‌ها

#### رمزگذاری فیلدهای حساس
```php
// در فایل app/Models/User.php
protected $casts = [
    'two_factor_secret' => 'encrypted',
    'api_token' => 'encrypted',
    'backup_codes' => 'encrypted',
];

// در فایل app/Models/CoinTransaction.php
protected $casts = [
    'description' => 'encrypted',
    'metadata' => 'encrypted',
];
```

#### رمزگذاری فایل‌ها
```php
// در فایل app/Services/FileEncryptionService.php
class FileEncryptionService
{
    public function encryptFile($filePath, $key)
    {
        $data = file_get_contents($filePath);
        $encrypted = encrypt($data);
        
        file_put_contents($filePath . '.encrypted', $encrypted);
        unlink($filePath);
        
        return $filePath . '.encrypted';
    }
    
    public function decryptFile($filePath, $key)
    {
        $encrypted = file_get_contents($filePath);
        $data = decrypt($encrypted);
        
        $originalPath = str_replace('.encrypted', '', $filePath);
        file_put_contents($originalPath, $data);
        
        return $originalPath;
    }
}
```

### اعتبارسنجی داده‌ها

#### اعتبارسنجی ورودی‌ها
```php
// در فایل app/Http/Requests/Admin/CoinTransactionRequest.php
class CoinTransactionRequest extends FormRequest
{
    public function rules()
    {
        return [
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|integer|min:1|max:1000000',
            'type' => 'required|in:earned,purchased,gift,refund,admin_adjustment',
            'description' => 'nullable|string|max:500|regex:/^[a-zA-Z0-9\s\u0600-\u06FF]+$/',
        ];
    }
    
    public function messages()
    {
        return [
            'user_id.required' => 'فیلد کاربر الزامی است.',
            'amount.min' => 'مبلغ باید بزرگتر از 0 باشد.',
            'amount.max' => 'مبلغ نمی‌تواند بیش از 1,000,000 باشد.',
            'description.regex' => 'توضیحات فقط می‌تواند شامل حروف، اعداد و فاصله باشد.',
        ];
    }
}
```

#### پاکسازی خروجی‌ها
```php
// در فایل app/Http/Controllers/Admin/CoinController.php
public function show(CoinTransaction $coin)
{
    // پاکسازی داده‌ها قبل از نمایش
    $coin->description = e($coin->description);
    $coin->user->name = e($coin->user->name);
    
    return view('admin.coins.show', compact('coin'));
}
```

## امنیت شبکه

### تنظیمات HTTPS

#### گواهی SSL
```bash
# نصب Certbot
sudo apt-get install certbot python3-certbot-apache

# دریافت گواهی SSL
sudo certbot --apache -d admin.sarvcast.com

# تمدید خودکار
sudo crontab -e
0 12 * * * /usr/bin/certbot renew --quiet
```

#### تنظیمات Apache
```apache
# در فایل /etc/apache2/sites-available/admin.sarvcast.com.conf
<VirtualHost *:443>
    ServerName admin.sarvcast.com
    DocumentRoot /var/www/sarvcast/public
    
    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/admin.sarvcast.com/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/admin.sarvcast.com/privkey.pem
    
    # امنیت SSL
    SSLProtocol -all +TLSv1.2 +TLSv1.3
    SSLCipherSuite ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384
    SSLHonorCipherOrder on
    
    # Headers امنیتی
    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options DENY
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
    Header always set Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'"
</VirtualHost>
```

### فایروال

#### تنظیمات UFW
```bash
# فعال‌سازی فایروال
sudo ufw enable

# قوانین پایه
sudo ufw default deny incoming
sudo ufw default allow outgoing

# مجاز کردن پورت‌های ضروری
sudo ufw allow 22/tcp    # SSH
sudo ufw allow 80/tcp    # HTTP
sudo ufw allow 443/tcp   # HTTPS

# مجاز کردن IP های خاص
sudo ufw allow from 192.168.1.0/24 to any port 22
sudo ufw allow from 10.0.0.0/8 to any port 22

# بررسی وضعیت
sudo ufw status verbose
```

#### تنظیمات iptables
```bash
# پاک کردن قوانین موجود
sudo iptables -F
sudo iptables -X

# قوانین پایه
sudo iptables -P INPUT DROP
sudo iptables -P FORWARD DROP
sudo iptables -P OUTPUT ACCEPT

# مجاز کردن loopback
sudo iptables -A INPUT -i lo -j ACCEPT

# مجاز کردن اتصالات موجود
sudo iptables -A INPUT -m state --state ESTABLISHED,RELATED -j ACCEPT

# مجاز کردن پورت‌های ضروری
sudo iptables -A INPUT -p tcp --dport 22 -j ACCEPT
sudo iptables -A INPUT -p tcp --dport 80 -j ACCEPT
sudo iptables -A INPUT -p tcp --dport 443 -j ACCEPT

# محدود کردن تلاش‌های SSH
sudo iptables -A INPUT -p tcp --dport 22 -m limit --limit 3/min --limit-burst 3 -j ACCEPT

# ذخیره قوانین
sudo iptables-save > /etc/iptables/rules.v4
```

## امنیت سرور

### به‌روزرسانی سیستم

#### به‌روزرسانی Ubuntu/Debian
```bash
# به‌روزرسانی لیست پکیج‌ها
sudo apt update

# به‌روزرسانی پکیج‌ها
sudo apt upgrade -y

# حذف پکیج‌های غیرضروری
sudo apt autoremove -y
sudo apt autoclean
```

#### به‌روزرسانی خودکار
```bash
# نصب unattended-upgrades
sudo apt install unattended-upgrades

# تنظیم به‌روزرسانی خودکار
sudo dpkg-reconfigure unattended-upgrades

# بررسی وضعیت
sudo unattended-upgrades --dry-run
```

### مدیریت کاربران

#### ایجاد کاربر ادمین
```bash
# ایجاد کاربر جدید
sudo adduser adminuser

# افزودن به گروه sudo
sudo usermod -aG sudo adminuser

# غیرفعال کردن ورود root
sudo passwd -l root
```

#### تنظیمات SSH
```bash
# ویرایش فایل SSH
sudo nano /etc/ssh/sshd_config

# تنظیمات امنیتی
Port 2222
PermitRootLogin no
PasswordAuthentication no
PubkeyAuthentication yes
MaxAuthTries 3
ClientAliveInterval 300
ClientAliveCountMax 2
```

### نظارت بر سیستم

#### نصب و تنظیم Fail2ban
```bash
# نصب Fail2ban
sudo apt install fail2ban

# تنظیمات Fail2ban
sudo nano /etc/fail2ban/jail.local

[DEFAULT]
bantime = 3600
findtime = 600
maxretry = 3

[sshd]
enabled = true
port = ssh
logpath = /var/log/auth.log
maxretry = 3

[apache-auth]
enabled = true
port = http,https
logpath = /var/log/apache2/error.log
maxretry = 3

# راه‌اندازی سرویس
sudo systemctl enable fail2ban
sudo systemctl start fail2ban
```

## امنیت پایگاه داده

### تنظیمات MySQL

#### ایجاد کاربر امن
```sql
-- ایجاد کاربر جدید
CREATE USER 'sarvcast_admin'@'localhost' IDENTIFIED BY 'StrongPassword123!';

-- اعطای مجوزها
GRANT SELECT, INSERT, UPDATE, DELETE ON sarvcast.* TO 'sarvcast_admin'@'localhost';

-- اعطای مجوزهای خاص
GRANT CREATE, DROP, ALTER ON sarvcast.* TO 'sarvcast_admin'@'localhost';

-- اعمال تغییرات
FLUSH PRIVILEGES;
```

#### تنظیمات امنیتی MySQL
```sql
-- اجرای اسکریپت امنیتی
mysql_secure_installation

-- تنظیمات اضافی
SET GLOBAL local_infile = 0;
SET GLOBAL sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO';
```

### رمزگذاری پایگاه داده

#### رمزگذاری در سطح جدول
```sql
-- رمزگذاری جدول حساس
ALTER TABLE users ENCRYPTION = 'Y';

-- رمزگذاری ستون‌های خاص
ALTER TABLE coin_transactions MODIFY COLUMN description VARBINARY(500);
```

#### پشتیبان‌گیری رمزگذاری شده
```bash
# پشتیبان‌گیری با رمزگذاری
mysqldump -u root -p sarvcast | gpg --cipher-algo AES256 --compress-algo 1 --symmetric --output sarvcast_backup_$(date +%Y%m%d).sql.gpg
```

## امنیت فایل

### مجوزهای فایل

#### تنظیم مجوزهای صحیح
```bash
# مجوزهای فایل‌های Laravel
find /var/www/sarvcast -type f -exec chmod 644 {} \;
find /var/www/sarvcast -type d -exec chmod 755 {} \;

# مجوزهای خاص
chmod 600 /var/www/sarvcast/.env
chmod 755 /var/www/sarvcast/storage
chmod 755 /var/www/sarvcast/bootstrap/cache
```

#### مالکیت فایل‌ها
```bash
# تنظیم مالکیت
chown -R www-data:www-data /var/www/sarvcast
chown -R www-data:www-data /var/www/sarvcast/storage
chown -R www-data:www-data /var/www/sarvcast/bootstrap/cache
```

### اسکن فایل‌ها

#### اسکن ویروس
```bash
# نصب ClamAV
sudo apt install clamav clamav-daemon

# به‌روزرسانی پایگاه داده
sudo freshclam

# اسکن فایل‌ها
sudo clamscan -r /var/www/sarvcast
```

#### بررسی یکپارچگی فایل‌ها
```bash
# ایجاد checksum
find /var/www/sarvcast -type f -exec md5sum {} \; > /var/log/sarvcast_checksums.md5

# بررسی یکپارچگی
md5sum -c /var/log/sarvcast_checksums.md5
```

## نظارت و لاگ‌گیری

### لاگ‌گیری Laravel

#### تنظیمات لاگ
```php
// در فایل config/logging.php
'channels' => [
    'admin' => [
        'driver' => 'daily',
        'path' => storage_path('logs/admin.log'),
        'level' => 'info',
        'days' => 30,
    ],
    'security' => [
        'driver' => 'daily',
        'path' => storage_path('logs/security.log'),
        'level' => 'warning',
        'days' => 90,
    ],
],
```

#### لاگ‌گیری فعالیت‌های ادمین
```php
// در فایل app/Http/Middleware/AdminMiddleware.php
private function logAdminActivity(Request $request, $user)
{
    $logData = [
        'user_id' => $user->id,
        'user_email' => $user->email,
        'ip_address' => $request->ip(),
        'user_agent' => $request->userAgent(),
        'method' => $request->method(),
        'url' => $request->fullUrl(),
        'route_name' => $request->route()->getName(),
        'timestamp' => now(),
    ];

    \Log::channel('admin')->info('Admin Activity', $logData);
    \Log::channel('security')->warning('Admin Access', $logData);
}
```

### نظارت بر سیستم

#### نصب و تنظیم OSSEC
```bash
# نصب OSSEC
wget -q -O - https://updates.atomicorp.com/installers/atomic | sudo bash
sudo /opt/atomic/bin/atomic-enterprise-installer --install

# تنظیمات OSSEC
sudo nano /var/ossec/etc/ossec.conf
```

#### نظارت بر لاگ‌ها
```bash
# نصب Logwatch
sudo apt install logwatch

# تنظیمات Logwatch
sudo nano /etc/logwatch/conf/logwatch.conf

# اجرای روزانه
sudo logwatch --detail high --mailto admin@sarvcast.com
```

## پشتیبان‌گیری امن

### پشتیبان‌گیری خودکار

#### اسکریپت پشتیبان‌گیری
```bash
#!/bin/bash
# فایل: /opt/scripts/backup_sarvcast.sh

DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/opt/backups"
DB_NAME="sarvcast"
DB_USER="sarvcast_admin"
DB_PASS="StrongPassword123!"

# ایجاد پوشه پشتیبان‌گیری
mkdir -p $BACKUP_DIR

# پشتیبان‌گیری پایگاه داده
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME | gzip > $BACKUP_DIR/db_$DATE.sql.gz

# پشتیبان‌گیری فایل‌ها
tar -czf $BACKUP_DIR/files_$DATE.tar.gz /var/www/sarvcast

# رمزگذاری پشتیبان‌گیری
gpg --cipher-algo AES256 --compress-algo 1 --symmetric --output $BACKUP_DIR/backup_$DATE.tar.gz.gpg $BACKUP_DIR/files_$DATE.tar.gz

# حذف فایل‌های غیررمزگذاری شده
rm $BACKUP_DIR/files_$DATE.tar.gz

# حذف پشتیبان‌گیری‌های قدیمی (بیش از 30 روز)
find $BACKUP_DIR -name "*.gz" -mtime +30 -delete
find $BACKUP_DIR -name "*.gpg" -mtime +30 -delete

echo "پشتیبان‌گیری با موفقیت انجام شد: $DATE"
```

#### تنظیم Cron Job
```bash
# ویرایش crontab
sudo crontab -e

# اجرای روزانه در ساعت 2 صبح
0 2 * * * /opt/scripts/backup_sarvcast.sh >> /var/log/backup.log 2>&1
```

### بازیابی

#### اسکریپت بازیابی
```bash
#!/bin/bash
# فایل: /opt/scripts/restore_sarvcast.sh

BACKUP_FILE=$1
DB_NAME="sarvcast"
DB_USER="sarvcast_admin"
DB_PASS="StrongPassword123!"

if [ -z "$BACKUP_FILE" ]; then
    echo "لطفاً نام فایل پشتیبان‌گیری را وارد کنید"
    exit 1
fi

# رمزگشایی فایل
gpg --output /tmp/restore.tar.gz --decrypt $BACKUP_FILE

# استخراج فایل‌ها
tar -xzf /tmp/restore.tar.gz -C /

# بازیابی پایگاه داده
gunzip -c $BACKUP_DIR/db_$DATE.sql.gz | mysql -u $DB_USER -p$DB_PASS $DB_NAME

# پاک کردن فایل موقت
rm /tmp/restore.tar.gz

echo "بازیابی با موفقیت انجام شد"
```

## واکنش به حوادث

### تشخیص حوادث امنیتی

#### مانیتورینگ لاگ‌ها
```bash
# اسکریپت تشخیص حملات
#!/bin/bash

# بررسی تلاش‌های ورود ناموفق
grep "Failed password" /var/log/auth.log | tail -20

# بررسی درخواست‌های مشکوک
grep "404" /var/log/apache2/access.log | grep -E "(admin|wp-admin|phpmyadmin)" | tail -20

# بررسی درخواست‌های غیرعادی
grep "GET /admin" /var/log/apache2/access.log | awk '{print $1}' | sort | uniq -c | sort -nr | head -10
```

#### هشدارهای امنیتی
```php
// در فایل app/Services/SecurityAlertService.php
class SecurityAlertService
{
    public function checkSuspiciousActivity()
    {
        // بررسی تلاش‌های ورود ناموفق
        $failedLogins = DB::table('admin_activity_logs')
            ->where('method', 'POST')
            ->where('url', 'like', '%login%')
            ->where('created_at', '>=', now()->subHour())
            ->count();
        
        if ($failedLogins > 10) {
            $this->sendSecurityAlert('Multiple failed login attempts detected');
        }
        
        // بررسی درخواست‌های غیرعادی
        $suspiciousRequests = DB::table('admin_activity_logs')
            ->where('url', 'like', '%../%')
            ->orWhere('url', 'like', '%<script%')
            ->where('created_at', '>=', now()->subHour())
            ->count();
        
        if ($suspiciousRequests > 0) {
            $this->sendSecurityAlert('Suspicious requests detected');
        }
    }
    
    private function sendSecurityAlert($message)
    {
        // ارسال ایمیل هشدار
        Mail::to('security@sarvcast.com')->send(new SecurityAlertMail($message));
        
        // ارسال پیامک
        // SMS::send('09123456789', $message);
        
        // لاگ‌گیری
        \Log::channel('security')->critical($message);
    }
}
```

### پاسخ به حوادث

#### مراحل پاسخ
1. **تشخیص**: شناسایی و تایید حادثه
2. **جداسازی**: قطع دسترسی مهاجم
3. **ارزیابی**: بررسی میزان خسارت
4. **بازیابی**: بازگردانی سیستم
5. **یادگیری**: تحلیل و بهبود

#### اسکریپت پاسخ اضطراری
```bash
#!/bin/bash
# فایل: /opt/scripts/emergency_response.sh

echo "شروع پاسخ اضطراری به حادثه امنیتی..."

# قطع دسترسی‌های مشکوک
sudo iptables -A INPUT -s $SUSPICIOUS_IP -j DROP

# تغییر رمزهای عبور
sudo passwd adminuser

# غیرفعال کردن سرویس‌های غیرضروری
sudo systemctl stop apache2
sudo systemctl stop mysql

# بررسی یکپارچگی فایل‌ها
find /var/www/sarvcast -type f -exec md5sum {} \; > /tmp/current_checksums.md5
diff /var/log/sarvcast_checksums.md5 /tmp/current_checksums.md5

# بازیابی از پشتیبان‌گیری
/opt/scripts/restore_sarvcast.sh /opt/backups/backup_latest.tar.gz.gpg

# راه‌اندازی مجدد سرویس‌ها
sudo systemctl start mysql
sudo systemctl start apache2

echo "پاسخ اضطراری تکمیل شد"
```

---

**نکته**: این راهنما به صورت مداوم به‌روزرسانی می‌شود. برای آخرین نسخه، به بخش مستندات امنیت مراجعه کنید.
