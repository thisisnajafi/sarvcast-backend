# Manji Backend API Implementation Guide

## Overview
This document provides comprehensive specifications for implementing the backend APIs required for the Manji mobile application. The APIs are designed to support a children's podcast platform with stories, episodes, categories, and user interactions.

## Base Configuration
- **Base URL**: `https://my.manji.ir/api/v1`
- **Response Format**: JSON
- **Authentication**: Bearer token (for user-specific data)
- **Content-Type**: `application/json`
- **Accept**: `application/json`

---

## 1. 📚 Categories API

### GET `/categories`
Get all story categories for the home page categories section.

**Query Parameters:**
- `page` (optional, integer): Page number for pagination
- `limit` (optional, integer): Number of items per page
- `sort_by` (optional, string): Field to sort by (name, created_at, story_count)
- `sort_order` (optional, string): Sort order (asc, desc)

**Response:**
```json
{
  "success": true,
  "message": "Categories retrieved successfully",
  "data": [
    {
      "id": 1,
      "name": "داستان‌های ماجراجویی",
      "description": "داستان‌های هیجان‌انگیز و ماجراجویانه برای کودکان",
      "icon_path": "assets/icons/adventure.svg",
      "color": "#FF5722",
      "story_count": 25,
      "created_at": "2024-01-01T00:00:00Z",
      "updated_at": "2024-01-01T00:00:00Z"
    },
    {
      "id": 2,
      "name": "داستان‌های خواب",
      "description": "داستان‌های آرام و آرامش‌بخش برای خواب",
      "icon_path": "assets/icons/bedtime.svg",
      "color": "#9C27B0",
      "story_count": 18,
      "created_at": "2024-01-01T00:00:00Z",
      "updated_at": "2024-01-01T00:00:00Z"
    }
  ],
  "pagination": {
    "current_page": 1,
    "total_pages": 1,
    "total_items": 8,
    "per_page": 20
  }
}
```

### GET `/categories/{id}`
Get specific category details.

**Path Parameters:**
- `id` (integer): Category ID

**Response:**
```json
{
  "success": true,
  "message": "Category retrieved successfully",
  "data": {
    "id": 1,
    "name": "داستان‌های ماجراجویی",
    "description": "داستان‌های هیجان‌انگیز و ماجراجویانه برای کودکان",
    "icon_path": "assets/icons/adventure.svg",
    "color": "#FF5722",
    "story_count": 25,
    "created_at": "2024-01-01T00:00:00Z",
    "updated_at": "2024-01-01T00:00:00Z"
  }
}
```

### GET `/categories/{id}/stories`
Get stories belonging to a specific category.

**Path Parameters:**
- `id` (integer): Category ID

**Query Parameters:**
- `page` (optional, integer): Page number
- `limit` (optional, integer): Items per page
- `sort_by` (optional, string): Sort field
- `sort_order` (optional, string): Sort order

**Response:**
```json
{
  "success": true,
  "message": "Stories retrieved successfully",
  "data": [
    {
      "id": 1,
      "title": "ماجراجویی در جنگل",
      "subtitle": "داستانی هیجان‌انگیز برای کودکان",
      "description": "داستان کامل ماجراجویی در جنگل...",
      "image_url": "https://my.manji.ir/images/story1.jpg",
      "cover_image_url": "https://my.manji.ir/images/story1_cover.jpg",
      "duration": 1800,
      "is_premium": false,
      "rating": 4.5,
      "play_count": 1250,
      "tags": ["ماجراجویی", "جنگل", "کودکان"],
      "age_group": "6-10",
      "language": "فارسی",
      "is_favorite": false,
      "is_completely_free": true,
      "total_episodes": 5,
      "free_episodes": 5,
      "episode_count": 5,
      "progress": 0.0,
      "created_at": "2024-01-01T00:00:00Z",
      "updated_at": "2024-01-01T00:00:00Z",
      "category": {
        "id": 1,
        "name": "ماجراجویی"
      },
      "narrator": {
        "id": 1,
        "name": "علی احمدی"
      },
      "author": {
        "id": 2,
        "name": "مریم رضایی"
      },
      "director": {
        "id": 3,
        "name": "حسن محمدی"
      },
      "writer": {
        "id": 4,
        "name": "فاطمه کریمی"
      },
      "voice_actors": [
        {
          "id": 5,
          "name": "سارا نوری"
        }
      ],
      "episode_ids": [1, 2, 3, 4, 5]
    }
  ]
}
```

---

## 2. 📖 Stories API

### GET `/stories`
Get all stories with optional filtering.

**Query Parameters:**
- `page` (optional, integer): Page number
- `limit` (optional, integer): Items per page
- `sort_by` (optional, string): Sort field
- `sort_order` (optional, string): Sort order
- `type` (optional, string): Story type
- `age_group` (optional, string): Target age group
- `is_premium` (optional, boolean): Premium status
- `min_rating` (optional, float): Minimum rating

**Response:**
```json
{
  "success": true,
  "message": "Stories retrieved successfully",
  "data": [
    {
      "id": 1,
      "title": "ماجراجویی در جنگل",
      "subtitle": "داستانی هیجان‌انگیز برای کودکان",
      "description": "داستان کامل ماجراجویی در جنگل...",
      "image_url": "https://my.manji.ir/images/story1.jpg",
      "cover_image_url": "https://my.manji.ir/images/story1_cover.jpg",
      "duration": 1800,
      "is_premium": false,
      "rating": 4.5,
      "play_count": 1250,
      "tags": ["ماجراجویی", "جنگل", "کودکان"],
      "age_group": "6-10",
      "language": "فارسی",
      "is_favorite": false,
      "is_completely_free": true,
      "total_episodes": 5,
      "free_episodes": 5,
      "episode_count": 5,
      "progress": 0.0,
      "created_at": "2024-01-01T00:00:00Z",
      "updated_at": "2024-01-01T00:00:00Z",
      "category": {
        "id": 1,
        "name": "ماجراجویی"
      },
      "narrator": {
        "id": 1,
        "name": "علی احمدی"
      },
      "author": {
        "id": 2,
        "name": "مریم رضایی"
      },
      "director": {
        "id": 3,
        "name": "حسن محمدی"
      },
      "writer": {
        "id": 4,
        "name": "فاطمه کریمی"
      },
      "voice_actors": [
        {
          "id": 5,
          "name": "سارا نوری"
        }
      ],
      "episode_ids": [1, 2, 3, 4, 5]
    }
  ]
}
```

### GET `/stories/{id}`
Get specific story details.

**Path Parameters:**
- `id` (integer): Story ID

**Response:**
```json
{
  "success": true,
  "message": "Story retrieved successfully",
  "data": {
    "id": 1,
    "title": "ماجراجویی در جنگل",
    "subtitle": "داستانی هیجان‌انگیز برای کودکان",
    "description": "داستان کامل ماجراجویی در جنگل...",
    "image_url": "https://my.manji.ir/images/story1.jpg",
    "cover_image_url": "https://my.manji.ir/images/story1_cover.jpg",
    "duration": 1800,
    "is_premium": false,
    "rating": 4.5,
    "play_count": 1250,
    "tags": ["ماجراجویی", "جنگل", "کودکان"],
    "age_group": "6-10",
    "language": "فارسی",
    "is_favorite": false,
    "is_completely_free": true,
    "total_episodes": 5,
    "free_episodes": 5,
    "episode_count": 5,
    "progress": 0.0,
    "created_at": "2024-01-01T00:00:00Z",
    "updated_at": "2024-01-01T00:00:00Z",
    "category": {
      "id": 1,
      "name": "ماجراجویی"
    },
    "narrator": {
      "id": 1,
      "name": "علی احمدی"
    },
    "author": {
      "id": 2,
      "name": "مریم رضایی"
    },
    "director": {
      "id": 3,
      "name": "حسن محمدی"
    },
    "writer": {
      "id": 4,
      "name": "فاطمه کریمی"
    },
    "voice_actors": [
      {
        "id": 5,
        "name": "سارا نوری"
      }
    ],
    "episode_ids": [1, 2, 3, 4, 5]
  }
}
```

### GET `/stories/featured`
Get featured stories for the home page featured section.

**Query Parameters:**
- `limit` (optional, integer): Maximum number of featured stories to return

**Response:**
```json
{
  "success": true,
  "message": "Featured stories retrieved successfully",
  "data": [
    {
      "id": 1,
      "title": "ماجراجویی در جنگل",
      "subtitle": "داستانی هیجان‌انگیز برای کودکان",
      "description": "داستان کامل ماجراجویی در جنگل...",
      "image_url": "https://my.manji.ir/images/story1.jpg",
      "cover_image_url": "https://my.manji.ir/images/story1_cover.jpg",
      "duration": 1800,
      "is_premium": false,
      "rating": 4.5,
      "play_count": 1250,
      "tags": ["ماجراجویی", "جنگل", "کودکان"],
      "age_group": "6-10",
      "language": "فارسی",
      "is_favorite": false,
      "is_completely_free": true,
      "total_episodes": 5,
      "free_episodes": 5,
      "episode_count": 5,
      "progress": 0.0,
      "created_at": "2024-01-01T00:00:00Z",
      "updated_at": "2024-01-01T00:00:00Z",
      "category": {
        "id": 1,
        "name": "ماجراجویی"
      },
      "narrator": {
        "id": 1,
        "name": "علی احمدی"
      },
      "author": {
        "id": 2,
        "name": "مریم رضایی"
      },
      "director": {
        "id": 3,
        "name": "حسن محمدی"
      },
      "writer": {
        "id": 4,
        "name": "فاطمه کریمی"
      },
      "voice_actors": [
        {
          "id": 5,
          "name": "سارا نوری"
        }
      ],
      "episode_ids": [1, 2, 3, 4, 5]
    }
  ]
}
```

### GET `/stories/popular`
Get popular stories based on play count and ratings.

**Query Parameters:**
- `limit` (optional, integer): Maximum number of stories to return
- `period` (optional, string): Time period (daily, weekly, monthly)

**Response:**
```json
{
  "success": true,
  "message": "Popular stories retrieved successfully",
  "data": [
    {
      "id": 1,
      "title": "ماجراجویی در جنگل",
      "subtitle": "داستانی هیجان‌انگیز برای کودکان",
      "description": "داستان کامل ماجراجویی در جنگل...",
      "image_url": "https://my.manji.ir/images/story1.jpg",
      "cover_image_url": "https://my.manji.ir/images/story1_cover.jpg",
      "duration": 1800,
      "is_premium": false,
      "rating": 4.5,
      "play_count": 1250,
      "tags": ["ماجراجویی", "جنگل", "کودکان"],
      "age_group": "6-10",
      "language": "فارسی",
      "is_favorite": false,
      "is_completely_free": true,
      "total_episodes": 5,
      "free_episodes": 5,
      "episode_count": 5,
      "progress": 0.0,
      "created_at": "2024-01-01T00:00:00Z",
      "updated_at": "2024-01-01T00:00:00Z",
      "category": {
        "id": 1,
        "name": "ماجراجویی"
      },
      "narrator": {
        "id": 1,
        "name": "علی احمدی"
      },
      "author": {
        "id": 2,
        "name": "مریم رضایی"
      },
      "director": {
        "id": 3,
        "name": "حسن محمدی"
      },
      "writer": {
        "id": 4,
        "name": "فاطمه کریمی"
      },
      "voice_actors": [
        {
          "id": 5,
          "name": "سارا نوری"
        }
      ],
      "episode_ids": [1, 2, 3, 4, 5]
    }
  ]
}
```

### GET `/stories/recent`
Get recently added stories.

**Query Parameters:**
- `limit` (optional, integer): Maximum number of stories to return

**Response:**
```json
{
  "success": true,
  "message": "Recent stories retrieved successfully",
  "data": [
    {
      "id": 1,
      "title": "ماجراجویی در جنگل",
      "subtitle": "داستانی هیجان‌انگیز برای کودکان",
      "description": "داستان کامل ماجراجویی در جنگل...",
      "image_url": "https://my.manji.ir/images/story1.jpg",
      "cover_image_url": "https://my.manji.ir/images/story1_cover.jpg",
      "duration": 1800,
      "is_premium": false,
      "rating": 4.5,
      "play_count": 1250,
      "tags": ["ماجراجویی", "جنگل", "کودکان"],
      "age_group": "6-10",
      "language": "فارسی",
      "is_favorite": false,
      "is_completely_free": true,
      "total_episodes": 5,
      "free_episodes": 5,
      "episode_count": 5,
      "progress": 0.0,
      "created_at": "2024-01-01T00:00:00Z",
      "updated_at": "2024-01-01T00:00:00Z",
      "category": {
        "id": 1,
        "name": "ماجراجویی"
      },
      "narrator": {
        "id": 1,
        "name": "علی احمدی"
      },
      "author": {
        "id": 2,
        "name": "مریم رضایی"
      },
      "director": {
        "id": 3,
        "name": "حسن محمدی"
      },
      "writer": {
        "id": 4,
        "name": "فاطمه کریمی"
      },
      "voice_actors": [
        {
          "id": 5,
          "name": "سارا نوری"
        }
      ],
      "episode_ids": [1, 2, 3, 4, 5]
    }
  ]
}
```

### GET `/stories/recommendations`
Get personalized story recommendations for the user.

**Query Parameters:**
- `limit` (optional, integer): Maximum number of recommendations to return

**Response:**
```json
{
  "success": true,
  "message": "Story recommendations retrieved successfully",
  "data": [
    {
      "id": 1,
      "title": "ماجراجویی در جنگل",
      "subtitle": "داستانی هیجان‌انگیز برای کودکان",
      "description": "داستان کامل ماجراجویی در جنگل...",
      "image_url": "https://my.manji.ir/images/story1.jpg",
      "cover_image_url": "https://my.manji.ir/images/story1_cover.jpg",
      "duration": 1800,
      "is_premium": false,
      "rating": 4.5,
      "play_count": 1250,
      "tags": ["ماجراجویی", "جنگل", "کودکان"],
      "age_group": "6-10",
      "language": "فارسی",
      "is_favorite": false,
      "is_completely_free": true,
      "total_episodes": 5,
      "free_episodes": 5,
      "episode_count": 5,
      "progress": 0.0,
      "created_at": "2024-01-01T00:00:00Z",
      "updated_at": "2024-01-01T00:00:00Z",
      "category": {
        "id": 1,
        "name": "ماجراجویی"
      },
      "narrator": {
        "id": 1,
        "name": "علی احمدی"
      },
      "author": {
        "id": 2,
        "name": "مریم رضایی"
      },
      "director": {
        "id": 3,
        "name": "حسن محمدی"
      },
      "writer": {
        "id": 4,
        "name": "فاطمه کریمی"
      },
      "voice_actors": [
        {
          "id": 5,
          "name": "سارا نوری"
        }
      ],
      "episode_ids": [1, 2, 3, 4, 5]
    }
  ]
}
```

### GET `/stories/{id}/episodes`
Get episodes for a specific story.

**Path Parameters:**
- `id` (integer): Story ID

**Query Parameters:**
- `page` (optional, integer): Page number
- `limit` (optional, integer): Items per page
- `sort_by` (optional, string): Sort field
- `sort_order` (optional, string): Sort order

**Response:**
```json
{
  "success": true,
  "message": "Episodes retrieved successfully",
  "data": [
    {
      "id": 1,
      "story_id": 1,
      "title": "شروع ماجرا",
      "subtitle": "قسمت اول",
      "description": "شروع داستان ماجراجویی در جنگل...",
      "audio_url": "https://my.manji.ir/audio/episode1.mp3",
      "duration": 360,
      "episode_number": 1,
      "is_premium": false,
      "created_at": "2024-01-01T00:00:00Z",
      "updated_at": "2024-01-01T00:00:00Z"
    }
  ]
}
```

---

## 3. 🎧 Episodes API

### GET `/episodes/{id}`
Get specific episode details.

**Path Parameters:**
- `id` (integer): Episode ID

**Response:**
```json
{
  "success": true,
  "message": "Episode retrieved successfully",
  "data": {
    "id": 1,
    "story_id": 1,
    "title": "شروع ماجرا",
    "subtitle": "قسمت اول",
    "description": "شروع داستان ماجراجویی در جنگل...",
    "audio_url": "https://my.manji.ir/audio/episode1.mp3",
    "duration": 360,
    "episode_number": 1,
    "is_premium": false,
    "created_at": "2024-01-01T00:00:00Z",
    "updated_at": "2024-01-01T00:00:00Z"
  }
}
```

---

## 4. 👥 People API

### GET `/people`
Get authors, narrators, voice actors, and other people involved in content creation.

**Query Parameters:**
- `page` (optional, integer): Page number
- `limit` (optional, integer): Items per page
- `role` (optional, string): Person role (narrator, author, voice_actor, director, writer)
- `sort_by` (optional, string): Sort field
- `sort_order` (optional, string): Sort order

**Response:**
```json
{
  "success": true,
  "message": "People retrieved successfully",
  "data": [
    {
      "id": 1,
      "name": "علی احمدی",
      "role": "narrator",
      "bio": "راوی با تجربه و صداپیشه حرفه‌ای با بیش از 10 سال سابقه در زمینه داستان‌گویی برای کودکان",
      "avatar_url": "https://my.manji.ir/images/narrator1.jpg",
      "created_at": "2024-01-01T00:00:00Z",
      "updated_at": "2024-01-01T00:00:00Z"
    }
  ]
}
```

### GET `/people/{id}`
Get specific person details.

**Path Parameters:**
- `id` (integer): Person ID

**Response:**
```json
{
  "success": true,
  "message": "Person retrieved successfully",
  "data": {
    "id": 1,
    "name": "علی احمدی",
    "role": "narrator",
    "bio": "راوی با تجربه و صداپیشه حرفه‌ای با بیش از 10 سال سابقه در زمینه داستان‌گویی برای کودکان",
    "avatar_url": "https://my.manji.ir/images/narrator1.jpg",
    "created_at": "2024-01-01T00:00:00Z",
    "updated_at": "2024-01-01T00:00:00Z"
  }
}
```

### GET `/people/{id}/stories`
Get stories by a specific person.

**Path Parameters:**
- `id` (integer): Person ID

**Query Parameters:**
- `page` (optional, integer): Page number
- `limit` (optional, integer): Items per page

**Response:**
```json
{
  "success": true,
  "message": "Stories retrieved successfully",
  "data": [
    {
      "id": 1,
      "title": "ماجراجویی در جنگل",
      "subtitle": "داستانی هیجان‌انگیز برای کودکان",
      "description": "داستان کامل ماجراجویی در جنگل...",
      "image_url": "https://my.manji.ir/images/story1.jpg",
      "cover_image_url": "https://my.manji.ir/images/story1_cover.jpg",
      "duration": 1800,
      "is_premium": false,
      "rating": 4.5,
      "play_count": 1250,
      "tags": ["ماجراجویی", "جنگل", "کودکان"],
      "age_group": "6-10",
      "language": "فارسی",
      "is_favorite": false,
      "is_completely_free": true,
      "total_episodes": 5,
      "free_episodes": 5,
      "episode_count": 5,
      "progress": 0.0,
      "created_at": "2024-01-01T00:00:00Z",
      "updated_at": "2024-01-01T00:00:00Z",
      "category": {
        "id": 1,
        "name": "ماجراجویی"
      },
      "narrator": {
        "id": 1,
        "name": "علی احمدی"
      },
      "author": {
        "id": 2,
        "name": "مریم رضایی"
      },
      "director": {
        "id": 3,
        "name": "حسن محمدی"
      },
      "writer": {
        "id": 4,
        "name": "فاطمه کریمی"
      },
      "voice_actors": [
        {
          "id": 5,
          "name": "سارا نوری"
        }
      ],
      "episode_ids": [1, 2, 3, 4, 5]
    }
  ]
}
```

---

## 5. 🖼️ Image Timeline API

The image timeline feature synchronizes images with audio playback, creating an immersive visual storytelling experience for children. Images are displayed at specific time intervals during episode playback.

### GET `/episodes/{id}/image-timeline`
Get the complete image timeline for an episode.

**Path Parameters:**
- `id` (integer): Episode ID

**Response:**
```json
{
  "success": true,
  "message": "Image timeline retrieved successfully",
  "data": [
    {
      "id": 1,
      "start_time": 0,
      "end_time": 30,
      "image_url": "https://my.manji.ir/images/episode1_scene1.jpg",
      "image_order": 1
    },
    {
      "id": 2,
      "start_time": 30,
      "end_time": 60,
      "image_url": "https://my.manji.ir/images/episode1_scene2.jpg",
      "image_order": 2
    },
    {
      "id": 3,
      "start_time": 60,
      "end_time": 90,
      "image_url": "https://my.manji.ir/images/episode1_scene3.jpg",
      "image_order": 3
    }
  ]
}
```

### GET `/episodes/{id}/image-for-time`
Get the specific image that should be displayed at a given time during episode playback.

**Path Parameters:**
- `id` (integer): Episode ID

**Query Parameters:**
- `time` (required, integer): Time in seconds from episode start

**Response:**
```json
{
  "success": true,
  "message": "Image for time retrieved successfully",
  "data": {
    "id": 2,
    "start_time": 30,
    "end_time": 60,
    "image_url": "https://my.manji.ir/images/episode1_scene2.jpg",
    "image_order": 2,
    "is_current": true,
    "time_remaining": 30
  }
}
```

### POST `/episodes/{id}/image-timeline`
Create or update image timeline for an episode (Admin only).

**Path Parameters:**
- `id` (integer): Episode ID

**Request Body:**
```json
{
  "image_timeline": [
    {
      "start_time": 0,
      "end_time": 30,
      "image_url": "https://my.manji.ir/images/episode1_scene1.jpg",
      "image_order": 1
    },
    {
      "start_time": 30,
      "end_time": 60,
      "image_url": "https://my.manji.ir/images/episode1_scene2.jpg",
      "image_order": 2
    }
  ]
}
```

**Response:**
```json
{
  "success": true,
  "message": "Image timeline created successfully",
  "data": {
    "episode_id": 1,
    "total_images": 2,
    "total_duration": 60,
    "created_at": "2024-01-01T00:00:00Z"
  }
}
```

### PUT `/episodes/{id}/image-timeline`
Update existing image timeline for an episode (Admin only).

**Path Parameters:**
- `id` (integer): Episode ID

**Request Body:**
```json
{
  "image_timeline": [
    {
      "id": 1,
      "start_time": 0,
      "end_time": 35,
      "image_url": "https://my.manji.ir/images/episode1_scene1_updated.jpg",
      "image_order": 1
    },
    {
      "start_time": 35,
      "end_time": 70,
      "image_url": "https://my.manji.ir/images/episode1_scene2.jpg",
      "image_order": 2
    }
  ]
}
```

**Response:**
```json
{
  "success": true,
  "message": "Image timeline updated successfully",
  "data": {
    "episode_id": 1,
    "total_images": 2,
    "total_duration": 70,
    "updated_at": "2024-01-01T00:00:00Z"
  }
}
```

### DELETE `/episodes/{id}/image-timeline`
Delete image timeline for an episode (Admin only).

**Path Parameters:**
- `id` (integer): Episode ID

**Response:**
```json
{
  "success": true,
  "message": "Image timeline deleted successfully"
}
```

---

## 6. 🔍 Search API

### GET `/search/stories`
Search for stories based on query parameters.

**Query Parameters:**
- `q` (required, string): Search query
- `page` (optional, integer): Page number
- `limit` (optional, integer): Items per page
- `category_id` (optional, integer): Filter by category
- `age_group` (optional, string): Filter by age group
- `is_premium` (optional, boolean): Filter by premium status

**Response:**
```json
{
  "success": true,
  "message": "Search results retrieved successfully",
  "data": [
    {
      "id": 1,
      "title": "ماجراجویی در جنگل",
      "subtitle": "داستانی هیجان‌انگیز برای کودکان",
      "description": "داستان کامل ماجراجویی در جنگل...",
      "image_url": "https://my.manji.ir/images/story1.jpg",
      "cover_image_url": "https://my.manji.ir/images/story1_cover.jpg",
      "duration": 1800,
      "is_premium": false,
      "rating": 4.5,
      "play_count": 1250,
      "tags": ["ماجراجویی", "جنگل", "کودکان"],
      "age_group": "6-10",
      "language": "فارسی",
      "is_favorite": false,
      "is_completely_free": true,
      "total_episodes": 5,
      "free_episodes": 5,
      "episode_count": 5,
      "progress": 0.0,
      "created_at": "2024-01-01T00:00:00Z",
      "updated_at": "2024-01-01T00:00:00Z",
      "category": {
        "id": 1,
        "name": "ماجراجویی"
      },
      "narrator": {
        "id": 1,
        "name": "علی احمدی"
      },
      "author": {
        "id": 2,
        "name": "مریم رضایی"
      },
      "director": {
        "id": 3,
        "name": "حسن محمدی"
      },
      "writer": {
        "id": 4,
        "name": "فاطمه کریمی"
      },
      "voice_actors": [
        {
          "id": 5,
          "name": "سارا نوری"
        }
      ],
      "episode_ids": [1, 2, 3, 4, 5]
    }
  ],
  "pagination": {
    "current_page": 1,
    "total_pages": 1,
    "total_items": 1,
    "per_page": 20
  }
}
```

### GET `/search/global`
Global search across all content types.

**Query Parameters:**
- `q` (required, string): Search query
- `page` (optional, integer): Page number
- `limit` (optional, integer): Items per page

**Response:**
```json
{
  "success": true,
  "message": "Global search results retrieved successfully",
  "data": {
    "stories": [
      {
        "id": 1,
        "title": "ماجراجویی در جنگل",
        "subtitle": "داستانی هیجان‌انگیز برای کودکان",
        "image_url": "https://my.manji.ir/images/story1.jpg",
        "duration": 1800,
        "rating": 4.5,
        "category": {
          "id": 1,
          "name": "ماجراجویی"
        }
      }
    ],
    "people": [
      {
        "id": 1,
        "name": "علی احمدی",
        "role": "narrator",
        "avatar_url": "https://my.manji.ir/images/narrator1.jpg"
      }
    ],
    "categories": [
      {
        "id": 1,
        "name": "ماجراجویی",
        "description": "داستان‌های هیجان‌انگیز و ماجراجویانه",
        "icon_path": "assets/icons/adventure.svg",
        "color": "#FF5722"
      }
    ]
  }
}
```

---

## 6. 📊 Data Models

### Category Model
```json
{
  "id": 1,
  "name": "داستان‌های ماجراجویی",
  "description": "داستان‌های هیجان‌انگیز و ماجراجویانه برای کودکان",
  "icon_path": "assets/icons/adventure.svg",
  "color": "#FF5722",
  "story_count": 25,
  "created_at": "2024-01-01T00:00:00Z",
  "updated_at": "2024-01-01T00:00:00Z"
}
```

### Story Model
```json
{
  "id": 1,
  "title": "ماجراجویی در جنگل",
  "subtitle": "داستانی هیجان‌انگیز برای کودکان",
  "description": "داستان کامل ماجراجویی در جنگل...",
  "image_url": "https://my.manji.ir/images/story1.jpg",
  "cover_image_url": "https://my.manji.ir/images/story1_cover.jpg",
  "duration": 1800,
  "is_premium": false,
  "rating": 4.5,
  "play_count": 1250,
  "tags": ["ماجراجویی", "جنگل", "کودکان"],
  "age_group": "6-10",
  "language": "فارسی",
  "is_favorite": false,
  "is_completely_free": true,
  "total_episodes": 5,
  "free_episodes": 5,
  "episode_count": 5,
  "progress": 0.0,
  "created_at": "2024-01-01T00:00:00Z",
  "updated_at": "2024-01-01T00:00:00Z",
  "category": {
    "id": 1,
    "name": "ماجراجویی"
  },
  "narrator": {
    "id": 1,
    "name": "علی احمدی"
  },
  "author": {
    "id": 2,
    "name": "مریم رضایی"
  },
  "director": {
    "id": 3,
    "name": "حسن محمدی"
  },
  "writer": {
    "id": 4,
    "name": "فاطمه کریمی"
  },
  "voice_actors": [
    {
      "id": 5,
      "name": "سارا نوری"
    }
  ],
  "episode_ids": [1, 2, 3, 4, 5]
}
```

### Episode Model
```json
{
  "id": 1,
  "story_id": 1,
  "title": "شروع ماجرا",
  "subtitle": "قسمت اول",
  "description": "شروع داستان ماجراجویی در جنگل...",
  "audio_url": "https://my.manji.ir/audio/episode1.mp3",
  "duration": 360,
  "episode_number": 1,
  "is_premium": false,
  "use_image_timeline": true,
  "created_at": "2024-01-01T00:00:00Z",
  "updated_at": "2024-01-01T00:00:00Z",
  "image_timeline": [
    {
      "id": 1,
      "start_time": 0,
      "end_time": 30,
      "image_url": "https://my.manji.ir/images/episode1_scene1.jpg",
      "image_order": 1
    },
    {
      "id": 2,
      "start_time": 30,
      "end_time": 60,
      "image_url": "https://my.manji.ir/images/episode1_scene2.jpg",
      "image_order": 2
    }
  ]
}
```

### ImageTimeline Model
```json
{
  "id": 1,
  "start_time": 0,
  "end_time": 30,
  "image_url": "https://my.manji.ir/images/episode1_scene1.jpg",
  "image_order": 1
}
```

### Person Model
```json
{
  "id": 1,
  "name": "علی احمدی",
  "role": "narrator",
  "bio": "راوی با تجربه و صداپیشه حرفه‌ای با بیش از 10 سال سابقه در زمینه داستان‌گویی برای کودکان",
  "avatar_url": "https://my.manji.ir/images/narrator1.jpg",
  "created_at": "2024-01-01T00:00:00Z",
  "updated_at": "2024-01-01T00:00:00Z"
}
```

---

## 7. 🎯 Implementation Priority

### Phase 1 (Critical for Home Page)
1. ✅ **GET `/categories`** - Categories section
2. ✅ **GET `/stories/featured`** - Featured stories section
3. ✅ **GET `/stories/recent`** - Recent stories section
4. ✅ **GET `/stories/popular`** - Popular stories section
5. ✅ **GET `/categories/{id}/stories`** - Stories by category

### Phase 2 (Story Details)
6. ✅ **GET `/stories/{id}`** - Story details page
7. ✅ **GET `/stories/{id}/episodes`** - Episodes for story
8. ✅ **GET `/episodes/{id}`** - Episode details
9. ✅ **GET `/episodes/{id}/image-timeline`** - Image timeline for episodes
10. ✅ **GET `/episodes/{id}/image-for-time`** - Get image for specific time

### Phase 3 (Additional Features)
11. ✅ **GET `/people`** - Authors/narrators
12. ✅ **GET `/search/stories`** - Search functionality
13. ✅ **POST/PUT/DELETE `/episodes/{id}/image-timeline`** - Admin image timeline management

---

## 8. 📝 Sample Data Requirements

### Categories (5-8 items)
- داستان‌های ماجراجویی (Adventure Stories)
- داستان‌های خواب (Bedtime Stories)
- داستان‌های آموزشی (Educational Stories)
- داستان‌های فانتزی (Fantasy Stories)
- داستان‌های اخلاقی (Moral Stories)
- داستان‌های کلاسیک (Classic Tales)
- داستان‌های علمی (Science Stories)
- داستان‌های تاریخی (Historical Stories)

### Stories (20-30 items)
- Stories across different categories
- Mix of premium and free content
- Various age groups (3-5, 6-10, 11-15)
- Different durations (5-60 minutes)
- Multiple episodes per story

### Episodes (50-100 items)
- 3-10 episodes per story
- Various durations (5-20 minutes each)
- High-quality audio files
- Proper episode numbering
- Image timeline support (30-50% of episodes)

### Image Timeline Data (200-500 items)
- 5-15 images per episode with timeline
- Time intervals: 10-60 seconds per image
- High-resolution images (1920x1080 or higher)
- Optimized for mobile display
- Proper image ordering and timing

### People (10-15 items)
- Narrators (5-8 people)
- Authors (3-5 people)
- Voice actors (2-3 people)
- Directors (2-3 people)
- Writers (2-3 people)

---

## 9. 🔧 Technical Requirements

### Response Format
- **Content-Type**: `application/json`
- **Accept**: `application/json`
- **Character Encoding**: UTF-8
- **Date Format**: ISO 8601 (2024-01-01T00:00:00Z)

### Error Handling
```json
{
  "success": false,
  "message": "Error description in Persian",
  "errors": {
    "field_name": ["Error message"]
  },
  "code": "ERROR_CODE"
}
```

### HTTP Status Codes
- **200**: Success
- **400**: Bad Request
- **401**: Unauthorized
- **403**: Forbidden
- **404**: Not Found
- **422**: Validation Error
- **500**: Internal Server Error

### Pagination
```json
{
  "pagination": {
    "current_page": 1,
    "total_pages": 5,
    "total_items": 100,
    "per_page": 20,
    "has_next": true,
    "has_prev": false
  }
}
```

### CORS Configuration
- **Allowed Origins**: `https://my.manji.ir`, `http://localhost:*`
- **Allowed Methods**: GET, POST, PUT, DELETE, OPTIONS
- **Allowed Headers**: Content-Type, Authorization, Accept
- **Max Age**: 86400 seconds

### Rate Limiting
- **General API**: 1000 requests per hour per IP
- **Search API**: 100 requests per hour per IP
- **Authentication**: 10 requests per minute per IP

### Image Timeline Requirements
- **Image Formats**: JPEG, PNG, WebP supported
- **Image Sizes**: Multiple resolutions (1920x1080, 1280x720, 640x360)
- **CDN Integration**: Images served via CDN for fast loading
- **Caching**: Implement proper caching headers for images
- **Compression**: Optimize images for mobile bandwidth
- **Fallback**: Default image if timeline image fails to load
- **Time Precision**: Support millisecond precision for timing
- **Validation**: Ensure timeline doesn't exceed episode duration

---

## 10. 🚀 Deployment Checklist

### Database Setup
- [ ] Create database schema
- [ ] Set up indexes for performance
- [ ] Configure database connection pooling
- [ ] Set up database backups

### API Server Setup
- [ ] Configure web server (Nginx/Apache)
- [ ] Set up SSL certificates
- [ ] Configure load balancing
- [ ] Set up monitoring and logging

### Content Delivery
- [ ] Set up CDN for images and audio
- [ ] Configure file storage
- [ ] Set up image optimization
- [ ] Configure audio streaming

### Testing
- [ ] Unit tests for all endpoints
- [ ] Integration tests
- [ ] Performance testing
- [ ] Security testing

### Documentation
- [ ] API documentation (Swagger/OpenAPI)
- [ ] Database schema documentation
- [ ] Deployment guide
- [ ] Troubleshooting guide

---

## 11. 📞 Support

For questions or clarifications about this API specification, please contact the development team or refer to the main project documentation.

**Last Updated**: January 2024
**Version**: 1.0
**Status**: Ready for Implementation
