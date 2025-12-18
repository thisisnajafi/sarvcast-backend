# Flutter API Integration Guide for SarvCast

## Overview
This guide provides a comprehensive, ordered approach for Flutter developers to integrate with the SarvCast API endpoints. The endpoints are organized by feature implementation order, starting with core functionality and progressing to advanced features.

## Base Configuration
- **Base URL**: `https://my.sarvcast.ir/api/v1`
- **Response Format**: JSON
- **Authentication**: Bearer token (for user-specific data)
- **Content-Type**: `application/json`
- **Accept**: `application/json`

---

## 🚀 Phase 1: Core App Setup & Authentication

### 1.1 App Configuration
```dart
// Get app configuration and version info
GET /mobile/config
GET /mobile/version
```

**Implementation Order**: First - Initialize app with proper configuration

### 1.2 Device Registration
```dart
// Register device for push notifications
POST /mobile/device/register
POST /mobile/device/fcm-token
```

**Implementation Order**: Second - Set up device management

### 1.3 User Authentication Flow
```dart
// Step 1: Send verification code
POST /auth/send-verification-code
Body: {"phone_number": "09123456789"}

// Step 2: Register new user or login existing
POST /auth/register  // For new users
POST /auth/login     // For existing users
Body: {
  "phone_number": "09123456789",
  "verification_code": "1234",
  "first_name": "علی",
  "last_name": "احمدی",
  "role": "basic"  // Optional: parent, child, basic
}
```

**Implementation Order**: Third - Implement complete auth flow

---

## 🏠 Phase 2: Home Page Implementation

### 2.1 Featured Content
```dart
// Get featured stories for hero section
GET /stories/featured?limit=5
```

### 2.2 Categories Section
```dart
// Get all categories for categories grid
GET /categories?page=1&per_page=20
```

### 2.3 Popular Stories
```dart
// Get popular stories for trending section
GET /stories/popular?limit=10&period=week
```

### 2.4 Recent Stories
```dart
// Get recently added stories
GET /stories/recent?limit=10
```

### 2.5 Personalized Recommendations
```dart
// Get personalized recommendations (requires auth)
GET /stories/recommendations?limit=10
Headers: {"Authorization": "Bearer {token}"}
```

**Implementation Order**: Fourth - Build home page with all sections

---

## 📚 Phase 3: Content Discovery & Browsing

### 3.1 Story Listing
```dart
// Get all stories with filtering
GET /stories?page=1&per_page=20&sort_by=popular&age_group=6-10
```

### 3.2 Category-based Stories
```dart
// Get stories by category
GET /categories/{category_id}/stories?page=1&per_page=20
```

### 3.3 Story Details
```dart
// Get detailed story information
GET /stories/{story_id}
```

### 3.4 Episode Management
```dart
// Get episodes for a story
GET /stories/{story_id}/episodes?page=1&per_page=20

// Get episode details
GET /episodes/{episode_id}
```

**Implementation Order**: Fifth - Implement content browsing

---

## 🔍 Phase 4: Search & Discovery

### 4.1 Story Search
```dart
// Search stories with filters
GET /search/stories?q=ماجراجویی&category_id=1&age_group=6-10&min_rating=4.0
```

### 4.2 Global Search
```dart
// Search across all content types
GET /search/global?q=علی&limit=20
```

### 4.3 Search Suggestions
```dart
// Get search suggestions
GET /search/suggestions?q=ماج&limit=10
```

### 4.4 Trending Searches
```dart
// Get trending search terms
GET /search/trending?limit=10
```

**Implementation Order**: Sixth - Add search functionality

---

## 👥 Phase 5: People & Creators

### 5.1 People Listing
```dart
// Get all people (narrators, authors, etc.)
GET /people?page=1&per_page=20&role=narrator
```

### 5.2 Person Details
```dart
// Get person information
GET /people/{person_id}
```

### 5.3 Stories by Person
```dart
// Get stories by specific person
GET /people/{person_id}/stories?page=1&per_page=20
```

### 5.4 People Search
```dart
// Search people
GET /people/search?query=علی&role=narrator&limit=10
```

### 5.5 People by Role
```dart
// Get people filtered by role
GET /people/role/narrator
GET /people/role/author
GET /people/role/voice_actor
```

**Implementation Order**: Seventh - Add people/creators section

---

## 🎧 Phase 6: Audio Playback & Image Timeline

### 6.1 Episode Playback
```dart
// Start playing episode
POST /episodes/{episode_id}/play
Headers: {"Authorization": "Bearer {token}"}
```

### 6.2 Image Timeline
```dart
// Get image timeline for episode
GET /episodes/{episode_id}/image-timeline

// Get image for specific time
GET /episodes/{episode_id}/image-for-time?time=30
```

### 6.3 Advanced Timeline Features
```dart
// Get timeline with voice actors
GET /episodes/{episode_id}/image-timeline-with-voice-actors

// Get timeline for specific voice actor
GET /episodes/{episode_id}/image-timeline-for-voice-actor?voice_actor_id=1

// Get key frames
GET /episodes/{episode_id}/key-frames
```

**Implementation Order**: Eighth - Implement audio player with visual timeline

---

## ❤️ Phase 7: User Interactions

### 7.1 Favorites Management
```dart
// Add story to favorites
POST /stories/{story_id}/favorite
Headers: {"Authorization": "Bearer {token}"}

// Remove from favorites
DELETE /stories/{story_id}/favorite
Headers: {"Authorization": "Bearer {token}"}

// Get user favorites
GET /user/favorites?page=1&per_page=20
Headers: {"Authorization": "Bearer {token}"}
```

### 7.2 Rating & Reviews
```dart
// Rate a story
POST /stories/{story_id}/rating
Headers: {"Authorization": "Bearer {token}"}
Body: {
  "rating": 5,
  "review": "داستان بسیار زیبا بود"
}
```

### 7.3 Episode Bookmarks
```dart
// Bookmark episode
POST /episodes/{episode_id}/bookmark
Headers: {"Authorization": "Bearer {token}"}

// Remove bookmark
DELETE /episodes/{episode_id}/bookmark
Headers: {"Authorization": "Bearer {token}"}
```

**Implementation Order**: Ninth - Add user interaction features

---

## 💬 Phase 8: Social Features

### 8.1 Story Comments
```dart
// Get story comments
GET /stories/{story_id}/comments?page=1&per_page=20

// Add comment
POST /stories/{story_id}/comments
Headers: {"Authorization": "Bearer {token}"}
Body: {
  "content": "داستان بسیار زیبا و آموزنده بود",
  "parent_id": null  // null for top-level comment
}

// Like/unlike comment
POST /comments/{comment_id}/like
Headers: {"Authorization": "Bearer {token}"}
```

### 8.2 Social Sharing
```dart
// Share content
POST /social/share
Headers: {"Authorization": "Bearer {token}"}
Body: {
  "content_type": "story",
  "content_id": 1,
  "platform": "whatsapp"
}
```

### 8.3 User Following
```dart
// Follow user
POST /social/follow
Headers: {"Authorization": "Bearer {token}"}
Body: {"user_id": 123}

// Get followers/following
GET /social/followers?page=1&per_page=20
GET /social/following?page=1&per_page=20
```

**Implementation Order**: Tenth - Add social engagement features

---

## 📱 Phase 9: Offline Content

### 9.1 Offline Content Management
```dart
// Get offline stories
GET /mobile/offline/stories?page=1&per_page=20
Headers: {"Authorization": "Bearer {token}"}

// Get offline episodes
GET /mobile/offline/episodes?page=1&per_page=20
Headers: {"Authorization": "Bearer {token}"}

// Check download access
GET /mobile/offline/check-access?content_type=story&content_id=1
Headers: {"Authorization": "Bearer {token}"}
```

### 9.2 Download Tracking
```dart
// Track download
POST /mobile/offline/track-download
Headers: {"Authorization": "Bearer {token}"}
Body: {
  "content_type": "episode",
  "content_id": 1,
  "action": "download_started"
}

// Get download history
GET /mobile/offline/download-history?page=1&per_page=20
Headers: {"Authorization": "Bearer {token}"}
```

**Implementation Order**: Eleventh - Implement offline functionality

---

## 👨‍👩‍👧‍👦 Phase 10: Parental Controls

### 10.1 Parental Control Setup
```dart
// Get parental controls
GET /parental-controls
Headers: {"Authorization": "Bearer {token}"}

// Update parental controls
PUT /parental-controls
Headers: {"Authorization": "Bearer {token}"}
Body: {
  "age_restriction": "6-10",
  "content_filters": ["violence", "scary"],
  "time_limits": {
    "daily_limit": 120,
    "bedtime_start": "21:00",
    "bedtime_end": "07:00"
  }
}
```

### 10.2 Child Profile Management
```dart
// Get child profiles
GET /parental-controls/child-profiles
Headers: {"Authorization": "Bearer {token}"}

// Create child profile
POST /parental-controls/child-profiles
Headers: {"Authorization": "Bearer {token}"}
Body: {
  "name": "علی",
  "age": 8,
  "preferences": {
    "favorite_categories": [1, 2],
    "disliked_content": [3]
  }
}
```

**Implementation Order**: Twelfth - Add parental control features

---

## ⚙️ Phase 11: User Preferences & Settings

### 11.1 User Preferences
```dart
// Get user preferences
GET /user/preferences
Headers: {"Authorization": "Bearer {token}"}

// Update preferences
PUT /user/preferences
Headers: {"Authorization": "Bearer {token}"}
Body: {
  "language": "fa",
  "audio_quality": "high",
  "auto_play": true,
  "notifications": {
    "new_stories": true,
    "recommendations": false
  }
}
```

### 11.2 Profile Management
```dart
// Get user profile
GET /auth/profile
Headers: {"Authorization": "Bearer {token}"}

// Update profile
PUT /auth/profile
Headers: {"Authorization": "Bearer {token}"}
Body: {
  "first_name": "علی",
  "last_name": "احمدی",
  "profile_image_url": "https://example.com/image.jpg"
}
```

**Implementation Order**: Thirteenth - Add settings and preferences

---

## 📊 Phase 12: Analytics & History

### 12.1 Play History
```dart
// Get play history
GET /user/history?page=1&per_page=20
Headers: {"Authorization": "Bearer {token}"}

// Track play activity
POST /mobile/track-play
Headers: {"Authorization": "Bearer {token}"}
Body: {
  "episode_id": 1,
  "duration": 300,
  "completed": false,
  "timestamp": "2024-01-01T12:00:00Z"
}
```

### 12.2 Analytics
```dart
// Get user analytics
GET /analytics/user-stats
Headers: {"Authorization": "Bearer {token}"}

// Get content analytics
GET /analytics/content-stats?content_type=story&content_id=1
Headers: {"Authorization": "Bearer {token}"}
```

**Implementation Order**: Fourteenth - Add analytics and tracking

---

## 💳 Phase 13: Subscription & Payments

### 13.1 Subscription Management
```dart
// Get subscription plans
GET /subscriptions/plans

// Get user subscription
GET /subscriptions
Headers: {"Authorization": "Bearer {token}"}

// Subscribe to plan
POST /subscriptions/subscribe
Headers: {"Authorization": "Bearer {token}"}
Body: {
  "plan_id": 1,
  "payment_method": "zarinpal"
}
```

### 13.2 Payment Processing
```dart
// Process payment
POST /payments/process
Headers: {"Authorization": "Bearer {token}"}
Body: {
  "amount": 50000,
  "description": "اشتراک ماهانه",
  "callback_url": "https://app.sarvcast.ir/callback"
}

// Verify payment
POST /payments/verify
Headers: {"Authorization": "Bearer {token}"}
Body: {
  "authority": "A000000000000000000000000000000000000",
  "status": "OK"
}
```

### 13.3 Coupon System
```dart
// Apply coupon
POST /coupons/apply
Headers: {"Authorization": "Bearer {token}"}
Body: {
  "code": "WELCOME20",
  "subscription_id": 1
}
```

**Implementation Order**: Fifteenth - Add monetization features

---

## 🔔 Phase 14: Notifications

### 14.1 Notification Management
```dart
// Get notifications
GET /notifications?page=1&per_page=20
Headers: {"Authorization": "Bearer {token}"}

// Mark notification as read
PUT /notifications/{notification_id}/read
Headers: {"Authorization": "Bearer {token}"}

// Get notification preferences
GET /notifications/preferences
Headers: {"Authorization": "Bearer {token}"}
```

### 14.2 Push Notifications
```dart
// Update FCM token
POST /mobile/device/fcm-token
Headers: {"Authorization": "Bearer {token}"}
Body: {
  "fcm_token": "fcm_token_here",
  "device_type": "android"
}
```

**Implementation Order**: Sixteenth - Add notification system

---

## 🏥 Phase 15: Health & Monitoring

### 15.1 Health Checks
```dart
// Check API health
GET /health/check

// Get API metrics
GET /health/metrics
```

### 15.2 Error Handling
```dart
// Report errors
POST /mobile/error-report
Body: {
  "error_type": "crash",
  "error_message": "Null pointer exception",
  "stack_trace": "...",
  "device_info": {
    "os": "Android",
    "version": "12",
    "app_version": "1.0.0"
  }
}
```

**Implementation Order**: Seventeenth - Add monitoring and error handling

---

## 📋 Implementation Checklist

### Phase 1: Core Setup ✅
- [ ] App configuration
- [ ] Device registration
- [ ] Authentication flow

### Phase 2: Home Page ✅
- [ ] Featured stories
- [ ] Categories grid
- [ ] Popular stories
- [ ] Recent stories
- [ ] Recommendations

### Phase 3: Content Discovery ✅
- [ ] Story listing
- [ ] Category filtering
- [ ] Story details
- [ ] Episode management

### Phase 4: Search ✅
- [ ] Story search
- [ ] Global search
- [ ] Search suggestions
- [ ] Trending searches

### Phase 5: People ✅
- [ ] People listing
- [ ] Person details
- [ ] Stories by person
- [ ] People search

### Phase 6: Audio Player ✅
- [ ] Episode playback
- [ ] Image timeline
- [ ] Advanced timeline features

### Phase 7: User Interactions ✅
- [ ] Favorites
- [ ] Ratings
- [ ] Bookmarks

### Phase 8: Social Features ✅
- [ ] Comments
- [ ] Sharing
- [ ] Following

### Phase 9: Offline Content ✅
- [ ] Offline management
- [ ] Download tracking

### Phase 10: Parental Controls ✅
- [ ] Control setup
- [ ] Child profiles

### Phase 11: Settings ✅
- [ ] User preferences
- [ ] Profile management

### Phase 12: Analytics ✅
- [ ] Play history
- [ ] User analytics

### Phase 13: Monetization ✅
- [ ] Subscriptions
- [ ] Payments
- [ ] Coupons

### Phase 14: Notifications ✅
- [ ] Notification management
- [ ] Push notifications

### Phase 15: Monitoring ✅
- [ ] Health checks
- [ ] Error reporting

---

## 🛠️ Flutter Implementation Tips

### 1. HTTP Client Setup
```dart
class ApiClient {
  static const String baseUrl = 'https://my.sarvcast.ir/api/v1';
  static const Map<String, String> headers = {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  };
  
  static Future<Map<String, String>> getAuthHeaders() async {
    final token = await getStoredToken();
    return {
      ...headers,
      'Authorization': 'Bearer $token',
    };
  }
}
```

### 2. Error Handling
```dart
class ApiException implements Exception {
  final String message;
  final int? statusCode;
  
  ApiException(this.message, [this.statusCode]);
  
  @override
  String toString() => 'ApiException: $message';
}
```

### 3. Response Models
```dart
class ApiResponse<T> {
  final bool success;
  final String message;
  final T? data;
  final Map<String, dynamic>? errors;
  
  ApiResponse({
    required this.success,
    required this.message,
    this.data,
    this.errors,
  });
  
  factory ApiResponse.fromJson(Map<String, dynamic> json, T Function(dynamic) fromJsonT) {
    return ApiResponse<T>(
      success: json['success'] ?? false,
      message: json['message'] ?? '',
      data: json['data'] != null ? fromJsonT(json['data']) : null,
      errors: json['errors'],
    );
  }
}
```

### 4. Caching Strategy
- Use `cache.api` middleware for public endpoints
- Implement local caching for offline content
- Cache user preferences and settings
- Store authentication tokens securely

### 5. Performance Optimization
- Implement pagination for all list endpoints
- Use lazy loading for images and audio
- Cache frequently accessed data
- Implement proper error boundaries

---

## 📞 Support

For questions or clarifications about this API integration guide, please refer to:
- **API Documentation**: `docs/LARAVEL_API_DOCUMENTATION.md`
- **Postman Collection**: `SarvCast_API.postman_collection.json`
- **Testing Guide**: `docs/POSTMAN_API_TESTING_GUIDE.md`

**Last Updated**: January 2024  
**Version**: 1.0  
**Status**: Ready for Flutter Implementation

