# SarvCast Flutter Forced Update Integration Guide

## Overview
This document provides comprehensive guidance for Flutter developers to implement forced app updates using the SarvCast backend system. It covers version checking, forced update handling, and user experience management.

## Base Configuration

### API Base URL
```
https://my.sarvcast.ir/api/v1
```

### Required Headers
```dart
Map<String, String> headers = {
  'Content-Type': 'application/json',
  'Accept': 'application/json',
  'User-Agent': 'SarvCast-Flutter/1.0.0',
};
```

## Version Management API

### 1. Check for Updates

**Route:** `POST /version/check`

**Purpose:** Check if an app update is required

**Request Body:**
```json
{
  "platform": "android",
  "current_version": "1.0.0",
  "current_build_number": "100"
}
```

**Flutter Implementation:**
```dart
class VersionService {
  static const String baseUrl = 'https://my.sarvcast.ir/api/v1';
  
  Future<VersionCheckResult> checkForUpdates({
    required String platform,
    required String currentVersion,
    String? currentBuildNumber,
  }) async {
    final uri = Uri.parse('$baseUrl/version/check');
    
    final body = {
      'platform': platform,
      'current_version': currentVersion,
      if (currentBuildNumber != null) 'current_build_number': currentBuildNumber,
    };
    
    final response = await http.post(
      uri,
      headers: headers,
      body: json.encode(body),
    );
    
    if (response.statusCode == 200) {
      final data = json.decode(response.body);
      if (data['success'] == true) {
        return VersionCheckResult.fromJson(data);
      }
    }
    
    throw Exception('Failed to check for updates');
  }
}
```

**Expected Response (Update Required):**
```json
{
  "success": true,
  "update_required": true,
  "force_update": true,
  "data": {
    "latest_version": "2.0.0",
    "latest_build_number": "200",
    "download_url": "https://play.google.com/store/apps/details?id=com.sarvcast.app",
    "update_message": "نسخه جدید با ویژگی‌های جذاب منتشر شد!",
    "release_notes": "• بهبود عملکرد\n• رفع مشکلات\n• ویژگی‌های جدید",
    "update_type": "force",
    "platform": "android"
  }
}
```

**Expected Response (No Update Required):**
```json
{
  "success": true,
  "update_required": false,
  "force_update": false,
  "data": {
    "latest_version": "1.0.0",
    "latest_build_number": "100"
  }
}
```

### 2. Get Latest Version Information

**Route:** `GET /version/latest`

**Purpose:** Get the latest version information for a platform

**Parameters:**
- `platform` (required): "android" or "ios"

**Flutter Implementation:**
```dart
Future<LatestVersionInfo> getLatestVersion({
  required String platform,
}) async {
  final uri = Uri.parse('$baseUrl/version/latest').replace(
    queryParameters: {'platform': platform},
  );
  
  final response = await http.get(uri, headers: headers);
  
  if (response.statusCode == 200) {
    final data = json.decode(response.body);
    if (data['success'] == true) {
      return LatestVersionInfo.fromJson(data['data']);
    }
  }
  
  throw Exception('Failed to get latest version');
}
```

**Expected Response:**
```json
{
  "success": true,
  "data": {
    "version": "2.0.0",
    "build_number": "200",
    "platform": "android",
    "download_url": "https://play.google.com/store/apps/details?id=com.sarvcast.app",
    "update_message": "نسخه جدید با ویژگی‌های جذاب منتشر شد!",
    "release_notes": "• بهبود عملکرد\n• رفع مشکلات\n• ویژگی‌های جدید",
    "update_type": "force",
    "release_date": "2024-01-15T10:30:00Z",
    "effective_date": "2024-01-15T10:30:00Z"
  }
}
```

### 3. Get App Configuration

**Route:** `GET /version/config`

**Purpose:** Get app configuration including version information

**Flutter Implementation:**
```dart
Future<AppConfig> getAppConfig() async {
  final uri = Uri.parse('$baseUrl/version/config');
  
  final response = await http.get(uri, headers: headers);
  
  if (response.statusCode == 200) {
    final data = json.decode(response.body);
    if (data['success'] == true) {
      return AppConfig.fromJson(data['data']);
    }
  }
  
  throw Exception('Failed to get app config');
}
```

**Expected Response:**
```json
{
  "success": true,
  "data": {
    "app_name": "SarvCast",
    "app_version": "1.0.0",
    "app_build": "1",
    "min_supported_version": "1.0.0",
    "update_check_url": "https://my.sarvcast.ir/api/v1/version/check",
    "support_email": "support@sarvcast.com",
    "support_phone": "021-12345678",
    "website_url": "https://sarvcast.com",
    "privacy_policy_url": "https://sarvcast.com/privacy",
    "terms_of_service_url": "https://sarvcast.com/terms"
  }
}
```

## Data Models

### VersionCheckResult Model
```dart
class VersionCheckResult {
  final bool success;
  final bool updateRequired;
  final bool forceUpdate;
  final VersionInfo? versionInfo;

  VersionCheckResult({
    required this.success,
    required this.updateRequired,
    required this.forceUpdate,
    this.versionInfo,
  });

  factory VersionCheckResult.fromJson(Map<String, dynamic> json) {
    return VersionCheckResult(
      success: json['success'] ?? false,
      updateRequired: json['update_required'] ?? false,
      forceUpdate: json['force_update'] ?? false,
      versionInfo: json['data'] != null ? VersionInfo.fromJson(json['data']) : null,
    );
  }
}
```

### VersionInfo Model
```dart
class VersionInfo {
  final String latestVersion;
  final String? latestBuildNumber;
  final String downloadUrl;
  final String? updateMessage;
  final String? releaseNotes;
  final String updateType;
  final String platform;

  VersionInfo({
    required this.latestVersion,
    this.latestBuildNumber,
    required this.downloadUrl,
    this.updateMessage,
    this.releaseNotes,
    required this.updateType,
    required this.platform,
  });

  factory VersionInfo.fromJson(Map<String, dynamic> json) {
    return VersionInfo(
      latestVersion: json['latest_version'] ?? '',
      latestBuildNumber: json['latest_build_number'],
      downloadUrl: json['download_url'] ?? '',
      updateMessage: json['update_message'],
      releaseNotes: json['release_notes'],
      updateType: json['update_type'] ?? 'optional',
      platform: json['platform'] ?? '',
    );
  }

  bool get isForceUpdate => updateType == 'force';
  bool get isOptionalUpdate => updateType == 'optional';
}
```

### LatestVersionInfo Model
```dart
class LatestVersionInfo {
  final String version;
  final String? buildNumber;
  final String platform;
  final String downloadUrl;
  final String? updateMessage;
  final String? releaseNotes;
  final String updateType;
  final DateTime? releaseDate;
  final DateTime? effectiveDate;

  LatestVersionInfo({
    required this.version,
    this.buildNumber,
    required this.platform,
    required this.downloadUrl,
    this.updateMessage,
    this.releaseNotes,
    required this.updateType,
    this.releaseDate,
    this.effectiveDate,
  });

  factory LatestVersionInfo.fromJson(Map<String, dynamic> json) {
    return LatestVersionInfo(
      version: json['version'] ?? '',
      buildNumber: json['build_number'],
      platform: json['platform'] ?? '',
      downloadUrl: json['download_url'] ?? '',
      updateMessage: json['update_message'],
      releaseNotes: json['release_notes'],
      updateType: json['update_type'] ?? 'optional',
      releaseDate: json['release_date'] != null ? DateTime.parse(json['release_date']) : null,
      effectiveDate: json['effective_date'] != null ? DateTime.parse(json['effective_date']) : null,
    );
  }

  bool get isForceUpdate => updateType == 'force';
  bool get isOptionalUpdate => updateType == 'optional';
}
```

### AppConfig Model
```dart
class AppConfig {
  final String appName;
  final String appVersion;
  final String appBuild;
  final String minSupportedVersion;
  final String updateCheckUrl;
  final String supportEmail;
  final String supportPhone;
  final String websiteUrl;
  final String privacyPolicyUrl;
  final String termsOfServiceUrl;

  AppConfig({
    required this.appName,
    required this.appVersion,
    required this.appBuild,
    required this.minSupportedVersion,
    required this.updateCheckUrl,
    required this.supportEmail,
    required this.supportPhone,
    required this.websiteUrl,
    required this.privacyPolicyUrl,
    required this.termsOfServiceUrl,
  });

  factory AppConfig.fromJson(Map<String, dynamic> json) {
    return AppConfig(
      appName: json['app_name'] ?? '',
      appVersion: json['app_version'] ?? '',
      appBuild: json['app_build'] ?? '',
      minSupportedVersion: json['min_supported_version'] ?? '',
      updateCheckUrl: json['update_check_url'] ?? '',
      supportEmail: json['support_email'] ?? '',
      supportPhone: json['support_phone'] ?? '',
      websiteUrl: json['website_url'] ?? '',
      privacyPolicyUrl: json['privacy_policy_url'] ?? '',
      termsOfServiceUrl: json['terms_of_service_url'] ?? '',
    );
  }
}
```

## Flutter Implementation

### Version Check Service
```dart
class VersionCheckService {
  static const String baseUrl = 'https://my.sarvcast.ir/api/v1';
  final VersionService _versionService = VersionService();
  
  /// Check for app updates
  Future<VersionCheckResult> checkForUpdates() async {
    try {
      final packageInfo = await PackageInfo.fromPlatform();
      final platform = Platform.isAndroid ? 'android' : 'ios';
      
      return await _versionService.checkForUpdates(
        platform: platform,
        currentVersion: packageInfo.version,
        currentBuildNumber: packageInfo.buildNumber,
      );
    } catch (e) {
      throw Exception('Failed to check for updates: $e');
    }
  }
  
  /// Get latest version info
  Future<LatestVersionInfo> getLatestVersion() async {
    try {
      final platform = Platform.isAndroid ? 'android' : 'ios';
      return await _versionService.getLatestVersion(platform: platform);
    } catch (e) {
      throw Exception('Failed to get latest version: $e');
    }
  }
  
  /// Get app configuration
  Future<AppConfig> getAppConfig() async {
    try {
      return await _versionService.getAppConfig();
    } catch (e) {
      throw Exception('Failed to get app config: $e');
    }
  }
}
```

### Forced Update Widget
```dart
class ForcedUpdateWidget extends StatefulWidget {
  final VersionInfo versionInfo;
  final VoidCallback? onUpdatePressed;
  final VoidCallback? onLaterPressed;

  const ForcedUpdateWidget({
    Key? key,
    required this.versionInfo,
    this.onUpdatePressed,
    this.onLaterPressed,
  }) : super(key: key);

  @override
  _ForcedUpdateWidgetState createState() => _ForcedUpdateWidgetState();
}

class _ForcedUpdateWidgetState extends State<ForcedUpdateWidget> {
  @override
  Widget build(BuildContext context) {
    return WillPopScope(
      onWillPop: () async => widget.versionInfo.isForceUpdate,
      child: Scaffold(
        backgroundColor: Colors.white,
        body: SafeArea(
          child: Padding(
            padding: const EdgeInsets.all(24.0),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                // App Icon
                Container(
                  width: 120,
                  height: 120,
                  decoration: BoxDecoration(
                    color: Colors.blue.shade50,
                    borderRadius: BorderRadius.circular(20),
                  ),
                  child: Icon(
                    Icons.system_update,
                    size: 60,
                    color: Colors.blue,
                  ),
                ),
                
                SizedBox(height: 32),
                
                // Title
                Text(
                  'نسخه جدید منتشر شد!',
                  style: TextStyle(
                    fontSize: 24,
                    fontWeight: FontWeight.bold,
                    color: Colors.grey[800],
                  ),
                  textAlign: TextAlign.center,
                ),
                
                SizedBox(height: 16),
                
                // Version Info
                Container(
                  padding: EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    color: Colors.grey[50],
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(color: Colors.grey[200]!),
                  ),
                  child: Column(
                    children: [
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Text('نسخه فعلی:', style: TextStyle(color: Colors.grey[600])),
                          Text('1.0.0', style: TextStyle(fontWeight: FontWeight.bold)),
                        ],
                      ),
                      SizedBox(height: 8),
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Text('نسخه جدید:', style: TextStyle(color: Colors.grey[600])),
                          Text(
                            widget.versionInfo.latestVersion,
                            style: TextStyle(
                              fontWeight: FontWeight.bold,
                              color: Colors.green,
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
                
                SizedBox(height: 24),
                
                // Update Message
                if (widget.versionInfo.updateMessage != null) ...[
                  Text(
                    widget.versionInfo.updateMessage!,
                    style: TextStyle(
                      fontSize: 16,
                      color: Colors.grey[700],
                    ),
                    textAlign: TextAlign.center,
                  ),
                  SizedBox(height: 24),
                ],
                
                // Release Notes
                if (widget.versionInfo.releaseNotes != null) ...[
                  Container(
                    padding: EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      color: Colors.blue.shade50,
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'تغییرات جدید:',
                          style: TextStyle(
                            fontWeight: FontWeight.bold,
                            color: Colors.blue[800],
                          ),
                        ),
                        SizedBox(height: 8),
                        Text(
                          widget.versionInfo.releaseNotes!,
                          style: TextStyle(
                            color: Colors.blue[700],
                            height: 1.5,
                          ),
                        ),
                      ],
                    ),
                  ),
                  SizedBox(height: 32),
                ],
                
                // Update Button
                SizedBox(
                  width: double.infinity,
                  height: 56,
                  child: ElevatedButton(
                    onPressed: widget.onUpdatePressed ?? _openDownloadPage,
                    style: ElevatedButton.styleFrom(
                      backgroundColor: Colors.blue,
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12),
                      ),
                    ),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Icon(Icons.download, color: Colors.white),
                        SizedBox(width: 8),
                        Text(
                          'بروزرسانی',
                          style: TextStyle(
                            fontSize: 18,
                            fontWeight: FontWeight.bold,
                            color: Colors.white,
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
                
                // Later Button (only for optional updates)
                if (!widget.versionInfo.isForceUpdate) ...[
                  SizedBox(height: 16),
                  TextButton(
                    onPressed: widget.onLaterPressed,
                    child: Text(
                      'بعداً',
                      style: TextStyle(
                        fontSize: 16,
                        color: Colors.grey[600],
                      ),
                    ),
                  ),
                ],
              ],
            ),
          ),
        ),
      ),
    );
  }
  
  void _openDownloadPage() {
    final url = widget.versionInfo.downloadUrl;
    if (url.isNotEmpty) {
      // Use url_launcher to open the download page
      launch(url);
    }
  }
}
```

### Optional Update Widget
```dart
class OptionalUpdateWidget extends StatelessWidget {
  final VersionInfo versionInfo;
  final VoidCallback? onUpdatePressed;
  final VoidCallback? onLaterPressed;

  const OptionalUpdateWidget({
    Key? key,
    required this.versionInfo,
    this.onUpdatePressed,
    this.onLaterPressed,
  }) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return AlertDialog(
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(16),
      ),
      title: Row(
        children: [
          Icon(Icons.system_update, color: Colors.blue),
          SizedBox(width: 8),
          Text('بروزرسانی موجود'),
        ],
      ),
      content: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'نسخه جدید ${versionInfo.latestVersion} منتشر شده است.',
            style: TextStyle(fontSize: 16),
          ),
          
          if (versionInfo.updateMessage != null) ...[
            SizedBox(height: 12),
            Text(
              versionInfo.updateMessage!,
              style: TextStyle(
                color: Colors.grey[600],
                fontSize: 14,
              ),
            ),
          ],
          
          if (versionInfo.releaseNotes != null) ...[
            SizedBox(height: 12),
            Container(
              padding: EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: Colors.blue.shade50,
                borderRadius: BorderRadius.circular(8),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'تغییرات:',
                    style: TextStyle(
                      fontWeight: FontWeight.bold,
                      color: Colors.blue[800],
                    ),
                  ),
                  SizedBox(height: 4),
                  Text(
                    versionInfo.releaseNotes!,
                    style: TextStyle(
                      color: Colors.blue[700],
                      fontSize: 12,
                    ),
                  ),
                ],
              ),
            ),
          ],
        ],
      ),
      actions: [
        TextButton(
          onPressed: onLaterPressed,
          child: Text('بعداً'),
        ),
        ElevatedButton(
          onPressed: onUpdatePressed ?? _openDownloadPage,
          child: Text('بروزرسانی'),
        ),
      ],
    );
  }
  
  void _openDownloadPage() {
    final url = versionInfo.downloadUrl;
    if (url.isNotEmpty) {
      launch(url);
    }
  }
}
```

### App Update Manager
```dart
class AppUpdateManager {
  static final AppUpdateManager _instance = AppUpdateManager._internal();
  factory AppUpdateManager() => _instance;
  AppUpdateManager._internal();
  
  final VersionCheckService _versionService = VersionCheckService();
  bool _isChecking = false;
  
  /// Check for updates and show appropriate dialog
  Future<void> checkForUpdates({
    bool showOptionalDialog = true,
    bool showForceDialog = true,
  }) async {
    if (_isChecking) return;
    
    try {
      _isChecking = true;
      
      final result = await _versionService.checkForUpdates();
      
      if (result.updateRequired) {
        if (result.versionInfo != null) {
          if (result.forceUpdate && showForceDialog) {
            _showForceUpdateDialog(result.versionInfo!);
          } else if (!result.forceUpdate && showOptionalDialog) {
            _showOptionalUpdateDialog(result.versionInfo!);
          }
        }
      }
    } catch (e) {
      print('Error checking for updates: $e');
    } finally {
      _isChecking = false;
    }
  }
  
  /// Show forced update dialog
  void _showForceUpdateDialog(VersionInfo versionInfo) {
    // Get the current context
    final context = navigatorKey.currentContext;
    if (context == null) return;
    
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (context) => ForcedUpdateWidget(
        versionInfo: versionInfo,
        onUpdatePressed: () {
          Navigator.of(context).pop();
          _openDownloadPage(versionInfo.downloadUrl);
        },
      ),
    );
  }
  
  /// Show optional update dialog
  void _showOptionalUpdateDialog(VersionInfo versionInfo) {
    // Get the current context
    final context = navigatorKey.currentContext;
    if (context == null) return;
    
    showDialog(
      context: context,
      builder: (context) => OptionalUpdateWidget(
        versionInfo: versionInfo,
        onUpdatePressed: () {
          Navigator.of(context).pop();
          _openDownloadPage(versionInfo.downloadUrl);
        },
        onLaterPressed: () {
          Navigator.of(context).pop();
        },
      ),
    );
  }
  
  /// Open download page
  void _openDownloadPage(String url) {
    if (url.isNotEmpty) {
      launch(url);
    }
  }
  
  /// Check for updates on app start
  Future<void> checkOnAppStart() async {
    // Wait a bit for the app to fully load
    await Future.delayed(Duration(seconds: 2));
    
    // Check for updates
    await checkForUpdates();
  }
  
  /// Check for updates periodically
  void startPeriodicCheck({Duration interval = const Duration(hours: 24)}) {
    Timer.periodic(interval, (timer) {
      checkForUpdates();
    });
  }
}
```

### Main App Integration
```dart
class MyApp extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'SarvCast',
      navigatorKey: navigatorKey,
      home: MyHomePage(),
    );
  }
}

class MyHomePage extends StatefulWidget {
  @override
  _MyHomePageState createState() => _MyHomePageState();
}

class _MyHomePageState extends State<MyHomePage> {
  final AppUpdateManager _updateManager = AppUpdateManager();
  
  @override
  void initState() {
    super.initState();
    
    // Check for updates when app starts
    _updateManager.checkOnAppStart();
    
    // Start periodic update checks
    _updateManager.startPeriodicCheck();
  }
  
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('SarvCast'),
        actions: [
          IconButton(
            icon: Icon(Icons.refresh),
            onPressed: () => _updateManager.checkForUpdates(),
            tooltip: 'Check for Updates',
          ),
        ],
      ),
      body: Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Text('Welcome to SarvCast!'),
            SizedBox(height: 20),
            ElevatedButton(
              onPressed: () => _updateManager.checkForUpdates(),
              child: Text('Check for Updates'),
            ),
          ],
        ),
      ),
    );
  }
}
```

## Error Handling

### Version Check Error Handling
```dart
class VersionCheckException implements Exception {
  final String message;
  final int? statusCode;
  
  VersionCheckException(this.message, [this.statusCode]);
  
  @override
  String toString() => 'VersionCheckException: $message';
}

Future<T> _handleVersionCheckCall<T>(Future<T> Function() apiCall) async {
  try {
    return await apiCall();
  } on SocketException {
    throw VersionCheckException('خطا در اتصال به اینترنت');
  } on TimeoutException {
    throw VersionCheckException('زمان اتصال به سرور به پایان رسید');
  } on FormatException {
    throw VersionCheckException('خطا در فرمت داده‌های دریافتی');
  } on VersionCheckException {
    rethrow;
  } catch (e) {
    throw VersionCheckException('خطای نامشخص: ${e.toString()}');
  }
}
```

## Testing

### Unit Tests
```dart
void main() {
  group('VersionCheckService', () {
    test('should check for updates successfully', () async {
      // Mock HTTP response
      when(mockHttpClient.post(any, headers: anyNamed('headers'), body: anyNamed('body')))
          .thenAnswer((_) async => http.Response(
                jsonEncode({
                  'success': true,
                  'update_required': true,
                  'force_update': true,
                  'data': {
                    'latest_version': '2.0.0',
                    'latest_build_number': '200',
                    'download_url': 'https://play.google.com/store/apps/details?id=com.sarvcast.app',
                    'update_message': 'نسخه جدید منتشر شد!',
                    'release_notes': '• بهبود عملکرد\n• رفع مشکلات',
                    'update_type': 'force',
                    'platform': 'android'
                  }
                }),
                200,
              ));
      
      final service = VersionCheckService();
      final result = await service.checkForUpdates();
      
      expect(result.updateRequired, true);
      expect(result.forceUpdate, true);
      expect(result.versionInfo?.latestVersion, '2.0.0');
    });
  });
}
```

## Deployment Checklist

### Pre-deployment
- [ ] Test version check API endpoints
- [ ] Test forced update flow
- [ ] Test optional update flow
- [ ] Test error handling scenarios
- [ ] Verify download URL functionality
- [ ] Test on both Android and iOS
- [ ] Verify app store links

### Post-deployment
- [ ] Monitor version check success rates
- [ ] Track update adoption rates
- [ ] Monitor error rates
- [ ] Collect user feedback
- [ ] Monitor API performance
- [ ] Track forced update effectiveness

## Troubleshooting

### Common Issues

1. **Version Check Failing**
   - Check network connectivity
   - Verify API endpoint URL
   - Check request parameters
   - Monitor error logs

2. **Download Page Not Opening**
   - Verify download URL
   - Check url_launcher configuration
   - Test on different devices
   - Check app store availability

3. **Update Dialog Not Showing**
   - Check app state
   - Verify context availability
   - Check dialog conditions
   - Monitor error logs

## Support

For technical support or update issues, contact the development team.

**Document Version:** 1.0  
**Last Updated:** January 2024  
**Next Review:** February 2024
