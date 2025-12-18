# راهنمای یکپارچه‌سازی اپلیکیشن موبایل - سروکست

## مقدمه

این راهنما برای توسعه‌دهندگان اپلیکیشن موبایل سروکست تهیه شده است تا بتوانند به راحتی با API جدید یکپارچه شوند و از ویژگی‌های جدید استفاده کنند.

## تغییرات کلیدی

### 1. احراز هویت با شماره تلفن فارسی
- **تغییر**: کاربران فقط با شماره تلفن فارسی ثبت‌نام و ورود می‌کنند
- **فرمت**: +989123456789 (کد کشور ایران + 9 + 9 رقم)
- **اعتبارسنجی**: شماره تلفن‌ها به صورت خودکار اعتبارسنجی و نرمال می‌شوند

### 2. سیستم تایم‌لاین تصاویر
- **ویژگی جدید**: نمایش تصاویر مختلف بر اساس موقعیت پخش صدا
- **استفاده**: برای اپیزودهایی که `use_image_timeline: true` دارند
- **API**: دریافت تصویر مناسب برای زمان مشخص

### 3. سیستم نظرات داستان
- **ویژگی جدید**: کاربران می‌توانند روی داستان‌ها نظر بگذارند
- **تأیید**: نظرات توسط مدیران تأیید می‌شوند
- **محدودیت**: حداکثر 10 نظر در روز، فاصله 5 دقیقه بین نظرات

### 4. محدودیت‌های کاربر
- **آپلود فایل**: کاربران نمی‌توانند تصویر یا صدا آپلود کنند
- **گیمیفیکیشن**: سیستم امتیازدهی موقتاً غیرفعال است
- **لایک نظرات**: امکان لایک/دیسلایک نظرات حذف شده است

## راهنمای یکپارچه‌سازی

### مرحله 1: احراز هویت

#### ثبت‌نام کاربر
```http
POST /api/v1/auth/send-verification-code
Content-Type: application/json

{
    "phone_number": "09123456789"
}
```

```http
POST /api/v1/auth/register
Content-Type: application/json

{
    "phone_number": "09123456789",
    "verification_code": "123456",
    "first_name": "علی",
    "last_name": "احمدی"
}
```

#### ورود کاربر
```http
POST /api/v1/auth/send-verification-code
Content-Type: application/json

{
    "phone_number": "09123456789"
}
```

```http
POST /api/v1/auth/login
Content-Type: application/json

{
    "phone_number": "09123456789",
    "verification_code": "123456"
}
```

### مرحله 2: دریافت اپیزود با تایم‌لاین

#### درخواست اپیزود
```http
GET /api/v1/episodes/{episodeId}?include_timeline=true
Authorization: Bearer {token}
```

#### پاسخ نمونه
```json
{
    "success": true,
    "data": {
        "id": "uuid",
        "title": "عنوان اپیزود",
        "duration": 300,
        "use_image_timeline": true,
        "image_timeline": [
            {
                "id": 1,
                "start_time": 0,
                "end_time": 15,
                "image_url": "https://cdn.sarvcast.com/images/scene1.jpg",
                "image_order": 1
            },
            {
                "id": 2,
                "start_time": 16,
                "end_time": 30,
                "image_url": "https://cdn.sarvcast.com/images/scene2.jpg",
                "image_order": 2
            }
        ]
    }
}
```

### مرحله 3: دریافت تصویر برای زمان مشخص

#### درخواست تصویر
```http
GET /api/v1/episodes/{episodeId}/image-for-time?time=25
Authorization: Bearer {token}
```

#### پاسخ نمونه
```json
{
    "success": true,
    "data": {
        "episode_id": "uuid",
        "time": 25,
        "image_url": "https://cdn.sarvcast.com/images/scene2.jpg"
    }
}
```

### مرحله 4: سیستم نظرات

#### دریافت نظرات داستان
```http
GET /api/v1/stories/{storyId}/comments?page=1&per_page=20
Authorization: Bearer {token}
```

#### افزودن نظر
```http
POST /api/v1/stories/{storyId}/comments
Authorization: Bearer {token}
Content-Type: application/json

{
    "comment": "داستان بسیار زیبا و آموزنده بود"
}
```

#### دریافت نظرات کاربر
```http
GET /api/v1/comments/my-comments?page=1&per_page=20
Authorization: Bearer {token}
```

## پیاده‌سازی در اپلیکیشن

### 1. مدیریت احراز هویت

#### Swift (iOS)
```swift
class AuthManager {
    func sendVerificationCode(phoneNumber: String) async throws {
        let request = URLRequest(url: URL(string: "\(baseURL)/auth/send-verification-code")!)
        request.httpMethod = "POST"
        request.setValue("application/json", forHTTPHeaderField: "Content-Type")
        
        let body = ["phone_number": phoneNumber]
        request.httpBody = try JSONSerialization.data(withJSONObject: body)
        
        let (_, response) = try await URLSession.shared.data(for: request)
        // Handle response
    }
    
    func register(phoneNumber: String, code: String, firstName: String, lastName: String) async throws -> String {
        let request = URLRequest(url: URL(string: "\(baseURL)/auth/register")!)
        request.httpMethod = "POST"
        request.setValue("application/json", forHTTPHeaderField: "Content-Type")
        
        let body = [
            "phone_number": phoneNumber,
            "verification_code": code,
            "first_name": firstName,
            "last_name": lastName
        ]
        request.httpBody = try JSONSerialization.data(withJSONObject: body)
        
        let (data, _) = try await URLSession.shared.data(for: request)
        let response = try JSONDecoder().decode(AuthResponse.self, from: data)
        return response.data.token
    }
}
```

#### Kotlin (Android)
```kotlin
class AuthRepository {
    suspend fun sendVerificationCode(phoneNumber: String): Result<Unit> {
        return try {
            val request = Request.Builder()
                .url("$baseUrl/auth/send-verification-code")
                .post(jsonRequestBody(mapOf("phone_number" to phoneNumber)))
                .build()
            
            val response = httpClient.newCall(request).execute()
            if (response.isSuccessful) {
                Result.success(Unit)
            } else {
                Result.failure(Exception("Failed to send code"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }
    
    suspend fun register(phoneNumber: String, code: String, firstName: String, lastName: String): Result<String> {
        return try {
            val request = Request.Builder()
                .url("$baseUrl/auth/register")
                .post(jsonRequestBody(mapOf(
                    "phone_number" to phoneNumber,
                    "verification_code" to code,
                    "first_name" to firstName,
                    "last_name" to lastName
                )))
                .build()
            
            val response = httpClient.newCall(request).execute()
            val responseBody = response.body?.string()
            val authResponse = gson.fromJson(responseBody, AuthResponse::class.java)
            Result.success(authResponse.data.token)
        } catch (e: Exception) {
            Result.failure(e)
        }
    }
}
```

## مدل‌های داده

### Swift (iOS)
```swift
struct Episode: Codable {
    let id: String
    let title: String
    let duration: Int
    let useImageTimeline: Bool
    let imageTimeline: [TimelineEntry]?
}

struct TimelineEntry: Codable {
    let id: Int
    let startTime: Int
    let endTime: Int
    let imageUrl: String
    let imageOrder: Int
}

struct Comment: Codable {
    let id: Int
    let comment: String
    let isApproved: Bool
    let isVisible: Bool
    let createdAt: String
    let timeSinceCreated: String
    let user: CommentUser
}

struct CommentUser: Codable {
    let id: Int
    let name: String
    let avatar: String?
}
```

### Kotlin (Android)
```kotlin
data class Episode(
    val id: String,
    val title: String,
    val duration: Int,
    val useImageTimeline: Boolean,
    val imageTimeline: List<TimelineEntry>?
)

data class TimelineEntry(
    val id: Int,
    val startTime: Int,
    val endTime: Int,
    val imageUrl: String,
    val imageOrder: Int
)

data class Comment(
    val id: Int,
    val comment: String,
    val isApproved: Boolean,
    val isVisible: Boolean,
    val createdAt: String,
    val timeSinceCreated: String,
    val user: CommentUser
)

data class CommentUser(
    val id: Int,
    val name: String,
    val avatar: String?
)
```

## بهترین شیوه‌ها

### 1. مدیریت حافظه
- تصاویر تایم‌لاین را در حافظه کش کنید
- تصاویر قدیمی را از حافظه پاک کنید
- از lazy loading برای تصاویر استفاده کنید

### 2. عملکرد
- درخواست‌های API را در پس‌زمینه انجام دهید
- از pagination برای نظرات استفاده کنید
- تصاویر را به صورت پیش‌بارگذاری کنید

### 3. تجربه کاربری
- نمایش وضعیت بارگذاری
- پیام‌های خطای مناسب
- انیمیشن‌های نرم برای تغییر تصاویر

### 4. امنیت
- توکن‌های احراز هویت را امن ذخیره کنید
- از HTTPS استفاده کنید
- داده‌های حساس را رمزگذاری کنید

## پشتیبانی

### منابع کمک
- مستندات API کامل
- Postman Collection
- OpenAPI Specification
- تیم پشتیبانی فنی

### تماس با پشتیبانی
- ایمیل: dev-support@sarvcast.com
- تلفن: 021-12345678
- چت آنلاین: پنل توسعه‌دهندگان

---

**نکته مهم**: این راهنما برای نسخه 1.0.0 API تهیه شده است. برای آخرین تغییرات، به مستندات API مراجعه کنید.