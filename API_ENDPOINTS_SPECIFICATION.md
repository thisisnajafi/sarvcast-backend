# SarvCast API Endpoints Specification

## Base URL
```
https://api.sarvcast.com/v1
```

## Authentication
All protected endpoints require Bearer token in Authorization header:
```
Authorization: Bearer {jwt_token}
```

## Response Format
```json
{
    "success": true,
    "message": "Success message",
    "data": {},
    "meta": {
        "pagination": {
            "current_page": 1,
            "last_page": 10,
            "per_page": 20,
            "total": 200
        }
    }
}
```

## Authentication Endpoints

### POST /auth/register
Register new user account.

**Request:**
```json
{
    "email": "user@example.com",
    "phone_number": "+989123456789",
    "first_name": "علی",
    "last_name": "احمدی",
    "password": "password123",
    "password_confirmation": "password123",
    "role": "parent"
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "user": {
            "id": 1,
            "email": "user@example.com",
            "first_name": "علی",
            "last_name": "احمدی",
            "role": "parent",
            "status": "pending"
        },
        "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
    }
}
```

### POST /auth/login
Authenticate user and return access token.

### POST /auth/logout
Logout user and invalidate token.

### POST /auth/refresh
Refresh access token.

### POST /auth/forgot-password
Send password reset email.

### POST /auth/reset-password
Reset password with token.

## Story Endpoints

### GET /stories
Get paginated list of stories.

**Query Parameters:**
- `page`: Page number (default: 1)
- `per_page`: Items per page (default: 20)
- `category_id`: Filter by category
- `age_group`: Filter by age group
- `is_premium`: Filter by premium status
- `search`: Search in title and description
- `sort`: Sort by (newest, oldest, popular, rating)

### GET /stories/{id}
Get detailed story information.

### GET /stories/{id}/episodes
Get episodes for a specific story.

### POST /stories/{id}/favorite
Add story to favorites.

### DELETE /stories/{id}/favorite
Remove story from favorites.

### POST /stories/{id}/rating
Rate a story (1-5 stars).

## Episode Endpoints

### GET /episodes/{id}
Get episode details.

### POST /episodes/{id}/play
Record episode play.

**Request:**
```json
{
    "duration_played": 300,
    "total_duration": 900,
    "completed": false,
    "device_info": {
        "platform": "android",
        "version": "1.0.0"
    }
}
```

### POST /episodes/{id}/bookmark
Bookmark an episode.

### DELETE /episodes/{id}/bookmark
Remove bookmark.

## Category Endpoints

### GET /categories
Get all active categories.

### GET /categories/{id}/stories
Get stories in a category.

## User Endpoints

### GET /user/profile
Get current user profile.

### PUT /user/profile
Update user profile.

### GET /user/favorites
Get user's favorite stories.

### GET /user/history
Get user's play history.

### POST /user/profiles
Create child profile.

### GET /user/profiles
Get child profiles.

### PUT /user/profiles/{id}
Update child profile.

### DELETE /user/profiles/{id}
Delete child profile.

## Subscription Endpoints

### GET /subscriptions/plans
Get available subscription plans.

### POST /subscriptions
Create new subscription.

### GET /subscriptions/current
Get current user's subscription.

### POST /subscriptions/cancel
Cancel subscription.

## Payment Endpoints

### POST /payments/initiate
Initiate payment process.

### POST /payments/verify
Verify payment.

### GET /payments/history
Get payment history.

## Notification Endpoints

### GET /notifications
Get user notifications.

### PUT /notifications/{id}/read
Mark notification as read.

### PUT /notifications/read-all
Mark all notifications as read.

## Admin Endpoints

### GET /admin/dashboard/stats
Get dashboard statistics.

### GET /admin/stories
Get all stories for admin.

### POST /admin/stories
Create new story.

### PUT /admin/stories/{id}
Update story.

### DELETE /admin/stories/{id}
Delete story.

### GET /admin/users
Get all users for admin.

### PUT /admin/users/{id}/status
Update user status.

### GET /admin/subscriptions
Get all subscriptions.

### GET /admin/analytics
Get analytics data.

## Error Codes

- `400`: Bad Request
- `401`: Unauthorized
- `403`: Forbidden
- `404`: Not Found
- `422`: Validation Error
- `429`: Too Many Requests
- `500`: Internal Server Error

## Rate Limiting

- Authenticated users: 1000 requests/hour
- Unauthenticated users: 100 requests/hour
- Admin users: 5000 requests/hour
