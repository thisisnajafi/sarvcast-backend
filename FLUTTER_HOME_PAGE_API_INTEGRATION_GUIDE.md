# SarvCast Flutter Home Page API Integration Guide

## Overview
This document provides comprehensive guidance for Flutter developers to integrate with the SarvCast home page APIs. The home page displays categories, featured stories, recent stories, popular stories, and bedtime stories to provide users with a comprehensive overview of available content.

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

### 1. Categories Endpoint

**Route:** `GET /categories`

**Purpose:** Fetch all available content categories for the home page

**Parameters:**
- `limit` (optional): Number of categories to return (default: 10)
- `page` (optional): Page number for pagination
- `sort_by` (optional): Sort field (name, created_at, updated_at, order)
- `sort_order` (optional): Sort direction (asc, desc)

**Flutter Implementation:**
```dart
class CategoryService {
  static const String baseUrl = 'https://my.sarvcast.ir/api/v1';
  
  Future<List<Category>> getCategories({
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
    
    final uri = Uri.parse('$baseUrl/categories').replace(
      queryParameters: queryParams,
    );
    
    final response = await http.get(uri, headers: headers);
    
    if (response.statusCode == 200) {
      final data = json.decode(response.body);
      if (data['success'] == true) {
        return (data['data'] as List)
            .map((json) => Category.fromJson(json))
            .toList();
      }
    }
    
    throw Exception('Failed to load categories');
  }
}
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Categories retrieved successfully",
  "data": [
    {
      "id": 1,
      "name": "داستان‌های ماجراجویی",
      "slug": "adventure-stories",
      "description": "داستان‌های هیجان‌انگیز و ماجراجویانه برای کودکان",
      "color": "#F59E0B",
      "status": "active",
      "order": 1,
      "story_count": 15,
      "icon_path": "/icons/adventure-stories.svg",
      "created_at": "2024-01-15T10:30:00Z",
      "updated_at": "2024-01-20T14:45:00Z"
    }
  ]
}
```

**Category Model:**
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
  final String iconPath;
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
    required this.iconPath,
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
      createdAt: DateTime.parse(json['created_at']),
      updatedAt: DateTime.parse(json['updated_at']),
    );
  }
}
```

### 2. Featured Stories Endpoint

**Route:** `GET /stories/featured`

**Purpose:** Fetch featured/promoted stories for the home page

**Parameters:**
- `limit` (optional): Number of stories to return (default: 6)

**Flutter Implementation:**
```dart
class StoryService {
  static const String baseUrl = 'https://my.sarvcast.ir/api/v1';
  
  Future<List<Story>> getFeaturedStories({int? limit}) async {
    final queryParams = <String, String>{};
    if (limit != null) queryParams['limit'] = limit.toString();
    
    final uri = Uri.parse('$baseUrl/stories/featured').replace(
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
    
    throw Exception('Failed to load featured stories');
  }
}
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Featured stories retrieved successfully",
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
        "created_at": "2024-01-15T10:30:00Z",
        "updated_at": "2024-01-20T14:45:00Z"
      },
      "narrator": {
        "id": 1,
        "name": "علی احمدی",
        "bio": "راوی با تجربه",
        "image_url": "https://example.com/avatar.jpg",
        "roles": ["narrator", "voice_actor"],
        "total_stories": 10,
        "total_episodes": 50,
        "average_rating": 4.5,
        "is_verified": true,
        "last_active_at": "2024-01-18T16:20:00Z",
        "created_at": "2024-01-10T09:15:00Z"
      },
      "image_url": "https://example.com/story1.jpg",
      "cover_image_url": "https://example.com/story1_cover.jpg",
      "total_episodes": 3,
      "free_episodes": 3,
      "episode_ids": [1, 2, 3],
      "is_favorite": false,
      "progress": 0.0,
      "tags": ["جادو", "جنگل", "ماجراجویی"],
      "language": "fa"
    }
  ]
}
```

### 3. Recent Stories Endpoint

**Route:** `GET /stories/recent`

**Purpose:** Fetch recently added stories

**Parameters:**
- `limit` (optional): Number of stories to return (default: 6)

**Flutter Implementation:**
```dart
Future<List<Story>> getRecentStories({int? limit}) async {
  final queryParams = <String, String>{};
  if (limit != null) queryParams['limit'] = limit.toString();
  
  final uri = Uri.parse('$baseUrl/stories/recent').replace(
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
  
  throw Exception('Failed to load recent stories');
}
```

### 4. Popular Stories Endpoint

**Route:** `GET /stories/popular`

**Purpose:** Fetch most popular stories

**Parameters:**
- `limit` (optional): Number of stories to return (default: 6)
- `period` (optional): Time period for popularity (week, month, year)

**Flutter Implementation:**
```dart
Future<List<Story>> getPopularStories({
  int? limit,
  String? period,
}) async {
  final queryParams = <String, String>{};
  if (limit != null) queryParams['limit'] = limit.toString();
  if (period != null) queryParams['period'] = period;
  
  final uri = Uri.parse('$baseUrl/stories/popular').replace(
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
  
  throw Exception('Failed to load popular stories');
}
```

### 5. Category Stories Endpoint

**Route:** `GET /categories/{id}/stories`

**Purpose:** Fetch stories from a specific category

**Parameters:**
- `id` (required): Category ID
- `limit` (optional): Number of stories to return (default: 6)

**Flutter Implementation:**
```dart
Future<List<Story>> getCategoryStories({
  required int categoryId,
  int? limit,
}) async {
  final queryParams = <String, String>{};
  if (limit != null) queryParams['limit'] = limit.toString();
  
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

**Special Case for Bedtime Stories:**
```dart
// Get bedtime stories (category ID 2)
Future<List<Story>> getBedtimeStories({int? limit}) async {
  return getCategoryStories(categoryId: 2, limit: limit);
}
```

## Data Models

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
  final String? imageUrl;
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
    this.imageUrl,
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
      imageUrl: json['image_url'],
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

## Home Page Implementation

### Home Page Widget
```dart
class HomePage extends StatefulWidget {
  @override
  _HomePageState createState() => _HomePageState();
}

class _HomePageState extends State<HomePage> {
  final CategoryService _categoryService = CategoryService();
  final StoryService _storyService = StoryService();
  
  List<Category> categories = [];
  List<Story> featuredStories = [];
  List<Story> recentStories = [];
  List<Story> popularStories = [];
  List<Story> bedtimeStories = [];
  
  bool isLoading = true;
  String? error;

  @override
  void initState() {
    super.initState();
    _loadHomePageData();
  }

  Future<void> _loadHomePageData() async {
    try {
      setState(() {
        isLoading = true;
        error = null;
      });

      // Load all data in parallel
      final results = await Future.wait([
        _categoryService.getCategories(limit: 10),
        _storyService.getFeaturedStories(limit: 6),
        _storyService.getRecentStories(limit: 6),
        _storyService.getPopularStories(limit: 6),
        _storyService.getBedtimeStories(limit: 6),
      ]);

      setState(() {
        categories = results[0] as List<Category>;
        featuredStories = results[1] as List<Story>;
        recentStories = results[2] as List<Story>;
        popularStories = results[3] as List<Story>;
        bedtimeStories = results[4] as List<Story>;
        isLoading = false;
      });
    } catch (e) {
      setState(() {
        error = e.toString();
        isLoading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    if (isLoading) {
      return Scaffold(
        body: Center(child: CircularProgressIndicator()),
      );
    }

    if (error != null) {
      return Scaffold(
        body: Center(
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Text('خطا در بارگذاری داده‌ها'),
              Text(error!),
              ElevatedButton(
                onPressed: _loadHomePageData,
                child: Text('تلاش مجدد'),
              ),
            ],
          ),
        ),
      );
    }

    return Scaffold(
      body: RefreshIndicator(
        onRefresh: _loadHomePageData,
        child: SingleChildScrollView(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Categories Section
              _buildCategoriesSection(),
              
              // Featured Stories Section
              _buildStoriesSection(
                title: 'داستان‌های ویژه',
                stories: featuredStories,
              ),
              
              // Recent Stories Section
              _buildStoriesSection(
                title: 'جدیدترین داستان‌ها',
                stories: recentStories,
              ),
              
              // Popular Stories Section
              _buildStoriesSection(
                title: 'محبوب‌ترین داستان‌ها',
                stories: popularStories,
              ),
              
              // Bedtime Stories Section
              _buildStoriesSection(
                title: 'داستان‌های شب',
                stories: bedtimeStories,
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildCategoriesSection() {
    return Container(
      height: 120,
      child: ListView.builder(
        scrollDirection: Axis.horizontal,
        padding: EdgeInsets.symmetric(horizontal: 16),
        itemCount: categories.length,
        itemBuilder: (context, index) {
          final category = categories[index];
          return _buildCategoryCard(category);
        },
      ),
    );
  }

  Widget _buildCategoryCard(Category category) {
    return Container(
      width: 100,
      margin: EdgeInsets.only(right: 12),
      child: Column(
        children: [
          Container(
            width: 60,
            height: 60,
            decoration: BoxDecoration(
              color: Color(int.parse(category.color.replaceFirst('#', '0xff'))),
              borderRadius: BorderRadius.circular(30),
            ),
            child: Icon(
              Icons.category,
              color: Colors.white,
              size: 30,
            ),
          ),
          SizedBox(height: 8),
          Text(
            category.name,
            style: TextStyle(fontSize: 12),
            textAlign: TextAlign.center,
            maxLines: 2,
            overflow: TextOverflow.ellipsis,
          ),
        ],
      ),
    );
  }

  Widget _buildStoriesSection({
    required String title,
    required List<Story> stories,
  }) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: EdgeInsets.all(16),
          child: Text(
            title,
            style: TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.bold,
            ),
          ),
        ),
        Container(
          height: 200,
          child: ListView.builder(
            scrollDirection: Axis.horizontal,
            padding: EdgeInsets.symmetric(horizontal: 16),
            itemCount: stories.length,
            itemBuilder: (context, index) {
              final story = stories[index];
              return _buildStoryCard(story);
            },
          ),
        ),
      ],
    );
  }

  Widget _buildStoryCard(Story story) {
    return Container(
      width: 150,
      margin: EdgeInsets.only(right: 12),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            width: 150,
            height: 120,
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(8),
              color: Colors.grey[300],
            ),
            child: story.imageUrl != null
                ? ClipRRect(
                    borderRadius: BorderRadius.circular(8),
                    child: Image.network(
                      story.imageUrl!,
                      fit: BoxFit.cover,
                      errorBuilder: (context, error, stackTrace) {
                        return Icon(Icons.image, size: 50);
                      },
                    ),
                  )
                : Icon(Icons.image, size: 50),
          ),
          SizedBox(height: 8),
          Text(
            story.title,
            style: TextStyle(
              fontSize: 14,
              fontWeight: FontWeight.bold,
            ),
            maxLines: 2,
            overflow: TextOverflow.ellipsis,
          ),
          Text(
            story.subtitle,
            style: TextStyle(
              fontSize: 12,
              color: Colors.grey[600],
            ),
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
          ),
        ],
      ),
    );
  }
}
```

## Error Handling

### Network Error Handling
```dart
class ApiException implements Exception {
  final String message;
  final int? statusCode;
  
  ApiException(this.message, [this.statusCode]);
  
  @override
  String toString() => 'ApiException: $message';
}

Future<T> _handleApiCall<T>(Future<T> Function() apiCall) async {
  try {
    return await apiCall();
  } on SocketException {
    throw ApiException('خطا در اتصال به اینترنت');
  } on TimeoutException {
    throw ApiException('زمان اتصال به سرور به پایان رسید');
  } on FormatException {
    throw ApiException('خطا در فرمت داده‌های دریافتی');
  } catch (e) {
    throw ApiException('خطای نامشخص: ${e.toString()}');
  }
}
```

### Retry Logic
```dart
Future<T> _retryApiCall<T>(
  Future<T> Function() apiCall, {
  int maxRetries = 3,
  Duration delay = const Duration(seconds: 1),
}) async {
  for (int i = 0; i < maxRetries; i++) {
    try {
      return await apiCall();
    } catch (e) {
      if (i == maxRetries - 1) rethrow;
      await Future.delayed(delay * (i + 1));
    }
  }
  throw ApiException('تعداد تلاش‌ها به پایان رسید');
}
```

## Caching Strategy

### Local Caching
```dart
class CacheService {
  static const String _categoriesKey = 'categories';
  static const String _featuredStoriesKey = 'featured_stories';
  static const String _recentStoriesKey = 'recent_stories';
  static const String _popularStoriesKey = 'popular_stories';
  static const String _bedtimeStoriesKey = 'bedtime_stories';
  
  static const Duration _cacheExpiry = Duration(minutes: 30);
  
  Future<void> cacheCategories(List<Category> categories) async {
    final prefs = await SharedPreferences.getInstance();
    final json = categories.map((c) => c.toJson()).toList();
    await prefs.setString(_categoriesKey, jsonEncode(json));
    await prefs.setInt('${_categoriesKey}_timestamp', DateTime.now().millisecondsSinceEpoch);
  }
  
  Future<List<Category>?> getCachedCategories() async {
    final prefs = await SharedPreferences.getInstance();
    final timestamp = prefs.getInt('${_categoriesKey}_timestamp');
    
    if (timestamp == null) return null;
    
    final cacheTime = DateTime.fromMillisecondsSinceEpoch(timestamp);
    if (DateTime.now().difference(cacheTime) > _cacheExpiry) {
      return null;
    }
    
    final jsonString = prefs.getString(_categoriesKey);
    if (jsonString == null) return null;
    
    final json = jsonDecode(jsonString) as List;
    return json.map((j) => Category.fromJson(j)).toList();
  }
}
```

## Performance Optimization

### Image Loading
```dart
class OptimizedImage extends StatelessWidget {
  final String imageUrl;
  final double? width;
  final double? height;
  final BoxFit fit;
  
  const OptimizedImage({
    Key? key,
    required this.imageUrl,
    this.width,
    this.height,
    this.fit = BoxFit.cover,
  }) : super(key: key);
  
  @override
  Widget build(BuildContext context) {
    return CachedNetworkImage(
      imageUrl: imageUrl,
      width: width,
      height: height,
      fit: fit,
      placeholder: (context, url) => Container(
        color: Colors.grey[300],
        child: Center(child: CircularProgressIndicator()),
      ),
      errorWidget: (context, url, error) => Container(
        color: Colors.grey[300],
        child: Icon(Icons.image, size: 50),
      ),
      memCacheWidth: width?.toInt(),
      memCacheHeight: height?.toInt(),
    );
  }
}
```

### Lazy Loading
```dart
class LazyStoryList extends StatefulWidget {
  final Future<List<Story>> Function() loadStories;
  final Widget Function(Story) itemBuilder;
  
  const LazyStoryList({
    Key? key,
    required this.loadStories,
    required this.itemBuilder,
  }) : super(key: key);
  
  @override
  _LazyStoryListState createState() => _LazyStoryListState();
}

class _LazyStoryListState extends State<LazyStoryList> {
  List<Story> stories = [];
  bool isLoading = false;
  bool hasMore = true;
  
  @override
  void initState() {
    super.initState();
    _loadStories();
  }
  
  Future<void> _loadStories() async {
    if (isLoading || !hasMore) return;
    
    setState(() {
      isLoading = true;
    });
    
    try {
      final newStories = await widget.loadStories();
      setState(() {
        stories.addAll(newStories);
        hasMore = newStories.length > 0;
        isLoading = false;
      });
    } catch (e) {
      setState(() {
        isLoading = false;
      });
    }
  }
  
  @override
  Widget build(BuildContext context) {
    return ListView.builder(
      itemCount: stories.length + (isLoading ? 1 : 0),
      itemBuilder: (context, index) {
        if (index == stories.length) {
          return Center(child: CircularProgressIndicator());
        }
        return widget.itemBuilder(stories[index]);
      },
    );
  }
}
```

## Testing

### Unit Tests
```dart
void main() {
  group('CategoryService', () {
    test('should fetch categories successfully', () async {
      // Mock HTTP response
      when(mockHttpClient.get(any))
          .thenAnswer((_) async => http.Response(
                jsonEncode({
                  'success': true,
                  'message': 'Categories retrieved successfully',
                  'data': [
                    {
                      'id': 1,
                      'name': 'Test Category',
                      'slug': 'test-category',
                      'description': 'Test Description',
                      'color': '#FF0000',
                      'status': 'active',
                      'order': 1,
                      'story_count': 5,
                      'icon_path': '/icons/test.svg',
                      'created_at': '2024-01-01T00:00:00Z',
                      'updated_at': '2024-01-01T00:00:00Z',
                    }
                  ]
                }),
                200,
              ));
      
      final service = CategoryService();
      final categories = await service.getCategories();
      
      expect(categories.length, 1);
      expect(categories.first.name, 'Test Category');
    });
  });
}
```

## Deployment Checklist

### Pre-deployment
- [ ] Test all API endpoints
- [ ] Verify error handling
- [ ] Test offline functionality
- [ ] Optimize image loading
- [ ] Implement caching strategy
- [ ] Test on different screen sizes
- [ ] Verify RTL support for Persian text

### Post-deployment
- [ ] Monitor API response times
- [ ] Check error rates
- [ ] Monitor user engagement
- [ ] Track performance metrics
- [ ] Collect user feedback

## Troubleshooting

### Common Issues

1. **Empty Data Response**
   - Check API endpoint URLs
   - Verify network connectivity
   - Check API response format
   - Ensure proper error handling

2. **Slow Loading**
   - Implement caching
   - Optimize image loading
   - Use lazy loading
   - Implement pagination

3. **Image Loading Issues**
   - Check image URLs
   - Implement fallback images
   - Use optimized image formats
   - Implement proper error handling

4. **Persian Text Issues**
   - Ensure proper font support
   - Check RTL text direction
   - Verify text encoding
   - Test on different devices

## Support

For technical support or API issues, contact the development team.

**Document Version:** 1.0  
**Last Updated:** January 2024  
**Next Review:** February 2024
