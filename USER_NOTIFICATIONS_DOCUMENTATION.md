# User Notifications Documentation

## Overview

This document describes all the notifications that users receive in the Manji application. Notifications are sent via multiple channels: **Push Notifications**, **In-App Notifications**, **Email**, and **SMS** (for critical events).

---

## 📱 Notification Channels

Based on the configuration in `config/notification.php`, different notification types use different channels:

- **Default**: In-App + Push
- **Subscription**: In-App + Push
- **Payment**: In-App + Push
- **Content**: In-App + Push
- **Marketing**: In-App + Push + SMS

---

## 🔔 Notification Types

### 1. Subscription Notifications

These notifications are sent when subscription-related events occur.

#### 1.1 Subscription Created
- **Event**: `subscription_created`
- **Title**: "اشتراک جدید"
- **Message**: "اشتراک شما با موفقیت ایجاد شد"
- **Type**: `success`
- **Channels**: In-App + Push
- **When**: When a user successfully creates a new subscription

#### 1.2 Subscription Activated
- **Event**: `subscription_activated`
- **Title**: "اشتراک فعال شد"
- **Message**: "اشتراک شما فعال شد و می‌توانید از تمام امکانات استفاده کنید"
- **Type**: `success`
- **Channels**: In-App + Push
- **When**: When a subscription becomes active

#### 1.3 Subscription Expired
- **Event**: `subscription_expired`
- **Title**: "اشتراک منقضی شد"
- **Message**: "اشتراک شما منقضی شده است. برای ادامه استفاده، اشتراک جدید خریداری کنید"
- **Type**: `warning`
- **Channels**: In-App + Push
- **When**: When a subscription expires

#### 1.4 Subscription Cancelled
- **Event**: `subscription_cancelled`
- **Title**: "اشتراک لغو شد"
- **Message**: "اشتراک شما لغو شد"
- **Type**: `info`
- **Channels**: In-App + Push
- **When**: When a user cancels their subscription

---

### 2. Payment Notifications

These notifications are sent for payment-related events.

#### 2.1 Payment Success
- **Event**: `payment_success`
- **Title**: "پرداخت موفق"
- **Message**: "پرداخت شما با موفقیت انجام شد"
- **Type**: `success`
- **Channels**: In-App + Push
- **When**: When a payment is successfully completed

#### 2.2 Payment Failed
- **Event**: `payment_failed`
- **Title**: "پرداخت ناموفق"
- **Message**: "پرداخت شما انجام نشد. لطفاً مجدداً تلاش کنید"
- **Type**: `error`
- **Channels**: In-App + Push
- **When**: When a payment attempt fails

---

### 3. Content Notifications

These notifications inform users about new content related to their interests.

#### 3.1 New Episode
- **Event**: `new_episode`
- **Title**: "اپیزود جدید"
- **Message**: "اپیزود جدید از داستان مورد علاقه شما منتشر شد"
- **Type**: `info`
- **Channels**: In-App + Push
- **When**: When a new episode is published for a story the user follows/favorites
- **Data**: Contains `story_id` and `episode_id` for navigation

#### 3.2 New Story
- **Event**: `new_story`
- **Title**: "داستان جدید"
- **Message**: "داستان جدید در دسته‌بندی مورد علاقه شما منتشر شد"
- **Type**: `info`
- **Channels**: In-App + Push
- **When**: When a new story is published in a category the user follows/favorites
- **Data**: Contains `story_id` for navigation

#### 3.3 Story Completed
- **Event**: `story_completed`
- **Title**: "داستان تکمیل شد"
- **Message**: "شما داستان را به پایان رساندید"
- **Type**: `success`
- **Channels**: In-App + Push
- **When**: When a user finishes listening to all episodes of a story
- **Data**: Contains `story_id` for navigation

---

### 4. Marketing Notifications

These notifications are sent for promotional and marketing purposes.

#### 4.1 Marketing/Promotional
- **Method**: `sendMarketingNotification()`
- **Title**: Custom (e.g., "پیشنهاد ویژه", "تخفیف محدود")
- **Message**: Custom marketing message
- **Type**: `promotion`
- **Channels**: In-App + Push + **SMS** (if enabled)
- **When**: 
  - Special offers
  - Discount codes
  - Limited-time promotions
  - New feature announcements
  - Seasonal campaigns
- **Data**: Custom marketing data

**Example Usage:**
```php
$notificationService->sendMarketingNotification(
    $user,
    'پیشنهاد ویژه',
    'کد تخفیف 50% برای شما: SAVE50',
    ['promo_code' => 'SAVE50', 'discount' => 50],
    true // Send SMS
);
```

---

## 🎯 How Notifications Are Triggered

### Current Implementation Status

The notification service methods are defined, but they need to be called from appropriate places in the application. Here's where notifications **should** be triggered:

#### Subscription Notifications
- **Subscription Created**: When subscription is created in payment/subscription controller
- **Subscription Activated**: When subscription status changes to 'active'
- **Subscription Expired**: When subscription expiry date is reached (via scheduled job)
- **Subscription Cancelled**: When user cancels subscription

#### Payment Notifications
- **Payment Success**: When payment status is confirmed as 'completed'
- **Payment Failed**: When payment status is 'failed' or verification fails

#### Content Notifications
- **New Episode**: When admin publishes a new episode (in `EpisodeController::store()` or `update()`)
- **New Story**: When admin publishes a new story (in `StoryController::store()` or `update()`)
- **Story Completed**: When user finishes the last episode of a story (in episode playback tracking)

---

## 📋 Notification Data Structure

Each notification includes the following data for navigation:

### Subscription Notifications
```json
{
  "type": "subscription",
  "subscription_id": 123,
  "plan_id": 456
}
```

### Payment Notifications
```json
{
  "type": "payment",
  "payment_id": 789,
  "amount": 50000,
  "subscription_id": 123
}
```

### Content Notifications
```json
{
  "type": "story",  // or "episode"
  "story_id": 123,
  "episode_id": 456  // for episode notifications
}
```

---

## 🔧 Implementation Notes

### To Enable Notifications

1. **Subscription Notifications**: 
   - Add calls to `NotificationService::sendSubscriptionNotification()` in:
     - Subscription creation endpoint
     - Subscription status update logic
     - Subscription expiry check (scheduled job)

2. **Payment Notifications**:
   - Add calls to `NotificationService::sendSubscriptionNotification()` with `payment_success` or `payment_failed` events in:
     - Payment verification endpoint
     - Payment callback handlers

3. **Content Notifications**:
   - Add calls to `NotificationService::sendContentNotification()` in:
     - `EpisodeController::store()` - when episode is published
     - `StoryController::store()` - when story is published
     - Episode playback completion tracking

### Example Implementation

```php
// In EpisodeController::store() after episode is created
if ($episode->status === 'published') {
    // Get users who follow this story or have it in favorites
    $users = User::whereHas('favorites', function($q) use ($episode) {
        $q->where('story_id', $episode->story_id);
    })->get();
    
    foreach ($users as $user) {
        $notificationService->sendContentNotification(
            $user,
            'new_episode',
            [
                'story_id' => $episode->story_id,
                'episode_id' => $episode->id
            ]
        );
    }
}
```

---

## 📊 Notification Preferences

Users can control notification preferences through:
- Notification settings API endpoint
- Settings page in the app
- Notification preferences stored in database

**Note**: Currently, notification preferences are defined in the model but may need UI implementation for users to customize which notifications they receive.

**Marketing SMS**: Users can opt-out of marketing SMS notifications through their notification preferences.

---

## 🚀 Future Enhancements

### Potential Additional Notifications

1. **Achievement Notifications**
   - User reaches listening milestones
   - User completes challenges
   - User earns badges

2. **Social Notifications**
   - Someone comments on a story
   - Someone likes your comment
   - New followers (if social features added)

3. **Reminder Notifications**
   - Daily listening reminders
   - Continue where you left off
   - New content available

4. **System Notifications**
   - App updates available
   - Maintenance notices
   - Security alerts

5. **Promotional Notifications**
   - Special offers
   - Discount codes
   - Limited-time content

---

## 📝 Summary

### Currently Defined Notifications

✅ **Subscription**: 4 types (created, activated, expired, cancelled)
✅ **Payment**: 2 types (success, failed)
✅ **Content**: 3 types (new episode, new story, story completed)
✅ **Marketing**: Custom promotional notifications

### Total: 9+ Notification Types (Marketing is custom)

### Notification Channels per Type

- **Subscription**: In-App + Push
- **Payment**: In-App + Push
- **Content**: In-App + Push
- **Marketing**: In-App + Push + SMS

### Next Steps

1. ✅ Notification service methods are ready
2. ⚠️ Need to add triggers in controllers/jobs
3. ✅ Flutter app can receive and display notifications
4. ✅ Navigation from notifications is implemented

---

## 🔗 Related Files

- `app/Services/NotificationService.php` - Main notification service
- `app/Services/InAppNotificationService.php` - In-app notification service
- `app/Http/Controllers/Admin/StoryController.php` - Story management
- `app/Http/Controllers/Admin/EpisodeController.php` - Episode management
- `app/Http/Controllers/Api/InAppNotificationController.php` - Notification API
- `config/notification.php` - Notification configuration

