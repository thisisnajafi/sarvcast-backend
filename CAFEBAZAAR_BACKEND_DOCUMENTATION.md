# CafeBazaar Backend API Documentation

## Overview

This document provides comprehensive documentation for the CafeBazaar subscription backend APIs in the Laravel application.

## API Endpoints

### 1. Verify Subscription

**Endpoint**: `POST /api/v1/subscriptions/cafebazaar/verify`

**Authentication**: Required (Bearer token)

**Request Body**:
```json
{
  "purchase_token": "string (required)",
  "product_id": "string (required)",
  "order_id": "string (optional)",
  "is_sandbox": "boolean (optional)",
  "billing_platform": "cafebazaar"
}
```

**Response (Success - 200)**:
```json
{
  "success": true,
  "message": "اشتراک کافه‌بازار با موفقیت تایید و فعال شد.",
  "data": {
    "payment": {
      "id": 123,
      "user_id": 456,
      "amount": 50000,
      "currency": "IRR",
      "status": "completed",
      "transaction_id": "CB_1234567890",
      "billing_platform": "cafebazaar",
      "purchase_token": "...",
      "product_id": "subscription_1month"
    },
    "subscription": {
      "id": 789,
      "user_id": 456,
      "status": "active",
      "end_date": "2025-12-07T00:00:00Z",
      "billing_platform": "cafebazaar"
    }
  }
}
```

**Response (Error - 400/422/500)**:
```json
{
  "success": false,
  "message": "خطا در تایید اشتراک",
  "errors": {}
}
```

**Implementation**: `App\Http\Controllers\Api\CafeBazaarSubscriptionController::verifySubscription()`

### 2. Get Subscription Status

**Endpoint**: `GET /api/v1/subscriptions/cafebazaar/status`

**Authentication**: Required (Bearer token)

**Response (Success - 200)**:
```json
{
  "success": true,
  "message": "اشتراک کافه‌بازار فعال است.",
  "data": {
    "status": "active",
    "end_date": "2025-12-07T00:00:00Z",
    "auto_renew": true,
    "billing_platform": "cafebazaar"
  }
}
```

**Response (Not Found - 404)**:
```json
{
  "success": false,
  "message": "اشتراک کافه‌بازار فعالی یافت نشد."
}
```

**Implementation**: `App\Http\Controllers\Api\CafeBazaarSubscriptionController::getSubscriptionStatus()`

### 3. Restore Purchases

**Endpoint**: `POST /api/v1/subscriptions/cafebazaar/restore`

**Authentication**: Required (Bearer token)

**Request Body**:
```json
{
  "purchase_tokens": [
    {
      "purchase_token": "string (required)",
      "product_id": "string (required)",
      "order_id": "string (optional)"
    }
  ],
  "is_sandbox": "boolean (optional)"
}
```

**Response (Success - 200)**:
```json
{
  "success": true,
  "message": "عملیات بازیابی خرید با موفقیت انجام شد.",
  "processed_purchases": [
    {
      "product_id": "subscription_1month",
      "status": "success",
      "message": "خرید با موفقیت بازیابی و فعال شد."
    }
  ],
  "failed_purchases": []
}
```

**Implementation**: `App\Http\Controllers\Api\CafeBazaarSubscriptionController::restorePurchases()`

## Flavor Validation

All endpoints use `FlavorHelper::isCafeBazaar()` to validate that requests are from the CafeBazaar flavor.

**Implementation**: `App\Helpers\FlavorHelper`

**Validation Methods**:
1. `billing_platform` parameter
2. `package_name` parameter
3. `User-Agent` header
4. Payment metadata

**Error Response (403)**:
```json
{
  "success": false,
  "message": "این endpoint فقط برای درخواست‌های کافه‌بازار قابل استفاده است."
}
```

## Database Schema

### Payment Table
- `billing_platform`: 'cafebazaar'
- `purchase_token`: CafeBazaar purchase token
- `product_id`: Product ID from CafeBazaar
- `order_id`: Order ID from CafeBazaar
- `payment_metadata`: JSON with additional data

### Subscription Table
- `billing_platform`: 'cafebazaar'
- `store_subscription_id`: Subscription ID from CafeBazaar
- `store_metadata`: JSON with CafeBazaar-specific data

## Error Handling

### Common Error Codes
- `403`: Invalid flavor (not CafeBazaar)
- `422`: Validation error
- `400`: Bad request (invalid purchase token, etc.)
- `500`: Server error

### Logging

All operations are logged with:
- User ID
- Product ID
- Purchase token (partial, for security)
- Error details
- Timestamps

**Log Locations**:
- Success: `storage/logs/laravel.log` (info level)
- Errors: `storage/logs/laravel.log` (error level)

## Maintenance

### Regular Tasks
- Monitor subscription activation rates
- Review error logs
- Check API performance
- Update dependencies

### Configuration
- CafeBazaar API credentials in `.env`
- Product ID mappings in `CafeBazaarService`
- Flavor validation in `FlavorHelper`

## Troubleshooting

See main documentation: `CAFEBAZAAR_COMPLETE_DOCUMENTATION.md`

---

**Last Updated**: 2025-12-07  
**Version**: 1.0.0

