# Story Comments System - Flutter Integration Guide

## Overview

This document provides comprehensive guidance for implementing the story comments system in your Flutter application. The system supports nested comments, likes, replies, and real-time updates.

## Database Schema

### Tables Created

1. **story_comments** - Main comments table
2. **story_comment_likes** - Comment likes tracking

### Key Features

- ✅ **Nested Comments** - Support for replies to comments
- ✅ **Like System** - Users can like/unlike comments
- ✅ **Approval System** - Comments can be approved/rejected
- ✅ **Pinning** - Important comments can be pinned
- ✅ **Rate Limiting** - Prevents spam (2-minute cooldown)
- ✅ **Metadata Support** - Additional data storage
- ✅ **Real-time Updates** - Live comment counts

## API Endpoints

### Base URL
```
https://your-domain.com/api/v1
```

### Authentication
All endpoints require authentication via Sanctum token:
```dart
headers: {
  'Authorization': 'Bearer $token',
  'Content-Type': 'application/json',
  'Accept': 'application/json'
}
```

## API Endpoints Documentation

### 1. Get Story Comments

**Endpoint:** `GET /stories/{storyId}/comments`

**Parameters:**
- `page` (optional): Page number (default: 1)
- `per_page` (optional): Items per page (default: 20, max: 100)
- `include_pending` (optional): Include pending comments (default: false)
- `sort_by` (optional): Sort order - `latest`, `oldest`, `most_liked` (default: latest)
- `include_replies` (optional): Include replies in response (default: true)

**Example Request:**
```dart
final response = await http.get(
  Uri.parse('$baseUrl/stories/123/comments?page=1&per_page=20&sort_by=latest'),
  headers: headers,
);
```

**Response:**
```json
{
  "success": true,
  "message": "نظرات داستان دریافت شد",
  "data": {
    "story_id": 123,
    "comments": [
      {
        "id": 1,
        "content": "عالی بود!",
        "is_approved": true,
        "is_visible": true,
        "is_pinned": false,
        "likes_count": 5,
        "replies_count": 2,
        "is_liked": false,
        "is_reply": false,
        "parent_id": null,
        "created_at": "2025-09-20T10:15:30.000Z",
        "time_since_created": "2 hours ago",
        "user": {
          "id": 456,
          "name": "علی احمدی",
          "avatar": "https://example.com/avatar.jpg"
        },
        "replies": [
          {
            "id": 2,
            "content": "موافقم!",
            "likes_count": 1,
            "replies_count": 0,
            "is_liked": true,
            "is_reply": true,
            "parent_id": 1,
            "created_at": "2025-09-20T11:20:15.000Z",
            "time_since_created": "1 hour ago",
            "user": {
              "id": 789,
              "name": "فاطمه محمدی",
              "avatar": null
            },
            "replies": []
          }
        ]
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 20,
      "total": 50,
      "last_page": 3,
      "has_more": true
    }
  }
}
```

### 2. Add Comment

**Endpoint:** `POST /stories/{storyId}/comments`

**Request Body:**
```json
{
  "content": "نظر شما",
  "parent_id": null, // Optional - for replies
  "metadata": { // Optional
    "device_info": "Flutter App",
    "version": "1.0.0"
  }
}
```

**Example Request:**
```dart
final response = await http.post(
  Uri.parse('$baseUrl/stories/123/comments'),
  headers: headers,
  body: jsonEncode({
    'content': 'داستان فوق‌العاده‌ای بود!',
    'parent_id': null,
    'metadata': {
      'device_info': 'Flutter App',
      'version': '1.0.0'
    }
  }),
);
```

**Response:**
```json
{
  "success": true,
  "message": "نظر شما با موفقیت ارسال شد",
  "data": {
    "comment": {
      "id": 3,
      "content": "داستان فوق‌العاده‌ای بود!",
      "likes_count": 0,
      "replies_count": 0,
      "is_liked": false,
      "is_reply": false,
      "parent_id": null,
      "created_at": "2025-09-20T12:30:45.000Z",
      "time_since_created": "just now",
      "user": {
        "id": 456,
        "name": "علی احمدی",
        "avatar": "https://example.com/avatar.jpg"
      },
      "replies": []
    }
  }
}
```

### 3. Like/Unlike Comment

**Endpoint:** `POST /comments/{commentId}/like`

**Example Request:**
```dart
final response = await http.post(
  Uri.parse('$baseUrl/comments/1/like'),
  headers: headers,
);
```

**Response:**
```json
{
  "success": true,
  "message": "نظر لایک شد",
  "data": {
    "comment_id": 1,
    "likes_count": 6,
    "is_liked": true
  }
}
```

### 4. Get Comment Replies

**Endpoint:** `GET /comments/{commentId}/replies`

**Parameters:**
- `page` (optional): Page number (default: 1)
- `per_page` (optional): Items per page (default: 20, max: 100)

**Example Request:**
```dart
final response = await http.get(
  Uri.parse('$baseUrl/comments/1/replies?page=1&per_page=10'),
  headers: headers,
);
```

### 5. Get User's Comments

**Endpoint:** `GET /comments/my-comments`

**Parameters:**
- `page` (optional): Page number (default: 1)
- `per_page` (optional): Items per page (default: 20, max: 100)
- `story_id` (optional): Filter by specific story

**Example Request:**
```dart
final response = await http.get(
  Uri.parse('$baseUrl/comments/my-comments?page=1&story_id=123'),
  headers: headers,
);
```

### 6. Delete Comment

**Endpoint:** `DELETE /comments/{commentId}`

**Note:** Users can only delete their own comments within 2 hours of creation.

**Example Request:**
```dart
final response = await http.delete(
  Uri.parse('$baseUrl/comments/1'),
  headers: headers,
);
```

### 7. Get Comment Statistics

**Endpoint:** `GET /stories/{storyId}/comments/statistics`

**Example Request:**
```dart
final response = await http.get(
  Uri.parse('$baseUrl/stories/123/comments/statistics'),
  headers: headers,
);
```

**Response:**
```json
{
  "success": true,
  "message": "آمار نظرات داستان دریافت شد",
  "data": {
    "story_id": 123,
    "statistics": {
      "total_comments": 150,
      "approved_comments": 145,
      "pending_comments": 5,
      "pinned_comments": 3,
      "recent_comments": 25
    }
  }
}
```

## Flutter Implementation Guide

### 1. Data Models

Create Dart models for the API responses:

```dart
class Comment {
  final int id;
  final String content;
  final bool isApproved;
  final bool isVisible;
  final bool isPinned;
  final int likesCount;
  final int repliesCount;
  final bool isLiked;
  final bool isReply;
  final int? parentId;
  final DateTime createdAt;
  final String timeSinceCreated;
  final CommentUser user;
  final List<Comment> replies;

  Comment({
    required this.id,
    required this.content,
    required this.isApproved,
    required this.isVisible,
    required this.isPinned,
    required this.likesCount,
    required this.repliesCount,
    required this.isLiked,
    required this.isReply,
    this.parentId,
    required this.createdAt,
    required this.timeSinceCreated,
    required this.user,
    required this.replies,
  });

  factory Comment.fromJson(Map<String, dynamic> json) {
    return Comment(
      id: json['id'],
      content: json['content'],
      isApproved: json['is_approved'],
      isVisible: json['is_visible'],
      isPinned: json['is_pinned'],
      likesCount: json['likes_count'],
      repliesCount: json['replies_count'],
      isLiked: json['is_liked'],
      isReply: json['is_reply'],
      parentId: json['parent_id'],
      createdAt: DateTime.parse(json['created_at']),
      timeSinceCreated: json['time_since_created'],
      user: CommentUser.fromJson(json['user']),
      replies: (json['replies'] as List)
          .map((reply) => Comment.fromJson(reply))
          .toList(),
    );
  }
}

class CommentUser {
  final int id;
  final String name;
  final String? avatar;

  CommentUser({
    required this.id,
    required this.name,
    this.avatar,
  });

  factory CommentUser.fromJson(Map<String, dynamic> json) {
    return CommentUser(
      id: json['id'],
      name: json['name'],
      avatar: json['avatar'],
    );
  }
}

class CommentResponse {
  final bool success;
  final String message;
  final List<Comment> comments;
  final CommentPagination pagination;

  CommentResponse({
    required this.success,
    required this.message,
    required this.comments,
    required this.pagination,
  });

  factory CommentResponse.fromJson(Map<String, dynamic> json) {
    return CommentResponse(
      success: json['success'],
      message: json['message'],
      comments: (json['data']['comments'] as List)
          .map((comment) => Comment.fromJson(comment))
          .toList(),
      pagination: CommentPagination.fromJson(json['data']['pagination']),
    );
  }
}

class CommentPagination {
  final int currentPage;
  final int perPage;
  final int total;
  final int lastPage;
  final bool hasMore;

  CommentPagination({
    required this.currentPage,
    required this.perPage,
    required this.total,
    required this.lastPage,
    required this.hasMore,
  });

  factory CommentPagination.fromJson(Map<String, dynamic> json) {
    return CommentPagination(
      currentPage: json['current_page'],
      perPage: json['per_page'],
      total: json['total'],
      lastPage: json['last_page'],
      hasMore: json['has_more'],
    );
  }
}
```

### 2. API Service

Create an API service for comment operations:

```dart
class CommentService {
  static const String baseUrl = 'https://your-domain.com/api/v1';
  
  static Future<CommentResponse> getComments({
    required int storyId,
    int page = 1,
    int perPage = 20,
    String sortBy = 'latest',
    bool includeReplies = true,
  }) async {
    final token = await AuthService.getToken();
    final response = await http.get(
      Uri.parse('$baseUrl/stories/$storyId/comments?'
          'page=$page&per_page=$perPage&sort_by=$sortBy&include_replies=$includeReplies'),
      headers: {
        'Authorization': 'Bearer $token',
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
    );

    if (response.statusCode == 200) {
      return CommentResponse.fromJson(jsonDecode(response.body));
    } else {
      throw Exception('Failed to load comments');
    }
  }

  static Future<Comment> addComment({
    required int storyId,
    required String content,
    int? parentId,
    Map<String, dynamic>? metadata,
  }) async {
    final token = await AuthService.getToken();
    final response = await http.post(
      Uri.parse('$baseUrl/stories/$storyId/comments'),
      headers: {
        'Authorization': 'Bearer $token',
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: jsonEncode({
        'content': content,
        'parent_id': parentId,
        'metadata': metadata ?? {},
      }),
    );

    if (response.statusCode == 201) {
      final data = jsonDecode(response.body);
      return Comment.fromJson(data['data']['comment']);
    } else {
      throw Exception('Failed to add comment');
    }
  }

  static Future<Map<String, dynamic>> toggleLike(int commentId) async {
    final token = await AuthService.getToken();
    final response = await http.post(
      Uri.parse('$baseUrl/comments/$commentId/like'),
      headers: {
        'Authorization': 'Bearer $token',
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
    );

    if (response.statusCode == 200) {
      final data = jsonDecode(response.body);
      return data['data'];
    } else {
      throw Exception('Failed to toggle like');
    }
  }

  static Future<List<Comment>> getReplies({
    required int commentId,
    int page = 1,
    int perPage = 20,
  }) async {
    final token = await AuthService.getToken();
    final response = await http.get(
      Uri.parse('$baseUrl/comments/$commentId/replies?'
          'page=$page&per_page=$perPage'),
      headers: {
        'Authorization': 'Bearer $token',
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
    );

    if (response.statusCode == 200) {
      final data = jsonDecode(response.body);
      return (data['data']['replies'] as List)
          .map((reply) => Comment.fromJson(reply))
          .toList();
    } else {
      throw Exception('Failed to load replies');
    }
  }

  static Future<bool> deleteComment(int commentId) async {
    final token = await AuthService.getToken();
    final response = await http.delete(
      Uri.parse('$baseUrl/comments/$commentId'),
      headers: {
        'Authorization': 'Bearer $token',
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
    );

    return response.statusCode == 200;
  }
}
```

### 3. UI Components

#### Comment Widget

```dart
class CommentWidget extends StatefulWidget {
  final Comment comment;
  final VoidCallback? onReply;
  final VoidCallback? onLike;
  final VoidCallback? onDelete;

  const CommentWidget({
    Key? key,
    required this.comment,
    this.onReply,
    this.onLike,
    this.onDelete,
  }) : super(key: key);

  @override
  _CommentWidgetState createState() => _CommentWidgetState();
}

class _CommentWidgetState extends State<CommentWidget> {
  bool isLiked = false;
  int likesCount = 0;

  @override
  void initState() {
    super.initState();
    isLiked = widget.comment.isLiked;
    likesCount = widget.comment.likesCount;
  }

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: EdgeInsets.symmetric(vertical: 4),
      child: Padding(
        padding: EdgeInsets.all(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // User info and timestamp
            Row(
              children: [
                CircleAvatar(
                  radius: 16,
                  backgroundImage: widget.comment.user.avatar != null
                      ? NetworkImage(widget.comment.user.avatar!)
                      : null,
                  child: widget.comment.user.avatar == null
                      ? Text(widget.comment.user.name[0])
                      : null,
                ),
                SizedBox(width: 8),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        widget.comment.user.name,
                        style: TextStyle(fontWeight: FontWeight.bold),
                      ),
                      Text(
                        widget.comment.timeSinceCreated,
                        style: TextStyle(
                          fontSize: 12,
                          color: Colors.grey[600],
                        ),
                      ),
                    ],
                  ),
                ),
                if (widget.comment.isPinned)
                  Icon(Icons.push_pin, color: Colors.orange, size: 16),
              ],
            ),
            SizedBox(height: 8),
            
            // Comment content
            Text(widget.comment.content),
            SizedBox(height: 8),
            
            // Action buttons
            Row(
              children: [
                IconButton(
                  icon: Icon(
                    isLiked ? Icons.favorite : Icons.favorite_border,
                    color: isLiked ? Colors.red : Colors.grey,
                  ),
                  onPressed: () async {
                    try {
                      final result = await CommentService.toggleLike(widget.comment.id);
                      setState(() {
                        isLiked = result['is_liked'];
                        likesCount = result['likes_count'];
                      });
                      widget.onLike?.call();
                    } catch (e) {
                      ScaffoldMessenger.of(context).showSnackBar(
                        SnackBar(content: Text('خطا در لایک نظر')),
                      );
                    }
                  },
                ),
                Text('$likesCount'),
                SizedBox(width: 16),
                TextButton.icon(
                  icon: Icon(Icons.reply, size: 16),
                  label: Text('پاسخ'),
                  onPressed: widget.onReply,
                ),
                Spacer(),
                if (widget.comment.user.id == currentUserId)
                  IconButton(
                    icon: Icon(Icons.delete, color: Colors.red),
                    onPressed: widget.onDelete,
                  ),
              ],
            ),
            
            // Replies
            if (widget.comment.replies.isNotEmpty)
              Padding(
                padding: EdgeInsets.only(left: 16),
                child: Column(
                  children: widget.comment.replies
                      .map((reply) => CommentWidget(
                            comment: reply,
                            onReply: widget.onReply,
                            onLike: widget.onLike,
                            onDelete: widget.onDelete,
                          ))
                      .toList(),
                ),
              ),
          ],
        ),
      ),
    );
  }
}
```

#### Comments List Widget

```dart
class CommentsListWidget extends StatefulWidget {
  final int storyId;

  const CommentsListWidget({Key? key, required this.storyId}) : super(key: key);

  @override
  _CommentsListWidgetState createState() => _CommentsListWidgetState();
}

class _CommentsListWidgetState extends State<CommentsListWidget> {
  List<Comment> comments = [];
  bool isLoading = false;
  bool hasMore = true;
  int currentPage = 1;
  String sortBy = 'latest';

  @override
  void initState() {
    super.initState();
    loadComments();
  }

  Future<void> loadComments({bool refresh = false}) async {
    if (isLoading) return;

    setState(() {
      isLoading = true;
      if (refresh) {
        currentPage = 1;
        comments.clear();
        hasMore = true;
      }
    });

    try {
      final response = await CommentService.getComments(
        storyId: widget.storyId,
        page: currentPage,
        sortBy: sortBy,
      );

      setState(() {
        if (refresh) {
          comments = response.comments;
        } else {
          comments.addAll(response.comments);
        }
        hasMore = response.pagination.hasMore;
        currentPage++;
        isLoading = false;
      });
    } catch (e) {
      setState(() {
        isLoading = false;
      });
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('خطا در بارگذاری نظرات')),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        // Sort options
        Row(
          children: [
            Text('مرتب‌سازی:'),
            SizedBox(width: 8),
            DropdownButton<String>(
              value: sortBy,
              items: [
                DropdownMenuItem(value: 'latest', child: Text('جدیدترین')),
                DropdownMenuItem(value: 'oldest', child: Text('قدیمی‌ترین')),
                DropdownMenuItem(value: 'most_liked', child: Text('محبوب‌ترین')),
              ],
              onChanged: (value) {
                if (value != null) {
                  setState(() {
                    sortBy = value;
                  });
                  loadComments(refresh: true);
                }
              },
            ),
          ],
        ),
        
        // Comments list
        Expanded(
          child: RefreshIndicator(
            onRefresh: () => loadComments(refresh: true),
            child: ListView.builder(
              itemCount: comments.length + (hasMore ? 1 : 0),
              itemBuilder: (context, index) {
                if (index == comments.length) {
                  return Center(
                    child: Padding(
                      padding: EdgeInsets.all(16),
                      child: CircularProgressIndicator(),
                    ),
                  );
                }

                return CommentWidget(
                  comment: comments[index],
                  onReply: () => _showReplyDialog(comments[index]),
                  onLike: () {},
                  onDelete: () => _deleteComment(comments[index]),
                );
              },
            ),
          ),
        ),
      ],
    );
  }

  void _showReplyDialog(Comment parentComment) {
    showDialog(
      context: context,
      builder: (context) => ReplyDialog(
        parentComment: parentComment,
        onReply: (content) async {
          try {
            await CommentService.addComment(
              storyId: widget.storyId,
              content: content,
              parentId: parentComment.id,
            );
            loadComments(refresh: true);
          } catch (e) {
            ScaffoldMessenger.of(context).showSnackBar(
              SnackBar(content: Text('خطا در ارسال پاسخ')),
            );
          }
        },
      ),
    );
  }

  void _deleteComment(Comment comment) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: Text('حذف نظر'),
        content: Text('آیا مطمئن هستید که می‌خواهید این نظر را حذف کنید؟'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: Text('لغو'),
          ),
          TextButton(
            onPressed: () => Navigator.pop(context, true),
            child: Text('حذف'),
          ),
        ],
      ),
    );

    if (confirmed == true) {
      try {
        await CommentService.deleteComment(comment.id);
        loadComments(refresh: true);
      } catch (e) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('خطا در حذف نظر')),
        );
      }
    }
  }
}
```

#### Add Comment Widget

```dart
class AddCommentWidget extends StatefulWidget {
  final int storyId;
  final int? parentId;
  final String? parentUserName;
  final VoidCallback? onCommentAdded;

  const AddCommentWidget({
    Key? key,
    required this.storyId,
    this.parentId,
    this.parentUserName,
    this.onCommentAdded,
  }) : super(key: key);

  @override
  _AddCommentWidgetState createState() => _AddCommentWidgetState();
}

class _AddCommentWidgetState extends State<AddCommentWidget> {
  final TextEditingController _controller = TextEditingController();
  bool isSubmitting = false;

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: EdgeInsets.all(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            if (widget.parentUserName != null)
              Text(
                'پاسخ به ${widget.parentUserName}',
                style: TextStyle(
                  fontWeight: FontWeight.bold,
                  color: Colors.blue,
                ),
              ),
            SizedBox(height: 8),
            TextField(
              controller: _controller,
              maxLines: 3,
              decoration: InputDecoration(
                hintText: 'نظر خود را بنویسید...',
                border: OutlineInputBorder(),
              ),
            ),
            SizedBox(height: 8),
            Row(
              mainAxisAlignment: MainAxisAlignment.end,
              children: [
                if (widget.parentId != null)
                  TextButton(
                    onPressed: () => Navigator.pop(context),
                    child: Text('لغو'),
                  ),
                SizedBox(width: 8),
                ElevatedButton(
                  onPressed: isSubmitting ? null : _submitComment,
                  child: isSubmitting
                      ? SizedBox(
                          width: 16,
                          height: 16,
                          child: CircularProgressIndicator(strokeWidth: 2),
                        )
                      : Text('ارسال'),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _submitComment() async {
    if (_controller.text.trim().isEmpty) return;

    setState(() {
      isSubmitting = true;
    });

    try {
      await CommentService.addComment(
        storyId: widget.storyId,
        content: _controller.text.trim(),
        parentId: widget.parentId,
        metadata: {
          'device_info': 'Flutter App',
          'version': '1.0.0',
        },
      );

      _controller.clear();
      widget.onCommentAdded?.call();
      
      if (widget.parentId != null) {
        Navigator.pop(context);
      }
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('خطا در ارسال نظر')),
      );
    } finally {
      setState(() {
        isSubmitting = false;
      });
    }
  }
}
```

### 4. Error Handling

Implement proper error handling for different scenarios:

```dart
class CommentErrorHandler {
  static void handleError(BuildContext context, dynamic error) {
    String message = 'خطای نامشخص';
    
    if (error is SocketException) {
      message = 'خطا در اتصال به اینترنت';
    } else if (error is TimeoutException) {
      message = 'زمان اتصال به پایان رسید';
    } else if (error is HttpException) {
      final response = error.response;
      if (response != null) {
        switch (response.statusCode) {
          case 422:
            message = 'داده‌های ورودی نامعتبر';
            break;
          case 429:
            message = 'تعداد درخواست‌ها زیاد است. لطفاً کمی صبر کنید';
            break;
          case 404:
            message = 'نظر یافت نشد';
            break;
          case 403:
            message = 'شما مجاز به انجام این عمل نیستید';
            break;
          default:
            message = 'خطا در سرور';
        }
      }
    }
    
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(message)),
    );
  }
}
```

### 5. Rate Limiting

The API implements rate limiting (2-minute cooldown between comments). Handle this gracefully:

```dart
class RateLimitHandler {
  static DateTime? lastCommentTime;
  
  static bool canComment() {
    if (lastCommentTime == null) return true;
    
    final now = DateTime.now();
    final difference = now.difference(lastCommentTime!);
    
    return difference.inMinutes >= 2;
  }
  
  static void recordComment() {
    lastCommentTime = DateTime.now();
  }
  
  static Duration? getTimeUntilNextComment() {
    if (lastCommentTime == null) return null;
    
    final now = DateTime.now();
    final difference = now.difference(lastCommentTime!);
    final remaining = Duration(minutes: 2) - difference;
    
    return remaining.isNegative ? null : remaining;
  }
}
```

## Best Practices

### 1. Performance Optimization
- Implement pagination to load comments in chunks
- Use `ListView.builder` for efficient scrolling
- Cache comments locally for offline viewing
- Implement pull-to-refresh functionality

### 2. User Experience
- Show loading indicators during API calls
- Implement optimistic updates for likes
- Provide clear error messages
- Support RTL text direction for Persian content

### 3. Security
- Always validate user input
- Implement proper authentication
- Use HTTPS for all API calls
- Sanitize comment content

### 4. Accessibility
- Add semantic labels for screen readers
- Ensure proper contrast ratios
- Support keyboard navigation
- Provide alternative text for images

## Testing

### Unit Tests
```dart
void main() {
  group('CommentService', () {
    test('should parse comment response correctly', () {
      final json = {
        'success': true,
        'message': 'نظرات داستان دریافت شد',
        'data': {
          'comments': [
            {
              'id': 1,
              'content': 'عالی بود!',
              'likes_count': 5,
              'user': {
                'id': 1,
                'name': 'علی احمدی',
                'avatar': null,
              },
              'replies': [],
            }
          ],
          'pagination': {
            'current_page': 1,
            'per_page': 20,
            'total': 1,
            'last_page': 1,
            'has_more': false,
          }
        }
      };
      
      final response = CommentResponse.fromJson(json);
      expect(response.success, true);
      expect(response.comments.length, 1);
      expect(response.comments.first.content, 'عالی بود!');
    });
  });
}
```

### Integration Tests
```dart
void main() {
  group('Comment Integration Tests', () {
    testWidgets('should display comments list', (tester) async {
      await tester.pumpWidget(
        MaterialApp(
          home: CommentsListWidget(storyId: 1),
        ),
      );
      
      await tester.pumpAndSettle();
      
      expect(find.byType(CommentWidget), findsWidgets);
    });
  });
}
```

## Conclusion

This comprehensive guide provides everything needed to implement a full-featured commenting system in your Flutter application. The system supports nested comments, likes, real-time updates, and proper error handling.

For additional support or questions, please refer to the API documentation or contact the development team.
