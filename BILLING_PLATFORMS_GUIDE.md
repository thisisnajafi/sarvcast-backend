# راهنمای مدیریت پلتفرم‌های پرداخت

این سند راهنمای کامل مدیریت سه روش پرداخت در سیستم SarvCast است:
1. **پرداخت از وب‌سایت** (Zarinpal - نسخه فعلی)
2. **خرید درون‌برنامه‌ای کافه‌بازار** (CafeBazaar)
3. **خرید درون‌برنامه‌ای مایکت** (Myket)

---

## 📋 فهرست مطالب

1. [معماری سیستم](#معماری-سیستم)
2. [پیکربندی](#پیکربندی)
3. [API Endpoints](#api-endpoints)
4. [مدیریت در داشبورد](#مدیریت-در-داشبورد)
5. [پیاده‌سازی در Flutter](#پیاده‌سازی-در-flutter)
6. [تست و عیب‌یابی](#تست-و-عیب‌یابی)

---

## 🏗️ معماری سیستم

### ساختار دیتابیس

سیستم از سه جدول اصلی استفاده می‌کند:

#### 1. Payments Table
- `billing_platform`: پلتفرم پرداخت (`website`, `cafebazaar`, `myket`)
- `purchase_token`: توکن خرید از استور
- `order_id`: شناسه سفارش از استور
- `product_id`: شناسه محصول/اشتراک
- `store_response`: پاسخ کامل از استور

#### 2. Subscriptions Table
- `billing_platform`: پلتفرم پرداخت
- `store_subscription_id`: شناسه اشتراک از استور
- `auto_renew_enabled`: وضعیت تمدید خودکار از استور
- `store_expiry_time`: زمان انقضا از استور

#### 3. App Versions Table
- `billing_platform`: پلتفرم پرداخت برای این نسخه
- `billing_config`: تنظیمات خاص پلتفرم

---

## ⚙️ پیکربندی

### 1. Environment Variables

فایل `.env` را با مقادیر زیر به‌روزرسانی کنید:

```env
# Zarinpal (Website Payment)
ZARINPAL_MERCHANT_ID=your_merchant_id
ZARINPAL_CALLBACK_URL=https://my.sarvcast.ir
ZARINPAL_SANDBOX=false

# CafeBazaar
CAFEBAZAAR_PACKAGE_NAME=ir.sarvcast.app
CAFEBAZAAR_API_KEY=your_api_key
CAFEBAZAAR_API_URL=https://pardakht.cafebazaar.ir/devapi/v2/api/validate
CAFEBAZAAR_SUBSCRIPTION_API_URL=https://pardakht.cafebazaar.ir/devapi/v2/api/validate/subscription
CAFEBAZAAR_ACKNOWLEDGE_URL=https://pardakht.cafebazaar.ir/devapi/v2/api/acknowledge

# Myket
MYKET_PACKAGE_NAME=ir.sarvcast.app
MYKET_API_KEY=your_api_key
MYKET_API_URL=https://developer.myket.ir/api/applications/validatePurchase
MYKET_SUBSCRIPTION_API_URL=https://developer.myket.ir/api/applications/validateSubscription
```

### 2. Product ID Mapping

در فایل `config/services.php` می‌توانید mapping محصولات را تنظیم کنید:

```php
'cafebazaar' => [
    'product_mapping' => [
        'subscription_1month' => '1month',
        'subscription_3months' => '3months',
        'subscription_6months' => '6months',
        'subscription_1year' => '1year',
    ],
],
```

### 3. Migration

اجرای migration برای افزودن فیلدهای جدید:

```bash
php artisan migrate
```

---

## 🔌 API Endpoints

### 1. دریافت تنظیمات پلتفرم پرداخت

```http
GET /api/v1/billing/platform-config
```

**Query Parameters:**
- `app_version` (optional): نسخه اپلیکیشن
- `platform` (optional): پلتفرم (`android`, `ios`)

**Response:**
```json
{
  "success": true,
  "data": {
    "billing_platform": "cafebazaar",
    "supported_platforms": ["website", "cafebazaar", "myket"],
    "platform_config": {
      "website": {
        "name": "پرداخت از وب‌سایت",
        "gateway": "zarinpal",
        "requires_webview": true
      },
      "cafebazaar": {
        "name": "کافه‌بازار",
        "package_name": "ir.sarvcast.app",
        "requires_in_app_purchase": true
      },
      "myket": {
        "name": "مایکت",
        "package_name": "ir.sarvcast.app",
        "requires_in_app_purchase": true
      }
    }
  }
}
```

### 2. تایید خرید کافه‌بازار

```http
POST /api/v1/payments/cafebazaar/verify
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "purchase_token": "purchase_token_from_cafebazaar",
  "product_id": "subscription_1month",
  "order_id": "order_id_from_cafebazaar"
}
```

**Response:**
```json
{
  "success": true,
  "message": "خرید با موفقیت تایید و اشتراک فعال شد",
  "data": {
    "payment": {
      "id": 123,
      "amount": 50000,
      "currency": "IRT",
      "status": "completed",
      "billing_platform": "cafebazaar",
      "purchase_token": "...",
      "order_id": "...",
      "product_id": "subscription_1month"
    },
    "subscription": {
      "id": 456,
      "type": "1month",
      "status": "active",
      "start_date": "2025-01-20T10:00:00Z",
      "end_date": "2025-02-19T10:00:00Z"
    }
  }
}
```

### 3. تایید خرید مایکت

```http
POST /api/v1/payments/myket/verify
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "purchase_token": "purchase_token_from_myket",
  "product_id": "subscription_1month",
  "order_id": "order_id_from_myket"
}
```

**Response:** مشابه کافه‌بازار

### 4. پرداخت از وب‌سایت (Zarinpal)

```http
POST /api/v1/payments/initiate
POST /api/v1/payments/verify
```

(همانند قبل - بدون تغییر)

---

## 🎛️ مدیریت در داشبورد

### فیلتر پرداخت‌ها بر اساس پلتفرم

در صفحه مدیریت پرداخت‌ها (`/admin/payments`)، می‌توانید پرداخت‌ها را بر اساس `billing_platform` فیلتر کنید:

- **Website**: پرداخت‌های انجام شده از وب‌سایت
- **CafeBazaar**: خریدهای درون‌برنامه‌ای از کافه‌بازار
- **Myket**: خریدهای درون‌برنامه‌ای از مایکت

### آمار و گزارش‌ها

در داشبورد می‌توانید:
- تعداد پرداخت‌ها بر اساس هر پلتفرم
- درآمد هر پلتفرم
- نرخ موفقیت هر پلتفرم
- مقایسه عملکرد پلتفرم‌ها

---

## 📱 پیاده‌سازی در Flutter

### 1. تشخیص پلتفرم پرداخت

```dart
class BillingService {
  Future<String> getBillingPlatform() async {
    final response = await http.get(
      Uri.parse('$baseUrl/billing/platform-config'),
      headers: await _getHeaders(),
    );
    
    if (response.statusCode == 200) {
      final data = json.decode(response.body);
      return data['data']['billing_platform'];
    }
    
    return 'website'; // Default
  }
}
```

### 2. پرداخت از کافه‌بازار

```dart
Future<void> purchaseFromCafeBazaar(String productId) async {
  // استفاده از پکیج cafebazaar_in_app_purchase
  final purchaseResult = await CafeBazaarInAppPurchase.purchase(productId);
  
  if (purchaseResult.success) {
    // ارسال به سرور برای تایید
    final response = await http.post(
      Uri.parse('$baseUrl/payments/cafebazaar/verify'),
      headers: await _getHeaders(),
      body: json.encode({
        'purchase_token': purchaseResult.purchaseToken,
        'product_id': productId,
        'order_id': purchaseResult.orderId,
      }),
    );
    
    if (response.statusCode == 200) {
      // اشتراک فعال شد
      await _refreshSubscriptionStatus();
    }
  }
}
```

### 3. پرداخت از مایکت

```dart
Future<void> purchaseFromMyket(String productId) async {
  // استفاده از پکیج myket_in_app_purchase
  final purchaseResult = await MyketInAppPurchase.purchase(productId);
  
  if (purchaseResult.success) {
    // ارسال به سرور برای تایید
    final response = await http.post(
      Uri.parse('$baseUrl/payments/myket/verify'),
      headers: await _getHeaders(),
      body: json.encode({
        'purchase_token': purchaseResult.purchaseToken,
        'product_id': productId,
        'order_id': purchaseResult.orderId,
      }),
    );
    
    if (response.statusCode == 200) {
      // اشتراک فعال شد
      await _refreshSubscriptionStatus();
    }
  }
}
```

### 4. پرداخت از وب‌سایت (Zarinpal)

```dart
Future<void> purchaseFromWebsite(String planSlug) async {
  // همانند قبل - بدون تغییر
  // استفاده از WebView برای Zarinpal
}
```

---

## 🧪 تست و عیب‌یابی

### تست کافه‌بازار

1. استفاده از حساب تست کافه‌بازار
2. خرید محصول تست
3. بررسی لاگ‌های سرور برای تایید

### تست مایکت

1. استفاده از حساب تست مایکت
2. خرید محصول تست
3. بررسی لاگ‌های سرور

### لاگ‌ها

تمام عملیات در فایل لاگ ثبت می‌شوند:
- `storage/logs/laravel.log`

---

## 📝 نکات مهم

1. **امنیت**: همیشه تایید خرید را در سمت سرور انجام دهید
2. **Duplicate Prevention**: سیستم به صورت خودکار از ثبت مجدد خرید جلوگیری می‌کند
3. **Acknowledgment**: برای کافه‌بازار، acknowledgment الزامی است
4. **Product Mapping**: مطمئن شوید product_id ها درست map شده‌اند

---

## 🔄 Migration Checklist

- [ ] اجرای migration
- [ ] تنظیم environment variables
- [ ] تست API endpoints
- [ ] به‌روزرسانی Flutter app
- [ ] تست هر سه روش پرداخت
- [ ] بررسی لاگ‌ها
- [ ] تست در production

---

## 📞 پشتیبانی

برای سوالات و مشکلات:
- بررسی لاگ‌ها
- بررسی مستندات API
- تماس با تیم توسعه

