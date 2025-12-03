# ✅ چک‌لیست راه‌اندازی سیستم پرداخت چند پلتفرمی

## مرحله 1: اجرای Migration

```bash
cd sarvcast-laravel
php artisan migrate
```

این migration فیلدهای زیر را اضافه می‌کند:
- `billing_platform` در جدول `payments` و `subscriptions`
- فیلدهای مربوط به in-app purchase (purchase_token, order_id, product_id, ...)
- فیلدهای مربوط به store metadata

**بررسی:**
```bash
php artisan migrate:status
```

---

## مرحله 2: بررسی تنظیمات Environment

مطمئن شوید تمام متغیرهای زیر در `.env` تنظیم شده‌اند:

```env
# CafeBazaar
CAFEBAZAAR_PACKAGE_NAME=ir.sarvcast.app
CAFEBAZAAR_API_KEY=your_api_key_here
CAFEBAZAAR_API_URL=https://pardakht.cafebazaar.ir/devapi/v2/api/validate
CAFEBAZAAR_SUBSCRIPTION_API_URL=https://pardakht.cafebazaar.ir/devapi/v2/api/validate/subscription
CAFEBAZAAR_ACKNOWLEDGE_URL=https://pardakht.cafebazaar.ir/devapi/v2/api/acknowledge

# Myket
MYKET_PACKAGE_NAME=ir.sarvcast.app
MYKET_API_KEY=your_api_key_here
MYKET_API_URL=https://developer.myket.ir/api/applications/validatePurchase
MYKET_SUBSCRIPTION_API_URL=https://developer.myket.ir/api/applications/validateSubscription

# Zarinpal (Website - already configured)
ZARINPAL_MERCHANT_ID=your_merchant_id
ZARINPAL_CALLBACK_URL=https://my.sarvcast.ir
ZARINPAL_SANDBOX=false
```

**نکته:** `package_name` باید دقیقاً همان package name اپلیکیشن شما در استورها باشد.

---

## مرحله 3: تنظیم Product IDs در استورها

### 3.1 کافه‌بازار

1. وارد [پنل توسعه‌دهندگان کافه‌بازار](https://developers.cafebazaar.ir/) شوید
2. به بخش "In-App Products" بروید
3. محصولات زیر را ایجاد کنید:
   - `subscription_1month` - اشتراک یک ماهه
   - `subscription_3months` - اشتراک سه ماهه
   - `subscription_6months` - اشتراک شش ماهه
   - `subscription_1year` - اشتراک یک ساله

4. برای هر محصول:
   - قیمت را تنظیم کنید
   - نوع را "Subscription" انتخاب کنید (برای اشتراک‌ها)
   - وضعیت را "Active" کنید

### 3.2 مایکت

1. وارد [پنل توسعه‌دهندگان مایکت](https://developer.myket.ir/) شوید
2. به بخش "In-App Products" بروید
3. همان محصولات بالا را ایجاد کنید:
   - `subscription_1month`
   - `subscription_3months`
   - `subscription_6months`
   - `subscription_1year`

**مهم:** Product IDs باید در هر دو استور یکسان باشند.

---

## مرحله 4: بررسی Product Mapping

فایل `config/services.php` را بررسی کنید. Product mapping باید به این صورت باشد:

```php
'cafebazaar' => [
    'product_mapping' => [
        'subscription_1month' => '1month',
        'subscription_3months' => '3months',
        'subscription_6months' => '6months',
        'subscription_1year' => '1year',
    ],
],

'myket' => [
    'product_mapping' => [
        'subscription_1month' => '1month',
        'subscription_3months' => '3months',
        'subscription_6months' => '6months',
        'subscription_1year' => '1year',
    ],
],
```

اگر Product IDs شما متفاوت است، این mapping را به‌روزرسانی کنید.

---

## مرحله 5: تست API Endpoints

### 5.1 تست دریافت تنظیمات پلتفرم

```bash
curl -X GET "https://my.sarvcast.ir/api/v1/billing/platform-config" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**پاسخ مورد انتظار:**
```json
{
  "success": true,
  "data": {
    "billing_platform": "website",
    "supported_platforms": ["website", "cafebazaar", "myket"],
    ...
  }
}
```

### 5.2 تست تایید خرید کافه‌بازار (با داده تست)

```bash
curl -X POST "https://my.sarvcast.ir/api/v1/payments/cafebazaar/verify" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "purchase_token": "test_token",
    "product_id": "subscription_1month",
    "order_id": "test_order_123"
  }'
```

**نکته:** برای تست واقعی، باید از purchase token واقعی از کافه‌بازار استفاده کنید.

---

## مرحله 6: تنظیم App Versions

برای هر نسخه اپلیکیشن، باید پلتفرم پرداخت را مشخص کنید:

### از طریق Database:

```sql
UPDATE app_versions 
SET billing_platform = 'cafebazaar' 
WHERE version = '1.0.0' AND platform = 'android';
```

### از طریق Admin Panel:

1. وارد `/admin/app-versions` شوید
2. نسخه مورد نظر را ویرایش کنید
3. فیلد `billing_platform` را تنظیم کنید:
   - `website` - برای نسخه وب‌سایت
   - `cafebazaar` - برای نسخه کافه‌بازار
   - `myket` - برای نسخه مایکت

---

## مرحله 7: به‌روزرسانی Flutter App

### 7.1 افزودن Dependencies

در `pubspec.yaml`:

```yaml
dependencies:
  # CafeBazaar In-App Purchase
  cafebazaar_in_app_purchase: ^1.0.0  # یا پکیج مناسب
  
  # Myket In-App Purchase  
  myket_in_app_purchase: ^1.0.0  # یا پکیج مناسب
  
  # یا استفاده از پکیج یکپارچه
  in_app_purchase: ^3.1.11
```

### 7.2 پیاده‌سازی در Flutter

مثال کد در `BILLING_PLATFORMS_GUIDE.md` موجود است.

---

## مرحله 8: تست کامل

### 8.1 تست کافه‌بازار

1. نصب نسخه اپلیکیشن از کافه‌بازار
2. خرید اشتراک از داخل اپ
3. بررسی لاگ‌های سرور:
   ```bash
   tail -f storage/logs/laravel.log | grep CafeBazaar
   ```
4. بررسی در داشبورد: `/admin/payments?billing_platform=cafebazaar`

### 8.2 تست مایکت

1. نصب نسخه اپلیکیشن از مایکت
2. خرید اشتراک از داخل اپ
3. بررسی لاگ‌های سرور:
   ```bash
   tail -f storage/logs/laravel.log | grep Myket
   ```
4. بررسی در داشبورد: `/admin/payments?billing_platform=myket`

### 8.3 تست وب‌سایت

1. خرید از وب‌سایت
2. بررسی در داشبورد: `/admin/payments?billing_platform=website`

---

## مرحله 9: مانیتورینگ

### 9.1 بررسی آمار پرداخت‌ها

در داشبورد `/admin/payments` می‌توانید:
- فیلتر بر اساس `billing_platform`
- مشاهده آمار هر پلتفرم
- بررسی نرخ موفقیت

### 9.2 لاگ‌ها

تمام عملیات در `storage/logs/laravel.log` ثبت می‌شوند:
- تایید خریدها
- خطاها
- اطلاعات debug

---

## ✅ چک‌لیست نهایی

- [ ] Migration اجرا شده
- [ ] Environment variables تنظیم شده
- [ ] Product IDs در کافه‌بازار ایجاد شده
- [ ] Product IDs در مایکت ایجاد شده
- [ ] Product mapping بررسی شده
- [ ] API endpoints تست شده
- [ ] App versions تنظیم شده
- [ ] Flutter app به‌روزرسانی شده
- [ ] تست کامل انجام شده
- [ ] لاگ‌ها بررسی شده

---

## 🆘 عیب‌یابی

### مشکل: API key نامعتبر

**راه‌حل:**
- بررسی صحت API key در `.env`
- بررسی دسترسی API key در پنل استور
- بررسی IP whitelist (اگر تنظیم شده)

### مشکل: Product ID یافت نشد

**راه‌حل:**
- بررسی Product ID در استور
- بررسی Product mapping در `config/services.php`
- بررسی لاگ‌ها برای جزئیات بیشتر

### مشکل: Purchase token نامعتبر

**راه‌حل:**
- بررسی صحت purchase token
- بررسی انقضای token
- استفاده از token جدید

---

## 📞 پشتیبانی

برای مشکلات بیشتر:
1. بررسی لاگ‌ها: `storage/logs/laravel.log`
2. بررسی مستندات: `BILLING_PLATFORMS_GUIDE.md`
3. تماس با تیم توسعه

