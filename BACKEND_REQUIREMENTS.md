# Backend Requirements for SarvCast API Implementation

## Overview
This document outlines the backend requirements for implementing the SarvCast API endpoints. The backend should be built using Laravel framework and provide comprehensive REST API endpoints for the Flutter mobile application.

## Technology Stack
- **Framework**: Laravel 10.x
- **Database**: MySQL 8.0 or PostgreSQL 13+
- **Authentication**: Laravel Sanctum
- **File Storage**: AWS S3 or local storage
- **Queue System**: Redis or database queues
- **Cache**: Redis
- **Search**: Laravel Scout with Algolia or Elasticsearch

## Database Schema Requirements

### Core Tables

#### Users Table
```sql
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    phone_number VARCHAR(20) UNIQUE NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'parent', 'child') DEFAULT 'parent',
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    email_verified_at TIMESTAMP NULL,
    phone_verified_at TIMESTAMP NULL,
    last_login_at TIMESTAMP NULL,
    language VARCHAR(5) DEFAULT 'fa',
    timezone VARCHAR(50) DEFAULT 'Asia/Tehran',
    profile_image_url VARCHAR(500) NULL,
    preferences JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

#### Categories Table
```sql
CREATE TABLE categories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT NULL,
    image_url VARCHAR(500) NULL,
    sort_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

#### People Table (Authors, Narrators, etc.)
```sql
CREATE TABLE people (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    role ENUM('author', 'narrator', 'translator', 'illustrator') NOT NULL,
    bio TEXT NULL,
    image_url VARCHAR(500) NULL,
    website_url VARCHAR(500) NULL,
    social_links JSON NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

#### Stories Table
```sql
CREATE TABLE stories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    subtitle VARCHAR(300) NULL,
    description TEXT NULL,
    category_id BIGINT UNSIGNED NOT NULL,
    type ENUM('original', 'translated', 'adapted') DEFAULT 'original',
    age_group ENUM('3-5', '6-8', '9-12', '13-17') NOT NULL,
    is_premium BOOLEAN DEFAULT FALSE,
    cover_image_url VARCHAR(500) NULL,
    background_image_url VARCHAR(500) NULL,
    author_id BIGINT UNSIGNED NULL,
    narrator_id BIGINT UNSIGNED NULL,
    duration_minutes INT NULL,
    episode_count INT DEFAULT 0,
    average_rating DECIMAL(3,2) DEFAULT 0.00,
    total_ratings INT DEFAULT 0,
    total_plays INT DEFAULT 0,
    is_featured BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id),
    FOREIGN KEY (author_id) REFERENCES people(id),
    FOREIGN KEY (narrator_id) REFERENCES people(id)
);
```

#### Episodes Table
```sql
CREATE TABLE episodes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    story_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT NULL,
    episode_number INT NOT NULL,
    audio_url VARCHAR(500) NOT NULL,
    duration_seconds INT NOT NULL,
    file_size_bytes BIGINT NULL,
    is_premium BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (story_id) REFERENCES stories(id),
    UNIQUE KEY unique_story_episode (story_id, episode_number)
);
```

#### Image Timeline Table
```sql
CREATE TABLE image_timelines (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    episode_id BIGINT UNSIGNED NOT NULL,
    start_time INT NOT NULL, -- in seconds
    end_time INT NOT NULL, -- in seconds
    image_url VARCHAR(500) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (episode_id) REFERENCES episodes(id)
);
```

### User Interaction Tables

#### Child Profiles Table
```sql
CREATE TABLE child_profiles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    age INT NOT NULL,
    avatar_url VARCHAR(500) NULL,
    favorite_category VARCHAR(100) NULL,
    preferences JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

#### Favorites Table
```sql
CREATE TABLE favorites (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    item_id BIGINT UNSIGNED NOT NULL,
    item_type ENUM('story', 'episode', 'person') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    UNIQUE KEY unique_user_item (user_id, item_id, item_type)
);
```

#### Play History Table
```sql
CREATE TABLE play_history (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    episode_id BIGINT UNSIGNED NOT NULL,
    current_time INT NOT NULL DEFAULT 0, -- in seconds
    total_time INT NOT NULL, -- in seconds
    is_completed BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (episode_id) REFERENCES episodes(id),
    UNIQUE KEY unique_user_episode (user_id, episode_id)
);
```

#### Ratings Table
```sql
CREATE TABLE ratings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    item_id BIGINT UNSIGNED NOT NULL,
    item_type ENUM('story', 'episode') NOT NULL,
    rating TINYINT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    UNIQUE KEY unique_user_item_rating (user_id, item_id, item_type)
);
```

### Subscription & Payment Tables

#### Subscription Plans Table
```sql
CREATE TABLE subscription_plans (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT NULL,
    price_monthly DECIMAL(10,2) NOT NULL,
    price_yearly DECIMAL(10,2) NULL,
    features JSON NOT NULL,
    is_popular BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

#### Subscriptions Table
```sql
CREATE TABLE subscriptions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    plan_id BIGINT UNSIGNED NOT NULL,
    status ENUM('active', 'cancelled', 'expired') DEFAULT 'active',
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    auto_renew BOOLEAN DEFAULT TRUE,
    promo_code VARCHAR(50) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (plan_id) REFERENCES subscription_plans(id)
);
```

#### Payment Methods Table
```sql
CREATE TABLE payment_methods (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    type ENUM('card', 'bank_transfer', 'wallet') NOT NULL,
    token VARCHAR(500) NOT NULL,
    last_four VARCHAR(4) NULL,
    expiry_month TINYINT NULL,
    expiry_year SMALLINT NULL,
    is_default BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

#### Payments Table
```sql
CREATE TABLE payments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    subscription_id BIGINT UNSIGNED NULL,
    payment_method_id BIGINT UNSIGNED NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'IRR',
    status ENUM('pending', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
    transaction_id VARCHAR(100) NULL,
    description TEXT NULL,
    metadata JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (subscription_id) REFERENCES subscriptions(id),
    FOREIGN KEY (payment_method_id) REFERENCES payment_methods(id)
);
```

### Notification Tables

#### Notifications Table
```sql
CREATE TABLE notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL, -- NULL for broadcast notifications
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('story_update', 'episode_release', 'subscription', 'payment', 'system') NOT NULL,
    data JSON NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

#### Notification Preferences Table
```sql
CREATE TABLE notification_preferences (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    push_notifications BOOLEAN DEFAULT TRUE,
    email_notifications BOOLEAN DEFAULT TRUE,
    story_updates BOOLEAN DEFAULT TRUE,
    episode_releases BOOLEAN DEFAULT TRUE,
    subscription_reminders BOOLEAN DEFAULT TRUE,
    marketing_emails BOOLEAN DEFAULT FALSE,
    weekly_digest BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    UNIQUE KEY unique_user_preferences (user_id)
);
```

## API Endpoints Implementation

### Authentication Endpoints
- `POST /api/v1/auth/register` - User registration
- `POST /api/v1/auth/login` - User login
- `POST /api/v1/auth/logout` - User logout
- `POST /api/v1/auth/refresh` - Refresh token
- `GET /api/v1/auth/profile` - Get user profile
- `PUT /api/v1/auth/profile` - Update user profile
- `POST /api/v1/auth/change-password` - Change password
- `POST /api/v1/auth/forgot-password` - Request password reset
- `POST /api/v1/auth/reset-password` - Reset password
- `POST /api/v1/auth/verify-email` - Verify email
- `POST /api/v1/auth/resend-verification` - Resend email verification

### Content Endpoints
- `GET /api/v1/categories` - Get all categories
- `GET /api/v1/categories/{id}` - Get category by ID
- `GET /api/v1/categories/{id}/stories` - Get stories by category
- `GET /api/v1/stories` - Get all stories
- `GET /api/v1/stories/{id}` - Get story by ID
- `GET /api/v1/stories/{id}/episodes` - Get episodes by story
- `GET /api/v1/stories/featured` - Get featured stories
- `GET /api/v1/stories/popular` - Get popular stories
- `GET /api/v1/stories/recent` - Get recent stories
- `GET /api/v1/stories/recommendations` - Get story recommendations
- `GET /api/v1/episodes/{id}` - Get episode by ID
- `GET /api/v1/people` - Get all people
- `GET /api/v1/people/{id}` - Get person by ID
- `GET /api/v1/people/{id}/stories` - Get stories by person

### User Interaction Endpoints
- `GET /api/v1/favorites` - Get user favorites
- `POST /api/v1/favorites` - Add to favorites
- `DELETE /api/v1/favorites/{id}` - Remove from favorites
- `GET /api/v1/play-history` - Get play history
- `POST /api/v1/play-history` - Add play history
- `PUT /api/v1/play-history/{id}` - Update play history
- `DELETE /api/v1/play-history` - Clear play history
- `GET /api/v1/ratings` - Get ratings
- `POST /api/v1/ratings` - Add rating
- `PUT /api/v1/ratings/{id}` - Update rating
- `DELETE /api/v1/ratings/{id}` - Delete rating
- `GET /api/v1/child-profiles` - Get child profiles
- `POST /api/v1/child-profiles` - Create child profile
- `PUT /api/v1/child-profiles/{id}` - Update child profile
- `DELETE /api/v1/child-profiles/{id}` - Delete child profile
- `POST /api/v1/child-profiles/{id}/switch` - Switch to child profile
- `GET /api/v1/child-profiles/{id}/recommendations` - Get child profile recommendations

### Subscription Endpoints
- `GET /api/v1/subscription-plans` - Get subscription plans
- `GET /api/v1/subscription-plans/{id}` - Get subscription plan by ID
- `GET /api/v1/subscriptions/current` - Get user subscription
- `POST /api/v1/subscriptions` - Create subscription
- `PUT /api/v1/subscriptions/{id}` - Update subscription
- `POST /api/v1/subscriptions/{id}/cancel` - Cancel subscription
- `GET /api/v1/payment-methods` - Get payment methods
- `POST /api/v1/payment-methods` - Add payment method
- `PUT /api/v1/payment-methods/{id}` - Update payment method
- `DELETE /api/v1/payment-methods/{id}` - Delete payment method
- `GET /api/v1/payments` - Get payments
- `GET /api/v1/payments/{id}` - Get payment by ID
- `POST /api/v1/payments` - Create payment
- `POST /api/v1/payments/{id}/process` - Process payment
- `GET /api/v1/payments/statistics` - Get payment statistics

### Search Endpoints
- `GET /api/v1/search` - Search content
- `GET /api/v1/search/suggestions` - Get search suggestions
- `GET /api/v1/search/trending` - Get trending searches
- `GET /api/v1/search/filters` - Get search filters
- `GET /api/v1/search/statistics` - Get search statistics
- `GET /api/v1/search/global` - Global search

### Notification Endpoints
- `GET /api/v1/notifications` - Get notifications
- `GET /api/v1/notifications/{id}` - Get notification by ID
- `PUT /api/v1/notifications/{id}/read` - Mark notification as read
- `PUT /api/v1/notifications/read-all` - Mark all notifications as read
- `DELETE /api/v1/notifications/{id}` - Delete notification
- `GET /api/v1/notifications/settings` - Get notification settings
- `PUT /api/v1/notifications/settings` - Update notification settings
- `GET /api/v1/notifications/statistics` - Get notification statistics

### File Upload Endpoints
- `POST /api/v1/files/upload` - Upload file
- `POST /api/v1/files/upload-bulk` - Upload multiple files
- `GET /api/v1/files/config` - Get file upload configuration
- `DELETE /api/v1/files` - Delete file
- `GET /api/v1/files/metadata` - Get file metadata

## Authentication & Security

### JWT Token Implementation
- Use Laravel Sanctum for API authentication
- Implement refresh token mechanism
- Set appropriate token expiration times
- Implement token blacklisting for logout

### Rate Limiting
- Implement rate limiting for all endpoints
- Different limits for authenticated vs unauthenticated users
- Special limits for sensitive operations (login, password reset)

### Input Validation
- Validate all input data using Laravel validation rules
- Sanitize user inputs to prevent XSS attacks
- Implement CSRF protection for web routes

### File Upload Security
- Validate file types and sizes
- Scan uploaded files for malware
- Store files outside web root
- Generate unique file names

## Performance Optimization

### Database Optimization
- Add appropriate indexes for frequently queried columns
- Use database query optimization techniques
- Implement database connection pooling
- Use read replicas for read-heavy operations

### Caching Strategy
- Implement Redis caching for frequently accessed data
- Cache API responses where appropriate
- Use Laravel's built-in caching mechanisms
- Implement cache invalidation strategies

### API Response Optimization
- Implement pagination for list endpoints
- Use API resources for consistent response formatting
- Implement response compression
- Use HTTP/2 for better performance

## File Storage

### Audio Files
- Store audio files in cloud storage (AWS S3)
- Implement CDN for faster delivery
- Generate multiple quality versions
- Implement streaming for large files

### Images
- Store images in cloud storage
- Generate multiple sizes (thumbnails, medium, large)
- Implement image optimization
- Use WebP format for better compression

### File Management
- Implement file cleanup for unused files
- Generate unique file names to prevent conflicts
- Implement file access control
- Monitor storage usage

## Monitoring & Logging

### Application Monitoring
- Implement application performance monitoring
- Monitor API response times
- Track error rates and exceptions
- Monitor database performance

### Logging
- Implement comprehensive logging
- Log all API requests and responses
- Log authentication events
- Log payment transactions

### Alerts
- Set up alerts for critical errors
- Monitor system resource usage
- Alert on unusual activity patterns
- Monitor payment processing

## Testing Requirements

### Unit Testing
- Write unit tests for all business logic
- Test API endpoints thoroughly
- Test authentication and authorization
- Test payment processing

### Integration Testing
- Test database interactions
- Test file upload functionality
- Test third-party service integrations
- Test API response formats

### Performance Testing
- Load test API endpoints
- Test database performance under load
- Test file upload performance
- Test concurrent user scenarios

## Deployment Requirements

### Environment Setup
- Production environment with proper security
- Staging environment for testing
- Development environment for local development
- Environment-specific configuration

### Database Setup
- Production database with proper backup
- Database migration scripts
- Seed data for initial setup
- Database monitoring and maintenance

### Security Configuration
- HTTPS enforcement
- Proper firewall configuration
- Regular security updates
- Vulnerability scanning

## API Documentation

### Documentation Requirements
- Complete API documentation with examples
- Interactive API documentation (Swagger/OpenAPI)
- Authentication examples
- Error code documentation
- Rate limiting documentation

### Versioning
- Implement API versioning strategy
- Maintain backward compatibility
- Document breaking changes
- Provide migration guides

## Additional Considerations

### Internationalization
- Support for multiple languages
- RTL text support
- Localized content
- Timezone handling

### Analytics
- Track user behavior
- Monitor content popularity
- Analyze search patterns
- Generate usage reports

### Backup & Recovery
- Regular database backups
- File storage backups
- Disaster recovery plan
- Data retention policies

This document provides a comprehensive overview of the backend requirements for implementing the SarvCast API. The implementation should follow Laravel best practices and ensure scalability, security, and performance.
