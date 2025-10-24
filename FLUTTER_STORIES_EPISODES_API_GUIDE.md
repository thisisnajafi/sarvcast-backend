# Flutter API Integration Guide - Stories & Episodes

## 📚 Table of Contents
1. [Stories API Integration](#stories-api-integration)
2. [Episodes API Integration](#episodes-api-integration)
3. [Categories API Integration](#categories-api-integration)
4. [Timeline Management](#timeline-management)
5. [Play Count Tracking](#play-count-tracking)
6. [Error Handling](#error-handling)
7. [Sample Implementation](#sample-implementation)

---

## 🎯 Stories API Integration

### **Base URL**
```
https://my.sarvcast.ir/api/v1
```

### **1. Get All Stories**
```dart
// GET /stories
final response = await http.get(
  Uri.parse('https://my.sarvcast.ir/api/v1/stories?limit=20'),
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
  },
);
```

**Response Format:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "پشمالو",
      "subtitle": "پشمالو",
      "description": "تخساخ",
      "image_url": "https://my.sarvcast.ir/images/stories/1758956130_cover.webp",
      "cover_image_url": "https://my.sarvcast.ir/images/stories/1758956130_cover_cover.webp",
      "category_id": 5,
      "age_group": "3-5",
      "language": "persian",
      "duration": 0, // Duration in seconds
      "total_episodes": 1,
      "free_episodes": 0,
      "is_premium": false,
      "is_completely_free": true,
      "play_count": 0,
      "rating": "0.00",
      "tags": ["ماجراجویی"],
      "status": "published",
      "created_at": "2025-09-27T06:55:30.000000Z",
      "updated_at": "2025-09-27T08:03:06.000000Z",
      "category": {
        "id": 5,
        "name": "داستان‌های حیوانات",
        "slug": "animal-stories",
        "description": "داستان‌هایی که شخصیت‌های اصلی آن‌ها حیوانات هستند",
        "icon_path": "categories/1758958943_cover.webp",
        "color": "#f97316",
        "story_count": 1,
        "is_active": true
      },
      "director": {
        "id": 1,
        "name": "امیر مسعود عابدینی",
        "roles": ["voice_actor", "director", "writer", "producer", "author", "narrator"],
        "is_verified": true
      },
      "narrator": {
        "id": 1,
        "name": "امیر مسعود عابدینی",
        "roles": ["voice_actor", "director", "writer", "producer", "author", "narrator"],
        "is_verified": true
      }
    }
  ]
}
```

### **2. Get Featured Stories**
```dart
// GET /stories/featured
final response = await http.get(
  Uri.parse('https://my.sarvcast.ir/api/v1/stories/featured?limit=6'),
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
  },
);
```

### **3. Get Popular Stories**
```dart
// GET /stories/popular
final response = await http.get(
  Uri.parse('https://my.sarvcast.ir/api/v1/stories/popular?limit=6&period=week'),
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
  },
);
```

### **4. Get Recent Stories**
```dart
// GET /stories/recent
final response = await http.get(
  Uri.parse('https://my.sarvcast.ir/api/v1/stories/recent?limit=6'),
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
  },
);
```

### **5. Get Single Story Details**
```dart
// GET /stories/{id}
final response = await http.get(
  Uri.parse('https://my.sarvcast.ir/api/v1/stories/1'),
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
  },
);
```

---

## 🎬 Episodes API Integration

### **1. Get Story Episodes**
```dart
// GET /stories/{id}/episodes
final response = await http.get(
  Uri.parse('https://my.sarvcast.ir/api/v1/stories/1/episodes'),
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
  },
);
```

**Response Format:**
```json
{
  "success": true,
  "data": {
    "episodes": [
      {
        "id": 1,
        "story_id": 1,
        "title": "داستان پشمالو",
        "description": "پشمالو بدون غرش هم شجاعه",
        "audio_url": "https://my.sarvcast.ir/episodes/audio/cDSxlmldYnILXhfxJaSOsSVBh9Vk1jCcNHX78E5r.mp3",
        "duration": 409, // Duration in seconds
        "episode_number": 1,
        "is_premium": false,
        "play_count": 0,
        "rating": "0.00",
        "status": "published",
        "published_at": "2025-09-26T16:56:20.000000Z",
        "created_at": "2025-09-26T16:56:20.000000Z",
        "updated_at": "2025-09-26T16:56:20.000000Z",
        "narrator": {
          "id": 1,
          "name": "امیر مسعود عابدینی",
          "roles": ["narrator"],
          "is_verified": true
        },
        "people": [
          {
            "id": 1,
            "name": "امیر مسعود عابدینی",
            "roles": ["voice_actor", "director"],
            "image_url": null
          }
        ],
        "image_timelines": [
          {
            "id": 1,
            "episode_id": 1,
            "start_time": 0,
            "end_time": 60,
            "image_url": "https://my.sarvcast.ir/images/timeline/image1.jpg",
            "image_order": 1,
            "scene_description": "شروع داستان",
            "transition_type": "fade",
            "is_key_frame": true
          }
        ]
      }
    ]
  }
}
```

### **2. Get Single Episode Details**
```dart
// GET /episodes/{id}
final response = await http.get(
  Uri.parse('https://my.sarvcast.ir/api/v1/episodes/1'),
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
  },
);
```

---

## 📂 Categories API Integration

### **1. Get All Categories**
```dart
// GET /categories
final response = await http.get(
  Uri.parse('https://my.sarvcast.ir/api/v1/categories'),
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
  },
);
```

**Response Format:**
```json
{
  "success": true,
  "data": [
    {
      "id": 5,
      "name": "داستان‌های حیوانات",
      "slug": "animal-stories",
      "description": "داستان‌هایی که شخصیت‌های اصلی آن‌ها حیوانات هستند",
      "color": "#f97316",
      "status": "active",
      "order": 5,
      "story_count": 1,
      "icon_path": "categories/1758958943_cover.webp",
      "image_url": "https://my.sarvcast.ir/images/categories/1758958943_cover.webp",
      "created_at": "2025-09-27T03:11:56.000000Z",
      "updated_at": "2025-09-27T08:03:06.000000Z"
    }
  ]
}
```

### **2. Get Category Stories**
```dart
// GET /categories/{id}/stories
final response = await http.get(
  Uri.parse('https://my.sarvcast.ir/api/v1/categories/5/stories?limit=10'),
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
  },
);
```

---

## 🎞️ Timeline Management

### **Image Timeline Structure**
Each episode can have multiple image timelines that show images at specific time intervals during playback.

```dart
class ImageTimeline {
  final int id;
  final int episodeId;
  final int startTime; // Start time in seconds
  final int endTime;   // End time in seconds
  final String imageUrl;
  final int imageOrder;
  final String sceneDescription;
  final String transitionType; // 'fade', 'slide', 'zoom'
  final bool isKeyFrame;

  ImageTimeline({
    required this.id,
    required this.episodeId,
    required this.startTime,
    required this.endTime,
    required this.imageUrl,
    required this.imageOrder,
    required this.sceneDescription,
    required this.transitionType,
    required this.isKeyFrame,
  });

  factory ImageTimeline.fromJson(Map<String, dynamic> json) {
    return ImageTimeline(
      id: json['id'],
      episodeId: json['episode_id'],
      startTime: json['start_time'],
      endTime: json['end_time'],
      imageUrl: json['image_url'],
      imageOrder: json['image_order'],
      sceneDescription: json['scene_description'],
      transitionType: json['transition_type'],
      isKeyFrame: json['is_key_frame'],
    );
  }
}
```

### **Timeline Implementation Example**
```dart
class EpisodePlayer extends StatefulWidget {
  final Episode episode;
  
  @override
  _EpisodePlayerState createState() => _EpisodePlayerState();
}

class _EpisodePlayerState extends State<EpisodePlayer> {
  AudioPlayer audioPlayer = AudioPlayer();
  Duration currentPosition = Duration.zero;
  List<ImageTimeline> timelines = [];
  String? currentImageUrl;

  @override
  void initState() {
    super.initState();
    loadTimelines();
    setupAudioPlayer();
  }

  void loadTimelines() {
    // Load timelines from episode data
    timelines = widget.episode.imageTimelines ?? [];
  }

  void setupAudioPlayer() {
    audioPlayer.onPositionChanged.listen((position) {
      setState(() {
        currentPosition = position;
        updateCurrentImage(position);
      });
    });
  }

  void updateCurrentImage(Duration position) {
    final currentSeconds = position.inSeconds;
    
    for (var timeline in timelines) {
      if (currentSeconds >= timeline.startTime && 
          currentSeconds <= timeline.endTime) {
        if (currentImageUrl != timeline.imageUrl) {
          setState(() {
            currentImageUrl = timeline.imageUrl;
          });
        }
        break;
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        // Display current timeline image
        if (currentImageUrl != null)
          Image.network(
            currentImageUrl!,
            width: double.infinity,
            height: 200,
            fit: BoxFit.cover,
          ),
        
        // Audio player controls
        AudioPlayerWidget(
          audioPlayer: audioPlayer,
          episode: widget.episode,
        ),
      ],
    );
  }
}
```

---

## 📊 Play Count Tracking

### **Increment Episode Play Count**
```dart
// POST /episodes/{id}/play
Future<void> incrementPlayCount(int episodeId) async {
  try {
    final response = await http.post(
      Uri.parse('https://my.sarvcast.ir/api/v1/episodes/$episodeId/play'),
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'Authorization': 'Bearer $userToken', // If user is authenticated
      },
    );

    if (response.statusCode == 200) {
      print('Play count incremented successfully');
    } else {
      print('Failed to increment play count: ${response.statusCode}');
    }
  } catch (e) {
    print('Error incrementing play count: $e');
  }
}
```

### **Get Episode Play Statistics**
```dart
// GET /episodes/{id}/play/statistics
Future<Map<String, dynamic>> getEpisodeStatistics(int episodeId) async {
  try {
    final response = await http.get(
      Uri.parse('https://my.sarvcast.ir/api/v1/episodes/$episodeId/play/statistics'),
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
    );

    if (response.statusCode == 200) {
      final data = json.decode(response.body);
      return data['data'];
    }
  } catch (e) {
    print('Error getting episode statistics: $e');
  }
  return {};
}
```

---

## 🛠️ Error Handling

### **Common Error Responses**
```json
{
  "success": false,
  "message": "Error message",
  "errors": {
    "field_name": ["Validation error message"]
  }
}
```

### **Error Handling Implementation**
```dart
class ApiService {
  static Future<Map<String, dynamic>> handleResponse(http.Response response) async {
    if (response.statusCode == 200) {
      return json.decode(response.body);
    } else if (response.statusCode == 401) {
      throw Exception('Unauthorized - Please login again');
    } else if (response.statusCode == 403) {
      throw Exception('Forbidden - Access denied');
    } else if (response.statusCode == 404) {
      throw Exception('Not found - Resource not available');
    } else if (response.statusCode == 500) {
      throw Exception('Server error - Please try again later');
    } else {
      throw Exception('Unknown error occurred');
    }
  }
}
```

---

## 📱 Sample Implementation

### **Story Model**
```dart
class Story {
  final int id;
  final String title;
  final String subtitle;
  final String description;
  final String imageUrl;
  final String coverImageUrl;
  final int categoryId;
  final String ageGroup;
  final String language;
  final int duration; // Duration in seconds
  final int totalEpisodes;
  final int freeEpisodes;
  final bool isPremium;
  final bool isCompletelyFree;
  final int playCount;
  final double rating;
  final List<String> tags;
  final String status;
  final DateTime createdAt;
  final DateTime updatedAt;
  final Category? category;
  final Person? director;
  final Person? narrator;

  Story({
    required this.id,
    required this.title,
    required this.subtitle,
    required this.description,
    required this.imageUrl,
    required this.coverImageUrl,
    required this.categoryId,
    required this.ageGroup,
    required this.language,
    required this.duration,
    required this.totalEpisodes,
    required this.freeEpisodes,
    required this.isPremium,
    required this.isCompletelyFree,
    required this.playCount,
    required this.rating,
    required this.tags,
    required this.status,
    required this.createdAt,
    required this.updatedAt,
    this.category,
    this.director,
    this.narrator,
  });

  factory Story.fromJson(Map<String, dynamic> json) {
    return Story(
      id: json['id'],
      title: json['title'],
      subtitle: json['subtitle'],
      description: json['description'],
      imageUrl: json['image_url'] ?? '',
      coverImageUrl: json['cover_image_url'] ?? '',
      categoryId: json['category_id'],
      ageGroup: json['age_group'],
      language: json['language'],
      duration: json['duration'] ?? 0,
      totalEpisodes: json['total_episodes'] ?? 0,
      freeEpisodes: json['free_episodes'] ?? 0,
      isPremium: json['is_premium'] ?? false,
      isCompletelyFree: json['is_completely_free'] ?? false,
      playCount: json['play_count'] ?? 0,
      rating: double.tryParse(json['rating'].toString()) ?? 0.0,
      tags: List<String>.from(json['tags'] ?? []),
      status: json['status'],
      createdAt: DateTime.parse(json['created_at']),
      updatedAt: DateTime.parse(json['updated_at']),
      category: json['category'] != null ? Category.fromJson(json['category']) : null,
      director: json['director'] != null ? Person.fromJson(json['director']) : null,
      narrator: json['narrator'] != null ? Person.fromJson(json['narrator']) : null,
    );
  }

  // Helper methods
  String get formattedDuration {
    final minutes = duration ~/ 60;
    final seconds = duration % 60;
    return '${minutes}:${seconds.toString().padLeft(2, '0')}';
  }

  bool get hasFreeContent => freeEpisodes > 0 || isCompletelyFree;
}
```

### **Episode Model**
```dart
class Episode {
  final int id;
  final int storyId;
  final String title;
  final String description;
  final String audioUrl;
  final int duration; // Duration in seconds
  final int episodeNumber;
  final bool isPremium;
  final int playCount;
  final double rating;
  final String status;
  final DateTime publishedAt;
  final DateTime createdAt;
  final DateTime updatedAt;
  final Person? narrator;
  final List<Person> people;
  final List<ImageTimeline> imageTimelines;

  Episode({
    required this.id,
    required this.storyId,
    required this.title,
    required this.description,
    required this.audioUrl,
    required this.duration,
    required this.episodeNumber,
    required this.isPremium,
    required this.playCount,
    required this.rating,
    required this.status,
    required this.publishedAt,
    required this.createdAt,
    required this.updatedAt,
    this.narrator,
    required this.people,
    required this.imageTimelines,
  });

  factory Episode.fromJson(Map<String, dynamic> json) {
    return Episode(
      id: json['id'],
      storyId: json['story_id'],
      title: json['title'],
      description: json['description'],
      audioUrl: json['audio_url'],
      duration: json['duration'] ?? 0,
      episodeNumber: json['episode_number'],
      isPremium: json['is_premium'] ?? false,
      playCount: json['play_count'] ?? 0,
      rating: double.tryParse(json['rating'].toString()) ?? 0.0,
      status: json['status'],
      publishedAt: DateTime.parse(json['published_at']),
      createdAt: DateTime.parse(json['created_at']),
      updatedAt: DateTime.parse(json['updated_at']),
      narrator: json['narrator'] != null ? Person.fromJson(json['narrator']) : null,
      people: (json['people'] as List<dynamic>?)
          ?.map((person) => Person.fromJson(person))
          .toList() ?? [],
      imageTimelines: (json['image_timelines'] as List<dynamic>?)
          ?.map((timeline) => ImageTimeline.fromJson(timeline))
          .toList() ?? [],
    );
  }

  String get formattedDuration {
    final minutes = duration ~/ 60;
    final seconds = duration % 60;
    return '${minutes}:${seconds.toString().padLeft(2, '0')}';
  }
}
```

### **API Service Implementation**
```dart
class StoryApiService {
  static const String baseUrl = 'https://my.sarvcast.ir/api/v1';

  static Future<List<Story>> getStories({int limit = 20}) async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/stories?limit=$limit'),
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
      );

      final data = await ApiService.handleResponse(response);
      
      if (data['success'] == true) {
        final storiesJson = data['data'] as List<dynamic>;
        return storiesJson.map((json) => Story.fromJson(json)).toList();
      } else {
        throw Exception(data['message'] ?? 'Failed to load stories');
      }
    } catch (e) {
      print('Error loading stories: $e');
      return [];
    }
  }

  static Future<List<Episode>> getStoryEpisodes(int storyId) async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/stories/$storyId/episodes'),
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
      );

      final data = await ApiService.handleResponse(response);
      
      if (data['success'] == true) {
        final episodesData = data['data']['episodes'] as List<dynamic>;
        return episodesData.map((json) => Episode.fromJson(json)).toList();
      } else {
        throw Exception(data['message'] ?? 'Failed to load episodes');
      }
    } catch (e) {
      print('Error loading episodes: $e');
      return [];
    }
  }

  static Future<void> incrementPlayCount(int episodeId) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/episodes/$episodeId/play'),
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
      );

      if (response.statusCode != 200) {
        print('Failed to increment play count: ${response.statusCode}');
      }
    } catch (e) {
      print('Error incrementing play count: $e');
    }
  }
}
```

---

## 🔧 Troubleshooting

### **Common Issues:**

1. **Getting 0 stories despite API returning data:**
   - Check if you're parsing the response correctly
   - Verify the `data` field contains the stories array
   - Ensure you're handling the `success` field properly

2. **Timeline images not showing:**
   - Verify image URLs are accessible
   - Check if timeline data is properly loaded
   - Ensure audio player position updates are working

3. **Play count not incrementing:**
   - Check network connectivity
   - Verify API endpoint is correct
   - Handle authentication if required

### **Debug Tips:**
```dart
// Add logging to debug API responses
void debugApiResponse(http.Response response) {
  print('Status Code: ${response.statusCode}');
  print('Response Body: ${response.body}');
  
  try {
    final data = json.decode(response.body);
    print('Parsed Data: $data');
  } catch (e) {
    print('JSON Parse Error: $e');
  }
}
```

---

## 📋 Quick Reference

### **API Endpoints:**
- `GET /stories` - Get all stories
- `GET /stories/featured` - Get featured stories
- `GET /stories/popular` - Get popular stories
- `GET /stories/recent` - Get recent stories
- `GET /stories/{id}` - Get single story
- `GET /stories/{id}/episodes` - Get story episodes
- `GET /episodes/{id}` - Get single episode
- `POST /episodes/{id}/play` - Increment play count
- `GET /categories` - Get all categories
- `GET /categories/{id}/stories` - Get category stories

### **Key Features:**
- ✅ Story management with images and metadata
- ✅ Episode playback with timeline support
- ✅ Free and premium content handling
- ✅ Play count tracking
- ✅ Category-based organization
- ✅ Timeline image synchronization
- ✅ Error handling and validation

This comprehensive guide should help you implement all story and episode functionality in your Flutter application! 🚀
