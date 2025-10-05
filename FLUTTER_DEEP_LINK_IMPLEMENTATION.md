# FLUTTER DEEP LINK IMPLEMENTATION GUIDE

## 📱 COMPLETE FLUTTER DEEP LINK SETUP

This guide provides a complete implementation for handling deep links from the SarvCast payment system in your Flutter application.

## 🔧 1. DEPENDENCIES

Add these dependencies to your `pubspec.yaml`:

```yaml
dependencies:
  flutter:
    sdk: flutter
  
  # Deep link handling
  app_links: ^3.4.2
  
  # URL launching (for fallback)
  url_launcher: ^6.2.1
  
  # JSON handling
  convert: ^3.1.1
  
  # State management (optional)
  provider: ^6.1.1
  # or
  # bloc: ^8.1.4
```

## 📱 2. PLATFORM CONFIGURATION

### Android Configuration

**File:** `android/app/src/main/AndroidManifest.xml`

```xml
<manifest xmlns:android="http://schemas.android.com/apk/res/android">
    <application>
        <activity
            android:name=".MainActivity"
            android:exported="true"
            android:launchMode="singleTop"
            android:theme="@style/LaunchTheme">
            
            <!-- Existing intent filters -->
            <intent-filter>
                <action android:name="android.intent.action.MAIN"/>
                <category android:name="android.intent.category.LAUNCHER"/>
            </intent-filter>
            
            <!-- Deep link intent filter -->
            <intent-filter android:autoVerify="true">
                <action android:name="android.intent.action.VIEW" />
                <category android:name="android.intent.category.DEFAULT" />
                <category android:name="android.intent.category.BROWSABLE" />
                <data android:scheme="sarvcast" />
            </intent-filter>
        </activity>
    </application>
</manifest>
```

### iOS Configuration

**File:** `ios/Runner/Info.plist`

```xml
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0">
<dict>
    <!-- Existing configuration -->
    
    <!-- Deep link configuration -->
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
</dict>
</plist>
```

## 🎯 3. DEEP LINK HANDLER SERVICE

**File:** `lib/services/deep_link_service.dart`

```dart
import 'dart:convert';
import 'package:app_links/app_links.dart';
import 'package:flutter/foundation.dart';

class DeepLinkService {
  static final DeepLinkService _instance = DeepLinkService._internal();
  factory DeepLinkService() => _instance;
  DeepLinkService._internal();

  final AppLinks _appLinks = AppLinks();
  bool _isInitialized = false;

  // Callbacks for different deep link events
  Function(Map<String, dynamic>)? onPaymentSuccess;
  Function(Map<String, dynamic>)? onPaymentFailure;
  Function(Map<String, dynamic>)? onSubscriptionSuccess;
  Function(Map<String, dynamic>)? onSubscriptionFailure;
  Function()? onNavigateToHome;

  /// Initialize the deep link service
  Future<void> initialize() async {
    if (_isInitialized) return;
    
    try {
      // Listen for incoming deep links
      _appLinks.uriLinkStream.listen(
        _handleDeepLink,
        onError: (error) {
          debugPrint('Deep link error: $error');
        },
      );
      
      // Handle initial link (if app was opened via deep link)
      final initialUri = await _appLinks.getInitialLink();
      if (initialUri != null) {
        _handleDeepLink(initialUri);
      }
      
      _isInitialized = true;
      debugPrint('Deep link service initialized');
    } catch (e) {
      debugPrint('Failed to initialize deep link service: $e');
    }
  }

  /// Handle incoming deep links
  void _handleDeepLink(Uri uri) {
    debugPrint('Received deep link: $uri');
    
    if (uri.scheme != 'sarvcast') {
      debugPrint('Invalid scheme: ${uri.scheme}');
      return;
    }

    try {
      final path = uri.path;
      final dataParam = uri.queryParameters['data'];
      
      if (dataParam != null) {
        final data = jsonDecode(Uri.decodeComponent(dataParam));
        _processDeepLinkData(path, data);
      } else {
        // Handle links without data parameter
        _processDeepLinkPath(path);
      }
    } catch (e) {
      debugPrint('Error processing deep link: $e');
    }
  }

  /// Process deep link data
  void _processDeepLinkData(String path, Map<String, dynamic> data) {
    debugPrint('Processing deep link: $path with data: $data');
    
    switch (path) {
      case '/payment/success':
        onPaymentSuccess?.call(data);
        break;
      case '/payment/failure':
        onPaymentFailure?.call(data);
        break;
      case '/subscription/success':
        onSubscriptionSuccess?.call(data);
        break;
      case '/subscription/failure':
        onSubscriptionFailure?.call(data);
        break;
      case '/home':
        onNavigateToHome?.call();
        break;
      default:
        // Default to home if path is not recognized
        onNavigateToHome?.call();
    }
  }

  /// Process deep link path without data
  void _processDeepLinkPath(String path) {
    debugPrint('Processing deep link path: $path');
    
    switch (path) {
      case '/home':
        onNavigateToHome?.call();
        break;
      default:
        onNavigateToHome?.call();
    }
  }

  /// Dispose the service
  void dispose() {
    _isInitialized = false;
  }
}
```

## 🎨 4. PAYMENT SUCCESS SCREEN

**File:** `lib/screens/payment_success_screen.dart`

```dart
import 'package:flutter/material.dart';

class PaymentSuccessScreen extends StatelessWidget {
  final Map<String, dynamic> paymentData;

  const PaymentSuccessScreen({
    Key? key,
    required this.paymentData,
  }) : super(key: key);

  @override
  Widget build(BuildContext context) {
    final paymentId = paymentData['payment_id']?.toString() ?? 'N/A';
    final subscriptionId = paymentData['subscription_id']?.toString() ?? 'N/A';
    final amount = paymentData['amount']?.toString() ?? 'N/A';
    final transactionId = paymentData['transaction_id']?.toString() ?? 'N/A';

    return Scaffold(
      backgroundColor: Colors.green.shade50,
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.all(24.0),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              // Success Animation
              Container(
                width: 120,
                height: 120,
                decoration: BoxDecoration(
                  color: Colors.green,
                  shape: BoxShape.circle,
                ),
                child: Icon(
                  Icons.check,
                  color: Colors.white,
                  size: 60,
                ),
              ),
              
              SizedBox(height: 32),
              
              // Success Message
              Text(
                'پرداخت با موفقیت انجام شد!',
                style: TextStyle(
                  fontSize: 24,
                  fontWeight: FontWeight.bold,
                  color: Colors.green.shade800,
                ),
                textAlign: TextAlign.center,
              ),
              
              SizedBox(height: 16),
              
              Text(
                'اشتراک شما فعال شد و می‌توانید از تمام امکانات استفاده کنید.',
                style: TextStyle(
                  fontSize: 16,
                  color: Colors.grey.shade700,
                ),
                textAlign: TextAlign.center,
              ),
              
              SizedBox(height: 48),
              
              // Payment Details Card
              Container(
                padding: EdgeInsets.all(20),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(12),
                  boxShadow: [
                    BoxShadow(
                      color: Colors.grey.shade200,
                      blurRadius: 8,
                      offset: Offset(0, 2),
                    ),
                  ],
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'جزئیات پرداخت',
                      style: TextStyle(
                        fontSize: 18,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    SizedBox(height: 16),
                    _buildDetailRow('شناسه پرداخت:', paymentId),
                    _buildDetailRow('شناسه اشتراک:', subscriptionId),
                    _buildDetailRow('مبلغ:', '${_formatAmount(amount)} ریال'),
                    _buildDetailRow('شناسه تراکنش:', transactionId),
                  ],
                ),
              ),
              
              SizedBox(height: 48),
              
              // Continue Button
              SizedBox(
                width: double.infinity,
                height: 56,
                child: ElevatedButton(
                  onPressed: () {
                    Navigator.pushNamedAndRemoveUntil(
                      context,
                      '/home',
                      (route) => false,
                    );
                  },
                  style: ElevatedButton.styleFrom(
                    backgroundColor: Colors.green,
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12),
                    ),
                  ),
                  child: Text(
                    'ادامه',
                    style: TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                      color: Colors.white,
                    ),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildDetailRow(String label, String value) {
    return Padding(
      padding: EdgeInsets.symmetric(vertical: 4),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Expanded(
            flex: 2,
            child: Text(
              label,
              style: TextStyle(
                fontSize: 14,
                color: Colors.grey.shade600,
              ),
            ),
          ),
          Expanded(
            flex: 3,
            child: Text(
              value,
              style: TextStyle(
                fontSize: 14,
                fontWeight: FontWeight.w500,
              ),
            ),
          ),
        ],
      ),
    );
  }

  String _formatAmount(String amount) {
    try {
      final num = int.parse(amount);
      return num.toString().replaceAllMapped(
        RegExp(r'(\d{1,3})(?=(\d{3})+(?!\d))'),
        (Match m) => '${m[1]},',
      );
    } catch (e) {
      return amount;
    }
  }
}
```

## ❌ 5. PAYMENT FAILURE SCREEN

**File:** `lib/screens/payment_failure_screen.dart`

```dart
import 'package:flutter/material.dart';

class PaymentFailureScreen extends StatelessWidget {
  final Map<String, dynamic> failureData;

  const PaymentFailureScreen({
    Key? key,
    required this.failureData,
  }) : super(key: key);

  @override
  Widget build(BuildContext context) {
    final error = failureData['error']?.toString() ?? 'خطای نامشخص';

    return Scaffold(
      backgroundColor: Colors.red.shade50,
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.all(24.0),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              // Failure Animation
              Container(
                width: 120,
                height: 120,
                decoration: BoxDecoration(
                  color: Colors.red,
                  shape: BoxShape.circle,
                ),
                child: Icon(
                  Icons.close,
                  color: Colors.white,
                  size: 60,
                ),
              ),
              
              SizedBox(height: 32),
              
              // Failure Message
              Text(
                'پرداخت ناموفق',
                style: TextStyle(
                  fontSize: 24,
                  fontWeight: FontWeight.bold,
                  color: Colors.red.shade800,
                ),
                textAlign: TextAlign.center,
              ),
              
              SizedBox(height: 16),
              
              Text(
                'متأسفانه پرداخت شما انجام نشد. لطفاً دوباره تلاش کنید.',
                style: TextStyle(
                  fontSize: 16,
                  color: Colors.grey.shade700,
                ),
                textAlign: TextAlign.center,
              ),
              
              SizedBox(height: 24),
              
              // Error Details Card
              Container(
                padding: EdgeInsets.all(20),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(12),
                  boxShadow: [
                    BoxShadow(
                      color: Colors.grey.shade200,
                      blurRadius: 8,
                      offset: Offset(0, 2),
                    ),
                  ],
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'جزئیات خطا',
                      style: TextStyle(
                        fontSize: 18,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    SizedBox(height: 16),
                    Text(
                      error,
                      style: TextStyle(
                        fontSize: 14,
                        color: Colors.red.shade700,
                      ),
                    ),
                  ],
                ),
              ),
              
              SizedBox(height: 48),
              
              // Action Buttons
              Row(
                children: [
                  Expanded(
                    child: SizedBox(
                      height: 56,
                      child: OutlinedButton(
                        onPressed: () {
                          Navigator.pushNamedAndRemoveUntil(
                            context,
                            '/subscription',
                            (route) => false,
                          );
                        },
                        style: OutlinedButton.styleFrom(
                          side: BorderSide(color: Colors.red),
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(12),
                          ),
                        ),
                        child: Text(
                          'تلاش مجدد',
                          style: TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.bold,
                            color: Colors.red,
                          ),
                        ),
                      ),
                    ),
                  ),
                  SizedBox(width: 16),
                  Expanded(
                    child: SizedBox(
                      height: 56,
                      child: ElevatedButton(
                        onPressed: () {
                          Navigator.pushNamedAndRemoveUntil(
                            context,
                            '/home',
                            (route) => false,
                          );
                        },
                        style: ElevatedButton.styleFrom(
                          backgroundColor: Colors.red,
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(12),
                          ),
                        ),
                        child: Text(
                          'بازگشت',
                          style: TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.bold,
                            color: Colors.white,
                          ),
                        ),
                      ),
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}
```

## 🏠 6. MAIN APP INTEGRATION

**File:** `lib/main.dart`

```dart
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'services/deep_link_service.dart';
import 'screens/payment_success_screen.dart';
import 'screens/payment_failure_screen.dart';

void main() {
  runApp(MyApp());
}

class MyApp extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'SarvCast',
      theme: ThemeData(
        primarySwatch: Colors.blue,
        fontFamily: 'IranSans', // Add Persian font
      ),
      home: AppInitializer(),
      routes: {
        '/home': (context) => HomeScreen(),
        '/subscription': (context) => SubscriptionScreen(),
        '/payment-success': (context) {
          final args = ModalRoute.of(context)!.settings.arguments as Map<String, dynamic>;
          return PaymentSuccessScreen(paymentData: args);
        },
        '/payment-failure': (context) {
          final args = ModalRoute.of(context)!.settings.arguments as Map<String, dynamic>;
          return PaymentFailureScreen(failureData: args);
        },
      },
    );
  }
}

class AppInitializer extends StatefulWidget {
  @override
  _AppInitializerState createState() => _AppInitializerState();
}

class _AppInitializerState extends State<AppInitializer> {
  @override
  void initState() {
    super.initState();
    _initializeDeepLinks();
  }

  void _initializeDeepLinks() {
    final deepLinkService = DeepLinkService();
    
    // Set up deep link callbacks
    deepLinkService.onPaymentSuccess = (data) {
      Navigator.pushNamed(
        context,
        '/payment-success',
        arguments: data,
      );
    };
    
    deepLinkService.onPaymentFailure = (data) {
      Navigator.pushNamed(
        context,
        '/payment-failure',
        arguments: data,
      );
    };
    
    deepLinkService.onSubscriptionSuccess = (data) {
      Navigator.pushNamed(
        context,
        '/payment-success',
        arguments: data,
      );
    };
    
    deepLinkService.onSubscriptionFailure = (data) {
      Navigator.pushNamed(
        context,
        '/payment-failure',
        arguments: data,
      );
    };
    
    deepLinkService.onNavigateToHome = () {
      Navigator.pushNamedAndRemoveUntil(
        context,
        '/home',
        (route) => false,
      );
    };
    
    // Initialize the service
    deepLinkService.initialize();
    
    // Navigate to home screen
    WidgetsBinding.instance.addPostFrameCallback((_) {
      Navigator.pushReplacementNamed(context, '/home');
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Center(
        child: CircularProgressIndicator(),
      ),
    );
  }
}

// Placeholder screens
class HomeScreen extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text('SarvCast')),
      body: Center(
        child: Text('Home Screen'),
      ),
    );
  }
}

class SubscriptionScreen extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text('Subscription')),
      body: Center(
        child: Text('Subscription Screen'),
      ),
    );
  }
}
```

## 🧪 7. TESTING DEEP LINKS

### Android Testing
```bash
adb shell am start \
  -W -a android.intent.action.VIEW \
  -d "sarvcast://payment/success?data=%7B%22success%22%3Atrue%2C%22payment_id%22%3A123%2C%22subscription_id%22%3A456%2C%22amount%22%3A160000%2C%22transaction_id%22%3A%22A000000000000000000000000000j1j3zqzz%22%7D" \
  com.yourpackage.sarvcast
```

### iOS Testing
```bash
xcrun simctl openurl booted "sarvcast://payment/success?data=%7B%22success%22%3Atrue%2C%22payment_id%22%3A123%2C%22subscription_id%22%3A456%2C%22amount%22%3A160000%2C%22transaction_id%22%3A%22A000000000000000000000000000j1j3zqzz%22%7D"
```

## 📋 8. DEEP LINK DATA STRUCTURE

### Success Data
```json
{
  "success": true,
  "payment_id": 123,
  "subscription_id": 456,
  "amount": 160000,
  "transaction_id": "A000000000000000000000000000j1j3zqzz",
  "timestamp": "2025-10-05T16:25:43.000Z"
}
```

### Failure Data
```json
{
  "success": false,
  "error": "پرداخت ناموفق",
  "timestamp": "2025-10-05T16:25:43.000Z"
}
```

## ✅ 9. IMPLEMENTATION CHECKLIST

- [ ] Add dependencies to `pubspec.yaml`
- [ ] Configure Android manifest
- [ ] Configure iOS Info.plist
- [ ] Create `DeepLinkService`
- [ ] Create `PaymentSuccessScreen`
- [ ] Create `PaymentFailureScreen`
- [ ] Integrate with main app
- [ ] Test deep links
- [ ] Handle edge cases
- [ ] Add error handling

## 🎯 10. SUPPORTED DEEP LINK SCHEMES

| **Scheme** | **Purpose** | **Data** |
|------------|-------------|----------|
| `sarvcast://payment/success` | Payment success | Payment details |
| `sarvcast://payment/failure` | Payment failure | Error details |
| `sarvcast://subscription/success` | Subscription success | Subscription details |
| `sarvcast://subscription/failure` | Subscription failure | Error details |
| `sarvcast://home` | Navigate to home | None |
| `sarvcast://` | Default fallback | None |

## 🚀 DEPLOYMENT NOTES

1. **Test thoroughly** on both Android and iOS
2. **Handle edge cases** like malformed URLs
3. **Add logging** for debugging
4. **Consider analytics** for deep link usage
5. **Update app store** descriptions with deep link support

**This implementation provides complete deep link support for the SarvCast payment system!** 🎉📱
