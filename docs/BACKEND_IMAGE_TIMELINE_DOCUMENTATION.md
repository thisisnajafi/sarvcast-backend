# Backend Implementation: Image Timeline Feature

## Overview
The Image Timeline feature allows episodes to display different images based on the current audio playback position. Images change with smooth fade animations as the story progresses, creating an immersive visual storytelling experience.

## Database Schema Changes

### 1. Create `image_timelines` Table

```sql
CREATE TABLE image_timelines (
    id VARCHAR(36) PRIMARY KEY,
    episode_id VARCHAR(36) NOT NULL,
    start_time INT NOT NULL COMMENT 'Start time in seconds',
    end_time INT NOT NULL COMMENT 'End time in seconds',
    image_url VARCHAR(500) NOT NULL COMMENT 'Image URL for this time period',
    image_order INT NOT NULL COMMENT 'Order of image in timeline',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (episode_id) REFERENCES episodes(id) ON DELETE CASCADE,
    INDEX idx_episode_time (episode_id, start_time, end_time),
    INDEX idx_episode_order (episode_id, image_order)
);
```

### 2. Update `episodes` Table (Optional)

```sql
-- Add a flag to indicate if episode uses timeline-based images
ALTER TABLE episodes 
ADD COLUMN use_image_timeline BOOLEAN DEFAULT FALSE COMMENT 'Whether episode uses timeline-based image changes';
```

## API Endpoints

### 1. Get Episode with Image Timeline

**Endpoint:** `GET /api/episodes/{episodeId}`

**Response:**
```json
{
  "id": "episode-123",
  "storyId": "story-456",
  "title": "ماجراجویی در جنگل جادویی",
  "description": "داستان پسر کوچکی که در جنگل جادویی گم می شود",
  "audioUrl": "https://cdn.sarvcast.com/audio/episode-123.mp3",
  "duration": 900,
  "isPremium": false,
  "episodeNumber": 1,
  "imageUrls": [
    "https://cdn.sarvcast.com/images/episode-123-image1.jpg",
    "https://cdn.sarvcast.com/images/episode-123-image2.jpg",
    "https://cdn.sarvcast.com/images/episode-123-image3.jpg"
  ],
  "imageTimeline": [
    {
      "startTime": 0,
      "endTime": 45,
      "imageUrl": "https://cdn.sarvcast.com/images/episode-123-image1.jpg"
    },
    {
      "startTime": 46,
      "endTime": 75,
      "imageUrl": "https://cdn.sarvcast.com/images/episode-123-image2.jpg"
    },
    {
      "startTime": 76,
      "endTime": 120,
      "imageUrl": "https://cdn.sarvcast.com/images/episode-123-image3.jpg"
    }
  ],
  "createdAt": "2024-01-15T10:30:00Z",
  "playCount": 1250,
  "rating": 4.5,
  "isDownloaded": false,
  "isCompleted": false,
  "isBookmarked": false,
  "userRating": 0.0,
  "tags": ["ماجراجویی", "جنگل", "جادویی"],
  "narrator": "علی احمدی",
  "director": "مریم رضایی",
  "writer": "حسن محمدی"
}
```

### 2. Create/Update Image Timeline

**Endpoint:** `POST /api/episodes/{episodeId}/image-timeline`

**Request Body:**
```json
{
  "imageTimeline": [
    {
      "startTime": 0,
      "endTime": 45,
      "imageUrl": "https://cdn.sarvcast.com/images/episode-123-image1.jpg"
    },
    {
      "startTime": 46,
      "endTime": 75,
      "imageUrl": "https://cdn.sarvcast.com/images/episode-123-image2.jpg"
    },
    {
      "startTime": 76,
      "endTime": 120,
      "imageUrl": "https://cdn.sarvcast.com/images/episode-123-image3.jpg"
    }
  ]
}
```

**Response:**
```json
{
  "success": true,
  "message": "Image timeline updated successfully",
  "data": {
    "episodeId": "episode-123",
    "timelineCount": 3
  }
}
```

### 3. Get Image Timeline Only

**Endpoint:** `GET /api/episodes/{episodeId}/image-timeline`

**Response:**
```json
{
  "episodeId": "episode-123",
  "imageTimeline": [
    {
      "id": "timeline-1",
      "startTime": 0,
      "endTime": 45,
      "imageUrl": "https://cdn.sarvcast.com/images/episode-123-image1.jpg",
      "imageOrder": 1
    },
    {
      "id": "timeline-2",
      "startTime": 46,
      "endTime": 75,
      "imageUrl": "https://cdn.sarvcast.com/images/episode-123-image2.jpg",
      "imageOrder": 2
    }
  ]
}
```

## Backend Implementation Tasks

### Task 1: Database Migration
- [ ] Create `image_timelines` table
- [ ] Add `use_image_timeline` column to `episodes` table
- [ ] Create necessary indexes for performance
- [ ] Write migration scripts for existing data

### Task 2: Model Implementation
- [ ] Create `ImageTimeline` model class
- [ ] Update `Episode` model to include `imageTimeline` field
- [ ] Add validation for timeline data (no overlapping times, proper ordering)
- [ ] Implement helper methods for timeline operations

### Task 3: Repository Layer
- [ ] Create `ImageTimelineRepository` interface
- [ ] Implement database operations:
  - [ ] `findByEpisodeId(String episodeId)`
  - [ ] `saveTimeline(String episodeId, List<ImageTimeline> timeline)`
  - [ ] `updateTimeline(String episodeId, List<ImageTimeline> timeline)`
  - [ ] `deleteTimeline(String episodeId)`
- [ ] Add caching layer for frequently accessed timelines

### Task 4: Service Layer
- [ ] Create `ImageTimelineService` class
- [ ] Implement business logic:
  - [ ] Timeline validation (no gaps, no overlaps)
  - [ ] Image URL validation
  - [ ] Timeline optimization (merge adjacent identical images)
- [ ] Add error handling and logging

### Task 5: Controller Layer
- [ ] Create `ImageTimelineController`
- [ ] Implement REST endpoints:
  - [ ] `GET /api/episodes/{episodeId}/image-timeline`
  - [ ] `POST /api/episodes/{episodeId}/image-timeline`
  - [ ] `PUT /api/episodes/{episodeId}/image-timeline`
  - [ ] `DELETE /api/episodes/{episodeId}/image-timeline`
- [ ] Add request validation
- [ ] Implement proper HTTP status codes and error responses

### Task 6: Update Episode Controller
- [ ] Modify `GET /api/episodes/{episodeId}` to include image timeline
- [ ] Add optional query parameter `includeTimeline=true`
- [ ] Update episode creation/update endpoints to handle timeline data

### Task 7: Image Management
- [ ] Implement image upload endpoint for timeline images
- [ ] Add image optimization (resize, compress)
- [ ] Implement CDN integration for image delivery
- [ ] Add image validation (format, size, dimensions)

### Task 8: Admin Panel Integration
- [ ] Create timeline management interface
- [ ] Add visual timeline editor
- [ ] Implement drag-and-drop timeline creation
- [ ] Add preview functionality

### Task 9: Testing
- [ ] Unit tests for models and services
- [ ] Integration tests for API endpoints
- [ ] Performance tests for timeline queries
- [ ] Load testing for image delivery

### Task 10: Documentation
- [ ] API documentation with examples
- [ ] Database schema documentation
- [ ] Admin panel user guide
- [ ] Performance optimization guide

## Data Validation Rules

### Timeline Validation
1. **No Gaps**: Timeline must cover the entire episode duration
2. **No Overlaps**: No two timeline entries can have overlapping time ranges
3. **Proper Ordering**: Timeline entries must be in chronological order
4. **Valid Times**: Start time must be >= 0, end time must be <= episode duration
5. **Image URLs**: Must be valid URLs pointing to accessible images

### Example Valid Timeline
```json
[
  {"startTime": 0, "endTime": 45, "imageUrl": "image1.jpg"},
  {"startTime": 46, "endTime": 90, "imageUrl": "image2.jpg"},
  {"startTime": 91, "endTime": 120, "imageUrl": "image3.jpg"}
]
```

### Example Invalid Timeline (Gap)
```json
[
  {"startTime": 0, "endTime": 45, "imageUrl": "image1.jpg"},
  {"startTime": 50, "endTime": 90, "imageUrl": "image2.jpg"}  // Gap from 46-49
]
```

## Performance Considerations

### Database Optimization
- Index on `(episode_id, start_time, end_time)` for fast timeline lookups
- Consider partitioning by episode_id for large datasets
- Use connection pooling for database connections

### Caching Strategy
- Cache timeline data in Redis with TTL of 1 hour
- Implement cache invalidation on timeline updates
- Use CDN for image delivery with appropriate cache headers

### Image Optimization
- Serve images in WebP format for better compression
- Implement responsive images (different sizes for different devices)
- Use lazy loading for timeline images

## Security Considerations

### Image Upload Security
- Validate file types and sizes
- Scan uploaded images for malware
- Implement rate limiting for uploads
- Use signed URLs for secure image access

### API Security
- Implement proper authentication and authorization
- Validate all input data
- Implement rate limiting
- Use HTTPS for all image URLs

## Monitoring and Analytics

### Metrics to Track
- Timeline load times
- Image delivery performance
- User engagement with timeline features
- Error rates for timeline operations

### Logging
- Log all timeline operations
- Track performance metrics
- Monitor error patterns
- Log image delivery statistics

## Future Enhancements

### Advanced Features
- [ ] Dynamic timeline based on user preferences
- [ ] Interactive timeline elements
- [ ] Timeline sharing and collaboration
- [ ] A/B testing for different timeline configurations
- [ ] Machine learning for optimal timeline creation

### Integration Opportunities
- [ ] Integration with video editing tools
- [ ] Automated timeline generation from audio analysis
- [ ] Integration with content management systems
- [ ] Export timeline data for external tools
