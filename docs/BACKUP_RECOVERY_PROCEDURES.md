# راهنمای پشتیبان‌گیری و بازیابی - سروکست

## مقدمه

این راهنما شامل روش‌های پشتیبان‌گیری و بازیابی داده‌های سروکست، به ویژه ویژگی‌های جدید مانند تایم‌لاین تصاویر و سیستم نظرات است.

## ویژگی‌های جدید شامل شده

### 1. جداول جدید پایگاه داده
- `image_timelines`: داده‌های تایم‌لاین تصاویر
- `story_comments`: نظرات کاربران روی داستان‌ها
- `episodes.use_image_timeline`: فلگ استفاده از تایم‌لاین در اپیزودها

### 2. فایل‌های جدید
- تصاویر تایم‌لاین در `storage/app/public/timeline-images/`
- لاگ‌های سیستم نظرات
- کش‌های تایم‌لاین

## روش‌های پشتیبان‌گیری

### 1. پشتیبان‌گیری خودکار (روزانه)

#### Linux/macOS
```bash
#!/bin/bash
# اسکریپت پشتیبان‌گیری روزانه

BACKUP_DIR="/backups/sarvcast"
DATE=$(date +%Y%m%d-%H%M%S)
BACKUP_NAME="sarvcast-backup-$DATE"

# ایجاد دایرکتوری پشتیبان
mkdir -p "$BACKUP_DIR/$BACKUP_NAME"

# پشتیبان‌گیری پایگاه داده
docker-compose -f /var/www/sarvcast/docker-compose.production.yml exec -T mysql mysqldump \
    -u sarvcast_user \
    -p$DB_PASSWORD \
    --single-transaction \
    --routines \
    --triggers \
    --include-new-tables \
    sarvcast_production > "$BACKUP_DIR/$BACKUP_NAME/database.sql"

# پشتیبان‌گیری فایل‌های اپلیکیشن
tar -czf "$BACKUP_DIR/$BACKUP_NAME/application.tar.gz" \
    --exclude='node_modules' \
    --exclude='vendor' \
    --exclude='storage/logs' \
    --exclude='storage/framework/cache' \
    --exclude='storage/framework/sessions' \
    --exclude='storage/framework/views' \
    --exclude='.git' \
    --exclude='.env' \
    /var/www/sarvcast

# پشتیبان‌گیری فایل‌های آپلود شده
tar -czf "$BACKUP_DIR/$BACKUP_NAME/storage.tar.gz" \
    /var/www/sarvcast/storage/app/public

# پشتیبان‌گیری حجم‌های Docker
docker run --rm \
    -v sarvcast_mysql_data:/data \
    -v sarvcast_redis_data:/redis \
    -v "$BACKUP_DIR/$BACKUP_NAME":/backup \
    alpine tar czf /backup/volumes.tar.gz /data /redis

echo "پشتیبان‌گیری کامل شد: $BACKUP_NAME"
```

#### Windows
```batch
@echo off
REM اسکریپت پشتیبان‌گیری روزانه برای Windows

set BACKUP_DIR=C:\backups\sarvcast
set DATE=%date:~-4,4%%date:~-10,2%%date:~-7,2%-%time:~0,2%%time:~3,2%%time:~6,2%
set BACKUP_NAME=sarvcast-backup-%DATE%

REM ایجاد دایرکتوری پشتیبان
mkdir "%BACKUP_DIR%\%BACKUP_NAME%"

REM پشتیبان‌گیری پایگاه داده
docker-compose -f C:\var\www\sarvcast\docker-compose.production.yml exec -T mysql mysqldump -u sarvcast_user -p%DB_PASSWORD% --single-transaction --routines --triggers sarvcast_production > "%BACKUP_DIR%\%BACKUP_NAME%\database.sql"

REM پشتیبان‌گیری فایل‌های اپلیکیشن
powershell -command "Compress-Archive -Path 'C:\var\www\sarvcast\*' -DestinationPath '%BACKUP_DIR%\%BACKUP_NAME%\application.zip' -Exclude @('node_modules', 'vendor', 'storage\logs', 'storage\framework\cache', 'storage\framework\sessions', 'storage\framework\views', '.git', '.env') -Force"

REM پشتیبان‌گیری فایل‌های آپلود شده
powershell -command "Compress-Archive -Path 'C:\var\www\sarvcast\storage\app\public\*' -DestinationPath '%BACKUP_DIR%\%BACKUP_NAME%\storage.zip' -Force"

REM پشتیبان‌گیری حجم‌های Docker
docker run --rm -v sarvcast_mysql_data:/data -v sarvcast_redis_data:/redis -v "%BACKUP_DIR%\%BACKUP_NAME%":/backup alpine tar czf /backup/volumes.tar.gz /data /redis

echo پشتیبان‌گیری کامل شد: %BACKUP_NAME%
```

### 2. پشتیبان‌گیری دستی

#### پشتیبان‌گیری کامل
```bash
# اجرای اسکریپت پشتیبان‌گیری
./scripts/backup.sh

# یا برای Windows
scripts\backup.bat
```

#### پشتیبان‌گیری انتخابی

##### فقط پایگاه داده
```bash
docker-compose -f docker-compose.production.yml exec mysql mysqldump \
    -u sarvcast_user \
    -p$DB_PASSWORD \
    --single-transaction \
    --routines \
    --triggers \
    sarvcast_production > backup-database-$(date +%Y%m%d).sql
```

##### فقط فایل‌های تایم‌لاین
```bash
tar -czf timeline-images-backup-$(date +%Y%m%d).tar.gz \
    storage/app/public/timeline-images/
```

##### فقط نظرات
```bash
docker-compose -f docker-compose.production.yml exec mysql mysqldump \
    -u sarvcast_user \
    -p$DB_PASSWORD \
    --single-transaction \
    --where="created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)" \
    sarvcast_production story_comments > comments-backup-$(date +%Y%m%d).sql
```

### 3. پشتیبان‌گیری ابری

#### آپلود به AWS S3
```bash
#!/bin/bash
# آپلود پشتیبان به S3

BACKUP_FILE="sarvcast-backup-$(date +%Y%m%d).tar.gz"
S3_BUCKET="sarvcast-backups"
S3_PATH="backups/$(date +%Y)/$(date +%m)/"

# ایجاد پشتیبان فشرده
tar -czf "$BACKUP_FILE" \
    --exclude='node_modules' \
    --exclude='vendor' \
    --exclude='storage/logs' \
    --exclude='storage/framework/cache' \
    --exclude='storage/framework/sessions' \
    --exclude='storage/framework/views' \
    --exclude='.git' \
    --exclude='.env' \
    /var/www/sarvcast

# آپلود به S3
aws s3 cp "$BACKUP_FILE" "s3://$S3_BUCKET/$S3_PATH$BACKUP_FILE"

# حذف فایل موقت
rm "$BACKUP_FILE"

echo "پشتیبان به S3 آپلود شد: s3://$S3_BUCKET/$S3_PATH$BACKUP_FILE"
```

## روش‌های بازیابی

### 1. بازیابی کامل

#### از پشتیبان کامل
```bash
#!/bin/bash
# بازیابی کامل از پشتیبان

BACKUP_PATH="/backups/sarvcast/sarvcast-backup-20241201-120000"
APP_PATH="/var/www/sarvcast"

# توقف سرویس‌ها
docker-compose -f "$APP_PATH/docker-compose.production.yml" down

# بازیابی فایل‌های اپلیکیشن
tar -xzf "$BACKUP_PATH/application.tar.gz" -C /

# بازیابی فایل‌های آپلود شده
tar -xzf "$BACKUP_PATH/storage.tar.gz" -C /

# راه‌اندازی سرویس‌ها
docker-compose -f "$APP_PATH/docker-compose.production.yml" up -d mysql redis
sleep 30

# بازیابی پایگاه داده
docker-compose -f "$APP_PATH/docker-compose.production.yml" exec -T mysql mysql \
    -u sarvcast_user \
    -p$DB_PASSWORD \
    sarvcast_production < "$BACKUP_PATH/database.sql"

# بازیابی حجم‌های Docker
docker run --rm \
    -v sarvcast_mysql_data:/data \
    -v sarvcast_redis_data:/redis \
    -v "$BACKUP_PATH":/backup \
    alpine tar xzf /backup/volumes.tar.gz -C /

# راه‌اندازی تمام سرویس‌ها
docker-compose -f "$APP_PATH/docker-compose.production.yml" up -d

echo "بازیابی کامل انجام شد"
```

### 2. بازیابی انتخابی

#### بازیابی فقط پایگاه داده
```bash
# بازیابی پایگاه داده
docker-compose -f docker-compose.production.yml exec -T mysql mysql \
    -u sarvcast_user \
    -p$DB_PASSWORD \
    sarvcast_production < backup-database-20241201.sql
```

#### بازیابی فقط فایل‌های تایم‌لاین
```bash
# بازیابی فایل‌های تایم‌لاین
tar -xzf timeline-images-backup-20241201.tar.gz -C storage/app/public/
```

#### بازیابی فقط نظرات
```bash
# بازیابی نظرات
docker-compose -f docker-compose.production.yml exec -T mysql mysql \
    -u sarvcast_user \
    -p$DB_PASSWORD \
    sarvcast_production < comments-backup-20241201.sql
```

### 3. بازیابی از S3

```bash
#!/bin/bash
# بازیابی از S3

BACKUP_FILE="sarvcast-backup-20241201.tar.gz"
S3_BUCKET="sarvcast-backups"
S3_PATH="backups/2024/12/"

# دانلود از S3
aws s3 cp "s3://$S3_BUCKET/$S3_PATH$BACKUP_FILE" "./$BACKUP_FILE"

# استخراج
tar -xzf "$BACKUP_FILE"

# ادامه فرآیند بازیابی...
```

## پشتیبان‌گیری ویژگی‌های خاص

### 1. پشتیبان‌گیری تایم‌لاین تصاویر

```bash
#!/bin/bash
# پشتیبان‌گیری تایم‌لاین تصاویر

TIMELINE_BACKUP_DIR="/backups/timeline-images"
DATE=$(date +%Y%m%d)

mkdir -p "$TIMELINE_BACKUP_DIR"

# پشتیبان‌گیری فایل‌های تصاویر
tar -czf "$TIMELINE_BACKUP_DIR/timeline-images-$DATE.tar.gz" \
    storage/app/public/timeline-images/

# پشتیبان‌گیری داده‌های تایم‌لاین
docker-compose -f docker-compose.production.yml exec -T mysql mysqldump \
    -u sarvcast_user \
    -p$DB_PASSWORD \
    --single-transaction \
    sarvcast_production image_timelines > "$TIMELINE_BACKUP_DIR/image-timelines-$DATE.sql"

echo "پشتیبان‌گیری تایم‌لاین کامل شد"
```

### 2. پشتیبان‌گیری نظرات

```bash
#!/bin/bash
# پشتیبان‌گیری نظرات

COMMENTS_BACKUP_DIR="/backups/comments"
DATE=$(date +%Y%m%d)

mkdir -p "$COMMENTS_BACKUP_DIR"

# پشتیبان‌گیری نظرات تایید شده
docker-compose -f docker-compose.production.yml exec -T mysql mysqldump \
    -u sarvcast_user \
    -p$DB_PASSWORD \
    --single-transaction \
    --where="status='approved'" \
    sarvcast_production story_comments > "$COMMENTS_BACKUP_DIR/approved-comments-$DATE.sql"

# پشتیبان‌گیری تمام نظرات
docker-compose -f docker-compose.production.yml exec -T mysql mysqldump \
    -u sarvcast_user \
    -p$DB_PASSWORD \
    --single-transaction \
    sarvcast_production story_comments > "$COMMENTS_BACKUP_DIR/all-comments-$DATE.sql"

echo "پشتیبان‌گیری نظرات کامل شد"
```

## مانیتورینگ پشتیبان‌گیری

### 1. بررسی وضعیت پشتیبان‌گیری

```bash
#!/bin/bash
# بررسی وضعیت پشتیبان‌گیری

BACKUP_DIR="/backups/sarvcast"
LOG_FILE="/var/log/sarvcast-backup.log"

echo "=== گزارش وضعیت پشتیبان‌گیری ==="
echo "تاریخ: $(date)"
echo ""

# بررسی آخرین پشتیبان
LATEST_BACKUP=$(ls -t "$BACKUP_DIR" | head -1)
if [ -n "$LATEST_BACKUP" ]; then
    echo "آخرین پشتیبان: $LATEST_BACKUP"
    echo "تاریخ ایجاد: $(stat -c %y "$BACKUP_DIR/$LATEST_BACKUP")"
    echo "اندازه: $(du -sh "$BACKUP_DIR/$LATEST_BACKUP" | cut -f1)"
else
    echo "هیچ پشتیبانی یافت نشد"
fi

echo ""

# بررسی فایل‌های پشتیبان
echo "فایل‌های پشتیبان موجود:"
ls -la "$BACKUP_DIR" | tail -10

echo ""

# بررسی لاگ‌ها
echo "آخرین ورودی‌های لاگ:"
tail -5 "$LOG_FILE"
```

### 2. هشدارهای پشتیبان‌گیری

```bash
#!/bin/bash
# اسکریپت هشدار پشتیبان‌گیری

BACKUP_DIR="/backups/sarvcast"
ALERT_EMAIL="admin@sarvcast.com"

# بررسی آخرین پشتیبان
LATEST_BACKUP=$(ls -t "$BACKUP_DIR" | head -1)
if [ -n "$LATEST_BACKUP" ]; then
    BACKUP_TIME=$(stat -c %Y "$BACKUP_DIR/$LATEST_BACKUP")
    CURRENT_TIME=$(date +%s)
    TIME_DIFF=$((CURRENT_TIME - BACKUP_TIME))
    
    # اگر پشتیبان بیش از 25 ساعت قدیمی باشد
    if [ $TIME_DIFF -gt 90000 ]; then
        echo "هشدار: آخرین پشتیبان بیش از 25 ساعت قدیمی است" | mail -s "هشدار پشتیبان‌گیری سروکست" "$ALERT_EMAIL"
    fi
else
    echo "هشدار: هیچ پشتیبانی یافت نشد" | mail -s "هشدار پشتیبان‌گیری سروکست" "$ALERT_EMAIL"
fi
```

## تست بازیابی

### 1. تست بازیابی در محیط آزمایشی

```bash
#!/bin/bash
# تست بازیابی در محیط آزمایشی

TEST_ENV="/var/www/sarvcast-test"
BACKUP_PATH="/backups/sarvcast/sarvcast-backup-20241201-120000"

echo "ایجاد محیط آزمایشی..."
mkdir -p "$TEST_ENV"

echo "کپی فایل‌های پشتیبان..."
cp -r "$BACKUP_PATH" "$TEST_ENV/"

echo "استخراج فایل‌های اپلیکیشن..."
tar -xzf "$TEST_ENV/sarvcast-backup-20241201-120000/application.tar.gz" -C "$TEST_ENV/"

echo "راه‌اندازی محیط آزمایشی..."
cd "$TEST_ENV"
docker-compose -f docker-compose.production.yml up -d

echo "تست بازیابی کامل شد"
```

### 2. بررسی یکپارچگی داده‌ها

```bash
#!/bin/bash
# بررسی یکپارچگی داده‌ها پس از بازیابی

echo "بررسی یکپارچگی پایگاه داده..."

# بررسی جداول جدید
docker-compose -f docker-compose.production.yml exec mysql mysql \
    -u sarvcast_user \
    -p$DB_PASSWORD \
    -e "SELECT COUNT(*) as image_timelines_count FROM sarvcast_production.image_timelines;"

docker-compose -f docker-compose.production.yml exec mysql mysql \
    -u sarvcast_user \
    -p$DB_PASSWORD \
    -e "SELECT COUNT(*) as story_comments_count FROM sarvcast_production.story_comments;"

# بررسی فایل‌های تایم‌لاین
echo "بررسی فایل‌های تایم‌لاین..."
ls -la storage/app/public/timeline-images/

echo "بررسی یکپارچگی کامل شد"
```

## برنامه‌ریزی پشتیبان‌گیری

### 1. Cron Jobs

```bash
# ویرایش crontab
crontab -e

# پشتیبان‌گیری روزانه در ساعت 2 صبح
0 2 * * * /var/www/sarvcast/scripts/backup.sh

# پشتیبان‌گیری هفتگی کامل در یکشنبه‌ها
0 1 * * 0 /var/www/sarvcast/scripts/full-backup.sh

# پاک‌سازی پشتیبان‌های قدیمی (بیش از 30 روز)
0 3 * * * find /backups/sarvcast -type d -mtime +30 -exec rm -rf {} \;
```

### 2. Windows Task Scheduler

```batch
REM ایجاد وظیفه پشتیبان‌گیری روزانه
schtasks /create /tn "SarvCast Daily Backup" /tr "C:\var\www\sarvcast\scripts\backup.bat" /sc daily /st 02:00 /f

REM ایجاد وظیفه پشتیبان‌گیری هفتگی
schtasks /create /tn "SarvCast Weekly Backup" /tr "C:\var\www\sarvcast\scripts\full-backup.bat" /sc weekly /d SUN /st 01:00 /f
```

## نکات مهم

### 1. امنیت پشتیبان‌ها
- پشتیبان‌ها را در مکان‌های امن نگهداری کنید
- از رمزگذاری برای پشتیبان‌های حساس استفاده کنید
- دسترسی به پشتیبان‌ها را محدود کنید

### 2. تست منظم
- پشتیبان‌ها را به طور منظم تست کنید
- فرآیند بازیابی را تمرین کنید
- زمان بازیابی را اندازه‌گیری کنید

### 3. مستندسازی
- تمام فرآیندهای پشتیبان‌گیری را مستند کنید
- تغییرات را ثبت کنید
- آموزش تیم را به‌روزرسانی کنید

## تماس با پشتیبانی

در صورت بروز مشکل در فرآیند پشتیبان‌گیری یا بازیابی:

- ایمیل: support@sarvcast.com
- تلفن: 021-12345678
- تلگرام: @SarvCastSupport

---

**نکته مهم**: این راهنما برای مدیریت پشتیبان‌گیری و بازیابی سروکست تهیه شده است. برای سوالات فنی، با تیم پشتیبانی تماس بگیرید.
