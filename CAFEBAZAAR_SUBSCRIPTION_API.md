# CafeBazaar Subscription API Documentation

## Overview

This document describes the Laravel backend API endpoints for verifying CafeBazaar subscription receipts and updating user subscription status. The implementation is **flavor-aware**, meaning it only processes CafeBazaar purchases.

## API Endpoints

### 1. Verify Subscription Receipt

**Endpoint:** `POST /api/v1/subscriptions/cafebazaar/verify`

**Authentication:** Required (Bearer token)

**Description:** Verifies a CafeBazaar subscription purchase receipt and updates the user's subscription status in the database.

**Request Body:**
```json
{
  "purchase_token": "string (required, max 500)",
  "product_id": "string (required, max 100)",
  "order_id": "string (optional, max 100)",
  "billing_platform": "cafebazaar (optional, must be 'cafebazaar')"
}
```

**Response (Success - 200):**
```json
{
  "success": true,
  "message": "خرید با موفقیت تایید و اشتراک فعال شد",
  "data": {
    "payment": {
      "id": 123,
      "user_id": 1,
      "amount": 99000,
      "currency": "IRR",
      "status": "completed",
      "billing_platform": "cafebazaar",
      "purchase_token": "...",
      "product_id": "subscription_1month",
      "is_acknowledged": true,
      "subscription": { ... }
    },
    "subscription": {
      "id": 456,
      "user_id": 1,
      "type": "1month",
      "status": "active",
      "start_date": "2024-01-01T00:00:00Z",
      "end_date": "2024-02-01T00:00:00Z",
      "billing_platform": "cafebazaar",
      "auto_renew": true
    },
    "acknowledged": true
  }
}
```

**Response (Error - 400/422/500):**
```json
{
  "success": false,
  "message": "Error message in Persian",
  "errors": { ... } // Only for validation errors
}
```

**Flavor-Aware Check:**
- The endpoint validates that `billing_platform` is `cafebazaar`
- Returns 400 error if wrong platform is specified
- Only processes CafeBazaar purchases

### 2. Get Subscription Status

**Endpoint:** `GET /api/v1/subscriptions/cafebazaar/status`

**Authentication:** Required (Bearer token)

**Description:** Returns the current CafeBazaar subscription status for the authenticated user.

**Response (Success - 200):**
```json
{
  "success": true,
  "data": {
    "has_subscription": true,
    "subscription": {
      "id": 456,
      "type": "1month",
      "status": "active",
      "end_date": "2024-02-01T00:00:00Z",
      ...
    },
    "is_active": true,
    "days_remaining": 15,
    "status": "active",
    "end_date": "2024-02-01T00:00:00Z"
  }
}
```

### 3. Restore Purchases

**Endpoint:** `POST /api/v1/subscriptions/cafebazaar/restore`

**Authentication:** Required (Bearer token)

**Description:** Restores previous CafeBazaar purchases for the authenticated user.

**Response (Success - 200):**
```json
{
  "success": true,
  "message": "خریدهای قبلی بازیابی شد",
  "data": {
    "restored_count": 3,
    "purchases": [
      {
        "payment_id": 123,
        "subscription_id": 456,
        "product_id": "subscription_1month",
        "purchase_date": "2024-01-01T00:00:00Z",
        "subscription_status": "active",
        "subscription_end_date": "2024-02-01T00:00:00Z"
      },
      ...
    ]
  }
}
```

## Features

### 1. Flavor-Aware Processing
- ✅ Validates `billing_platform` is `cafebazaar`
- ✅ Rejects requests with wrong platform
- ✅ Only processes CafeBazaar purchases
- ✅ Logs platform mismatches

### 2. Error Handling
- ✅ Comprehensive validation with Persian error messages
- ✅ Try-catch blocks for all operations
- ✅ Database transaction rollback on errors
- ✅ Detailed error logging with context
- ✅ User-friendly error responses

### 3. Logging
- ✅ Logs all verification attempts
- ✅ Logs successful verifications with details
- ✅ Logs errors with full context
- ✅ Logs platform validation failures
- ✅ Logs database operations

### 4. Subscription Management
- ✅ Creates new subscriptions
- ✅ Extends existing subscriptions
- ✅ Updates subscription status to 'active'
- ✅ Calculates correct end dates
- ✅ Stores CafeBazaar metadata

### 5. Payment Processing
- ✅ Creates payment records
- ✅ Links payments to subscriptions
- ✅ Acknowledges purchases with CafeBazaar
- ✅ Prevents duplicate processing (idempotency)
- ✅ Stores purchase metadata

## Database Updates

### Payment Record
- Creates payment with `billing_platform = 'cafebazaar'`
- Sets status to `completed`
- Stores purchase token, product ID, order ID
- Links to subscription

### Subscription Record
- Creates or extends subscription
- Sets status to `active`
- Calculates end date based on plan duration
- Stores CafeBazaar-specific metadata
- Sets `billing_platform = 'cafebazaar'`

## Error Scenarios

### 1. Validation Errors (422)
- Missing required fields
- Invalid field formats
- Platform mismatch

### 2. CafeBazaar Verification Failure (400)
- Invalid purchase token
- Product ID not found
- Purchase already consumed
- CafeBazaar API error

### 3. Database Errors (500)
- Transaction rollback
- Foreign key violations
- Constraint violations

### 4. Business Logic Errors (400/404)
- Invalid product ID mapping
- Subscription plan not found
- User not found

## Security

- ✅ Authentication required for all endpoints
- ✅ Purchase token validation
- ✅ Idempotency checks (prevents duplicate processing)
- ✅ Flavor-aware validation
- ✅ Input sanitization and validation
- ✅ Secure logging (partial token logging)

## Testing

### Test Cases

1. **Valid Purchase Verification**
   - Send valid purchase token and product ID
   - Verify subscription is created/updated
   - Check payment record is created
   - Verify acknowledgment is sent

2. **Duplicate Purchase**
   - Send same purchase token twice
   - Verify second request returns existing subscription
   - Check no duplicate records created

3. **Invalid Platform**
   - Send request with `billing_platform = 'myket'`
   - Verify 400 error is returned
   - Check error message indicates platform mismatch

4. **Invalid Purchase Token**
   - Send invalid purchase token
   - Verify CafeBazaar API returns error
   - Check proper error response

5. **Subscription Extension**
   - Verify purchase for user with existing subscription
   - Verify subscription end date is extended
   - Check payment is linked correctly

## Integration with Flutter App

The Flutter app should call:
```
POST /api/v1/subscriptions/cafebazaar/verify
```

With the following payload:
```json
{
  "purchase_token": "<token from CafeBazaar>",
  "product_id": "<product ID>",
  "order_id": "<optional order ID>",
  "billing_platform": "cafebazaar"
}
```

## Configuration

Ensure the following config values are set in `config/services.php`:

```php
'cafebazaar' => [
    'package_name' => 'com.sarvabi.sarvcast.cafebazaar',
    'api_key' => env('CAFEBAZAAR_API_KEY'),
    'api_url' => env('CAFEBAZAAR_API_URL', 'https://pardakht.cafebazaar.ir/devapi/v2/api/validate'),
    'product_mapping' => [
        'subscription_1month' => '1month',
        'subscription_3months' => '3months',
        'subscription_6months' => '6months',
        'subscription_1year' => '1year',
    ],
],
```

## Notes

- All endpoints require authentication
- Purchase tokens are partially logged for security
- Database transactions ensure data consistency
- Idempotency prevents duplicate processing
- Flavor-aware checks ensure only CafeBazaar purchases are processed

