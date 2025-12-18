# SarvCast Kids Podcast App - Backend API Documentation

## Overview
This document outlines the backend API requirements to support the comprehensive UI/UX improvements for the SarvCast kids podcast app.

## Base URL
```
https://api.sarvcast.com/v1
```

## Authentication
All endpoints require authentication via JWT token in the Authorization header:
```
Authorization: Bearer <jwt_token>
```

## Core Endpoints

### 1. User Profile & Recommendations

**PROMPT FOR BACKEND DEVELOPER:**
This section handles user profile management and personalized content recommendations. The system should track user preferences, listening history, and behavior patterns to generate intelligent content suggestions. Implement machine learning algorithms to analyze user data and provide relevant recommendations with confidence scores. Store user activity data in a structured format to enable pattern recognition and preference learning.

#### GET /users/{userId}/profile
Get user profile for recommendations
```json
{
  "userId": "string",
  "age": "number",
  "gender": "string",
  "favoriteCategories": ["string"],
  "favoriteCharacters": ["string"],
  "favoriteTags": ["string"],
  "completedStories": ["string"],
  "bookmarkedStories": ["string"],
  "listeningTimeByCategory": {"category": "minutes"},
  "totalListeningTime": "number",
  "averageRating": "number",
  "preferredLanguage": "string",
  "learningGoals": ["string"],
  "parentalRestrictions": ["string"]
}
```

#### PUT /users/{userId}/profile
Update user profile with activity
```json
{
  "storyId": "string",
  "category": "string",
  "character": "string",
  "tags": ["string"],
  "rating": "number",
  "listeningTime": "number",
  "completed": "boolean",
  "bookmarked": "boolean",
  "shared": "boolean",
  "downloaded": "boolean"
}
```

#### GET /users/{userId}/recommendations
Get personalized recommendations
```json
{
  "recommendations": [
    {
      "id": "string",
      "title": "string",
      "persianTitle": "string",
      "subtitle": "string",
      "imageUrl": "string",
      "audioUrl": "string",
      "category": "string",
      "duration": "number",
      "ageRating": "number",
      "tags": ["string"],
      "type": "string",
      "reason": "string",
      "confidenceScore": "number",
      "isNew": "boolean",
      "isTrending": "boolean",
      "emoji": "string",
      "character": "string",
      "voiceActor": "string",
      "learningObjectives": ["string"]
    }
  ]
}
```

#### GET /users/{userId}/recommendation-clusters
Get recommendation clusters
```json
{
  "clusters": [
    {
      "id": "string",
      "title": "string",
      "persianTitle": "string",
      "description": "string",
      "recommendations": ["recommendation_objects"],
      "type": "string",
      "reason": "string",
      "confidenceScore": "number",
      "isPersonalized": "boolean",
      "priority": "number",
      "emoji": "string"
    }
  ]
}
```

### 2. Achievements & Gamification

**PROMPT FOR BACKEND DEVELOPER:**
This section implements a comprehensive gamification system to motivate children through achievements, badges, and XP rewards. Create a rule-based system that automatically unlocks achievements based on user milestones (listening time, story completion, category exploration, etc.). Implement progress tracking for each achievement and calculate XP rewards dynamically. Store achievement data with timestamps and metadata for analytics. Consider implementing achievement categories (listening, exploration, social, educational) with different rarity levels.

#### GET /users/{userId}/achievements
Get user achievements
```json
{
  "achievements": [
    {
      "id": "string",
      "title": "string",
      "persianTitle": "string",
      "description": "string",
      "category": "string",
      "icon": "string",
      "xpReward": "number",
      "isUnlocked": "boolean",
      "unlockedAt": "datetime",
      "progress": "number",
      "maxProgress": "number"
    }
  ]
}
```

#### POST /users/{userId}/achievements/{achievementId}/unlock
Unlock achievement
```json
{
  "achievementId": "string",
  "unlockedAt": "datetime",
  "metadata": "object"
}
```

#### GET /users/{userId}/badges
Get user badges
```json
{
  "badges": [
    {
      "id": "string",
      "title": "string",
      "persianTitle": "string",
      "description": "string",
      "icon": "string",
      "rarity": "string",
      "isEarned": "boolean",
      "earnedAt": "datetime",
      "category": "string"
    }
  ]
}
```

### 3. Sleep Timer

**PROMPT FOR BACKEND DEVELOPER:**
This section manages bedtime functionality with calming features for children. Implement preset sleep timer options (5, 10, 15, 30 minutes) and custom duration settings. Store user preferences for sleep timer behavior (fade out, stop, pause) and provide bedtime tips based on child's age and preferences. Track sleep timer usage patterns for analytics and consider implementing parental controls for bedtime restrictions. Ensure the system can handle multiple concurrent sleep timers and provide real-time updates.

#### GET /users/{userId}/sleep-timer
Get sleep timer settings
```json
{
  "isEnabled": "boolean",
  "preset": "string",
  "customDuration": "number",
  "action": "string",
  "bedtimeTips": ["string"],
  "lastUsed": "datetime"
}
```

#### PUT /users/{userId}/sleep-timer
Update sleep timer settings
```json
{
  "isEnabled": "boolean",
  "preset": "string",
  "customDuration": "number",
  "action": "string"
}
```

### 4. Character Profiles

**PROMPT FOR BACKEND DEVELOPER:**
This section creates an interactive character system that builds emotional connections with children. Implement character personality systems with mood tracking and relationship building over time. Store character interaction history, voice samples, and animation preferences. Create a system that remembers past interactions and adapts character responses based on child's preferences and behavior patterns. Implement character-based content recommendations and track which characters resonate most with each child for personalized experiences.

#### GET /characters
Get interactive characters
```json
{
  "characters": [
    {
      "id": "string",
      "name": "string",
      "persianName": "string",
      "type": "string",
      "personality": "string",
      "mood": "string",
      "imageUrl": "string",
      "voiceSamples": ["string"],
      "animations": ["string"],
      "relationship": "string",
      "interactions": "number",
      "lastInteraction": "datetime"
    }
  ]
}
```

#### POST /characters/{characterId}/interact
Record character interaction
```json
{
  "interactionType": "string",
  "mood": "string",
      "timestamp": "datetime",
      "metadata": "object"
}
```

### 5. Social Features

**PROMPT FOR BACKEND DEVELOPER:**
This section implements kid-safe social features with family-centric sharing and interaction. Create a secure family group system where parents can manage child accounts and monitor shared content. Implement content sharing with privacy levels (family-only, friends, public) and parental approval workflows. Store family activity feeds, voice messages, and drawing shares with appropriate content moderation. Ensure all social interactions are logged for safety and implement reporting mechanisms for inappropriate content. Consider implementing family challenges and collaborative listening experiences.

#### GET /users/{userId}/family-group
Get family group
```json
{
  "groupId": "string",
      "name": "string",
      "members": [
        {
          "userId": "string",
          "role": "string",
          "name": "string",
          "avatar": "string"
        }
      ],
      "sharedContent": ["string"],
      "activities": ["string"]
}
```

#### POST /users/{userId}/share
Share content with family
```json
{
      "contentId": "string",
      "contentType": "string",
      "message": "string",
      "recipients": ["string"],
      "privacyLevel": "string"
}
```

#### GET /users/{userId}/family-activities
Get family activities
```json
{
      "activities": [
        {
          "id": "string",
          "type": "string",
          "userId": "string",
          "contentId": "string",
          "message": "string",
          "timestamp": "datetime",
          "metadata": "object"
        }
      ]
}
```

### 6. Offline Content

**PROMPT FOR BACKEND DEVELOPER:**
This section manages offline content downloads and storage for children who may not have consistent internet access. Implement a robust download system with progress tracking, pause/resume functionality, and storage management. Create intelligent caching strategies that prioritize frequently accessed content and automatically manage storage space. Implement content expiration policies and update mechanisms for downloaded content. Track download patterns to optimize content recommendations for offline users and provide clear storage usage information to parents.

#### GET /users/{userId}/offline-content
Get offline content
```json
{
      "downloadedStories": [
        {
          "id": "string",
          "title": "string",
          "downloadStatus": "string",
          "downloadProgress": "number",
          "fileSize": "number",
          "downloadedAt": "datetime",
          "expiresAt": "datetime"
        }
      ],
      "storageInfo": {
        "totalSpace": "number",
        "usedSpace": "number",
        "availableSpace": "number"
      }
}
```

#### POST /users/{userId}/download
Start download
```json
{
      "contentId": "string",
      "contentType": "string",
      "priority": "string"
}
```

#### DELETE /users/{userId}/download/{downloadId}
Cancel download
```json
{
      "downloadId": "string"
}
```

### 7. Error Handling

**PROMPT FOR BACKEND DEVELOPER:**
This section implements kid-friendly error handling that transforms technical errors into encouraging, helpful messages. Create a comprehensive error classification system with severity levels and recovery strategies. Implement automatic error recovery mechanisms and provide contextual help based on error type and user context. Store error analytics to identify common issues and improve user experience. Ensure all error messages are translated to Persian and include emoji-based visual indicators. Implement error prevention rules and proactive monitoring to minimize user-facing errors.

#### GET /users/{userId}/errors
Get user errors
```json
{
      "errors": [
        {
          "id": "string",
          "type": "string",
          "title": "string",
          "persianTitle": "string",
          "message": "string",
          "persianMessage": "string",
          "emoji": "string",
          "severity": "string",
          "actions": ["string"],
          "helpText": "string",
          "isRecoverable": "boolean",
          "timestamp": "datetime"
        }
      ]
}
```

#### POST /users/{userId}/errors/{errorId}/resolve
Resolve error
```json
{
      "errorId": "string",
      "resolutionMethod": "string",
      "timestamp": "datetime"
}
```

### 8. Voice Commands

**PROMPT FOR BACKEND DEVELOPER:**
This section implements voice command processing for children who may have difficulty with text-based interfaces. Integrate with speech-to-text services (Google Speech API, Azure Speech, or similar) and implement natural language processing to understand child-friendly commands. Create a command recognition system that handles Persian language input and common child speech patterns. Implement command validation and provide audio feedback responses. Store voice command analytics to improve recognition accuracy and track usage patterns. Consider implementing voice-based navigation and content discovery features.

#### POST /users/{userId}/voice-commands
Process voice command
```json
{
      "audioData": "string",
      "language": "string",
      "timestamp": "datetime"
}
```

Response:
```json
{
      "command": "string",
      "confidence": "number",
      "action": "string",
      "parameters": "object"
}
```

### 9. Parental Controls

**PROMPT FOR BACKEND DEVELOPER:**
This section implements comprehensive parental control systems to ensure child safety and appropriate content access. Create a hierarchical permission system where parents can manage multiple child accounts with different restriction levels. Implement age-based content filtering, time restrictions, and category blocking. Store parental preferences and provide detailed activity reports for parents. Implement content approval workflows for shared content and social interactions. Ensure all parental control changes are logged and provide emergency override mechanisms. Consider implementing location-based restrictions and device management features.

#### GET /users/{userId}/parental-controls
Get parental controls
```json
{
      "isEnabled": "boolean",
      "ageGroup": "string",
      "contentFiltering": "string",
      "timeRestrictions": "object",
      "safetyFeatures": "object",
      "blockedCategories": ["string"],
      "allowedCategories": ["string"]
}
```

#### PUT /users/{userId}/parental-controls
Update parental controls
```json
{
      "isEnabled": "boolean",
      "ageGroup": "string",
      "contentFiltering": "string",
      "timeRestrictions": "object",
      "safetyFeatures": "object",
      "blockedCategories": ["string"],
      "allowedCategories": ["string"]
}
```

### 10. Analytics & Feedback

**PROMPT FOR BACKEND DEVELOPER:**
This section implements comprehensive analytics and feedback collection to continuously improve the user experience. Create a privacy-compliant analytics system that tracks user behavior, content preferences, and engagement patterns. Implement real-time event tracking with proper data anonymization and parental consent management. Store user feedback with sentiment analysis and categorization. Create dashboards for content creators and parents to understand usage patterns. Implement A/B testing capabilities for new features and content recommendations. Ensure all analytics comply with COPPA and other child privacy regulations.

#### POST /users/{userId}/analytics
Record user analytics
```json
{
      "eventType": "string",
      "eventData": "object",
      "timestamp": "datetime",
      "sessionId": "string",
      "metadata": "object"
}
```

#### POST /users/{userId}/feedback
Submit feedback
```json
{
      "type": "string",
      "contentId": "string",
      "rating": "number",
      "comment": "string",
      "tags": ["string"],
      "timestamp": "datetime"
}
```

## Response Codes

- `200` - Success
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `500` - Internal Server Error

## Error Response Format

```json
{
      "error": {
        "code": "string",
        "message": "string",
        "persianMessage": "string",
        "details": "object",
        "timestamp": "datetime"
      }
}
```

## Rate Limiting

- 100 requests per minute per user
- 1000 requests per hour per user
- 10000 requests per day per user

## WebSocket Events

**PROMPT FOR BACKEND DEVELOPER:**
This section implements real-time communication for live updates and interactive features. Create a WebSocket server that handles multiple concurrent connections with proper authentication and authorization. Implement event broadcasting for family activities, achievement unlocks, and download progress updates. Ensure WebSocket connections are stable and handle reconnection logic gracefully. Implement rate limiting and message queuing for high-traffic scenarios. Consider implementing WebSocket rooms for family groups and provide real-time notifications for parental controls and safety alerts.

### Real-time Updates
```
ws://api.sarvcast.com/v1/ws/{userId}
```

Events:
- `recommendation_update` - New recommendations available
- `achievement_unlocked` - Achievement unlocked
- `family_activity` - New family activity
- `download_progress` - Download progress update
- `error_occurred` - New error occurred

## Database Schema Requirements

**PROMPT FOR BACKEND DEVELOPER:**
This section defines the complete database schema for the SarvCast application. Design a scalable, normalized database structure that supports all the features implemented in the frontend. Implement proper indexing for performance optimization, especially for user queries and recommendation algorithms. Use JSON fields for flexible data storage where appropriate (user preferences, metadata, etc.). Implement database migrations and versioning for schema updates. Consider implementing read replicas for analytics queries and ensure proper backup and disaster recovery procedures. Use appropriate data types and constraints to maintain data integrity.

### Users Table
```sql
CREATE TABLE users (
  id VARCHAR(36) PRIMARY KEY,
  age INT,
  gender VARCHAR(10),
  preferred_language VARCHAR(5),
  created_at TIMESTAMP,
  updated_at TIMESTAMP
);
```

### User Profiles Table
```sql
CREATE TABLE user_profiles (
  user_id VARCHAR(36) PRIMARY KEY,
  favorite_categories JSON,
  favorite_characters JSON,
  favorite_tags JSON,
  completed_stories JSON,
  bookmarked_stories JSON,
  listening_time_by_category JSON,
  total_listening_time INT,
  average_rating DECIMAL(3,2),
  learning_goals JSON,
  parental_restrictions JSON,
  created_at TIMESTAMP,
  updated_at TIMESTAMP
);
```

### Recommendations Table
```sql
CREATE TABLE recommendations (
  id VARCHAR(36) PRIMARY KEY,
  user_id VARCHAR(36),
  content_id VARCHAR(36),
  type VARCHAR(50),
  reason VARCHAR(50),
  confidence_score DECIMAL(3,2),
  relevance_score DECIMAL(3,2),
  is_personalized BOOLEAN,
  created_at TIMESTAMP,
  expires_at TIMESTAMP
);
```

### Achievements Table
```sql
CREATE TABLE achievements (
  id VARCHAR(36) PRIMARY KEY,
  title VARCHAR(100),
  persian_title VARCHAR(100),
  description TEXT,
  category VARCHAR(50),
  icon VARCHAR(50),
  xp_reward INT,
  max_progress INT,
  created_at TIMESTAMP
);
```

### User Achievements Table
```sql
CREATE TABLE user_achievements (
  user_id VARCHAR(36),
  achievement_id VARCHAR(36),
  is_unlocked BOOLEAN,
  progress INT,
  unlocked_at TIMESTAMP,
  PRIMARY KEY (user_id, achievement_id)
);
```

## Implementation Notes

**PROMPT FOR BACKEND DEVELOPER:**
This section provides critical implementation guidelines for building a robust, scalable backend system. Focus on performance optimization, security, and child safety throughout the implementation. Implement comprehensive logging and monitoring from day one. Use microservices architecture where appropriate to ensure scalability. Implement proper error handling and graceful degradation for all services. Ensure all APIs are properly documented and tested. Consider implementing API versioning for future updates. Focus on data privacy and security, especially for child users, and ensure compliance with relevant regulations.

1. **Caching**: Implement Redis caching for recommendations and user profiles
2. **Queue System**: Use message queues for background processing of recommendations
3. **Machine Learning**: Implement ML models for personalized recommendations
4. **CDN**: Use CDN for audio files and images
5. **Monitoring**: Implement comprehensive logging and monitoring
6. **Security**: Implement rate limiting, input validation, and security headers
7. **Backup**: Implement automated backups and disaster recovery
8. **Testing**: Implement comprehensive unit and integration tests

## Deployment Requirements

**PROMPT FOR BACKEND DEVELOPER:**
This section outlines the production deployment requirements for the SarvCast backend. Choose technologies that provide high availability, scalability, and security for a children's application. Implement proper CI/CD pipelines for automated testing and deployment. Use containerization for consistent deployment across environments. Implement proper load balancing and auto-scaling for high traffic scenarios. Ensure all services are properly monitored and have health checks. Implement proper backup strategies and disaster recovery procedures. Consider implementing blue-green deployments for zero-downtime updates.

- **Server**: Node.js/Express or Python/Django
- **Database**: PostgreSQL with Redis for caching
- **Queue**: Redis or RabbitMQ for background jobs
- **Storage**: AWS S3 or similar for file storage
- **CDN**: CloudFront or similar for content delivery
- **Monitoring**: Prometheus + Grafana or similar
- **Logging**: ELK stack or similar
- **SSL**: HTTPS with proper certificates
- **Load Balancing**: Nginx or similar
- **Containerization**: Docker with Kubernetes or similar
