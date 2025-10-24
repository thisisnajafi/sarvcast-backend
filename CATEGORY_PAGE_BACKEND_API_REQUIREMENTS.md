# Category Page Backend API Requirements

## Overview
This document outlines the backend API requirements for the SarvCast category page functionality. The category page displays category details, background images, and associated stories in a horizontal scrolling layout.

## Base Configuration

### API Base URL
```
https://my.sarvcast.ir/api/v1
```

### Required Headers
```dart
Map<String, String> headers = {
  'Content-Type': 'application/json',
  'Accept': 'application/json',
  'User-Agent': 'SarvCast-Flutter/1.0.0',
};
```

### Authentication (Optional)
For personalized content, include Bearer token:
```dart
Map<String, String> authHeaders = {
  ...headers,
  'Authorization': 'Bearer $userToken',
};
```

## API Endpoints

### 1. Get Category Details

**Route:** `GET /categories/{id}`

**Purpose:** Fetch detailed information about a specific category including its background image

**Parameters:**
- `id` (required): Category ID in the URL path

**Flutter Implementation:**
```dart
Future<Category> getCategoryDetails(int categoryId) async {
  final uri = Uri.parse('$baseUrl/categories/$categoryId');
  
  final response = await http.get(uri, headers: headers);
  
  if (response.statusCode == 200) {
    final data = json.decode(response.body);
    if (data['success'] == true) {
      return Category.fromJson(data['data']);
    }
  }
  
  throw Exception('Failed to load category details');
}
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Category details retrieved successfully",
  "data": {
    "id": 1,
    "name": "داستان‌های ماجراجویی",
    "slug": "adventure-stories",
    "description": "داستان‌های هیجان‌انگیز و ماجراجویانه برای کودکان",
    "color": "#F59E0B",
    "status": "active",
    "order": 1,
    "story_count": 15,
    "icon_path": "/icons/adventure-stories.svg",
    "image_url": "https://my.sarvcast.ir/storage/categories/adventure-bg.jpg",
    "created_at": "2024-01-15T10:30:00Z",
    "updated_at": "2024-01-20T14:45:00Z"
  }
}
```

### 2. Get Category Stories

**Route:** `GET /categories/{id}/stories`

**Purpose:** Fetch all stories belonging to a specific category

**Parameters:**
- `id` (required): Category ID in the URL path
- `limit` (optional): Number of stories to return (default: 20)
- `page` (optional): Page number for pagination (default: 1)
- `sort_by` (optional): Sort field (created_at, title, rating, play_count)
- `sort_order` (optional): Sort direction (asc, desc)

**Flutter Implementation:**
```dart
Future<List<Story>> getCategoryStories({
  required int categoryId,
  int? limit,
  int? page,
  String? sortBy,
  String? sortOrder,
}) async {
  final queryParams = <String, String>{};
  
  if (limit != null) queryParams['limit'] = limit.toString();
  if (page != null) queryParams['page'] = page.toString();
  if (sortBy != null) queryParams['sort_by'] = sortBy;
  if (sortOrder != null) queryParams['sort_order'] = sortOrder;
  
  final uri = Uri.parse('$baseUrl/categories/$categoryId/stories').replace(
    queryParameters: queryParams,
  );
  
  final response = await http.get(uri, headers: headers);
  
  if (response.statusCode == 200) {
    final data = json.decode(response.body);
    if (data['success'] == true) {
      return (data['data'] as List)
          .map((json) => Story.fromJson(json))
          .toList();
    }
  }
  
  throw Exception('Failed to load category stories');
}
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Category stories retrieved successfully",
  "data": [
    {
      "id": 1,
      "title": "جنگل جادویی",
      "subtitle": "ماجراجویی شگفت‌انگیز",
      "description": "داستانی جادویی درباره جنگلی اسرارآمیز",
      "category_id": 1,
      "age_group": "5-8",
      "duration": 1800,
      "status": "published",
      "is_premium": false,
      "is_completely_free": true,
      "play_count": 1250,
      "rating": 4.5,
      "rating_count": 89,
      "favorite_count": 45,
      "episode_count": 3,
      "created_at": "2024-01-15T10:30:00Z",
      "updated_at": "2024-01-20T14:45:00Z",
      "category": {
        "id": 1,
        "name": "داستان‌های ماجراجویی",
        "slug": "adventure-stories",
        "description": "داستان‌های هیجان‌انگیز و ماجراجویانه برای کودکان",
        "color": "#F59E0B",
        "status": "active",
        "order": 1,
        "story_count": 15,
        "icon_path": "/icons/adventure-stories.svg",
        "image_url": "https://my.sarvcast.ir/storage/categories/adventure-bg.jpg",
        "created_at": "2024-01-15T10:30:00Z",
        "updated_at": "2024-01-20T14:45:00Z"
      },
      "narrator": {
        "id": 1,
        "name": "علی احمدی",
        "bio": "راوی با تجربه",
        "image_url": "https://my.sarvcast.ir/storage/narrators/ali-ahmadi.jpg",
        "roles": ["narrator", "voice_actor"],
        "total_stories": 10,
        "total_episodes": 50,
        "average_rating": 4.5,
        "is_verified": true,
        "last_active_at": "2024-01-18T16:20:00Z",
        "created_at": "2024-01-10T09:15:00Z"
      },
      "image_url": "https://my.sarvcast.ir/storage/stories/magical-forest.jpg",
      "cover_image_url": "https://my.sarvcast.ir/storage/stories/magical-forest-cover.jpg",
      "total_episodes": 3,
      "free_episodes": 3,
      "episode_ids": [1, 2, 3],
      "is_favorite": false,
      "progress": 0.0,
      "tags": ["جادو", "جنگل", "ماجراجویی"],
      "language": "fa"
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 20,
    "total": 15,
    "last_page": 1,
    "has_more": false
  }
}
```

## Data Models

### Category Model
```dart
class Category {
  final int id;
  final String name;
  final String slug;
  final String description;
  final String color;
  final String status;
  final int order;
  final int storyCount;
  final String? iconPath;
  final String? imageUrl;  // REQUIRED: Background image for category page
  final DateTime createdAt;
  final DateTime updatedAt;

  Category({
    required this.id,
    required this.name,
    required this.slug,
    required this.description,
    required this.color,
    required this.status,
    required this.order,
    required this.storyCount,
    this.iconPath,
    this.imageUrl,  // REQUIRED: For category page background
    required this.createdAt,
    required this.updatedAt,
  });

  factory Category.fromJson(Map<String, dynamic> json) {
    return Category(
      id: json['id'],
      name: json['name'],
      slug: json['slug'],
      description: json['description'],
      color: json['color'],
      status: json['status'],
      order: json['order'],
      storyCount: json['story_count'],
      iconPath: json['icon_path'],
      imageUrl: json['image_url'],  // REQUIRED: For category page background
      createdAt: DateTime.parse(json['created_at']),
      updatedAt: DateTime.parse(json['updated_at']),
    );
  }
}
```

### Story Model
```dart
class Story {
  final int id;
  final String title;
  final String subtitle;
  final String description;
  final int categoryId;
  final String ageGroup;
  final int duration;
  final String status;
  final bool isPremium;
  final bool isCompletelyFree;
  final int playCount;
  final double rating;
  final int ratingCount;
  final int favoriteCount;
  final int episodeCount;
  final DateTime createdAt;
  final DateTime updatedAt;
  final Category? category;
  final Person? narrator;
  final String? imageUrl;  // REQUIRED: Story thumbnail image
  final String? coverImageUrl;
  final int totalEpisodes;
  final int freeEpisodes;
  final List<int> episodeIds;
  final bool isFavorite;
  final double progress;
  final List<String> tags;
  final String language;

  Story({
    required this.id,
    required this.title,
    required this.subtitle,
    required this.description,
    required this.categoryId,
    required this.ageGroup,
    required this.duration,
    required this.status,
    required this.isPremium,
    required this.isCompletelyFree,
    required this.playCount,
    required this.rating,
    required this.ratingCount,
    required this.favoriteCount,
    required this.episodeCount,
    required this.createdAt,
    required this.updatedAt,
    this.category,
    this.narrator,
    this.imageUrl,  // REQUIRED: For story cards
    this.coverImageUrl,
    required this.totalEpisodes,
    required this.freeEpisodes,
    required this.episodeIds,
    required this.isFavorite,
    required this.progress,
    required this.tags,
    required this.language,
  });

  factory Story.fromJson(Map<String, dynamic> json) {
    return Story(
      id: json['id'],
      title: json['title'],
      subtitle: json['subtitle'],
      description: json['description'],
      categoryId: json['category_id'],
      ageGroup: json['age_group'],
      duration: json['duration'],
      status: json['status'],
      isPremium: json['is_premium'],
      isCompletelyFree: json['is_completely_free'],
      playCount: json['play_count'],
      rating: (json['rating'] ?? 0.0).toDouble(),
      ratingCount: json['rating_count'],
      favoriteCount: json['favorite_count'],
      episodeCount: json['episode_count'],
      createdAt: DateTime.parse(json['created_at']),
      updatedAt: DateTime.parse(json['updated_at']),
      category: json['category'] != null ? Category.fromJson(json['category']) : null,
      narrator: json['narrator'] != null ? Person.fromJson(json['narrator']) : null,
      imageUrl: json['image_url'],  // REQUIRED: For story cards
      coverImageUrl: json['cover_image_url'],
      totalEpisodes: json['total_episodes'],
      freeEpisodes: json['free_episodes'],
      episodeIds: List<int>.from(json['episode_ids']),
      isFavorite: json['is_favorite'] ?? false,
      progress: (json['progress'] ?? 0.0).toDouble(),
      tags: List<String>.from(json['tags']),
      language: json['language'],
    );
  }
}
```

### Person Model (for Narrator)
```dart
class Person {
  final int id;
  final String name;
  final String bio;
  final String? imageUrl;
  final List<String> roles;
  final int totalStories;
  final int totalEpisodes;
  final double averageRating;
  final bool isVerified;
  final DateTime? lastActiveAt;
  final DateTime createdAt;

  Person({
    required this.id,
    required this.name,
    required this.bio,
    this.imageUrl,
    required this.roles,
    required this.totalStories,
    required this.totalEpisodes,
    required this.averageRating,
    required this.isVerified,
    this.lastActiveAt,
    required this.createdAt,
  });

  factory Person.fromJson(Map<String, dynamic> json) {
    return Person(
      id: json['id'],
      name: json['name'],
      bio: json['bio'],
      imageUrl: json['image_url'],
      roles: List<String>.from(json['roles']),
      totalStories: json['total_stories'],
      totalEpisodes: json['total_episodes'],
      averageRating: (json['average_rating'] ?? 0.0).toDouble(),
      isVerified: json['is_verified'],
      lastActiveAt: json['last_active_at'] != null 
          ? DateTime.parse(json['last_active_at']) 
          : null,
      createdAt: DateTime.parse(json['created_at']),
    );
  }
}
```

## Critical Requirements

### 1. Category Background Images
- **REQUIRED**: `image_url` field in Category model
- **Format**: Full URL to category background image
- **Resolution**: Minimum 1200x800px for optimal display
- **Formats**: JPG, PNG, WebP supported
- **CDN**: Images should be served from CDN for performance

### 2. Story Thumbnail Images
- **REQUIRED**: `image_url` field in Story model
- **Format**: Full URL to story thumbnail image
- **Resolution**: Minimum 300x200px for story cards
- **Aspect Ratio**: 3:2 recommended for consistent display
- **Formats**: JPG, PNG, WebP supported

### 3. Image Fallbacks
- **Category Images**: If `image_url` is null/empty, app will use gradient fallback
- **Story Images**: If `image_url` is null/empty, app will use default placeholder
- **Error Handling**: App handles image loading errors gracefully

### 4. Performance Considerations
- **Image Optimization**: Images should be optimized for web delivery
- **Lazy Loading**: App implements lazy loading for story images
- **Caching**: Images should have appropriate cache headers
- **CDN**: Use CDN for image delivery

### 5. Data Consistency
- **Category ID**: Must match between category details and stories
- **Story Count**: `story_count` in category should match actual stories returned
- **Status**: Only return stories with `status: "published"`
- **Active Categories**: Only return categories with `status: "active"`

## Error Handling

### HTTP Status Codes
- **200**: Success
- **404**: Category not found
- **500**: Server error

### Error Response Format
```json
{
  "success": false,
  "message": "Category not found",
  "error": {
    "code": "CATEGORY_NOT_FOUND",
    "details": "The requested category does not exist"
  }
}
```

## Testing Requirements

### 1. Category Details Endpoint
- Test with valid category ID
- Test with invalid category ID (should return 404)
- Test with inactive category (should return 404 or empty)
- Verify `image_url` field is present and valid

### 2. Category Stories Endpoint
- Test with valid category ID
- Test pagination parameters
- Test sorting parameters
- Verify story `image_url` fields are present
- Test with category that has no stories

### 3. Image URLs
- Verify all image URLs are accessible
- Test image loading performance
- Verify image formats and resolutions
- Test CDN delivery

## Implementation Checklist

### Backend Requirements
- [ ] Add `image_url` field to categories table
- [ ] Add `image_url` field to stories table
- [ ] Update category details endpoint to include `image_url`
- [ ] Update category stories endpoint to include story `image_url`
- [ ] Implement image upload functionality for categories
- [ ] Implement image upload functionality for stories
- [ ] Set up CDN for image delivery
- [ ] Add image optimization pipeline
- [ ] Update database migrations
- [ ] Add image validation
- [ ] Implement image fallback logic
- [ ] Add image caching headers
- [ ] Test all endpoints with image data
- [ ] Verify image URLs are accessible
- [ ] Performance test image loading
- [ ] Add error handling for missing images

### Database Schema Updates
```sql
-- Add image_url to categories table
ALTER TABLE categories ADD COLUMN image_url VARCHAR(500) NULL;

-- Add image_url to stories table (if not already exists)
ALTER TABLE stories ADD COLUMN image_url VARCHAR(500) NULL;

-- Add indexes for performance
CREATE INDEX idx_categories_image_url ON categories(image_url);
CREATE INDEX idx_stories_image_url ON stories(image_url);
```

### API Response Updates
- [ ] Update category details response to include `image_url`
- [ ] Update category stories response to include story `image_url`
- [ ] Update story model to include `image_url`
- [ ] Update category model to include `image_url`
- [ ] Add image URL validation
- [ ] Add image URL formatting
- [ ] Update API documentation
- [ ] Add image URL examples to documentation

## Support

For technical support or API issues, contact the development team.

**Document Version:** 1.0  
**Last Updated:** January 2024  
**Next Review:** February 2024
