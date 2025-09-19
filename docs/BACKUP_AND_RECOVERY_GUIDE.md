# راهنمای پشتیبان‌گیری و بازیابی - سروکست

## مقدمه

این راهنما برای مدیران سیستم سروکست تهیه شده است تا بتوانند از داده‌های جدید (تایم‌لاین تصاویر، نظرات داستان‌ها، و متریک‌های عملکرد) پشتیبان‌گیری کنند و در صورت نیاز آن‌ها را بازیابی کنند.

## ویژگی‌های پشتیبان‌گیری

### 1. پشتیبان‌گیری کامل (Full Backup)
- پشتیبان‌گیری از کل پایگاه داده
- پشتیبان‌گیری از تمام داده‌های جدید
- پشتیبان‌گیری از فایل‌های پیکربندی
- ایجاد آرشیو فشرده

### 2. پشتیبان‌گیری افزایشی (Incremental Backup)
- پشتیبان‌گیری از تغییرات جدید
- صرفه‌جویی در فضای ذخیره‌سازی
- سرعت بالای پشتیبان‌گیری

### 3. بازیابی انتخابی
- بازیابی داده‌های خاص
- بازیابی کامل سیستم
- اعتبارسنجی داده‌های بازیابی شده

## داده‌های پشتیبان‌گیری شده

### 1. تایم‌لاین تصاویر
- جدول `image_timelines`
- اطلاعات اپیزودهای مرتبط
- تصاویر و زمان‌بندی

### 2. نظرات داستان‌ها
- جدول `story_comments`
- اطلاعات کاربران و داستان‌ها
- وضعیت تأیید نظرات

### 3. متریک‌های عملکرد
- جدول `performance_metrics`
- آمار عملکرد سیستم
- هشدارهای عملکرد

### 4. پایگاه داده کامل
- تمام جداول سیستم
- روابط و محدودیت‌ها
- داده‌های کاربران

## استفاده از API پشتیبان‌گیری

### ایجاد پشتیبان کامل
```http
POST /api/v1/admin/backup/create-full
Authorization: Bearer {admin_token}
```

**پاسخ:**
```json
{
    "success": true,
    "message": "پشتیبان‌گیری کامل با موفقیت انجام شد",
    "data": {
        "backup_id": "backup_2025_09_16_12_30_45",
        "backup_path": "backups/backup_2025_09_16_12_30_45.zip",
        "size": 15728640,
        "created_at": "2025-09-16T12:30:45Z",
        "manifest": {
            "backup_id": "backup_2025_09_16_12_30_45",
            "created_at": "2025-09-16T12:30:45Z",
            "version": "1.0",
            "components": {
                "database": {
                    "file": "backups/backup_2025_09_16_12_30_45/database.sql",
                    "size": 10485760
                },
                "image_timelines": {
                    "file": "backups/backup_2025_09_16_12_30_45/image_timelines.json",
                    "count": 150,
                    "size": 204800
                },
                "story_comments": {
                    "file": "backups/backup_2025_09_16_12_30_45/story_comments.json",
                    "count": 500,
                    "size": 512000
                },
                "performance_metrics": {
                    "file": "backups/backup_2025_09_16_12_30_45/performance_metrics.json",
                    "count": 1000,
                    "size": 1024000
                }
            },
            "total_size": 15728640
        }
    }
}
```

### ایجاد پشتیبان افزایشی
```http
POST /api/v1/admin/backup/create-incremental
Authorization: Bearer {admin_token}
Content-Type: application/json

{
    "since": "2025-09-15T00:00:00Z"
}
```

### فهرست پشتیبان‌ها
```http
GET /api/v1/admin/backup/list
Authorization: Bearer {admin_token}
```

**پاسخ:**
```json
{
    "success": true,
    "message": "فهرست پشتیبان‌ها دریافت شد",
    "data": {
        "backups": [
            {
                "file": "backups/backup_2025_09_16_12_30_45.zip",
                "size": 15728640,
                "created_at": 1726488645,
                "backup_id": "backup_2025_09_16_12_30_45"
            }
        ],
        "total_count": 1,
        "total_size": 15728640
    }
}
```

### بازیابی از پشتیبان
```http
POST /api/v1/admin/backup/restore
Authorization: Bearer {admin_token}
Content-Type: application/json

{
    "backup_id": "backup_2025_09_16_12_30_45"
}
```

### دانلود پشتیبان
```http
GET /api/v1/admin/backup/download/{backupId}
Authorization: Bearer {admin_token}
```

### حذف پشتیبان
```http
DELETE /api/v1/admin/backup/{backupId}
Authorization: Bearer {admin_token}
```

### پاکسازی پشتیبان‌های قدیمی
```http
POST /api/v1/admin/backup/cleanup
Authorization: Bearer {admin_token}
Content-Type: application/json

{
    "days_to_keep": 30
}
```

## زمان‌بندی خودکار

### تنظیم زمان‌بندی
```http
POST /api/v1/admin/backup/schedule
Authorization: Bearer {admin_token}
Content-Type: application/json

{
    "full_backup_frequency": "weekly",
    "incremental_backup_frequency": "daily",
    "retention_days": 30,
    "enabled": true
}
```

### دریافت زمان‌بندی
```http
GET /api/v1/admin/backup/schedule
Authorization: Bearer {admin_token}
```

## بهترین شیوه‌ها

### 1. فرکانس پشتیبان‌گیری
- **پشتیبان کامل**: هفتگی یا ماهانه
- **پشتیبان افزایشی**: روزانه یا ساعتی
- **متریک‌های عملکرد**: روزانه

### 2. نگهداری پشتیبان‌ها
- نگهداری حداقل 30 روز
- نگهداری پشتیبان‌های مهم تا 1 سال
- پاکسازی خودکار پشتیبان‌های قدیمی

### 3. امنیت
- رمزگذاری پشتیبان‌ها
- ذخیره در مکان‌های مختلف
- تست منظم بازیابی

### 4. نظارت
- نظارت بر موفقیت پشتیبان‌گیری
- هشدار در صورت شکست
- گزارش‌گیری منظم

## عیب‌یابی

### مشکلات رایج

#### 1. خطای فضای ذخیره‌سازی
```
خطا: Not enough disk space for backup
راه‌حل: پاکسازی فضا یا افزایش ظرفیت
```

#### 2. خطای دسترسی به پایگاه داده
```
خطا: Database connection failed
راه‌حل: بررسی تنظیمات پایگاه داده
```

#### 3. خطای فشرده‌سازی
```
خطا: Cannot create zip archive
راه‌حل: بررسی مجوزهای فایل و فضای ذخیره
```

### راه‌حل‌های پیشنهادی
1. بررسی لاگ‌های سیستم
2. تست پشتیبان‌گیری در محیط آزمایش
3. نظارت بر عملکرد سیستم
4. بروزرسانی منظم سیستم

## بازیابی در مواقع اضطراری

### مراحل بازیابی کامل
1. توقف سرویس‌ها
2. بازیابی پایگاه داده
3. بازیابی فایل‌ها
4. بررسی یکپارچگی داده‌ها
5. راه‌اندازی مجدد سرویس‌ها

### بازیابی انتخابی
1. شناسایی داده‌های آسیب دیده
2. بازیابی از پشتیبان مناسب
3. اعتبارسنجی داده‌ها
4. تست عملکرد

## نظارت و گزارش‌گیری

### آمار پشتیبان‌ها
```http
GET /api/v1/admin/backup/stats
Authorization: Bearer {admin_token}
```

**پاسخ:**
```json
{
    "success": true,
    "message": "آمار پشتیبان‌ها دریافت شد",
    "data": {
        "total_backups": 10,
        "total_size": 157286400,
        "oldest_backup": 1726400645,
        "newest_backup": 1726488645,
        "average_size": 15728640
    }
}
```

### گزارش‌های پیشنهادی
- گزارش روزانه پشتیبان‌گیری
- گزارش ماهانه آمار پشتیبان‌ها
- گزارش سالانه بازیابی‌ها
- گزارش عملکرد سیستم پشتیبان‌گیری

## امنیت و حریم خصوصی

### حفاظت از داده‌ها
- رمزگذاری پشتیبان‌ها
- کنترل دسترسی
- نظارت بر دسترسی‌ها
- پاکسازی امن داده‌ها

### انطباق با قوانین
- رعایت قوانین حریم خصوصی
- نگهداری مناسب داده‌ها
- گزارش‌گیری به مراجع قانونی
- حذف امن داده‌ها

## پشتیبانی

### منابع کمک
- مستندات فنی
- راهنمای عیب‌یابی
- تیم پشتیبانی فنی

### تماس با پشتیبانی
- ایمیل: backup-support@sarvcast.com
- تلفن: 021-12345678
- چت آنلاین: پنل مدیریت

---

**نکته مهم**: این راهنما برای نسخه 1.0.0 سیستم پشتیبان‌گیری تهیه شده است. برای آخرین تغییرات، به مستندات فنی مراجعه کنید.
