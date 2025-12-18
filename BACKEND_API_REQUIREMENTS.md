# SarvCast Backend API Requirements

## Overview
This document outlines the complete backend requirements for implementing the SarvCast API as specified in the API documentation. The backend should be implemented using Laravel with the following key features and endpoints.

## Database Schema

### 1. Users Table
```sql
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    phone_number VARCHAR(20) UNIQUE NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    role ENUM('parent', 'admin', 'moderator') DEFAULT 'parent',
    status ENUM('active', 'inactive', 'suspended', 'pending') DEFAULT 'active',
    preferences JSON,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

### 2. Categories Table
```sql
CREATE TABLE categories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    color VARCHAR(7) NOT NULL,
    status ENUM('active', 'inactive', 'archived') DEFAULT 'active',
    order_index INT DEFAULT 0,
    story_count INT DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

### 3. Stories Table
```sql
CREATE TABLE stories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    subtitle VARCHAR(300),
    description TEXT,
    category_id BIGINT UNSIGNED NOT NULL,
    age_group ENUM('3-5', '6-9', '10-12', '13+') NOT NULL,
    duration INT NOT NULL,
    status ENUM('published', 'draft', 'archived', 'pending') DEFAULT 'draft',
    is_premium BOOLEAN DEFAULT FALSE,
    is_completely_free BOOLEAN DEFAULT TRUE,
    play_count INT DEFAULT 0,
    rating DECIMAL(3,2) DEFAULT 0.00,
    rating_count INT DEFAULT 0,
    favorite_count INT DEFAULT 0,
    episode_count INT DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (category_id) REFERENCES categories(id)
);
```

### 4. Episodes Table
```sql
CREATE TABLE episodes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    story_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(200) NOT NULL,
    episode_number INT NOT NULL,
    description TEXT,
    duration INT NOT NULL,
    is_free BOOLEAN DEFAULT TRUE,
    play_count INT DEFAULT 0,
    avg_rating DECIMAL(3,2) DEFAULT 0.00,
    rating_count INT DEFAULT 0,
    audio_url VARCHAR(500) NOT NULL,
    use_image_timeline BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (story_id) REFERENCES stories(id)
);
```

### 5. People Table
```sql
CREATE TABLE people (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    bio TEXT,
    image_url VARCHAR(500),
    roles JSON NOT NULL,
    total_stories INT DEFAULT 0,
    total_episodes INT DEFAULT 0,
    average_rating DECIMAL(3,2) DEFAULT 0.00,
    is_verified BOOLEAN DEFAULT FALSE,
    last_active_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

### 6. Story People Table (Many-to-Many)
```sql
CREATE TABLE story_people (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    story_id BIGINT UNSIGNED NOT NULL,
    person_id BIGINT UNSIGNED NOT NULL,
    role ENUM('director', 'writer', 'producer', 'author', 'narrator', 'voice_actor') NOT NULL,
    created_at TIMESTAMP NULL,
    FOREIGN KEY (story_id) REFERENCES stories(id),
    FOREIGN KEY (person_id) REFERENCES people(id),
    UNIQUE KEY unique_story_person_role (story_id, person_id, role)
);
```

### 7. Episode People Table (Many-to-Many)
```sql
CREATE TABLE episode_people (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    episode_id BIGINT UNSIGNED NOT NULL,
    person_id BIGINT UNSIGNED NOT NULL,
    role ENUM('director', 'writer', 'producer', 'author', 'narrator', 'voice_actor') NOT NULL,
    created_at TIMESTAMP NULL,
    FOREIGN KEY (episode_id) REFERENCES episodes(id),
    FOREIGN KEY (person_id) REFERENCES people(id),
    UNIQUE KEY unique_episode_person_role (episode_id, person_id, role)
);
```

### 8. Favorites Table
```sql
CREATE TABLE favorites (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    story_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (story_id) REFERENCES stories(id),
    UNIQUE KEY unique_user_story (user_id, story_id)
);
```

### 9. Play History Table
```sql
CREATE TABLE play_histories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    episode_id BIGINT UNSIGNED NOT NULL,
    story_id BIGINT UNSIGNED NOT NULL,
    played_at TIMESTAMP NOT NULL,
    duration_played INT NOT NULL,
    total_duration INT NOT NULL,
    completed BOOLEAN DEFAULT FALSE,
    completion_percentage DECIMAL(5,2) DEFAULT 0.00,
    remaining_time INT DEFAULT 0,
    device_info JSON,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (episode_id) REFERENCES episodes(id),
    FOREIGN KEY (story_id) REFERENCES stories(id)
);
```

### 10. Ratings Table
```sql
CREATE TABLE ratings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    story_id BIGINT UNSIGNED NULL,
    episode_id BIGINT UNSIGNED NULL,
    rating TINYINT UNSIGNED NOT NULL CHECK (rating >= 1 AND rating <= 5),
    review TEXT,
    star_rating VARCHAR(10),
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (story_id) REFERENCES stories(id),
    FOREIGN KEY (episode_id) REFERENCES episodes(id),
    UNIQUE KEY unique_user_story_rating (user_id, story_id),
    UNIQUE KEY unique_user_episode_rating (user_id, episode_id)
);
```

### 11. Subscriptions Table
```sql
CREATE TABLE subscriptions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    plan_id VARCHAR(50) NOT NULL,
    status ENUM('active', 'inactive', 'pending', 'cancelled', 'expired') DEFAULT 'pending',
    amount INT NOT NULL,
    currency VARCHAR(3) DEFAULT 'IRR',
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    auto_renew BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

### 12. Payments Table
```sql
CREATE TABLE payments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    subscription_id BIGINT UNSIGNED NULL,
    amount INT NOT NULL,
    currency VARCHAR(3) DEFAULT 'IRR',
    status ENUM('pending', 'completed', 'failed', 'cancelled', 'refunded') DEFAULT 'pending',
    payment_method VARCHAR(50) NOT NULL,
    transaction_id VARCHAR(100),
    gateway VARCHAR(50),
    authority VARCHAR(100),
    payment_url VARCHAR(500),
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (subscription_id) REFERENCES subscriptions(id)
);
```

### 13. Notifications Table
```sql
CREATE TABLE notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    type VARCHAR(50) NOT NULL,
    read_at TIMESTAMP NULL,
    data JSON,
    action_url VARCHAR(500),
    image_url VARCHAR(500),
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

### 14. Comments Table
```sql
CREATE TABLE comments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    story_id BIGINT UNSIGNED NOT NULL,
    comment TEXT NOT NULL,
    is_approved BOOLEAN DEFAULT FALSE,
    is_visible BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (story_id) REFERENCES stories(id)
);
```

### 15. Image Timeline Table
```sql
CREATE TABLE image_timelines (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    episode_id BIGINT UNSIGNED NOT NULL,
    start_time INT NOT NULL,
    end_time INT NOT NULL,
    image_url VARCHAR(500) NOT NULL,
    image_order INT NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (episode_id) REFERENCES episodes(id)
);
```

### 16. File Uploads Table
```sql
CREATE TABLE file_uploads (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    file_id VARCHAR(50) UNIQUE NOT NULL,
    filename VARCHAR(200) NOT NULL,
    original_name VARCHAR(200) NOT NULL,
    path VARCHAR(500) NOT NULL,
    url VARCHAR(500) NOT NULL,
    size INT NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    metadata JSON,
    dimensions JSON,
    uploaded_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

### 17. Devices Table
```sql
CREATE TABLE devices (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    device_id VARCHAR(100) NOT NULL,
    device_type VARCHAR(50) NOT NULL,
    device_model VARCHAR(100),
    os_version VARCHAR(50),
    app_version VARCHAR(20),
    fcm_token VARCHAR(500),
    last_active_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id),
    UNIQUE KEY unique_user_device (user_id, device_id)
);
```

## API Endpoints Implementation

### Authentication Endpoints
- [ ] `POST /auth/send-verification-code` - Send SMS verification code
- [ ] `POST /auth/register` - Register new user
- [ ] `POST /auth/login` - User login
- [ ] `POST /auth/admin/login` - Admin login
- [ ] `POST /auth/logout` - User logout
- [ ] `GET /auth/profile` - Get user profile
- [ ] `PUT /auth/profile` - Update user profile
- [ ] `POST /auth/refresh-token` - Refresh authentication token

### Content Endpoints
- [ ] `GET /categories` - Get all categories
- [ ] `GET /categories/{id}/stories` - Get category stories
- [ ] `GET /people` - Get all people
- [ ] `GET /people/{id}` - Get person details
- [ ] `GET /people/search` - Search people
- [ ] `GET /people/role/{role}` - Get people by role
- [ ] `GET /people/{id}/statistics` - Get person statistics
- [ ] `GET /stories` - Get all stories
- [ ] `GET /stories/{id}` - Get story details
- [ ] `GET /stories/{id}/episodes` - Get story episodes
- [ ] `GET /episodes/{id}` - Get episode details

### User Interaction Endpoints
- [ ] `GET /favorites` - Get user favorites
- [ ] `POST /favorites` - Add to favorites
- [ ] `DELETE /favorites/{storyId}` - Remove from favorites
- [ ] `POST /favorites/toggle` - Toggle favorite status
- [ ] `GET /favorites/check/{storyId}` - Check favorite status
- [ ] `GET /favorites/most-favorited` - Get most favorited stories
- [ ] `GET /favorites/stats` - Get favorite statistics
- [ ] `POST /favorites/bulk` - Bulk favorites operation

### Play History Endpoints
- [ ] `GET /play-history` - Get user play history
- [ ] `POST /play-history/record` - Record play session
- [ ] `PUT /play-history/{id}/progress` - Update play progress
- [ ] `GET /play-history/recent` - Get recent play history
- [ ] `GET /play-history/completed` - Get completed episodes
- [ ] `GET /play-history/in-progress` - Get in-progress episodes
- [ ] `GET /play-history/stats` - Get user play statistics
- [ ] `GET /play-history/episode/{id}/stats` - Get episode play statistics
- [ ] `GET /play-history/story/{id}/stats` - Get story play statistics
- [ ] `GET /play-history/most-played` - Get most played episodes
- [ ] `GET /play-history/most-played-stories` - Get most played stories
- [ ] `GET /play-history/analytics` - Get play analytics

### Rating Endpoints
- [ ] `GET /ratings` - Get user ratings
- [ ] `POST /ratings/story` - Submit story rating
- [ ] `POST /ratings/episode` - Submit episode rating
- [ ] `GET /ratings/story/{id}` - Get story ratings
- [ ] `GET /ratings/episode/{id}` - Get episode ratings
- [ ] `GET /ratings/story/{id}/user` - Get user's story rating
- [ ] `GET /ratings/episode/{id}/user` - Get user's episode rating
- [ ] `GET /ratings/highest-rated-stories` - Get highest rated stories
- [ ] `GET /ratings/highest-rated-episodes` - Get highest rated episodes
- [ ] `GET /ratings/recent-reviews` - Get recent reviews
- [ ] `GET /ratings/user-stats` - Get user rating statistics
- [ ] `GET /ratings/analytics` - Get rating analytics

### Search Endpoints
- [ ] `GET /search/stories` - Search stories
- [ ] `GET /search/episodes` - Search episodes
- [ ] `GET /search/people` - Search people
- [ ] `GET /search/global` - Global search
- [ ] `GET /search/suggestions` - Get search suggestions
- [ ] `GET /search/trending` - Get trending searches
- [ ] `GET /search/filters` - Get search filters
- [ ] `GET /search/stats` - Get search statistics

### Subscription Endpoints
- [ ] `GET /subscriptions/plans` - Get subscription plans
- [ ] `POST /subscriptions` - Create subscription
- [ ] `GET /subscriptions/current` - Get current subscription
- [ ] `POST /subscriptions/cancel` - Cancel subscription
- [ ] `GET /subscriptions/history` - Get subscription history

### Payment Endpoints
- [ ] `POST /payments/initiate` - Initiate payment
- [ ] `POST /payments/verify` - Verify payment
- [ ] `GET /payments/history` - Get payment history

### File Upload Endpoints
- [ ] `POST /upload/image` - Upload image
- [ ] `POST /upload/audio` - Upload audio
- [ ] `POST /upload/document` - Upload document
- [ ] `POST /upload/multiple` - Upload multiple files
- [ ] `DELETE /upload/delete` - Delete file
- [ ] `GET /upload/info` - Get file info
- [ ] `POST /upload/cleanup` - Cleanup temp files
- [ ] `GET /upload/config` - Get upload config

### Audio Processing Endpoints
- [ ] `POST /audio/process` - Process audio file
- [ ] `POST /audio/extract-metadata` - Extract metadata
- [ ] `POST /audio/convert` - Convert format
- [ ] `POST /audio/normalize` - Normalize audio
- [ ] `POST /audio/trim` - Trim audio
- [ ] `POST /audio/validate` - Validate audio file
- [ ] `GET /audio/stats` - Get processing statistics
- [ ] `POST /audio/cleanup` - Cleanup temporary files

### Image Processing Endpoints
- [ ] `POST /image/process` - Process image file
- [ ] `POST /image/resize` - Resize image
- [ ] `POST /image/crop` - Crop image
- [ ] `POST /image/watermark` - Add watermark
- [ ] `POST /image/optimize` - Optimize image
- [ ] `POST /image/thumbnail` - Generate thumbnail
- [ ] `POST /image/multiple-sizes` - Generate multiple sizes
- [ ] `GET /image/info` - Get image information
- [ ] `POST /image/validate` - Validate image file
- [ ] `GET /image/stats` - Get processing statistics
- [ ] `POST /image/cleanup` - Cleanup temporary files

### Image Timeline Endpoints
- [ ] `GET /episodes/{id}/image-timeline` - Get image timeline
- [ ] `POST /episodes/{id}/image-timeline` - Create/update image timeline
- [ ] `DELETE /episodes/{id}/image-timeline` - Delete image timeline
- [ ] `GET /episodes/{id}/image-for-time` - Get image for specific time

### Comments Endpoints
- [ ] `GET /stories/{id}/comments` - Get story comments
- [ ] `POST /stories/{id}/comments` - Add comment to story
- [ ] `GET /comments/my-comments` - Get user's comments
- [ ] `DELETE /comments/{id}` - Delete comment
- [ ] `GET /stories/{id}/comments/statistics` - Get comment statistics

### Notifications Endpoints
- [ ] `GET /notifications` - Get notifications
- [ ] `PUT /notifications/{id}/read` - Mark notification as read
- [ ] `PUT /notifications/read-all` - Mark all notifications as read
- [ ] `DELETE /notifications/{id}` - Delete notification
- [ ] `GET /notifications/settings` - Get notification settings
- [ ] `PUT /notifications/settings` - Update notification settings

### Mobile Endpoints
- [ ] `GET /mobile/config` - Get app configuration
- [ ] `GET /mobile/search` - Search content
- [ ] `GET /mobile/recommendations` - Get recommendations
- [ ] `GET /mobile/trending` - Get trending content
- [ ] `PUT /mobile/preferences` - Update user preferences
- [ ] `POST /mobile/track/play` - Track play event
- [ ] `POST /mobile/device/register` - Register device
- [ ] `PUT /mobile/device/update` - Update device info
- [ ] `GET /mobile/device/stats` - Get device statistics

### Health Check Endpoints
- [ ] `GET /health` - Application health
- [ ] `GET /health/metrics` - Application metrics
- [ ] `GET /health/status` - System status

## Key Features to Implement

### 1. Authentication System
- SMS verification using Laravel SMS packages
- JWT token authentication with refresh tokens
- Role-based access control (parent, admin, moderator)
- Biometric authentication support
- Password reset functionality

### 2. Content Management
- Story and episode CRUD operations
- Category management
- People (directors, narrators, etc.) management
- File upload and processing
- Image timeline management for episodes
- Content approval workflow

### 3. User Interactions
- Favorites system
- Play history tracking
- Rating and review system
- Comments system with moderation
- User preferences management

### 4. Search and Discovery
- Full-text search for stories, episodes, and people
- Advanced filtering options
- Search suggestions and trending searches
- Search analytics

### 5. Subscription and Payment
- Multiple subscription plans
- Payment gateway integration (ZarinPal)
- Subscription management
- Payment history and analytics

### 6. Notifications
- Push notifications using Firebase
- Email notifications
- SMS notifications
- Notification preferences

### 7. File Processing
- Image processing (resize, crop, watermark, optimize)
- Audio processing (convert, normalize, trim)
- Document processing (OCR, text extraction)
- File validation and security

### 8. Analytics and Reporting
- User behavior analytics
- Content performance metrics
- Payment and subscription analytics
- System health monitoring

## Security Requirements

### 1. Authentication Security
- Secure token storage and transmission
- Token expiration and refresh
- Rate limiting for authentication endpoints
- Brute force protection

### 2. Data Security
- Input validation and sanitization
- SQL injection prevention
- XSS protection
- CSRF protection
- File upload security

### 3. API Security
- API rate limiting
- Request validation
- Response encryption for sensitive data
- CORS configuration
- Security headers

### 4. Privacy
- User data encryption
- GDPR compliance
- Data retention policies
- User consent management

## Performance Requirements

### 1. Database Optimization
- Proper indexing strategy
- Query optimization
- Database connection pooling
- Caching implementation

### 2. API Performance
- Response time optimization
- Pagination for large datasets
- Caching for frequently accessed data
- CDN integration for file delivery

### 3. Scalability
- Horizontal scaling support
- Load balancing
- Microservices architecture consideration
- Queue system for background jobs

## Monitoring and Logging

### 1. Application Monitoring
- Error tracking and reporting
- Performance monitoring
- Uptime monitoring
- Resource usage tracking

### 2. API Monitoring
- Request/response logging
- Error rate monitoring
- Response time tracking
- Usage analytics

### 3. Business Metrics
- User engagement metrics
- Content performance metrics
- Revenue tracking
- Subscription analytics

## Deployment Requirements

### 1. Environment Setup
- Production environment configuration
- Staging environment for testing
- Development environment setup
- Environment variable management

### 2. CI/CD Pipeline
- Automated testing
- Code quality checks
- Automated deployment
- Rollback procedures

### 3. Infrastructure
- Server requirements
- Database setup
- File storage configuration
- CDN setup
- SSL certificate management

## Testing Requirements

### 1. Unit Testing
- Model testing
- Service testing
- Controller testing
- Utility function testing

### 2. Integration Testing
- API endpoint testing
- Database integration testing
- External service integration testing
- File upload testing

### 3. Performance Testing
- Load testing
- Stress testing
- Database performance testing
- API response time testing

### 4. Security Testing
- Authentication testing
- Authorization testing
- Input validation testing
- File upload security testing

## Documentation Requirements

### 1. API Documentation
- Complete endpoint documentation
- Request/response examples
- Error code documentation
- Authentication guide

### 2. Database Documentation
- Schema documentation
- Relationship diagrams
- Index documentation
- Migration documentation

### 3. Deployment Documentation
- Installation guide
- Configuration guide
- Troubleshooting guide
- Maintenance procedures

This comprehensive backend implementation will provide all the necessary functionality for the SarvCast mobile application, ensuring scalability, security, and performance.
