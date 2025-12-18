# SarvCast Controllers Documentation

## Overview
SarvCast - Persian Children's Audio Story Platform has a comprehensive set of custom controllers designed specifically for the audio story streaming service. All controllers are purpose-built and there are no default Laravel controllers present in the system.

## Controller Architecture

### 1. Admin Controllers (`app/Http/Controllers/Admin/`)

#### Core Management Controllers
- **`AnalyticsController.php`** - Comprehensive analytics dashboard
- **`AudioManagementController.php`** - Audio file processing and management
- **`AuthController.php`** - Admin authentication system
- **`BackupController.php`** - System backup and recovery
- **`CategoryController.php`** - Story category management
- **`ContentAnalyticsController.php`** - Content performance analytics
- **`ContentModerationController.php`** - Content review and moderation
- **`DashboardController.php`** - Main admin dashboard
- **`EpisodeController.php`** - Episode management with voice actors and image timelines
- **`EpisodeVoiceActorController.php`** - Voice actor management for episodes
- **`ImageTimelineController.php`** - Image timeline management for episodes
- **`NotificationController.php`** - Admin notification system
- **`PerformanceMonitoringController.php`** - System performance monitoring
- **`PersonController.php`** - People (voice actors, narrators, etc.) management
- **`ProfileController.php`** - Admin profile management
- **`RevenueAnalyticsController.php`** - Revenue and financial analytics
- **`RoleController.php`** - Role and permission management
- **`StoryController.php`** - Story management with enhanced features
- **`SubscriptionController.php`** - Subscription management
- **`SystemAnalyticsController.php`** - System-wide analytics
- **`UserAnalyticsController.php`** - User behavior analytics
- **`UserController.php`** - User management

### 2. API Controllers (`app/Http/Controllers/Api/`)

#### Authentication & User Management
- **`AccessControlController.php`** - Mobile app access control
- **`AuthController.php`** - API authentication with SMS verification
- **`UserController.php`** - User profile and management APIs

#### Content Management
- **`CategoryController.php`** - Category APIs
- **`EpisodeController.php`** - Episode streaming and management APIs
- **`EpisodeVoiceActorController.php`** - Voice actor APIs for episodes
- **`ImageTimelineController.php`** - Image timeline APIs
- **`StoryController.php`** - Story APIs with favorites and ratings
- **`StoryCommentController.php`** - Story commenting system

#### Business Model Controllers
- **`AffiliateController.php`** - Affiliate program management
- **`CoinController.php`** - Coin system management
- **`CommissionPaymentController.php`** - Commission payment processing
- **`CouponController.php`** - Coupon code system
- **`PaymentController.php`** - Payment processing
- **`SubscriptionController.php`** - Subscription management

#### Partnership Programs
- **`CorporateController.php`** - Corporate sponsorship management
- **`InfluencerController.php`** - Influencer partnership program
- **`SchoolController.php`** - School partnership program
- **`TeacherController.php`** - Teacher/educator program

#### Analytics & Reporting
- **`CoinAnalyticsController.php`** - Coin system analytics
- **`ReferralAnalyticsController.php`** - Referral program analytics
- **`ReferralController.php`** - Referral system management

#### Mobile & User Experience
- **`MobileController.php`** - Mobile app configuration and features
- **`FavoriteController.php`** - User favorites management
- **`PlayHistoryController.php`** - Play history tracking
- **`RatingController.php`** - Rating and review system
- **`RecommendationController.php`** - Content recommendation engine
- **`SearchController.php`** - Search functionality
- **`SocialController.php`** - Social features (sharing, following)

#### Gamification & Engagement
- **`GamificationController.php`** - Gamification features
- **`QuizController.php`** - Episode quiz system
- **`InAppNotificationController.php`** - In-app notifications

#### Content Processing
- **`AudioProcessingController.php`** - Audio file processing
- **`ImageProcessingController.php`** - Image processing and optimization
- **`FileUploadController.php`** - File upload management

#### Communication
- **`NotificationController.php`** - Push notifications
- **`SmsController.php`** - SMS messaging system

#### System & Health
- **`HealthController.php`** - API health monitoring
- **`ContentPersonalizationController.php`** - Content personalization

### 3. Specialized Controllers

#### Payment Processing
- **`PaymentCallbackController.php`** - Payment gateway callbacks (ZarinPal, etc.)

## Key Features by Controller Category

### Admin Management
- **Story Management**: Complete CRUD with bulk operations, publishing, duplication
- **Episode Management**: Advanced episode creation with voice actors and image timelines
- **User Management**: User administration with activity tracking and notifications
- **Analytics**: Comprehensive analytics across all aspects of the platform
- **Content Moderation**: Review and approval system for content
- **Role Management**: Granular permission system

### API Services
- **Authentication**: SMS-based authentication system
- **Content Streaming**: Optimized audio streaming with metadata
- **Mobile Integration**: Full mobile app support with offline capabilities
- **Business Logic**: Complete implementation of revenue sharing and partnership programs
- **Gamification**: Coin system, quizzes, and user engagement features

### Business Model Implementation
- **Revenue Sharing**: Affiliate, teacher, influencer, and corporate partnerships
- **Subscription Management**: Multiple subscription tiers with payment processing
- **Coupon System**: Advanced coupon management with revenue sharing
- **Commission Payments**: Automated commission calculation and payment processing

## Controller Relationships

### Admin → API Controllers
- Admin controllers manage the platform while API controllers serve mobile apps
- Shared models and services ensure consistency
- Admin analytics feed into API recommendations

### Content Flow
1. **Admin**: Create stories and episodes with voice actors/image timelines
2. **API**: Serve content to mobile apps with personalization
3. **Analytics**: Track usage and provide insights back to admin

### Business Flow
1. **Partnerships**: Create partner accounts and campaigns
2. **Revenue**: Track commissions and process payments
3. **Analytics**: Monitor performance and optimize revenue

## Security & Access Control

### Admin Security
- Role-based access control
- Permission-based route protection
- Audit logging for all admin actions

### API Security
- Sanctum authentication
- Rate limiting and security middleware
- Mobile device registration and management

## Performance Optimization

### Caching Strategy
- Content caching for frequently accessed stories
- User preference caching for personalization
- Analytics data caching for dashboard performance

### File Processing
- Asynchronous audio processing
- Image optimization and multiple size generation
- CDN integration for media delivery

## Unique SarvCast Features

### Voice Actor Management
- Multiple voice actors per episode
- Time-based voice actor assignments
- Character-specific voice descriptions

### Image Timeline System
- Synchronized images with audio timeline
- Multiple transition effects
- Key frame identification

### Persian Language Support
- RTL (Right-to-Left) interface support
- Persian date formatting
- Persian text processing and validation

### Business Model Innovation
- Multi-tier partnership programs
- Revenue sharing across all partner types
- Automated commission processing
- Comprehensive analytics and reporting

## Conclusion

SarvCast has a completely custom controller architecture designed specifically for the Persian children's audio story platform. Every controller serves a specific purpose in the business model, from content creation and management to revenue generation and user engagement. The system is built to scale and support the complex requirements of a modern audio streaming service with multiple revenue streams and partnership programs.

**Total Controllers**: 50+ custom controllers
**Default Laravel Controllers**: 0 (none present)
**Architecture**: Fully custom, purpose-built for SarvCast
