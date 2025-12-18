# SMS Template Integration with Meli Payamak

## Overview
This document describes the integration of Meli Payamak SMS templates with the SarvCast application for sending verification codes.

## Template Configuration

### Verification Code Template
- **Template ID**: `371085`
- **Template Message**: `کد ورود شما: {0} این کد 5 دقیقه اعتبار دارد سروکست`
- **Parameters**: `{0}` - The verification code (4-digit number)

### Environment Configuration
Add the following to your `.env` file:
```env
MELIPAYAMK_VERIFICATION_TEMPLATE=371085
```

### Service Configuration
The template is configured in `config/services.php`:
```php
'melipayamk' => [
    'token' => env('MELIPAYAMK_TOKEN', '77c431b7-aec5-4313-b744-d2f16bf760ab'),
    'sender' => env('MELIPAYAMK_SENDER', '50002710008883'),
    'templates' => [
        'verification' => env('MELIPAYAMK_VERIFICATION_TEMPLATE', 371085),
    ],
],
```

## Implementation Details

### SmsService Updates
The `SmsService` class has been updated to support template-based SMS sending:

1. **New Method**: `sendSmsWithTemplate()`
   - Sends SMS using Meli Payamak template system
   - Uses `sendByBaseNumber()` method from MelipayamakApi
   - Supports parameter substitution

2. **Updated Method**: `sendOtp()`
   - Now uses template-based sending instead of plain text
   - Automatically uses template ID 371085 for verification codes
   - Passes the generated OTP code as parameter `{0}`

### Template Message Flow
```
User requests verification code
    ↓
SmsService::sendOtp() generates 4-digit code
    ↓
Code stored in cache for 5 minutes
    ↓
Template ID 371085 used with parameter [code]
    ↓
Meli Payamak sends: "کد ورود شما: 1234 این کد 5 دقیقه اعتبار دارد سروکست"
```

## Usage Examples

### Sending Verification Code
```php
use App\Services\SmsService;

$smsService = new SmsService();
$result = $smsService->sendOtp('09123456789', 'login');

if ($result['success']) {
    echo "Verification code sent successfully";
} else {
    echo "Failed to send verification code: " . $result['error'];
}
```

### Direct Template Usage
```php
use App\Services\SmsService;

$smsService = new SmsService();
$result = $smsService->sendSmsWithTemplate(
    '09123456789', 
    371085, 
    ['1234'] // OTP code parameter
);
```

## API Integration

### Authentication Controller
The `AuthController` uses the updated SMS service:
```php
// In sendVerificationCode method
$result = $this->smsService->sendOtp($phoneNumber, 'login');
```

### Response Format
```json
{
    "success": true,
    "message": "کد تایید به شماره شما ارسال شد",
    "data": {
        "is_new_user": false,
        "expires_in": 300,
        "next_step": "login"
    }
}
```

## Benefits of Template Integration

1. **Compliance**: Uses approved SMS templates from Meli Payamak
2. **Consistency**: Standardized message format across all verification codes
3. **Branding**: Includes "سروکست" brand name in messages
4. **Reliability**: Higher delivery rates with template-based SMS
5. **Cost Efficiency**: Better pricing for template-based messages

## Error Handling

### Common Error Scenarios
1. **Invalid Template ID**: Template not found or not approved
2. **Parameter Mismatch**: Wrong number of parameters for template
3. **Rate Limiting**: Too many SMS requests in short time
4. **Network Issues**: Connection problems with Meli Payamak API

### Error Response Format
```json
{
    "success": false,
    "message": "خطا در ارسال کد تایید. لطفاً مجدداً تلاش کنید.",
    "error": "Template not found"
}
```

## Monitoring and Logging

### Log Entries
All SMS operations are logged with detailed information:
```php
Log::info('SMS sent via Melipayamk with template', [
    'melipayamak_username' => $this->username,
    'sending_data' => $sendingData,
    'response' => $json,
    'raw_response' => $response,
    'message_id' => $json->Value ?? null
]);
```

### Database Tracking
OTP attempts are stored in the `otp_attempts` table:
- Phone number
- Generated code
- Purpose (login, verification, etc.)
- Verification status
- Expiration time

## Testing

### Test Environment
For testing purposes, you can use a test template ID or mock the SMS service:
```php
// In tests
$this->mock(SmsService::class, function ($mock) {
    $mock->shouldReceive('sendOtp')
         ->andReturn(['success' => true, 'message_id' => 'test123']);
});
```

### Production Considerations
- Ensure template is approved and active in Meli Payamak panel
- Monitor delivery rates and error logs
- Set up proper rate limiting to avoid spam
- Implement proper error handling for failed deliveries

## Migration Notes

### From Plain Text to Template
The migration from plain text SMS to template-based SMS is backward compatible:
- Existing `sendSms()` method still available for non-template messages
- `sendOtp()` method automatically uses template system
- No changes required in calling code

### Configuration Migration
Update your `.env` file to include the template ID:
```env
# Add this line
MELIPAYAMK_VERIFICATION_TEMPLATE=371085
```

## Support

For issues related to SMS template integration:
1. Check Meli Payamak panel for template status
2. Review application logs for detailed error messages
3. Verify template ID and parameter count
4. Contact Meli Payamak support for template-related issues

---

**Last Updated**: January 2024  
**Version**: 1.0  
**Status**: Production Ready
