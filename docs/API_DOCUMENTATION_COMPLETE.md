# SarvCast API Documentation

## Overview
SarvCast is a Persian children's audio story platform API that provides comprehensive functionality for content management, user authentication, subscriptions, and mobile app integration.

## Base URL
```
https://api.sarvcast.com/v1
```

## Authentication
SarvCast uses Laravel Sanctum for API authentication. Include the Bearer token in the Authorization header:

```
Authorization: Bearer {token}
```

## Response Format
All API responses follow a consistent format:

### Success Response
```json
{
    "success": true,
    "data": {
        // Response data
    },
    "message": "Success message (optional)"
}
```

### Error Response
```json
{
    "success": false,
    "message": "Error message",
    "errors": {
        // Validation errors (optional)
    }
}
```

## Rate Limiting
- **Public endpoints**: 100 requests per minute per IP
- **Authenticated endpoints**: 1000 requests per minute per user
- **Admin endpoints**: 5000 requests per minute per admin

## Endpoints

### Authentication

#### Send Verification Code
```http
POST /auth/send-verification-code
```

**Request Body:**
```json
{
    "phone_number": "09123456789"
}
```

**Response:**
```json
{
    "success": true,
    "message": "کد تأیید ارسال شد"
}
```

#### Register User
```http
POST /auth/register
```

**Request Body:**
```json
{
    "phone_number": "09123456789",
    "verification_code": "123456",
    "first_name": "علی",
    "last_name": "احمدی"
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "user": {
            "id": 1,
            "phone_number": "09123456789",
            "first_name": "علی",
            "last_name": "احمدی",
            "role": "parent",
            "status": "active"
        },
        "token": "1|abc123..."
    }
}
```

#### Login User
```http
POST /auth/login
```

**Request Body:**
```json
{
    "phone_number": "09123456789",
    "verification_code": "123456"
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "user": {
            "id": 1,
            "phone_number": "09123456789",
            "first_name": "علی",
            "last_name": "احمدی",
            "role": "parent",
            "status": "active"
        },
        "token": "1|abc123..."
    }
}
```

#### Admin Login
```http
POST /auth/admin/login
```

**Request Body:**
```json
{
    "phone_number": "09123456789",
    "password": "admin123"
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "user": {
            "id": 1,
            "phone_number": "09123456789",
            "first_name": "مدیر",
            "last_name": "سیستم",
            "role": "admin",
            "status": "active"
        },
        "token": "1|abc123..."
    }
}
```

#### Logout
```http
POST /auth/logout
```

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
    "success": true,
    "message": "با موفقیت خارج شدید"
}
```

#### Get Profile
```http
GET /auth/profile
```

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "phone_number": "09123456789",
        "first_name": "علی",
        "last_name": "احمدی",
        "role": "parent",
        "status": "active",
        "preferences": {
            "language": "fa",
            "notifications": {
                "push": true,
                "email": true,
                "sms": false
            }
        },
        "created_at": "2024-01-01T00:00:00.000000Z",
        "updated_at": "2024-01-01T00:00:00.000000Z"
    }
}
```

#### Update Profile
```http
PUT /auth/profile
```

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
    "first_name": "علی",
    "last_name": "احمدی",
    "preferences": {
        "language": "fa",
        "notifications": {
            "push": true,
            "email": false,
            "sms": true
        }
    }
}
```

**Response:**
```json
{
    "success": true,
    "message": "پروفایل با موفقیت به‌روزرسانی شد",
    "data": {
        "id": 1,
        "phone_number": "09123456789",
        "first_name": "علی",
        "last_name": "احمدی",
        "role": "parent",
        "status": "active",
        "preferences": {
            "language": "fa",
            "notifications": {
                "push": true,
                "email": false,
                "sms": true
            }
        },
        "updated_at": "2024-01-01T00:00:00.000000Z"
    }
}
```

### Categories

#### Get All Categories
```http
GET /categories
```

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "داستان‌های کلاسیک",
            "slug": "classic-stories",
            "description": "داستان‌های کلاسیک و قدیمی که نسل‌هاست کودکان را سرگرم می‌کنند",
            "color": "#FF6B6B",
            "status": "active",
            "order": 1,
            "story_count": 15,
            "created_at": "2024-01-01T00:00:00.000000Z",
            "updated_at": "2024-01-01T00:00:00.000000Z"
        }
    ]
}
```

#### Get Category Stories
```http
GET /categories/{id}/stories
```

**Query Parameters:**
- `page` (optional): Page number for pagination
- `limit` (optional): Number of stories per page (default: 20)

**Response:**
```json
{
    "success": true,
    "data": {
        "stories": [
            {
                "id": 1,
                "title": "سفیدبرفی و هفت کوتوله",
                "subtitle": "داستان کلاسیک محبوب کودکان",
                "description": "داستان زیبای سفیدبرفی و هفت کوتوله...",
                "category_id": 1,
                "age_group": "3-6",
                "duration": 25,
                "status": "published",
                "is_premium": false,
                "is_completely_free": true,
                "play_count": 1250,
                "rating": 4.5,
                "rating_count": 89,
                "created_at": "2024-01-01T00:00:00.000000Z",
                "updated_at": "2024-01-01T00:00:00.000000Z",
                "category": {
                    "id": 1,
                    "name": "داستان‌های کلاسیک",
                    "color": "#FF6B6B"
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
}
```

### Stories

#### Get All Stories
```http
GET /stories
```

**Query Parameters:**
- `page` (optional): Page number for pagination
- `limit` (optional): Number of stories per page (default: 20)
- `category_id` (optional): Filter by category
- `age_group` (optional): Filter by age group
- `is_premium` (optional): Filter by premium status (true/false)
- `search` (optional): Search in title and description

**Response:**
```json
{
    "success": true,
    "data": {
        "stories": [
            {
                "id": 1,
                "title": "سفیدبرفی و هفت کوتوله",
                "subtitle": "داستان کلاسیک محبوب کودکان",
                "description": "داستان زیبای سفیدبرفی و هفت کوتوله...",
                "category_id": 1,
                "age_group": "3-6",
                "duration": 25,
                "status": "published",
                "is_premium": false,
                "is_completely_free": true,
                "play_count": 1250,
                "rating": 4.5,
                "rating_count": 89,
                "created_at": "2024-01-01T00:00:00.000000Z",
                "updated_at": "2024-01-01T00:00:00.000000Z",
                "category": {
                    "id": 1,
                    "name": "داستان‌های کلاسیک",
                    "color": "#FF6B6B"
                },
                "episodes": [
                    {
                        "id": 1,
                        "title": "قسمت 1: شروع ماجرا",
                        "episode_number": 1,
                        "duration": 25,
                        "is_free": true,
                        "play_count": 450
                    }
                ]
            }
        ],
        "pagination": {
            "current_page": 1,
            "last_page": 10,
            "per_page": 20,
            "total": 200
        }
    }
}
```

#### Get Story Details
```http
GET /stories/{id}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "title": "سفیدبرفی و هفت کوتوله",
        "subtitle": "داستان کلاسیک محبوب کودکان",
        "description": "داستان زیبای سفیدبرفی و هفت کوتوله که یکی از محبوب‌ترین داستان‌های کلاسیک کودکان است.",
        "category_id": 1,
        "age_group": "3-6",
        "duration": 25,
        "status": "published",
        "is_premium": false,
        "is_completely_free": true,
        "play_count": 1250,
        "rating": 4.5,
        "rating_count": 89,
        "created_at": "2024-01-01T00:00:00.000000Z",
        "updated_at": "2024-01-01T00:00:00.000000Z",
        "category": {
            "id": 1,
            "name": "داستان‌های کلاسیک",
            "color": "#FF6B6B"
        },
        "director": {
            "id": 1,
            "name": "احمد رضایی",
            "role": "director"
        },
        "author": {
            "id": 1,
            "name": "سارا احمدی",
            "role": "author"
        },
        "narrator": {
            "id": 1,
            "name": "مریم کریمی",
            "role": "narrator"
        },
        "episodes": [
            {
                "id": 1,
                "title": "قسمت 1: شروع ماجرا",
                "episode_number": 1,
                "duration": 25,
                "is_free": true,
                "play_count": 450,
                "audio_url": "https://cdn.sarvcast.com/episodes/episode_1.mp3"
            }
        ]
    }
}
```

#### Get Story Episodes
```http
GET /stories/{id}/episodes
```

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "title": "قسمت 1: شروع ماجرا",
            "episode_number": 1,
            "description": "قسمت اول از داستان سفیدبرفی و هفت کوتوله",
            "duration": 25,
            "is_free": true,
            "play_count": 450,
            "audio_url": "https://cdn.sarvcast.com/episodes/episode_1.mp3",
            "created_at": "2024-01-01T00:00:00.000000Z"
        }
    ]
}
```

### Episodes

#### Get Episode Details
```http
GET /episodes/{id}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "title": "قسمت 1: شروع ماجرا",
        "episode_number": 1,
        "description": "قسمت اول از داستان سفیدبرفی و هفت کوتوله",
        "duration": 25,
        "is_free": true,
        "play_count": 450,
        "audio_url": "https://cdn.sarvcast.com/episodes/episode_1.mp3",
        "created_at": "2024-01-01T00:00:00.000000Z",
        "story": {
            "id": 1,
            "title": "سفیدبرفی و هفت کوتوله",
            "category": {
                "id": 1,
                "name": "داستان‌های کلاسیک"
            }
        },
        "narrator": {
            "id": 1,
            "name": "مریم کریمی"
        }
    }
}
```

### User Management

#### Get User Favorites
```http
GET /user/favorites
```

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "story_id": 1,
            "created_at": "2024-01-01T00:00:00.000000Z",
            "story": {
                "id": 1,
                "title": "سفیدبرفی و هفت کوتوله",
                "category": {
                    "id": 1,
                    "name": "داستان‌های کلاسیک"
                }
            }
        }
    ]
}
```

#### Get User Play History
```http
GET /user/history
```

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "episode_id": 1,
            "listened_duration": 1200,
            "completed": false,
            "played_at": "2024-01-01T00:00:00.000000Z",
            "episode": {
                "id": 1,
                "title": "قسمت 1: شروع ماجرا",
                "story": {
                    "id": 1,
                    "title": "سفیدبرفی و هفت کوتوله"
                }
            }
        }
    ]
}
```

#### Create Child Profile
```http
POST /user/profiles
```

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
    "name": "سارا",
    "age": 5,
    "favorite_category_id": 1
}
```

**Response:**
```json
{
    "success": true,
    "message": "پروفایل کودک با موفقیت ایجاد شد",
    "data": {
        "id": 1,
        "name": "سارا",
        "age": 5,
        "favorite_category_id": 1,
        "user_id": 1,
        "created_at": "2024-01-01T00:00:00.000000Z"
    }
}
```

#### Get User Profiles
```http
GET /user/profiles
```

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "سارا",
            "age": 5,
            "favorite_category_id": 1,
            "user_id": 1,
            "created_at": "2024-01-01T00:00:00.000000Z",
            "favorite_category": {
                "id": 1,
                "name": "داستان‌های کلاسیک"
            }
        }
    ]
}
```

### Story Interactions

#### Add Story to Favorites
```http
POST /stories/{id}/favorite
```

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
    "success": true,
    "message": "داستان به علاقه‌مندی‌ها اضافه شد"
}
```

#### Remove Story from Favorites
```http
DELETE /stories/{id}/favorite
```

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
    "success": true,
    "message": "داستان از علاقه‌مندی‌ها حذف شد"
}
```

#### Rate Story
```http
POST /stories/{id}/rating
```

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
    "rating": 5,
    "review": "داستان بسیار زیبا و آموزنده بود"
}
```

**Response:**
```json
{
    "success": true,
    "message": "امتیاز شما ثبت شد"
}
```

### Episode Interactions

#### Track Episode Play
```http
POST /episodes/{id}/play
```

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
    "duration": 1200,
    "completed": false,
    "device_info": {
        "platform": "android",
        "version": "1.0.0"
    }
}
```

**Response:**
```json
{
    "success": true,
    "message": "پخش ثبت شد"
}
```

#### Bookmark Episode
```http
POST /episodes/{id}/bookmark
```

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
    "position": 1200
}
```

**Response:**
```json
{
    "success": true,
    "message": "نشانک اضافه شد"
}
```

#### Remove Episode Bookmark
```http
DELETE /episodes/{id}/bookmark
```

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
    "success": true,
    "message": "نشانک حذف شد"
}
```

### Subscriptions

#### Get Subscription Plans
```http
GET /subscriptions/plans
```

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": "monthly_premium",
            "name": "اشتراک ماهانه",
            "description": "دسترسی به تمام محتوای پولی",
            "price": 50000,
            "currency": "IRR",
            "duration_days": 30,
            "features": [
                "دسترسی به تمام داستان‌ها",
                "دانلود آفلاین",
                "بدون تبلیغات"
            ]
        }
    ]
}
```

#### Create Subscription
```http
POST /subscriptions
```

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
    "plan_id": "monthly_premium"
}
```

**Response:**
```json
{
    "success": true,
    "message": "اشتراک ایجاد شد",
    "data": {
        "id": 1,
        "plan_id": "monthly_premium",
        "status": "pending",
        "amount": 50000,
        "currency": "IRR",
        "start_date": "2024-01-01",
        "end_date": "2024-01-31"
    }
}
```

#### Get Current Subscription
```http
GET /subscriptions/current
```

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "plan_id": "monthly_premium",
        "status": "active",
        "amount": 50000,
        "currency": "IRR",
        "start_date": "2024-01-01",
        "end_date": "2024-01-31",
        "auto_renew": true
    }
}
```

#### Cancel Subscription
```http
POST /subscriptions/cancel
```

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
    "success": true,
    "message": "اشتراک لغو شد"
}
```

### Payments

#### Initiate Payment
```http
POST /payments/initiate
```

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
    "subscription_id": 1,
    "amount": 50000,
    "currency": "IRR"
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "payment_id": 1,
        "gateway": "zarinpal",
        "authority": "A0000000000000000000000000000000000000000",
        "payment_url": "https://www.zarinpal.com/pg/StartPay/A0000000000000000000000000000000000000000"
    }
}
```

#### Verify Payment
```http
POST /payments/verify
```

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
    "payment_id": 1,
    "authority": "A0000000000000000000000000000000000000000"
}
```

**Response:**
```json
{
    "success": true,
    "message": "پرداخت با موفقیت انجام شد",
    "data": {
        "payment_id": 1,
        "status": "completed",
        "transaction_id": "123456789",
        "amount": 50000,
        "currency": "IRR"
    }
}
```

#### Get Payment History
```http
GET /payments/history
```

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "amount": 50000,
            "currency": "IRR",
            "status": "completed",
            "payment_method": "zarinpal",
            "transaction_id": "123456789",
            "created_at": "2024-01-01T00:00:00.000000Z"
        }
    ]
}
```

### Notifications

#### Get Notifications
```http
GET /notifications
```

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "title": "داستان جدید",
            "message": "داستان جدید 'ماجراهای آلیس' منتشر شد",
            "type": "story_published",
            "read_at": null,
            "created_at": "2024-01-01T00:00:00.000000Z"
        }
    ]
}
```

#### Mark Notification as Read
```http
PUT /notifications/{id}/read
```

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
    "success": true,
    "message": "اعلان خوانده شد"
}
```

#### Mark All Notifications as Read
```http
PUT /notifications/read-all
```

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
    "success": true,
    "message": "تمام اعلان‌ها خوانده شدند"
}
```

### Mobile-Specific Endpoints

#### Get App Configuration
```http
GET /mobile/config
```

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "app_name": "SarvCast",
        "app_version": "1.0.0",
        "api_version": "v1",
        "features": {
            "offline_mode": true,
            "parental_controls": true,
            "push_notifications": true,
            "social_sharing": true,
            "downloads": true,
            "favorites": true,
            "ratings": true
        },
        "limits": {
            "max_downloads": 50,
            "max_offline_stories": 20,
            "max_offline_episodes": 100
        },
        "supported_formats": {
            "audio": ["mp3", "m4a", "wav"],
            "image": ["jpg", "jpeg", "png", "webp"]
        },
        "update_required": false,
        "maintenance_mode": false
    }
}
```

#### Search Content
```http
GET /mobile/search?q=سفیدبرفی&type=stories&limit=10
```

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "stories": [
            {
                "id": 1,
                "title": "سفیدبرفی و هفت کوتوله",
                "category": {
                    "id": 1,
                    "name": "داستان‌های کلاسیک"
                }
            }
        ]
    }
}
```

#### Get Recommendations
```http
GET /mobile/recommendations?limit=10
```

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "recommendations": [
            {
                "id": 2,
                "title": "ماجراهای آلیس در سرزمین عجایب",
                "category": {
                    "id": 4,
                    "name": "داستان‌های فانتزی"
                }
            }
        ],
        "total": 1
    }
}
```

#### Get Trending Content
```http
GET /mobile/trending?type=stories&period=week&limit=10
```

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "trending": [
            {
                "id": 1,
                "title": "سفیدبرفی و هفت کوتوله",
                "play_histories_count": 150,
                "category": {
                    "id": 1,
                    "name": "داستان‌های کلاسیک"
                }
            }
        ],
        "total": 1,
        "period": "week"
    }
}
```

#### Update User Preferences
```http
PUT /mobile/preferences
```

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
    "audio": {
        "quality": "high",
        "auto_play": true
    },
    "parental_controls": {
        "enabled": true,
        "age_limit": 8,
        "content_filter": "moderate"
    }
}
```

**Response:**
```json
{
    "success": true,
    "message": "تنظیمات با موفقیت به‌روزرسانی شد",
    "data": {
        "audio": {
            "quality": "high",
            "auto_play": true
        },
        "parental_controls": {
            "enabled": true,
            "age_limit": 8,
            "content_filter": "moderate"
        }
    }
}
```

#### Track Play Event
```http
POST /mobile/track/play
```

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
    "episode_id": 1,
    "duration": 1200,
    "completed": false,
    "device_info": {
        "platform": "android",
        "version": "1.0.0"
    }
}
```

**Response:**
```json
{
    "success": true,
    "message": "پخش ثبت شد"
}
```

#### Register Device
```http
POST /mobile/device/register
```

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
    "device_id": "unique_device_id",
    "device_type": "android",
    "device_model": "Samsung Galaxy S21",
    "os_version": "Android 12",
    "app_version": "1.0.0",
    "fcm_token": "firebase_token_here"
}
```

**Response:**
```json
{
    "success": true,
    "message": "دستگاه ثبت شد"
}
```

### Health Check Endpoints

#### Application Health
```http
GET /health
```

**Response:**
```json
{
    "status": "healthy",
    "timestamp": "2024-01-01T00:00:00.000000Z",
    "checks": {
        "database": {
            "status": "healthy",
            "response_time": 0.05,
            "message": "Database connection successful"
        },
        "redis": {
            "status": "healthy",
            "response_time": 0.01,
            "message": "Redis connection successful"
        },
        "storage": {
            "status": "healthy",
            "message": "File storage working correctly"
        }
    }
}
```

#### Application Metrics
```http
GET /health/metrics
```

**Response:**
```json
{
    "success": true,
    "data": {
        "timestamp": "2024-01-01T00:00:00.000000Z",
        "users": {
            "total": 1250,
            "active": 1100,
            "new_today": 25
        },
        "content": {
            "stories": 150,
            "episodes": 450,
            "new_stories_today": 2,
            "new_episodes_today": 5
        },
        "business": {
            "active_subscriptions": 300,
            "payments_today": 15,
            "revenue_today": 750000
        },
        "performance": {
            "memory_usage": 67108864,
            "peak_memory": 134217728,
            "execution_time": 0.125
        }
    }
}
```

## Error Codes

| Code | Description |
|------|-------------|
| 400 | Bad Request - Invalid input data |
| 401 | Unauthorized - Authentication required |
| 403 | Forbidden - Access denied |
| 404 | Not Found - Resource not found |
| 422 | Unprocessable Entity - Validation errors |
| 429 | Too Many Requests - Rate limit exceeded |
| 500 | Internal Server Error - Server error |
| 503 | Service Unavailable - Service temporarily unavailable |

## SDKs and Libraries

### JavaScript/Node.js
```bash
npm install sarvcast-api-client
```

```javascript
import SarvCastClient from 'sarvcast-api-client';

const client = new SarvCastClient({
    baseUrl: 'https://api.sarvcast.com/v1',
    apiKey: 'your-api-key'
});

// Get stories
const stories = await client.stories.getAll();
```

### PHP
```bash
composer require sarvcast/api-client
```

```php
use SarvCast\ApiClient\SarvCastClient;

$client = new SarvCastClient([
    'base_url' => 'https://api.sarvcast.com/v1',
    'api_key' => 'your-api-key'
]);

// Get stories
$stories = $client->stories()->getAll();
```

### Python
```bash
pip install sarvcast-api-client
```

```python
from sarvcast_api_client import SarvCastClient

client = SarvCastClient(
    base_url='https://api.sarvcast.com/v1',
    api_key='your-api-key'
)

# Get stories
stories = client.stories.get_all()
```

## Support

For API support and questions:
- Email: api-support@sarvcast.com
- Documentation: https://docs.sarvcast.com
- Status Page: https://status.sarvcast.com

## Changelog

### Version 1.0.0 (2024-01-01)
- Initial API release
- User authentication with SMS
- Story and episode management
- Subscription and payment integration
- Mobile app support
- Admin dashboard API
