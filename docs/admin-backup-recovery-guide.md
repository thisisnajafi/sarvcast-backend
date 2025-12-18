# راهنمای پشتیبان‌گیری و بازیابی پنل مدیریت SarvCast

## فهرست مطالب

1. [معرفی پشتیبان‌گیری](#معرفی-پشتیبان‌گیری)
2. [انواع پشتیبان‌گیری](#انواع-پشتیبان‌گیری)
3. [پشتیبان‌گیری خودکار](#پشتیبان‌گیری-خودکار)
4. [پشتیبان‌گیری دستی](#پشتیبان‌گیری-دستی)
5. [رمزگذاری پشتیبان‌گیری](#رمزگذاری-پشتیبان‌گیری)
6. [ذخیره‌سازی ابری](#ذخیره‌سازی-ابری)
7. [بازیابی داده‌ها](#بازیابی-داده‌ها)
8. [تست پشتیبان‌گیری](#تست-پشتیبان‌گیری)
9. [نظارت بر پشتیبان‌گیری](#نظارت-بر-پشتیبان‌گیری)
10. [بهینه‌سازی پشتیبان‌گیری](#بهینه‌سازی-پشتیبان‌گیری)

## معرفی پشتیبان‌گیری

پشتیبان‌گیری منظم و امن یکی از مهم‌ترین جنبه‌های مدیریت سیستم است که از دست رفتن داده‌ها جلوگیری می‌کند.

### اصول پشتیبان‌گیری
- **منظم**: پشتیبان‌گیری در فواصل زمانی مشخص
- **کامل**: شامل تمام داده‌ها و فایل‌ها
- **امن**: رمزگذاری شده و محافظت شده
- **قابل بازیابی**: تست شده و قابل اعتماد
- **مستند**: مستندسازی کامل فرآیند

### انواع داده‌ها
- **پایگاه داده**: جداول، داده‌ها، ساختار
- **فایل‌ها**: تصاویر، صداها، اسناد
- **تنظیمات**: فایل‌های پیکربندی
- **کد**: فایل‌های منبع
- **لاگ‌ها**: فایل‌های لاگ سیستم

## انواع پشتیبان‌گیری

### پشتیبان‌گیری کامل (Full Backup)
```bash
#!/bin/bash
# فایل: /opt/scripts/full_backup.sh

DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/opt/backups/full"
DB_NAME="sarvcast"
DB_USER="sarvcast_admin"
DB_PASS="StrongPassword123!"

# ایجاد پوشه پشتیبان‌گیری
mkdir -p $BACKUP_DIR

# پشتیبان‌گیری پایگاه داده
echo "شروع پشتیبان‌گیری پایگاه داده..."
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME > $BACKUP_DIR/db_full_$DATE.sql

# فشرده‌سازی پایگاه داده
gzip $BACKUP_DIR/db_full_$DATE.sql

# پشتیبان‌گیری فایل‌ها
echo "شروع پشتیبان‌گیری فایل‌ها..."
tar -czf $BACKUP_DIR/files_full_$DATE.tar.gz /var/www/sarvcast

# پشتیبان‌گیری تنظیمات
echo "شروع پشتیبان‌گیری تنظیمات..."
tar -czf $BACKUP_DIR/config_full_$DATE.tar.gz /etc/apache2 /etc/mysql

# رمزگذاری پشتیبان‌گیری
echo "رمزگذاری پشتیبان‌گیری..."
gpg --cipher-algo AES256 --compress-algo 1 --symmetric --output $BACKUP_DIR/backup_full_$DATE.tar.gz.gpg $BACKUP_DIR/files_full_$DATE.tar.gz

# حذف فایل‌های غیررمزگذاری شده
rm $BACKUP_DIR/files_full_$DATE.tar.gz

echo "پشتیبان‌گیری کامل با موفقیت انجام شد: $DATE"
```

### پشتیبان‌گیری تفاضلی (Differential Backup)
```bash
#!/bin/bash
# فایل: /opt/scripts/differential_backup.sh

DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/opt/backups/differential"
FULL_BACKUP_DIR="/opt/backups/full"
DB_NAME="sarvcast"
DB_USER="sarvcast_admin"
DB_PASS="StrongPassword123!"

# یافتن آخرین پشتیبان‌گیری کامل
LAST_FULL=$(ls -t $FULL_BACKUP_DIR/backup_full_*.tar.gz.gpg | head -1)
LAST_FULL_DATE=$(basename $LAST_FULL | sed 's/backup_full_\(.*\)\.tar\.gz\.gpg/\1/')

# ایجاد پوشه پشتیبان‌گیری
mkdir -p $BACKUP_DIR

# پشتیبان‌گیری تفاضلی پایگاه داده
echo "شروع پشتیبان‌گیری تفاضلی پایگاه داده..."
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME --where="updated_at > '$LAST_FULL_DATE'" > $BACKUP_DIR/db_diff_$DATE.sql

# فشرده‌سازی پایگاه داده
gzip $BACKUP_DIR/db_diff_$DATE.sql

# پشتیبان‌گیری تفاضلی فایل‌ها
echo "شروع پشتیبان‌گیری تفاضلی فایل‌ها..."
find /var/www/sarvcast -newer $LAST_FULL -type f -print0 | tar -czf $BACKUP_DIR/files_diff_$DATE.tar.gz --null -T -

# رمزگذاری پشتیبان‌گیری
echo "رمزگذاری پشتیبان‌گیری تفاضلی..."
gpg --cipher-algo AES256 --compress-algo 1 --symmetric --output $BACKUP_DIR/backup_diff_$DATE.tar.gz.gpg $BACKUP_DIR/files_diff_$DATE.tar.gz

# حذف فایل‌های غیررمزگذاری شده
rm $BACKUP_DIR/files_diff_$DATE.tar.gz

echo "پشتیبان‌گیری تفاضلی با موفقیت انجام شد: $DATE"
```

### پشتیبان‌گیری افزایشی (Incremental Backup)
```bash
#!/bin/bash
# فایل: /opt/scripts/incremental_backup.sh

DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/opt/backups/incremental"
DB_NAME="sarvcast"
DB_USER="sarvcast_admin"
DB_PASS="StrongPassword123!"

# یافتن آخرین پشتیبان‌گیری
LAST_BACKUP=$(ls -t $BACKUP_DIR/backup_inc_*.tar.gz.gpg 2>/dev/null | head -1)

# ایجاد پوشه پشتیبان‌گیری
mkdir -p $BACKUP_DIR

# پشتیبان‌گیری افزایشی پایگاه داده
echo "شروع پشتیبان‌گیری افزایشی پایگاه داده..."
if [ -n "$LAST_BACKUP" ]; then
    LAST_BACKUP_DATE=$(basename $LAST_BACKUP | sed 's/backup_inc_\(.*\)\.tar\.gz\.gpg/\1/')
    mysqldump -u $DB_USER -p$DB_PASS $DB_NAME --where="updated_at > '$LAST_BACKUP_DATE'" > $BACKUP_DIR/db_inc_$DATE.sql
else
    mysqldump -u $DB_USER -p$DB_PASS $DB_NAME > $BACKUP_DIR/db_inc_$DATE.sql
fi

# فشرده‌سازی پایگاه داده
gzip $BACKUP_DIR/db_inc_$DATE.sql

# پشتیبان‌گیری افزایشی فایل‌ها
echo "شروع پشتیبان‌گیری افزایشی فایل‌ها..."
if [ -n "$LAST_BACKUP" ]; then
    find /var/www/sarvcast -newer $LAST_BACKUP -type f -print0 | tar -czf $BACKUP_DIR/files_inc_$DATE.tar.gz --null -T -
else
    tar -czf $BACKUP_DIR/files_inc_$DATE.tar.gz /var/www/sarvcast
fi

# رمزگذاری پشتیبان‌گیری
echo "رمزگذاری پشتیبان‌گیری افزایشی..."
gpg --cipher-algo AES256 --compress-algo 1 --symmetric --output $BACKUP_DIR/backup_inc_$DATE.tar.gz.gpg $BACKUP_DIR/files_inc_$DATE.tar.gz

# حذف فایل‌های غیررمزگذاری شده
rm $BACKUP_DIR/files_inc_$DATE.tar.gz

echo "پشتیبان‌گیری افزایشی با موفقیت انجام شد: $DATE"
```

## پشتیبان‌گیری خودکار

### تنظیم Cron Jobs
```bash
# ویرایش crontab
sudo crontab -e

# پشتیبان‌گیری کامل هفتگی (یکشنبه ساعت 2 صبح)
0 2 * * 0 /opt/scripts/full_backup.sh >> /var/log/backup_full.log 2>&1

# پشتیبان‌گیری تفاضلی روزانه (روزهای دوشنبه تا جمعه ساعت 3 صبح)
0 3 * * 1-5 /opt/scripts/differential_backup.sh >> /var/log/backup_diff.log 2>&1

# پشتیبان‌گیری افزایشی ساعتی (ساعت 4 صبح تا 11 شب)
0 4-23 * * * /opt/scripts/incremental_backup.sh >> /var/log/backup_inc.log 2>&1

# پاک کردن پشتیبان‌گیری‌های قدیمی (روزانه ساعت 1 صبح)
0 1 * * * /opt/scripts/cleanup_old_backups.sh >> /var/log/backup_cleanup.log 2>&1
```

### اسکریپت پاک‌سازی
```bash
#!/bin/bash
# فایل: /opt/scripts/cleanup_old_backups.sh

BACKUP_DIR="/opt/backups"
RETENTION_DAYS=30

echo "شروع پاک‌سازی پشتیبان‌گیری‌های قدیمی..."

# پاک کردن پشتیبان‌گیری‌های کامل قدیمی (بیش از 30 روز)
find $BACKUP_DIR/full -name "*.gpg" -mtime +$RETENTION_DAYS -delete

# پاک کردن پشتیبان‌گیری‌های تفاضلی قدیمی (بیش از 7 روز)
find $BACKUP_DIR/differential -name "*.gpg" -mtime +7 -delete

# پاک کردن پشتیبان‌گیری‌های افزایشی قدیمی (بیش از 3 روز)
find $BACKUP_DIR/incremental -name "*.gpg" -mtime +3 -delete

echo "پاک‌سازی پشتیبان‌گیری‌های قدیمی تکمیل شد"
```

## پشتیبان‌گیری دستی

### پشتیبان‌گیری از طریق Laravel Artisan
```php
// در فایل app/Console/Commands/BackupCommand.php
class BackupCommand extends Command
{
    protected $signature = 'backup:create {type=full} {--encrypt}';
    protected $description = 'ایجاد پشتیبان‌گیری';

    public function handle()
    {
        $type = $this->argument('type');
        $encrypt = $this->option('encrypt');
        
        $this->info("شروع پشتیبان‌گیری $type...");
        
        switch ($type) {
            case 'full':
                $this->createFullBackup($encrypt);
                break;
            case 'differential':
                $this->createDifferentialBackup($encrypt);
                break;
            case 'incremental':
                $this->createIncrementalBackup($encrypt);
                break;
            default:
                $this->error('نوع پشتیبان‌گیری نامعتبر است');
                return 1;
        }
        
        $this->info("پشتیبان‌گیری $type با موفقیت انجام شد");
        return 0;
    }
    
    private function createFullBackup($encrypt)
    {
        $date = now()->format('Ymd_His');
        $backupDir = storage_path('backups/full');
        
        // ایجاد پوشه
        if (!file_exists($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        
        // پشتیبان‌گیری پایگاه داده
        $dbFile = $backupDir . "/db_full_$date.sql";
        $this->backupDatabase($dbFile);
        
        // پشتیبان‌گیری فایل‌ها
        $filesFile = $backupDir . "/files_full_$date.tar.gz";
        $this->backupFiles($filesFile);
        
        // رمزگذاری در صورت درخواست
        if ($encrypt) {
            $this->encryptBackup($dbFile);
            $this->encryptBackup($filesFile);
        }
        
        $this->info("پشتیبان‌گیری کامل ایجاد شد: $date");
    }
    
    private function backupDatabase($file)
    {
        $config = config('database.connections.mysql');
        $command = sprintf(
            'mysqldump -h%s -u%s -p%s %s > %s',
            $config['host'],
            $config['username'],
            $config['password'],
            $config['database'],
            $file
        );
        
        exec($command);
    }
    
    private function backupFiles($file)
    {
        $command = sprintf(
            'tar -czf %s -C %s .',
            $file,
            base_path()
        );
        
        exec($command);
    }
    
    private function encryptBackup($file)
    {
        $encryptedFile = $file . '.gpg';
        $command = sprintf(
            'gpg --cipher-algo AES256 --compress-algo 1 --symmetric --output %s %s',
            $encryptedFile,
            $file
        );
        
        exec($command);
        unlink($file);
    }
}
```

### پشتیبان‌گیری از طریق رابط کاربری
```php
// در فایل app/Http/Controllers/Admin/BackupController.php
public function createBackup(Request $request)
{
    $request->validate([
        'type' => 'required|in:full,differential,incremental',
        'encrypt' => 'boolean',
        'description' => 'nullable|string|max:500'
    ]);
    
    $backup = Backup::create([
        'type' => $request->type,
        'status' => 'pending',
        'description' => $request->description,
        'created_by' => auth()->id(),
        'encrypted' => $request->boolean('encrypt'),
    ]);
    
    // اجرای پشتیبان‌گیری در پس‌زمینه
    dispatch(new CreateBackupJob($backup));
    
    return response()->json([
        'success' => true,
        'message' => 'پشتیبان‌گیری در حال ایجاد است...',
        'backup_id' => $backup->id
    ]);
}
```

## رمزگذاری پشتیبان‌گیری

### تنظیمات GPG
```bash
# ایجاد کلید GPG
gpg --gen-key

# نمایش کلیدهای موجود
gpg --list-keys

# صادرات کلید عمومی
gpg --armor --export admin@sarvcast.com > public_key.asc

# صادرات کلید خصوصی
gpg --armor --export-secret-keys admin@sarvcast.com > private_key.asc
```

### رمزگذاری خودکار
```bash
#!/bin/bash
# فایل: /opt/scripts/encrypt_backup.sh

BACKUP_FILE=$1
ENCRYPTED_FILE=$2
RECIPIENT="admin@sarvcast.com"

if [ -z "$BACKUP_FILE" ] || [ -z "$ENCRYPTED_FILE" ]; then
    echo "استفاده: $0 <فایل_پشتیبان‌گیری> <فایل_رمزگذاری_شده>"
    exit 1
fi

# رمزگذاری با کلید عمومی
gpg --encrypt --recipient $RECIPIENT --output $ENCRYPTED_FILE $BACKUP_FILE

# حذف فایل اصلی
rm $BACKUP_FILE

echo "فایل با موفقیت رمزگذاری شد: $ENCRYPTED_FILE"
```

### رمزگشایی پشتیبان‌گیری
```bash
#!/bin/bash
# فایل: /opt/scripts/decrypt_backup.sh

ENCRYPTED_FILE=$1
DECRYPTED_FILE=$2

if [ -z "$ENCRYPTED_FILE" ] || [ -z "$DECRYPTED_FILE" ]; then
    echo "استفاده: $0 <فایل_رمزگذاری_شده> <فایل_رمزگشایی_شده>"
    exit 1
fi

# رمزگشایی
gpg --decrypt --output $DECRYPTED_FILE $ENCRYPTED_FILE

echo "فایل با موفقیت رمزگشایی شد: $DECRYPTED_FILE"
```

## ذخیره‌سازی ابری

### آپلود به AWS S3
```bash
#!/bin/bash
# فایل: /opt/scripts/upload_to_s3.sh

BACKUP_FILE=$1
BUCKET_NAME="sarvcast-backups"
AWS_REGION="us-east-1"

if [ -z "$BACKUP_FILE" ]; then
    echo "استفاده: $0 <فایل_پشتیبان‌گیری>"
    exit 1
fi

# آپلود به S3
aws s3 cp $BACKUP_FILE s3://$BUCKET_NAME/$(basename $BACKUP_FILE) --region $AWS_REGION

# حذف فایل محلی
rm $BACKUP_FILE

echo "فایل با موفقیت به S3 آپلود شد: $(basename $BACKUP_FILE)"
```

### آپلود به Google Cloud Storage
```bash
#!/bin/bash
# فایل: /opt/scripts/upload_to_gcs.sh

BACKUP_FILE=$1
BUCKET_NAME="sarvcast-backups"

if [ -z "$BACKUP_FILE" ]; then
    echo "استفاده: $0 <فایل_پشتیبان‌گیری>"
    exit 1
fi

# آپلود به GCS
gsutil cp $BACKUP_FILE gs://$BUCKET_NAME/$(basename $BACKUP_FILE)

# حذف فایل محلی
rm $BACKUP_FILE

echo "فایل با موفقیت به GCS آپلود شد: $(basename $BACKUP_FILE)"
```

## بازیابی داده‌ها

### بازیابی کامل
```bash
#!/bin/bash
# فایل: /opt/scripts/restore_full.sh

BACKUP_FILE=$1
DB_NAME="sarvcast"
DB_USER="sarvcast_admin"
DB_PASS="StrongPassword123!"

if [ -z "$BACKUP_FILE" ]; then
    echo "استفاده: $0 <فایل_پشتیبان‌گیری>"
    exit 1
fi

echo "شروع بازیابی کامل..."

# رمزگشایی فایل
DECRYPTED_FILE="/tmp/restore_$(date +%Y%m%d_%H%M%S).tar.gz"
gpg --decrypt --output $DECRYPTED_FILE $BACKUP_FILE

# استخراج فایل‌ها
tar -xzf $DECRYPTED_FILE -C /

# بازیابی پایگاه داده
DB_FILE=$(find /tmp -name "db_full_*.sql.gz" | head -1)
if [ -n "$DB_FILE" ]; then
    gunzip -c $DB_FILE | mysql -u $DB_USER -p$DB_PASS $DB_NAME
fi

# تنظیم مجوزهای فایل‌ها
chown -R www-data:www-data /var/www/sarvcast
chmod -R 755 /var/www/sarvcast

# پاک کردن فایل‌های موقت
rm $DECRYPTED_FILE
rm -f /tmp/db_full_*.sql.gz

echo "بازیابی کامل با موفقیت انجام شد"
```

### بازیابی انتخابی
```bash
#!/bin/bash
# فایل: /opt/scripts/restore_selective.sh

BACKUP_FILE=$1
RESTORE_TYPE=$2  # database, files, config
TABLE_NAME=$3    # برای بازیابی جدول خاص

if [ -z "$BACKUP_FILE" ] || [ -z "$RESTORE_TYPE" ]; then
    echo "استفاده: $0 <فایل_پشتیبان‌گیری> <نوع_بازیابی> [نام_جدول]"
    echo "انواع بازیابی: database, files, config"
    exit 1
fi

echo "شروع بازیابی انتخابی..."

# رمزگشایی فایل
DECRYPTED_FILE="/tmp/restore_$(date +%Y%m%d_%H%M%S).tar.gz"
gpg --decrypt --output $DECRYPTED_FILE $BACKUP_FILE

case $RESTORE_TYPE in
    "database")
        if [ -n "$TABLE_NAME" ]; then
            # بازیابی جدول خاص
            DB_FILE=$(find /tmp -name "db_*.sql.gz" | head -1)
            if [ -n "$DB_FILE" ]; then
                gunzip -c $DB_FILE | grep -A 1000 "CREATE TABLE.*$TABLE_NAME" | mysql -u $DB_USER -p$DB_PASS $DB_NAME
            fi
        else
            # بازیابی کل پایگاه داده
            DB_FILE=$(find /tmp -name "db_*.sql.gz" | head -1)
            if [ -n "$DB_FILE" ]; then
                gunzip -c $DB_FILE | mysql -u $DB_USER -p$DB_PASS $DB_NAME
            fi
        fi
        ;;
    "files")
        # بازیابی فایل‌ها
        tar -xzf $DECRYPTED_FILE -C / --exclude="*.sql.gz"
        ;;
    "config")
        # بازیابی تنظیمات
        tar -xzf $DECRYPTED_FILE -C / --wildcards "*/config/*"
        ;;
esac

# پاک کردن فایل‌های موقت
rm $DECRYPTED_FILE

echo "بازیابی انتخابی با موفقیت انجام شد"
```

## تست پشتیبان‌گیری

### تست یکپارچگی
```bash
#!/bin/bash
# فایل: /opt/scripts/test_backup_integrity.sh

BACKUP_FILE=$1

if [ -z "$BACKUP_FILE" ]; then
    echo "استفاده: $0 <فایل_پشتیبان‌گیری>"
    exit 1
fi

echo "شروع تست یکپارچگی پشتیبان‌گیری..."

# بررسی وجود فایل
if [ ! -f "$BACKUP_FILE" ]; then
    echo "خطا: فایل پشتیبان‌گیری یافت نشد"
    exit 1
fi

# بررسی اندازه فایل
FILE_SIZE=$(stat -c%s "$BACKUP_FILE")
if [ $FILE_SIZE -lt 1000 ]; then
    echo "خطا: فایل پشتیبان‌گیری خیلی کوچک است"
    exit 1
fi

# تست رمزگشایی
echo "تست رمزگشایی..."
if gpg --decrypt --output /tmp/test_decrypt.tar.gz $BACKUP_FILE 2>/dev/null; then
    echo "رمزگشایی موفق"
    rm /tmp/test_decrypt.tar.gz
else
    echo "خطا: رمزگشایی ناموفق"
    exit 1
fi

# تست استخراج
echo "تست استخراج..."
if tar -tzf /tmp/test_decrypt.tar.gz >/dev/null 2>&1; then
    echo "استخراج موفق"
else
    echo "خطا: استخراج ناموفق"
    exit 1
fi

echo "تست یکپارچگی پشتیبان‌گیری موفق"
```

### تست بازیابی
```bash
#!/bin/bash
# فایل: /opt/scripts/test_restore.sh

BACKUP_FILE=$1
TEST_DB_NAME="sarvcast_test"

if [ -z "$BACKUP_FILE" ]; then
    echo "استفاده: $0 <فایل_پشتیبان‌گیری>"
    exit 1
fi

echo "شروع تست بازیابی..."

# ایجاد پایگاه داده تست
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS $TEST_DB_NAME;"

# رمزگشایی و استخراج
DECRYPTED_FILE="/tmp/test_restore_$(date +%Y%m%d_%H%M%S).tar.gz"
gpg --decrypt --output $DECRYPTED_FILE $BACKUP_FILE

# بازیابی به پایگاه داده تست
DB_FILE=$(find /tmp -name "db_*.sql.gz" | head -1)
if [ -n "$DB_FILE" ]; then
    gunzip -c $DB_FILE | mysql -u root -p $TEST_DB_NAME
fi

# بررسی جداول
TABLE_COUNT=$(mysql -u root -p -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$TEST_DB_NAME';" -s)
echo "تعداد جداول بازیابی شده: $TABLE_COUNT"

# حذف پایگاه داده تست
mysql -u root -p -e "DROP DATABASE $TEST_DB_NAME;"

# پاک کردن فایل‌های موقت
rm $DECRYPTED_FILE

echo "تست بازیابی موفق"
```

## نظارت بر پشتیبان‌گیری

### مانیتورینگ وضعیت
```bash
#!/bin/bash
# فایل: /opt/scripts/monitor_backups.sh

BACKUP_DIR="/opt/backups"
ALERT_EMAIL="admin@sarvcast.com"

echo "شروع نظارت بر پشتیبان‌گیری‌ها..."

# بررسی آخرین پشتیبان‌گیری کامل
LAST_FULL=$(find $BACKUP_DIR/full -name "*.gpg" -type f -printf '%T@ %p\n' | sort -n | tail -1 | cut -d' ' -f2-)
if [ -n "$LAST_FULL" ]; then
    LAST_FULL_DATE=$(stat -c %Y "$LAST_FULL")
    DAYS_OLD=$(( (NOW - LAST_FULL_DATE) / 86400 ))
    
    if [ $DAYS_OLD -gt 7 ]; then
        echo "هشدار: آخرین پشتیبان‌گیری کامل بیش از 7 روز قدمت دارد"
        mail -s "هشدار پشتیبان‌گیری" $ALERT_EMAIL << EOF
آخرین پشتیبان‌گیری کامل بیش از 7 روز قدمت دارد.
فایل: $LAST_FULL
تاریخ: $(date -d @$LAST_FULL_DATE)
EOF
    fi
fi

# بررسی فضای دیسک
DISK_USAGE=$(df $BACKUP_DIR | awk 'NR==2 {print $5}' | sed 's/%//')
if [ $DISK_USAGE -gt 80 ]; then
    echo "هشدار: فضای دیسک بیش از 80% پر است"
    mail -s "هشدار فضای دیسک" $ALERT_EMAIL << EOF
فضای دیسک پشتیبان‌گیری بیش از 80% پر است.
استفاده فعلی: $DISK_USAGE%
EOF
fi

echo "نظارت بر پشتیبان‌گیری‌ها تکمیل شد"
```

### گزارش‌گیری
```bash
#!/bin/bash
# فایل: /opt/scripts/backup_report.sh

BACKUP_DIR="/opt/backups"
REPORT_EMAIL="admin@sarvcast.com"

echo "ایجاد گزارش پشتیبان‌گیری..."

# شمارش پشتیبان‌گیری‌ها
FULL_COUNT=$(find $BACKUP_DIR/full -name "*.gpg" | wc -l)
DIFF_COUNT=$(find $BACKUP_DIR/differential -name "*.gpg" | wc -l)
INC_COUNT=$(find $BACKUP_DIR/incremental -name "*.gpg" | wc -l)

# محاسبه حجم کل
TOTAL_SIZE=$(du -sh $BACKUP_DIR | cut -f1)

# ایجاد گزارش
cat > /tmp/backup_report.txt << EOF
گزارش پشتیبان‌گیری - $(date)

آمار پشتیبان‌گیری‌ها:
- پشتیبان‌گیری کامل: $FULL_COUNT
- پشتیبان‌گیری تفاضلی: $DIFF_COUNT
- پشتیبان‌گیری افزایشی: $INC_COUNT

حجم کل: $TOTAL_SIZE

آخرین پشتیبان‌گیری‌ها:
$(find $BACKUP_DIR -name "*.gpg" -type f -printf '%T@ %p\n' | sort -n | tail -5 | while read timestamp file; do
    echo "$(date -d @$timestamp): $(basename $file)"
done)

وضعیت فضای دیسک:
$(df -h $BACKUP_DIR)
EOF

# ارسال گزارش
mail -s "گزارش پشتیبان‌گیری" $REPORT_EMAIL < /tmp/backup_report.txt

echo "گزارش پشتیبان‌گیری ارسال شد"
```

## بهینه‌سازی پشتیبان‌گیری

### فشرده‌سازی پیشرفته
```bash
#!/bin/bash
# فایل: /opt/scripts/optimize_compression.sh

BACKUP_FILE=$1
COMPRESSION_LEVEL=9  # 1-9 (9 = بیشترین فشرده‌سازی)

if [ -z "$BACKUP_FILE" ]; then
    echo "استفاده: $0 <فایل_پشتیبان‌گیری>"
    exit 1
fi

echo "شروع بهینه‌سازی فشرده‌سازی..."

# فشرده‌سازی با سطح بالا
gzip -$COMPRESSION_LEVEL $BACKUP_FILE

# فشرده‌سازی اضافی با bzip2
bzip2 -$COMPRESSION_LEVEL ${BACKUP_FILE}.gz

echo "فشرده‌سازی بهینه‌سازی شد: ${BACKUP_FILE}.gz.bz2"
```

### پشتیبان‌گیری موازی
```bash
#!/bin/bash
# فایل: /opt/scripts/parallel_backup.sh

BACKUP_DIR="/opt/backups/parallel"
MAX_PARALLEL=4

echo "شروع پشتیبان‌گیری موازی..."

# پشتیبان‌گیری موازی جداول
mysql -u root -p -e "SHOW TABLES" sarvcast | tail -n +2 | while read table; do
    (
        mysqldump -u root -p sarvcast $table > $BACKUP_DIR/${table}.sql
        echo "جدول $table پشتیبان‌گیری شد"
    ) &
    
    # محدود کردن تعداد فرآیندهای موازی
    if [ $(jobs -r | wc -l) -ge $MAX_PARALLEL ]; then
        wait
    fi
done

# انتظار برای تکمیل تمام فرآیندها
wait

echo "پشتیبان‌گیری موازی تکمیل شد"
```

---

**نکته**: این راهنما به صورت مداوم به‌روزرسانی می‌شود. برای آخرین نسخه، به بخش مستندات پشتیبان‌گیری مراجعه کنید.
