# Route Reorganization Summary - SarvCast

## Overview

This document summarizes the reorganization of routes between `api.php` and `web.php` to properly separate user-facing API functionality from admin-only functionality.

## Changes Made

### 1. Removed from `routes/api.php`

All admin-related routes have been moved from the API routes to web routes:

#### Admin Dashboard Routes
- `GET admin/dashboard/stats`
- `GET admin/analytics`

#### Admin Resource Management Routes
- `apiResource admin/stories`
- `apiResource admin/episodes` 
- `apiResource admin/categories`
- `apiResource admin/users`
- `GET admin/subscriptions`

#### Admin Image Timeline Routes
- `GET admin/episodes/{episode}/timeline`
- `POST admin/episodes/{episode}/timeline`
- `PUT admin/episodes/{episode}/timeline`
- `DELETE admin/episodes/{episode}/timeline`
- `GET admin/episodes/{episode}/timeline/statistics`
- `POST admin/timeline/validate`
- `POST admin/timeline/optimize`
- `POST admin/timeline/bulk-action`

#### Admin Audio Processing Routes
- `POST admin/audio/process`
- `POST admin/audio/extract-metadata`
- `POST admin/audio/convert`
- `POST admin/audio/normalize`
- `POST admin/audio/trim`
- `POST admin/audio/validate`
- `GET admin/audio/stats`
- `POST admin/audio/cleanup`

#### Admin Image Processing Routes
- `POST admin/image/process`
- `POST admin/image/resize`
- `POST admin/image/crop`
- `POST admin/image/watermark`
- `POST admin/image/optimize`
- `POST admin/image/thumbnail`
- `POST admin/image/multiple-sizes`
- `GET admin/image/info`
- `POST admin/image/validate`
- `GET admin/image/stats`
- `POST admin/image/cleanup`

#### Admin Audio Management Routes
- `GET admin/audio-management`
- `POST admin/audio-management/upload`
- `GET admin/audio-management/stats`
- `POST admin/audio-management/bulk-operation`

#### Admin Performance Monitoring Routes
- `GET admin/performance/dashboard`
- `GET admin/performance/statistics`
- `GET admin/performance/real-time`
- `GET admin/performance/report`
- `POST admin/performance/cleanup`
- `GET admin/performance/alerts`
- `GET admin/performance/timeline-metrics`
- `GET admin/performance/comment-metrics`

#### Admin Backup and Recovery Routes
- `POST admin/backup/create-full`
- `POST admin/backup/create-incremental`
- `GET admin/backup/list`
- `POST admin/backup/restore`
- `GET admin/backup/download/{backupId}`
- `DELETE admin/backup/{backupId}`
- `POST admin/backup/cleanup`
- `GET admin/backup/stats`
- `POST admin/backup/schedule`
- `GET admin/backup/schedule`

#### Admin In-App Notifications Routes
- `POST admin/notifications/create`
- `POST admin/notifications/send-multiple`
- `GET admin/notifications/statistics`
- `POST admin/notifications/cleanup-expired`

### 2. Added to `routes/web.php`

All admin routes have been added to the web routes under the admin middleware group with proper naming conventions:

```php
// Admin Dashboard API Routes
Route::prefix('api')->name('api.')->group(function () {
    // All admin API routes moved here with proper naming
});
```

### 3. Modified in `routes/api.php`

#### Image Timeline Routes (User Access Only)
**Before:**
```php
Route::prefix('episodes')->middleware('auth:sanctum')->group(function () {
    Route::get('{episodeId}/image-timeline', [ImageTimelineController::class, 'getTimeline']);
    Route::post('{episodeId}/image-timeline', [ImageTimelineController::class, 'saveTimeline']); // REMOVED
    Route::delete('{episodeId}/image-timeline', [ImageTimelineController::class, 'deleteTimeline']); // REMOVED
    Route::get('{episodeId}/image-for-time', [ImageTimelineController::class, 'getImageForTime']);
    Route::get('{episodeId}/timeline-statistics', [ImageTimelineController::class, 'getStatistics']); // REMOVED
});

Route::prefix('timeline')->middleware('auth:sanctum')->group(function () {
    Route::post('validate', [ImageTimelineController::class, 'validateTimeline']); // REMOVED
    Route::post('optimize', [ImageTimelineController::class, 'optimizeTimeline']); // REMOVED
});
```

**After:**
```php
// Image Timeline routes (User read-only access)
Route::prefix('episodes')->middleware('auth:sanctum')->group(function () {
    Route::get('{episodeId}/image-timeline', [ImageTimelineController::class, 'getTimeline']);
    Route::get('{episodeId}/image-for-time', [ImageTimelineController::class, 'getImageForTime']);
});
```

## Current API Structure

### User-Facing API Routes (`/api/v1/`)

#### Authentication
- `POST /api/v1/auth/send-verification-code`
- `POST /api/v1/auth/register`
- `POST /api/v1/auth/login`
- `POST /api/v1/auth/logout`
- `POST /api/v1/auth/refresh`
- `GET /api/v1/auth/profile`
- `PUT /api/v1/auth/profile`
- `POST /api/v1/auth/change-password`

#### Public Content
- `GET /api/v1/categories`
- `GET /api/v1/categories/{category}/stories`
- `GET /api/v1/stories`
- `GET /api/v1/stories/{story}`
- `GET /api/v1/stories/{story}/episodes`
- `GET /api/v1/episodes/{episode}`
- `GET /api/v1/people`
- `GET /api/v1/people/{person}`

#### User Features
- `GET /api/v1/user/favorites`
- `GET /api/v1/user/history`
- `POST /api/v1/user/profiles`
- `GET /api/v1/user/profiles`
- `PUT /api/v1/user/profiles/{profile}`
- `DELETE /api/v1/user/profiles/{profile}`

#### Story Interactions
- `POST /api/v1/stories/{story}/favorite`
- `DELETE /api/v1/stories/{story}/favorite`
- `POST /api/v1/stories/{story}/rating`

#### Episode Interactions
- `POST /api/v1/episodes/{episode}/play`
- `POST /api/v1/episodes/{episode}/bookmark`
- `DELETE /api/v1/episodes/{episode}/bookmark`

#### Image Timeline (Read-Only)
- `GET /api/v1/episodes/{episodeId}/image-timeline`
- `GET /api/v1/episodes/{episodeId}/image-for-time`

#### Story Comments
- `GET /api/v1/stories/{storyId}/comments`
- `POST /api/v1/stories/{storyId}/comments`
- `GET /api/v1/stories/{storyId}/comments/statistics`
- `GET /api/v1/comments/my-comments`
- `DELETE /api/v1/comments/{commentId}`

#### Subscriptions
- `GET /api/v1/subscriptions/plans`
- `POST /api/v1/subscriptions`
- `GET /api/v1/subscriptions/current`
- `POST /api/v1/subscriptions/cancel`

#### Payments
- `POST /api/v1/payments/initiate`
- `POST /api/v1/payments/verify`
- `GET /api/v1/payments/history`

#### Notifications
- `GET /api/v1/notifications`
- `PUT /api/v1/notifications/{notification}/read`
- `PUT /api/v1/notifications/read-all`

#### Mobile Features
- `GET /api/v1/mobile/config`
- `GET /api/v1/mobile/version`
- `GET /api/v1/mobile/offline/stories`
- `GET /api/v1/mobile/offline/episodes`
- `GET /api/v1/mobile/search`
- `GET /api/v1/mobile/recommendations`
- `GET /api/v1/mobile/trending`
- `GET /api/v1/mobile/preferences`
- `PUT /api/v1/mobile/preferences`
- `GET /api/v1/mobile/parental-controls`
- `PUT /api/v1/mobile/parental-controls`

#### Health Check
- `GET /api/v1/health`
- `GET /api/v1/health/metrics`
- `GET /api/v1/health/report`
- `GET /api/v1/health/errors`
- `GET /api/v1/health/performance`

### Admin-Only Routes (`/admin/`)

#### Admin Dashboard
- `GET /admin/` - Dashboard
- `GET /admin/api/dashboard/stats` - Dashboard API

#### Admin Resource Management
- `GET /admin/stories` - Stories management
- `GET /admin/episodes` - Episodes management
- `GET /admin/categories` - Categories management
- `GET /admin/users` - Users management
- `GET /admin/people` - People management

#### Admin Image Timeline Management
- `GET /admin/episodes/{episode}/timeline` - Timeline management page
- `GET /admin/api/episodes/{episode}/timeline` - Timeline API
- `POST /admin/api/episodes/{episode}/timeline` - Create timeline
- `PUT /admin/api/episodes/{episode}/timeline` - Update timeline
- `DELETE /admin/api/episodes/{episode}/timeline` - Delete timeline
- `GET /admin/api/episodes/{episode}/timeline/statistics` - Timeline statistics
- `POST /admin/api/timeline/validate` - Validate timeline
- `POST /admin/api/timeline/optimize` - Optimize timeline
- `POST /admin/api/timeline/bulk-action` - Bulk timeline actions

#### Admin Audio Management
- `GET /admin/audio` - Audio management page
- `GET /admin/api/audio-management` - Audio management API
- `POST /admin/api/audio-management/upload` - Upload audio
- `GET /admin/api/audio-management/stats` - Audio statistics
- `POST /admin/api/audio-management/bulk-operation` - Bulk audio operations

#### Admin Audio Processing
- `POST /admin/api/audio/process` - Process audio
- `POST /admin/api/audio/extract-metadata` - Extract metadata
- `POST /admin/api/audio/convert` - Convert format
- `POST /admin/api/audio/normalize` - Normalize audio
- `POST /admin/api/audio/trim` - Trim audio
- `POST /admin/api/audio/validate` - Validate audio
- `GET /admin/api/audio/stats` - Audio processing stats
- `POST /admin/api/audio/cleanup` - Cleanup audio files

#### Admin Image Processing
- `POST /admin/api/image/process` - Process image
- `POST /admin/api/image/resize` - Resize image
- `POST /admin/api/image/crop` - Crop image
- `POST /admin/api/image/watermark` - Add watermark
- `POST /admin/api/image/optimize` - Optimize image
- `POST /admin/api/image/thumbnail` - Generate thumbnail
- `POST /admin/api/image/multiple-sizes` - Generate multiple sizes
- `GET /admin/api/image/info` - Get image info
- `POST /admin/api/image/validate` - Validate image
- `GET /admin/api/image/stats` - Image processing stats
- `POST /admin/api/image/cleanup` - Cleanup image files

#### Admin Performance Monitoring
- `GET /admin/api/performance/dashboard` - Performance dashboard
- `GET /admin/api/performance/statistics` - Performance statistics
- `GET /admin/api/performance/real-time` - Real-time metrics
- `GET /admin/api/performance/report` - Performance report
- `POST /admin/api/performance/cleanup` - Cleanup performance data
- `GET /admin/api/performance/alerts` - Performance alerts
- `GET /admin/api/performance/timeline-metrics` - Timeline metrics
- `GET /admin/api/performance/comment-metrics` - Comment metrics

#### Admin Backup and Recovery
- `POST /admin/api/backup/create-full` - Create full backup
- `POST /admin/api/backup/create-incremental` - Create incremental backup
- `GET /admin/api/backup/list` - List backups
- `POST /admin/api/backup/restore` - Restore backup
- `GET /admin/api/backup/download/{backupId}` - Download backup
- `DELETE /admin/api/backup/{backupId}` - Delete backup
- `POST /admin/api/backup/cleanup` - Cleanup old backups
- `GET /admin/api/backup/stats` - Backup statistics
- `POST /admin/api/backup/schedule` - Schedule backups
- `GET /admin/api/backup/schedule` - Get backup schedule

#### Admin Notifications
- `GET /admin/notifications` - Notifications management
- `POST /admin/api/notifications/create` - Create notification
- `POST /admin/api/notifications/send-multiple` - Send multiple notifications
- `GET /admin/api/notifications/statistics` - Notification statistics
- `POST /admin/api/notifications/cleanup-expired` - Cleanup expired notifications

#### Admin Analytics
- `GET /admin/analytics` - Analytics dashboard
- `GET /admin/content-analytics` - Content analytics
- `GET /admin/user-analytics` - User analytics
- `GET /admin/revenue-analytics` - Revenue analytics
- `GET /admin/system-analytics` - System analytics

#### Admin Moderation
- `GET /admin/moderation` - Content moderation
- `GET /admin/moderation/{story}` - Story moderation
- `POST /admin/moderation/{story}/approve` - Approve story
- `POST /admin/moderation/{story}/reject` - Reject story
- `POST /admin/moderation/{story}/flag` - Flag story

## Security Benefits

### 1. **Separation of Concerns**
- User API routes are completely separate from admin functionality
- Admin routes require additional authentication and authorization
- Clear distinction between public and private functionality

### 2. **Access Control**
- Users can only access read-only timeline functionality
- All timeline creation, modification, and deletion is admin-only
- Audio and image processing is completely admin-only
- Performance monitoring and backup operations are admin-only

### 3. **API Security**
- User API is focused on consumption only
- No admin functions exposed to regular users
- Proper middleware separation between user and admin routes

## Route Naming Convention

### User API Routes
- All user routes use `/api/v1/` prefix
- Clear, descriptive endpoint names
- RESTful conventions where applicable

### Admin Routes
- All admin routes use `/admin/` prefix
- Admin API routes use `/admin/api/` prefix
- Consistent naming with `admin.api.` route names
- Clear separation between web interface and API endpoints

## Testing

### User API Testing
```bash
# Test user timeline access (read-only)
curl -H "Authorization: Bearer {user_token}" \
     -H "Accept: application/json" \
     "https://sarvcast.com/api/v1/episodes/123/image-timeline"

# Test user image for time
curl -H "Authorization: Bearer {user_token}" \
     -H "Accept: application/json" \
     "https://sarvcast.com/api/v1/episodes/123/image-for-time?time=30"
```

### Admin API Testing
```bash
# Test admin timeline management
curl -H "Authorization: Bearer {admin_token}" \
     -H "Accept: application/json" \
     "https://sarvcast.com/admin/api/episodes/123/timeline"

# Test admin timeline creation
curl -X POST \
     -H "Authorization: Bearer {admin_token}" \
     -H "Accept: application/json" \
     -H "Content-Type: application/json" \
     -d '{"timeline_data": [...]}' \
     "https://sarvcast.com/admin/api/episodes/123/timeline"
```

## Conclusion

The route reorganization successfully separates user-facing functionality from admin functionality:

✅ **User API** (`/api/v1/`) - Contains only user-facing features
✅ **Admin Web** (`/admin/`) - Contains admin interface and API endpoints
✅ **Security** - Proper access control and separation of concerns
✅ **Maintainability** - Clear organization and naming conventions
✅ **Functionality** - All features preserved with proper access levels

The system now properly enforces that:
- Users can only read timeline data
- Users cannot create, modify, or delete timelines
- All admin functions are properly protected
- Audio and image processing is admin-only
- Performance monitoring and backup operations are admin-only
