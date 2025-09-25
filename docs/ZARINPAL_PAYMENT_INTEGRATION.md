# Zarinpal Payment Integration Configuration

## Overview
This document describes the Zarinpal payment gateway integration configuration for the SarvCast application.

## Merchant Configuration

### Your Zarinpal Merchant Code
- **Merchant ID**: `77751ff3-c1cc-411b-869d-2ac7d7b02f88`
- **Status**: Production Ready
- **Gateway**: Zarinpal Payment Gateway

## Configuration Setup

### 1. Environment Variables
Add the following to your `.env` file:

```env
# Zarinpal Configuration
ZARINPAL_MERCHANT_ID=77751ff3-c1cc-411b-869d-2ac7d7b02f88
ZARINPAL_CALLBACK_URL=https://my.sarvcast.ir
ZARINPAL_SANDBOX=false
```

### 2. Service Configuration
The configuration is stored in `config/services.php`:

```php
'zarinpal' => [
    'merchant_id' => env('ZARINPAL_MERCHANT_ID', '77751ff3-c1cc-411b-869d-2ac7d7b02f88'),
    'callback_url' => env('ZARINPAL_CALLBACK_URL', 'https://my.sarvcast.ir'),
    'sandbox' => env('ZARINPAL_SANDBOX', false),
],
```

## Payment Service Implementation

### PaymentService Class
The `PaymentService` class handles all Zarinpal payment operations:

#### Key Features:
- ✅ **Configurable Merchant ID**: Uses your merchant code `77751ff3-c1cc-411b-869d-2ac7d7b02f88`
- ✅ **Sandbox Support**: Can switch between sandbox and production modes
- ✅ **Comprehensive Logging**: Detailed logs for debugging and monitoring
- ✅ **Error Handling**: Robust error handling with meaningful messages
- ✅ **Callback Processing**: Automatic payment verification and subscription activation

#### Methods:

**1. `initiateZarinPalPayment(Payment $payment)`**
- Creates payment request with Zarinpal
- Returns payment URL for user redirection
- Stores authority code for verification

**2. `verifyZarinPalPayment(string $authority, int $amount)`**
- Verifies payment with Zarinpal
- Returns payment details including ref_id
- Handles both sandbox and production APIs

**3. `processCallback(array $data)`**
- Processes payment callbacks from Zarinpal
- Updates payment status and activates subscriptions
- Fires sales notification events

## API Endpoints

### Payment Initiation
```http
POST /api/v1/payments/initiate
Content-Type: application/json
Authorization: Bearer {token}

{
    "subscription_id": 1
}
```

**Response:**
```json
{
    "success": true,
    "message": "درخواست پرداخت با موفقیت ایجاد شد",
    "data": {
        "payment": {
            "id": 1,
            "amount": 50000,
            "status": "pending",
            "transaction_id": "A00000000000000000000000000000000000000000000"
        },
        "payment_url": "https://www.zarinpal.com/pg/StartPay/A00000000000000000000000000000000000000000000",
        "authority": "A00000000000000000000000000000000000000000000"
    }
}
```

### Payment Verification
```http
POST /api/v1/payments/verify
Content-Type: application/json
Authorization: Bearer {token}

{
    "authority": "A00000000000000000000000000000000000000000000",
    "status": "OK"
}
```

**Response:**
```json
{
    "success": true,
    "message": "پرداخت با موفقیت انجام شد",
    "data": {
        "payment": {
            "id": 1,
            "status": "completed",
            "paid_at": "2024-01-15T10:30:00Z"
        },
        "subscription": {
            "id": 1,
            "status": "active",
            "start_date": "2024-01-15T10:30:00Z",
            "end_date": "2024-02-14T10:30:00Z"
        }
    }
}
```

## Payment Flow

### 1. Payment Initiation
```
User requests subscription payment
    ↓
PaymentController::initiate() creates Payment record
    ↓
PaymentService::initiateZarinPalPayment() calls Zarinpal API
    ↓
Zarinpal returns authority code and payment URL
    ↓
User redirected to Zarinpal payment page
```

### 2. Payment Processing
```
User completes payment on Zarinpal
    ↓
Zarinpal redirects to callback URL with status
    ↓
PaymentController::verify() processes callback
    ↓
PaymentService::verifyZarinPalPayment() verifies with Zarinpal
    ↓
Payment status updated to 'completed'
    ↓
Subscription activated automatically
```

## Environment Modes

### Production Mode
- **API URL**: `https://api.zarinpal.com/pg/v4/payment/`
- **Payment URL**: `https://www.zarinpal.com/pg/StartPay/`
- **Merchant ID**: `77751ff3-c1cc-411b-869d-2ac7d7b02f88`
- **Real Transactions**: Yes

### Sandbox Mode
- **API URL**: `https://sandbox.zarinpal.com/pg/v4/payment/`
- **Payment URL**: `https://sandbox.zarinpal.com/pg/StartPay/`
- **Test Mode**: Yes
- **Real Transactions**: No

## Logging and Monitoring

### Log Entries
All payment operations are logged with detailed information:

**Payment Initiation:**
```php
Log::info('Initiating ZarinPal payment', [
    'payment_id' => $payment->id,
    'amount' => $payment->amount,
    'merchant_id' => $this->zarinpalMerchantId,
    'sandbox_mode' => $this->sandboxMode,
    'api_url' => $apiUrl
]);
```

**Payment Verification:**
```php
Log::info('ZarinPal payment verified successfully', [
    'authority' => $authority,
    'ref_id' => $result['data']['ref_id'],
    'amount' => $result['data']['amount']
]);
```

### Error Handling
Comprehensive error logging for debugging:
- API request failures
- Payment verification errors
- Network connectivity issues
- Invalid merchant configurations

## Security Considerations

### 1. Merchant ID Protection
- Merchant ID is stored in environment variables
- Not exposed in client-side code
- Configurable per environment

### 2. Callback URL Security
- Callback URL is configurable
- Should use HTTPS in production
- Validates payment authority before processing

### 3. Amount Validation
- Payment amounts are validated server-side
- Prevents amount tampering
- Double verification with Zarinpal

## Testing

### Test Environment Setup
```env
ZARINPAL_SANDBOX=true
ZARINPAL_MERCHANT_ID=77751ff3-c1cc-411b-869d-2ac7d7b02f88
```

### Test Payment Flow
1. Create test subscription
2. Initiate payment (will use sandbox)
3. Complete payment on sandbox page
4. Verify payment status
5. Check subscription activation

## Troubleshooting

### Common Issues

**1. Invalid Merchant ID**
- Check environment variable `ZARINPAL_MERCHANT_ID`
- Verify merchant ID format
- Ensure merchant is active in Zarinpal panel

**2. Callback URL Issues**
- Verify `ZARINPAL_CALLBACK_URL` is correct
- Ensure URL is accessible from internet
- Check HTTPS configuration

**3. Payment Verification Failures**
- Check amount matches exactly
- Verify authority code is valid
- Ensure payment is not already verified

### Debug Steps
1. Check application logs for detailed error messages
2. Verify Zarinpal merchant panel for transaction status
3. Test with sandbox mode first
4. Validate callback URL accessibility

## Production Deployment

### Checklist
- [ ] Set `ZARINPAL_SANDBOX=false`
- [ ] Verify merchant ID is correct
- [ ] Test callback URL accessibility
- [ ] Enable HTTPS for callback URL
- [ ] Monitor payment logs
- [ ] Set up payment notifications

### Monitoring
- Monitor payment success rates
- Track failed payment reasons
- Monitor callback processing times
- Set up alerts for payment failures

## Support

For Zarinpal-related issues:
1. Check Zarinpal merchant panel
2. Review application logs
3. Contact Zarinpal support for gateway issues
4. Verify merchant account status

---

**Last Updated**: January 2024  
**Version**: 1.0  
**Status**: Production Ready  
**Merchant ID**: `77751ff3-c1cc-411b-869d-2ac7d7b02f88`
