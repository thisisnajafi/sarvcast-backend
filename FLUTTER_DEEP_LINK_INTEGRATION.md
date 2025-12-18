# Flutter Deep Link Integration Guide

## Overview
The payment callback pages now include beautiful, responsive designs with Flutter deep link integration. Users can seamlessly return to your Flutter app after payment completion or failure.

## Payment Callback URLs

### Success Page
- **URL**: `https://my.sarvcast.ir/payment/success?payment_id={payment_id}`
- **Features**: 
  - ✅ Beautiful success animation with confetti
  - ✅ Payment details display
  - ✅ Subscription information
  - ✅ Return to app button
  - ✅ Auto-return after 30 seconds

### Failure Page
- **URL**: `https://my.sarvcast.ir/payment/failure`
- **Features**:
  - ✅ Encouraging failure message
  - ✅ Error details (if available)
  - ✅ Retry payment button
  - ✅ Return to app button
  - ✅ Support contact information

## Flutter Deep Link Setup

### 1. Configure Custom URL Scheme

In your Flutter app's platform-specific configuration:

#### Android (`android/app/src/main/AndroidManifest.xml`)
```xml
<activity
    android:name=".MainActivity"
    android:exported="true"
    android:launchMode="singleTop"
    android:theme="@style/LaunchTheme">
    
    <!-- Existing intent filters -->
    
    <!-- Add this intent filter for deep links -->
    <intent-filter android:autoVerify="true">
        <action android:name="android.intent.action.VIEW" />
        <category android:name="android.intent.category.DEFAULT" />
        <category android:name="android.intent.category.BROWSABLE" />
        <data android:scheme="sarvcast" />
    </intent-filter>
</activity>
```

#### iOS (`ios/Runner/Info.plist`)
```xml
<key>CFBundleURLTypes</key>
<array>
    <dict>
        <key>CFBundleURLName</key>
        <string>sarvcast</string>
        <key>CFBundleURLSchemes</key>
        <array>
            <string>sarvcast</string>
        </array>
    </dict>
</array>
```

### 2. Handle Deep Links in Flutter

#### Install Dependencies
```yaml
dependencies:
  app_links: ^3.4.2
  url_launcher: ^6.2.1
```

#### Deep Link Handler
```dart
import 'package:app_links/app_links.dart';
import 'dart:convert';

class PaymentDeepLinkHandler {
  static final AppLinks _appLinks = AppLinks();
  
  static void initialize() {
    _appLinks.uriLinkStream.listen((Uri uri) {
      handleDeepLink(uri);
    });
  }
  
  static void handleDeepLink(Uri uri) {
    if (uri.scheme == 'sarvcast') {
      final path = uri.path;
      final dataParam = uri.queryParameters['data'];
      
      if (dataParam != null) {
        try {
          final data = jsonDecode(Uri.decodeComponent(dataParam));
          handlePaymentCallback(path, data);
        } catch (e) {
          print('Error parsing deep link data: $e');
        }
      }
    }
  }
  
  static void handlePaymentCallback(String path, Map<String, dynamic> data) {
    switch (path) {
      case '/payment/success':
        _handlePaymentSuccess(data);
        break;
      case '/payment/failure':
        _handlePaymentFailure(data);
        break;
      case '/subscription/success':
        _handleSubscriptionSuccess(data);
        break;
      case '/subscription/failure':
        _handleSubscriptionFailure(data);
        break;
      case '/home':
        _navigateToHome();
        break;
      default:
        _navigateToHome();
    }
  }
  
  static void _handlePaymentSuccess(Map<String, dynamic> data) {
    // Navigate to success screen or show success dialog
    final paymentId = data['payment_id'];
    final subscriptionId = data['subscription_id'];
    final amount = data['amount'];
    final transactionId = data['transaction_id'];
    
    // Example navigation
    // Navigator.pushNamed(context, '/payment-success', arguments: {
    //   'paymentId': paymentId,
    //   'subscriptionId': subscriptionId,
    //   'amount': amount,
    //   'transactionId': transactionId,
    // });
    
    print('Payment Success: $data');
  }
  
  static void _handlePaymentFailure(Map<String, dynamic> data) {
    // Navigate to failure screen or show error dialog
    final error = data['error'];
    
    // Example navigation
    // Navigator.pushNamed(context, '/payment-failure', arguments: {
    //   'error': error,
    // });
    
    print('Payment Failure: $data');
  }
  
  static void _handleSubscriptionSuccess(Map<String, dynamic> data) {
    // Handle subscription success
    _handlePaymentSuccess(data);
  }
  
  static void _handleSubscriptionFailure(Map<String, dynamic> data) {
    // Handle subscription failure
    _handlePaymentFailure(data);
  }
  
  static void _navigateToHome() {
    // Navigate to home screen
    // Navigator.pushNamedAndRemoveUntil(context, '/home', (route) => false);
    print('Navigating to home');
  }
}
```

#### Initialize in main.dart
```dart
import 'package:flutter/material.dart';
import 'payment_deep_link_handler.dart';

void main() {
  runApp(MyApp());
  
  // Initialize deep link handler
  PaymentDeepLinkHandler.initialize();
}

class MyApp extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'SarvCast',
      // ... your app configuration
    );
  }
}
```

### 3. Payment Data Structure

#### Success Data
```json
{
  "success": true,
  "payment_id": 123,
  "subscription_id": 456,
  "amount": 160000,
  "transaction_id": "SUB_1759661474_3840",
  "timestamp": "2025-10-05T14:21:14.000Z"
}
```

#### Failure Data
```json
{
  "success": false,
  "error": "پرداخت ناموفق - موجودی کافی نیست",
  "timestamp": "2025-10-05T14:21:14.000Z"
}
```

## Testing Deep Links

### Android Testing
```bash
# Test success callback
adb shell am start -W -a android.intent.action.VIEW -d "sarvcast://payment/success?data=%7B%22success%22%3Atrue%2C%22payment_id%22%3A123%7D" com.your.package.name

# Test failure callback
adb shell am start -W -a android.intent.action.VIEW -d "sarvcast://payment/failure?data=%7B%22success%22%3Afalse%2C%22error%22%3A%22Test%20error%22%7D" com.your.package.name
```

### iOS Testing
```bash
# Test success callback
xcrun simctl openurl booted "sarvcast://payment/success?data=%7B%22success%22%3Atrue%2C%22payment_id%22%3A123%7D"

# Test failure callback
xcrun simctl openurl booted "sarvcast://payment/failure?data=%7B%22success%22%3Afalse%2C%22error%22%3A%22Test%20error%22%7D"
```

## Payment Flow Integration

### 1. Initiate Payment
```dart
// In your Flutter app
Future<void> initiatePayment() async {
  final response = await http.post(
    Uri.parse('https://my.sarvcast.ir/api/v1/subscriptions'),
    headers: {
      'Authorization': 'Bearer $token',
      'Content-Type': 'application/json',
    },
    body: jsonEncode({
      'plan_id': 1,
      'payment_method': 'zarinpal',
      'coupon_code': 'WELCOME20',
    }),
  );
  
  if (response.statusCode == 200) {
    final data = jsonDecode(response.body);
    final paymentUrl = data['data']['payment_url'];
    
    // Open payment URL in browser
    await launchUrl(Uri.parse(paymentUrl));
  }
}
```

### 2. Handle Return from Payment
The deep link handler will automatically catch the return from the payment page and navigate to the appropriate screen in your app.

## Customization Options

### 1. Custom URL Schemes
You can modify the URL schemes in the payment pages by editing:
- `resources/views/payment/success.blade.php` (line 173-176)
- `resources/views/payment/failure.blade.php` (line 174-177)

### 2. Custom Data Parameters
Modify the data structure in the JavaScript functions to include additional information your app needs.

### 3. Auto-Return Timing
Change the auto-return timeout in the success page (currently 30 seconds):
```javascript
setTimeout(() => {
    returnToApp();
}, 30000); // Change this value
```

## Security Considerations

1. **Validate Payment Data**: Always verify payment data on your backend before processing
2. **Use HTTPS**: Ensure all payment URLs use HTTPS
3. **Token Validation**: Validate user tokens in payment callbacks
4. **Error Handling**: Implement proper error handling for failed deep links

## Support

For any issues with the payment integration:
- 📧 Email: support@sarvcast.ir
- 📱 Telegram: @sarvcast_support
- 🕐 Hours: 9 AM to 6 PM
