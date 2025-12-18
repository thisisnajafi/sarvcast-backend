# Flutter App Version Control Implementation Guide

## Overview
This document provides comprehensive guidance for implementing app version control in Flutter, including optional and forced updates. The implementation should match the existing application UI design patterns and provide a seamless user experience.

## Table of Contents
1. [Architecture Overview](#architecture-overview)
2. [API Integration](#api-integration)
3. [UI Components Design](#ui-components-design)
4. [Implementation Prompts](#implementation-prompts)
5. [Code Examples](#code-examples)
6. [Testing Guidelines](#testing-guidelines)

---

## Architecture Overview

### Core Components
- **VersionService**: Handles API communication and version checking
- **UpdateDialog**: Displays update prompts to users
- **VersionModel**: Data model for version information
- **UpdateManager**: Manages update flow and user preferences

### Update Types
- **Optional Update**: User can choose to update or continue using current version
- **Forced Update**: User must update to continue using the app
- **Maintenance Update**: App is temporarily unavailable for maintenance

---

## API Integration

### Endpoint
```
GET /api/app/version-check
```

### Request Parameters
```dart
{
  "platform": "android" | "ios",
  "current_version": "1.0.0",
  "build_number": "100"
}
```

### Response Format
```dart
{
  "success": true,
  "data": {
    "version": "1.1.0",
    "build_number": "110",
    "platform": "android",
    "update_type": "optional" | "forced" | "maintenance",
    "title": "نسخه جدید موجود است",
    "description": "به‌روزرسانی جدید با ویژگی‌های بهبود یافته",
    "changelog": "• بهبود عملکرد\n• رفع باگ‌ها\n• ویژگی‌های جدید",
    "update_notes": "لطفاً قبل از به‌روزرسانی از اطلاعات خود پشتیبان تهیه کنید",
    "download_url": "https://play.google.com/store/apps/details?id=com.sarvcast.app",
    "minimum_os_version": "Android 7.0",
    "is_active": true,
    "is_latest": true,
    "release_date": "2024-01-15",
    "force_update_date": "2024-02-15",
    "priority": 80,
    "compatibility": ["android", "ios"],
    "metadata": {
      "file_size": "25.5 MB",
      "estimated_download_time": "2-3 minutes"
    }
  }
}
```

---

## UI Components Design

### Design Principles
- **Consistent with App Theme**: Use existing color scheme and typography
- **Persian RTL Support**: All text and layouts should support RTL
- **Accessibility**: Ensure proper contrast and screen reader support
- **Responsive Design**: Adapt to different screen sizes
- **Material Design**: Follow Material Design 3 guidelines

### Color Scheme
```dart
// Primary colors matching the app theme
const Color primaryColor = Color(0xFF3B82F6); // Blue
const Color successColor = Color(0xFF10B981); // Green
const Color warningColor = Color(0xFFF59E0B); // Amber
const Color errorColor = Color(0xFFEF4444);   // Red
const Color surfaceColor = Color(0xFFF8FAFC); // Light gray
const Color onSurfaceColor = Color(0xFF1E293B); // Dark gray
```

### Typography
```dart
// Persian font family
const String fontFamily = 'Vazir'; // or your preferred Persian font

// Text styles
const TextStyle headingStyle = TextStyle(
  fontFamily: fontFamily,
  fontSize: 20,
  fontWeight: FontWeight.bold,
  color: onSurfaceColor,
);

const TextStyle bodyStyle = TextStyle(
  fontFamily: fontFamily,
  fontSize: 16,
  color: onSurfaceColor,
);

const TextStyle captionStyle = TextStyle(
  fontFamily: fontFamily,
  fontSize: 14,
  color: onSurfaceColor.withOpacity(0.7),
);
```

---

## Implementation Prompts

### Prompt 1: Version Service Implementation
```
Create a Flutter service class called VersionService that:

1. Makes HTTP requests to the version check API endpoint
2. Handles network errors gracefully with proper error messages
3. Implements caching to avoid excessive API calls
4. Supports both Android and iOS platforms
5. Returns a VersionModel object with all version information
6. Includes proper logging for debugging
7. Handles timeout scenarios (30 seconds max)
8. Uses the existing app's HTTP client configuration
9. Implements retry logic for failed requests (max 3 retries)
10. Supports offline mode detection

The service should be a singleton and integrate with the existing dependency injection system.
```

### Prompt 2: Version Model Implementation
```
Create a Flutter data model called VersionModel that:

1. Maps JSON response to Dart object using json_annotation
2. Includes all fields from the API response
3. Implements proper type safety with nullable fields where appropriate
4. Includes helper methods for:
   - Checking if update is required
   - Getting formatted release date
   - Determining update urgency level
   - Validating compatibility with current device
5. Supports serialization/deserialization
6. Includes validation for required fields
7. Handles different update types (optional, forced, maintenance)
8. Provides formatted strings for UI display
9. Includes comparison methods for version checking
10. Supports metadata parsing and access
```

### Prompt 3: Update Dialog UI Component
```
Design a Flutter dialog widget called UpdateDialog that:

1. Matches the app's existing UI design language
2. Supports RTL layout for Persian text
3. Displays different layouts based on update type:
   - Optional: Shows "Update" and "Later" buttons
   - Forced: Shows only "Update" button
   - Maintenance: Shows "OK" button with maintenance message
4. Includes proper spacing and padding (16px margins)
5. Uses Material Design 3 components (Material 3 buttons, cards)
6. Implements proper accessibility labels
7. Supports different screen sizes (phone, tablet)
8. Includes loading states for download initiation
9. Shows progress indicator for forced updates
10. Handles dialog dismissal properly (only for optional updates)

Visual elements should include:
- App icon (24x24dp)
- Title with proper typography
- Description text with scrollable content
- Changelog section with bullet points
- Update notes section (if available)
- File size and download time estimates
- Action buttons with proper styling
- Close button (X) for optional updates only
```

### Prompt 4: Update Manager Implementation
```
Create a Flutter manager class called UpdateManager that:

1. Integrates with VersionService to check for updates
2. Manages update dialog display logic
3. Handles user preferences for update checking frequency
4. Implements background update checking
5. Manages app lifecycle events (app resume, foreground)
6. Stores update check timestamps to avoid spam
7. Handles different update scenarios:
   - App launch update check
   - Background update check
   - Manual update check from settings
8. Implements proper state management
9. Handles edge cases (no internet, API errors)
10. Provides callbacks for update events
11. Integrates with existing analytics system
12. Supports different update policies (immediate, scheduled)
```

### Prompt 5: Settings Integration
```
Create a Flutter settings page section for version management that:

1. Displays current app version and build number
2. Shows last update check time
3. Provides manual "Check for Updates" button
4. Includes toggle for automatic update checking
5. Shows update frequency options (daily, weekly, monthly)
6. Displays update history (if available)
7. Includes "About" section with app information
8. Provides feedback/complaint submission option
9. Shows system information (OS version, device model)
10. Includes proper navigation and back button handling

The page should:
- Use existing app's settings page layout
- Follow Material Design guidelines
- Support RTL layout
- Include proper spacing and typography
- Handle loading states for manual checks
- Show success/error messages appropriately
- Integrate with existing theme system
```

### Prompt 6: Update Flow State Management
```
Implement state management for the update flow using Provider/Riverpod that:

1. Manages update check state (idle, checking, success, error)
2. Stores version information globally
3. Handles update dialog visibility state
4. Manages user update preferences
5. Tracks update check history
6. Handles different update scenarios
7. Provides reactive UI updates
8. Manages loading states across the app
9. Handles error states with proper error messages
10. Supports offline/online state detection
11. Integrates with existing app state management
12. Provides proper state persistence

The state should include:
- Current app version
- Latest available version
- Update check status
- User preferences
- Error messages
- Loading indicators
- Update dialog state
```

### Prompt 7: Error Handling and User Feedback
```
Implement comprehensive error handling for version control that:

1. Handles network connectivity issues
2. Manages API server errors (500, 404, etc.)
3. Handles invalid response formats
4. Manages timeout scenarios
5. Provides user-friendly error messages in Persian
6. Implements retry mechanisms
7. Logs errors for debugging
8. Shows appropriate UI feedback
9. Handles edge cases gracefully
10. Provides fallback behaviors

Error scenarios to handle:
- No internet connection
- Server unavailable
- Invalid API response
- Parsing errors
- Timeout errors
- Permission issues
- Storage space issues
- App store connectivity issues
```

### Prompt 8: Testing Implementation
```
Create comprehensive tests for version control functionality:

1. Unit tests for VersionService
2. Unit tests for VersionModel
3. Widget tests for UpdateDialog
4. Integration tests for update flow
5. Mock tests for API responses
6. Error scenario testing
7. UI component testing
8. State management testing
9. Performance testing
10. Accessibility testing

Test cases should cover:
- Successful update check
- Network error scenarios
- Invalid response handling
- Different update types
- User interaction flows
- State transitions
- Error recovery
- Edge cases
- Performance benchmarks
- Accessibility compliance
```

---

## Code Examples

### VersionService Implementation
```dart
class VersionService {
  static final VersionService _instance = VersionService._internal();
  factory VersionService() => _instance;
  VersionService._internal();

  final Dio _dio = Dio();
  final String _baseUrl = 'https://my.sarvcast.ir/api';

  Future<VersionModel?> checkForUpdates() async {
    try {
      final response = await _dio.get(
        '$_baseUrl/app/version-check',
        queryParameters: {
          'platform': Platform.isAndroid ? 'android' : 'ios',
          'current_version': await _getCurrentVersion(),
          'build_number': await _getCurrentBuildNumber(),
        },
        options: Options(
          timeout: Duration(seconds: 30),
          headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
          },
        ),
      );

      if (response.statusCode == 200 && response.data['success']) {
        return VersionModel.fromJson(response.data['data']);
      }
      return null;
    } catch (e) {
      debugPrint('Version check error: $e');
      return null;
    }
  }

  Future<String> _getCurrentVersion() async {
    PackageInfo packageInfo = await PackageInfo.fromPlatform();
    return packageInfo.version;
  }

  Future<String> _getCurrentBuildNumber() async {
    PackageInfo packageInfo = await PackageInfo.fromPlatform();
    return packageInfo.buildNumber;
  }
}
```

### UpdateDialog Widget
```dart
class UpdateDialog extends StatelessWidget {
  final VersionModel version;
  final VoidCallback? onUpdate;
  final VoidCallback? onLater;
  final VoidCallback? onClose;

  const UpdateDialog({
    Key? key,
    required this.version,
    this.onUpdate,
    this.onLater,
    this.onClose,
  }) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return Directionality(
      textDirection: TextDirection.rtl,
      child: AlertDialog(
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(16),
        ),
        title: Row(
          children: [
            Icon(
              Icons.system_update,
              color: Theme.of(context).primaryColor,
              size: 24,
            ),
            SizedBox(width: 12),
            Expanded(
              child: Text(
                version.title,
                style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                  fontFamily: 'Vazir',
                  fontWeight: FontWeight.bold,
                ),
              ),
            ),
          ],
        ),
        content: SingleChildScrollView(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                version.description,
                style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                  fontFamily: 'Vazir',
                ),
              ),
              if (version.changelog.isNotEmpty) ...[
                SizedBox(height: 16),
                Text(
                  'تغییرات:',
                  style: Theme.of(context).textTheme.titleSmall?.copyWith(
                    fontFamily: 'Vazir',
                    fontWeight: FontWeight.bold,
                  ),
                ),
                SizedBox(height: 8),
                Text(
                  version.changelog,
                  style: Theme.of(context).textTheme.bodySmall?.copyWith(
                    fontFamily: 'Vazir',
                  ),
                ),
              ],
              if (version.updateNotes.isNotEmpty) ...[
                SizedBox(height: 16),
                Container(
                  padding: EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: Theme.of(context).primaryColor.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Row(
                    children: [
                      Icon(
                        Icons.info_outline,
                        color: Theme.of(context).primaryColor,
                        size: 20,
                      ),
                      SizedBox(width: 8),
                      Expanded(
                        child: Text(
                          version.updateNotes,
                          style: Theme.of(context).textTheme.bodySmall?.copyWith(
                            fontFamily: 'Vazir',
                            color: Theme.of(context).primaryColor,
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ],
          ),
        ),
        actions: [
          if (version.updateType == UpdateType.optional) ...[
            TextButton(
              onPressed: onLater,
              child: Text(
                'بعداً',
                style: TextStyle(fontFamily: 'Vazir'),
              ),
            ),
            if (onClose != null)
              IconButton(
                onPressed: onClose,
                icon: Icon(Icons.close),
              ),
          ],
          ElevatedButton(
            onPressed: onUpdate,
            style: ElevatedButton.styleFrom(
              backgroundColor: Theme.of(context).primaryColor,
              foregroundColor: Colors.white,
            ),
            child: Text(
              version.updateType == UpdateType.maintenance ? 'متوجه شدم' : 'به‌روزرسانی',
              style: TextStyle(fontFamily: 'Vazir'),
            ),
          ),
        ],
      ),
    );
  }
}
```

### UpdateManager Implementation
```dart
class UpdateManager {
  static final UpdateManager _instance = UpdateManager._internal();
  factory UpdateManager() => _instance;
  UpdateManager._internal();

  final VersionService _versionService = VersionService();
  final SharedPreferences _prefs = await SharedPreferences.getInstance();

  Future<void> checkForUpdates({bool showDialog = true}) async {
    try {
      final version = await _versionService.checkForUpdates();
      if (version != null && _shouldShowUpdate(version)) {
        if (showDialog) {
          _showUpdateDialog(version);
        }
      }
    } catch (e) {
      debugPrint('Update check failed: $e');
    }
  }

  bool _shouldShowUpdate(VersionModel version) {
    final lastCheck = _prefs.getInt('last_update_check') ?? 0;
    final now = DateTime.now().millisecondsSinceEpoch;
    
    // Don't show update dialog more than once per day for optional updates
    if (version.updateType == UpdateType.optional) {
      return (now - lastCheck) > Duration(days: 1).inMilliseconds;
    }
    
    return true; // Always show forced updates
  }

  void _showUpdateDialog(VersionModel version) {
    // Implementation depends on your navigation system
    // This is a simplified example
    Get.dialog(
      UpdateDialog(
        version: version,
        onUpdate: () => _handleUpdate(version),
        onLater: () => _handleLater(),
        onClose: () => Get.back(),
      ),
      barrierDismissible: version.updateType == UpdateType.optional,
    );
  }

  void _handleUpdate(VersionModel version) {
    if (version.updateType == UpdateType.maintenance) {
      Get.back();
      return;
    }
    
    // Launch app store or download URL
    _launchUpdateUrl(version.downloadUrl);
  }

  void _handleLater() {
    Get.back();
    _prefs.setInt('last_update_check', DateTime.now().millisecondsSinceEpoch);
  }

  Future<void> _launchUpdateUrl(String url) async {
    try {
      await launchUrl(Uri.parse(url));
    } catch (e) {
      debugPrint('Failed to launch update URL: $e');
    }
  }
}
```

---

## Testing Guidelines

### Unit Tests
```dart
void main() {
  group('VersionService Tests', () {
    test('should return VersionModel when API call succeeds', () async {
      // Mock API response
      // Test successful version check
    });

    test('should handle network errors gracefully', () async {
      // Mock network error
      // Test error handling
    });
  });

  group('VersionModel Tests', () {
    test('should parse JSON response correctly', () {
      // Test JSON parsing
    });

    test('should handle missing fields gracefully', () {
      // Test nullable fields
    });
  });
}
```

### Widget Tests
```dart
void main() {
  group('UpdateDialog Tests', () {
    testWidgets('should display correct content for optional update', (tester) async {
      // Test optional update dialog
    });

    testWidgets('should display correct content for forced update', (tester) async {
      // Test forced update dialog
    });
  });
}
```

---

## Integration Checklist

- [ ] VersionService implemented with proper error handling
- [ ] VersionModel created with all required fields
- [ ] UpdateDialog designed with RTL support
- [ ] UpdateManager integrated with app lifecycle
- [ ] Settings page includes version management
- [ ] State management implemented
- [ ] Error handling covers all scenarios
- [ ] Tests written and passing
- [ ] UI matches app design language
- [ ] Accessibility requirements met
- [ ] Performance optimized
- [ ] Documentation completed

---

## Notes

1. **RTL Support**: Ensure all text and layouts support right-to-left reading
2. **Persian Fonts**: Use appropriate Persian font family throughout
3. **Material Design**: Follow Material Design 3 guidelines
4. **Accessibility**: Include proper accessibility labels and support
5. **Performance**: Implement caching and optimize API calls
6. **Error Handling**: Provide user-friendly error messages in Persian
7. **Testing**: Write comprehensive tests for all components
8. **Documentation**: Keep documentation updated with code changes

This implementation guide provides a comprehensive foundation for implementing app version control in Flutter while maintaining consistency with your existing application design and user experience.
