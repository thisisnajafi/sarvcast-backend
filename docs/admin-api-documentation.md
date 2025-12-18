# مستندات API پنل مدیریت SarvCast

## فهرست مطالب

1. [معرفی API](#معرفی-api)
2. [احراز هویت](#احراز-هویت)
3. [نرخ محدودیت](#نرخ-محدودیت)
4. [مدیریت سکه‌ها](#مدیریت-سکه‌ها)
5. [مدیریت کدهای تخفیف](#مدیریت-کدهای-تخفیف)
6. [مدیریت پرداخت‌های کمیسیون](#مدیریت-پرداخت‌های-کمیسیون)
7. [مدیریت برنامه وابسته](#مدیریت-برنامه-وابسته)
8. [مدیریت پلن‌های اشتراک](#مدیریت-پلن‌های-اشتراک)
9. [مدیریت نقش‌ها](#مدیریت-نقش‌ها)
10. [کدهای خطا](#کدهای-خطا)
11. [مثال‌های کاربردی](#مثال‌های-کاربردی)

## معرفی API

API پنل مدیریت SarvCast یک RESTful API است که امکان مدیریت کامل سیستم را فراهم می‌کند. این API از استانداردهای HTTP استفاده می‌کند و پاسخ‌ها را در قالب JSON ارائه می‌دهد.

### Base URL
```
https://yourdomain.com/api/admin
```

### Headers مورد نیاز
```http
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
```

## احراز هویت

### دریافت Token
```http
POST /api/v1/auth/admin/login
Content-Type: application/json

{
    "email": "admin@example.com",
    "password": "password"
}
```

### پاسخ موفق
```json
{
    "success": true,
    "message": "ورود موفقیت‌آمیز",
    "data": {
        "user": {
            "id": 1,
            "name": "Admin User",
            "email": "admin@example.com",
            "role": "admin"
        },
        "token": "1|abcdef1234567890..."
    }
}
```

### استفاده از Token
```http
GET /api/admin/coins
Authorization: Bearer 1|abcdef1234567890...
```

## نرخ محدودیت

- **محدودیت**: 100 درخواست در دقیقه
- **Header پاسخ**: `X-RateLimit-Limit`, `X-RateLimit-Remaining`
- **کد خطا**: 429 (Too Many Requests)

### پاسخ نرخ محدودیت
```json
{
    "success": false,
    "message": "تعداد درخواست‌های شما بیش از حد مجاز است. لطفاً کمی صبر کنید.",
    "error": "RATE_LIMITED"
}
```

## مدیریت سکه‌ها

### دریافت لیست تراکنش‌های سکه

```http
GET /api/admin/coins
```

#### پارامترهای Query
- `search` (string): جستجو بر اساس نام یا ایمیل کاربر
- `type` (string): نوع تراکنش (`earned`, `purchased`, `gift`, `refund`, `admin_adjustment`)
- `status` (string): وضعیت تراکنش (`pending`, `completed`, `failed`)
- `page` (integer): شماره صفحه (پیش‌فرض: 1)
- `per_page` (integer): تعداد آیتم در هر صفحه (پیش‌فرض: 20)

#### پاسخ موفق
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "user_id": 2,
            "amount": 1000,
            "type": "earned",
            "description": "کسب سکه از کوییز",
            "status": "completed",
            "created_at": "2024-01-15T10:30:00Z",
            "updated_at": "2024-01-15T10:30:00Z",
            "user": {
                "id": 2,
                "name": "کاربر نمونه",
                "email": "user@example.com"
            }
        }
    ],
    "pagination": {
        "current_page": 1,
        "last_page": 5,
        "per_page": 20,
        "total": 100
    }
}
```

### ایجاد تراکنش سکه جدید

```http
POST /api/admin/coins
Content-Type: application/json

{
    "user_id": 2,
    "amount": 1000,
    "type": "earned",
    "description": "کسب سکه از کوییز"
}
```

#### پاسخ موفق
```json
{
    "success": true,
    "message": "سکه با موفقیت اضافه شد.",
    "data": {
        "id": 1,
        "user_id": 2,
        "amount": 1000,
        "type": "earned",
        "description": "کسب سکه از کوییز",
        "status": "completed",
        "created_at": "2024-01-15T10:30:00Z",
        "updated_at": "2024-01-15T10:30:00Z",
        "user": {
            "id": 2,
            "name": "کاربر نمونه",
            "email": "user@example.com"
        }
    }
}
```

### دریافت جزئیات تراکنش سکه

```http
GET /api/admin/coins/{id}
```

#### پاسخ موفق
```json
{
    "success": true,
    "data": {
        "id": 1,
        "user_id": 2,
        "amount": 1000,
        "type": "earned",
        "description": "کسب سکه از کوییز",
        "status": "completed",
        "created_at": "2024-01-15T10:30:00Z",
        "updated_at": "2024-01-15T10:30:00Z",
        "user": {
            "id": 2,
            "name": "کاربر نمونه",
            "email": "user@example.com"
        }
    }
}
```

### به‌روزرسانی تراکنش سکه

```http
PUT /api/admin/coins/{id}
Content-Type: application/json

{
    "amount": 1500,
    "type": "gift",
    "description": "هدیه سکه",
    "status": "completed"
}
```

#### پاسخ موفق
```json
{
    "success": true,
    "message": "تراکنش سکه با موفقیت به‌روزرسانی شد.",
    "data": {
        "id": 1,
        "user_id": 2,
        "amount": 1500,
        "type": "gift",
        "description": "هدیه سکه",
        "status": "completed",
        "created_at": "2024-01-15T10:30:00Z",
        "updated_at": "2024-01-15T11:00:00Z",
        "user": {
            "id": 2,
            "name": "کاربر نمونه",
            "email": "user@example.com"
        }
    }
}
```

### حذف تراکنش سکه

```http
DELETE /api/admin/coins/{id}
```

#### پاسخ موفق
```json
{
    "success": true,
    "message": "تراکنش سکه با موفقیت حذف شد."
}
```

### عملیات گروهی

```http
POST /api/admin/coins/bulk-action
Content-Type: application/json

{
    "action": "approve",
    "selected_items": [1, 2, 3]
}
```

#### عملیات‌های موجود
- `approve`: تایید تراکنش‌ها
- `reject`: رد تراکنش‌ها
- `delete`: حذف تراکنش‌ها

#### پاسخ موفق
```json
{
    "success": true,
    "message": "تراکنش‌های انتخاب شده با موفقیت تایید شدند."
}
```

### دریافت آمار سکه‌ها

```http
GET /api/admin/coins/statistics/data
```

#### پاسخ موفق
```json
{
    "success": true,
    "data": {
        "stats": {
            "total_transactions": 1000,
            "total_coins_earned": 50000,
            "total_coins_purchased": 30000,
            "total_coins_gifted": 10000,
            "pending_transactions": 50,
            "completed_transactions": 900,
            "failed_transactions": 50
        },
        "daily_stats": [
            {
                "date": "2024-01-15",
                "transactions": 25,
                "coins": 5000
            },
            {
                "date": "2024-01-14",
                "transactions": 30,
                "coins": 6000
            }
        ]
    }
}
```

## مدیریت کدهای تخفیف

### دریافت لیست کدهای تخفیف

```http
GET /api/admin/coupons
```

#### پارامترهای Query
- `search` (string): جستجو بر اساس کد یا توضیحات
- `type` (string): نوع تخفیف (`percentage`, `fixed_amount`, `free_coins`)
- `status` (string): وضعیت کد (`active`, `inactive`, `expired`)
- `page` (integer): شماره صفحه
- `per_page` (integer): تعداد آیتم در هر صفحه

#### پاسخ موفق
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "code": "WELCOME2024",
            "type": "percentage",
            "value": 20,
            "description": "کد خوش‌آمدگویی",
            "usage_limit": 100,
            "used_count": 25,
            "expires_at": "2024-12-31T23:59:59Z",
            "status": "active",
            "created_at": "2024-01-15T10:30:00Z"
        }
    ],
    "pagination": {
        "current_page": 1,
        "last_page": 3,
        "per_page": 20,
        "total": 50
    }
}
```

### ایجاد کد تخفیف جدید

```http
POST /api/admin/coupons
Content-Type: application/json

{
    "code": "NEWUSER2024",
    "type": "percentage",
    "value": 25,
    "description": "تخفیف کاربر جدید",
    "usage_limit": 50,
    "expires_at": "2024-12-31T23:59:59Z",
    "status": "active"
}
```

#### پاسخ موفق
```json
{
    "success": true,
    "message": "کد تخفیف با موفقیت ایجاد شد.",
    "data": {
        "id": 2,
        "code": "NEWUSER2024",
        "type": "percentage",
        "value": 25,
        "description": "تخفیف کاربر جدید",
        "usage_limit": 50,
        "used_count": 0,
        "expires_at": "2024-12-31T23:59:59Z",
        "status": "active",
        "created_at": "2024-01-15T11:00:00Z"
    }
}
```

## مدیریت پرداخت‌های کمیسیون

### دریافت لیست پرداخت‌های کمیسیون

```http
GET /api/admin/commission-payments
```

#### پارامترهای Query
- `search` (string): جستجو بر اساس نام شریک
- `status` (string): وضعیت پرداخت (`pending`, `paid`, `failed`)
- `payment_method` (string): روش پرداخت (`bank_transfer`, `paypal`, `zarinpal`, `crypto`)
- `page` (integer): شماره صفحه
- `per_page` (integer): تعداد آیتم در هر صفحه

#### پاسخ موفق
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "partner_id": 2,
            "amount": 500000,
            "commission_rate": 10,
            "payment_method": "bank_transfer",
            "status": "pending",
            "due_date": "2024-01-20T00:00:00Z",
            "paid_at": null,
            "created_at": "2024-01-15T10:30:00Z",
            "partner": {
                "id": 2,
                "name": "شریک نمونه",
                "email": "partner@example.com"
            }
        }
    ],
    "pagination": {
        "current_page": 1,
        "last_page": 2,
        "per_page": 20,
        "total": 25
    }
}
```

## مدیریت برنامه وابسته

### دریافت لیست شرکای وابسته

```http
GET /api/admin/affiliate
```

#### پارامترهای Query
- `search` (string): جستجو بر اساس نام یا ایمیل شریک
- `status` (string): وضعیت شریک (`active`, `inactive`, `suspended`)
- `tier` (string): سطح شریک (`bronze`, `silver`, `gold`, `platinum`)
- `page` (integer): شماره صفحه
- `per_page` (integer): تعداد آیتم در هر صفحه

#### پاسخ موفق
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "user_id": 2,
            "referral_code": "AFF001",
            "commission_rate": 15,
            "tier": "silver",
            "status": "active",
            "total_earnings": 1000000,
            "total_referrals": 50,
            "created_at": "2024-01-15T10:30:00Z",
            "user": {
                "id": 2,
                "name": "شریک نمونه",
                "email": "partner@example.com"
            }
        }
    ],
    "pagination": {
        "current_page": 1,
        "last_page": 3,
        "per_page": 20,
        "total": 45
    }
}
```

## مدیریت پلن‌های اشتراک

### دریافت لیست پلن‌های اشتراک

```http
GET /api/admin/subscription-plans
```

#### پارامترهای Query
- `search` (string): جستجو بر اساس نام یا توضیحات
- `type` (string): نوع پلن (`monthly`, `yearly`, `lifetime`)
- `status` (string): وضعیت پلن (`active`, `inactive`)
- `page` (integer): شماره صفحه
- `per_page` (integer): تعداد آیتم در هر صفحه

#### پاسخ موفق
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "پلن پایه",
            "description": "پلن مناسب برای کاربران عادی",
            "type": "monthly",
            "price": 50000,
            "duration_days": 30,
            "features": ["دسترسی به تمام داستان‌ها", "پشتیبانی ایمیل"],
            "max_stories": null,
            "max_episodes": null,
            "max_storage_gb": 5,
            "priority_support": false,
            "custom_domain": false,
            "analytics_access": false,
            "api_access": false,
            "status": "active",
            "sort_order": 1,
            "created_at": "2024-01-15T10:30:00Z"
        }
    ],
    "pagination": {
        "current_page": 1,
        "last_page": 2,
        "per_page": 20,
        "total": 5
    }
}
```

## مدیریت نقش‌ها

### دریافت لیست نقش‌ها

```http
GET /api/admin/roles
```

#### پارامترهای Query
- `search` (string): جستجو بر اساس نام یا توضیحات
- `status` (string): وضعیت نقش (`active`, `inactive`)
- `page` (integer): شماره صفحه
- `per_page` (integer): تعداد آیتم در هر صفحه

#### پاسخ موفق
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "content_manager",
            "display_name": "مدیر محتوا",
            "description": "مدیریت محتوا و داستان‌ها",
            "status": "active",
            "permissions": [
                {
                    "id": 1,
                    "name": "stories.create",
                    "display_name": "ایجاد داستان"
                },
                {
                    "id": 2,
                    "name": "stories.edit",
                    "display_name": "ویرایش داستان"
                }
            ],
            "created_at": "2024-01-15T10:30:00Z"
        }
    ],
    "pagination": {
        "current_page": 1,
        "last_page": 1,
        "per_page": 20,
        "total": 3
    }
}
```

## کدهای خطا

### کدهای HTTP
- `200`: موفقیت‌آمیز
- `201`: ایجاد موفقیت‌آمیز
- `400`: درخواست نامعتبر
- `401`: عدم احراز هویت
- `403`: عدم دسترسی
- `404`: یافت نشد
- `422`: خطای اعتبارسنجی
- `429`: نرخ محدودیت
- `500`: خطای سرور

### کدهای خطای سفارشی
- `UNAUTHENTICATED`: عدم احراز هویت
- `FORBIDDEN`: عدم دسترسی
- `ACCOUNT_INACTIVE`: حساب غیرفعال
- `SUPER_ADMIN_REQUIRED`: نیاز به ادمین کل
- `RATE_LIMITED`: نرخ محدودیت
- `VALIDATION_ERROR`: خطای اعتبارسنجی
- `NOT_FOUND`: یافت نشد
- `SERVER_ERROR`: خطای سرور

### نمونه پاسخ خطا
```json
{
    "success": false,
    "message": "فیلد کاربر الزامی است.",
    "error": "VALIDATION_ERROR",
    "errors": {
        "user_id": ["فیلد کاربر الزامی است."],
        "amount": ["مبلغ باید بزرگتر از 0 باشد."]
    }
}
```

## مثال‌های کاربردی

### JavaScript (Fetch API)
```javascript
// دریافت لیست سکه‌ها
async function getCoins() {
    const response = await fetch('/api/admin/coins', {
        headers: {
            'Authorization': 'Bearer ' + token,
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        }
    });
    
    const data = await response.json();
    return data;
}

// ایجاد تراکنش سکه جدید
async function createCoinTransaction(userId, amount, type, description) {
    const response = await fetch('/api/admin/coins', {
        method: 'POST',
        headers: {
            'Authorization': 'Bearer ' + token,
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            user_id: userId,
            amount: amount,
            type: type,
            description: description
        })
    });
    
    const data = await response.json();
    return data;
}
```

### PHP (cURL)
```php
// دریافت لیست سکه‌ها
function getCoins($token) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://yourdomain.com/api/admin/coins');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true);
}

// ایجاد تراکنش سکه جدید
function createCoinTransaction($token, $userId, $amount, $type, $description) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://yourdomain.com/api/admin/coins');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'user_id' => $userId,
        'amount' => $amount,
        'type' => $type,
        'description' => $description
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true);
}
```

### Python (Requests)
```python
import requests

# دریافت لیست سکه‌ها
def get_coins(token):
    headers = {
        'Authorization': f'Bearer {token}',
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    }
    
    response = requests.get('https://yourdomain.com/api/admin/coins', headers=headers)
    return response.json()

# ایجاد تراکنش سکه جدید
def create_coin_transaction(token, user_id, amount, type, description):
    headers = {
        'Authorization': f'Bearer {token}',
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    }
    
    data = {
        'user_id': user_id,
        'amount': amount,
        'type': type,
        'description': description
    }
    
    response = requests.post('https://yourdomain.com/api/admin/coins', 
                           headers=headers, json=data)
    return response.json()
```

---

**نکته**: این مستندات به صورت مداوم به‌روزرسانی می‌شود. برای آخرین نسخه، به بخش مستندات API مراجعه کنید.
