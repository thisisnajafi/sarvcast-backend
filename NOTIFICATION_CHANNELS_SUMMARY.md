# Notification Channels Summary

## Current Implementation

Based on your requirements, the notification system has been updated to use:

### ✅ Active Channels

1. **In-App Notifications** - All notification types
   - Stored in database
   - Visible in app notifications page
   - Can be marked as read/unread
   - Can be deleted

2. **Push Notifications** - All notification types
   - Sent via Firebase Cloud Messaging (FCM)
   - Appears in system notification tray
   - Can navigate to content when tapped

3. **SMS Notifications** - Marketing only
   - Sent via SMS service (Melipayamak)
   - Only for promotional/marketing messages
   - Requires user phone number

### ❌ Disabled Channels

- **Email Notifications** - Removed from all notification types

---

## Notification Types & Channels

| Notification Type | In-App | Push | SMS |
|------------------|--------|------|-----|
| Subscription Created | ✅ | ✅ | ❌ |
| Subscription Activated | ✅ | ✅ | ❌ |
| Subscription Expired | ✅ | ✅ | ❌ |
| Subscription Cancelled | ✅ | ✅ | ❌ |
| Payment Success | ✅ | ✅ | ❌ |
| Payment Failed | ✅ | ✅ | ❌ |
| New Episode | ✅ | ✅ | ❌ |
| New Story | ✅ | ✅ | ❌ |
| Story Completed | ✅ | ✅ | ❌ |
| Marketing/Promotional | ✅ | ✅ | ✅ |

---

## Marketing SMS Notifications

### New Methods Added

#### `sendMarketingNotification()`
Send marketing notification to a single user with optional SMS.

```php
$notificationService->sendMarketingNotification(
    $user,
    'پیشنهاد ویژه',
    'کد تخفیف 50% برای شما: SAVE50',
    ['promo_code' => 'SAVE50', 'discount' => 50],
    true // Send SMS
);
```

#### `sendBulkMarketingNotification()`
Send marketing notification to multiple users.

```php
$notificationService->sendBulkMarketingNotification(
    [1, 2, 3], // User IDs
    'پیشنهاد ویژه',
    'کد تخفیف 50% برای شما: SAVE50',
    ['promo_code' => 'SAVE50'],
    true // Send SMS
);
```

### When to Use Marketing SMS

- Special offers and discounts
- Limited-time promotions
- New feature announcements
- Seasonal campaigns
- Re-engagement campaigns
- Subscription renewal reminders (optional)

### SMS Opt-Out

Users should be able to opt-out of marketing SMS through:
- Notification settings in app
- User preferences API
- Admin panel

---

## Configuration

Updated in `config/notification.php`:

```php
'channels' => [
    'default' => ['in_app', 'push'],
    'subscription' => ['in_app', 'push'],
    'payment' => ['in_app', 'push'],
    'content' => ['in_app', 'push'],
    'marketing' => ['in_app', 'push', 'sms'],
],
```

---

## Files Modified

1. ✅ `app/Services/NotificationService.php`
   - Removed email from subscription notifications
   - Added `sendMarketingNotification()` method
   - Added `sendBulkMarketingNotification()` method

2. ✅ `config/notification.php`
   - Removed email from all channel configurations
   - Updated marketing channel to use SMS instead of email

3. ✅ `USER_NOTIFICATIONS_DOCUMENTATION.md`
   - Updated documentation to reflect current channels
   - Added marketing notification section

---

## Usage Examples

### Send Marketing Notification with SMS

```php
use App\Services\NotificationService;

$notificationService = app(NotificationService::class);

// Single user
$notificationService->sendMarketingNotification(
    $user,
    'تخفیف ویژه',
    'کد تخفیف 30%: DISCOUNT30 - فقط امروز!',
    ['promo_code' => 'DISCOUNT30', 'expires_at' => '2024-12-31'],
    true // Send SMS
);

// Multiple users
$userIds = [1, 2, 3, 4, 5];
$notificationService->sendBulkMarketingNotification(
    $userIds,
    'داستان جدید',
    'داستان جدید "شاهزاده و گدا" منتشر شد!',
    ['story_id' => 123],
    false // Don't send SMS for content announcements
);
```

### Regular Notifications (No SMS)

```php
// Subscription notification
$notificationService->sendSubscriptionNotification(
    $user,
    'subscription_created',
    ['subscription_id' => 123]
);

// Content notification
$notificationService->sendContentNotification(
    $user,
    'new_episode',
    ['story_id' => 456, 'episode_id' => 789]
);
```

---

## Summary

✅ **In-App Notifications**: All types
✅ **Push Notifications**: All types  
✅ **SMS Notifications**: Marketing only
❌ **Email Notifications**: Disabled

The system is now configured according to your requirements!

