# Audio Files in Public Directory

## Overview

Audio files for episodes are now stored directly in `public/audio/episodes/` instead of `storage/app/public/audio/episodes/`. This makes them directly accessible via the web without requiring a storage symlink.

## Changes Made

### 1. EpisodeController (`app/Http/Controllers/Admin/EpisodeController.php`)
- ✅ Updated `store()` method to save files to `public/audio/episodes/`
- ✅ Updated `update()` method to save files to `public/audio/episodes/`
- ✅ Updated `destroy()` method to delete files from `public/audio/episodes/`
- ✅ Updated `bulkAction()` method to delete files from `public/audio/episodes/`
- ✅ All file operations now use `public_path()` instead of `storage_path('app/public/')`

### 2. AudioController (`app/Http/Controllers/AudioController.php`)
- ✅ Updated to look for files in `public/audio/episodes/`
- ✅ Uses `public_path()` instead of `storage_path('app/public/')`

### 3. Episode Model (`app/Models/Episode.php`)
- ✅ Updated `getAudioUrlAttribute()` to generate URLs pointing to `/audio/episodes/`
- ✅ Simplified URL generation (no longer needs storage symlink)

### 4. AudioManagementController (`app/Http/Controllers/Admin/AudioManagementController.php`)
- ✅ Updated `store()` method to save files to `public/audio/episodes/`

## File Structure

```
public/
  └── audio/
      └── episodes/
          ├── audio_OrD3ybUN2v9BVAQi7lwz8Jcmph5GWxTR-2025-12-18_13-10-28.mp3
          └── ...
```

## URL Format

Audio files are accessible at:
- Direct URL: `https://my.sarvcast.ir/audio/episodes/filename.mp3`
- Via AudioController: `https://my.sarvcast.ir/audio/episodes/filename.mp3` (with proper MIME types)

## Database Storage

The `audio_url` field in the `episodes` table stores:
- Format: `audio/episodes/filename.mp3`
- Relative to `public/` directory

## Migration Notes

### Existing Files

If you have existing audio files in `storage/app/public/audio/episodes/`, you should:

1. **Move existing files:**
   ```bash
   mkdir -p public/audio/episodes
   mv storage/app/public/audio/episodes/* public/audio/episodes/
   ```

2. **Update database (if needed):**
   The database paths should already be correct (`audio/episodes/filename.mp3`), but verify:
   ```sql
   SELECT id, audio_url FROM episodes WHERE audio_url IS NOT NULL LIMIT 5;
   ```

3. **Set permissions:**
   ```bash
   chmod -R 755 public/audio/episodes
   chown -R www-data:www-data public/audio/episodes
   ```

## Benefits

1. **No Storage Symlink Required**: Files are directly accessible
2. **Simpler Setup**: No need to run `php artisan storage:link`
3. **Direct Access**: Files can be accessed directly via web server
4. **Better Performance**: No symlink resolution overhead

## Security Considerations

- Files in `public/` are directly accessible via web
- The AudioController route (`/audio/episodes/{path}`) provides additional security and proper MIME types
- Only files in `audio/episodes/` directory are served

## Testing

1. **Upload a new episode with audio:**
   - Go to admin panel → Episodes → Create
   - Upload an audio file
   - Verify it's saved in `public/audio/episodes/`

2. **Test audio playback:**
   - Create a timeline for an episode
   - Select the episode
   - Verify audio plays correctly

3. **Verify file access:**
   ```bash
   ls -la public/audio/episodes/
   curl -I https://your-domain.com/audio/episodes/filename.mp3
   ```

## Troubleshooting

### Files Not Accessible

1. **Check directory exists:**
   ```bash
   ls -la public/audio/episodes/
   ```

2. **Check permissions:**
   ```bash
   chmod -R 755 public/audio/episodes
   ```

3. **Check web server configuration:**
   - Ensure web server can read files in `public/` directory
   - Check `.htaccess` or nginx configuration

### Old Files Still in Storage

If old files are still in `storage/app/public/audio/episodes/`:
- They won't be accessible via the new system
- Move them to `public/audio/episodes/` if needed
- Or delete them if no longer needed

## Related Files

- `app/Http/Controllers/Admin/EpisodeController.php` - Episode CRUD operations
- `app/Http/Controllers/AudioController.php` - Audio file serving
- `app/Models/Episode.php` - Episode model with audio URL accessor
- `routes/web.php` - Audio serving route

