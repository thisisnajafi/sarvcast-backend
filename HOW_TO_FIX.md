# FIX SUBSCRIPTION END_DATE ISSUE

## THE PROBLEM
Subscription ID 12 (and possibly others) has `status='active'` but `end_date=NULL`.
This prevents the subscription from being detected as active.

## THE FIX

### Option 1: Fix Single Subscription (Recommended)
Run this command on your server:

```bash
php artisan subscription:fix-end-date 12
```

This will:
- Find subscription ID 12
- Calculate the correct end_date based on type and start_date
- Update the subscription
- Verify the fix

### Option 2: Fix ALL Active Subscriptions with NULL end_date
If you have multiple subscriptions with this issue:

```bash
php artisan subscription:fix-all-end-dates
```

This will:
- Find all active subscriptions with null end_date
- Fix them all at once
- Show a summary

### Option 3: Direct SQL (If commands don't work)
```sql
UPDATE subscriptions 
SET end_date = DATE_ADD(start_date, INTERVAL 30 DAY) 
WHERE id = 12;
```

Or specifically for subscription 12:
```sql
UPDATE subscriptions 
SET end_date = '2025-11-04 13:49:32' 
WHERE id = 12;
```

## VERIFICATION

After running the fix:

1. **Check the subscription:**
```bash
php artisan tinker
```
```php
$sub = \App\Models\Subscription::find(12);
echo "Status: {$sub->status}\n";
echo "End Date: {$sub->end_date}\n";
exit;
```

2. **Test the API:**
```bash
GET /api/v1/auth/profile
```

Should return:
```json
{
    "premium": {
        "is_premium": true,
        "subscription_status": "active"
    }
}
```

## FILES CREATED

1. `app/Console/Commands/FixSubscriptionEndDate.php` - Fix single subscription
2. `app/Console/Commands/FixAllActiveSubscriptionsWithoutEndDate.php` - Fix all subscriptions
3. `fix_subscription_12.php` - Standalone script (if Artisan doesn't work)

## USAGE

**On your production server:**
```bash
cd /path/to/sarvcast
php artisan subscription:fix-end-date 12
```

**Or if you need to fix multiple:**
```bash
php artisan subscription:fix-all-end-dates
```

That's it! The user will immediately be premium after running the command.
