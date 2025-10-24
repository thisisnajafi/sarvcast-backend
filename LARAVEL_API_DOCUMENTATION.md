# SarvCast Laravel API & Admin Dashboard Documentation

## Project Overview

SarvCast is a Persian children's audio story platform with Flutter mobile app, Laravel API backend, and Tailwind CSS admin dashboard. This documentation covers the complete Laravel backend implementation.

## Table of Contents

1. [Project Structure](#project-structure)
2. [Database Schema](#database-schema)
3. [API Endpoints](#api-endpoints)
4. [Authentication & Authorization](#authentication--authorization)
5. [Admin Dashboard Features](#admin-dashboard-features)
6. [File Storage & Media Management](#file-storage--media-management)
7. [Payment Integration](#payment-integration)
8. [Notification System](#notification-system)
9. [Analytics & Reporting](#analytics--reporting)
10. [Security & Privacy](#security--privacy)
11. [Deployment & DevOps](#deployment--devops)
12. [Development Tasks](#development-tasks)

## Project Structure

```
sarvcast-backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/
│   │   │   │   ├── AuthController.php
│   │   │   │   ├── StoryController.php
│   │   │   │   ├── EpisodeController.php
│   │   │   │   ├── CategoryController.php
│   │   │   │   ├── UserController.php
│   │   │   │   ├── SubscriptionController.php
│   │   │   │   ├── PaymentController.php
│   │   │   │   ├── NotificationController.php
│   │   │   │   └── AnalyticsController.php
│   │   │   └── Admin/
│   │   │       ├── DashboardController.php
│   │   │       ├── StoryManagementController.php
│   │   │       ├── UserManagementController.php
│   │   │       ├── SubscriptionManagementController.php
│   │   │       ├── ContentModerationController.php
│   │   │       ├── AnalyticsController.php
│   │   │       └── SettingsController.php
│   │   ├── Middleware/
│   │   │   ├── ApiAuth.php
│   │   │   ├── AdminAuth.php
│   │   │   ├── ParentalControl.php
│   │   │   └── RateLimiting.php
│   │   └── Requests/
│   │       ├── StoryRequest.php
│   │       ├── EpisodeRequest.php
│   │       ├── UserRequest.php
│   │       └── SubscriptionRequest.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── UserProfile.php
│   │   ├── Story.php
│   │   ├── Episode.php
│   │   ├── Category.php
│   │   ├── Person.php
│   │   ├── Subscription.php
│   │   ├── Payment.php
│   │   ├── Notification.php
│   │   ├── PlayHistory.php
│   │   ├── Favorite.php
│   │   └── Rating.php
│   ├── Services/
│   │   ├── AudioProcessingService.php
│   │   ├── PaymentService.php
│   │   ├── NotificationService.php
│   │   ├── AnalyticsService.php
│   │   ├── ContentModerationService.php
│   │   └── FileStorageService.php
│   └── Jobs/
│       ├── ProcessAudioFile.php
│       ├── SendNotification.php
│       ├── GenerateAnalytics.php
│       └── CleanupExpiredFiles.php
├── database/
│   ├── migrations/
│   ├── seeders/
│   └── factories/
├── resources/
│   ├── views/
│   │   └── admin/
│   │       ├── dashboard.blade.php
│   │       ├── stories/
│   │       ├── users/
│   │       ├── subscriptions/
│   │       └── analytics/
│   └── js/
│       └── admin/
├── routes/
│   ├── api.php
│   ├── web.php
│   └── admin.php
└── storage/
    ├── app/
    │   ├── public/
    │   │   ├── stories/
    │   │   ├── episodes/
    │   │   ├── categories/
    │   │   └── users/
    │   └── private/
    │       ├── audio/
    │       └── temp/
    └── logs/
```

## Database Schema

### Core Tables

#### users
```sql
CREATE TABLE users (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    phone_number VARCHAR(20) UNIQUE NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    profile_image_url VARCHAR(500) NULL,
    role ENUM('parent', 'child', 'admin') DEFAULT 'parent',
    status ENUM('active', 'inactive', 'suspended', 'pending') DEFAULT 'pending',
    email_verified_at TIMESTAMP NULL,
    phone_verified_at TIMESTAMP NULL,
    parent_id BIGINT UNSIGNED NULL,
    language VARCHAR(10) DEFAULT 'fa',
    timezone VARCHAR(50) DEFAULT 'Asia/Tehran',
    preferences JSON NULL,
    last_login_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (parent_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_email (email),
    INDEX idx_phone (phone_number),
    INDEX idx_parent (parent_id),
    INDEX idx_status (status)
);
```

#### user_profiles
```sql
CREATE TABLE user_profiles (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    avatar_url VARCHAR(500) NULL,
    age INT NOT NULL,
    favorite_category_id BIGINT UNSIGNED NULL,
    preferences JSON NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (favorite_category_id) REFERENCES categories(id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_age (age)
);
```

#### categories
```sql
CREATE TABLE categories (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT NULL,
    icon_path VARCHAR(500) NULL,
    color VARCHAR(7) DEFAULT '#4A90E2',
    story_count INT DEFAULT 0,
    total_episodes INT DEFAULT 0,
    total_duration INT DEFAULT 0, -- in minutes
    average_rating DECIMAL(3,2) DEFAULT 0.00,
    is_active BOOLEAN DEFAULT TRUE,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_active (is_active),
    INDEX idx_sort (sort_order)
);
```

#### stories
```sql
CREATE TABLE stories (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    subtitle VARCHAR(300) NULL,
    description TEXT NOT NULL,
    image_url VARCHAR(500) NOT NULL,
    cover_image_url VARCHAR(500) NULL,
    category_id BIGINT UNSIGNED NOT NULL,
    director_id BIGINT UNSIGNED NULL,
    writer_id BIGINT UNSIGNED NULL,
    author_id BIGINT UNSIGNED NULL,
    narrator_id BIGINT UNSIGNED NULL,
    age_group VARCHAR(20) NOT NULL,
    language VARCHAR(10) DEFAULT 'fa',
    duration INT NOT NULL, -- total duration in minutes
    total_episodes INT DEFAULT 0,
    free_episodes INT DEFAULT 0,
    is_premium BOOLEAN DEFAULT FALSE,
    is_completely_free BOOLEAN DEFAULT FALSE,
    play_count INT DEFAULT 0,
    rating DECIMAL(3,2) DEFAULT 0.00,
    tags JSON NULL,
    status ENUM('draft', 'pending', 'approved', 'rejected', 'published') DEFAULT 'draft',
    published_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT,
    FOREIGN KEY (director_id) REFERENCES people(id) ON DELETE SET NULL,
    FOREIGN KEY (writer_id) REFERENCES people(id) ON DELETE SET NULL,
    FOREIGN KEY (author_id) REFERENCES people(id) ON DELETE SET NULL,
    FOREIGN KEY (narrator_id) REFERENCES people(id) ON DELETE SET NULL,
    INDEX idx_category (category_id),
    INDEX idx_status (status),
    INDEX idx_premium (is_premium),
    INDEX idx_age_group (age_group),
    FULLTEXT idx_search (title, subtitle, description)
);
```

#### episodes
```sql
CREATE TABLE episodes (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    story_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT NULL,
    audio_url VARCHAR(500) NOT NULL,
    local_audio_path VARCHAR(500) NULL,
    duration INT NOT NULL, -- in minutes
    episode_number INT NOT NULL,
    is_premium BOOLEAN DEFAULT FALSE,
    image_urls JSON NULL, -- array of image URLs
    play_count INT DEFAULT 0,
    rating DECIMAL(3,2) DEFAULT 0.00,
    tags JSON NULL,
    status ENUM('draft', 'pending', 'approved', 'rejected', 'published') DEFAULT 'draft',
    published_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (story_id) REFERENCES stories(id) ON DELETE CASCADE,
    INDEX idx_story (story_id),
    INDEX idx_episode_number (episode_number),
    INDEX idx_status (status),
    INDEX idx_premium (is_premium),
    UNIQUE KEY unique_story_episode (story_id, episode_number)
);
```

#### people
```sql
CREATE TABLE people (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    bio TEXT NULL,
    image_url VARCHAR(500) NULL,
    roles JSON NOT NULL, -- array of roles: voice_actor, director, writer, producer
    total_stories INT DEFAULT 0,
    total_episodes INT DEFAULT 0,
    average_rating DECIMAL(3,2) DEFAULT 0.00,
    is_verified BOOLEAN DEFAULT FALSE,
    last_active_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_name (name),
    INDEX idx_verified (is_verified),
    FULLTEXT idx_search (name, bio)
);
```

#### subscriptions
```sql
CREATE TABLE subscriptions (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    type ENUM('monthly', 'quarterly', 'yearly', 'family') NOT NULL,
    status ENUM('active', 'expired', 'cancelled', 'pending', 'trial') DEFAULT 'pending',
    start_date TIMESTAMP NOT NULL,
    end_date TIMESTAMP NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'IRR',
    auto_renew BOOLEAN DEFAULT TRUE,
    payment_method VARCHAR(50) NULL,
    transaction_id VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_status (status),
    INDEX idx_end_date (end_date)
);
```

#### payments
```sql
CREATE TABLE payments (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    subscription_id BIGINT UNSIGNED NULL,
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'IRR',
    payment_method VARCHAR(50) NOT NULL,
    transaction_id VARCHAR(100) UNIQUE NOT NULL,
    gateway_response JSON NULL,
    status ENUM('pending', 'completed', 'failed', 'cancelled', 'refunded') DEFAULT 'pending',
    processed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (subscription_id) REFERENCES subscriptions(id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_transaction (transaction_id),
    INDEX idx_status (status)
);
```

#### play_histories
```sql
CREATE TABLE play_histories (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    episode_id BIGINT UNSIGNED NOT NULL,
    story_id BIGINT UNSIGNED NOT NULL,
    played_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    duration_played INT NOT NULL, -- in seconds
    total_duration INT NOT NULL, -- in seconds
    completed BOOLEAN DEFAULT FALSE,
    device_info JSON NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (episode_id) REFERENCES episodes(id) ON DELETE CASCADE,
    FOREIGN KEY (story_id) REFERENCES stories(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_episode (episode_id),
    INDEX idx_played_at (played_at)
);
```

#### favorites
```sql
CREATE TABLE favorites (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    story_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (story_id) REFERENCES stories(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_story (user_id, story_id),
    INDEX idx_user (user_id)
);
```

#### ratings
```sql
CREATE TABLE ratings (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    story_id BIGINT UNSIGNED NULL,
    episode_id BIGINT UNSIGNED NULL,
    rating TINYINT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    review TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (story_id) REFERENCES stories(id) ON DELETE CASCADE,
    FOREIGN KEY (episode_id) REFERENCES episodes(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_story_rating (user_id, story_id),
    UNIQUE KEY unique_user_episode_rating (user_id, episode_id),
    INDEX idx_user (user_id),
    INDEX idx_story (story_id),
    INDEX idx_episode (episode_id)
);
```

#### notifications
```sql
CREATE TABLE notifications (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NULL, -- NULL for broadcast notifications
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'warning', 'success', 'error', 'promotional') DEFAULT 'info',
    data JSON NULL,
    scheduled_at TIMESTAMP NULL,
    sent_at TIMESTAMP NULL,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_scheduled (scheduled_at),
    INDEX idx_sent (sent_at)
);
```

## API Endpoints

### Authentication Endpoints

#### POST /api/auth/register
Register a new user account.

**Request Body:**
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
    "message": "User registered successfully",
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

#### POST /api/auth/login
Authenticate user and return access token.

**Request Body:**
```json
{
    "email": "user@example.com",
    "password": "password123"
}
```

#### POST /api/auth/logout
Logout user and invalidate token.

#### POST /api/auth/refresh
Refresh access token.

#### POST /api/auth/forgot-password
Send password reset email.

#### POST /api/auth/reset-password
Reset password with token.

### Story Endpoints

#### GET /api/stories
Get paginated list of stories with filters.

**Query Parameters:**
- `page`: Page number (default: 1)
- `per_page`: Items per page (default: 20)
- `category_id`: Filter by category
- `age_group`: Filter by age group
- `is_premium`: Filter by premium status
- `search`: Search in title and description
- `sort`: Sort by (newest, oldest, popular, rating)

**Response:**
```json
{
    "success": true,
    "data": {
        "stories": [
            {
                "id": 1,
                "title": "ماجراجویی در جنگل جادویی",
                "subtitle": "داستان پسر کوچکی که در جنگل جادویی گم می‌شود",
                "description": "داستان هیجان‌انگیز...",
                "image_url": "https://api.sarvcast.com/storage/stories/story1.jpg",
                "cover_image_url": "https://api.sarvcast.com/storage/stories/story1_cover.jpg",
                "category": {
                    "id": 1,
                    "name": "ماجراجویی",
                    "color": "#FF6B6B"
                },
                "director": {
                    "id": 1,
                    "name": "علی احمدی"
                },
                "narrator": {
                    "id": 2,
                    "name": "مریم کریمی"
                },
                "age_group": "7-9",
                "duration": 45,
                "total_episodes": 3,
                "free_episodes": 1,
                "is_premium": false,
                "is_completely_free": false,
                "play_count": 1250,
                "rating": 4.5,
                "tags": ["ماجراجویی", "جنگل", "جادویی"],
                "status": "published",
                "published_at": "2024-01-15T10:00:00Z",
                "created_at": "2024-01-10T10:00:00Z"
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

#### GET /api/stories/{id}
Get detailed story information.

#### GET /api/stories/{id}/episodes
Get episodes for a specific story.

#### POST /api/stories/{id}/favorite
Add story to favorites.

#### DELETE /api/stories/{id}/favorite
Remove story from favorites.

#### POST /api/stories/{id}/rating
Rate a story.

### Episode Endpoints

#### GET /api/episodes/{id}
Get episode details.

#### POST /api/episodes/{id}/play
Record episode play.

**Request Body:**
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

#### POST /api/episodes/{id}/bookmark
Bookmark an episode.

#### DELETE /api/episodes/{id}/bookmark
Remove bookmark.

### Category Endpoints

#### GET /api/categories
Get all active categories.

#### GET /api/categories/{id}/stories
Get stories in a category.

### User Endpoints

#### GET /api/user/profile
Get current user profile.

#### PUT /api/user/profile
Update user profile.

#### GET /api/user/favorites
Get user's favorite stories.

#### GET /api/user/history
Get user's play history.

#### POST /api/user/profiles
Create child profile.

#### GET /api/user/profiles
Get child profiles.

#### PUT /api/user/profiles/{id}
Update child profile.

#### DELETE /api/user/profiles/{id}
Delete child profile.

### Subscription Endpoints

#### GET /api/subscriptions/plans
Get available subscription plans.

#### POST /api/subscriptions
Create new subscription.

#### GET /api/subscriptions/current
Get current user's subscription.

#### POST /api/subscriptions/cancel
Cancel subscription.

### Payment Endpoints

#### POST /api/payments/initiate
Initiate payment process.

#### POST /api/payments/verify
Verify payment.

#### GET /api/payments/history
Get payment history.

### Notification Endpoints

#### GET /api/notifications
Get user notifications.

#### PUT /api/notifications/{id}/read
Mark notification as read.

#### PUT /api/notifications/read-all
Mark all notifications as read.

## Authentication & Authorization

### JWT Token Structure
```json
{
    "iss": "sarvcast-api",
    "aud": "sarvcast-app",
    "iat": 1640995200,
    "exp": 1641081600,
    "sub": "1",
    "role": "parent",
    "status": "active"
}
```

### Middleware

#### ApiAuth Middleware
- Validates JWT token
- Sets authenticated user in request
- Handles token refresh

#### AdminAuth Middleware
- Validates admin role
- Additional security checks

#### ParentalControl Middleware
- Enforces parental controls
- Age-appropriate content filtering
- Time restrictions

#### RateLimiting Middleware
- API rate limiting
- Per-user limits
- Different limits for different endpoints

## Admin Dashboard Features

### Dashboard Overview
- User statistics
- Story performance metrics
- Revenue analytics
- System health monitoring

### Story Management
- Create/edit stories
- Episode management
- Content moderation
- Bulk operations
- SEO optimization

### User Management
- User accounts overview
- Child profile management
- Subscription management
- User activity monitoring

### Content Moderation
- Story approval workflow
- Episode review process
- Content flagging system
- Automated content scanning

### Analytics & Reporting
- User engagement metrics
- Story performance reports
- Revenue reports
- Custom date ranges

### System Settings
- App configuration
- Payment gateway settings
- Notification templates
- Feature flags

## File Storage & Media Management

### Storage Structure
```
storage/
├── app/
│   ├── public/
│   │   ├── stories/
│   │   │   ├── {story_id}/
│   │   │   │   ├── cover.jpg
│   │   │   │   ├── thumbnail.jpg
│   │   │   │   └── images/
│   │   ├── episodes/
│   │   │   ├── {episode_id}/
│   │   │   │   ├── audio.mp3
│   │   │   │   └── images/
│   │   ├── categories/
│   │   │   └── {category_id}/
│   │   └── users/
│   │       └── {user_id}/
│   └── private/
│       ├── audio/
│       │   ├── original/
│       │   ├── processed/
│       │   └── temp/
│       └── backups/
```

### File Processing Pipeline
1. **Upload**: Files uploaded to temp directory
2. **Validation**: File type, size, and content validation
3. **Processing**: Audio compression, image optimization
4. **Storage**: Move to permanent storage
5. **CDN**: Upload to CDN for fast delivery

### Audio Processing
- Format conversion (MP3, AAC)
- Quality optimization
- Metadata extraction
- Waveform generation

### Image Processing
- Automatic resizing
- Format optimization
- Thumbnail generation
- Watermarking

## Payment Integration

### Supported Gateways
- ZarinPal (Primary)
- Pay.ir
- Mellat Bank
- Saman Bank

### Payment Flow
1. **Initiate**: Create payment request
2. **Redirect**: User redirected to gateway
3. **Callback**: Handle payment result
4. **Verify**: Verify payment with gateway
5. **Complete**: Activate subscription

### Subscription Management
- Automatic renewal
- Grace period handling
- Proration calculations
- Refund processing

## Notification System

### Notification Types
- **System**: App updates, maintenance
- **Content**: New stories, episodes
- **Subscription**: Payment reminders, expiry
- **Engagement**: Achievement, milestones
- **Promotional**: Offers, discounts

### Delivery Channels
- Push notifications
- Email notifications
- SMS notifications
- In-app notifications

### Scheduling
- Immediate delivery
- Scheduled delivery
- Recurring notifications
- Time zone handling

## Analytics & Reporting

### User Analytics
- Registration trends
- Active users
- Session duration
- Feature usage

### Content Analytics
- Story popularity
- Episode completion rates
- Category performance
- Search analytics

### Revenue Analytics
- Subscription revenue
- Payment success rates
- Churn analysis
- Lifetime value

### Technical Analytics
- API performance
- Error rates
- Storage usage
- CDN performance

## Security & Privacy

### Data Protection
- GDPR compliance
- Data encryption
- Secure storage
- Regular backups

### API Security
- Rate limiting
- Input validation
- SQL injection prevention
- XSS protection

### User Privacy
- Data anonymization
- Consent management
- Right to deletion
- Data portability

### Child Safety
- COPPA compliance
- Content filtering
- Parental controls
- Safe browsing

## Deployment & DevOps

### Environment Setup
- Development
- Staging
- Production

### CI/CD Pipeline
- Automated testing
- Code quality checks
- Security scanning
- Deployment automation

### Monitoring
- Application monitoring
- Error tracking
- Performance monitoring
- Uptime monitoring

### Backup Strategy
- Database backups
- File backups
- Configuration backups
- Disaster recovery

## Development Tasks

### Phase 1: Core Setup (Week 1-2)
1. **Project Initialization**
   - Laravel project setup
   - Database configuration
   - Basic authentication
   - API structure

2. **Database Design**
   - Create migrations
   - Set up relationships
   - Add indexes
   - Create seeders

3. **Basic API Endpoints**
   - Authentication endpoints
   - User management
   - Basic CRUD operations

### Phase 2: Content Management (Week 3-4)
1. **Story Management**
   - Story CRUD operations
   - Category management
   - File upload handling
   - Content validation

2. **Episode Management**
   - Episode CRUD operations
   - Audio processing
   - Image handling
   - Metadata management

3. **Search & Filtering**
   - Full-text search
   - Advanced filtering
   - Sorting options
   - Pagination

### Phase 3: User Features (Week 5-6)
1. **User Profiles**
   - Profile management
   - Child profiles
   - Preferences
   - Avatar handling

2. **Favorites & History**
   - Favorite stories
   - Play history
   - Bookmarks
   - Progress tracking

3. **Rating System**
   - Story ratings
   - Episode ratings
   - Review system
   - Rating aggregation

### Phase 4: Subscription & Payment (Week 7-8)
1. **Subscription System**
   - Subscription plans
   - User subscriptions
   - Access control
   - Renewal handling

2. **Payment Integration**
   - Payment gateways
   - Transaction handling
   - Receipt generation
   - Refund processing

3. **Billing Management**
   - Invoice generation
   - Payment history
   - Subscription analytics
   - Revenue tracking

### Phase 5: Admin Dashboard (Week 9-10)
1. **Dashboard Setup**
   - Tailwind CSS setup
   - Admin layout
   - Navigation structure
   - Responsive design

2. **Content Management**
   - Story management interface
   - Episode management
   - Bulk operations
   - Content moderation

3. **User Management**
   - User overview
   - Profile management
   - Subscription management
   - Activity monitoring

### Phase 6: Advanced Features (Week 11-12)
1. **Notification System**
   - Push notifications
   - Email notifications
   - SMS integration
   - Notification scheduling

2. **Analytics Dashboard**
   - User analytics
   - Content analytics
   - Revenue analytics
   - Custom reports

3. **System Administration**
   - Settings management
   - Feature flags
   - System monitoring
   - Maintenance tools

### Phase 7: Testing & Optimization (Week 13-14)
1. **Testing**
   - Unit tests
   - Integration tests
   - API testing
   - Performance testing

2. **Security**
   - Security audit
   - Vulnerability scanning
   - Penetration testing
   - Compliance check

3. **Performance**
   - Database optimization
   - Caching implementation
   - CDN setup
   - Load testing

### Phase 8: Deployment & Launch (Week 15-16)
1. **Production Setup**
   - Server configuration
   - SSL certificates
   - Domain setup
   - Monitoring setup

2. **Data Migration**
   - Data backup
   - Migration scripts
   - Data validation
   - Rollback plan

3. **Launch Preparation**
   - Documentation
   - User guides
   - Support setup
   - Launch monitoring

## API Documentation

### Authentication Headers
```
Authorization: Bearer {jwt_token}
Content-Type: application/json
Accept: application/json
```

### Error Response Format
```json
{
    "success": false,
    "message": "Error message",
    "errors": {
        "field": ["Validation error message"]
    },
    "code": "ERROR_CODE"
}
```

### Success Response Format
```json
{
    "success": true,
    "message": "Success message",
    "data": {
        // Response data
    },
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

## Admin Dashboard UI Components

### Layout Structure
```html
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SarvCast Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#4A90E2',
                        secondary: '#FF6B6B',
                        accent: '#FFD93D',
                        success: '#6BCF7F',
                        warning: '#FFA726',
                        error: '#EF5350'
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 font-sans">
    <!-- Sidebar -->
    <div class="flex">
        <div class="w-64 bg-white shadow-lg">
            <!-- Sidebar content -->
        </div>
        
        <!-- Main content -->
        <div class="flex-1">
            <!-- Header -->
            <header class="bg-white shadow-sm border-b">
                <!-- Header content -->
            </header>
            
            <!-- Page content -->
            <main class="p-6">
                <!-- Page content -->
            </main>
        </div>
    </div>
</body>
</html>
```

### Component Examples

#### Story Card Component
```html
<div class="bg-white rounded-lg shadow-md overflow-hidden">
    <div class="h-48 bg-gradient-to-r from-blue-400 to-purple-500">
        <img src="{{ $story->image_url }}" alt="{{ $story->title }}" class="w-full h-full object-cover">
    </div>
    <div class="p-4">
        <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ $story->title }}</h3>
        <p class="text-gray-600 text-sm mb-3">{{ $story->subtitle }}</p>
        <div class="flex items-center justify-between">
            <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">{{ $story->category->name }}</span>
            <div class="flex items-center">
                <span class="text-yellow-500">★</span>
                <span class="text-sm text-gray-600 ml-1">{{ $story->rating }}</span>
            </div>
        </div>
    </div>
</div>
```

#### Data Table Component
```html
<div class="bg-white shadow rounded-lg">
    <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-medium text-gray-900">Stories</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($stories as $story)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">{{ $story->title }}</div>
                        <div class="text-sm text-gray-500">{{ $story->subtitle }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            {{ $story->category->name }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                            @if($story->status === 'published') bg-green-100 text-green-800
                            @elseif($story->status === 'pending') bg-yellow-100 text-yellow-800
                            @else bg-red-100 text-red-800
                            @endif">
                            {{ ucfirst($story->status) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <a href="{{ route('admin.stories.edit', $story) }}" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</a>
                        <a href="{{ route('admin.stories.show', $story) }}" class="text-green-600 hover:text-green-900">View</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="px-6 py-4 border-t border-gray-200">
        {{ $stories->links() }}
    </div>
</div>
```

This comprehensive documentation provides everything needed to build a complete Laravel API and admin dashboard for the SarvCast project. The structure matches the Flutter app's data models and provides all necessary endpoints and admin functionality.
