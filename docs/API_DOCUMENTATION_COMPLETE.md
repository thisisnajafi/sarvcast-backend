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

### People

#### Get All People
```http
GET /people
```

**Query Parameters:**
- `page` (optional): Page number for pagination
- `per_page` (optional): Number of people per page (default: 20, max: 100)
- `search` (optional): Search in name and bio
- `role` (optional): Filter by role (voice_actor, director, writer, producer, author, narrator)
- `verified` (optional): Filter by verification status (true/false)
- `sort_by` (optional): Sort by (name, total_stories, total_episodes, average_rating, created_at)
- `sort_order` (optional): Sort order (asc/desc)

**Response:**
```json
{
    "success": true,
    "data": {
        "people": [
            {
                "id": 1,
                "name": "علی احمدی",
                "bio": "صداپیشه و کارگردان با تجربه در زمینه داستان‌های کودکان",
                "image_url": "https://api.sarvcast.com/storage/people/person1.jpg",
                "roles": ["voice_actor", "director"],
                "total_stories": 15,
                "total_episodes": 45,
                "average_rating": 4.5,
                "is_verified": true,
                "last_active_at": "2024-01-15T10:00:00Z",
                "created_at": "2024-01-01T10:00:00Z"
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

#### Get Person Details
```http
GET /people/{id}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "name": "علی احمدی",
        "bio": "صداپیشه و کارگردان با تجربه در زمینه داستان‌های کودکان",
        "image_url": "https://api.sarvcast.com/storage/people/person1.jpg",
        "roles": ["voice_actor", "director"],
        "total_stories": 15,
        "total_episodes": 45,
        "average_rating": 4.5,
        "is_verified": true,
        "last_active_at": "2024-01-15T10:00:00Z",
        "created_at": "2024-01-01T10:00:00Z",
        "stories": [
            {
                "id": 1,
                "title": "ماجراجویی در جنگل جادویی",
                "status": "published",
                "total_episodes": 3
            }
        ],
        "episodes": [
            {
                "id": 1,
                "title": "قسمت اول: شروع ماجرا",
                "story_id": 1,
                "episode_number": 1
            }
        ]
    }
}
```

#### Search People
```http
GET /people/search
```

**Query Parameters:**
- `query` (required): Search term (min: 2, max: 100)
- `role` (optional): Filter by role
- `limit` (optional): Maximum results (default: 20, max: 50)

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "علی احمدی",
            "bio": "صداپیشه و کارگردان...",
            "image_url": "https://api.sarvcast.com/storage/people/person1.jpg",
            "roles": ["voice_actor", "director"],
            "is_verified": true
        }
    ]
}
```

#### Get People by Role
```http
GET /people/role/{role}
```

**Path Parameters:**
- `role`: Role type (voice_actor, director, writer, producer, author, narrator)

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "علی احمدی",
            "bio": "صداپیشه و کارگردان...",
            "image_url": "https://api.sarvcast.com/storage/people/person1.jpg",
            "roles": ["voice_actor", "director"],
            "is_verified": true
        }
    ]
}
```

#### Get Person Statistics
```http
GET /people/{id}/statistics
```

**Response:**
```json
{
    "success": true,
    "data": {
        "total_stories": 15,
        "total_episodes": 45,
        "average_rating": 4.5,
        "total_play_count": 1250,
        "roles": ["voice_actor", "director"],
        "is_verified": true,
        "created_at": "2024-01-01T10:00:00Z",
        "last_active_at": "2024-01-15T10:00:00Z"
    }
}
```

### File Upload

#### Upload Image
```http
POST /upload/image
```

**Request Body (multipart/form-data):**
- `image` (required): Image file (jpg, jpeg, png, gif, webp)
- `resize` (optional): Resize image (width,height) e.g., "800,600"
- `quality` (optional): Image quality (1-100, default: 90)
- `watermark` (optional): Add watermark (true/false, default: false)

**Response:**
```json
{
    "success": true,
    "data": {
        "file_id": "img_1234567890",
        "filename": "image_20240115_120000.jpg",
        "original_name": "profile.jpg",
        "path": "images/2024/01/image_20240115_120000.jpg",
        "url": "https://api.sarvcast.com/storage/images/2024/01/image_20240115_120000.jpg",
        "size": 245760,
        "mime_type": "image/jpeg",
        "dimensions": {
            "width": 800,
            "height": 600
        },
        "uploaded_at": "2024-01-15T12:00:00Z"
    },
    "message": "تصویر با موفقیت آپلود شد"
}
```

#### Upload Audio
```http
POST /upload/audio
```

**Request Body (multipart/form-data):**
- `audio` (required): Audio file (mp3, wav, m4a, aac, ogg)
- `extract_metadata` (optional): Extract metadata (true/false, default: true)
- `convert_format` (optional): Convert to mp3 (true/false, default: false)
- `bitrate` (optional): Audio bitrate (128, 192, 256, 320, default: 192)

**Response:**
```json
{
    "success": true,
    "data": {
        "file_id": "aud_1234567890",
        "filename": "audio_20240115_120000.mp3",
        "original_name": "story_episode_1.mp3",
        "path": "audio/2024/01/audio_20240115_120000.mp3",
        "url": "https://api.sarvcast.com/storage/audio/2024/01/audio_20240115_120000.mp3",
        "size": 5242880,
        "mime_type": "audio/mpeg",
        "duration": 180,
        "bitrate": 192,
        "metadata": {
            "title": "قسمت اول: شروع ماجرا",
            "artist": "علی احمدی",
            "album": "ماجراجویی در جنگل جادویی"
        },
        "uploaded_at": "2024-01-15T12:00:00Z"
    },
    "message": "فایل صوتی با موفقیت آپلود شد"
}
```

#### Upload Document
```http
POST /upload/document
```

**Request Body (multipart/form-data):**
- `document` (required): Document file (pdf, doc, docx, txt, rtf)
- `extract_text` (optional): Extract text content (true/false, default: false)
- `ocr` (optional): OCR for images in PDF (true/false, default: false)

**Response:**
```json
{
    "success": true,
    "data": {
        "file_id": "doc_1234567890",
        "filename": "document_20240115_120000.pdf",
        "original_name": "story_script.pdf",
        "path": "documents/2024/01/document_20240115_120000.pdf",
        "url": "https://api.sarvcast.com/storage/documents/2024/01/document_20240115_120000.pdf",
        "size": 1048576,
        "mime_type": "application/pdf",
        "pages": 5,
        "extracted_text": "متن استخراج شده از سند...",
        "uploaded_at": "2024-01-15T12:00:00Z"
    },
    "message": "سند با موفقیت آپلود شد"
}
```

#### Upload Multiple Files
```http
POST /upload/multiple
```

**Request Body (multipart/form-data):**
- `files[]` (required): Array of files
- `type` (required): File type (image, audio, document, mixed)
- `resize` (optional): Resize images (width,height)
- `quality` (optional): Image quality (1-100)
- `extract_metadata` (optional): Extract metadata (true/false)

**Response:**
```json
{
    "success": true,
    "data": {
        "uploaded_files": [
            {
                "file_id": "img_1234567890",
                "filename": "image_20240115_120000.jpg",
                "url": "https://api.sarvcast.com/storage/images/2024/01/image_20240115_120000.jpg",
                "size": 245760
            },
            {
                "file_id": "aud_1234567891",
                "filename": "audio_20240115_120001.mp3",
                "url": "https://api.sarvcast.com/storage/audio/2024/01/audio_20240115_120001.mp3",
                "size": 5242880
            }
        ],
        "total_files": 2,
        "total_size": 5488640,
        "uploaded_at": "2024-01-15T12:00:00Z"
    },
    "message": "2 فایل با موفقیت آپلود شد"
}
```

#### Delete File
```http
DELETE /upload/delete
```

**Request Body:**
```json
{
    "file_id": "img_1234567890"
}
```

**Response:**
```json
{
    "success": true,
    "message": "فایل با موفقیت حذف شد"
}
```

#### Get File Info
```http
GET /upload/info?file_id=img_1234567890
```

**Response:**
```json
{
    "success": true,
    "data": {
        "file_id": "img_1234567890",
        "filename": "image_20240115_120000.jpg",
        "original_name": "profile.jpg",
        "path": "images/2024/01/image_20240115_120000.jpg",
        "url": "https://api.sarvcast.com/storage/images/2024/01/image_20240115_120000.jpg",
        "size": 245760,
        "mime_type": "image/jpeg",
        "dimensions": {
            "width": 800,
            "height": 600
        },
        "uploaded_at": "2024-01-15T12:00:00Z"
    }
}
```

#### Cleanup Temp Files
```http
POST /upload/cleanup
```

**Request Body:**
```json
{
    "file_ids": ["img_1234567890", "aud_1234567891"]
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "cleaned_files": 2,
        "freed_space": 5488640
    },
    "message": "2 فایل موقت با موفقیت پاک شد"
}
```

#### Get Upload Config
```http
GET /upload/config
```

**Response:**
```json
{
    "success": true,
    "data": {
        "max_file_size": {
            "image": 5242880,
            "audio": 52428800,
            "document": 10485760
        },
        "allowed_extensions": {
            "image": ["jpg", "jpeg", "png", "gif", "webp"],
            "audio": ["mp3", "wav", "m4a", "aac", "ogg"],
            "document": ["pdf", "doc", "docx", "txt", "rtf"]
        },
        "storage_paths": {
            "image": "images",
            "audio": "audio",
            "document": "documents"
        },
        "processing_options": {
            "image_resize": true,
            "image_quality": 90,
            "audio_conversion": true,
            "audio_bitrate": 192,
            "document_ocr": false
        }
    }
}
```

### Audio Processing

#### Process Audio File
```http
POST /audio/process
```

**Request Body:**
```json
{
    "file_path": "audio/2024/01/story_episode_1.mp3",
    "convert_format": "mp3",
    "bitrate": 192,
    "normalize": true,
    "fade_in": 2,
    "fade_out": 3,
    "trim_start": 0,
    "trim_end": 180,
    "volume": 1.2,
    "quality": "high"
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "original_file": "audio/2024/01/story_episode_1.mp3",
        "processed_file": "audio/2024/01/story_episode_1_processed.mp3",
        "metadata": {
            "duration": 180,
            "bitrate": 192000,
            "sample_rate": 44100,
            "channels": 2,
            "format": "mp3",
            "title": "قسمت اول: شروع ماجرا",
            "artist": "علی احمدی"
        },
        "processing_info": {
            "command": "ffmpeg -i ...",
            "execution_time": 15.5,
            "output_size": 5242880,
            "processing_options": {
                "convert_format": "mp3",
                "bitrate": 192,
                "normalize": true
            }
        }
    },
    "message": "فایل صوتی با موفقیت پردازش شد"
}
```

#### Extract Metadata
```http
POST /audio/extract-metadata
```

**Request Body:**
```json
{
    "file_path": "audio/2024/01/story_episode_1.mp3"
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "file_path": "audio/2024/01/story_episode_1.mp3",
        "metadata": {
            "duration": 180.5,
            "bitrate": 192000,
            "sample_rate": 44100,
            "channels": 2,
            "format": "mp3",
            "title": "قسمت اول: شروع ماجرا",
            "artist": "علی احمدی",
            "album": "ماجراجویی در جنگل جادویی",
            "year": "2024",
            "genre": "Children's Story"
        }
    },
    "message": "متادیتا با موفقیت استخراج شد"
}
```

#### Convert Format
```http
POST /audio/convert
```

**Request Body:**
```json
{
    "file_path": "audio/2024/01/story_episode_1.wav",
    "target_format": "mp3",
    "bitrate": 192
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "original_file": "audio/2024/01/story_episode_1.wav",
        "converted_file": "audio/2024/01/story_episode_1_processed.mp3",
        "target_format": "mp3",
        "bitrate": 192
    },
    "message": "تبدیل فرمت با موفقیت انجام شد"
}
```

#### Normalize Audio
```http
POST /audio/normalize
```

**Request Body:**
```json
{
    "file_path": "audio/2024/01/story_episode_1.mp3"
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "original_file": "audio/2024/01/story_episode_1.mp3",
        "normalized_file": "audio/2024/01/story_episode_1_processed.mp3"
    },
    "message": "نرمال‌سازی صدا با موفقیت انجام شد"
}
```

#### Trim Audio
```http
POST /audio/trim
```

**Request Body:**
```json
{
    "file_path": "audio/2024/01/story_episode_1.mp3",
    "start_seconds": 10,
    "end_seconds": 170
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "original_file": "audio/2024/01/story_episode_1.mp3",
        "trimmed_file": "audio/2024/01/story_episode_1_processed.mp3",
        "start_seconds": 10,
        "end_seconds": 170
    },
    "message": "برش فایل صوتی با موفقیت انجام شد"
}
```

#### Validate Audio File
```http
POST /audio/validate
```

**Request Body:**
```json
{
    "file_path": "audio/2024/01/story_episode_1.mp3"
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "valid": true,
        "errors": [],
        "warnings": [],
        "info": {
            "duration": 180,
            "bitrate": 192000,
            "sample_rate": 44100,
            "channels": 2,
            "format": "mp3"
        }
    },
    "message": "فایل صوتی معتبر است"
}
```

#### Get Processing Statistics
```http
GET /audio/stats
```

**Response:**
```json
{
    "success": true,
    "data": {
        "temp_files_count": 5,
        "temp_files_size": 52428800,
        "temp_files_size_mb": 50.0,
        "supported_formats": ["mp3", "wav", "m4a", "aac", "ogg", "flac"],
        "default_bitrate": 192,
        "output_format": "mp3"
    },
    "message": "آمار پردازش صدا دریافت شد"
}
```

#### Cleanup Temporary Files
```http
POST /audio/cleanup
```

**Request Body:**
```json
{
    "file_paths": ["temp/audio/file1.mp3", "temp/audio/file2.wav"]
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "cleaned_files": 2
    },
    "message": "2 فایل موقت پاک شد"
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
