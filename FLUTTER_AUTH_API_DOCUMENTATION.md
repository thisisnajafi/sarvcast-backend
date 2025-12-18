# Flutter Authentication API Documentation

## Overview
This document describes the authentication flow for the SarvCast mobile application. The API uses SMS-based verification with automatic user detection (new vs existing users).

## Base URL
```
https://your-domain.com/api
```

## Authentication Flow

### 1. Send Verification Code

**Endpoint:** `POST /auth/send-verification-code`

**Description:** Sends a 4-digit SMS verification code and detects if the user is new or existing.

**Request Body:**
```json
{
    "phone_number": "09123456789"
}
```

**Request Parameters:**
- `phone_number` (required): Iranian phone number in format `09xxxxxxxxx` or `+989xxxxxxxxx`

**Response (Success - New User):**
```json
{
    "success": true,
    "message": "کد تایید به شماره شما ارسال شد",
    "data": {
        "is_new_user": true,
        "expires_in": 300,
        "next_step": "registration"
    }
}
```

**Response (Success - Existing User):**
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

**Response (Error):**
```json
{
    "success": false,
    "message": "شماره تلفن نامعتبر است",
    "errors": {
        "phone_number": ["The phone number format is invalid."]
    }
}
```

**HTTP Status Codes:**
- `200`: Success
- `422`: Validation error
- `429`: Too many requests (rate limited)

---

### 2. Register New User

**Endpoint:** `POST /auth/register`

**Description:** Registers a new user after SMS verification.

**Request Body:**
```json
{
    "phone_number": "09123456789",
    "first_name": "علی",
    "last_name": "احمدی",
    "verification_code": "1234",
    "role": "parent",
    "parent_id": null
}
```

**Request Parameters:**
- `phone_number` (required): Iranian phone number
- `first_name` (required): User's first name (max 100 characters)
- `last_name` (required): User's last name (max 100 characters)
- `verification_code` (required): 4-digit SMS verification code
- `role` (required): User role (`parent` or `child`)
- `parent_id` (optional): Parent user ID (required if role is `child`)

**Response (Success):**
```json
{
    "success": true,
    "message": "ثبت‌نام با موفقیت انجام شد",
    "data": {
        "user": {
            "id": 1,
            "phone_number": "09123456789",
            "first_name": "علی",
            "last_name": "احمدی",
            "role": "parent",
            "status": "active",
            "phone_verified_at": "2024-01-01T12:00:00.000000Z",
            "created_at": "2024-01-01T12:00:00.000000Z",
            "updated_at": "2024-01-01T12:00:00.000000Z"
        },
        "token": "1|abcdef1234567890..."
    }
}
```

**Response (Error - User Already Exists):**
```json
{
    "success": false,
    "message": "کاربری با این شماره تلفن قبلاً ثبت شده است"
}
```

**Response (Error - Invalid Code):**
```json
{
    "success": false,
    "message": "کد تایید نامعتبر یا منقضی شده است"
}
```

**HTTP Status Codes:**
- `201`: Registration successful
- `400`: Invalid verification code
- `409`: User already exists
- `422`: Validation error

---

### 3. Login Existing User

**Endpoint:** `POST /auth/login`

**Description:** Logs in an existing user after SMS verification.

**Request Body:**
```json
{
    "phone_number": "09123456789",
    "verification_code": "1234"
}
```

**Request Parameters:**
- `phone_number` (required): Iranian phone number
- `verification_code` (required): 4-digit SMS verification code

**Response (Success):**
```json
{
    "success": true,
    "message": "ورود با موفقیت انجام شد",
    "data": {
        "user": {
            "id": 1,
            "phone_number": "09123456789",
            "first_name": "علی",
            "last_name": "احمدی",
            "role": "parent",
            "status": "active",
            "phone_verified_at": "2024-01-01T12:00:00.000000Z",
            "created_at": "2024-01-01T12:00:00.000000Z",
            "updated_at": "2024-01-01T12:00:00.000000Z"
        },
        "token": "1|abcdef1234567890..."
    }
}
```

**Response (Error - User Not Found):**
```json
{
    "success": false,
    "message": "کاربری با این شماره تلفن یافت نشد"
}
```

**Response (Error - Invalid Code):**
```json
{
    "success": false,
    "message": "کد تایید نامعتبر یا منقضی شده است"
}
```

**HTTP Status Codes:**
- `200`: Login successful
- `400`: Invalid verification code
- `404`: User not found
- `422`: Validation error

---

## Flutter Implementation Guide

### 1. Phone Number Input Screen

```dart
class PhoneInputScreen extends StatefulWidget {
  @override
  _PhoneInputScreenState createState() => _PhoneInputScreenState();
}

class _PhoneInputScreenState extends State<PhoneInputScreen> {
  final _phoneController = TextEditingController();
  bool _isLoading = false;

  Future<void> _sendVerificationCode() async {
    if (_phoneController.text.isEmpty) return;
    
    setState(() => _isLoading = true);
    
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/auth/send-verification-code'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({
          'phone_number': _phoneController.text,
        }),
      );
      
      final data = jsonDecode(response.body);
      
      if (data['success']) {
        // Navigate to verification screen with user type
        Navigator.pushReplacement(
          context,
          MaterialPageRoute(
            builder: (context) => VerificationScreen(
              phoneNumber: _phoneController.text,
              isNewUser: data['data']['is_new_user'],
              nextStep: data['data']['next_step'],
            ),
          ),
        );
      } else {
        // Show error message
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(data['message'])),
        );
      }
    } catch (e) {
      // Handle network error
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('خطا در ارتباط با سرور')),
      );
    } finally {
      setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text('ورود / ثبت‌نام')),
      body: Padding(
        padding: EdgeInsets.all(16.0),
        child: Column(
          children: [
            TextField(
              controller: _phoneController,
              keyboardType: TextInputType.phone,
              decoration: InputDecoration(
                labelText: 'شماره تلفن',
                hintText: '09123456789',
                prefixText: '+98 ',
              ),
            ),
            SizedBox(height: 20),
            ElevatedButton(
              onPressed: _isLoading ? null : _sendVerificationCode,
              child: _isLoading 
                ? CircularProgressIndicator() 
                : Text('ارسال کد تایید'),
            ),
          ],
        ),
      ),
    );
  }
}
```

### 2. Verification Code Screen

```dart
class VerificationScreen extends StatefulWidget {
  final String phoneNumber;
  final bool isNewUser;
  final String nextStep;

  const VerificationScreen({
    Key? key,
    required this.phoneNumber,
    required this.isNewUser,
    required this.nextStep,
  }) : super(key: key);

  @override
  _VerificationScreenState createState() => _VerificationScreenState();
}

class _VerificationScreenState extends State<VerificationScreen> {
  final _codeController = TextEditingController();
  bool _isLoading = false;

  Future<void> _verifyCode() async {
    if (_codeController.text.length != 4) return;
    
    setState(() => _isLoading = true);
    
    try {
      if (widget.nextStep == 'registration') {
        await _registerUser();
      } else {
        await _loginUser();
      }
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('خطا در ارتباط با سرور')),
      );
    } finally {
      setState(() => _isLoading = false);
    }
  }

  Future<void> _registerUser() async {
    // Show name input dialog for new users
    final nameData = await _showNameInputDialog();
    if (nameData == null) return;

    final response = await http.post(
      Uri.parse('$baseUrl/auth/register'),
      headers: {'Content-Type': 'application/json'},
      body: jsonEncode({
        'phone_number': widget.phoneNumber,
        'first_name': nameData['firstName'],
        'last_name': nameData['lastName'],
        'verification_code': _codeController.text,
        'role': 'parent', // or get from user input
        'parent_id': null,
      }),
    );

    final data = jsonDecode(response.body);
    
    if (data['success']) {
      // Save token and navigate to main app
      await _saveUserData(data['data']);
      Navigator.pushReplacement(
        context,
        MaterialPageRoute(builder: (context) => MainScreen()),
      );
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(data['message'])),
      );
    }
  }

  Future<void> _loginUser() async {
    final response = await http.post(
      Uri.parse('$baseUrl/auth/login'),
      headers: {'Content-Type': 'application/json'},
      body: jsonEncode({
        'phone_number': widget.phoneNumber,
        'verification_code': _codeController.text,
      }),
    );

    final data = jsonDecode(response.body);
    
    if (data['success']) {
      // Save token and navigate to main app
      await _saveUserData(data['data']);
      Navigator.pushReplacement(
        context,
        MaterialPageRoute(builder: (context) => MainScreen()),
      );
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(data['message'])),
      );
    }
  }

  Future<Map<String, String>?> _showNameInputDialog() async {
    final firstNameController = TextEditingController();
    final lastNameController = TextEditingController();

    return showDialog<Map<String, String>>(
      context: context,
      builder: (context) => AlertDialog(
        title: Text('اطلاعات شخصی'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            TextField(
              controller: firstNameController,
              decoration: InputDecoration(labelText: 'نام'),
            ),
            TextField(
              controller: lastNameController,
              decoration: InputDecoration(labelText: 'نام خانوادگی'),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: Text('انصراف'),
          ),
          TextButton(
            onPressed: () {
              if (firstNameController.text.isNotEmpty && 
                  lastNameController.text.isNotEmpty) {
                Navigator.pop(context, {
                  'firstName': firstNameController.text,
                  'lastName': lastNameController.text,
                });
              }
            },
            child: Text('تایید'),
          ),
        ],
      ),
    );
  }

  Future<void> _saveUserData(Map<String, dynamic> data) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('auth_token', data['token']);
    await prefs.setString('user_data', jsonEncode(data['user']));
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text('تایید کد')),
      body: Padding(
        padding: EdgeInsets.all(16.0),
        child: Column(
          children: [
            Text('کد ۴ رقمی ارسال شده به ${widget.phoneNumber} را وارد کنید'),
            SizedBox(height: 20),
            TextField(
              controller: _codeController,
              keyboardType: TextInputType.number,
              maxLength: 4,
              textAlign: TextAlign.center,
              style: TextStyle(fontSize: 24, letterSpacing: 8),
              decoration: InputDecoration(
                hintText: '0000',
                counterText: '',
              ),
              onChanged: (value) {
                if (value.length == 4) {
                  _verifyCode();
                }
              },
            ),
            SizedBox(height: 20),
            ElevatedButton(
              onPressed: _isLoading ? null : _verifyCode,
              child: _isLoading 
                ? CircularProgressIndicator() 
                : Text('تایید کد'),
            ),
          ],
        ),
      ),
    );
  }
}
```

### 3. Token Management

```dart
class AuthService {
  static const String _tokenKey = 'auth_token';
  static const String _userKey = 'user_data';

  static Future<String?> getToken() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString(_tokenKey);
  }

  static Future<Map<String, dynamic>?> getUser() async {
    final prefs = await SharedPreferences.getInstance();
    final userData = prefs.getString(_userKey);
    if (userData != null) {
      return jsonDecode(userData);
    }
    return null;
  }

  static Future<void> saveAuthData(String token, Map<String, dynamic> user) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_tokenKey, token);
    await prefs.setString(_userKey, jsonEncode(user));
  }

  static Future<void> logout() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_tokenKey);
    await prefs.remove(_userKey);
  }

  static Future<bool> isLoggedIn() async {
    final token = await getToken();
    return token != null;
  }
}
```

### 4. HTTP Client with Token

```dart
class ApiClient {
  static const String baseUrl = 'https://your-domain.com/api';
  
  static Future<Map<String, String>> _getHeaders() async {
    final token = await AuthService.getToken();
    final headers = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    };
    
    if (token != null) {
      headers['Authorization'] = 'Bearer $token';
    }
    
    return headers;
  }

  static Future<http.Response> post(String endpoint, Map<String, dynamic> body) async {
    final headers = await _getHeaders();
    return http.post(
      Uri.parse('$baseUrl$endpoint'),
      headers: headers,
      body: jsonEncode(body),
    );
  }

  static Future<http.Response> get(String endpoint) async {
    final headers = await _getHeaders();
    return http.get(
      Uri.parse('$baseUrl$endpoint'),
      headers: headers,
    );
  }
}
```

## Error Handling

### Common Error Scenarios

1. **Invalid Phone Number Format**
   - Status: `422`
   - Message: "شماره تلفن نامعتبر است"

2. **Rate Limiting**
   - Status: `429`
   - Message: "تعداد درخواست‌های شما بیش از حد مجاز است"

3. **Invalid Verification Code**
   - Status: `400`
   - Message: "کد تایید نامعتبر یا منقضی شده است"

4. **User Already Exists (Registration)**
   - Status: `409`
   - Message: "کاربری با این شماره تلفن قبلاً ثبت شده است"

5. **User Not Found (Login)**
   - Status: `404`
   - Message: "کاربری با این شماره تلفن یافت نشد"

## Security Considerations

1. **Token Storage**: Store authentication tokens securely using Flutter's secure storage
2. **Rate Limiting**: Implement client-side rate limiting to prevent spam
3. **Code Expiration**: Verification codes expire after 5 minutes
4. **Phone Verification**: All users must verify their phone numbers

## Testing

### Test Phone Numbers
For development/testing, you can use these test phone numbers:
- `09123456789` - Test number 1
- `09987654321` - Test number 2

### Test Verification Codes
In development environment, you can use `1234` as a test verification code.

## Flow Diagram

```
User enters phone number
         ↓
Send verification code API
         ↓
    Check if user exists
         ↓
    ┌─────────┬─────────┐
    │ New User│Existing│
    │         │ User   │
    └─────────┴─────────┘
         ↓         ↓
   Show name input  Direct login
         ↓         ↓
   Register API    Login API
         ↓         ↓
    Save token &   Save token &
    navigate to    navigate to
    main app       main app
```

## Dependencies

Add these dependencies to your `pubspec.yaml`:

```yaml
dependencies:
  http: ^0.13.5
  shared_preferences: ^2.0.15
  flutter_secure_storage: ^9.0.0
```

## Notes

- All API responses are in Persian (Farsi)
- Phone numbers should be in Iranian format
- Verification codes are 4 digits
- Tokens should be included in Authorization header for authenticated requests
- The API automatically detects new vs existing users
