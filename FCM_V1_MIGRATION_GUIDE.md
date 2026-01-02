# Firebase Cloud Messaging (FCM) v1 API Migration Guide

## Overview

Firebase Cloud Messaging Legacy API has been **deprecated** and will be removed. This guide shows you how to migrate to **FCM HTTP v1 API**.

## Key Differences

| Feature | Legacy API | FCM v1 API |
|---------|-----------|------------|
| **Authentication** | Server Key | OAuth2 Access Token |
| **Credentials** | Server Key (string) | Service Account JSON file |
| **Endpoint** | `https://fcm.googleapis.com/fcm/send` | `https://fcm.googleapis.com/v1/projects/{project_id}/messages:send` |
| **Request Format** | Batch (multiple tokens) | Individual (one token per request) |
| **Status** | ❌ Deprecated | ✅ Current |

## Step-by-Step Migration

### Step 1: Enable FCM v1 API

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Select your project: **sarvcast-20d5c**
3. Navigate to **APIs & Services** → **Library**
4. Search for **"Firebase Cloud Messaging API"**
5. Click **Enable** (if not already enabled)

**Direct Link**: https://console.cloud.google.com/apis/library/fcm.googleapis.com?project=sarvcast-20d5c

### Step 2: Generate Service Account Key

1. Go to [Firebase Console](https://console.firebase.google.com/)
2. Select your project: **sarvcast-20d5c**
3. Click **Project Settings** (gear icon) → **Service Accounts** tab
4. Click **Generate new private key**
5. Confirm by clicking **Generate Key**
6. A JSON file will be downloaded (e.g., `sarvcast-20d5c-firebase-adminsdk-xxxxx.json`)

**Direct Link**: https://console.firebase.google.com/project/sarvcast-20d5c/settings/serviceaccounts/adminsdk

### Step 3: Store Service Account File

1. **Copy the downloaded JSON file** to your Laravel project:
   ```
   storage/app/firebase-service-account.json
   ```

2. **Important Security Notes**:
   - ✅ Add to `.gitignore` (already included)
   - ✅ Set proper file permissions (600 or 640)
   - ✅ Never commit this file to Git
   - ✅ Keep it secure on your server

### Step 4: Update Laravel .env File

Add these lines to `sarvcast-laravel/.env`:

```env
# Firebase FCM v1 API Configuration
FIREBASE_PROJECT_ID=sarvcast-20d5c
FIREBASE_SERVICE_ACCOUNT_PATH=storage/app/firebase-service-account.json
FIREBASE_USE_V1_API=true

# Legacy API (optional fallback - can be removed after migration)
# FIREBASE_SERVER_KEY=your-old-server-key-here
```

### Step 5: Verify File Permissions

On Linux/Mac:
```bash
chmod 600 storage/app/firebase-service-account.json
```

On Windows:
- Right-click file → Properties → Security
- Remove all users except your application user
- Grant read-only access

### Step 6: Test the Migration

1. **Clear Laravel cache**:
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

2. **Test in Tinker**:
   ```bash
   php artisan tinker
   ```
   ```php
   $user = \App\Models\User::first();
   $service = app(\App\Services\NotificationService::class);
   $service->sendPushNotification($user, 'Test', 'FCM v1 API test');
   ```

3. **Check logs**:
   ```bash
   tail -f storage/logs/laravel.log
   ```

## Code Changes Made

### ✅ New Files Created:
- `app/Services/FirebaseAuthService.php` - Handles OAuth2 token generation
- `FCM_V1_MIGRATION_GUIDE.md` - This guide

### ✅ Files Updated:
- `app/Services/NotificationService.php` - Now uses FCM v1 API
- `config/notification.php` - Added v1 API configuration

### Key Features:
- ✅ Automatic OAuth2 token generation
- ✅ Token caching (50 minutes, tokens expire after 1 hour)
- ✅ Fallback to Legacy API if v1 fails
- ✅ Individual token handling (FCM v1 requirement)
- ✅ Proper error logging

## Troubleshooting

### Error: "Service account file not found"

**Solution**:
1. Verify file path in `.env`: `FIREBASE_SERVICE_ACCOUNT_PATH`
2. Check file exists: `ls -la storage/app/firebase-service-account.json`
3. Verify file permissions

### Error: "Failed to get Firebase access token"

**Possible Causes**:
1. Service account JSON is invalid
2. FCM API not enabled in Google Cloud
3. Service account doesn't have proper permissions

**Solution**:
1. Re-download service account JSON from Firebase Console
2. Verify FCM API is enabled
3. Check service account has "Firebase Cloud Messaging Admin" role

### Error: "Invalid JWT signature"

**Solution**:
1. Verify service account JSON is complete
2. Check `private_key` field exists in JSON
3. Ensure no extra whitespace in JSON file

### Notifications Not Sending

**Check**:
1. Laravel logs: `storage/logs/laravel.log`
2. FCM tokens are valid (check `user_devices` table)
3. Access token is being generated (check cache)
4. Network connectivity to `fcm.googleapis.com`

## Rollback to Legacy API

If you need to temporarily rollback:

1. Update `.env`:
   ```env
   FIREBASE_USE_V1_API=false
   FIREBASE_SERVER_KEY=your-server-key
   ```

2. Clear cache:
   ```bash
   php artisan config:clear
   ```

## Verification Checklist

After migration, verify:

- [ ] Service account JSON file is in `storage/app/`
- [ ] `.env` has `FIREBASE_USE_V1_API=true`
- [ ] `.env` has `FIREBASE_PROJECT_ID=sarvcast-20d5c`
- [ ] `.env` has `FIREBASE_SERVICE_ACCOUNT_PATH` set
- [ ] FCM API is enabled in Google Cloud Console
- [ ] Test notification sends successfully
- [ ] Check Laravel logs for errors
- [ ] Verify notifications appear on devices

## Performance Notes

**FCM v1 API**:
- Sends one request per token (not batch)
- Slightly slower for many tokens
- More reliable and future-proof
- Better error handling per token

**Recommendation**: Use FCM v1 API for all new implementations.

## Security Best Practices

1. ✅ **Never commit** service account JSON to Git
2. ✅ Store file outside web root (use `storage/app/`)
3. ✅ Set restrictive file permissions (600)
4. ✅ Rotate service account keys periodically
5. ✅ Monitor access token generation in logs
6. ✅ Use environment variables for paths

## Additional Resources

- [Firebase FCM v1 Migration Guide](https://firebase.google.com/docs/cloud-messaging/migrate-v1)
- [FCM HTTP v1 API Reference](https://firebase.google.com/docs/reference/fcm/rest/v1/projects.messages)
- [Service Account Authentication](https://cloud.google.com/docs/authentication/production)

---

**Migration Status**: ✅ Code updated, ready for configuration
**Last Updated**: 2025-01-27

