# SarvCast API Postman Collection Guide

## Overview
This Postman collection contains all the API endpoints for the SarvCast audio story platform. The collection is organized into logical groups for easy navigation and testing.

## Setup Instructions

### 1. Import the Collection
1. Open Postman
2. Click "Import" button
3. Select the `SarvCast_API.postman_collection.json` file
4. The collection will be imported with all endpoints organized

### 2. Configure Environment Variables
The collection uses these variables:
- `base_url`: API base URL (default: `https://my.sarvcast.ir/api/v1`)
- `auth_token`: Bearer token for authenticated requests

To set up:
1. Create a new environment in Postman
2. Add these variables:
   - `base_url`: `https://my.sarvcast.ir/api/v1` (or your server URL)
   - `auth_token`: Leave empty initially
3. Select this environment for your requests

## API Groups

### 1. Authentication
**Purpose**: User registration, login, and session management

**Key Endpoints**:
- `POST /auth/send-verification-code` - Send SMS verification code
- `POST /auth/register` - Register new user
- `POST /auth/login` - Login existing user
- `POST /auth/admin/login` - Admin login
- `GET /auth/profile` - Get user profile
- `POST /auth/logout` - Logout user

**Authentication Flow**:
1. Send verification code to phone number
2. Use code to register/login
3. Store the returned token in `auth_token` variable
4. Use token for authenticated requests

### 2. Stories
**Purpose**: Browse and interact with audio stories

**Key Endpoints**:
- `GET /stories` - Get all stories (paginated)
- `GET /stories/{id}` - Get story details
- `GET /stories/{id}/episodes` - Get story episodes
- `GET /stories/featured` - Get featured stories for home page
- `GET /stories/popular` - Get popular stories by play count and ratings
- `GET /stories/recent` - Get recently added stories
- `GET /stories/recommendations` - Get personalized recommendations
- `POST /stories/{id}/favorite` - Add to favorites
- `POST /stories/{id}/rating` - Rate story

### 3. Story Comments
**Purpose**: User engagement through story commenting system

**Key Endpoints**:
- `GET /stories/{id}/comments` - Get story comments with pagination
- `POST /stories/{id}/comments` - Add new comment or reply
- `GET /stories/{id}/comments/statistics` - Get comment statistics
- `GET /comments/my-comments` - Get user's comments
- `DELETE /comments/{id}` - Delete user's comment
- `POST /comments/{id}/like` - Like/unlike comment
- `GET /comments/{id}/replies` - Get comment replies

**Comment Examples**:
```json
// Add Comment
POST /stories/1/comments
{
  "content": "داستان بسیار زیبا و آموزنده بود",
  "parent_id": null,
  "metadata": {
    "device": "mobile",
    "version": "1.0.0"
  }
}

// Add Reply
POST /stories/1/comments
{
  "content": "موافقم! داستان واقعاً جذاب بود",
  "parent_id": 1,
  "metadata": {
    "device": "mobile",
    "version": "1.0.0"
  }
}

// Get Comments with Pagination
GET /stories/1/comments?page=1&per_page=20&sort_by=latest&include_replies=true
```

### 4. People
**Purpose**: Browse authors, narrators, voice actors, and other content creators

**Key Endpoints**:
- `GET /people` - Get all people (paginated)
- `GET /people/{id}` - Get person details
- `GET /people/{id}/stories` - Get stories by person
- `GET /people/search` - Search people by name or role
- `GET /people/role/{role}` - Get people by specific role
- `GET /people/{id}/statistics` - Get person statistics

**People Examples**:
```json
// Get All People
GET /people?page=1&per_page=20&role=narrator

// Get Person Details
GET /people/1

// Get Stories by Person
GET /people/1/stories?page=1&per_page=20

// Search People
GET /people/search?query=علی&role=narrator&limit=10

// Get People by Role
GET /people/role/narrator

// Get Person Statistics
GET /people/1/statistics
```

### 5. Image Timeline
**Purpose**: Manage episode image timelines for visual storytelling

**Key Endpoints**:
- `GET /episodes/{id}/image-timeline` - Get episode image timeline
- `GET /episodes/{id}/image-for-time` - Get image for specific time
- `GET /episodes/{id}/image-timeline-with-voice-actors` - Get timeline with voice actors
- `GET /episodes/{id}/image-timeline-for-voice-actor` - Get timeline for specific voice actor
- `GET /episodes/{id}/key-frames` - Get key frames
- `GET /episodes/{id}/timeline-by-transition-type` - Get timeline by transition type
- `GET /episodes/{id}/timeline-statistics` - Get timeline statistics

**Image Timeline Examples**:
```json
// Get Episode Timeline
GET /episodes/1/image-timeline

// Get Image for Specific Time
GET /episodes/1/image-for-time?time=120

// Get Timeline with Voice Actors
GET /episodes/1/image-timeline-with-voice-actors

// Get Timeline for Voice Actor
GET /episodes/1/image-timeline-for-voice-actor?voice_actor_id=1
```

### 6. Search & Discovery
**Purpose**: Advanced search functionality for content discovery

**Key Endpoints**:
- `GET /search/stories` - Search stories with advanced filters
- `GET /search/episodes` - Search episodes with story context
- `GET /search/people` - Search narrators, authors, directors
- `GET /search/global` - Global search across all content
- `GET /search/suggestions` - Get search suggestions
- `GET /search/trending` - Get trending content
- `GET /search/filters` - Get available search filters
- `GET /search/stats` - Get search statistics

**Search Examples**:
```json
// Advanced Story Search
GET /search/stories?q=داستان&category_id=1&age_group=6-9&sort_by=rating&sort_order=desc&per_page=20

// Episode Search
GET /search/episodes?q=اپیزود&story_id=1&sort_by=episode_number&sort_order=asc

// People Search
GET /search/people?q=نویسنده&role=narrator&per_page=10

// Global Search
GET /search/global?q=داستان&limit=10
```

### 6. Episodes
**Purpose**: Play and manage individual episodes

**Key Endpoints**:
- `GET /episodes/{id}` - Get episode details
- `POST /episodes/{id}/play` - Start playing episode
- `POST /episodes/{id}/bookmark` - Bookmark episode

### 7. Recommendations & Trending
**Purpose**: Personalized content recommendations and trending content discovery

**Key Endpoints**:
- `GET /recommendations/personalized` - Get personalized recommendations
- `GET /recommendations/trending` - Get trending recommendations
- `GET /recommendations/similar/{id}` - Get similar content
- `GET /recommendations/category/{id}/recommendations` - Get category recommendations
- `GET /mobile/recommendations` - Get mobile-specific recommendations
- `GET /mobile/trending` - Get mobile trending content
- `GET /social/trending` - Get social trending content
- `GET /recommendations/analytics` - Get recommendation analytics

**Recommendation Examples**:
```json
// Get Personalized Recommendations
GET /recommendations/personalized?limit=10&type=stories

// Get Trending Content
GET /mobile/trending?type=stories&period=week&limit=10

// Get Similar Content
GET /recommendations/similar/1?type=story&limit=5

// Get Category Recommendations
GET /recommendations/category/1/recommendations?limit=10
```

### 8. Offline Content
**Purpose**: Download and manage content for offline listening

**Key Endpoints**:
- `GET /mobile/offline/stories` - Get stories available for offline download
- `GET /mobile/offline/episodes` - Get episodes available for offline download
- `POST /access/download` - Check download access permissions
- `POST /mobile/track/download` - Track download activity
- `GET /mobile/downloads/history` - Get download history
- `GET /mobile/downloads/statistics` - Get download statistics
- `DELETE /mobile/downloads/{id}` - Delete specific downloaded content
- `DELETE /mobile/downloads/clear` - Clear all downloads

**Offline Content Examples**:
```json
// Get Offline Stories
GET /mobile/offline/stories?limit=20&quality=high

// Get Offline Episodes
GET /mobile/offline/episodes?story_id=1&limit=10&quality=high

// Check Download Access
POST /access/download
{
  "content_type": "episode",
  "content_id": 1,
  "download_quality": "high"
}

// Track Download
POST /mobile/track/download
{
  "content_type": "episode",
  "content_id": 1,
  "download_size": 1024000,
  "download_quality": "high",
  "device_info": {
    "platform": "android",
    "version": "1.0.0",
    "storage_available": 5000000000
  }
}
```

### 8. Social Sharing & Tracking
**Purpose**: Manage social features, user interactions, and content sharing

**Key Endpoints**:
- `POST /social/follow/{userId}` - Follow a user
- `DELETE /social/unfollow/{userId}` - Unfollow a user
- `POST /social/share` - Share content
- `GET /social/followers/{userId}` - Get user followers
- `GET /social/following/{userId}` - Get user following
- `GET /social/activity-feed` - Get activity feed
- `POST /social/playlists` - Create playlist
- `POST /social/playlists/{playlistId}/add` - Add to playlist
- `POST /social/comments` - Add social comment
- `GET /social/stats/{userId}` - Get user social stats
- `GET /social/trending` - Get social trending content
- `GET /social/follow-status/{userId}` - Check follow status
- `POST /mobile/track/share` - Track share activity

**Social Sharing Examples**:
```json
// Follow User
POST /social/follow/1

// Share Content
POST /social/share
{
  "content_type": "story",
  "content_id": 1,
  "platform": "telegram",
  "message": "Check out this amazing story!"
}

// Create Playlist
POST /social/playlists
{
  "name": "My Favorite Stories",
  "description": "A collection of my favorite stories",
  "is_public": true
}

// Add Social Comment
POST /social/comments
{
  "content_type": "story",
  "content_id": 1,
  "comment": "Great story! My kids loved it."
}

// Track Share Activity
POST /mobile/track/share
{
  "content_type": "story",
  "content_id": 1,
  "share_platform": "whatsapp",
  "device_info": {
    "platform": "android",
    "version": "1.0.0"
  }
}
```

### 9. Mobile App Configuration
**Purpose**: Manage mobile app configuration, device registration, and mobile-specific features

**Key Endpoints**:
- `GET /mobile/config` - Get app configuration
- `GET /mobile/version` - Get app version information
- `POST /mobile/device/register` - Register device
- `POST /mobile/device/token` - Update FCM token
- `DELETE /mobile/device/unregister` - Unregister device
- `GET /mobile/search` - Mobile-specific search
- `GET /mobile/recommendations` - Get mobile recommendations
- `GET /mobile/trending` - Get mobile trending content
- `POST /mobile/track/play` - Track play activity
- `POST /mobile/track/download` - Track download activity
- `POST /mobile/track/share` - Track share activity

**Mobile Configuration Examples**:
```json
// Get App Configuration
GET /mobile/config
Response:
{
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

// Register Device
POST /mobile/device/register
{
  "device_id": "unique_device_id_123",
  "device_type": "android",
  "device_model": "Samsung Galaxy S21",
  "os_version": "Android 12",
  "app_version": "1.0.0",
  "fcm_token": "fcm_token_here",
  "timezone": "Asia/Tehran",
  "language": "fa",
  "country": "IR"
}

// Track Play Activity
POST /mobile/track/play
{
  "episode_id": 1,
  "action": "play",
  "duration": 300,
  "device_info": {
    "platform": "android",
    "version": "1.0.0"
  }
}
```

### 10. Rating & Review System
**Purpose**: Manage user ratings and reviews for stories and episodes

**Key Endpoints**:
- `GET /ratings` - Get user's ratings with pagination
- `POST /ratings/story` - Submit story rating and review
- `POST /ratings/episode` - Submit episode rating and review
- `GET /ratings/story/{storyId}` - Get story ratings
- `GET /ratings/episode/{episodeId}` - Get episode ratings
- `GET /ratings/story/{storyId}/user` - Get user's story rating
- `GET /ratings/episode/{episodeId}/user` - Get user's episode rating
- `GET /ratings/highest-rated-stories` - Get highest rated stories
- `GET /ratings/highest-rated-episodes` - Get highest rated episodes
- `GET /ratings/recent-reviews` - Get recent reviews
- `GET /ratings/user-stats` - Get user rating statistics
- `GET /ratings/analytics` - Get rating analytics

**Rating Examples**:
```json
// Submit Story Rating
POST /ratings/story
{
  "story_id": 1,
  "rating": 5,
  "review": "داستان بسیار زیبا و آموزنده بود. فرزندم عاشق آن شد!"
}

// Submit Episode Rating
POST /ratings/episode
{
  "episode_id": 1,
  "rating": 4,
  "review": "قسمت خوبی بود اما کمی کوتاه بود."
}

// Get User Rating Statistics
GET /ratings/user-stats
Response:
{
  "total_ratings": 25,
  "average_rating": 4.2,
  "story_ratings": 15,
  "episode_ratings": 10,
  "reviews_written": 20
}
```

### 11. Favorites Management
**Purpose**: Manage user's favorite stories and bookmarks

**Key Endpoints**:
- `GET /favorites` - Get user's favorite stories with pagination
- `POST /favorites` - Add story to favorites
- `DELETE /favorites/{storyId}` - Remove story from favorites
- `POST /favorites/toggle` - Toggle favorite status
- `GET /favorites/check/{storyId}` - Check if story is favorited
- `GET /favorites/most-favorited` - Get most favorited stories
- `GET /favorites/stats` - Get favorite statistics
- `POST /favorites/bulk` - Bulk add/remove favorites

**Favorites Examples**:
```json
// Add Story to Favorites
POST /favorites
{
  "story_id": 1
}

// Toggle Favorite Status
POST /favorites/toggle
{
  "story_id": 1
}

// Bulk Add Favorites
POST /favorites/bulk
{
  "action": "add",
  "story_ids": [1, 2, 3, 4, 5]
}

// Get Favorite Statistics
GET /favorites/stats
Response:
{
  "total_favorites": 25,
  "most_favorited_category": "adventure",
  "average_favorites_per_story": 12.5,
  "recent_favorites": 5
}
```

### 12. Play History & Analytics
**Purpose**: Track user play history, progress, and analytics for personalized experience

**Key Endpoints**:
- `GET /play-history` - Get user's play history with pagination and sorting
- `POST /play-history/record` - Record new play session
- `PUT /play-history/{id}/progress` - Update play progress
- `GET /play-history/recent` - Get recent play history
- `GET /play-history/completed` - Get completed episodes
- `GET /play-history/in-progress` - Get in-progress episodes
- `GET /play-history/stats` - Get user play statistics
- `GET /play-history/episode/{id}/stats` - Get episode-specific statistics
- `GET /play-history/story/{id}/stats` - Get story-specific statistics
- `GET /play-history/most-played` - Get most played episodes
- `GET /play-history/most-played-stories` - Get most played stories
- `GET /play-history/analytics` - Get detailed play analytics
- `POST /mobile/track/play` - Track play activity
- `POST /mobile/track/share` - Track share activity

**Play History Examples**:
```json
// Record Play History
POST /play-history/record
{
  "episode_id": 1,
  "story_id": 1,
  "duration_played": 300,
  "total_duration": 900,
  "completion_rate": 0.33,
  "device_info": {
    "platform": "android",
    "version": "1.0.0",
    "device_model": "Samsung Galaxy S21"
  },
  "context": {
    "time_of_day": "evening",
    "location": "home",
    "network_type": "wifi"
  }
}

// Update Play Progress
PUT /play-history/1/progress
{
  "duration_played": 450,
  "completion_rate": 0.5,
  "is_completed": false
}

// Get Play Statistics
GET /play-history/stats
Response:
{
  "total_episodes_played": 25,
  "total_duration": 7200,
  "average_completion_rate": 0.75,
  "most_active_day": "sunday",
  "preferred_time": "evening"
}

// Track Play Activity
POST /mobile/track/play
{
  "episode_id": 1,
  "action": "play",
  "duration": 300,
  "device_info": {
    "platform": "android",
    "version": "1.0.0"
  }
}
```

### 13. Parental Controls
**Purpose**: Manage parental controls and child profiles for safe content consumption

**Key Endpoints**:
- `GET /mobile/parental-controls` - Get current parental control settings
- `PUT /mobile/parental-controls` - Update parental control settings
- `GET /user/profiles` - Get child profiles
- `POST /user/profiles` - Create child profile
- `PUT /user/profiles/{id}` - Update child profile
- `DELETE /user/profiles/{id}` - Delete child profile
- `GET /mobile/parental-controls/filter-options` - Get content filter options
- `GET /mobile/parental-controls/statistics` - Get parental control statistics

**Parental Controls Examples**:
```json
// Update Parental Controls
PUT /mobile/parental-controls
{
  "enabled": true,
  "age_limit": 8,
  "content_filter": "moderate",
  "time_restrictions": {
    "enabled": true,
    "start_time": "08:00",
    "end_time": "20:00"
  },
  "category_restrictions": [1, 2],
  "blocked_keywords": ["خشونت", "ترسناک"],
  "require_password": true
}

// Create Child Profile
POST /user/profiles
{
  "name": "علی",
  "age": 7,
  "avatar_url": "https://example.com/avatar.jpg",
  "preferences": {
    "favorite_categories": [1, 2],
    "language": "fa",
    "audio_quality": "high"
  },
  "parental_settings": {
    "age_limit": 7,
    "content_filter": "strict",
    "time_restrictions": {
      "enabled": true,
      "start_time": "09:00",
      "end_time": "19:00"
    }
  }
}
```

### 14. User Preferences & Settings
**Purpose**: Manage user preferences, settings, and personalization

**Key Endpoints**:
- `GET /mobile/preferences` - Get user preferences
- `PUT /mobile/preferences` - Update user preferences
- `GET /recommendations/preferences` - Get recommendation preferences
- `POST /recommendations/learn-preferences` - Learn from user interactions
- `POST /recommendations/update-preferences` - Update preferences from interactions
- `POST /mobile/preferences/reset` - Reset user preferences
- `GET /mobile/preferences/export` - Export user preferences

**User Preferences Examples**:
```json
// Update User Preferences
PUT /mobile/preferences
{
  "audio": {
    "quality": "high",
    "auto_play": true,
    "volume": 0.8,
    "speed": 1.0
  },
  "notifications": {
    "push_enabled": true,
    "email_enabled": false,
    "new_story_notifications": true,
    "subscription_notifications": true
  },
  "language": "fa",
  "timezone": "Asia/Tehran",
  "theme": "light",
  "accessibility": {
    "high_contrast": false,
    "large_text": false,
    "screen_reader": false
  }
}

// Learn from User Interaction
POST /recommendations/learn-preferences
{
  "interaction_type": "play",
  "content_id": 1,
  "content_type": "story",
  "duration_played": 300,
  "completion_rate": 0.8,
  "rating": 5,
  "context": {
    "time_of_day": "evening",
    "device_type": "mobile",
    "location": "home"
  }
}
```

### 15. Categories
**Purpose**: Browse content by categories

**Key Endpoints**:
- `GET /categories` - Get all categories
- `GET /categories/{id}/stories` - Get stories in category

### 11. Subscriptions
**Purpose**: Manage user subscriptions

**Key Endpoints**:
- `GET /subscriptions/plans` - Get available plans
- `POST /subscriptions/calculate-price` - Calculate price with coupon
- `POST /subscriptions` - Create subscription
- `POST /subscriptions/trial` - Create trial subscription
- `GET /subscriptions/current` - Get current subscription
- `GET /subscriptions/{id}` - Get subscription details
- `POST /subscriptions/{id}/upgrade` - Upgrade subscription
- `POST /subscriptions/{id}/renew` - Renew subscription
- `POST /subscriptions/{id}/cancel` - Cancel subscription
- `GET /subscriptions/stats` - Get subscription statistics

**Subscription Examples**:
```json
// Calculate Price with Coupon
POST /subscriptions/calculate-price
{
  "plan_slug": "1month",
  "coupon_code": "WELCOME20"
}

// Create Subscription
POST /subscriptions
{
  "plan_slug": "1month",
  "payment_method": "zarinpal",
  "coupon_code": "WELCOME20"
}

// Upgrade Subscription
POST /subscriptions/1/upgrade
{
  "new_plan_slug": "3months",
  "payment_method": "zarinpal"
}
```

### 12. Payments
**Purpose**: Handle payment processing (Zarinpal integration)

**Key Endpoints**:
- `POST /payments/initiate` - Start payment process
- `POST /payments/verify` - Verify payment completion
- `GET /payments/history` - Get payment history

### 13. Coupons
**Purpose**: Apply discount codes

**Key Endpoints**:
- `POST /coupons/validate` - Validate coupon code
- `POST /coupons/use` - Use coupon for purchase
- `GET /coupons/my-coupons` - Get user's coupons

### 14. Notifications
**Purpose**: Manage in-app notifications

**Key Endpoints**:
- `GET /notifications` - Get user notifications
- `PUT /notifications/{id}/read` - Mark as read
- `PUT /notifications/read-all` - Mark all as read

### 15. Version Management
**Purpose**: App update management

**Key Endpoints**:
- `POST /version/check` - Check for updates
- `GET /version/latest` - Get latest version info
- `GET /version/config` - Get app configuration

### 16. Health Check
**Purpose**: Monitor API health

**Key Endpoints**:
- `GET /health` - Basic health status
- `GET /health/metrics` - Detailed metrics

### 17. Admin APIs
**Purpose**: Administrative functions (requires admin token)

**Key Endpoints**:
- `GET /admin/dashboard/stats` - Dashboard statistics
- `GET /admin/users` - Manage users
- `GET /admin/coupons` - Manage coupons
- `POST /admin/coupons` - Create coupons
- `GET /admin/stories` - List stories
- `POST /admin/stories` - Create story
- `GET /admin/episodes` - List episodes
- `POST /admin/episodes` - Create episode

**Admin Content Creation**:
```json
// Create Story
POST /admin/stories
{
  "title": "داستان جدید",
  "description": "توضیحات داستان جدید",
  "category_id": 1,
  "status": "draft",
  "is_premium": false,
  "age_rating": "all"
}

// Create Episode
POST /admin/episodes
{
  "story_id": 1,
  "title": "اپیزود اول",
  "episode_number": 1,
  "duration": 300,
  "status": "draft",
  "is_premium": false,
  "age_rating": "all"
}
```

## Testing Workflow

### 1. Basic API Test
1. Start with Health Check endpoints
2. Test public endpoints (Stories, Categories)
3. Verify API is responding correctly

### 2. Authentication Test
1. Send verification code to a test phone number
2. Register a new user
3. Login with the same credentials
4. Verify token is received and stored

### 3. Authenticated Features Test
1. Get user profile
2. Browse stories and episodes
3. Test favorites and bookmarks
4. Test subscription flow

### 4. Payment Flow Test
1. Get subscription plans
2. Initiate payment
3. Verify payment (use test Zarinpal credentials)
4. Check payment history

### 5. Admin Features Test
1. Login as admin
2. Test admin dashboard
3. Create/manage coupons
4. View user statistics

## Common Request Examples

### Register New User
```json
POST /auth/register
{
  "phone_number": "09123456789",
  "verification_code": "1234",
  "first_name": "علی",
  "last_name": "احمدی"
}
```

**Note**: The `role` field is optional. If not provided, the user will be assigned the `basic` role by default. Valid roles are: `parent`, `child`, `basic`.

### Create Subscription
```json
POST /subscriptions
{
  "plan_id": 1,
  "payment_method": "zarinpal",
  "coupon_code": "WELCOME20"
}
```

### Validate Coupon
```json
POST /coupons/validate
{
  "code": "WELCOME20",
  "amount": 50000
}
```

### Rate Story
```json
POST /stories/1/rating
{
  "rating": 5,
  "review": "عالی بود!"
}
```

## Error Handling

### Common HTTP Status Codes
- `200` - Success
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `422` - Validation Error
- `500` - Server Error

### Error Response Format
```json
{
  "message": "Error description",
  "errors": {
    "field_name": ["Validation error message"]
  }
}
```

## Authentication Notes

### Token Management
- Tokens are returned after successful login/registration
- Store token in `auth_token` environment variable
- Token expires after 24 hours (configurable)
- Use refresh endpoint to get new token

### Admin Access
- Admin endpoints require admin role
- Use admin login endpoint for admin access
- Admin tokens have extended permissions

## Testing Tips

1. **Use Environment Variables**: Always use the configured environment variables for base URL and tokens

2. **Test Error Cases**: Test with invalid data to verify error handling

3. **Check Response Headers**: Important information may be in response headers

4. **Validate JSON**: Ensure all JSON requests are properly formatted

5. **Test Pagination**: Use page and per_page parameters for paginated endpoints

6. **Monitor Rate Limits**: Some endpoints may have rate limiting

## Mobile App Integration

This API is designed for Flutter mobile app integration. Key considerations:

1. **SMS Verification**: Use phone number for authentication
2. **Offline Support**: Some endpoints support offline data
3. **Push Notifications**: FCM token management endpoints available
4. **Version Control**: Built-in app update management
5. **Payment Integration**: Zarinpal payment gateway support

## Support

For API issues or questions:
1. Check the health endpoints first
2. Verify authentication tokens
3. Review request/response formats
4. Check server logs for detailed errors

---

**Note**: This collection is regularly updated. Always use the latest version for the most current API endpoints and features.
