# SarvCast Database Schema

## Core Tables

### users
```sql
CREATE TABLE users (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    phone_number VARCHAR(20) UNIQUE NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    profile_image_url VARCHAR(500) NULL,
    role ENUM('parent', 'child', 'admin') DEFAULT 'parent',
    status ENUM('active', 'inactive', 'suspended', 'pending') DEFAULT 'pending',
    phone_verified_at TIMESTAMP NULL,
    parent_id BIGINT UNSIGNED NULL,
    language VARCHAR(10) DEFAULT 'fa',
    timezone VARCHAR(50) DEFAULT 'Asia/Tehran',
    preferences JSON NULL,
    last_login_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (parent_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_phone (phone_number),
    INDEX idx_parent (parent_id),
    INDEX idx_status (status)
);
```

### user_profiles
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

### categories
```sql
CREATE TABLE categories (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT NULL,
    icon_path VARCHAR(500) NULL,
    color VARCHAR(7) DEFAULT '#4A90E2',
    story_count INT DEFAULT 0,
    total_episodes INT DEFAULT 0,
    total_duration INT DEFAULT 0,
    average_rating DECIMAL(3,2) DEFAULT 0.00,
    is_active BOOLEAN DEFAULT TRUE,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_active (is_active),
    INDEX idx_sort (sort_order)
);
```

### stories
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
    duration INT NOT NULL,
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

### episodes
```sql
CREATE TABLE episodes (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    story_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT NULL,
    audio_url VARCHAR(500) NOT NULL,
    local_audio_path VARCHAR(500) NULL,
    duration INT NOT NULL,
    episode_number INT NOT NULL,
    is_premium BOOLEAN DEFAULT FALSE,
    image_urls JSON NULL,
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

### people
```sql
CREATE TABLE people (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    bio TEXT NULL,
    image_url VARCHAR(500) NULL,
    roles JSON NOT NULL,
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

### subscriptions
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

### payments
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

### play_histories
```sql
CREATE TABLE play_histories (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    episode_id BIGINT UNSIGNED NOT NULL,
    story_id BIGINT UNSIGNED NOT NULL,
    played_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    duration_played INT NOT NULL,
    total_duration INT NOT NULL,
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

### favorites
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

### ratings
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

### notifications
```sql
CREATE TABLE notifications (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NULL,
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

## Pivot Tables

### story_people
```sql
CREATE TABLE story_people (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    story_id BIGINT UNSIGNED NOT NULL,
    person_id BIGINT UNSIGNED NOT NULL,
    role ENUM('voice_actor', 'director', 'writer', 'producer') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (story_id) REFERENCES stories(id) ON DELETE CASCADE,
    FOREIGN KEY (person_id) REFERENCES people(id) ON DELETE CASCADE,
    UNIQUE KEY unique_story_person_role (story_id, person_id, role),
    INDEX idx_story (story_id),
    INDEX idx_person (person_id)
);
```

### episode_people
```sql
CREATE TABLE episode_people (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    episode_id BIGINT UNSIGNED NOT NULL,
    person_id BIGINT UNSIGNED NOT NULL,
    role ENUM('voice_actor', 'director', 'writer', 'producer') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (episode_id) REFERENCES episodes(id) ON DELETE CASCADE,
    FOREIGN KEY (person_id) REFERENCES people(id) ON DELETE CASCADE,
    UNIQUE KEY unique_episode_person_role (episode_id, person_id, role),
    INDEX idx_episode (episode_id),
    INDEX idx_person (person_id)
);
```

## Indexes for Performance

### Composite Indexes
```sql
-- User activity queries
CREATE INDEX idx_user_activity ON play_histories(user_id, played_at DESC);

-- Story performance queries
CREATE INDEX idx_story_performance ON stories(category_id, status, published_at DESC);

-- Subscription queries
CREATE INDEX idx_subscription_active ON subscriptions(user_id, status, end_date);

-- Payment queries
CREATE INDEX idx_payment_user_status ON payments(user_id, status, created_at DESC);
```

### Full-Text Search Indexes
```sql
-- Story search
ALTER TABLE stories ADD FULLTEXT(title, subtitle, description);

-- Person search
ALTER TABLE people ADD FULLTEXT(name, bio);

-- Episode search
ALTER TABLE episodes ADD FULLTEXT(title, description);
```

## Data Seeding

### Categories Seeder
```php
$categories = [
    ['name' => 'ماجراجویی', 'description' => 'داستان‌های ماجراجویی و هیجان‌انگیز', 'color' => '#FF6B6B'],
    ['name' => 'آموزشی', 'description' => 'داستان‌های آموزشی و مفید', 'color' => '#4ECDC4'],
    ['name' => 'فانتزی', 'description' => 'داستان‌های فانتزی و جادویی', 'color' => '#45B7D1'],
    ['name' => 'اخلاقی', 'description' => 'داستان‌های اخلاقی و آموزنده', 'color' => '#96CEB4'],
    ['name' => 'کلاسیک', 'description' => 'داستان‌های کلاسیک و قدیمی', 'color' => '#FFEAA7'],
];
```

### Sample Stories
```php
$stories = [
    [
        'title' => 'ماجراجویی در جنگل جادویی',
        'subtitle' => 'داستان پسر کوچکی که در جنگل جادویی گم می‌شود',
        'description' => 'داستان هیجان‌انگیز ماجراجویی در جنگل جادویی...',
        'category_id' => 1,
        'age_group' => '7-9',
        'duration' => 45,
        'total_episodes' => 3,
        'free_episodes' => 1,
        'is_premium' => false,
        'status' => 'published'
    ],
    // More sample stories...
];
```

## Database Maintenance

### Regular Maintenance Tasks
- Update story statistics (play_count, rating)
- Clean up expired sessions
- Archive old play histories
- Optimize database tables
- Update search indexes
- Backup database regularly

### Performance Monitoring
- Monitor slow queries
- Track index usage
- Monitor table sizes
- Track connection usage
- Monitor query performance
