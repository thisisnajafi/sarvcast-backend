# Voice Actor Notifications Documentation

## Overview
This document lists all notifications that should be sent to voice actors (narrators and character voice actors) with their corresponding deep links and triggers.

## Notification Categories

### 1. Assignment Notifications

#### 1.1 Story Narrator Assignment
- **Trigger**: When a user is assigned as narrator to a story (`narrator_id` is set/updated in `stories` table)
- **Location**: `StoryController::store()`, `update()`, `assignNarrator()` (if exists)
- **Recipient**: The assigned user (voice actor)
- **Channels**: In-app, Push, Email
- **Deep Link**: `/stories/{story_id}`
- **Data Payload**:
  ```json
  {
    "type": "voice_actor_assignment",
    "assignment_type": "story_narrator",
    "story_id": 123,
    "story_title": "داستان زیبا",
    "action": "assigned"
  }
  ```
- **Message**: "شما به عنوان راوی داستان «{story_title}» انتخاب شده‌اید."

---

#### 1.2 Character Voice Actor Assignment
- **Trigger**: When a user is assigned as voice actor to a character (`voice_actor_id` is set/updated in `characters` table)
- **Location**: `CharacterController::assignVoiceActor()`, `store()`, `update()`
- **Recipient**: The assigned user (voice actor)
- **Channels**: In-app, Push, Email
- **Deep Link**: `/stories/{story_id}/characters/{character_id}`
- **Data Payload**:
  ```json
  {
    "type": "voice_actor_assignment",
    "assignment_type": "character",
    "story_id": 123,
    "character_id": 456,
    "character_name": "علی",
    "story_title": "داستان زیبا",
    "action": "assigned"
  }
  ```
- **Message**: "شما به عنوان صداپیشه شخصیت «{character_name}» در داستان «{story_title}» انتخاب شده‌اید."

---

#### 1.3 Episode Voice Actor Assignment
- **Trigger**: When a person is assigned as voice actor to an episode (`episode_voice_actors` table entry created)
- **Location**: `EpisodeVoiceActorController::store()`, `EpisodeVoiceActorService::addVoiceActor()`
- **Recipient**: The assigned person (if they have a user account)
- **Channels**: In-app, Push, Email
- **Deep Link**: `/episodes/{episode_id}?voice_actor={voice_actor_id}`
- **Data Payload**:
  ```json
  {
    "type": "voice_actor_assignment",
    "assignment_type": "episode_voice_actor",
    "episode_id": 789,
    "episode_title": "قسمت 1",
    "story_id": 123,
    "story_title": "داستان زیبا",
    "role": "narrator",
    "character_name": "علی",
    "start_time": 0,
    "end_time": 300,
    "action": "assigned"
  }
  ```
- **Message**: "شما به عنوان صداپیشه در قسمت «{episode_title}» از داستان «{story_title}» انتخاب شده‌اید. نقش: {role}"

---

#### 1.4 Episode Narrator Assignment
- **Trigger**: When `narrator_id` is set/updated on an episode
- **Location**: `EpisodeController::store()`, `update()`, `changeNarrator()` (if exists)
- **Recipient**: The assigned narrator (user)
- **Channels**: In-app, Push, Email
- **Deep Link**: `/episodes/{episode_id}`
- **Data Payload**:
  ```json
  {
    "type": "voice_actor_assignment",
    "assignment_type": "episode_narrator",
    "episode_id": 789,
    "episode_title": "قسمت 1",
    "story_id": 123,
    "story_title": "داستان زیبا",
    "action": "assigned"
  }
  ```
- **Message**: "شما به عنوان راوی قسمت «{episode_title}» از داستان «{story_title}» انتخاب شده‌اید."

---

### 2. Assignment Removal Notifications

#### 2.1 Story Narrator Removed
- **Trigger**: When `narrator_id` is removed from a story (set to NULL)
- **Location**: `StoryController::update()`
- **Recipient**: The previously assigned narrator
- **Channels**: In-app, Push, Email
- **Deep Link**: `/stories/{story_id}`
- **Data Payload**:
  ```json
  {
    "type": "voice_actor_assignment",
    "assignment_type": "story_narrator",
    "story_id": 123,
    "story_title": "داستان زیبا",
    "action": "removed"
  }
  ```
- **Message**: "شما از نقش راوی داستان «{story_title}» حذف شده‌اید."

---

#### 2.2 Character Voice Actor Removed
- **Trigger**: When `voice_actor_id` is removed from a character (set to NULL)
- **Location**: `CharacterController::update()`, `assignVoiceActor()` (when setting to null)
- **Recipient**: The previously assigned voice actor
- **Channels**: In-app, Push, Email
- **Deep Link**: `/stories/{story_id}/characters/{character_id}`
- **Data Payload**:
  ```json
  {
    "type": "voice_actor_assignment",
    "assignment_type": "character",
    "story_id": 123,
    "character_id": 456,
    "character_name": "علی",
    "story_title": "داستان زیبا",
    "action": "removed"
  }
  ```
- **Message**: "شما از نقش صداپیشه شخصیت «{character_name}» در داستان «{story_title}» حذف شده‌اید."

---

#### 2.3 Episode Voice Actor Removed
- **Trigger**: When a voice actor is removed from an episode (`episode_voice_actors` entry deleted)
- **Location**: `EpisodeVoiceActorController::destroy()`, `EpisodeVoiceActorService::removeVoiceActor()`
- **Recipient**: The removed voice actor
- **Channels**: In-app, Push, Email
- **Deep Link**: `/episodes/{episode_id}`
- **Data Payload**:
  ```json
  {
    "type": "voice_actor_assignment",
    "assignment_type": "episode_voice_actor",
    "episode_id": 789,
    "episode_title": "قسمت 1",
    "story_id": 123,
    "story_title": "داستان زیبا",
    "action": "removed"
  }
  ```
- **Message**: "شما از نقش صداپیشه در قسمت «{episode_title}» از داستان «{story_title}» حذف شده‌اید."

---

### 3. Workflow Status Notifications

#### 3.1 Story Workflow Status Changed
- **Trigger**: When `workflow_status` changes on a story
- **Location**: `StoryController::updateWorkflowStatus()`
- **Recipients**: 
  - Story narrator (if assigned)
  - All character voice actors (if assigned)
- **Channels**: In-app, Push, Email
- **Deep Link**: `/stories/{story_id}?tab=workflow`
- **Data Payload**:
  ```json
  {
    "type": "workflow_status_change",
    "story_id": 123,
    "story_title": "داستان زیبا",
    "old_status": "written",
    "new_status": "characters_made",
    "status_label": "شخصیت‌ها ساخته شده"
  }
  ```
- **Message**: "وضعیت داستان «{story_title}» به «{status_label}» تغییر کرد."

**Status Transitions**:
- `written` → `characters_made`: "شخصیت‌ها برای داستان «{story_title}» ساخته شده‌اند. آماده ضبط هستید."
- `characters_made` → `recorded`: "ضبط داستان «{story_title}» شروع شده است."
- `recorded` → `timeline_created`: "تایم‌لاین برای داستان «{story_title}» ایجاد شده است."
- `timeline_created` → `published`: "داستان «{story_title}» منتشر شد! 🎉"

---

### 4. Content Published Notifications

#### 4.1 Story Published (for Narrator)
- **Trigger**: When a story is published and user is the narrator
- **Location**: `StoryController::publish()`, `update()` (when status changes to published)
- **Recipient**: Story narrator
- **Channels**: In-app, Push, Email
- **Deep Link**: `/stories/{story_id}`
- **Data Payload**:
  ```json
  {
    "type": "content_published",
    "content_type": "story",
    "story_id": 123,
    "story_title": "داستان زیبا",
    "role": "narrator"
  }
  ```
- **Message**: "داستان «{story_title}» که شما راوی آن هستید منتشر شد! 🎉"

---

#### 4.2 Story Published (for Character Voice Actors)
- **Trigger**: When a story is published and user is a character voice actor
- **Location**: `StoryController::publish()`, `update()` (when status changes to published)
- **Recipients**: All character voice actors assigned to the story
- **Channels**: In-app, Push, Email
- **Deep Link**: `/stories/{story_id}`
- **Data Payload**:
  ```json
  {
    "type": "content_published",
    "content_type": "story",
    "story_id": 123,
    "story_title": "داستان زیبا",
    "character_id": 456,
    "character_name": "علی",
    "role": "character_voice_actor"
  }
  ```
- **Message**: "داستان «{story_title}» که شما صداپیشه شخصیت «{character_name}» در آن هستید منتشر شد! 🎉"

---

#### 4.3 Episode Published (for Narrator)
- **Trigger**: When an episode is published and user is the narrator
- **Location**: `EpisodeController::publish()`, `store()`, `update()` (when status changes to published)
- **Recipient**: Episode narrator
- **Channels**: In-app, Push, Email
- **Deep Link**: `/episodes/{episode_id}`
- **Data Payload**:
  ```json
  {
    "type": "content_published",
    "content_type": "episode",
    "episode_id": 789,
    "episode_title": "قسمت 1",
    "story_id": 123,
    "story_title": "داستان زیبا",
    "role": "narrator"
  }
  ```
- **Message**: "قسمت «{episode_title}» از داستان «{story_title}» که شما راوی آن هستید منتشر شد! 🎉"

---

#### 4.4 Episode Published (for Voice Actors)
- **Trigger**: When an episode is published and user is a voice actor in it
- **Location**: `EpisodeController::publish()`, `store()`, `update()` (when status changes to published)
- **Recipients**: All voice actors assigned to the episode
- **Channels**: In-app, Push, Email
- **Deep Link**: `/episodes/{episode_id}?voice_actor={voice_actor_id}`
- **Data Payload**:
  ```json
  {
    "type": "content_published",
    "content_type": "episode",
    "episode_id": 789,
    "episode_title": "قسمت 1",
    "story_id": 123,
    "story_title": "داستان زیبا",
    "role": "voice_actor",
    "character_name": "علی",
    "start_time": 0,
    "end_time": 300
  }
  ```
- **Message**: "قسمت «{episode_title}» از داستان «{story_title}» که شما در آن صداپیشه هستید منتشر شد! 🎉"

---

### 5. Script & Content Ready Notifications

#### 5.1 Story Script Uploaded
- **Trigger**: When a script file is uploaded for a story
- **Location**: `StoryController::uploadScript()`, `store()`, `update()` (when script_file is uploaded)
- **Recipients**: 
  - Story narrator (if assigned)
  - All character voice actors (if assigned)
- **Channels**: In-app, Push, Email
- **Deep Link**: `/stories/{story_id}?tab=script`
- **Data Payload**:
  ```json
  {
    "type": "script_ready",
    "content_type": "story",
    "story_id": 123,
    "story_title": "داستان زیبا",
    "script_url": "/scripts/stories/123/script.md"
  }
  ```
- **Message**: "فیلمنامه داستان «{story_title}» آماده است. می‌توانید آن را مطالعه کنید."

---

#### 5.2 Episode Script Uploaded
- **Trigger**: When a script file is uploaded for an episode
- **Location**: `EpisodeController::store()`, `update()` (when script_file_url is set)
- **Recipients**: 
  - Episode narrator (if assigned)
  - All episode voice actors (if assigned)
- **Channels**: In-app, Push, Email
- **Deep Link**: `/episodes/{episode_id}?tab=script`
- **Data Payload**:
  ```json
  {
    "type": "script_ready",
    "content_type": "episode",
    "episode_id": 789,
    "episode_title": "قسمت 1",
    "story_id": 123,
    "story_title": "داستان زیبا",
    "script_url": "/scripts/episodes/789/script.md"
  }
  ```
- **Message**: "فیلمنامه قسمت «{episode_title}» از داستان «{story_title}» آماده است."

---

#### 5.3 Episode Ready for Recording
- **Trigger**: When episode workflow status indicates it's ready for recording (e.g., script uploaded, characters assigned)
- **Location**: `EpisodeController::update()` (when all prerequisites are met)
- **Recipients**: 
  - Episode narrator (if assigned)
  - All episode voice actors (if assigned)
- **Channels**: In-app, Push, Email
- **Deep Link**: `/episodes/{episode_id}?tab=recording`
- **Data Payload**:
  ```json
  {
    "type": "recording_ready",
    "episode_id": 789,
    "episode_title": "قسمت 1",
    "story_id": 123,
    "story_title": "داستان زیبا",
    "deadline": "2025-02-15",
    "script_url": "/scripts/episodes/789/script.md"
  }
  ```
- **Message**: "قسمت «{episode_title}» از داستان «{story_title}» آماده ضبط است. مهلت: {deadline}"

---

### 6. Deadline & Reminder Notifications

#### 6.1 Recording Deadline Approaching
- **Trigger**: Scheduled job that checks for approaching deadlines (e.g., 3 days before, 1 day before)
- **Location**: Scheduled job (cron)
- **Recipients**: Voice actors with upcoming deadlines
- **Channels**: In-app, Push, Email, SMS
- **Deep Link**: `/episodes/{episode_id}?tab=recording`
- **Data Payload**:
  ```json
  {
    "type": "deadline_reminder",
    "episode_id": 789,
    "episode_title": "قسمت 1",
    "story_id": 123,
    "story_title": "داستان زیبا",
    "deadline": "2025-02-15",
    "days_remaining": 3
  }
  ```
- **Message**: "یادآوری: مهلت ضبط قسمت «{episode_title}» از داستان «{story_title}» {days_remaining} روز دیگر است."

---

#### 6.2 Recording Deadline Passed
- **Trigger**: Scheduled job that checks for passed deadlines
- **Location**: Scheduled job (cron)
- **Recipients**: Voice actors with passed deadlines
- **Channels**: In-app, Push, Email
- **Deep Link**: `/episodes/{episode_id}?tab=recording`
- **Data Payload**:
  ```json
  {
    "type": "deadline_passed",
    "episode_id": 789,
    "episode_title": "قسمت 1",
    "story_id": 123,
    "story_title": "داستان زیبا",
    "deadline": "2025-02-15",
    "days_overdue": 2
  }
  ```
- **Message**: "⚠️ مهلت ضبط قسمت «{episode_title}» از داستان «{story_title}» {days_overdue} روز گذشته است. لطفاً هرچه سریع‌تر اقدام کنید."

---

### 7. Feedback & Review Notifications

#### 7.1 Admin Feedback on Recording
- **Trigger**: When admin provides feedback/review on a voice actor's work
- **Location**: Admin feedback endpoint (to be created)
- **Recipient**: The voice actor who received feedback
- **Channels**: In-app, Push, Email
- **Deep Link**: `/episodes/{episode_id}?tab=feedback`
- **Data Payload**:
  ```json
  {
    "type": "feedback",
    "feedback_type": "recording",
    "episode_id": 789,
    "episode_title": "قسمت 1",
    "story_id": 123,
    "story_title": "داستان زیبا",
    "feedback_text": "ضبط شما عالی بود اما...",
    "rating": 4.5
  }
  ```
- **Message**: "بازخورد جدید برای ضبط شما در قسمت «{episode_title}» دریافت شد."

---

#### 7.2 Recording Approved
- **Trigger**: When admin approves a voice actor's recording
- **Location**: Admin approval endpoint (to be created)
- **Recipient**: The voice actor
- **Channels**: In-app, Push, Email
- **Deep Link**: `/episodes/{episode_id}`
- **Data Payload**:
  ```json
  {
    "type": "recording_approved",
    "episode_id": 789,
    "episode_title": "قسمت 1",
    "story_id": 123,
    "story_title": "داستان زیبا"
  }
  ```
- **Message**: "✅ ضبط شما در قسمت «{episode_title}» تایید شد!"

---

#### 7.3 Recording Rejected (Needs Revision)
- **Trigger**: When admin rejects a voice actor's recording and requests revision
- **Location**: Admin rejection endpoint (to be created)
- **Recipient**: The voice actor
- **Channels**: In-app, Push, Email
- **Deep Link**: `/episodes/{episode_id}?tab=feedback`
- **Data Payload**:
  ```json
  {
    "type": "recording_rejected",
    "episode_id": 789,
    "episode_title": "قسمت 1",
    "story_id": 123,
    "story_title": "داستان زیبا",
    "rejection_reason": "کیفیت صدا مناسب نیست",
    "revision_required": true
  }
  ```
- **Message**: "❌ ضبط شما در قسمت «{episode_title}» نیاز به بازبینی دارد. دلیل: {rejection_reason}"

---

### 8. Assignment Update Notifications

#### 8.1 Episode Voice Actor Time Range Updated
- **Trigger**: When time range (start_time, end_time) is updated for an episode voice actor
- **Location**: `EpisodeVoiceActorController::update()`, `EpisodeVoiceActorService::updateVoiceActor()`
- **Recipient**: The voice actor
- **Channels**: In-app, Push
- **Deep Link**: `/episodes/{episode_id}?voice_actor={voice_actor_id}`
- **Data Payload**:
  ```json
  {
    "type": "assignment_updated",
    "assignment_type": "episode_voice_actor",
    "episode_id": 789,
    "episode_title": "قسمت 1",
    "story_id": 123,
    "story_title": "داستان زیبا",
    "old_start_time": 0,
    "old_end_time": 300,
    "new_start_time": 60,
    "new_end_time": 360
  }
  ```
- **Message**: "بازه زمانی شما در قسمت «{episode_title}» به‌روزرسانی شد: {new_start_time} تا {new_end_time} ثانیه"

---

#### 8.2 Character Assignment Updated
- **Trigger**: When character details are updated (name, description, etc.)
- **Location**: `CharacterController::update()`
- **Recipient**: Character voice actor (if assigned)
- **Channels**: In-app, Push
- **Deep Link**: `/stories/{story_id}/characters/{character_id}`
- **Data Payload**:
  ```json
  {
    "type": "assignment_updated",
    "assignment_type": "character",
    "story_id": 123,
    "character_id": 456,
    "character_name": "علی",
    "story_title": "داستان زیبا"
  }
  ```
- **Message**: "اطلاعات شخصیت «{character_name}» که شما صداپیشه آن هستید به‌روزرسانی شد."

---

## Implementation Priority

### High Priority (Must Implement)
1. Story Narrator Assignment
2. Character Voice Actor Assignment
3. Episode Voice Actor Assignment
4. Story Workflow Status Changed
5. Story Published (for Narrator/Voice Actors)
6. Episode Published (for Narrator/Voice Actors)
7. Script Uploaded (Story/Episode)

### Medium Priority (Should Implement)
8. Assignment Removed (all types)
9. Recording Deadline Approaching
10. Recording Approved/Rejected

### Low Priority (Nice to Have)
11. Episode Ready for Recording
12. Assignment Updated
13. Recording Deadline Passed

## Deep Link Routes (Flutter)

All deep links should be handled in `PushNotificationService`:

```dart
case 'voice_actor_assignment':
case 'workflow_status_change':
case 'content_published':
case 'script_ready':
case 'recording_ready':
case 'deadline_reminder':
case 'deadline_passed':
case 'feedback':
case 'recording_approved':
case 'recording_rejected':
case 'assignment_updated':
  // Navigate based on content_type and IDs
  if (data['story_id'] != null) {
    navigatorKey.currentState?.pushNamed('/story/${data['story_id']}');
  }
  if (data['episode_id'] != null) {
    navigatorKey.currentState?.pushNamed('/episode/${data['episode_id']}');
  }
  break;
```

## Notification Service Methods Needed

Add these methods to `NotificationService`:

```php
// Voice Actor Assignment Notifications
public function sendVoiceActorAssignmentNotification(User $user, string $assignmentType, array $data): bool
public function sendVoiceActorRemovalNotification(User $user, string $assignmentType, array $data): bool

// Workflow Notifications
public function sendWorkflowStatusChangeNotification(User $user, Story $story, string $oldStatus, string $newStatus): bool

// Content Published Notifications
public function sendContentPublishedNotification(User $user, string $contentType, array $data): bool

// Script & Recording Notifications
public function sendScriptReadyNotification(User $user, string $contentType, array $data): bool
public function sendRecordingReadyNotification(User $user, Episode $episode, ?string $deadline = null): bool

// Deadline Notifications
public function sendDeadlineReminderNotification(User $user, Episode $episode, int $daysRemaining): bool
public function sendDeadlinePassedNotification(User $user, Episode $episode, int $daysOverdue): bool

// Feedback Notifications
public function sendFeedbackNotification(User $user, string $feedbackType, array $data): bool
public function sendRecordingApprovedNotification(User $user, Episode $episode): bool
public function sendRecordingRejectedNotification(User $user, Episode $episode, string $reason): bool

// Assignment Update Notifications
public function sendAssignmentUpdatedNotification(User $user, string $assignmentType, array $data): bool
```

---

**Last Updated**: 2025-01-27
**Total Notifications**: 23 unique notification types for voice actors

