# FCM v1 API - Quick Start Guide

## 🚀 Quick Setup (5 minutes)

### Step 1: Get Service Account JSON

1. Go to: https://console.firebase.google.com/project/sarvcast-20d5c/settings/serviceaccounts/adminsdk
2. Click **"Generate new private key"**
3. Click **"Generate Key"**
4. Save the downloaded JSON file

### Step 2: Place File in Laravel

```bash
# Copy the downloaded file to:
storage/app/firebase-service-account.json
```

### Step 3: Update .env

Add to `sarvcast-laravel/.env`:

```env
FIREBASE_PROJECT_ID=sarvcast-20d5c
FIREBASE_SERVICE_ACCOUNT_PATH=storage/app/firebase-service-account.json
FIREBASE_USE_V1_API=true
```

### Step 4: Enable FCM API

1. Go to: https://console.cloud.google.com/apis/library/fcm.googleapis.com?project=sarvcast-20d5c
2. Click **"Enable"**

### Step 5: Clear Cache & Test

```bash
php artisan config:clear
php artisan cache:clear
```

## ✅ Done!

Your Laravel app now uses FCM v1 API. Test by sending a notification.

## 📋 What Changed?

- ✅ **New**: `FirebaseAuthService` - Handles OAuth2 tokens
- ✅ **Updated**: `NotificationService` - Uses FCM v1 API
- ✅ **Updated**: `config/notification.php` - Added v1 config

## 🔒 Security

The service account JSON file is automatically added to `.gitignore` - it will NOT be committed to Git.

---

**Need help?** See `FCM_V1_MIGRATION_GUIDE.md` for detailed instructions.

