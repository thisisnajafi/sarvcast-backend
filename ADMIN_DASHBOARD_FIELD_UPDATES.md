# Admin Dashboard Field Updates - Implementation Guide

## Overview
This document outlines all the changes needed to update the admin dashboard forms for creating stories, episodes, and assigning users to story characters, narrators, and authors.

## Changes Summary

### 1. Story Creation Form (`admin/stories/create`)

#### Current Issues:
- **Author (author_id)**: Currently uses `people` table, should use `users` table
- **Narrator (narrator_id)**: Currently uses `people` table, should use `users` table  
- **Script File**: Missing `script_file_url` field for uploading story scripts
- **Workflow Status**: Missing `workflow_status` field

#### Required Changes:

1. **Author Field (author_id)**
   - Change from `Person` model to `User` model
   - Filter users by roles: `voice_actor`, `admin`, `super_admin`
   - Update dropdown to show user names (first_name + last_name)
   - Update validation in `StoryController@store` to validate against `users` table

2. **Narrator Field (narrator_id)**
   - Change from `Person` model to `User` model
   - Filter users by roles: `voice_actor`, `admin`, `super_admin`
   - Update dropdown to show user names (first_name + last_name)
   - Update validation in `StoryController@store` to validate against `users` table

3. **Script File Upload (script_file_url)**
   - Add file upload field for script files (markdown/text files)
   - Accept formats: `.md`, `.txt`, `.doc`, `.docx`
   - Store in `public/scripts/stories/` directory
   - Update validation to accept script file uploads
   - Max file size: 10MB

4. **Workflow Status (workflow_status)**
   - Add dropdown field with options:
     - `written` - نوشته شده
     - `characters_made` - شخصیت‌ها ساخته شده
     - `recorded` - ضبط شده
     - `timeline_created` - تایم‌لاین ایجاد شده
     - `published` - منتشر شده
   - Default value: `written`
   - Make it nullable/optional

#### Files to Update:
- `resources/views/admin/stories/create.blade.php`
- `app/Http/Controllers/Admin/StoryController.php` (create method and store method)
- `app/Models/Story.php` (validation rules if needed)

---

### 2. Episode Creation Form (`admin/episodes/create`)

#### Current Issues:
- **Narrator (narrator_id)**: Currently uses `people` table, should use `users` table
- **Script File**: Missing `script_file_url` field for uploading episode scripts

#### Required Changes:

1. **Narrator Field (narrator_id)**
   - Change from `Person` model to `User` model
   - Filter users by roles: `voice_actor`, `admin`, `super_admin`
   - Update dropdown to show user names (first_name + last_name)
   - Update validation in `EpisodeController@store` to validate against `users` table

2. **Script File Upload (script_file_url)**
   - Add file upload field for script files (markdown/text files)
   - Accept formats: `.md`, `.txt`, `.doc`, `.docx`
   - Store in `public/scripts/episodes/` directory
   - Update validation to accept script file uploads
   - Max file size: 10MB

#### Files to Update:
- `resources/views/admin/episodes/create.blade.php`
- `app/Http/Controllers/Admin/EpisodeController.php` (create method and store method)
- `app/Models/Episode.php` (validation rules if needed)

---

### 3. Character Voice Actor Assignment

#### Current Status:
- Character model already has `voice_actor_id` that references `users` table ✅
- API endpoint exists for assigning voice actors ✅

#### Required Changes:

1. **Admin Dashboard UI**
   - Ensure character management interface allows selecting users
   - Filter users by roles: `voice_actor`, `admin`, `super_admin`
   - Show user full name (first_name + last_name) in dropdowns
   - Add search/filter functionality for finding users

2. **Validation**
   - Ensure validation checks user roles correctly
   - Only allow users with roles: `voice_actor`, `admin`, `super_admin`

#### Files to Check/Update:
- Character management views (if exists in admin panel)
- `app/Http/Controllers/Api/CharacterController.php` (already has assignVoiceActor method)
- Ensure admin dashboard has UI for character management

---

## Database Schema Reference

### Stories Table
- `author_id` → References `users.id` (NOT `people.id`)
- `narrator_id` → References `users.id` (NOT `people.id`)
- `script_file_url` → VARCHAR(500), nullable
- `workflow_status` → ENUM or VARCHAR, nullable

### Episodes Table
- `narrator_id` → Should reference `users.id` (check if migration exists)
- `script_file_url` → VARCHAR(500), nullable

### Characters Table
- `voice_actor_id` → References `users.id` ✅ (already correct)

---

## Implementation Priority

1. **High Priority:**
   - Update Story form: author_id and narrator_id to use users
   - Update Episode form: narrator_id to use users
   - Add script_file_url to both forms

2. **Medium Priority:**
   - Add workflow_status to Story form
   - Update validation rules in controllers

3. **Low Priority:**
   - Enhance character assignment UI (if not already done)
   - Add search/filter for user selection

---

## User Role Requirements

For author, narrator, and voice actor assignments, only users with these roles should be selectable:
- `voice_actor` (ROLE_VOICE_ACTOR)
- `admin` (ROLE_ADMIN)
- `super_admin` (ROLE_SUPER_ADMIN)

---

## File Upload Directories

- Story Scripts: `public/scripts/stories/`
- Episode Scripts: `public/scripts/episodes/`

Ensure these directories exist and are writable.

---

## Notes

- All changes should maintain backward compatibility where possible
- Update validation messages to be in Persian (Farsi)
- Ensure proper error handling for file uploads
- Add proper file type validation
- Consider file size limits (10MB for scripts)

