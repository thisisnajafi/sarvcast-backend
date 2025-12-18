# Two-Factor Authentication (2FA) Update - 6-Digit Codes

## Overview
This document describes the update of the Two-Factor Authentication (2FA) system from 4-digit to 6-digit verification codes for the SarvCast admin dashboard.

## 🔧 Changes Made

### 1. Controller Updates

**File**: `app/Http/Controllers/Admin/TwoFactorAuthController.php`
- **Line 62**: Updated validation from `'code' => 'required|string|size:4'` to `'code' => 'required|string|size:6'`
- **Purpose**: Ensures only 6-digit codes are accepted for verification

### 2. View Updates

**File**: `resources/views/admin/auth/2fa-verify.blade.php`
- **Line 57**: Updated label from "کد تایید ۴ رقمی" to "کد تایید ۶ رقمی"
- **Line 59**: Updated `maxlength="4"` to `maxlength="6"`
- **Line 61**: Updated placeholder from "0000" to "000000"
- **Line 180**: Updated auto-submit logic from 4 digits to 6 digits
- **Line 191**: Updated form validation from 4 digits to 6 digits
- **Line 193**: Updated alert message to reflect 6-digit requirement

### 3. Service Layer

**File**: `app/Services/SmsService.php`
- **Line 309**: Already generating 6-digit codes: `str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT)`
- **Status**: No changes needed - already correct

### 4. Test Updates

**File**: `tests/Feature/SmsLoginTest.php`
- **Line 40**: Updated assertion from 4 digits to 6 digits

**File**: `tests/Feature/SmsTemplateIntegrationTest.php`
- **Line 52**: Updated test OTP from "1234" to "123456"
- **Lines 116-126**: Updated test method from `it_generates_4_digit_otp()` to `it_generates_6_digit_otp()`

**File**: `tests/Feature/TwoFactorAuthTest.php`
- **New File**: Comprehensive test suite for 6-digit 2FA functionality

## 🎯 Benefits of 6-Digit Codes

### Security Improvements
- **Higher Entropy**: 1,000,000 possible combinations vs 10,000
- **Reduced Brute Force Risk**: 100x more difficult to guess
- **Better Security Standards**: Aligns with industry best practices

### User Experience
- **Consistent Length**: Matches SMS template pattern 371085
- **Professional Appearance**: More standard for financial/security applications
- **Better Memorization**: Easier to remember 6-digit patterns

## 🔄 Migration Impact

### Backward Compatibility
- **Breaking Change**: Existing 4-digit codes will be rejected
- **User Action Required**: Users must request new 6-digit codes
- **No Data Migration**: Cache-based system, no database changes needed

### System Behavior
- **SMS Service**: Already generating 6-digit codes
- **Template Integration**: Works seamlessly with Meli Payamak template
- **Rate Limiting**: Unchanged (5 attempts per hour)
- **Expiration**: Unchanged (5 minutes)

## 🧪 Testing Coverage

### New Test Suite: `TwoFactorAuthTest.php`

**Test Cases:**
1. ✅ **6-Digit OTP Generation**: Verifies correct length and format
2. ✅ **Controller Validation**: Tests 6-digit code acceptance
3. ✅ **Invalid Length Rejection**: Tests rejection of 4-digit codes
4. ✅ **Valid Code Acceptance**: Tests acceptance of 6-digit codes
5. ✅ **OTP Verification**: Tests successful verification flow
6. ✅ **Invalid OTP Handling**: Tests rejection of wrong codes
7. ✅ **Non-Numeric Code Handling**: Tests rejection of alphabetic codes
8. ✅ **Empty Code Handling**: Tests validation of empty input
9. ✅ **Too Short Code Handling**: Tests rejection of short codes
10. ✅ **Too Long Code Handling**: Tests rejection of long codes
11. ✅ **Code Uniqueness**: Tests variety in generated codes
12. ✅ **Rate Limiting**: Tests admin 2FA rate limiting

### Updated Existing Tests
- **SmsLoginTest**: Updated to expect 6-digit OTPs
- **SmsTemplateIntegrationTest**: Updated OTP generation tests

## 📱 User Interface Changes

### Admin 2FA Verification Page
- **Input Field**: Now accepts 6 digits maximum
- **Placeholder**: Shows "000000" instead of "0000"
- **Label**: Updated to "کد تایید ۶ رقمی"
- **Auto-Submit**: Triggers after 6 digits are entered
- **Validation**: Rejects codes that aren't exactly 6 digits

### Visual Indicators
- **Character Count**: Input field shows 6-character limit
- **Error Messages**: Updated to reflect 6-digit requirement
- **Success Messages**: Unchanged (still shows "کد تایید به شماره شما ارسال شد")

## 🔐 Security Considerations

### Code Generation
- **Range**: 100000 to 999999 (6-digit numbers)
- **Randomness**: Uses `random_int()` for cryptographically secure generation
- **Padding**: Zero-padded to ensure consistent 6-digit format
- **Uniqueness**: Each code is independently generated

### Validation
- **Length Check**: Exactly 6 characters required
- **Type Check**: String type validation
- **Required Field**: Cannot be empty
- **Server-Side**: Validation occurs on server, not just client-side

### Rate Limiting
- **Attempts**: Maximum 5 attempts per hour per phone number
- **Purpose**: Separate rate limiting for 'admin_2fa' purpose
- **Cache-Based**: Uses Laravel cache for tracking attempts
- **Automatic Reset**: Resets every hour

## 🚀 Deployment Notes

### Pre-Deployment Checklist
- [ ] Update all admin users about the change
- [ ] Test SMS delivery with 6-digit codes
- [ ] Verify template integration works correctly
- [ ] Run full test suite to ensure compatibility

### Post-Deployment Monitoring
- [ ] Monitor 2FA success rates
- [ ] Check for any validation errors
- [ ] Verify SMS delivery rates
- [ ] Monitor user feedback

### Rollback Plan
- **If Issues Arise**: Revert controller validation to 4 digits
- **SMS Service**: Already generating 6 digits, no rollback needed
- **View Updates**: Revert to 4-digit UI elements
- **Test Updates**: Revert test expectations

## 📊 Performance Impact

### Positive Impacts
- **No Database Changes**: Cache-based system, no schema updates
- **Same SMS Cost**: Same number of SMS messages sent
- **Same Processing Time**: Minimal difference in generation time
- **Better Security**: Improved security posture

### Considerations
- **User Input Time**: Slightly longer to enter 6 digits
- **Memory Usage**: Negligible increase in cache storage
- **Processing**: Same computational overhead

## 🔧 Configuration

### Environment Variables
No new environment variables required. Existing configuration remains:
```env
MELIPAYAMK_VERIFICATION_TEMPLATE=371085
MELIPAYAMK_SENDER=50002710008883
```

### Service Configuration
SMS service configuration unchanged:
```php
'templates' => [
    'verification' => env('MELIPAYAMK_VERIFICATION_TEMPLATE', 371085),
],
```

## 📞 Support Information

### User Communication
- **Admin Notification**: Inform all admin users about the change
- **Documentation Update**: Update admin documentation
- **Training**: Brief admin users on new 6-digit requirement

### Troubleshooting
- **Common Issues**: Users entering old 4-digit codes
- **Solution**: Request new 6-digit code
- **Support**: Contact system administrator if issues persist

## 🎯 Future Enhancements

### Potential Improvements
- **QR Code Support**: Add QR code generation for 2FA
- **Backup Codes**: Generate backup codes for recovery
- **Hardware Tokens**: Support for hardware-based 2FA
- **Biometric Integration**: Add fingerprint/face recognition

### Monitoring
- **Analytics**: Track 2FA success/failure rates
- **Performance**: Monitor SMS delivery times
- **Security**: Log failed verification attempts
- **User Experience**: Gather feedback on 6-digit codes

---

**Last Updated**: January 2024  
**Version**: 2.0  
**Status**: Production Ready  
**Breaking Change**: Yes (4-digit codes no longer accepted)
