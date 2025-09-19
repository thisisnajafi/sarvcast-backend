# SarvCast API Documentation

## Overview

SarvCast is a Persian children's audio story platform that provides a comprehensive API for managing stories, episodes, users, subscriptions, and more. This documentation covers all available API endpoints, authentication methods, and usage examples.

## Base URL

```
Production: https://sarvcast.com/api/v1
Development: http://localhost:8000/api/v1
```

## Authentication

SarvCast uses Laravel Sanctum for API authentication. All protected endpoints require a valid Bearer token.

### Getting an Access Token

```http
POST /api/v1/auth/login
Content-Type: application/json

{
    "phone": "+989123456789",
    "password": "your_password"
}
```

### Using the Access Token

Include the token in the Authorization header:

```http
Authorization: Bearer your_access_token_here
```

## Response Format

All API responses follow a consistent format:

### Success Response

```json
{
    "success": true,
    "message": "Operation completed successfully",
    "data": {
        // Response data
    },
    "meta": {
        "pagination": {
            "current_page": 1,
            "per_page": 15,
            "total": 100,
            "last_page": 7
        }
    }
}
```

### Error Response

```json
{
    "success": false,
    "message": "Error description",
    "errors": {
        "field_name": ["Validation error message"]
    }
}
```

## Rate Limiting

API requests are rate limited to prevent abuse:

- **General API**: 100 requests per minute
- **Authentication**: 5 requests per minute
- **File Upload**: 10 requests per minute

Rate limit headers are included in responses:

```
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 95
X-RateLimit-Reset: 1640995200
```

## Pagination

List endpoints support pagination with the following parameters:

- `page`: Page number (default: 1)
- `per_page`: Items per page (default: 15, max: 100)

## Filtering and Sorting

Many endpoints support filtering and sorting:

- `filter[field]`: Filter by specific field
- `sort`: Sort field (prefix with `-` for descending)
- `search`: Global search term

## Error Codes

| Code | Description |
|------|-------------|
| 200 | Success |
| 201 | Created |
| 400 | Bad Request |
| 401 | Unauthorized |
| 403 | Forbidden |
| 404 | Not Found |
| 422 | Validation Error |
| 429 | Too Many Requests |
| 500 | Internal Server Error |

## API Endpoints

### Authentication

#### Register User
```http
POST /api/v1/auth/register
```

**Request Body:**
```json
{
    "name": "John Doe",
    "phone": "+989123456789",
    "password": "password123",
    "password_confirmation": "password123",
    "email": "john@example.com"
}
```

**Response:**
```json
{
    "success": true,
    "message": "User registered successfully",
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "phone": "+989123456789",
            "email": "john@example.com",
            "created_at": "2023-01-01T00:00:00.000000Z"
        },
        "token": "1|abc123..."
    }
}
```

#### Login User
```http
POST /api/v1/auth/login
```

**Request Body:**
```json
{
    "phone": "+989123456789",
    "password": "password123"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Login successful",
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "phone": "+989123456789",
            "email": "john@example.com"
        },
        "token": "1|abc123..."
    }
}
```

#### Logout User
```http
POST /api/v1/auth/logout
Authorization: Bearer {token}
```

#### Refresh Token
```http
POST /api/v1/auth/refresh
Authorization: Bearer {token}
```

#### Verify Phone Number
```http
POST /api/v1/auth/verify-phone
```

**Request Body:**
```json
{
    "phone": "+989123456789",
    "verification_code": "123456"
}
```

#### Forgot Password
```http
POST /api/v1/auth/forgot-password
```

**Request Body:**
```json
{
    "phone": "+989123456789"
}
```

#### Reset Password
```http
POST /api/v1/auth/reset-password
```

**Request Body:**
```json
{
    "phone": "+989123456789",
    "token": "reset_token_here",
    "password": "new_password123",
    "password_confirmation": "new_password123"
}
```

### User Management

#### Get User Profile
```http
GET /api/v1/user
Authorization: Bearer {token}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "phone": "+989123456789",
            "email": "john@example.com",
            "avatar": "https://sarvcast.com/storage/avatars/user1.jpg",
            "subscription": {
                "plan": "premium",
                "expires_at": "2023-12-31T23:59:59.000000Z"
            },
            "children": [
                {
                    "id": 1,
                    "name": "Alice",
                    "age": 8,
                    "avatar": "https://sarvcast.com/storage/avatars/child1.jpg"
                }
            ]
        }
    }
}
```

#### Update User Profile
```http
PUT /api/v1/user
Authorization: Bearer {token}
```

**Request Body:**
```json
{
    "name": "John Smith",
    "email": "johnsmith@example.com"
}
```

#### Update Profile Picture
```http
POST /api/v1/user/profile-picture
Authorization: Bearer {token}
Content-Type: multipart/form-data

file: [image_file]
```

#### Get User Preferences
```http
GET /api/v1/user/preferences
Authorization: Bearer {token}
```

#### Update User Preferences
```http
POST /api/v1/user/preferences
Authorization: Bearer {token}
```

**Request Body:**
```json
{
    "language": "fa",
    "notifications": {
        "email": true,
        "sms": false,
        "push": true
    },
    "content_filters": {
        "age_appropriate": true,
        "educational_content": true
    }
}
```

#### Get User Activity
```http
GET /api/v1/user/activity
Authorization: Bearer {token}
```

#### Manage Child Profiles
```http
POST /api/v1/user/children
Authorization: Bearer {token}
```

**Request Body:**
```json
{
    "name": "Alice",
    "age": 8,
    "avatar": "https://sarvcast.com/storage/avatars/child1.jpg"
}
```

### Stories

#### Get Stories List
```http
GET /api/v1/stories
```

**Query Parameters:**
- `category_id`: Filter by category
- `search`: Search term
- `sort`: Sort field (title, created_at, popularity)
- `page`: Page number
- `per_page`: Items per page

**Response:**
```json
{
    "success": true,
    "data": {
        "stories": [
            {
                "id": 1,
                "title": "The Magic Forest",
                "description": "A magical adventure in the forest",
                "cover_image": "https://sarvcast.com/storage/covers/story1.jpg",
                "category": {
                    "id": 1,
                    "name": "Adventure"
                },
                "episodes_count": 5,
                "duration": 1800,
                "rating": 4.5,
                "is_premium": false,
                "created_at": "2023-01-01T00:00:00.000000Z"
            }
        ]
    },
    "meta": {
        "pagination": {
            "current_page": 1,
            "per_page": 15,
            "total": 100,
            "last_page": 7
        }
    }
}
```

#### Get Story Details
```http
GET /api/v1/stories/{id}
Authorization: Bearer {token}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "story": {
            "id": 1,
            "title": "The Magic Forest",
            "description": "A magical adventure in the forest",
            "cover_image": "https://sarvcast.com/storage/covers/story1.jpg",
            "category": {
                "id": 1,
                "name": "Adventure"
            },
            "episodes": [
                {
                    "id": 1,
                    "title": "Chapter 1: The Beginning",
                    "duration": 300,
                    "audio_file": "https://sarvcast.com/storage/audio/episode1.mp3",
                    "is_premium": false,
                    "order": 1
                }
            ],
            "people": [
                {
                    "id": 1,
                    "name": "John Narrator",
                    "role": "narrator"
                }
            ],
            "rating": 4.5,
            "reviews_count": 25,
            "is_premium": false,
            "created_at": "2023-01-01T00:00:00.000000Z"
        }
    }
}
```

### Episodes

#### Get Episodes List
```http
GET /api/v1/episodes
```

**Query Parameters:**
- `story_id`: Filter by story
- `search`: Search term
- `sort`: Sort field (title, created_at, order)
- `page`: Page number
- `per_page`: Items per page

#### Get Episode Details
```http
GET /api/v1/episodes/{id}
Authorization: Bearer {token}
```

#### Play Episode
```http
GET /api/v1/episodes/{id}/play
Authorization: Bearer {token}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "episode": {
            "id": 1,
            "title": "Chapter 1: The Beginning",
            "audio_file": "https://sarvcast.com/storage/audio/episode1.mp3",
            "duration": 300,
            "story": {
                "id": 1,
                "title": "The Magic Forest"
            }
        },
        "play_url": "https://sarvcast.com/storage/audio/episode1.mp3?token=play_token"
    }
}
```

### Categories

#### Get Categories List
```http
GET /api/v1/categories
```

**Response:**
```json
{
    "success": true,
    "data": {
        "categories": [
            {
                "id": 1,
                "name": "Adventure",
                "description": "Exciting adventure stories",
                "icon": "https://sarvcast.com/storage/icons/adventure.png",
                "stories_count": 25,
                "created_at": "2023-01-01T00:00:00.000000Z"
            }
        ]
    }
}
```

#### Get Category Details
```http
GET /api/v1/categories/{id}
```

### People (Voice Actors, Directors)

#### Get People List
```http
GET /api/v1/people
```

**Query Parameters:**
- `role`: Filter by role (narrator, director, writer)
- `search`: Search term
- `sort`: Sort field (name, created_at)

#### Get Person Details
```http
GET /api/v1/people/{id}
```

### Search & Discovery

#### Global Search
```http
GET /api/v1/search
```

**Query Parameters:**
- `q`: Search query
- `type`: Content type (stories, episodes, people)
- `category_id`: Filter by category
- `page`: Page number
- `per_page`: Items per page

#### Search Stories
```http
GET /api/v1/search/stories
```

#### Search Episodes
```http
GET /api/v1/search/episodes
```

#### Search People
```http
GET /api/v1/search/people
```

#### Get Search Suggestions
```http
GET /api/v1/search/suggestions
```

#### Get Trending Content
```http
GET /api/v1/search/trending
```

### Favorites

#### Toggle Favorite
```http
POST /api/v1/favorites/toggle
Authorization: Bearer {token}
```

**Request Body:**
```json
{
    "story_id": 1
}
```

#### Get User Favorites
```http
GET /api/v1/favorites
Authorization: Bearer {token}
```

#### Check Favorite Status
```http
GET /api/v1/favorites/check/{storyId}
Authorization: Bearer {token}
```

#### Get Most Favorited
```http
GET /api/v1/favorites/most-favorited
```

### Play History & Progress

#### Record Play
```http
POST /api/v1/play-history/record
Authorization: Bearer {token}
```

**Request Body:**
```json
{
    "episode_id": 1,
    "duration": 300,
    "progress": 150
}
```

#### Update Play Progress
```http
POST /api/v1/play-history/update-progress
Authorization: Bearer {token}
```

**Request Body:**
```json
{
    "episode_id": 1,
    "progress": 200
}
```

#### Get Play History
```http
GET /api/v1/play-history
Authorization: Bearer {token}
```

#### Get Recent Plays
```http
GET /api/v1/play-history/recent
Authorization: Bearer {token}
```

#### Get Completed Content
```http
GET /api/v1/play-history/completed
Authorization: Bearer {token}
```

#### Get In-Progress Content
```http
GET /api/v1/play-history/in-progress
Authorization: Bearer {token}
```

### Rating & Reviews

#### Submit Rating
```http
POST /api/v1/ratings
Authorization: Bearer {token}
```

**Request Body:**
```json
{
    "rateable_type": "story",
    "rateable_id": 1,
    "rating": 5,
    "review": "Great story!"
}
```

#### Get Ratings
```http
GET /api/v1/ratings/{type}/{id}
```

#### Get User Rating
```http
GET /api/v1/ratings/user/{type}/{id}
Authorization: Bearer {token}
```

#### Update Rating
```http
PUT /api/v1/ratings/{id}
Authorization: Bearer {token}
```

#### Delete Rating
```http
DELETE /api/v1/ratings/{id}
Authorization: Bearer {token}
```

### Subscriptions

#### Get Subscription Plans
```http
GET /api/v1/subscriptions/plans
```

**Response:**
```json
{
    "success": true,
    "data": {
        "plans": [
            {
                "id": 1,
                "name": "Basic",
                "description": "Basic subscription plan",
                "price": 50000,
                "currency": "IRR",
                "duration_days": 30,
                "features": [
                    "Access to basic stories",
                    "Ad-free experience"
                ]
            }
        ]
    }
}
```

#### Get User Subscriptions
```http
GET /api/v1/subscriptions
Authorization: Bearer {token}
```

#### Get Subscription Status
```http
GET /api/v1/subscriptions/status
Authorization: Bearer {token}
```

#### Create Subscription
```http
POST /api/v1/subscriptions
Authorization: Bearer {token}
```

**Request Body:**
```json
{
    "plan_id": 1,
    "payment_method": "zarinpal"
}
```

#### Activate Subscription
```http
POST /api/v1/subscriptions/{id}/activate
Authorization: Bearer {token}
```

#### Cancel Subscription
```http
POST /api/v1/subscriptions/{id}/cancel
Authorization: Bearer {token}
```

#### Renew Subscription
```http
POST /api/v1/subscriptions/{id}/renew
Authorization: Bearer {token}
```

### Payments

#### Process Payment
```http
POST /api/v1/payments/process
Authorization: Bearer {token}
```

**Request Body:**
```json
{
    "amount": 50000,
    "currency": "IRR",
    "payment_method": "zarinpal",
    "subscription_id": 1
}
```

#### Verify Payment
```http
POST /api/v1/payments/verify
Authorization: Bearer {token}
```

**Request Body:**
```json
{
    "payment_id": "payment_id_here",
    "authority": "zarinpal_authority_here"
}
```

#### Get Payment History
```http
GET /api/v1/payments/history
Authorization: Bearer {token}
```

### Notifications

#### Get Notifications
```http
GET /api/v1/notifications
Authorization: Bearer {token}
```

#### Get Unread Count
```http
GET /api/v1/notifications/unread-count
Authorization: Bearer {token}
```

#### Mark as Read
```http
POST /api/v1/notifications/{notificationId}/mark-read
Authorization: Bearer {token}
```

#### Mark All as Read
```http
POST /api/v1/notifications/mark-all-read
Authorization: Bearer {token}
```

#### Delete Notification
```http
DELETE /api/v1/notifications/{notificationId}
Authorization: Bearer {token}
```

### File Upload

#### Upload Image
```http
POST /api/v1/files/upload/image
Authorization: Bearer {token}
Content-Type: multipart/form-data

file: [image_file]
```

#### Upload Audio
```http
POST /api/v1/files/upload/audio
Authorization: Bearer {token}
Content-Type: multipart/form-data

file: [audio_file]
```

#### Upload Multiple Files
```http
POST /api/v1/files/upload/multiple
Authorization: Bearer {token}
Content-Type: multipart/form-data

files[]: [file1]
files[]: [file2]
```

#### Get File Info
```http
GET /api/v1/files/{fileId}/info
Authorization: Bearer {token}
```

#### Delete File
```http
DELETE /api/v1/files/{fileId}
Authorization: Bearer {token}
```

### Audio Processing

#### Convert Audio Format
```http
POST /api/v1/audio/convert
Authorization: Bearer {token}
```

**Request Body:**
```json
{
    "file_id": 1,
    "output_format": "mp3",
    "quality": "high"
}
```

#### Extract Audio Metadata
```http
POST /api/v1/audio/metadata
Authorization: Bearer {token}
```

**Request Body:**
```json
{
    "file_id": 1
}
```

#### Normalize Audio
```http
POST /api/v1/audio/normalize
Authorization: Bearer {token}
```

**Request Body:**
```json
{
    "file_id": 1,
    "target_loudness": -16
}
```

#### Trim Audio
```http
POST /api/v1/audio/trim
Authorization: Bearer {token}
```

**Request Body:**
```json
{
    "file_id": 1,
    "start_time": 10,
    "end_time": 300
}
```

### Image Processing

#### Resize Image
```http
POST /api/v1/image/resize
Authorization: Bearer {token}
```

**Request Body:**
```json
{
    "file_id": 1,
    "width": 800,
    "height": 600,
    "maintain_aspect_ratio": true
}
```

#### Crop Image
```http
POST /api/v1/image/crop
Authorization: Bearer {token}
```

**Request Body:**
```json
{
    "file_id": 1,
    "x": 100,
    "y": 100,
    "width": 400,
    "height": 300
}
```

#### Add Watermark
```http
POST /api/v1/image/watermark
Authorization: Bearer {token}
```

**Request Body:**
```json
{
    "file_id": 1,
    "watermark_text": "SarvCast",
    "position": "bottom-right",
    "opacity": 0.5
}
```

#### Optimize Image
```http
POST /api/v1/image/optimize
Authorization: Bearer {token}
```

**Request Body:**
```json
{
    "file_id": 1,
    "quality": 85,
    "format": "webp"
}
```

#### Generate Thumbnail
```http
POST /api/v1/image/thumbnail
Authorization: Bearer {token}
```

**Request Body:**
```json
{
    "file_id": 1,
    "size": "medium"
}
```

### Access Control

#### Get User Access Level
```http
GET /api/v1/access/level
Authorization: Bearer {token}
```

#### Check Story Access
```http
GET /api/v1/access/story/{storyId}
Authorization: Bearer {token}
```

#### Check Episode Access
```http
GET /api/v1/access/episode/{episodeId}
Authorization: Bearer {token}
```

#### Check Download Permission
```http
POST /api/v1/access/download
Authorization: Bearer {token}
```

**Request Body:**
```json
{
    "content_type": "episode",
    "content_id": 1
}
```

#### Get Filtered Content
```http
POST /api/v1/access/filtered-content
Authorization: Bearer {token}
```

**Request Body:**
```json
{
    "content_type": "stories",
    "filters": {
        "age_appropriate": true,
        "educational_content": true
    }
}
```

### SMS Notifications

#### Send SMS
```http
POST /api/v1/sms/send
Authorization: Bearer {token}
```

**Request Body:**
```json
{
    "phone": "+989123456789",
    "message": "Your verification code is: 123456"
}
```

#### Send Verification Code
```http
POST /api/v1/sms/verification-code
Authorization: Bearer {token}
```

**Request Body:**
```json
{
    "phone": "+989123456789"
}
```

#### Send Templated SMS
```http
POST /api/v1/sms/template
Authorization: Bearer {token}
```

**Request Body:**
```json
{
    "phone": "+989123456789",
    "template": "welcome",
    "variables": {
        "name": "John"
    }
}
```

#### Send Bulk SMS
```http
POST /api/v1/sms/bulk
Authorization: Bearer {token}
```

**Request Body:**
```json
{
    "phones": ["+989123456789", "+989987654321"],
    "message": "Bulk message content"
}
```

### Recommendations

#### Get Personalized Recommendations
```http
GET /api/v1/recommendations/personalized
Authorization: Bearer {token}
```

#### Get New User Recommendations
```http
GET /api/v1/recommendations/new-user
Authorization: Bearer {token}
```

#### Get Trending Recommendations
```http
GET /api/v1/recommendations/trending
```

#### Get Similar Content
```http
GET /api/v1/recommendations/similar/{storyId}
```

#### Get User Preferences
```http
GET /api/v1/recommendations/preferences
Authorization: Bearer {token}
```

#### Get User Behavior
```http
GET /api/v1/recommendations/behavior
Authorization: Bearer {token}
```

### Content Personalization

#### Get Personalized Feed
```http
GET /api/v1/personalization/feed
Authorization: Bearer {token}
```

#### Get Personalized Search
```http
GET /api/v1/personalization/search
Authorization: Bearer {token}
```

**Query Parameters:**
- `q`: Search query
- `limit`: Number of results

#### Get Personalized Category Recommendations
```http
GET /api/v1/personalization/category/{categoryId}/recommendations
Authorization: Bearer {token}
```

#### Get Personalized Dashboard
```http
GET /api/v1/personalization/dashboard
Authorization: Bearer {token}
```

#### Learn User Preferences
```http
POST /api/v1/personalization/learn-preferences
Authorization: Bearer {token}
```

**Request Body:**
```json
{
    "interaction_type": "play",
    "content_id": 1,
    "content_type": "episode",
    "duration": 300
}
```

### Social Features

#### Follow User
```http
POST /api/v1/social/follow/{userId}
Authorization: Bearer {token}
```

#### Unfollow User
```http
DELETE /api/v1/social/unfollow/{userId}
Authorization: Bearer {token}
```

#### Share Content
```http
POST /api/v1/social/share
Authorization: Bearer {token}
```

**Request Body:**
```json
{
    "content_type": "story",
    "content_id": 1,
    "platform": "telegram",
    "message": "Check out this amazing story!"
}
```

#### Get User Followers
```http
GET /api/v1/social/followers/{userId}
```

#### Get User Following
```http
GET /api/v1/social/following/{userId}
```

#### Get Activity Feed
```http
GET /api/v1/social/activity-feed
Authorization: Bearer {token}
```

#### Create Playlist
```http
POST /api/v1/social/playlists
Authorization: Bearer {token}
```

**Request Body:**
```json
{
    "name": "My Favorite Stories",
    "description": "A collection of my favorite stories",
    "is_public": true
}
```

#### Add to Playlist
```http
POST /api/v1/social/playlists/{playlistId}/add
Authorization: Bearer {token}
```

**Request Body:**
```json
{
    "content_type": "story",
    "content_id": 1
}
```

#### Add Comment
```http
POST /api/v1/social/comments
Authorization: Bearer {token}
```

**Request Body:**
```json
{
    "content_type": "story",
    "content_id": 1,
    "comment": "Great story! My kids loved it."
}
```

#### Like Comment
```http
POST /api/v1/social/comments/{commentId}/like
Authorization: Bearer {token}
```

#### Get User Social Stats
```http
GET /api/v1/social/stats/{userId}
```

#### Get Trending Content
```http
GET /api/v1/social/trending
```

#### Check Follow Status
```http
GET /api/v1/social/follow-status/{userId}
Authorization: Bearer {token}
```

### Gamification

#### Get User Profile
```http
GET /api/v1/gamification/profile
Authorization: Bearer {token}
```

#### Award Points
```http
POST /api/v1/gamification/award-points
Authorization: Bearer {token}
```

**Request Body:**
```json
{
    "points": 100,
    "source_type": "achievement",
    "source_id": 1,
    "description": "Completed first story"
}
```

#### Get Leaderboard
```http
GET /api/v1/gamification/leaderboard/{slug}
```

**Query Parameters:**
- `limit`: Number of entries (max 100)

#### Update Leaderboard
```http
POST /api/v1/gamification/leaderboard/{slug}/update
Authorization: Bearer {token}
```

#### Update Streak
```http
POST /api/v1/gamification/streak
Authorization: Bearer {token}
```

**Request Body:**
```json
{
    "streak_type": "listening",
    "increment": true
}
```

#### Get Available Challenges
```http
GET /api/v1/gamification/challenges
Authorization: Bearer {token}
```

#### Join Challenge
```http
POST /api/v1/gamification/challenges/{challengeId}/join
Authorization: Bearer {token}
```

#### Get User Achievements
```http
GET /api/v1/gamification/achievements
Authorization: Bearer {token}
```

#### Get User Badges
```http
GET /api/v1/gamification/badges
Authorization: Bearer {token}
```

#### Get User Streaks
```http
GET /api/v1/gamification/streaks
Authorization: Bearer {token}
```

#### Get All Achievements
```http
GET /api/v1/gamification/all-achievements
```

#### Get All Badges
```http
GET /api/v1/gamification/all-badges
```

### Health Check

#### System Health
```http
GET /api/v1/health
```

**Response:**
```json
{
    "success": true,
    "data": {
        "status": "healthy",
        "timestamp": "2023-01-01T00:00:00.000000Z",
        "services": {
            "database": "healthy",
            "redis": "healthy",
            "storage": "healthy"
        },
        "version": "1.0.0"
    }
}
```

## Admin Endpoints

Admin endpoints require admin authentication and are prefixed with `/api/v1/admin/`.

### Dashboard

#### Get Dashboard Stats
```http
GET /api/v1/admin/dashboard/stats
Authorization: Bearer {admin_token}
```

### Story Management

#### Create Story
```http
POST /api/v1/admin/stories
Authorization: Bearer {admin_token}
```

#### Update Story
```http
PUT /api/v1/admin/stories/{id}
Authorization: Bearer {admin_token}
```

#### Delete Story
```http
DELETE /api/v1/admin/stories/{id}
Authorization: Bearer {admin_token}
```

### Episode Management

#### Create Episode
```http
POST /api/v1/admin/episodes
Authorization: Bearer {admin_token}
```

#### Update Episode
```http
PUT /api/v1/admin/episodes/{id}
Authorization: Bearer {admin_token}
```

#### Delete Episode
```http
DELETE /api/v1/admin/episodes/{id}
Authorization: Bearer {admin_token}
```

### User Management

#### Get Users List
```http
GET /api/v1/admin/users
Authorization: Bearer {admin_token}
```

#### Update User
```http
PUT /api/v1/admin/users/{id}
Authorization: Bearer {admin_token}
```

#### Delete User
```http
DELETE /api/v1/admin/users/{id}
Authorization: Bearer {admin_token}
```

### Category Management

#### Create Category
```http
POST /api/v1/admin/categories
Authorization: Bearer {admin_token}
```

#### Update Category
```http
PUT /api/v1/admin/categories/{id}
Authorization: Bearer {admin_token}
```

#### Delete Category
```http
DELETE /api/v1/admin/categories/{id}
Authorization: Bearer {admin_token}
```

### Subscription Management

#### Get Subscriptions
```http
GET /api/v1/admin/subscriptions
Authorization: Bearer {admin_token}
```

### Analytics

#### Get Analytics
```http
GET /api/v1/admin/analytics
Authorization: Bearer {admin_token}
```

## SDKs and Libraries

### JavaScript/TypeScript

```javascript
import SarvCastAPI from 'sarvcast-js-sdk';

const api = new SarvCastAPI({
    baseURL: 'https://sarvcast.com/api/v1',
    apiKey: 'your_api_key'
});

// Login
const user = await api.auth.login({
    phone: '+989123456789',
    password: 'password123'
});

// Get stories
const stories = await api.stories.list({
    category_id: 1,
    page: 1,
    per_page: 20
});
```

### PHP

```php
use SarvCast\SarvCastAPI;

$api = new SarvCastAPI([
    'base_url' => 'https://sarvcast.com/api/v1',
    'api_key' => 'your_api_key'
]);

// Login
$user = $api->auth->login([
    'phone' => '+989123456789',
    'password' => 'password123'
]);

// Get stories
$stories = $api->stories->list([
    'category_id' => 1,
    'page' => 1,
    'per_page' => 20
]);
```

## Webhooks

SarvCast supports webhooks for real-time notifications:

### Webhook Events

- `user.registered`: User registration
- `user.subscribed`: User subscription
- `story.published`: Story published
- `episode.published`: Episode published
- `payment.completed`: Payment completed
- `payment.failed`: Payment failed

### Webhook Configuration

```http
POST /api/v1/webhooks
Authorization: Bearer {admin_token}
```

**Request Body:**
```json
{
    "url": "https://your-app.com/webhooks/sarvcast",
    "events": ["user.registered", "user.subscribed"],
    "secret": "your_webhook_secret"
}
```

### Webhook Payload

```json
{
    "event": "user.registered",
    "timestamp": "2023-01-01T00:00:00.000000Z",
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "phone": "+989123456789"
        }
    },
    "signature": "webhook_signature_hash"
}
```

## Error Handling

### Common Error Scenarios

1. **Invalid Authentication**
   ```json
   {
       "success": false,
       "message": "Unauthenticated",
       "error_code": "UNAUTHENTICATED"
   }
   ```

2. **Validation Errors**
   ```json
   {
       "success": false,
       "message": "Validation failed",
       "errors": {
           "phone": ["The phone field is required."],
           "password": ["The password must be at least 8 characters."]
       }
   }
   ```

3. **Rate Limit Exceeded**
   ```json
   {
       "success": false,
       "message": "Too many requests",
       "error_code": "RATE_LIMIT_EXCEEDED",
       "retry_after": 60
   }
   ```

4. **Content Not Found**
   ```json
   {
       "success": false,
       "message": "Story not found",
       "error_code": "NOT_FOUND"
   }
   ```

## Best Practices

### Authentication
- Always use HTTPS in production
- Store tokens securely
- Implement token refresh logic
- Handle authentication errors gracefully

### Error Handling
- Check response status codes
- Handle network errors
- Implement retry logic for transient failures
- Log errors for debugging

### Performance
- Use pagination for large datasets
- Implement caching where appropriate
- Use appropriate HTTP methods
- Minimize request payload size

### Security
- Validate all input data
- Use HTTPS for all requests
- Implement proper access controls
- Monitor for suspicious activity

## Support

For API support and questions:

- **Email**: api-support@sarvcast.com
- **Documentation**: https://docs.sarvcast.com
- **Status Page**: https://status.sarvcast.com
- **GitHub**: https://github.com/sarvcast/api

## Changelog

### Version 1.0.0 (2023-01-01)
- Initial API release
- Authentication system
- Story and episode management
- User management
- Subscription system
- Payment integration
- Search and discovery
- Recommendations
- Social features
- Gamification system
