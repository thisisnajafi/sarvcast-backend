# User Update Migration Guide - Push Notifications

## Overview

This document explains how existing users who update the app will have their devices automatically registered for push notifications.

## How It Works

### ✅ Automatic Registration Flow

1. **App Startup (main.dart)**
   - `PushNotificationService().initialize()` is called automatically
   - This happens on every app launch, including after updates

2. **Initialization Process**
   - Requests notification permissions
   - Gets FCM token from Firebase
   - Attempts to register device with backend via `_registerTokenWithBackend()`

3. **Backend Registration**
   - Endpoint: `POST /api/mobile/device/register`
   - Uses `updateOrInsert()` - handles both new and existing devices
   - Requires user authentication (user must be logged in)

### ⚠️ Important Considerations

**For Existing Users Updating:**

1. **First Launch After Update**
   - App initializes push notification service
   - FCM token is obtained
   - Device registration is attempted
   - **If user is logged in**: Device is registered immediately ✅
   - **If user is not logged in**: Registration fails silently (expected behavior)

2. **After User Logs In**
   - User logs in normally
   - Device registration should happen automatically via:
     - Token refresh listener (if token changes)
     - Or manual retry (see improvements below)

3. **Token Refresh**
   - Firebase may refresh FCM tokens
   - `onTokenRefresh` listener automatically calls `_registerTokenWithBackend()`
   - This ensures tokens stay up-to-date

## Current Implementation Status

### ✅ What Works
- Automatic initialization on app startup
- FCM token retrieval
- Device registration endpoint exists and uses `updateOrInsert()`
- Token refresh handling
- No duplicate devices (unique constraint on user_id + device_id)

### ⚠️ Potential Issues

1. **Registration Before Login**
   - Device registration is attempted before user logs in
   - This will fail (requires authentication)
   - **Impact**: Low - registration happens when user logs in or token refreshes

2. **No Explicit Post-Login Registration**
   - No explicit call to register device after successful login
   - **Impact**: Medium - relies on token refresh or app restart

## Recommended Improvements

### Option 1: Add Post-Login Registration (Recommended)

Add device registration after successful login:

```dart
// In verification_page.dart or auth_service.dart
Future<void> _loginUser() async {
  // ... existing login code ...
  
  if (response.success && response.data != null) {
    await AuthService.saveAuthData(response.data!.token!, response.data!.user!);
    
    // Register device after successful login
    try {
      await PushNotificationService().updateToken();
    } catch (e) {
      debugPrint('Device registration after login failed: $e');
      // Non-critical, continue anyway
    }
    
    _navigateToMainApp();
  }
}
```

### Option 2: Add Auth State Listener

Listen for authentication state changes and register device:

```dart
// In main.dart or a dedicated auth service
void _setupAuthListener() {
  // Listen for auth state changes
  // When user logs in, call PushNotificationService().updateToken()
}
```

### Option 3: Retry Logic

Add retry logic to `_registerTokenWithBackend()`:

```dart
Future<void> _registerTokenWithBackend({bool retryOnAuthError = true}) async {
  try {
    // ... existing registration code ...
  } catch (e) {
    if (e is UnauthorizedException && retryOnAuthError) {
      // Store flag to retry after login
      // Retry when user logs in
    }
  }
}
```

## Testing the Update Flow

### For Existing Users

1. **User has app version without push notifications**
2. **User updates to new version with push notifications**
3. **Expected behavior:**
   - App starts normally
   - Push notification service initializes
   - If user is logged in: Device registers immediately
   - If user is not logged in: Device registers when they log in
   - User receives push notifications

### Test Scenarios

1. **Update + Already Logged In**
   - ✅ Device should register immediately
   - ✅ User should receive notifications

2. **Update + Not Logged In**
   - ⚠️ Device registration will fail initially
   - ✅ Device should register when user logs in
   - ✅ User should receive notifications after login

3. **Multiple Devices**
   - ✅ Each device gets its own record (unique device_id)
   - ✅ User can receive notifications on all devices

4. **Token Refresh**
   - ✅ Token refresh automatically updates backend
   - ✅ No duplicate records

## Database Schema

The `user_devices` table structure:

```sql
CREATE TABLE user_devices (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    device_id VARCHAR(100) NOT NULL,
    device_type VARCHAR(50) NOT NULL,
    device_model VARCHAR(100) NULL,
    os_version VARCHAR(50) NULL,
    app_version VARCHAR(20) NULL,
    fcm_token VARCHAR(500) NULL,
    last_active TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY unique_user_device (user_id, device_id),
    INDEX idx_fcm_token (fcm_token),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

## Monitoring

### Check Device Registration

```bash
# Check if user has registered devices
php artisan tinker
$user = \App\Models\User::findByPhoneNumber('09136708883');
$devices = DB::table('user_devices')->where('user_id', $user->id)->get();
```

### Check FCM Tokens

```bash
# List all FCM tokens for a user
php artisan tinker
$user = \App\Models\User::findByPhoneNumber('09136708883');
$tokens = DB::table('user_devices')
    ->where('user_id', $user->id)
    ->whereNotNull('fcm_token')
    ->pluck('fcm_token');
```

## Summary

**For existing users updating the app:**

✅ **Automatic**: Device registration happens automatically on app startup  
✅ **No Duplicates**: `updateOrInsert()` prevents duplicate device records  
✅ **Token Refresh**: Automatic token updates handled  
⚠️ **Requires Login**: Device registration requires user to be authenticated  
✅ **Multiple Devices**: Each device gets its own record  

**Recommendation**: Add explicit device registration after successful login to ensure immediate registration for users who update while logged out.

