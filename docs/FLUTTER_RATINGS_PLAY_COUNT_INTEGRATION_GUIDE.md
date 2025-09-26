# SarvCast Flutter Ratings & Play Count Integration Guide

## Overview
This document provides comprehensive guidance for Flutter developers to integrate with the SarvCast story ratings and episode play count APIs. These features allow users to rate stories and track episode play statistics.

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

// For authenticated requests
Map<String, String> authHeaders = {
  ...headers,
  'Authorization': 'Bearer $userToken',
};
```

## Story Ratings API

### 1. Get Story Ratings

**Route:** `GET /stories/{storyId}/ratings`

**Purpose:** Fetch all ratings for a specific story

**Parameters:**
- `rating` (optional): Filter by rating value (1-5)
- `reviews_only` (optional): Show only ratings with reviews
- `per_page` (optional): Number of ratings per page (default: 20)

**Flutter Implementation:**
```dart
class StoryRatingService {
  static const String baseUrl = 'https://my.sarvcast.ir/api/v1';
  
  Future<List<StoryRating>> getStoryRatings({
    required int storyId,
    int? rating,
    bool? reviewsOnly,
    int? perPage,
  }) async {
    final queryParams = <String, String>{};
    
    if (rating != null) queryParams['rating'] = rating.toString();
    if (reviewsOnly != null) queryParams['reviews_only'] = reviewsOnly.toString();
    if (perPage != null) queryParams['per_page'] = perPage.toString();
    
    final uri = Uri.parse('$baseUrl/stories/$storyId/ratings').replace(
      queryParameters: queryParams,
    );
    
    final response = await http.get(uri, headers: headers);
    
    if (response.statusCode == 200) {
      final data = json.decode(response.body);
      if (data['success'] == true) {
        return (data['data'] as List)
            .map((json) => StoryRating.fromJson(json))
            .toList();
      }
    }
    
    throw Exception('Failed to load story ratings');
  }
}
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Story ratings retrieved successfully",
  "data": [
    {
      "id": 1,
      "user_id": 123,
      "story_id": 456,
      "rating": 4.5,
      "review": "عالی بود! داستان جالبی داشت.",
      "created_at": "2024-01-15T10:30:00Z",
      "updated_at": "2024-01-15T10:30:00Z",
      "user": {
        "id": 123,
        "first_name": "علی",
        "last_name": "احمدی",
        "profile_image_url": "https://example.com/avatar.jpg"
      }
    }
  ],
  "pagination": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 20,
    "total": 89
  }
}
```

### 2. Get Story Rating Statistics

**Route:** `GET /stories/{storyId}/ratings/statistics`

**Purpose:** Fetch rating statistics for a story

**Flutter Implementation:**
```dart
Future<StoryRatingStats> getStoryRatingStats(int storyId) async {
  final uri = Uri.parse('$baseUrl/stories/$storyId/ratings/statistics');
  
  final response = await http.get(uri, headers: headers);
  
  if (response.statusCode == 200) {
    final data = json.decode(response.body);
    if (data['success'] == true) {
      return StoryRatingStats.fromJson(data['data']);
    }
  }
  
  throw Exception('Failed to load story rating statistics');
}
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Story rating statistics retrieved successfully",
  "data": {
    "total_ratings": 89,
    "average_rating": 4.2,
    "rating_distribution": {
      "5": 45,
      "4": 25,
      "3": 12,
      "2": 5,
      "1": 2
    },
    "reviews_count": 23
  }
}
```

### 3. Get User's Rating (Authenticated)

**Route:** `GET /stories/{storyId}/ratings/my`

**Purpose:** Get the current user's rating for a story

**Flutter Implementation:**
```dart
Future<StoryRating?> getUserRating(int storyId) async {
  final uri = Uri.parse('$baseUrl/stories/$storyId/ratings/my');
  
  final response = await http.get(uri, headers: authHeaders);
  
  if (response.statusCode == 200) {
    final data = json.decode(response.body);
    if (data['success'] == true) {
      return StoryRating.fromJson(data['data']);
    }
  } else if (response.statusCode == 404) {
    return null; // User hasn't rated this story
  }
  
  throw Exception('Failed to load user rating');
}
```

### 4. Create or Update Rating (Authenticated)

**Route:** `POST /stories/{storyId}/ratings`

**Purpose:** Create a new rating or update existing one

**Request Body:**
```json
{
  "rating": 4.5,
  "review": "داستان بسیار جالبی بود!"
}
```

**Flutter Implementation:**
```dart
Future<StoryRating> rateStory({
  required int storyId,
  required double rating,
  String? review,
}) async {
  final uri = Uri.parse('$baseUrl/stories/$storyId/ratings');
  
  final body = {
    'rating': rating,
    if (review != null) 'review': review,
  };
  
  final response = await http.post(
    uri,
    headers: authHeaders,
    body: json.encode(body),
  );
  
  if (response.statusCode == 200) {
    final data = json.decode(response.body);
    if (data['success'] == true) {
      return StoryRating.fromJson(data['data']);
    }
  }
  
  throw Exception('Failed to rate story');
}
```

### 5. Delete Rating (Authenticated)

**Route:** `DELETE /stories/{storyId}/ratings`

**Purpose:** Remove user's rating for a story

**Flutter Implementation:**
```dart
Future<void> deleteRating(int storyId) async {
  final uri = Uri.parse('$baseUrl/stories/$storyId/ratings');
  
  final response = await http.delete(uri, headers: authHeaders);
  
  if (response.statusCode == 200) {
    final data = json.decode(response.body);
    if (data['success'] == true) {
      return;
    }
  }
  
  throw Exception('Failed to delete rating');
}
```

## Episode Play Count API

### 1. Increment Play Count

**Route:** `POST /episodes/{episodeId}/play`

**Purpose:** Increment episode play count and record play history

**Request Body:**
```json
{
  "duration_played": 120,
  "completed": false
}
```

**Flutter Implementation:**
```dart
class EpisodePlayService {
  static const String baseUrl = 'https://my.sarvcast.ir/api/v1';
  
  Future<EpisodePlayResult> incrementPlayCount({
    required int episodeId,
    int? durationPlayed,
    bool? completed,
  }) async {
    final uri = Uri.parse('$baseUrl/episodes/$episodeId/play');
    
    final body = <String, dynamic>{};
    if (durationPlayed != null) body['duration_played'] = durationPlayed;
    if (completed != null) body['completed'] = completed;
    
    final response = await http.post(
      uri,
      headers: authHeaders,
      body: json.encode(body),
    );
    
    if (response.statusCode == 200) {
      final data = json.decode(response.body);
      if (data['success'] == true) {
        return EpisodePlayResult.fromJson(data['data']);
      }
    }
    
    throw Exception('Failed to increment play count');
  }
}
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Play count incremented successfully",
  "data": {
    "episode_id": 123,
    "play_count": 1250
  }
}
```

### 2. Get Episode Play Statistics

**Route:** `GET /episodes/{episodeId}/play/statistics`

**Purpose:** Fetch play statistics for an episode

**Flutter Implementation:**
```dart
Future<EpisodePlayStats> getEpisodePlayStats(int episodeId) async {
  final uri = Uri.parse('$baseUrl/episodes/$episodeId/play/statistics');
  
  final response = await http.get(uri, headers: headers);
  
  if (response.statusCode == 200) {
    final data = json.decode(response.body);
    if (data['success'] == true) {
      return EpisodePlayStats.fromJson(data['data']);
    }
  }
  
  throw Exception('Failed to load episode play statistics');
}
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Episode play statistics retrieved successfully",
  "data": {
    "episode_id": 123,
    "total_plays": 1250,
    "unique_listeners": 890,
    "completion_rate": 78.5,
    "average_duration_played": 180.5,
    "recent_plays": [
      {
        "id": 1,
        "user_id": 456,
        "episode_id": 123,
        "story_id": 789,
        "duration_played": 200,
        "completed": true,
        "played_at": "2024-01-15T10:30:00Z",
        "user": {
          "id": 456,
          "first_name": "سارا",
          "last_name": "محمدی"
        }
      }
    ]
  }
}
```

### 3. Get User's Play History (Authenticated)

**Route:** `GET /episodes/{episodeId}/play/history`

**Purpose:** Get user's play history for an episode

**Flutter Implementation:**
```dart
Future<UserPlayHistory> getUserPlayHistory(int episodeId) async {
  final uri = Uri.parse('$baseUrl/episodes/$episodeId/play/history');
  
  final response = await http.get(uri, headers: authHeaders);
  
  if (response.statusCode == 200) {
    final data = json.decode(response.body);
    if (data['success'] == true) {
      return UserPlayHistory.fromJson(data['data']);
    }
  }
  
  throw Exception('Failed to load user play history');
}
```

**Expected Response:**
```json
{
  "success": true,
  "message": "User play history retrieved successfully",
  "data": {
    "episode_id": 123,
    "total_plays": 5,
    "total_duration_played": 900,
    "completed_plays": 3,
    "last_played": "2024-01-15T10:30:00Z",
    "play_history": [
      {
        "id": 1,
        "user_id": 456,
        "episode_id": 123,
        "story_id": 789,
        "duration_played": 200,
        "completed": true,
        "played_at": "2024-01-15T10:30:00Z"
      }
    ]
  }
}
```

### 4. Mark Episode as Completed (Authenticated)

**Route:** `POST /episodes/{episodeId}/play/completed`

**Purpose:** Mark an episode as completed by the user

**Request Body:**
```json
{
  "duration_played": 300
}
```

**Flutter Implementation:**
```dart
Future<PlayHistory> markEpisodeCompleted({
  required int episodeId,
  int? durationPlayed,
}) async {
  final uri = Uri.parse('$baseUrl/episodes/$episodeId/play/completed');
  
  final body = <String, dynamic>{};
  if (durationPlayed != null) body['duration_played'] = durationPlayed;
  
  final response = await http.post(
    uri,
    headers: authHeaders,
    body: json.encode(body),
  );
  
  if (response.statusCode == 200) {
    final data = json.decode(response.body);
    if (data['success'] == true) {
      return PlayHistory.fromJson(data['data']);
    }
  }
  
  throw Exception('Failed to mark episode as completed');
}
```

## Data Models

### StoryRating Model
```dart
class StoryRating {
  final int id;
  final int userId;
  final int storyId;
  final double rating;
  final String? review;
  final DateTime createdAt;
  final DateTime updatedAt;
  final User? user;

  StoryRating({
    required this.id,
    required this.userId,
    required this.storyId,
    required this.rating,
    this.review,
    required this.createdAt,
    required this.updatedAt,
    this.user,
  });

  factory StoryRating.fromJson(Map<String, dynamic> json) {
    return StoryRating(
      id: json['id'],
      userId: json['user_id'],
      storyId: json['story_id'],
      rating: (json['rating'] ?? 0.0).toDouble(),
      review: json['review'],
      createdAt: DateTime.parse(json['created_at']),
      updatedAt: DateTime.parse(json['updated_at']),
      user: json['user'] != null ? User.fromJson(json['user']) : null,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'user_id': userId,
      'story_id': storyId,
      'rating': rating,
      'review': review,
      'created_at': createdAt.toIso8601String(),
      'updated_at': updatedAt.toIso8601String(),
      'user': user?.toJson(),
    };
  }
}
```

### StoryRatingStats Model
```dart
class StoryRatingStats {
  final int totalRatings;
  final double averageRating;
  final Map<int, int> ratingDistribution;
  final int reviewsCount;

  StoryRatingStats({
    required this.totalRatings,
    required this.averageRating,
    required this.ratingDistribution,
    required this.reviewsCount,
  });

  factory StoryRatingStats.fromJson(Map<String, dynamic> json) {
    return StoryRatingStats(
      totalRatings: json['total_ratings'],
      averageRating: (json['average_rating'] ?? 0.0).toDouble(),
      ratingDistribution: Map<int, int>.from(json['rating_distribution']),
      reviewsCount: json['reviews_count'],
    );
  }
}
```

### EpisodePlayResult Model
```dart
class EpisodePlayResult {
  final int episodeId;
  final int playCount;

  EpisodePlayResult({
    required this.episodeId,
    required this.playCount,
  });

  factory EpisodePlayResult.fromJson(Map<String, dynamic> json) {
    return EpisodePlayResult(
      episodeId: json['episode_id'],
      playCount: json['play_count'],
    );
  }
}
```

### EpisodePlayStats Model
```dart
class EpisodePlayStats {
  final int episodeId;
  final int totalPlays;
  final int uniqueListeners;
  final double completionRate;
  final double averageDurationPlayed;
  final List<PlayHistory> recentPlays;

  EpisodePlayStats({
    required this.episodeId,
    required this.totalPlays,
    required this.uniqueListeners,
    required this.completionRate,
    required this.averageDurationPlayed,
    required this.recentPlays,
  });

  factory EpisodePlayStats.fromJson(Map<String, dynamic> json) {
    return EpisodePlayStats(
      episodeId: json['episode_id'],
      totalPlays: json['total_plays'],
      uniqueListeners: json['unique_listeners'],
      completionRate: (json['completion_rate'] ?? 0.0).toDouble(),
      averageDurationPlayed: (json['average_duration_played'] ?? 0.0).toDouble(),
      recentPlays: (json['recent_plays'] as List)
          .map((json) => PlayHistory.fromJson(json))
          .toList(),
    );
  }
}
```

### UserPlayHistory Model
```dart
class UserPlayHistory {
  final int episodeId;
  final int totalPlays;
  final int totalDurationPlayed;
  final int completedPlays;
  final DateTime? lastPlayed;
  final List<PlayHistory> playHistory;

  UserPlayHistory({
    required this.episodeId,
    required this.totalPlays,
    required this.totalDurationPlayed,
    required this.completedPlays,
    this.lastPlayed,
    required this.playHistory,
  });

  factory UserPlayHistory.fromJson(Map<String, dynamic> json) {
    return UserPlayHistory(
      episodeId: json['episode_id'],
      totalPlays: json['total_plays'],
      totalDurationPlayed: json['total_duration_played'],
      completedPlays: json['completed_plays'],
      lastPlayed: json['last_played'] != null 
          ? DateTime.parse(json['last_played']) 
          : null,
      playHistory: (json['play_history'] as List)
          .map((json) => PlayHistory.fromJson(json))
          .toList(),
    );
  }
}
```

### PlayHistory Model
```dart
class PlayHistory {
  final int id;
  final int userId;
  final int episodeId;
  final int storyId;
  final int durationPlayed;
  final bool completed;
  final DateTime playedAt;
  final User? user;

  PlayHistory({
    required this.id,
    required this.userId,
    required this.episodeId,
    required this.storyId,
    required this.durationPlayed,
    required this.completed,
    required this.playedAt,
    this.user,
  });

  factory PlayHistory.fromJson(Map<String, dynamic> json) {
    return PlayHistory(
      id: json['id'],
      userId: json['user_id'],
      episodeId: json['episode_id'],
      storyId: json['story_id'],
      durationPlayed: json['duration_played'],
      completed: json['completed'],
      playedAt: DateTime.parse(json['played_at']),
      user: json['user'] != null ? User.fromJson(json['user']) : null,
    );
  }
}
```

## Flutter UI Implementation

### Story Rating Widget
```dart
class StoryRatingWidget extends StatefulWidget {
  final int storyId;
  final double? currentRating;
  final String? currentReview;
  final Function(double rating, String? review) onRatingChanged;

  const StoryRatingWidget({
    Key? key,
    required this.storyId,
    this.currentRating,
    this.currentReview,
    required this.onRatingChanged,
  }) : super(key: key);

  @override
  _StoryRatingWidgetState createState() => _StoryRatingWidgetState();
}

class _StoryRatingWidgetState extends State<StoryRatingWidget> {
  late double _rating;
  late TextEditingController _reviewController;
  final StoryRatingService _ratingService = StoryRatingService();

  @override
  void initState() {
    super.initState();
    _rating = widget.currentRating ?? 0.0;
    _reviewController = TextEditingController(text: widget.currentReview ?? '');
  }

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          'امتیاز دهید',
          style: TextStyle(
            fontSize: 18,
            fontWeight: FontWeight.bold,
          ),
        ),
        SizedBox(height: 16),
        
        // Star Rating
        Row(
          children: List.generate(5, (index) {
            return GestureDetector(
              onTap: () {
                setState(() {
                  _rating = (index + 1).toDouble();
                });
                widget.onRatingChanged(_rating, _reviewController.text);
              },
              child: Icon(
                index < _rating ? Icons.star : Icons.star_border,
                color: Colors.amber,
                size: 32,
              ),
            );
          }),
        ),
        
        SizedBox(height: 16),
        
        // Review Text Field
        TextField(
          controller: _reviewController,
          decoration: InputDecoration(
            labelText: 'نظر شما (اختیاری)',
            border: OutlineInputBorder(),
            hintText: 'نظر خود را درباره این داستان بنویسید...',
          ),
          maxLines: 3,
          onChanged: (value) {
            widget.onRatingChanged(_rating, value);
          },
        ),
        
        SizedBox(height: 16),
        
        // Submit Button
        ElevatedButton(
          onPressed: _rating > 0 ? _submitRating : null,
          child: Text('ثبت امتیاز'),
        ),
      ],
    );
  }

  Future<void> _submitRating() async {
    try {
      await _ratingService.rateStory(
        storyId: widget.storyId,
        rating: _rating,
        review: _reviewController.text.isNotEmpty ? _reviewController.text : null,
      );
      
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('امتیاز شما ثبت شد')),
      );
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('خطا در ثبت امتیاز: ${e.toString()}')),
      );
    }
  }

  @override
  void dispose() {
    _reviewController.dispose();
    super.dispose();
  }
}
```

### Episode Play Tracker Widget
```dart
class EpisodePlayTracker extends StatefulWidget {
  final int episodeId;
  final int episodeDuration;
  final Function(int durationPlayed, bool completed) onPlayProgress;

  const EpisodePlayTracker({
    Key? key,
    required this.episodeId,
    required this.episodeDuration,
    required this.onPlayProgress,
  }) : super(key: key);

  @override
  _EpisodePlayTrackerState createState() => _EpisodePlayTrackerState();
}

class _EpisodePlayTrackerState extends State<EpisodePlayTracker> {
  final EpisodePlayService _playService = EpisodePlayService();
  int _currentPosition = 0;
  bool _isPlaying = false;
  bool _isCompleted = false;

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        // Progress Bar
        LinearProgressIndicator(
          value: _isCompleted ? 1.0 : (_currentPosition / widget.episodeDuration),
          backgroundColor: Colors.grey[300],
          valueColor: AlwaysStoppedAnimation<Color>(Colors.blue),
        ),
        
        SizedBox(height: 8),
        
        // Time Display
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Text(_formatTime(_currentPosition)),
            Text(_formatTime(widget.episodeDuration)),
          ],
        ),
        
        SizedBox(height: 16),
        
        // Play/Pause Button
        Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            IconButton(
              onPressed: _togglePlayPause,
              icon: Icon(_isPlaying ? Icons.pause : Icons.play_arrow),
              iconSize: 48,
            ),
            if (_currentPosition > 0)
              IconButton(
                onPressed: _markAsCompleted,
                icon: Icon(Icons.check_circle),
                iconSize: 32,
                color: _isCompleted ? Colors.green : Colors.grey,
              ),
          ],
        ),
      ],
    );
  }

  void _togglePlayPause() {
    setState(() {
      _isPlaying = !_isPlaying;
    });
    
    if (_isPlaying) {
      _startPlayback();
    } else {
      _pausePlayback();
    }
  }

  void _startPlayback() {
    // Start audio playback
    // This would integrate with your audio player
    _incrementPlayCount();
  }

  void _pausePlayback() {
    // Pause audio playback
    // This would integrate with your audio player
  }

  void _incrementPlayCount() async {
    try {
      await _playService.incrementPlayCount(
        episodeId: widget.episodeId,
        durationPlayed: _currentPosition,
        completed: _isCompleted,
      );
    } catch (e) {
      print('Error incrementing play count: $e');
    }
  }

  void _markAsCompleted() async {
    try {
      await _playService.markEpisodeCompleted(
        episodeId: widget.episodeId,
        durationPlayed: widget.episodeDuration,
      );
      
      setState(() {
        _isCompleted = true;
        _currentPosition = widget.episodeDuration;
      });
      
      widget.onPlayProgress(_currentPosition, true);
    } catch (e) {
      print('Error marking episode as completed: $e');
    }
  }

  String _formatTime(int seconds) {
    final minutes = seconds ~/ 60;
    final remainingSeconds = seconds % 60;
    return '${minutes.toString().padLeft(2, '0')}:${remainingSeconds.toString().padLeft(2, '0')}';
  }
}
```

### Story Rating Statistics Widget
```dart
class StoryRatingStatisticsWidget extends StatelessWidget {
  final int storyId;
  final StoryRatingService _ratingService = StoryRatingService();

  const StoryRatingStatisticsWidget({
    Key? key,
    required this.storyId,
  }) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return FutureBuilder<StoryRatingStats>(
      future: _ratingService.getStoryRatingStats(storyId),
      builder: (context, snapshot) {
        if (snapshot.connectionState == ConnectionState.waiting) {
          return Center(child: CircularProgressIndicator());
        }
        
        if (snapshot.hasError) {
          return Text('خطا در بارگذاری آمار امتیازات');
        }
        
        final stats = snapshot.data!;
        
        return Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Average Rating
            Row(
              children: [
                Text(
                  'امتیاز متوسط: ',
                  style: TextStyle(fontSize: 16),
                ),
                Text(
                  stats.averageRating.toStringAsFixed(1),
                  style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                    color: Colors.amber,
                  ),
                ),
                Icon(Icons.star, color: Colors.amber),
              ],
            ),
            
            SizedBox(height: 8),
            
            // Total Ratings
            Text(
              'تعداد امتیازات: ${stats.totalRatings}',
              style: TextStyle(fontSize: 14),
            ),
            
            SizedBox(height: 16),
            
            // Rating Distribution
            Text(
              'توزیع امتیازات:',
              style: TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.bold,
              ),
            ),
            
            SizedBox(height: 8),
            
            ...stats.ratingDistribution.entries.map((entry) {
              final rating = entry.key;
              final count = entry.value;
              final percentage = stats.totalRatings > 0 
                  ? (count / stats.totalRatings * 100) 
                  : 0.0;
              
              return Padding(
                padding: EdgeInsets.symmetric(vertical: 4),
                child: Row(
                  children: [
                    Text('$rating ستاره: '),
                    Expanded(
                      child: LinearProgressIndicator(
                        value: percentage / 100,
                        backgroundColor: Colors.grey[300],
                        valueColor: AlwaysStoppedAnimation<Color>(Colors.amber),
                      ),
                    ),
                    SizedBox(width: 8),
                    Text('$count (${percentage.toStringAsFixed(1)}%)'),
                  ],
                ),
              );
            }).toList(),
          ],
        );
      },
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

### Validation Error Handling
```dart
class ValidationException implements Exception {
  final Map<String, List<String>> errors;
  
  ValidationException(this.errors);
  
  @override
  String toString() => 'ValidationException: ${errors.toString()}';
}

Future<T> _handleValidationErrors<T>(Future<T> Function() apiCall) async {
  try {
    return await apiCall();
  } on ApiException catch (e) {
    if (e.statusCode == 422) {
      // Parse validation errors
      final errorData = json.decode(e.message);
      throw ValidationException(errorData['errors']);
    }
    rethrow;
  }
}
```

## Caching Strategy

### Local Caching for Ratings
```dart
class RatingCacheService {
  static const String _ratingStatsKey = 'rating_stats_';
  static const String _userRatingKey = 'user_rating_';
  static const Duration _cacheExpiry = Duration(minutes: 15);
  
  Future<void> cacheRatingStats(int storyId, StoryRatingStats stats) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(
      '$_ratingStatsKey$storyId',
      jsonEncode(stats.toJson()),
    );
    await prefs.setInt(
      '${_ratingStatsKey}${storyId}_timestamp',
      DateTime.now().millisecondsSinceEpoch,
    );
  }
  
  Future<StoryRatingStats?> getCachedRatingStats(int storyId) async {
    final prefs = await SharedPreferences.getInstance();
    final timestamp = prefs.getInt('${_ratingStatsKey}${storyId}_timestamp');
    
    if (timestamp == null) return null;
    
    final cacheTime = DateTime.fromMillisecondsSinceEpoch(timestamp);
    if (DateTime.now().difference(cacheTime) > _cacheExpiry) {
      return null;
    }
    
    final jsonString = prefs.getString('$_ratingStatsKey$storyId');
    if (jsonString == null) return null;
    
    final json = jsonDecode(jsonString);
    return StoryRatingStats.fromJson(json);
  }
}
```

## Performance Optimization

### Debounced Rating Submission
```dart
class DebouncedRatingService {
  Timer? _debounceTimer;
  final StoryRatingService _ratingService = StoryRatingService();
  
  void submitRating({
    required int storyId,
    required double rating,
    String? review,
  }) {
    _debounceTimer?.cancel();
    _debounceTimer = Timer(Duration(milliseconds: 500), () async {
      try {
        await _ratingService.rateStory(
          storyId: storyId,
          rating: rating,
          review: review,
        );
      } catch (e) {
        print('Error submitting rating: $e');
      }
    });
  }
  
  void dispose() {
    _debounceTimer?.cancel();
  }
}
```

### Batch Play Count Updates
```dart
class BatchPlayCountService {
  final Map<int, int> _pendingUpdates = {};
  Timer? _batchTimer;
  final EpisodePlayService _playService = EpisodePlayService();
  
  void updatePlayCount(int episodeId, int durationPlayed) {
    _pendingUpdates[episodeId] = durationPlayed;
    
    _batchTimer?.cancel();
    _batchTimer = Timer(Duration(seconds: 5), _flushUpdates);
  }
  
  Future<void> _flushUpdates() async {
    if (_pendingUpdates.isEmpty) return;
    
    final updates = Map.from(_pendingUpdates);
    _pendingUpdates.clear();
    
    for (final entry in updates.entries) {
      try {
        await _playService.incrementPlayCount(
          episodeId: entry.key,
          durationPlayed: entry.value,
        );
      } catch (e) {
        print('Error updating play count for episode ${entry.key}: $e');
      }
    }
  }
  
  void dispose() {
    _batchTimer?.cancel();
    _flushUpdates();
  }
}
```

## Testing

### Unit Tests
```dart
void main() {
  group('StoryRatingService', () {
    test('should rate story successfully', () async {
      // Mock HTTP response
      when(mockHttpClient.post(any, headers: anyNamed('headers'), body: anyNamed('body')))
          .thenAnswer((_) async => http.Response(
                jsonEncode({
                  'success': true,
                  'message': 'Rating created successfully',
                  'data': {
                    'id': 1,
                    'user_id': 123,
                    'story_id': 456,
                    'rating': 4.5,
                    'review': 'Great story!',
                    'created_at': '2024-01-15T10:30:00Z',
                    'updated_at': '2024-01-15T10:30:00Z',
                  }
                }),
                200,
              ));
      
      final service = StoryRatingService();
      final rating = await service.rateStory(
        storyId: 456,
        rating: 4.5,
        review: 'Great story!',
      );
      
      expect(rating.storyId, 456);
      expect(rating.rating, 4.5);
      expect(rating.review, 'Great story!');
    });
  });
}
```

## Deployment Checklist

### Pre-deployment
- [ ] Test all rating and play count endpoints
- [ ] Verify authentication requirements
- [ ] Test error handling scenarios
- [ ] Implement caching strategy
- [ ] Test offline functionality
- [ ] Verify data validation
- [ ] Test performance under load

### Post-deployment
- [ ] Monitor API response times
- [ ] Check error rates
- [ ] Monitor user engagement
- [ ] Track rating and play count metrics
- [ ] Collect user feedback
- [ ] Monitor database performance

## Troubleshooting

### Common Issues

1. **Rating Not Saving**
   - Check authentication token
   - Verify API endpoint URL
   - Check network connectivity
   - Validate rating value (0-5)

2. **Play Count Not Updating**
   - Check episode ID
   - Verify authentication
   - Check request body format
   - Monitor API response

3. **Statistics Not Loading**
   - Check cache expiration
   - Verify API response format
   - Check network connectivity
   - Monitor error logs

4. **Performance Issues**
   - Implement debouncing
   - Use batch updates
   - Optimize caching strategy
   - Monitor API call frequency

## Support

For technical support or API issues, contact the development team.

**Document Version:** 1.0  
**Last Updated:** January 2024  
**Next Review:** February 2024
