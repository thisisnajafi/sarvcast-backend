# Episode Enhancement Documentation
## Multiple Voice Actors & Timeline-Based Images

### Overview
This document outlines the implementation of two major enhancements to the SarvCast platform:

1. **Multiple Voice Actors per Episode**: Each episode can now have multiple voice actors with specific roles and time segments
2. **Timeline-Based Images**: Each episode can have multiple images synchronized with specific time segments of the audio

---

## 1. Multiple Voice Actors per Episode

### 1.1 Database Schema Changes

#### New Table: `episode_voice_actors`
```sql
CREATE TABLE episode_voice_actors (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    episode_id BIGINT UNSIGNED NOT NULL,
    person_id BIGINT UNSIGNED NOT NULL,
    role VARCHAR(100) NOT NULL, -- 'narrator', 'character_1', 'character_2', etc.
    character_name VARCHAR(255) NULL, -- Name of the character being voiced
    start_time INT UNSIGNED NOT NULL, -- Start time in seconds
    end_time INT UNSIGNED NOT NULL, -- End time in seconds
    voice_description TEXT NULL, -- Description of voice characteristics
    is_primary BOOLEAN DEFAULT FALSE, -- Primary voice actor for the episode
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    
    FOREIGN KEY (episode_id) REFERENCES episodes(id) ON DELETE CASCADE,
    FOREIGN KEY (person_id) REFERENCES people(id) ON DELETE CASCADE,
    
    INDEX idx_episode_voice_actors_episode_id (episode_id),
    INDEX idx_episode_voice_actors_person_id (person_id),
    INDEX idx_episode_voice_actors_time_range (start_time, end_time),
    INDEX idx_episode_voice_actors_role (role),
    UNIQUE KEY unique_episode_person_role_time (episode_id, person_id, role, start_time)
);
```

#### Updated Table: `episodes`
```sql
ALTER TABLE episodes ADD COLUMN has_multiple_voice_actors BOOLEAN DEFAULT FALSE AFTER use_image_timeline;
ALTER TABLE episodes ADD COLUMN voice_actor_count INT UNSIGNED DEFAULT 0 AFTER has_multiple_voice_actors;
```

### 1.2 Model Updates

#### New Model: `EpisodeVoiceActor`
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EpisodeVoiceActor extends Model
{
    protected $fillable = [
        'episode_id',
        'person_id',
        'role',
        'character_name',
        'start_time',
        'end_time',
        'voice_description',
        'is_primary'
    ];

    protected $casts = [
        'start_time' => 'integer',
        'end_time' => 'integer',
        'is_primary' => 'boolean'
    ];

    /**
     * Get the episode that owns the voice actor
     */
    public function episode(): BelongsTo
    {
        return $this->belongsTo(Episode::class);
    }

    /**
     * Get the person (voice actor)
     */
    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    /**
     * Scope for primary voice actors
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Scope for voice actors in a time range
     */
    public function scopeInTimeRange($query, int $startTime, int $endTime)
    {
        return $query->where(function($q) use ($startTime, $endTime) {
            $q->whereBetween('start_time', [$startTime, $endTime])
              ->orWhereBetween('end_time', [$startTime, $endTime])
              ->orWhere(function($q2) use ($startTime, $endTime) {
                  $q2->where('start_time', '<=', $startTime)
                     ->where('end_time', '>=', $endTime);
              });
        });
    }

    /**
     * Get voice actor data for API response
     */
    public function toApiResponse(): array
    {
        return [
            'id' => $this->id,
            'person' => [
                'id' => $this->person->id,
                'name' => $this->person->name,
                'image_url' => $this->person->image_url
            ],
            'role' => $this->role,
            'character_name' => $this->character_name,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'voice_description' => $this->voice_description,
            'is_primary' => $this->is_primary,
            'duration' => $this->end_time - $this->start_time
        ];
    }
}
```

#### Updated Model: `Episode`
```php
// Add to Episode model
public function voiceActors()
{
    return $this->hasMany(EpisodeVoiceActor::class)->orderBy('start_time');
}

public function primaryVoiceActor()
{
    return $this->hasOne(EpisodeVoiceActor::class)->where('is_primary', true);
}

public function voiceActorsInTimeRange(int $startTime, int $endTime)
{
    return $this->voiceActors()->inTimeRange($startTime, $endTime);
}

public function getVoiceActorForTime(int $timeInSeconds)
{
    return $this->voiceActors()
        ->where('start_time', '<=', $timeInSeconds)
        ->where('end_time', '>=', $timeInSeconds)
        ->first();
}
```

---

## 2. Timeline-Based Images Enhancement

### 2.1 Database Schema Updates

#### Updated Table: `image_timelines`
```sql
ALTER TABLE image_timelines ADD COLUMN voice_actor_id BIGINT UNSIGNED NULL AFTER episode_id;
ALTER TABLE image_timelines ADD COLUMN scene_description TEXT NULL AFTER image_url;
ALTER TABLE image_timelines ADD COLUMN transition_type VARCHAR(50) DEFAULT 'fade' AFTER scene_description;
ALTER TABLE image_timelines ADD COLUMN is_key_frame BOOLEAN DEFAULT FALSE AFTER transition_type;

ALTER TABLE image_timelines ADD FOREIGN KEY (voice_actor_id) REFERENCES episode_voice_actors(id) ON DELETE SET NULL;
ALTER TABLE image_timelines ADD INDEX idx_image_timelines_voice_actor_id (voice_actor_id);
```

### 2.2 Model Updates

#### Updated Model: `ImageTimeline`
```php
// Add to ImageTimeline model
protected $fillable = [
    'episode_id',
    'voice_actor_id', // NEW
    'start_time',
    'end_time',
    'image_url',
    'image_order',
    'scene_description', // NEW
    'transition_type', // NEW
    'is_key_frame' // NEW
];

protected $casts = [
    'start_time' => 'integer',
    'end_time' => 'integer',
    'image_order' => 'integer',
    'is_key_frame' => 'boolean'
];

/**
 * Get the voice actor associated with this image timeline
 */
public function voiceActor(): BelongsTo
{
    return $this->belongsTo(EpisodeVoiceActor::class);
}

/**
 * Scope for key frames
 */
public function scopeKeyFrames($query)
{
    return $query->where('is_key_frame', true);
}

/**
 * Get image timeline data for API response
 */
public function toApiResponse(): array
{
    return [
        'id' => $this->id,
        'start_time' => $this->start_time,
        'end_time' => $this->end_time,
        'image_url' => $this->image_url,
        'image_order' => $this->image_order,
        'scene_description' => $this->scene_description,
        'transition_type' => $this->transition_type,
        'is_key_frame' => $this->is_key_frame,
        'voice_actor' => $this->voiceActor ? $this->voiceActor->toApiResponse() : null
    ];
}
```

---

## 3. API Endpoints

### 3.1 Voice Actor Management Endpoints

#### Get Episode Voice Actors
```http
GET /api/v1/episodes/{episodeId}/voice-actors
```

**Response:**
```json
{
    "success": true,
    "message": "صداپیشگان قسمت دریافت شد",
    "data": {
        "episode_id": 1,
        "voice_actors": [
            {
                "id": 1,
                "person": {
                    "id": 5,
                    "name": "احمد رضایی",
                    "image_url": "https://example.com/images/ahmad.jpg"
                },
                "role": "narrator",
                "character_name": null,
                "start_time": 0,
                "end_time": 120,
                "voice_description": "صدای گرم و دوستانه",
                "is_primary": true,
                "duration": 120
            },
            {
                "id": 2,
                "person": {
                    "id": 6,
                    "name": "فاطمه احمدی",
                    "image_url": "https://example.com/images/fateme.jpg"
                },
                "role": "character",
                "character_name": "شاهزاده",
                "start_time": 120,
                "end_time": 300,
                "voice_description": "صدای نازک و کودکانه",
                "is_primary": false,
                "duration": 180
            }
        ],
        "total_duration": 300,
        "has_multiple_voice_actors": true
    }
}
```

#### Add Voice Actor to Episode
```http
POST /api/v1/episodes/{episodeId}/voice-actors
```

**Request Body:**
```json
{
    "person_id": 5,
    "role": "narrator",
    "character_name": null,
    "start_time": 0,
    "end_time": 120,
    "voice_description": "صدای گرم و دوستانه",
    "is_primary": true
}
```

#### Update Voice Actor
```http
PUT /api/v1/episodes/{episodeId}/voice-actors/{voiceActorId}
```

#### Delete Voice Actor
```http
DELETE /api/v1/episodes/{episodeId}/voice-actors/{voiceActorId}
```

#### Get Voice Actor for Specific Time
```http
GET /api/v1/episodes/{episodeId}/voice-actor-for-time?time=60
```

### 3.2 Enhanced Image Timeline Endpoints

#### Get Episode Image Timeline with Voice Actor Info
```http
GET /api/v1/episodes/{episodeId}/image-timeline?include_voice_actors=true
```

**Response:**
```json
{
    "success": true,
    "message": "تایم‌لاین تصاویر دریافت شد",
    "data": {
        "episode_id": 1,
        "image_timeline": [
            {
                "id": 1,
                "start_time": 0,
                "end_time": 45,
                "image_url": "https://example.com/images/scene1.jpg",
                "image_order": 1,
                "scene_description": "شروع داستان در جنگل",
                "transition_type": "fade",
                "is_key_frame": true,
                "voice_actor": {
                    "id": 1,
                    "person": {
                        "id": 5,
                        "name": "احمد رضایی"
                    },
                    "role": "narrator",
                    "character_name": null
                }
            },
            {
                "id": 2,
                "start_time": 46,
                "end_time": 90,
                "image_url": "https://example.com/images/scene2.jpg",
                "image_order": 2,
                "scene_description": "ملاقات با شاهزاده",
                "transition_type": "slide",
                "is_key_frame": false,
                "voice_actor": {
                    "id": 2,
                    "person": {
                        "id": 6,
                        "name": "فاطمه احمدی"
                    },
                    "role": "character",
                    "character_name": "شاهزاده"
                }
            }
        ]
    }
}
```

#### Create/Update Image Timeline with Voice Actor Association
```http
POST /api/v1/episodes/{episodeId}/image-timeline
```

**Request Body:**
```json
{
    "image_timeline": [
        {
            "start_time": 0,
            "end_time": 45,
            "image_url": "https://example.com/images/scene1.jpg",
            "image_order": 1,
            "scene_description": "شروع داستان در جنگل",
            "transition_type": "fade",
            "is_key_frame": true,
            "voice_actor_id": 1
        },
        {
            "start_time": 46,
            "end_time": 90,
            "image_url": "https://example.com/images/scene2.jpg",
            "image_order": 2,
            "scene_description": "ملاقات با شاهزاده",
            "transition_type": "slide",
            "is_key_frame": false,
            "voice_actor_id": 2
        }
    ]
}
```

---

## 4. Service Layer

### 4.1 Voice Actor Service

```php
<?php

namespace App\Services;

use App\Models\Episode;
use App\Models\EpisodeVoiceActor;
use App\Models\Person;
use Illuminate\Support\Facades\DB;

class EpisodeVoiceActorService
{
    /**
     * Add voice actor to episode
     */
    public function addVoiceActor(int $episodeId, array $data): array
    {
        try {
            DB::beginTransaction();

            $episode = Episode::findOrFail($episodeId);
            $person = Person::findOrFail($data['person_id']);

            // Validate time range
            $this->validateTimeRange($episodeId, $data['start_time'], $data['end_time']);

            $voiceActor = EpisodeVoiceActor::create([
                'episode_id' => $episodeId,
                'person_id' => $data['person_id'],
                'role' => $data['role'],
                'character_name' => $data['character_name'] ?? null,
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'voice_description' => $data['voice_description'] ?? null,
                'is_primary' => $data['is_primary'] ?? false
            ]);

            // Update episode voice actor count
            $this->updateEpisodeVoiceActorCount($episodeId);

            DB::commit();

            return [
                'success' => true,
                'message' => 'صداپیشه با موفقیت اضافه شد',
                'data' => $voiceActor->toApiResponse()
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'خطا در اضافه کردن صداپیشه: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get voice actors for episode
     */
    public function getVoiceActorsForEpisode(int $episodeId): array
    {
        $episode = Episode::findOrFail($episodeId);
        $voiceActors = $episode->voiceActors()->with('person')->get();

        return [
            'success' => true,
            'message' => 'صداپیشگان قسمت دریافت شد',
            'data' => [
                'episode_id' => $episodeId,
                'voice_actors' => $voiceActors->map->toApiResponse(),
                'total_duration' => $episode->duration,
                'has_multiple_voice_actors' => $episode->has_multiple_voice_actors
            ]
        ];
    }

    /**
     * Get voice actor for specific time
     */
    public function getVoiceActorForTime(int $episodeId, int $timeInSeconds): array
    {
        $episode = Episode::findOrFail($episodeId);
        $voiceActor = $episode->getVoiceActorForTime($timeInSeconds);

        if (!$voiceActor) {
            return [
                'success' => false,
                'message' => 'صداپیشه برای زمان مشخص شده یافت نشد'
            ];
        }

        return [
            'success' => true,
            'message' => 'صداپیشه برای زمان مشخص شده دریافت شد',
            'data' => $voiceActor->toApiResponse()
        ];
    }

    /**
     * Validate time range for voice actor
     */
    private function validateTimeRange(int $episodeId, int $startTime, int $endTime): void
    {
        $episode = Episode::findOrFail($episodeId);

        if ($startTime < 0 || $endTime > $episode->duration) {
            throw new \InvalidArgumentException('زمان شروع و پایان باید در محدوده مدت زمان قسمت باشد');
        }

        if ($startTime >= $endTime) {
            throw new \InvalidArgumentException('زمان شروع باید کمتر از زمان پایان باشد');
        }

        // Check for overlaps with existing voice actors
        $overlapping = EpisodeVoiceActor::where('episode_id', $episodeId)
            ->where(function($query) use ($startTime, $endTime) {
                $query->whereBetween('start_time', [$startTime, $endTime])
                      ->orWhereBetween('end_time', [$startTime, $endTime])
                      ->orWhere(function($q) use ($startTime, $endTime) {
                          $q->where('start_time', '<=', $startTime)
                            ->where('end_time', '>=', $endTime);
                      });
            })
            ->exists();

        if ($overlapping) {
            throw new \InvalidArgumentException('زمان مشخص شده با صداپیشه دیگری تداخل دارد');
        }
    }

    /**
     * Update episode voice actor count
     */
    private function updateEpisodeVoiceActorCount(int $episodeId): void
    {
        $count = EpisodeVoiceActor::where('episode_id', $episodeId)->count();
        $hasMultiple = $count > 1;

        Episode::where('id', $episodeId)->update([
            'voice_actor_count' => $count,
            'has_multiple_voice_actors' => $hasMultiple
        ]);
    }
}
```

### 4.2 Enhanced Image Timeline Service

```php
<?php

namespace App\Services;

use App\Models\Episode;
use App\Models\ImageTimeline;
use App\Models\EpisodeVoiceActor;
use Illuminate\Support\Facades\DB;

class ImageTimelineService
{
    /**
     * Save timeline with voice actor associations
     */
    public function saveTimelineWithVoiceActors(int $episodeId, array $timelineData): array
    {
        try {
            DB::beginTransaction();

            $episode = Episode::findOrFail($episodeId);

            // Validate timeline data
            $errors = ImageTimeline::validateTimeline($timelineData, $episode->duration);
            if (!empty($errors)) {
                return [
                    'success' => false,
                    'message' => 'خطا در اعتبارسنجی تایم‌لاین',
                    'errors' => $errors
                ];
            }

            // Delete existing timeline
            ImageTimeline::where('episode_id', $episodeId)->delete();

            // Create new timeline entries
            foreach ($timelineData as $index => $entry) {
                ImageTimeline::create([
                    'episode_id' => $episodeId,
                    'voice_actor_id' => $entry['voice_actor_id'] ?? null,
                    'start_time' => $entry['start_time'],
                    'end_time' => $entry['end_time'],
                    'image_url' => $entry['image_url'],
                    'image_order' => $index + 1,
                    'scene_description' => $entry['scene_description'] ?? null,
                    'transition_type' => $entry['transition_type'] ?? 'fade',
                    'is_key_frame' => $entry['is_key_frame'] ?? false
                ]);
            }

            // Update episode to use image timeline
            $episode->update(['use_image_timeline' => true]);

            DB::commit();

            return [
                'success' => true,
                'message' => 'تایم‌لاین تصاویر با موفقیت ذخیره شد',
                'data' => [
                    'episode_id' => $episodeId,
                    'timeline_count' => count($timelineData)
                ]
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'خطا در ذخیره تایم‌لاین: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get timeline with voice actor information
     */
    public function getTimelineWithVoiceActors(int $episodeId): array
    {
        $episode = Episode::findOrFail($episodeId);
        $timeline = $episode->imageTimelines()->with('voiceActor.person')->ordered()->get();

        return [
            'success' => true,
            'message' => 'تایم‌لاین تصاویر دریافت شد',
            'data' => [
                'episode_id' => $episodeId,
                'image_timeline' => $timeline->map->toApiResponse()
            ]
        ];
    }
}
```

---

## 5. Controller Updates

### 5.1 Episode Voice Actor Controller

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\EpisodeVoiceActorService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class EpisodeVoiceActorController extends Controller
{
    protected $voiceActorService;

    public function __construct(EpisodeVoiceActorService $voiceActorService)
    {
        $this->voiceActorService = $voiceActorService;
    }

    /**
     * Get voice actors for episode
     */
    public function getVoiceActors(int $episodeId): JsonResponse
    {
        $result = $this->voiceActorService->getVoiceActorsForEpisode($episodeId);
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Add voice actor to episode
     */
    public function addVoiceActor(Request $request, int $episodeId): JsonResponse
    {
        $request->validate([
            'person_id' => 'required|exists:people,id',
            'role' => 'required|string|max:100',
            'character_name' => 'nullable|string|max:255',
            'start_time' => 'required|integer|min:0',
            'end_time' => 'required|integer|min:1',
            'voice_description' => 'nullable|string|max:1000',
            'is_primary' => 'boolean'
        ], [
            'person_id.required' => 'انتخاب صداپیشه الزامی است',
            'person_id.exists' => 'صداپیشه انتخاب شده وجود ندارد',
            'role.required' => 'نقش صداپیشه الزامی است',
            'start_time.required' => 'زمان شروع الزامی است',
            'start_time.integer' => 'زمان شروع باید عدد باشد',
            'end_time.required' => 'زمان پایان الزامی است',
            'end_time.integer' => 'زمان پایان باید عدد باشد'
        ]);

        $result = $this->voiceActorService->addVoiceActor($episodeId, $request->all());
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Get voice actor for specific time
     */
    public function getVoiceActorForTime(Request $request, int $episodeId): JsonResponse
    {
        $request->validate([
            'time' => 'required|integer|min:0'
        ], [
            'time.required' => 'زمان الزامی است',
            'time.integer' => 'زمان باید عدد باشد'
        ]);

        $result = $this->voiceActorService->getVoiceActorForTime($episodeId, $request->time);
        return response()->json($result, $result['success'] ? 200 : 400);
    }
}
```

---

## 6. Migration Files

### 6.1 Create Episode Voice Actors Table

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('episode_voice_actors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('episode_id')->constrained()->onDelete('cascade');
            $table->foreignId('person_id')->constrained()->onDelete('cascade');
            $table->string('role', 100);
            $table->string('character_name', 255)->nullable();
            $table->unsignedInteger('start_time');
            $table->unsignedInteger('end_time');
            $table->text('voice_description')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->index(['episode_id']);
            $table->index(['person_id']);
            $table->index(['start_time', 'end_time']);
            $table->index(['role']);
            $table->unique(['episode_id', 'person_id', 'role', 'start_time'], 'unique_episode_person_role_time');
        });
    }

    public function down()
    {
        Schema::dropIfExists('episode_voice_actors');
    }
};
```

### 6.2 Update Episodes Table

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('episodes', function (Blueprint $table) {
            $table->boolean('has_multiple_voice_actors')->default(false)->after('use_image_timeline');
            $table->unsignedInteger('voice_actor_count')->default(0)->after('has_multiple_voice_actors');
        });
    }

    public function down()
    {
        Schema::table('episodes', function (Blueprint $table) {
            $table->dropColumn(['has_multiple_voice_actors', 'voice_actor_count']);
        });
    }
};
```

### 6.3 Update Image Timelines Table

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('image_timelines', function (Blueprint $table) {
            $table->foreignId('voice_actor_id')->nullable()->constrained('episode_voice_actors')->onDelete('set null')->after('episode_id');
            $table->text('scene_description')->nullable()->after('image_url');
            $table->string('transition_type', 50)->default('fade')->after('scene_description');
            $table->boolean('is_key_frame')->default(false)->after('transition_type');

            $table->index(['voice_actor_id']);
        });
    }

    public function down()
    {
        Schema::table('image_timelines', function (Blueprint $table) {
            $table->dropForeign(['voice_actor_id']);
            $table->dropIndex(['voice_actor_id']);
            $table->dropColumn(['voice_actor_id', 'scene_description', 'transition_type', 'is_key_frame']);
        });
    }
};
```

---

## 7. Route Updates

### 7.1 API Routes

```php
// Add to routes/api.php

// Voice Actor routes
Route::prefix('episodes')->middleware('auth:sanctum')->group(function () {
    Route::get('{episodeId}/voice-actors', [EpisodeVoiceActorController::class, 'getVoiceActors']);
    Route::post('{episodeId}/voice-actors', [EpisodeVoiceActorController::class, 'addVoiceActor']);
    Route::put('{episodeId}/voice-actors/{voiceActorId}', [EpisodeVoiceActorController::class, 'updateVoiceActor']);
    Route::delete('{episodeId}/voice-actors/{voiceActorId}', [EpisodeVoiceActorController::class, 'deleteVoiceActor']);
    Route::get('{episodeId}/voice-actor-for-time', [EpisodeVoiceActorController::class, 'getVoiceActorForTime']);
});

// Enhanced Image Timeline routes
Route::prefix('episodes')->middleware('auth:sanctum')->group(function () {
    Route::get('{episodeId}/image-timeline', [ImageTimelineController::class, 'getTimeline']);
    Route::post('{episodeId}/image-timeline', [ImageTimelineController::class, 'saveTimeline']);
    Route::get('{episodeId}/image-for-time', [ImageTimelineController::class, 'getImageForTime']);
});
```

---

## 8. Admin Dashboard Updates

### 8.1 Voice Actor Management Interface

The admin dashboard should include:

1. **Voice Actor Assignment Interface**
   - Drag-and-drop timeline editor
   - Voice actor selection dropdown
   - Character name input
   - Time range picker
   - Voice description textarea

2. **Timeline Visualization**
   - Visual timeline showing all voice actors
   - Color-coded segments for different voice actors
   - Overlap detection and warnings

3. **Image Timeline Enhancement**
   - Voice actor association for each image segment
   - Scene description input
   - Transition type selection
   - Key frame marking

### 8.2 Admin Routes

```php
// Add to routes/web.php (Admin section)

// Voice Actor Management
Route::prefix('episodes/{episode}/voice-actors')->name('episodes.voice-actors.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\EpisodeVoiceActorController::class, 'index'])->name('index');
    Route::post('/', [\App\Http\Controllers\Admin\EpisodeVoiceActorController::class, 'store'])->name('store');
    Route::put('/{voiceActor}', [\App\Http\Controllers\Admin\EpisodeVoiceActorController::class, 'update'])->name('update');
    Route::delete('/{voiceActor}', [\App\Http\Controllers\Admin\EpisodeVoiceActorController::class, 'destroy'])->name('destroy');
});
```

---

## 9. Testing Examples

### 9.1 Voice Actor Testing

```php
// Test adding voice actor to episode
public function test_can_add_voice_actor_to_episode()
{
    $episode = Episode::factory()->create(['duration' => 300]);
    $person = Person::factory()->create();

    $response = $this->postJson("/api/v1/episodes/{$episode->id}/voice-actors", [
        'person_id' => $person->id,
        'role' => 'narrator',
        'start_time' => 0,
        'end_time' => 120,
        'is_primary' => true
    ]);

    $response->assertStatus(200)
             ->assertJson(['success' => true]);

    $this->assertDatabaseHas('episode_voice_actors', [
        'episode_id' => $episode->id,
        'person_id' => $person->id,
        'role' => 'narrator'
    ]);
}
```

### 9.2 Timeline Testing

```php
// Test timeline with voice actor association
public function test_can_create_timeline_with_voice_actors()
{
    $episode = Episode::factory()->create(['duration' => 300]);
    $voiceActor = EpisodeVoiceActor::factory()->create(['episode_id' => $episode->id]);

    $timelineData = [
        [
            'start_time' => 0,
            'end_time' => 45,
            'image_url' => 'https://example.com/image1.jpg',
            'voice_actor_id' => $voiceActor->id,
            'scene_description' => 'شروع داستان'
        ]
    ];

    $response = $this->postJson("/api/v1/episodes/{$episode->id}/image-timeline", [
        'image_timeline' => $timelineData
    ]);

    $response->assertStatus(200)
             ->assertJson(['success' => true]);
}
```

---

## 10. Performance Considerations

### 10.1 Database Indexing
- Index on `episode_voice_actors(episode_id, start_time, end_time)` for time-based queries
- Index on `image_timelines(episode_id, voice_actor_id)` for voice actor associations

### 10.2 Caching Strategy
- Cache voice actor data for episodes
- Cache timeline data with voice actor information
- Use Redis for frequently accessed voice actor mappings

### 10.3 API Optimization
- Eager load voice actor relationships
- Use database-level filtering for time-based queries
- Implement pagination for large voice actor lists

---

## 11. Security Considerations

### 11.1 Access Control
- Only authenticated users can access voice actor endpoints
- Admin-only access for voice actor management
- Rate limiting on voice actor creation endpoints

### 11.2 Data Validation
- Validate time ranges against episode duration
- Prevent overlapping voice actor assignments
- Sanitize voice descriptions and character names

### 11.3 File Security
- Validate image URLs for timeline images
- Implement image upload restrictions
- Use secure file storage for voice actor assets

---

## 12. Future Enhancements

### 12.1 Advanced Features
- Voice actor performance analytics
- Character voice consistency tracking
- Automated voice actor recommendation system
- Voice actor collaboration tools

### 12.2 Mobile App Integration
- Voice actor information in mobile app
- Character identification during playback
- Voice actor switching interface
- Enhanced timeline visualization

---

This documentation provides a comprehensive guide for implementing multiple voice actors per episode and enhanced timeline-based images. The implementation maintains backward compatibility while adding powerful new features for content creators and users.
