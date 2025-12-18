# CafeBazaar Environment Configuration Guide

## Overview
This guide explains how to configure your Laravel application for CafeBazaar in-app purchase verification.

## Required Environment Variables

Add these variables to your `.env` file:

```env
# =================================================================
# CAFEBAZAAR CONFIGURATION - REQUIRED FOR IN-APP PURCHASES
# =================================================================

# Your app's package name in CafeBazaar (usually ir.yourcompany.appname)
CAFEBAZAAR_PACKAGE_NAME=ir.sarvcast.app

# API Key from CafeBazaar Developer Panel
# Get this from: https://pardakht.cafebazaar.ir/ > Your App > Payment Settings
CAFEBAZAAR_API_KEY=your_cafebazaar_api_key_here

# CafeBazaar API URLs (usually don't need to change)
CAFEBAZAAR_API_URL=https://pardakht.cafebazaar.ir/devapi/v2/api/validate
CAFEBAZAAR_SUBSCRIPTION_API_URL=https://pardakht.cafebazaar.ir/devapi/v2/api/validate/subscription
CAFEBAZAAR_ACKNOWLEDGE_URL=https://pardakht.cafebazaar.ir/devapi/v2/api/acknowledge
```

## How to Get CafeBazaar API Key

1. **Login to CafeBazaar Developer Panel**
   - Go to: https://pardakht.cafebazaar.ir/
   - Login with your developer account

2. **Select Your App**
   - Choose the app you want to configure
   - Navigate to "Payment Settings" or "تنظیمات پرداخت"

3. **Copy API Credentials**
   - Copy the "API Key" (کلید API)
   - Verify the "Package Name" matches your app's package name

4. **Update .env File**
   - Set `CAFEBAZAAR_API_KEY` to your API key
   - Set `CAFEBAZAAR_PACKAGE_NAME` to your app's package name

## Verification

After configuration, test the setup by:

1. **Check Configuration Loading**
   ```bash
   php artisan tinker
   ```
   ```php
   config('services.cafebazaar.api_key') // Should show your API key
   config('services.cafebazaar.package_name') // Should show your package name
   ```

2. **Test API Connection** (Optional)
   You can create a simple test route to verify CafeBazaar API connectivity.

## Security Notes

- ✅ **Never commit .env file** to version control
- ✅ **API keys are sensitive** - keep them secure
- ✅ **Use HTTPS** in production
- ✅ **Rotate keys periodically** for security

## Troubleshooting

### Common Issues:

1. **403 Forbidden on API calls**
   - Check if API key is correct
   - Verify package name matches CafeBazaar app

2. **Invalid purchase token**
   - Ensure tokens are from actual CafeBazaar purchases
   - Check if app is in production/sandbox mode

3. **Flavor validation errors**
   - Ensure requests include `billing_platform: 'cafebazaar'`
   - Check User-Agent headers if needed

## Next Steps

After configuration:

1. **Test Endpoints** - Use Postman to test CafeBazaar API endpoints
2. **Deploy Application** - Ensure .env is configured on production server
3. **Monitor Logs** - Check Laravel logs for any CafeBazaar-related errors

## Support

For issues with CafeBazaar integration:
- Check Laravel logs: `storage/logs/laravel.log`
- Verify API credentials in CafeBazaar developer panel
- Test with CafeBazaar sandbox environment first

---

**Last Updated**: December 2025
**Version**: 1.0.0
