# SarvCast API Documentation

## Overview
SarvCast is a Persian children's audio story platform API that provides comprehensive functionality for content management, user authentication, subscriptions, and mobile app integration.

## Base URL
```
https://api.sarvcast.com/v1
```

## Authentication
SarvCast uses Laravel Sanctum for API authentication with Persian phone numbers as unique identifiers. Include the Bearer token in the Authorization header:

```
Authorization: Bearer {token}
```

### Phone Number Format
- Persian phone numbers are used as unique identifiers
- Format: +989123456789 (Iran country code + 9 + 9-digit number)
- Phone numbers are validated and normalized during registration

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

### Image Processing

#### Process Image File
```http
POST /image/process
```

**Request Body:**
```json
{
    "file_path": "images/2024/01/story_cover.jpg",
    "resize": "800,600,true",
    "quality": 90,
    "watermark": true,
    "watermark_position": "bottom-right",
    "watermark_opacity": 0.7,
    "crop": "0,0,800,600",
    "rotate": 0,
    "flip": null,
    "brightness": 0,
    "contrast": 0,
    "blur": 0,
    "sharpen": 0,
    "format": "jpg",
    "optimize": true,
    "progressive": true
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "original_file": "images/2024/01/story_cover.jpg",
        "processed_file": "images/2024/01/story_cover_processed.jpg",
        "image_info": {
            "width": 800,
            "height": 600,
            "format": "image/jpeg",
            "size": 245760,
            "aspect_ratio": 1.33,
            "orientation": "landscape",
            "colorspace": "RGB",
            "has_alpha": false
        },
        "processing_info": {
            "original_size": 524288,
            "processed_size": 245760,
            "compression_ratio": 53.13,
            "processing_options": {
                "resize": [800, 600, true],
                "quality": 90,
                "watermark": true
            },
            "processing_time": 0.5
        }
    },
    "message": "تصویر با موفقیت پردازش شد"
}
```

#### Resize Image
```http
POST /image/resize
```

**Request Body:**
```json
{
    "file_path": "images/2024/01/story_cover.jpg",
    "width": 800,
    "height": 600,
    "maintain_aspect_ratio": true
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "original_file": "images/2024/01/story_cover.jpg",
        "resized_file": "images/2024/01/story_cover_processed.jpg",
        "width": 800,
        "height": 600,
        "maintain_aspect_ratio": true
    },
    "message": "تغییر اندازه تصویر با موفقیت انجام شد"
}
```

#### Crop Image
```http
POST /image/crop
```

**Request Body:**
```json
{
    "file_path": "images/2024/01/story_cover.jpg",
    "x": 100,
    "y": 100,
    "width": 600,
    "height": 400
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "original_file": "images/2024/01/story_cover.jpg",
        "cropped_file": "images/2024/01/story_cover_processed.jpg",
        "crop_area": {
            "x": 100,
            "y": 100,
            "width": 600,
            "height": 400
        }
    },
    "message": "برش تصویر با موفقیت انجام شد"
}
```

#### Add Watermark
```http
POST /image/watermark
```

**Request Body:**
```json
{
    "file_path": "images/2024/01/story_cover.jpg",
    "watermark_path": "assets/images/watermark.png",
    "position": "bottom-right",
    "opacity": 0.7
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "original_file": "images/2024/01/story_cover.jpg",
        "watermarked_file": "images/2024/01/story_cover_processed.jpg",
        "watermark_position": "bottom-right",
        "watermark_opacity": 0.7
    },
    "message": "واترمارک با موفقیت اضافه شد"
}
```

#### Optimize Image
```http
POST /image/optimize
```

**Request Body:**
```json
{
    "file_path": "images/2024/01/story_cover.jpg",
    "quality": 85
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "original_file": "images/2024/01/story_cover.jpg",
        "optimized_file": "images/2024/01/story_cover_processed.jpg",
        "quality": 85
    },
    "message": "بهینه‌سازی تصویر با موفقیت انجام شد"
}
```

#### Generate Thumbnail
```http
POST /image/thumbnail
```

**Request Body:**
```json
{
    "file_path": "images/2024/01/story_cover.jpg",
    "width": 300,
    "height": 300
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "original_file": "images/2024/01/story_cover.jpg",
        "thumbnail_file": "images/2024/01/story_cover_processed.jpg",
        "thumbnail_size": {
            "width": 300,
            "height": 300
        }
    },
    "message": "تصویر بندانگشتی با موفقیت ایجاد شد"
}
```

#### Generate Multiple Sizes
```http
POST /image/multiple-sizes
```

**Request Body:**
```json
{
    "file_path": "images/2024/01/story_cover.jpg",
    "sizes": {
        "small": {
            "width": 300,
            "height": 300,
            "quality": 85,
            "format": "jpg"
        },
        "medium": {
            "width": 600,
            "height": 600,
            "quality": 90,
            "format": "jpg"
        },
        "large": {
            "width": 1200,
            "height": 1200,
            "quality": 95,
            "format": "jpg"
        }
    }
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "original_file": "images/2024/01/story_cover.jpg",
        "processed_files": {
            "small": "images/2024/01/story_cover_processed.jpg",
            "medium": "images/2024/01/story_cover_processed_1.jpg",
            "large": "images/2024/01/story_cover_processed_2.jpg"
        },
        "sizes": {
            "small": {
                "width": 300,
                "height": 300,
                "quality": 85,
                "format": "jpg"
            },
            "medium": {
                "width": 600,
                "height": 600,
                "quality": 90,
                "format": "jpg"
            },
            "large": {
                "width": 1200,
                "height": 1200,
                "quality": 95,
                "format": "jpg"
            }
        }
    },
    "message": "اندازه‌های مختلف با موفقیت ایجاد شد"
}
```

#### Get Image Information
```http
GET /image/info?file_path=images/2024/01/story_cover.jpg
```

**Response:**
```json
{
    "success": true,
    "data": {
        "file_path": "images/2024/01/story_cover.jpg",
        "image_info": {
            "width": 1200,
            "height": 800,
            "format": "image/jpeg",
            "size": 524288,
            "aspect_ratio": 1.5,
            "orientation": "landscape",
            "colorspace": "RGB",
            "has_alpha": false
        }
    },
    "message": "اطلاعات تصویر دریافت شد"
}
```

#### Validate Image File
```http
POST /image/validate
```

**Request Body:**
```json
{
    "file_path": "images/2024/01/story_cover.jpg"
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
            "width": 1200,
            "height": 800,
            "format": "image/jpeg",
            "size": 524288,
            "aspect_ratio": 1.5,
            "orientation": "landscape",
            "colorspace": "RGB",
            "has_alpha": false
        }
    },
    "message": "تصویر معتبر است"
}
```

#### Get Processing Statistics
```http
GET /image/stats
```

**Response:**
```json
{
    "success": true,
    "data": {
        "temp_files_count": 8,
        "temp_files_size": 10485760,
        "temp_files_size_mb": 10.0,
        "supported_formats": ["jpg", "jpeg", "png", "gif", "webp", "bmp", "tiff"],
        "default_quality": 90,
        "output_format": "jpg",
        "watermark_available": true
    },
    "message": "آمار پردازش تصویر دریافت شد"
}
```

#### Cleanup Temporary Files
```http
POST /image/cleanup
```

**Request Body:**
```json
{
    "file_paths": ["temp/images/file1.jpg", "temp/images/file2.png"]
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

### Favorites

#### Get User's Favorites
```http
GET /favorites
```

**Query Parameters:**
- `per_page` (optional): Number of favorites per page (default: 20, max: 100)

**Response:**
```json
{
    "success": true,
    "data": {
        "favorites": [
            {
                "id": 1,
                "user_id": 1,
                "story_id": 5,
                "created_at": "2024-01-15T10:00:00Z",
                "story": {
                    "id": 5,
                    "title": "ماجراجویی در جنگل جادویی",
                    "subtitle": "داستان هیجان‌انگیز کودکان",
                    "description": "داستان زیبای ماجراجویی در جنگل...",
                    "category_id": 2,
                    "age_group": "6-9",
                    "duration": 45,
                    "status": "published",
                    "is_premium": false,
                    "play_count": 2500,
                    "rating": 4.8,
                    "rating_count": 156,
                    "category": {
                        "id": 2,
                        "name": "ماجراجویی",
                        "color": "#4ECDC4"
                    },
                    "episodes": [
                        {
                            "id": 10,
                            "title": "قسمت اول: شروع ماجرا",
                            "episode_number": 1,
                            "duration": 15
                        }
                    ]
                }
            }
        ],
        "pagination": {
            "current_page": 1,
            "last_page": 3,
            "per_page": 20,
            "total": 45,
            "has_more": true
        }
    },
    "message": "لیست علاقه‌مندی‌ها دریافت شد"
}
```

#### Add Story to Favorites
```http
POST /favorites
```

**Request Body:**
```json
{
    "story_id": 5
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "story_id": 5,
        "is_favorited": true,
        "favorite_count": 1250
    },
    "message": "داستان به علاقه‌مندی‌ها اضافه شد"
}
```

#### Remove Story from Favorites
```http
DELETE /favorites/{storyId}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "story_id": 5,
        "is_favorited": false,
        "favorite_count": 1249
    },
    "message": "داستان از علاقه‌مندی‌ها حذف شد"
}
```

#### Toggle Favorite Status
```http
POST /favorites/toggle
```

**Request Body:**
```json
{
    "story_id": 5
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "story_id": 5,
        "action": "added",
        "is_favorited": true,
        "favorite_count": 1250
    },
    "message": "داستان به علاقه‌مندی‌ها اضافه شد"
}
```

#### Check Favorite Status
```http
GET /favorites/check/{storyId}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "story_id": 5,
        "is_favorited": true,
        "favorite_count": 1250
    },
    "message": "داستان در علاقه‌مندی‌ها است"
}
```

#### Get Most Favorited Stories
```http
GET /favorites/most-favorited
```

**Query Parameters:**
- `limit` (optional): Number of stories to return (default: 10, max: 50)

**Response:**
```json
{
    "success": true,
    "data": {
        "stories": [
            {
                "story": {
                    "id": 1,
                    "title": "سفیدبرفی و هفت کوتوله",
                    "subtitle": "داستان کلاسیک محبوب کودکان",
                    "description": "داستان زیبای سفیدبرفی...",
                    "category": {
                        "id": 1,
                        "name": "داستان‌های کلاسیک",
                        "color": "#FF6B6B"
                    }
                },
                "favorite_count": 2500
            },
            {
                "story": {
                    "id": 5,
                    "title": "ماجراجویی در جنگل جادویی",
                    "subtitle": "داستان هیجان‌انگیز کودکان",
                    "description": "داستان زیبای ماجراجویی...",
                    "category": {
                        "id": 2,
                        "name": "ماجراجویی",
                        "color": "#4ECDC4"
                    }
                },
                "favorite_count": 1800
            }
        ],
        "limit": 10
    },
    "message": "محبوب‌ترین داستان‌ها دریافت شد"
}
```

#### Get Favorite Statistics
```http
GET /favorites/stats
```

**Response:**
```json
{
    "success": true,
    "data": {
        "total_favorites": 45,
        "recent_favorites": 8,
        "monthly_favorites": 25,
        "most_favorited_story": {
            "story_id": 1,
            "count": 5,
            "story": {
                "id": 1,
                "title": "سفیدبرفی و هفت کوتوله",
                "subtitle": "داستان کلاسیک محبوب کودکان"
            }
        }
    },
    "message": "آمار علاقه‌مندی‌ها دریافت شد"
}
```

#### Bulk Add/Remove Favorites
```http
POST /favorites/bulk
```

**Request Body:**
```json
{
    "action": "add",
    "story_ids": [1, 2, 3, 4, 5]
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "action": "add",
        "total_processed": 5,
        "success_count": 4,
        "error_count": 1,
        "results": [
            {
                "story_id": 1,
                "action": "add",
                "success": true,
                "message": "اضافه شد"
            },
            {
                "story_id": 2,
                "action": "add",
                "success": true,
                "message": "اضافه شد"
            },
            {
                "story_id": 3,
                "action": "add",
                "success": false,
                "message": "قبلاً اضافه شده بود"
            },
            {
                "story_id": 4,
                "action": "add",
                "success": true,
                "message": "اضافه شد"
            },
            {
                "story_id": 5,
                "action": "add",
                "success": true,
                "message": "اضافه شد"
            }
        ]
    },
    "message": "عملیات add برای 4 داستان با موفقیت انجام شد"
}
```

### Play History & Progress Tracking

#### Get User's Play History
```http
GET /play-history
```

**Query Parameters:**
- `per_page` (optional): Number of records per page (default: 20, max: 100)

**Response:**
```json
{
    "success": true,
    "data": {
        "play_history": [
            {
                "id": 1,
                "user_id": 1,
                "episode_id": 10,
                "story_id": 5,
                "played_at": "2024-01-15T10:00:00Z",
                "duration_played": 900,
                "total_duration": 1200,
                "completed": false,
                "completion_percentage": 75.0,
                "remaining_time": 300,
                "device_info": {
                    "platform": "android",
                    "app_version": "1.2.0"
                },
                "episode": {
                    "id": 10,
                    "title": "قسمت اول: شروع ماجرا",
                    "episode_number": 1,
                    "duration": 1200
                },
                "story": {
                    "id": 5,
                    "title": "ماجراجویی در جنگل جادویی",
                    "category": {
                        "id": 2,
                        "name": "ماجراجویی",
                        "color": "#4ECDC4"
                    }
                }
            }
        ],
        "pagination": {
            "current_page": 1,
            "last_page": 5,
            "per_page": 20,
            "total": 85,
            "has_more": true
        }
    },
    "message": "تاریخچه پخش دریافت شد"
}
```

#### Record Play Session
```http
POST /play-history/record
```

**Request Body:**
```json
{
    "episode_id": 10,
    "duration_played": 900,
    "total_duration": 1200,
    "device_info": {
        "platform": "android",
        "app_version": "1.2.0",
        "device_model": "Samsung Galaxy S21"
    }
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "play_history_id": 1,
        "episode_id": 10,
        "story_id": 5,
        "duration_played": 900,
        "total_duration": 1200,
        "completion_percentage": 75.0,
        "completed": false,
        "played_at": "2024-01-15T10:00:00Z"
    },
    "message": "جلسه پخش ثبت شد"
}
```

#### Update Play Progress
```http
PUT /play-history/{playHistoryId}/progress
```

**Request Body:**
```json
{
    "duration_played": 1100,
    "total_duration": 1200
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "play_history_id": 1,
        "duration_played": 1100,
        "total_duration": 1200,
        "completion_percentage": 91.67,
        "completed": true,
        "remaining_time": 100,
        "played_at": "2024-01-15T10:05:00Z"
    },
    "message": "پیشرفت پخش به‌روزرسانی شد"
}
```

#### Get Recent Play History
```http
GET /play-history/recent
```

**Query Parameters:**
- `limit` (optional): Number of records to return (default: 10, max: 50)

**Response:**
```json
{
    "success": true,
    "data": {
        "recent_history": [
            {
                "id": 1,
                "episode_id": 10,
                "story_id": 5,
                "played_at": "2024-01-15T10:00:00Z",
                "duration_played": 900,
                "total_duration": 1200,
                "completed": false,
                "completion_percentage": 75.0,
                "episode": {
                    "id": 10,
                    "title": "قسمت اول: شروع ماجرا",
                    "episode_number": 1
                },
                "story": {
                    "id": 5,
                    "title": "ماجراجویی در جنگل جادویی",
                    "category": {
                        "id": 2,
                        "name": "ماجراجویی"
                    }
                }
            }
        ],
        "limit": 10
    },
    "message": "تاریخچه پخش اخیر دریافت شد"
}
```

#### Get Completed Episodes
```http
GET /play-history/completed
```

**Query Parameters:**
- `per_page` (optional): Number of records per page (default: 20, max: 100)

**Response:**
```json
{
    "success": true,
    "data": {
        "completed_episodes": [
            {
                "id": 2,
                "episode_id": 8,
                "story_id": 3,
                "played_at": "2024-01-14T15:30:00Z",
                "duration_played": 1800,
                "total_duration": 1800,
                "completed": true,
                "completion_percentage": 100.0,
                "episode": {
                    "id": 8,
                    "title": "قسمت آخر: پایان ماجرا",
                    "episode_number": 5
                },
                "story": {
                    "id": 3,
                    "title": "سفیدبرفی و هفت کوتوله",
                    "category": {
                        "id": 1,
                        "name": "داستان‌های کلاسیک"
                    }
                }
            }
        ],
        "pagination": {
            "current_page": 1,
            "last_page": 2,
            "per_page": 20,
            "total": 25,
            "has_more": true
        }
    },
    "message": "قسمت‌های تکمیل شده دریافت شد"
}
```

#### Get In-Progress Episodes
```http
GET /play-history/in-progress
```

**Query Parameters:**
- `per_page` (optional): Number of records per page (default: 20, max: 100)

**Response:**
```json
{
    "success": true,
    "data": {
        "in_progress_episodes": [
            {
                "id": 1,
                "episode_id": 10,
                "story_id": 5,
                "played_at": "2024-01-15T10:00:00Z",
                "duration_played": 900,
                "total_duration": 1200,
                "completed": false,
                "completion_percentage": 75.0,
                "episode": {
                    "id": 10,
                    "title": "قسمت اول: شروع ماجرا",
                    "episode_number": 1
                },
                "story": {
                    "id": 5,
                    "title": "ماجراجویی در جنگل جادویی",
                    "category": {
                        "id": 2,
                        "name": "ماجراجویی"
                    }
                }
            }
        ],
        "pagination": {
            "current_page": 1,
            "last_page": 1,
            "per_page": 20,
            "total": 3,
            "has_more": false
        }
    },
    "message": "قسمت‌های در حال پخش دریافت شد"
}
```

#### Get User Play Statistics
```http
GET /play-history/stats
```

**Response:**
```json
{
    "success": true,
    "data": {
        "total_plays": 85,
        "completed_plays": 25,
        "unique_stories": 15,
        "unique_episodes": 45,
        "total_play_time": 72000,
        "total_play_time_hours": 20.0,
        "recent_plays": 8,
        "completion_rate": 29.41
    },
    "message": "آمار پخش دریافت شد"
}
```

#### Get Episode Play Statistics
```http
GET /play-history/episode/{episodeId}/stats
```

**Response:**
```json
{
    "success": true,
    "data": {
        "episode_id": 10,
        "stats": {
            "total_plays": 1250,
            "completed_plays": 980,
            "unique_users": 850,
            "completion_rate": 78.4,
            "average_completion_time": 1050.5
        }
    },
    "message": "آمار پخش قسمت دریافت شد"
}
```

#### Get Story Play Statistics
```http
GET /play-history/story/{storyId}/stats
```

**Response:**
```json
{
    "success": true,
    "data": {
        "story_id": 5,
        "stats": {
            "total_plays": 3500,
            "completed_plays": 2800,
            "unique_users": 1200,
            "completion_rate": 80.0,
            "average_completion_time": 1100.0
        }
    },
    "message": "آمار پخش داستان دریافت شد"
}
```

#### Get Most Played Episodes
```http
GET /play-history/most-played
```

**Query Parameters:**
- `limit` (optional): Number of episodes to return (default: 10, max: 50)

**Response:**
```json
{
    "success": true,
    "data": {
        "most_played_episodes": [
            {
                "episode": {
                    "id": 1,
                    "title": "قسمت اول: شروع ماجرا",
                    "episode_number": 1,
                    "duration": 1200
                },
                "play_count": 2500
            },
            {
                "episode": {
                    "id": 5,
                    "title": "قسمت دوم: ادامه ماجرا",
                    "episode_number": 2,
                    "duration": 1350
                },
                "play_count": 2200
            }
        ],
        "limit": 10
    },
    "message": "پربازدیدترین قسمت‌ها دریافت شد"
}
```

#### Get Most Played Stories
```http
GET /play-history/most-played-stories
```

**Query Parameters:**
- `limit` (optional): Number of stories to return (default: 10, max: 50)

**Response:**
```json
{
    "success": true,
    "data": {
        "most_played_stories": [
            {
                "story": {
                    "id": 1,
                    "title": "سفیدبرفی و هفت کوتوله",
                    "subtitle": "داستان کلاسیک محبوب کودکان",
                    "category": {
                        "id": 1,
                        "name": "داستان‌های کلاسیک",
                        "color": "#FF6B6B"
                    }
                },
                "play_count": 5000
            },
            {
                "story": {
                    "id": 5,
                    "title": "ماجراجویی در جنگل جادویی",
                    "subtitle": "داستان هیجان‌انگیز کودکان",
                    "category": {
                        "id": 2,
                        "name": "ماجراجویی",
                        "color": "#4ECDC4"
                    }
                },
                "play_count": 3500
            }
        ],
        "limit": 10
    },
    "message": "پربازدیدترین داستان‌ها دریافت شد"
}
```

#### Get Play Analytics
```http
GET /play-history/analytics
```

**Query Parameters:**
- `days` (optional): Number of days to analyze (default: 30, max: 365)

**Response:**
```json
{
    "success": true,
    "data": {
        "period_days": 30,
        "total_plays": 15000,
        "completed_plays": 12000,
        "unique_users": 2500,
        "total_play_time": 1800000,
        "total_play_time_hours": 500.0,
        "completion_rate": 80.0,
        "daily_plays": [
            {
                "date": "2024-01-01",
                "plays": 450
            },
            {
                "date": "2024-01-02",
                "plays": 520
            }
        ]
    },
    "message": "تحلیل پخش دریافت شد"
}
```

### Rating & Review System

#### Get User's Ratings
```http
GET /ratings
```

**Query Parameters:**
- `per_page` (optional): Number of ratings per page (default: 20, max: 100)

**Response:**
```json
{
    "success": true,
    "data": {
        "ratings": [
            {
                "id": 1,
                "user_id": 1,
                "story_id": 5,
                "episode_id": null,
                "rating": 5,
                "review": "داستان بسیار زیبا و آموزنده بود. فرزندم عاشق آن شد!",
                "star_rating": "★★★★★",
                "created_at": "2024-01-15T10:00:00Z",
                "updated_at": "2024-01-15T10:00:00Z",
                "story": {
                    "id": 5,
                    "title": "ماجراجویی در جنگل جادویی",
                    "category": {
                        "id": 2,
                        "name": "ماجراجویی",
                        "color": "#4ECDC4"
                    }
                },
                "episode": null
            }
        ],
        "pagination": {
            "current_page": 1,
            "last_page": 3,
            "per_page": 20,
            "total": 45,
            "has_more": true
        }
    },
    "message": "امتیازات شما دریافت شد"
}
```

#### Submit Story Rating
```http
POST /ratings/story
```

**Request Body:**
```json
{
    "story_id": 5,
    "rating": 5,
    "review": "داستان بسیار زیبا و آموزنده بود. فرزندم عاشق آن شد!"
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "rating_id": 1,
        "story_id": 5,
        "rating": 5,
        "review": "داستان بسیار زیبا و آموزنده بود. فرزندم عاشق آن شد!",
        "star_rating": "★★★★★",
        "stats": {
            "total_ratings": 1250,
            "average_rating": 4.5,
            "rating_distribution": {
                "1": 25,
                "2": 50,
                "3": 200,
                "4": 400,
                "5": 575
            },
            "reviews_count": 850
        }
    },
    "message": "امتیاز داستان ثبت شد"
}
```

#### Submit Episode Rating
```http
POST /ratings/episode
```

**Request Body:**
```json
{
    "episode_id": 10,
    "rating": 4,
    "review": "قسمت خوبی بود اما کمی کوتاه بود."
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "rating_id": 2,
        "episode_id": 10,
        "rating": 4,
        "review": "قسمت خوبی بود اما کمی کوتاه بود.",
        "star_rating": "★★★★☆",
        "stats": {
            "total_ratings": 450,
            "average_rating": 4.2,
            "rating_distribution": {
                "1": 10,
                "2": 20,
                "3": 80,
                "4": 150,
                "5": 190
            },
            "reviews_count": 200
        }
    },
    "message": "امتیاز قسمت ثبت شد"
}
```

#### Get Story Ratings
```http
GET /ratings/story/{storyId}
```

**Query Parameters:**
- `per_page` (optional): Number of ratings per page (default: 20, max: 100)

**Response:**
```json
{
    "success": true,
    "data": {
        "story_id": 5,
        "ratings": [
            {
                "id": 1,
                "user_id": 1,
                "rating": 5,
                "review": "داستان بسیار زیبا و آموزنده بود.",
                "star_rating": "★★★★★",
                "created_at": "2024-01-15T10:00:00Z",
                "user": {
                    "id": 1,
                    "first_name": "علی",
                    "last_name": "احمدی",
                    "profile_image_url": "https://api.sarvcast.com/storage/users/user1.jpg"
                }
            }
        ],
        "stats": {
            "total_ratings": 1250,
            "average_rating": 4.5,
            "rating_distribution": {
                "1": 25,
                "2": 50,
                "3": 200,
                "4": 400,
                "5": 575
            },
            "reviews_count": 850
        },
        "pagination": {
            "current_page": 1,
            "last_page": 63,
            "per_page": 20,
            "total": 1250,
            "has_more": true
        }
    },
    "message": "امتیازات داستان دریافت شد"
}
```

#### Get Episode Ratings
```http
GET /ratings/episode/{episodeId}
```

**Query Parameters:**
- `per_page` (optional): Number of ratings per page (default: 20, max: 100)

**Response:**
```json
{
    "success": true,
    "data": {
        "episode_id": 10,
        "ratings": [
            {
                "id": 2,
                "user_id": 1,
                "rating": 4,
                "review": "قسمت خوبی بود اما کمی کوتاه بود.",
                "star_rating": "★★★★☆",
                "created_at": "2024-01-15T10:00:00Z",
                "user": {
                    "id": 1,
                    "first_name": "علی",
                    "last_name": "احمدی",
                    "profile_image_url": "https://api.sarvcast.com/storage/users/user1.jpg"
                }
            }
        ],
        "stats": {
            "total_ratings": 450,
            "average_rating": 4.2,
            "rating_distribution": {
                "1": 10,
                "2": 20,
                "3": 80,
                "4": 150,
                "5": 190
            },
            "reviews_count": 200
        },
        "pagination": {
            "current_page": 1,
            "last_page": 23,
            "per_page": 20,
            "total": 450,
            "has_more": true
        }
    },
    "message": "امتیازات قسمت دریافت شد"
}
```

#### Get User's Story Rating
```http
GET /ratings/story/{storyId}/user
```

**Response:**
```json
{
    "success": true,
    "data": {
        "story_id": 5,
        "has_rated": true,
        "rating": 5,
        "review": "داستان بسیار زیبا و آموزنده بود.",
        "star_rating": "★★★★★",
        "created_at": "2024-01-15T10:00:00Z"
    },
    "message": "امتیاز شما برای این داستان دریافت شد"
}
```

#### Get User's Episode Rating
```http
GET /ratings/episode/{episodeId}/user
```

**Response:**
```json
{
    "success": true,
    "data": {
        "episode_id": 10,
        "has_rated": true,
        "rating": 4,
        "review": "قسمت خوبی بود اما کمی کوتاه بود.",
        "star_rating": "★★★★☆",
        "created_at": "2024-01-15T10:00:00Z"
    },
    "message": "امتیاز شما برای این قسمت دریافت شد"
}
```

#### Get Highest Rated Stories
```http
GET /ratings/highest-rated-stories
```

**Query Parameters:**
- `limit` (optional): Number of stories to return (default: 10, max: 50)

**Response:**
```json
{
    "success": true,
    "data": {
        "highest_rated_stories": [
            {
                "story": {
                    "id": 1,
                    "title": "سفیدبرفی و هفت کوتوله",
                    "subtitle": "داستان کلاسیک محبوب کودکان",
                    "category": {
                        "id": 1,
                        "name": "داستان‌های کلاسیک",
                        "color": "#FF6B6B"
                    }
                },
                "average_rating": 4.8,
                "rating_count": 2500
            },
            {
                "story": {
                    "id": 5,
                    "title": "ماجراجویی در جنگل جادویی",
                    "subtitle": "داستان هیجان‌انگیز کودکان",
                    "category": {
                        "id": 2,
                        "name": "ماجراجویی",
                        "color": "#4ECDC4"
                    }
                },
                "average_rating": 4.6,
                "rating_count": 1800
            }
        ],
        "limit": 10
    },
    "message": "بالاترین امتیاز داستان‌ها دریافت شد"
}
```

#### Get Highest Rated Episodes
```http
GET /ratings/highest-rated-episodes
```

**Query Parameters:**
- `limit` (optional): Number of episodes to return (default: 10, max: 50)

**Response:**
```json
{
    "success": true,
    "data": {
        "highest_rated_episodes": [
            {
                "episode": {
                    "id": 1,
                    "title": "قسمت اول: شروع ماجرا",
                    "episode_number": 1,
                    "duration": 1200
                },
                "story": {
                    "id": 1,
                    "title": "سفیدبرفی و هفت کوتوله",
                    "category": {
                        "id": 1,
                        "name": "داستان‌های کلاسیک",
                        "color": "#FF6B6B"
                    }
                },
                "average_rating": 4.9,
                "rating_count": 800
            },
            {
                "episode": {
                    "id": 5,
                    "title": "قسمت دوم: ادامه ماجرا",
                    "episode_number": 2,
                    "duration": 1350
                },
                "story": {
                    "id": 1,
                    "title": "سفیدبرفی و هفت کوتوله",
                    "category": {
                        "id": 1,
                        "name": "داستان‌های کلاسیک",
                        "color": "#FF6B6B"
                    }
                },
                "average_rating": 4.7,
                "rating_count": 750
            }
        ],
        "limit": 10
    },
    "message": "بالاترین امتیاز قسمت‌ها دریافت شد"
}
```

#### Get Recent Reviews
```http
GET /ratings/recent-reviews
```

**Query Parameters:**
- `limit` (optional): Number of reviews to return (default: 20, max: 100)

**Response:**
```json
{
    "success": true,
    "data": {
        "recent_reviews": [
            {
                "id": 1,
                "user_id": 1,
                "story_id": 5,
                "episode_id": null,
                "rating": 5,
                "review": "داستان بسیار زیبا و آموزنده بود. فرزندم عاشق آن شد!",
                "star_rating": "★★★★★",
                "created_at": "2024-01-15T10:00:00Z",
                "user": {
                    "id": 1,
                    "first_name": "علی",
                    "last_name": "احمدی",
                    "profile_image_url": "https://api.sarvcast.com/storage/users/user1.jpg"
                },
                "story": {
                    "id": 5,
                    "title": "ماجراجویی در جنگل جادویی",
                    "category": {
                        "id": 2,
                        "name": "ماجراجویی",
                        "color": "#4ECDC4"
                    }
                },
                "episode": null
            }
        ],
        "limit": 20
    },
    "message": "آخرین نقدها دریافت شد"
}
```

#### Get User Rating Statistics
```http
GET /ratings/user-stats
```

**Response:**
```json
{
    "success": true,
    "data": {
        "total_ratings": 25,
        "reviews_count": 15,
        "average_rating_given": 4.2
    },
    "message": "آمار امتیازات شما دریافت شد"
}
```

#### Get Rating Analytics
```http
GET /ratings/analytics
```

**Query Parameters:**
- `days` (optional): Number of days to analyze (default: 30, max: 365)

**Response:**
```json
{
    "success": true,
    "data": {
        "period_days": 30,
        "total_ratings": 5000,
        "total_reviews": 2500,
        "average_rating": 4.3,
        "daily_ratings": [
            {
                "date": "2024-01-01",
                "ratings": 150
            },
            {
                "date": "2024-01-02",
                "ratings": 180
            }
        ],
        "rating_distribution": {
            "1": 100,
            "2": 200,
            "3": 800,
            "4": 1500,
            "5": 2400
        }
    },
    "message": "تحلیل امتیازات دریافت شد"
}
```

### Search & Discovery

#### Search Stories
```http
GET /search/stories
```

**Query Parameters:**
- `q` (optional): Search term
- `category_id` (optional): Filter by category ID
- `age_group` (optional): Filter by age group (3-5, 6-9, 10-12, 13+)
- `min_duration` (optional): Minimum duration in seconds
- `max_duration` (optional): Maximum duration in seconds
- `is_premium` (optional): Filter by premium status (true/false)
- `min_rating` (optional): Minimum average rating (1-5)
- `person_id` (optional): Filter by person (director, writer, narrator, etc.)
- `sort_by` (optional): Sort by field (created_at, title, duration, play_count, rating)
- `sort_order` (optional): Sort order (asc, desc)
- `per_page` (optional): Number of results per page (default: 20, max: 100)

**Response:**
```json
{
    "success": true,
    "data": {
        "stories": [
            {
                "id": 5,
                "title": "ماجراجویی در جنگل جادویی",
                "subtitle": "داستان هیجان‌انگیز کودکان",
                "description": "داستان زیبای ماجراجویی در جنگل...",
                "category_id": 2,
                "age_group": "6-9",
                "duration": 2700,
                "status": "published",
                "is_premium": false,
                "play_count": 2500,
                "avg_rating": 4.5,
                "rating_count": 156,
                "favorite_count": 1250,
                "episode_count": 5,
                "category": {
                    "id": 2,
                    "name": "ماجراجویی",
                    "color": "#4ECDC4"
                },
                "director": {
                    "id": 1,
                    "name": "علی احمدی",
                    "role": "director"
                },
                "episodes": [
                    {
                        "id": 10,
                        "title": "قسمت اول: شروع ماجرا",
                        "episode_number": 1,
                        "duration": 540
                    }
                ]
            }
        ],
        "pagination": {
            "current_page": 1,
            "last_page": 3,
            "per_page": 20,
            "total": 45,
            "has_more": true
        },
        "filters_applied": {
            "search_term": "ماجراجویی",
            "age_group": "6-9",
            "sort_by": "rating",
            "sort_order": "desc"
        }
    },
    "message": "جستجوی داستان‌ها انجام شد"
}
```

#### Search Episodes
```http
GET /search/episodes
```

**Query Parameters:**
- `q` (optional): Search term
- `story_id` (optional): Filter by story ID
- `min_duration` (optional): Minimum duration in seconds
- `max_duration` (optional): Maximum duration in seconds
- `is_premium` (optional): Filter by premium status (true/false)
- `episode_number` (optional): Filter by episode number
- `sort_by` (optional): Sort by field (title, duration, play_count, episode_number)
- `sort_order` (optional): Sort order (asc, desc)
- `per_page` (optional): Number of results per page (default: 20, max: 100)

**Response:**
```json
{
    "success": true,
    "data": {
        "episodes": [
            {
                "id": 10,
                "title": "قسمت اول: شروع ماجرا",
                "description": "شروع ماجراجویی در جنگل...",
                "episode_number": 1,
                "duration": 540,
                "status": "published",
                "is_premium": false,
                "play_count": 800,
                "avg_rating": 4.8,
                "rating_count": 45,
                "story": {
                    "id": 5,
                    "title": "ماجراجویی در جنگل جادویی",
                    "category": {
                        "id": 2,
                        "name": "ماجراجویی",
                        "color": "#4ECDC4"
                    }
                }
            }
        ],
        "pagination": {
            "current_page": 1,
            "last_page": 2,
            "per_page": 20,
            "total": 25,
            "has_more": true
        },
        "filters_applied": {
            "search_term": "ماجرا",
            "story_id": 5,
            "sort_by": "episode_number",
            "sort_order": "asc"
        }
    },
    "message": "جستجوی قسمت‌ها انجام شد"
}
```

#### Search People
```http
GET /search/people
```

**Query Parameters:**
- `q` (optional): Search term
- `role` (optional): Filter by role (director, writer, author, narrator, voice_actor)
- `sort_by` (optional): Sort by field (name, created_at)
- `sort_order` (optional): Sort order (asc, desc)
- `per_page` (optional): Number of results per page (default: 20, max: 100)

**Response:**
```json
{
    "success": true,
    "data": {
        "people": [
            {
                "id": 1,
                "name": "علی احمدی",
                "bio": "کارگردان و نویسنده با تجربه در زمینه داستان‌های کودکان",
                "role": "director",
                "image_url": "https://api.sarvcast.com/storage/people/director1.jpg",
                "story_count": 15,
                "stories": [
                    {
                        "id": 5,
                        "title": "ماجراجویی در جنگل جادویی"
                    }
                ]
            }
        ],
        "pagination": {
            "current_page": 1,
            "last_page": 1,
            "per_page": 20,
            "total": 8,
            "has_more": false
        },
        "filters_applied": {
            "search_term": "علی",
            "role": "director",
            "sort_by": "name",
            "sort_order": "asc"
        }
    },
    "message": "جستجوی افراد انجام شد"
}
```

#### Global Search
```http
GET /search/global
```

**Query Parameters:**
- `q` (required): Search term
- `limit` (optional): Number of results per type (default: 10, max: 50)

**Response:**
```json
{
    "success": true,
    "data": {
        "stories": [
            {
                "id": 5,
                "title": "ماجراجویی در جنگل جادویی",
                "subtitle": "داستان هیجان‌انگیز کودکان",
                "category": {
                    "id": 2,
                    "name": "ماجراجویی",
                    "color": "#4ECDC4"
                },
                "type": "story"
            }
        ],
        "episodes": [
            {
                "id": 10,
                "title": "قسمت اول: شروع ماجرا",
                "episode_number": 1,
                "story": {
                    "id": 5,
                    "title": "ماجراجویی در جنگل جادویی",
                    "category": {
                        "id": 2,
                        "name": "ماجراجویی"
                    }
                },
                "type": "episode"
            }
        ],
        "people": [
            {
                "id": 1,
                "name": "علی احمدی",
                "role": "director",
                "type": "person"
            }
        ],
        "categories": [
            {
                "id": 2,
                "name": "ماجراجویی",
                "description": "داستان‌های ماجراجویی و هیجان‌انگیز",
                "type": "category"
            }
        ]
    },
    "message": "جستجوی جامع انجام شد"
}
```

#### Get Search Suggestions
```http
GET /search/suggestions
```

**Query Parameters:**
- `q` (required): Search term
- `limit` (optional): Number of suggestions (default: 10, max: 20)

**Response:**
```json
{
    "success": true,
    "data": {
        "suggestions": [
            "ماجراجویی در جنگل جادویی",
            "ماجراجویی",
            "ماجراجویی در کوهستان",
            "ماجراجویی در دریا"
        ],
        "query": "ماجرا",
        "limit": 10
    },
    "message": "پیشنهادات جستجو دریافت شد"
}
```

#### Get Trending Searches
```http
GET /search/trending
```

**Query Parameters:**
- `limit` (optional): Number of trending searches (default: 10, max: 20)

**Response:**
```json
{
    "success": true,
    "data": {
        "trending_searches": [
            "سفیدبرفی و هفت کوتوله",
            "ماجراجویی در جنگل جادویی",
            "داستان‌های کلاسیک",
            "ماجراجویی",
            "داستان‌های آموزشی"
        ],
        "limit": 10
    },
    "message": "جستجوهای ترند دریافت شد"
}
```

#### Get Search Filters
```http
GET /search/filters
```

**Response:**
```json
{
    "success": true,
    "data": {
        "categories": [
            {
                "id": 1,
                "name": "داستان‌های کلاسیک",
                "color": "#FF6B6B"
            },
            {
                "id": 2,
                "name": "ماجراجویی",
                "color": "#4ECDC4"
            }
        ],
        "age_groups": ["3-5", "6-9", "10-12", "13+"],
        "duration_ranges": [
            {
                "min": 0,
                "max": 300,
                "label": "کمتر از 5 دقیقه"
            },
            {
                "min": 300,
                "max": 900,
                "label": "5-15 دقیقه"
            },
            {
                "min": 900,
                "max": 1800,
                "label": "15-30 دقیقه"
            },
            {
                "min": 1800,
                "max": 3600,
                "label": "30-60 دقیقه"
            },
            {
                "min": 3600,
                "max": null,
                "label": "بیش از 60 دقیقه"
            }
        ],
        "person_roles": ["director", "writer", "author", "narrator", "voice_actor"],
        "sort_options": [
            {
                "value": "created_at",
                "label": "جدیدترین"
            },
            {
                "value": "title",
                "label": "عنوان"
            },
            {
                "value": "duration",
                "label": "مدت زمان"
            },
            {
                "value": "play_count",
                "label": "محبوبیت"
            },
            {
                "value": "rating",
                "label": "امتیاز"
            }
        ]
    },
    "message": "فیلترهای جستجو دریافت شد"
}
```

#### Get Search Statistics
```http
GET /search/stats
```

**Response:**
```json
{
    "success": true,
    "data": {
        "total_stories": 150,
        "total_episodes": 750,
        "total_categories": 8,
        "total_people": 45,
        "most_searched_categories": [
            {
                "id": 1,
                "name": "داستان‌های کلاسیک",
                "story_count": 45
            },
            {
                "id": 2,
                "name": "ماجراجویی",
                "story_count": 35
            }
        ],
        "most_searched_people": [
            {
                "id": 1,
                "name": "علی احمدی",
                "role": "director",
                "stories_count": 15
            },
            {
                "id": 2,
                "name": "فاطمه محمدی",
                "role": "narrator",
                "stories_count": 12
            }
        ]
    },
    "message": "آمار جستجو دریافت شد"
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

#### Get Episode with Image Timeline
```http
GET /episodes/{id}?include_timeline=true
```

**Response:**
```json
{
    "success": true,
    "data": {
        "episode": {
            "id": 1,
            "title": "قسمت 1: شروع ماجرا",
            "episode_number": 1,
            "description": "قسمت اول از داستان سفیدبرفی و هفت کوتوله",
            "duration": 25,
            "is_free": true,
            "play_count": 450,
            "audio_url": "https://cdn.sarvcast.com/episodes/episode_1.mp3",
            "use_image_timeline": true,
            "created_at": "2024-01-01T00:00:00.000000Z"
        },
        "image_timeline": [
            {
                "id": 1,
                "start_time": 0,
                "end_time": 10,
                "image_url": "https://cdn.sarvcast.com/images/episode_1_scene_1.jpg",
                "image_order": 1
            },
            {
                "id": 2,
                "start_time": 11,
                "end_time": 20,
                "image_url": "https://cdn.sarvcast.com/images/episode_1_scene_2.jpg",
                "image_order": 2
            }
        ]
    }
}
```

### Voice Actor Management

#### Get Episode Voice Actors
```http
GET /episodes/{episodeId}/voice-actors
```

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
    "success": true,
    "message": "صداپیشگان قسمت دریافت شد",
    "data": {
        "episode_id": 1,
        "voice_actors": [
            {
                "id": 1,
                "person": {
                    "id": 5,
                    "name": "احمد رضایی",
                    "image_url": "https://example.com/images/ahmad.jpg",
                    "bio": "صداپیشه با تجربه"
                },
                "role": "narrator",
                "character_name": null,
                "start_time": 0,
                "end_time": 120,
                "voice_description": "صدای گرم و دوستانه",
                "is_primary": true,
                "duration": 120,
                "start_time_formatted": "00:00",
                "end_time_formatted": "02:00"
            }
        ],
        "total_duration": 300,
        "has_multiple_voice_actors": true,
        "voice_actor_count": 2
    }
}
```

#### Add Voice Actor to Episode
```http
POST /episodes/{episodeId}/voice-actors
```

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
    "person_id": 5,
    "role": "narrator",
    "character_name": null,
    "start_time": 0,
    "end_time": 120,
    "voice_description": "صدای گرم و دوستانه",
    "is_primary": true
}
```

**Response:**
```json
{
    "success": true,
    "message": "صداپیشه با موفقیت اضافه شد",
    "data": {
        "id": 1,
        "person": {
            "id": 5,
            "name": "احمد رضایی",
            "image_url": "https://example.com/images/ahmad.jpg",
            "bio": "صداپیشه با تجربه"
        },
        "role": "narrator",
        "character_name": null,
        "start_time": 0,
        "end_time": 120,
        "voice_description": "صدای گرم و دوستانه",
        "is_primary": true,
        "duration": 120
    }
}
```

#### Update Voice Actor
```http
PUT /episodes/{episodeId}/voice-actors/{voiceActorId}
```

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:** Same as Add Voice Actor

#### Delete Voice Actor
```http
DELETE /episodes/{episodeId}/voice-actors/{voiceActorId}
```

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
    "success": true,
    "message": "صداپیشه با موفقیت حذف شد"
}
```

#### Get Voice Actor for Specific Time
```http
GET /episodes/{episodeId}/voice-actor-for-time?time=60
```

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
    "success": true,
    "message": "صداپیشه برای زمان مشخص شده دریافت شد",
    "data": {
        "id": 1,
        "person": {
            "id": 5,
            "name": "احمد رضایی",
            "image_url": "https://example.com/images/ahmad.jpg"
        },
        "role": "narrator",
        "character_name": null,
        "start_time": 0,
        "end_time": 120,
        "voice_description": "صدای گرم و دوستانه",
        "is_primary": true,
        "duration": 120
    }
}
```

#### Get All Voice Actors at Specific Time
```http
GET /episodes/{episodeId}/voice-actors-at-time?time=60
```

#### Get Voice Actors by Role
```http
GET /episodes/{episodeId}/voice-actors-by-role?role=narrator
```

#### Get Voice Actor Statistics
```http
GET /episodes/{episodeId}/voice-actor-statistics
```

**Response:**
```json
{
    "success": true,
    "message": "آمار صداپیشگان قسمت دریافت شد",
    "data": {
        "total_voice_actors": 2,
        "total_duration": 300,
        "roles": {
            "narrator": {
                "count": 1,
                "total_duration": 120,
                "voice_actors": [...]
            },
            "character": {
                "count": 1,
                "total_duration": 180,
                "voice_actors": [...]
            }
        },
        "primary_voice_actor": {...},
        "voice_actor_timeline": [...]
    }
}
```

### Enhanced Image Timeline Management

#### Get Image Timeline for Episode
```http
GET /episodes/{episodeId}/image-timeline?include_voice_actors=true
```

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
    "success": true,
    "message": "تایم‌لاین تصاویر دریافت شد",
    "data": {
        "episode_id": 1,
        "image_timeline": [
            {
                "id": 1,
                "start_time": 0,
                "end_time": 45,
                "image_url": "https://cdn.sarvcast.com/images/episode_1_scene_1.jpg",
                "image_order": 1,
                "scene_description": "شروع داستان در جنگل",
                "transition_type": "fade",
                "is_key_frame": true,
                "voice_actor": {
                    "id": 1,
                    "person": {
                        "id": 5,
                        "name": "احمد رضایی"
                    },
                    "role": "narrator",
                    "character_name": null
                },
                "start_time_formatted": "00:00",
                "end_time_formatted": "00:45",
                "duration": 45
            }
        ]
    }
}
```

#### Get Timeline with Voice Actor Information
```http
GET /episodes/{episodeId}/image-timeline-with-voice-actors
```

#### Get Timeline for Specific Voice Actor
```http
GET /episodes/{episodeId}/image-timeline-for-voice-actor?voice_actor_id=1
```

#### Get Key Frames
```http
GET /episodes/{episodeId}/key-frames
```

#### Get Timeline by Transition Type
```http
GET /episodes/{episodeId}/timeline-by-transition-type?transition_type=fade
```

#### Create/Update Image Timeline
```http
POST /episodes/{episodeId}/image-timeline
```

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
    "image_timeline": [
        {
            "start_time": 0,
            "end_time": 45,
            "image_url": "https://cdn.sarvcast.com/images/episode_1_scene_1.jpg",
            "voice_actor_id": 1,
            "scene_description": "شروع داستان در جنگل",
            "transition_type": "fade",
            "is_key_frame": true
        },
        {
            "start_time": 46,
            "end_time": 90,
            "image_url": "https://cdn.sarvcast.com/images/episode_1_scene_2.jpg",
            "voice_actor_id": 2,
            "scene_description": "ملاقات با شاهزاده",
            "transition_type": "slide",
            "is_key_frame": false
        }
    ]
}
```

**Response:**
```json
{
    "success": true,
    "message": "تایم‌لاین تصاویر با موفقیت ذخیره شد",
    "data": {
        "episode_id": 1,
        "timeline_count": 2
    }
}
```

#### Delete Image Timeline
```http
DELETE /episodes/{episodeId}/image-timeline
```

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
    "success": true,
    "message": "تایم‌لاین تصاویر با موفقیت حذف شد"
}
```

#### Get Image for Specific Time
```http
GET /episodes/{episodeId}/image-for-time?time=15
```

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
    "success": true,
    "message": "تصویر برای زمان مشخص شده یافت شد",
    "data": {
        "episode_id": 1,
        "time": 15,
        "image_url": "https://cdn.sarvcast.com/images/episode_1_scene_2.jpg"
    }
}
```

### Story Comments

#### Get Story Comments
```http
GET /stories/{storyId}/comments
```

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
- `page`: Page number (default: 1)
- `per_page`: Items per page (default: 20, max: 100)
- `include_pending`: Include pending comments (default: false)

**Response:**
```json
{
    "success": true,
    "message": "نظرات داستان دریافت شد",
    "data": {
        "story_id": 1,
        "comments": [
            {
                "id": 1,
                "comment": "داستان بسیار زیبا و آموزنده بود",
                "is_approved": true,
                "is_visible": true,
                "created_at": "2024-01-01T00:00:00.000000Z",
                "time_since_created": "2 ساعت پیش",
                "user": {
                    "id": 1,
                    "name": "علی احمدی",
                    "avatar": "https://cdn.sarvcast.com/avatars/user_1.jpg"
                }
            }
        ],
        "pagination": {
            "current_page": 1,
            "per_page": 20,
            "total": 1,
            "last_page": 1,
            "has_more": false
        }
    }
}
```

#### Add Comment to Story
```http
POST /stories/{storyId}/comments
```

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
    "comment": "داستان بسیار زیبا و آموزنده بود"
}
```

**Response:**
```json
{
    "success": true,
    "message": "نظر شما با موفقیت ارسال شد و در انتظار تایید است",
    "data": {
        "comment": {
            "id": 1,
            "comment": "داستان بسیار زیبا و آموزنده بود",
            "is_approved": false,
            "is_visible": true,
            "created_at": "2024-01-01T00:00:00.000000Z",
            "time_since_created": "همین الان",
            "user": {
                "id": 1,
                "name": "علی احمدی",
                "avatar": "https://cdn.sarvcast.com/avatars/user_1.jpg"
            }
        }
    }
}
```

#### Get User's Comments
```http
GET /comments/my-comments
```

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
- `page`: Page number (default: 1)
- `per_page`: Items per page (default: 20, max: 100)

**Response:**
```json
{
    "success": true,
    "message": "نظرات کاربر دریافت شد",
    "data": {
        "comments": [
            {
                "id": 1,
                "comment": "داستان بسیار زیبا و آموزنده بود",
                "is_approved": true,
                "is_visible": true,
                "created_at": "2024-01-01T00:00:00.000000Z",
                "time_since_created": "2 ساعت پیش",
                "story": {
                    "id": 1,
                    "title": "سفیدبرفی و هفت کوتوله",
                    "image_url": "https://cdn.sarvcast.com/stories/story_1.jpg"
                }
            }
        ],
        "pagination": {
            "current_page": 1,
            "per_page": 20,
            "total": 1,
            "last_page": 1,
            "has_more": false
        }
    }
}
```

#### Delete User's Comment
```http
DELETE /comments/{commentId}
```

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
    "success": true,
    "message": "نظر با موفقیت حذف شد"
}
```

#### Get Comment Statistics
```http
GET /stories/{storyId}/comments/statistics
```

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
    "success": true,
    "message": "آمار نظرات داستان دریافت شد",
    "data": {
        "story_id": 1,
        "statistics": {
            "total_comments": 25,
            "approved_comments": 20,
            "pending_comments": 5,
            "recent_comments": 3
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
