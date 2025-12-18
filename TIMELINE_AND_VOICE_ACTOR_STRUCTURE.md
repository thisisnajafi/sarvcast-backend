# Timeline and Voice Actor Data Structure

## Overview
This document explains the data structure for image timelines and voice actors in the SarvCast application.

---

## Image Timeline Structure

### Database Model: `ImageTimeline`

#### **Relationship with Episodes**
- **Type**: One-to-Many (Episode has many ImageTimeline entries)
- **Key Field**: `episode_id`
- **Structure**: Multiple image timeline entries per episode, NOT one timeline with multiple images

#### **How It Works**
```
Episode (ID: 1, Duration: 300 seconds)
  ├─ ImageTimeline Entry 1 (start: 0s,   end: 60s,  image: image1.jpg)
  ├─ ImageTimeline Entry 2 (start: 60s,  end: 120s, image: image2.jpg)
  ├─ ImageTimeline Entry 3 (start: 120s, end: 180s, image: image3.jpg)
  ├─ ImageTimeline Entry 4 (start: 180s, end: 240s, image: image4.jpg)
  └─ ImageTimeline Entry 5 (start: 240s, end: 300s, image: image5.jpg)
```

#### **Database Fields**
```php
[
    'episode_id'         => Integer,  // Links to episode
    'start_time'         => Integer,  // Start time in seconds
    'end_time'           => Integer,  // End time in seconds
    'image_url'          => String,   // Path to image file
    'image_order'        => Integer,  // Display order
    'scene_description'  => String,   // Optional description
    'transition_type'    => Enum,     // fade, cut, dissolve, slide
    'is_key_frame'       => Boolean,  // Is this a key frame?
    'voice_actor_id'     => Integer,  // Optional (nullable)
    'character_id'       => Integer,  // Optional (nullable)
    'scene_id'           => Integer,  // Optional (nullable)
]
```

#### **Timeline Entry Rules**
1. Each entry has **one image**
2. Each entry has **start and end times**
3. Entries **should not overlap** (validated in controller)
4. Multiple entries can have the **same episode_id** (this is the normal case)
5. Entries are ordered by `image_order` or `start_time`

#### **Example Timeline Data**
```json
[
    {
        "id": 1,
        "episode_id": 5,
        "start_time": 0,
        "end_time": 60,
        "image_url": "images/episodes/timeline/timeline_abc123.jpg",
        "image_order": 1,
        "scene_description": "Opening scene with mountains",
        "transition_type": "fade",
        "is_key_frame": true
    },
    {
        "id": 2,
        "episode_id": 5,
        "start_time": 60,
        "end_time": 120,
        "image_url": "images/episodes/timeline/timeline_def456.jpg",
        "image_order": 2,
        "scene_description": "Character enters the forest",
        "transition_type": "fade",
        "is_key_frame": false
    }
]
```

---

## Voice Actor Structure

### Database Model: `EpisodeVoiceActor`

#### **Relationship with Episodes**
- **Type**: One-to-Many (Episode has many Voice Actors)
- **Key Field**: `episode_id`
- **Structure**: Multiple voice actors per episode

#### **How It Works**
```
Episode (ID: 1)
  ├─ Voice Actor 1 (Person: John Doe,   Role: Narrator,      Time: 0-300s)
  ├─ Voice Actor 2 (Person: Jane Smith, Role: Character A,   Time: 0-300s)
  └─ Voice Actor 3 (Person: Bob Wilson, Role: Character B,   Time: 0-300s)
```

#### **Database Fields**
```php
[
    'episode_id'         => Integer,  // Links to episode
    'person_id'          => Integer,  // Links to person (voice actor)
    'role'               => String,   // Role/character name
    'character_name'     => String,   // Optional character name
    'start_time'         => Integer,  // Start time (default: 0)
    'end_time'           => Integer,  // End time (default: episode duration)
    'voice_description'  => String,   // Optional description
    'is_primary'         => Boolean,  // Is primary voice actor?
]
```

#### **Current Implementation (As of Update)**
- **`start_time`**: Automatically set to **0** (beginning of episode)
- **`end_time`**: Automatically set to **episode duration** (end of episode)
- **Reason**: Time ranges not currently needed in the app
- **UI**: Time fields are hidden in forms, automatically populated by controller

#### **Example Voice Actor Data**
```json
[
    {
        "id": 1,
        "episode_id": 5,
        "person_id": 10,
        "role": "راوی اصلی",
        "character_name": null,
        "start_time": 0,
        "end_time": 300,
        "voice_description": "صدای گرم و آرام",
        "is_primary": true
    },
    {
        "id": 2,
        "episode_id": 5,
        "person_id": 12,
        "role": "شخصیت اول",
        "character_name": "علی",
        "start_time": 0,
        "end_time": 300,
        "voice_description": null,
        "is_primary": false
    }
]
```

---

## Controller Implementation

### Timeline Controller (`EpisodeTimelineController`)

#### **Create Multiple Timelines**
```php
// Form submission sends:
// - image_timeline_data: JSON string with timeline metadata
// - timeline_image_0, timeline_image_1, ...: Individual image files

foreach ($imageTimelineData as $index => $timelineData) {
    // Get file for this specific timeline entry
    $imageFile = $request->file("timeline_image_{$index}");
    
    // Process and save image
    $imagePath = $this->handleImageUpload($imageFile, $request);
    
    // Create ONE timeline entry with ONE image
    $episode->imageTimelines()->create([
        'start_time' => $timelineData['start_time'],
        'end_time' => $timelineData['end_time'],
        'image_url' => $imagePath,
        'image_order' => $timelineData['image_order'],
        'scene_description' => $timelineData['scene_description'],
        'transition_type' => $timelineData['transition_type'],
        'is_key_frame' => $timelineData['is_key_frame'],
    ]);
}
```

### Voice Actor Controller (`EpisodeVoiceActorController`)

#### **Create Voice Actor with Default Times**
```php
public function store(Request $request, int $episodeId)
{
    // Get episode
    $episode = Episode::findOrFail($episodeId);
    
    // Set default times
    $data = $request->all();
    $data['start_time'] = 0;                    // Always start at beginning
    $data['end_time'] = $episode->duration;     // Always end at episode end
    
    $result = $this->voiceActorService->addVoiceActor($episodeId, $data);
}
```

---

## Frontend Implementation

### Timeline Creation Flow

1. **User opens timeline creation page**
   - Audio player loads with episode audio
   - Playback speed controls available (0.5x to 2x)

2. **User adds images**
   - Click "افزودن تصویر در زمان فعلی" button
   - New timeline row appears with:
     - Current audio time as `start_time`
     - Previous image's `end_time` updated to current time
     - Image upload field
     - Scene description field
     - Transition type dropdown
     - Keyframe checkbox

3. **Form submission**
   - JavaScript collects all timeline data into JSON
   - Each image file sent separately (timeline_image_0, timeline_image_1, ...)
   - FormData used for proper file upload handling
   - Controller creates multiple timeline entries (one per image)

### Voice Actor Form

1. **User opens voice actor create/edit page**
2. **Form shows**:
   - Person selector
   - Role input
   - Character name input
   - Voice description textarea
   - Primary checkbox
   - **Note**: "Voice actor automatically set for full episode duration"
3. **Hidden fields**:
   - `start_time` = 0
   - `end_time` = episode duration
4. **Controller automatically sets times** regardless of form data

---

## Key Differences Summary

| Feature | Timeline | Voice Actor |
|---------|----------|-------------|
| **Multiple per Episode** | ✅ Yes | ✅ Yes |
| **Has Time Range** | ✅ Yes (required, variable) | ✅ Yes (automatic, full duration) |
| **Has Image** | ✅ Yes (one per entry) | ❌ No |
| **Time Range Editable** | ✅ Yes (in UI) | ❌ No (hidden, automatic) |
| **Order Matters** | ✅ Yes (`image_order`) | ⚠️ Optional (`is_primary`) |
| **Can Overlap** | ❌ No (validated) | ✅ Yes (allowed) |

---

## Migration Considerations

### If you need to add time ranges to voice actors later:

1. **Update Controller**: Remove automatic time setting
2. **Update Views**: Unhide time fields in form
3. **Update Validation**: Re-enable time validation rules
4. **Update Service**: Remove default time assignment
5. **Update Frontend**: Add time range UI controls

### Database Schema
The database already supports time ranges for voice actors. No migration needed.

---

## Common Queries

### Get all timeline entries for an episode
```php
$timelines = Episode::find($episodeId)
    ->imageTimelines()
    ->orderBy('start_time')
    ->get();
```

### Get timeline entry at specific time
```php
$timeline = ImageTimeline::where('episode_id', $episodeId)
    ->where('start_time', '<=', $currentTime)
    ->where('end_time', '>=', $currentTime)
    ->first();
```

### Get all voice actors for an episode
```php
$voiceActors = Episode::find($episodeId)
    ->voiceActors()
    ->with('person')
    ->get();
```

### Get primary voice actor
```php
$primaryVoiceActor = Episode::find($episodeId)
    ->voiceActors()
    ->where('is_primary', true)
    ->first();
```

---

## API Response Format

### Timeline Entry
```json
{
    "id": 1,
    "start_time": 0,
    "end_time": 60,
    "image_url": "https://cdn.example.com/images/timeline/abc123.jpg",
    "image_order": 1,
    "scene_description": "Opening scene",
    "transition_type": "fade",
    "is_key_frame": true,
    "start_time_formatted": "00:00",
    "end_time_formatted": "01:00",
    "duration": 60
}
```

### Voice Actor
```json
{
    "id": 1,
    "person": {
        "id": 10,
        "name": "John Doe",
        "image_url": "https://cdn.example.com/images/people/john.jpg",
        "bio": "Professional voice actor"
    },
    "role": "Narrator",
    "character_name": null,
    "voice_description": "Warm and calm voice",
    "is_primary": true
}
```

---

## File Storage

### Timeline Images
- **Location**: `public/images/episodes/timeline/`
- **Naming**: `timeline_{random}_{datetime}.{ext}`
- **Example**: `timeline_abc123xyz456-2024-10-06_12-30-45.jpg`

### Episode Audio
- **Location**: `public/audio/episodes/`
- **Naming**: `audio_{random}_{datetime}.{ext}`
- **Example**: `audio_xyz789abc123-2024-10-06_12-00-00.mp3`

---

## Notes

1. **Timeline entries are NOT grouped** - each row in the database is independent
2. **Voice actor times are currently fixed** - always cover full episode duration
3. **Image files are stored separately** - one file per timeline entry
4. **Overlap validation** - timeline entries cannot overlap, voice actors can
5. **Order matters for timelines** - they play sequentially during episode playback
6. **Primary flag for voice actors** - indicates main narrator/actor

---

Last Updated: October 6, 2024

