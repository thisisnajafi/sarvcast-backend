# SarvCast Flutter Payment Integration Guide

## 📱 Overview

This document provides comprehensive guidance for integrating SarvCast's payment system with Flutter applications. The system includes subscription plans, Zarinpal payment gateway, and coupon code functionality.

## 🏗️ System Architecture

### Payment Flow
```
Flutter App → Laravel API → Zarinpal Gateway → Payment Processing → Subscription Activation
```

### Key Components
- **Subscription Plans**: 1 month, 3 months, 6 months, 1 year
- **Payment Gateway**: Zarinpal (Primary)
- **Coupon System**: Discount codes with commission tracking
- **Currency**: Iranian Rial (IRR)

---

## 🔧 API Endpoints

### Base URL
```
https://your-domain.com/api/v1
```

### Authentication
All payment-related endpoints require Bearer token authentication:
```dart
headers: {
  'Authorization': 'Bearer $token',
  'Content-Type': 'application/json',
  'Accept': 'application/json'
}
```

---

## 📋 Subscription Plans

### Get Available Plans
```http
GET /subscriptions/plans
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "اشتراک یک ماهه",
      "slug": "1month",
      "description": "دسترسی کامل به تمام محتوا",
      "duration_days": 30,
      "price": 50000.00,
      "currency": "IRR",
      "discount_percentage": 0,
      "is_active": true,
      "is_featured": false,
      "features": [
        "دسترسی به تمام داستان‌ها",
        "اپیزودهای پولی",
        "بدون تبلیغات",
        "دانلود آفلاین"
      ],
      "formatted_price": "50,000 IRR",
      "final_price": 50000.00,
      "formatted_final_price": "50,000 IRR"
    },
    {
      "id": 2,
      "name": "اشتراک سه‌ماهه",
      "slug": "3months",
      "description": "دسترسی کامل با تخفیف ویژه",
      "duration_days": 90,
      "price": 135000.00,
      "currency": "IRR",
      "discount_percentage": 10,
      "is_active": true,
      "is_featured": true,
      "features": [
        "دسترسی به تمام داستان‌ها",
        "اپیزودهای پولی",
        "بدون تبلیغات",
        "دانلود آفلاین",
        "10% تخفیف ویژه"
      ],
      "formatted_price": "135,000 IRR",
      "final_price": 121500.00,
      "formatted_final_price": "121,500 IRR"
    }
  ]
}
```

### Flutter Implementation
```dart
class SubscriptionService {
  static const String baseUrl = 'https://your-domain.com/api/v1';
  
  Future<List<SubscriptionPlan>> getPlans() async {
    final response = await http.get(
      Uri.parse('$baseUrl/subscriptions/plans'),
      headers: await _getHeaders(),
    );
    
    if (response.statusCode == 200) {
      final data = json.decode(response.body);
      return (data['data'] as List)
          .map((plan) => SubscriptionPlan.fromJson(plan))
          .toList();
    }
    throw Exception('Failed to load subscription plans');
  }
}

class SubscriptionPlan {
  final int id;
  final String name;
  final String slug;
  final String description;
  final int durationDays;
  final double price;
  final String currency;
  final int discountPercentage;
  final bool isActive;
  final bool isFeatured;
  final List<String> features;
  final String formattedPrice;
  final double finalPrice;
  final String formattedFinalPrice;

  SubscriptionPlan({
    required this.id,
    required this.name,
    required this.slug,
    required this.description,
    required this.durationDays,
    required this.price,
    required this.currency,
    required this.discountPercentage,
    required this.isActive,
    required this.isFeatured,
    required this.features,
    required this.formattedPrice,
    required this.finalPrice,
    required this.formattedFinalPrice,
  });

  factory SubscriptionPlan.fromJson(Map<String, dynamic> json) {
    return SubscriptionPlan(
      id: json['id'],
      name: json['name'],
      slug: json['slug'],
      description: json['description'],
      durationDays: json['duration_days'],
      price: json['price'].toDouble(),
      currency: json['currency'],
      discountPercentage: json['discount_percentage'],
      isActive: json['is_active'],
      isFeatured: json['is_featured'],
      features: List<String>.from(json['features']),
      formattedPrice: json['formatted_price'],
      finalPrice: json['final_price'].toDouble(),
      formattedFinalPrice: json['formatted_final_price'],
    );
  }
}
```

---

## 💳 Payment Processing

### 1. Create Subscription
```http
POST /subscriptions
```

**Request Body:**
```json
{
  "plan_slug": "1month",
  "coupon_code": "WELCOME10" // Optional
}
```

**Response:**
```json
{
  "success": true,
  "message": "اشتراک با موفقیت ایجاد شد",
  "data": {
    "subscription": {
      "id": 123,
      "user_id": 456,
      "type": "1month",
      "amount": 50000.00,
      "currency": "IRR",
      "status": "pending",
      "start_date": null,
      "end_date": null,
      "created_at": "2024-01-15T10:30:00Z"
    },
    "payment": {
      "id": 789,
      "user_id": 456,
      "subscription_id": 123,
      "amount": 50000.00,
      "currency": "IRR",
      "payment_method": "zarinpal",
      "status": "pending",
      "transaction_id": "A000000000000000000000000000000000000",
      "description": "پرداخت اشتراک 1month",
      "created_at": "2024-01-15T10:30:00Z"
    },
    "payment_url": "https://www.zarinpal.com/pg/StartPay/A000000000000000000000000000000000000",
    "authority": "A000000000000000000000000000000000000"
  }
}
```

### 2. Initiate Payment
```http
POST /payments/initiate
```

**Request Body:**
```json
{
  "subscription_id": 123
}
```

**Response:**
```json
{
  "success": true,
  "message": "درخواست پرداخت با موفقیت ایجاد شد",
  "data": {
    "payment_url": "https://www.zarinpal.com/pg/StartPay/A000000000000000000000000000000000000",
    "authority": "A000000000000000000000000000000000000",
    "amount": 50000,
    "currency": "IRR"
  }
}
```

### 3. Verify Payment
```http
POST /payments/verify
```

**Request Body:**
```json
{
  "authority": "A000000000000000000000000000000000000",
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
      "id": 789,
      "status": "completed",
      "paid_at": "2024-01-15T10:35:00Z",
      "ref_id": "1234567890123456"
    },
    "subscription": {
      "id": 123,
      "status": "active",
      "start_date": "2024-01-15T10:35:00Z",
      "end_date": "2024-02-14T10:35:00Z"
    }
  }
}
```

### Flutter Payment Implementation
```dart
class PaymentService {
  static const String baseUrl = 'https://your-domain.com/api/v1';
  
  Future<PaymentResult> initiatePayment(int subscriptionId) async {
    final response = await http.post(
      Uri.parse('$baseUrl/payments/initiate'),
      headers: await _getHeaders(),
      body: json.encode({
        'subscription_id': subscriptionId,
      }),
    );
    
    if (response.statusCode == 200) {
      final data = json.decode(response.body);
      return PaymentResult.fromJson(data['data']);
    }
    throw Exception('Failed to initiate payment');
  }
  
  Future<PaymentVerification> verifyPayment(String authority, String status) async {
    final response = await http.post(
      Uri.parse('$baseUrl/payments/verify'),
      headers: await _getHeaders(),
      body: json.encode({
        'authority': authority,
        'status': status,
      }),
    );
    
    if (response.statusCode == 200) {
      final data = json.decode(response.body);
      return PaymentVerification.fromJson(data['data']);
    }
    throw Exception('Failed to verify payment');
  }
}

class PaymentResult {
  final String paymentUrl;
  final String authority;
  final int amount;
  final String currency;

  PaymentResult({
    required this.paymentUrl,
    required this.authority,
    required this.amount,
    required this.currency,
  });

  factory PaymentResult.fromJson(Map<String, dynamic> json) {
    return PaymentResult(
      paymentUrl: json['payment_url'],
      authority: json['authority'],
      amount: json['amount'],
      currency: json['currency'],
    );
  }
}

class PaymentVerification {
  final Payment payment;
  final Subscription subscription;

  PaymentVerification({
    required this.payment,
    required this.subscription,
  });

  factory PaymentVerification.fromJson(Map<String, dynamic> json) {
    return PaymentVerification(
      payment: Payment.fromJson(json['payment']),
      subscription: Subscription.fromJson(json['subscription']),
    );
  }
}
```

### WebView Payment Integration
```dart
import 'package:webview_flutter/webview_flutter.dart';

class PaymentWebView extends StatefulWidget {
  final String paymentUrl;
  final Function(String authority, String status) onPaymentComplete;

  const PaymentWebView({
    Key? key,
    required this.paymentUrl,
    required this.onPaymentComplete,
  }) : super(key: key);

  @override
  _PaymentWebViewState createState() => _PaymentWebViewState();
}

class _PaymentWebViewState extends State<PaymentWebView> {
  late WebViewController _controller;
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _controller = WebViewController()
      ..setJavaScriptMode(JavaScriptMode.unrestricted)
      ..setNavigationDelegate(
        NavigationDelegate(
          onPageStarted: (String url) {
            setState(() {
              _isLoading = true;
            });
          },
          onPageFinished: (String url) {
            setState(() {
              _isLoading = false;
            });
          },
          onNavigationRequest: (NavigationRequest request) {
            // Handle Zarinpal callback (NOT webhook - user is redirected back)
            if (request.url.contains('callback') || request.url.contains('return')) {
              final uri = Uri.parse(request.url);
              final authority = uri.queryParameters['Authority'];
              final status = uri.queryParameters['Status'];
              
              if (authority != null && status != null) {
                widget.onPaymentComplete(authority, status);
                return NavigationDecision.prevent;
              }
            }
            return NavigationDecision.navigate;
          },
        ),
      )
      ..loadRequest(Uri.parse(widget.paymentUrl));
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('پرداخت'),
        backgroundColor: Colors.blue,
      ),
      body: Stack(
        children: [
          WebViewWidget(controller: _controller),
          if (_isLoading)
            Center(
              child: CircularProgressIndicator(),
            ),
        ],
      ),
    );
  }
}
```

---

## 🎫 Coupon Codes

### 1. Validate Coupon
```http
POST /coupons/validate
```

**Request Body:**
```json
{
  "code": "WELCOME10",
  "amount": 50000
}
```

**Response:**
```json
{
  "success": true,
  "message": "کد کوپن معتبر است",
  "data": {
    "coupon": {
      "id": 1,
      "code": "WELCOME10",
      "name": "خوش آمدید",
      "description": "تخفیف ویژه برای کاربران جدید",
      "type": "percentage",
      "discount_value": 10.00,
      "minimum_amount": 0.00,
      "maximum_discount": 10000.00,
      "is_active": true,
      "expires_at": "2024-12-31T23:59:59Z"
    },
    "original_amount": 50000.00,
    "discount_amount": 5000.00,
    "final_amount": 45000.00,
    "commission_amount": 0.00
  }
}
```

### 2. Use Coupon
```http
POST /coupons/use
```

**Request Body:**
```json
{
  "code": "WELCOME10",
  "subscription_id": 123
}
```

**Response:**
```json
{
  "success": true,
  "message": "کد کوپن با موفقیت استفاده شد",
  "data": {
    "id": 1,
    "coupon_code_id": 1,
    "user_id": 456,
    "subscription_id": 123,
    "original_amount": 50000.00,
    "discount_amount": 5000.00,
    "final_amount": 45000.00,
    "commission_amount": 0.00,
    "status": "completed",
    "used_at": "2024-01-15T10:30:00Z"
  }
}
```

### Flutter Coupon Implementation
```dart
class CouponService {
  static const String baseUrl = 'https://your-domain.com/api/v1';
  
  Future<CouponValidation> validateCoupon(String code, double amount) async {
    final response = await http.post(
      Uri.parse('$baseUrl/coupons/validate'),
      headers: await _getHeaders(),
      body: json.encode({
        'code': code,
        'amount': amount,
      }),
    );
    
    if (response.statusCode == 200) {
      final data = json.decode(response.body);
      return CouponValidation.fromJson(data['data']);
    } else {
      final error = json.decode(response.body);
      throw Exception(error['message']);
    }
  }
  
  Future<CouponUsage> useCoupon(String code, int subscriptionId) async {
    final response = await http.post(
      Uri.parse('$baseUrl/coupons/use'),
      headers: await _getHeaders(),
      body: json.encode({
        'code': code,
        'subscription_id': subscriptionId,
      }),
    );
    
    if (response.statusCode == 200) {
      final data = json.decode(response.body);
      return CouponUsage.fromJson(data['data']);
    } else {
      final error = json.decode(response.body);
      throw Exception(error['message']);
    }
  }
}

class CouponValidation {
  final Coupon coupon;
  final double originalAmount;
  final double discountAmount;
  final double finalAmount;
  final double commissionAmount;

  CouponValidation({
    required this.coupon,
    required this.originalAmount,
    required this.discountAmount,
    required this.finalAmount,
    required this.commissionAmount,
  });

  factory CouponValidation.fromJson(Map<String, dynamic> json) {
    return CouponValidation(
      coupon: Coupon.fromJson(json['coupon']),
      originalAmount: json['original_amount'].toDouble(),
      discountAmount: json['discount_amount'].toDouble(),
      finalAmount: json['final_amount'].toDouble(),
      commissionAmount: json['commission_amount'].toDouble(),
    );
  }
}

class Coupon {
  final int id;
  final String code;
  final String name;
  final String description;
  final String type;
  final double discountValue;
  final double minimumAmount;
  final double maximumDiscount;
  final bool isActive;
  final DateTime? expiresAt;

  Coupon({
    required this.id,
    required this.code,
    required this.name,
    required this.description,
    required this.type,
    required this.discountValue,
    required this.minimumAmount,
    required this.maximumDiscount,
    required this.isActive,
    this.expiresAt,
  });

  factory Coupon.fromJson(Map<String, dynamic> json) {
    return Coupon(
      id: json['id'],
      code: json['code'],
      name: json['name'],
      description: json['description'],
      type: json['type'],
      discountValue: json['discount_value'].toDouble(),
      minimumAmount: json['minimum_amount'].toDouble(),
      maximumDiscount: json['maximum_discount'].toDouble(),
      isActive: json['is_active'],
      expiresAt: json['expires_at'] != null 
          ? DateTime.parse(json['expires_at']) 
          : null,
    );
  }
}
```

---

## 📊 Subscription Management

### Get Current Subscription
```http
GET /subscriptions/current
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 123,
    "user_id": 456,
    "type": "1month",
    "amount": 50000.00,
    "currency": "IRR",
    "status": "active",
    "start_date": "2024-01-15T10:35:00Z",
    "end_date": "2024-02-14T10:35:00Z",
    "created_at": "2024-01-15T10:30:00Z",
    "days_remaining": 15,
    "is_active": true,
    "is_expired": false
  }
}
```

### Cancel Subscription
```http
POST /subscriptions/cancel
```

**Response:**
```json
{
  "success": true,
  "message": "اشتراک با موفقیت لغو شد",
  "data": {
    "id": 123,
    "status": "cancelled",
    "cancelled_at": "2024-01-20T10:30:00Z",
    "end_date": "2024-02-14T10:35:00Z"
  }
}
```

### Get Payment History
```http
GET /payments/history
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 789,
      "subscription_id": 123,
      "amount": 50000.00,
      "currency": "IRR",
      "payment_method": "zarinpal",
      "status": "completed",
      "transaction_id": "A000000000000000000000000000000000000",
      "ref_id": "1234567890123456",
      "paid_at": "2024-01-15T10:35:00Z",
      "created_at": "2024-01-15T10:30:00Z"
    }
  ]
}
```

---

## 🎨 Flutter UI Components

### Subscription Plan Card
```dart
class SubscriptionPlanCard extends StatelessWidget {
  final SubscriptionPlan plan;
  final bool isSelected;
  final VoidCallback onTap;

  const SubscriptionPlanCard({
    Key? key,
    required this.plan,
    required this.isSelected,
    required this.onTap,
  }) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return Card(
      elevation: isSelected ? 8 : 2,
      color: isSelected ? Colors.blue.shade50 : Colors.white,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        child: Container(
          padding: EdgeInsets.all(16),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(12),
            border: isSelected 
                ? Border.all(color: Colors.blue, width: 2)
                : null,
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text(
                    plan.name,
                    style: TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  if (plan.isFeatured)
                    Container(
                      padding: EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                      decoration: BoxDecoration(
                        color: Colors.orange,
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Text(
                        'ویژه',
                        style: TextStyle(
                          color: Colors.white,
                          fontSize: 12,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                    ),
                ],
              ),
              SizedBox(height: 8),
              Text(
                plan.description,
                style: TextStyle(
                  color: Colors.grey.shade600,
                  fontSize: 14,
                ),
              ),
              SizedBox(height: 16),
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      if (plan.discountPercentage > 0)
                        Text(
                          plan.formattedPrice,
                          style: TextStyle(
                            decoration: TextDecoration.lineThrough,
                            color: Colors.grey.shade500,
                            fontSize: 14,
                          ),
                        ),
                      Text(
                        plan.formattedFinalPrice,
                        style: TextStyle(
                          fontSize: 20,
                          fontWeight: FontWeight.bold,
                          color: Colors.green.shade700,
                        ),
                      ),
                    ],
                  ),
                  Text(
                    '${plan.durationDays} روز',
                    style: TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ],
              ),
              SizedBox(height: 16),
              ...plan.features.map((feature) => Padding(
                padding: EdgeInsets.only(bottom: 4),
                child: Row(
                  children: [
                    Icon(
                      Icons.check_circle,
                      color: Colors.green,
                      size: 16,
                    ),
                    SizedBox(width: 8),
                    Expanded(
                      child: Text(
                        feature,
                        style: TextStyle(fontSize: 14),
                      ),
                    ),
                  ],
                ),
              )),
            ],
          ),
        ),
      ),
    );
  }
}
```

### Coupon Input Widget
```dart
class CouponInputWidget extends StatefulWidget {
  final Function(String code) onApplyCoupon;
  final Function() onRemoveCoupon;
  final CouponValidation? appliedCoupon;

  const CouponInputWidget({
    Key? key,
    required this.onApplyCoupon,
    required this.onRemoveCoupon,
    this.appliedCoupon,
  }) : super(key: key);

  @override
  _CouponInputWidgetState createState() => _CouponInputWidgetState();
}

class _CouponInputWidgetState extends State<CouponInputWidget> {
  final TextEditingController _controller = TextEditingController();
  bool _isLoading = false;

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'کد تخفیف',
              style: TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.bold,
              ),
            ),
            SizedBox(height: 12),
            if (widget.appliedCoupon == null) ...[
              Row(
                children: [
                  Expanded(
                    child: TextField(
                      controller: _controller,
                      decoration: InputDecoration(
                        hintText: 'کد تخفیف را وارد کنید',
                        border: OutlineInputBorder(),
                        contentPadding: EdgeInsets.symmetric(
                          horizontal: 12,
                          vertical: 8,
                        ),
                      ),
                    ),
                  ),
                  SizedBox(width: 8),
                  ElevatedButton(
                    onPressed: _isLoading ? null : _applyCoupon,
                    child: _isLoading
                        ? SizedBox(
                            width: 20,
                            height: 20,
                            child: CircularProgressIndicator(strokeWidth: 2),
                          )
                        : Text('اعمال'),
                  ),
                ],
              ),
            ] else ...[
              Container(
                padding: EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: Colors.green.shade50,
                  borderRadius: BorderRadius.circular(8),
                  border: Border.all(color: Colors.green.shade200),
                ),
                child: Row(
                  children: [
                    Icon(Icons.check_circle, color: Colors.green),
                    SizedBox(width: 8),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            'کد ${widget.appliedCoupon!.coupon.code} اعمال شد',
                            style: TextStyle(
                              fontWeight: FontWeight.bold,
                              color: Colors.green.shade700,
                            ),
                          ),
                          Text(
                            'تخفیف: ${widget.appliedCoupon!.discountAmount.toStringAsFixed(0)} تومان',
                            style: TextStyle(
                              color: Colors.green.shade600,
                              fontSize: 12,
                            ),
                          ),
                        ],
                      ),
                    ),
                    IconButton(
                      onPressed: widget.onRemoveCoupon,
                      icon: Icon(Icons.close, color: Colors.red),
                    ),
                  ],
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }

  void _applyCoupon() async {
    if (_controller.text.trim().isEmpty) return;

    setState(() {
      _isLoading = true;
    });

    try {
      widget.onApplyCoupon(_controller.text.trim());
      _controller.clear();
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(e.toString()),
          backgroundColor: Colors.red,
        ),
      );
    } finally {
      setState(() {
        _isLoading = false;
      });
    }
  }
}
```

---

## 🔒 Security Considerations

### 1. Token Management
```dart
class AuthService {
  static String? _token;
  
  static Future<Map<String, String>> getHeaders() async {
    final token = await getToken();
    return {
      'Authorization': 'Bearer $token',
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    };
  }
  
  static Future<String?> getToken() async {
    if (_token == null) {
      final prefs = await SharedPreferences.getInstance();
      _token = prefs.getString('auth_token');
    }
    return _token;
  }
  
  static Future<void> saveToken(String token) async {
    _token = token;
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('auth_token', token);
  }
  
  static Future<void> clearToken() async {
    _token = null;
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('auth_token');
  }
}
```

### 2. Payment Security
- Always verify payment status on the server
- Never store sensitive payment information locally
- Use HTTPS for all API communications
- Implement proper error handling and logging

### 3. Input Validation
```dart
class InputValidator {
  static String? validateCouponCode(String? value) {
    if (value == null || value.trim().isEmpty) {
      return 'کد تخفیف را وارد کنید';
    }
    if (value.trim().length < 3) {
      return 'کد تخفیف باید حداقل ۳ کاراکتر باشد';
    }
    return null;
  }
  
  static String? validateAmount(double? value) {
    if (value == null || value <= 0) {
      return 'مبلغ باید بیشتر از صفر باشد';
    }
    return null;
  }
}
```

---

## 🚨 Error Handling

### Common Error Responses
```json
{
  "success": false,
  "message": "خطا در پردازش درخواست",
  "errors": {
    "subscription_id": ["اشتراک یافت نشد"],
    "amount": ["مبلغ نامعتبر است"]
  }
}
```

### Flutter Error Handling
```dart
class ApiException implements Exception {
  final String message;
  final int? statusCode;
  final Map<String, dynamic>? errors;

  ApiException(this.message, {this.statusCode, this.errors});

  @override
  String toString() => message;
}

Future<T> handleApiResponse<T>(http.Response response, T Function(Map<String, dynamic>) fromJson) async {
  if (response.statusCode >= 200 && response.statusCode < 300) {
    final data = json.decode(response.body);
    if (data['success'] == true) {
      return fromJson(data['data']);
    } else {
      throw ApiException(data['message']);
    }
  } else {
    final error = json.decode(response.body);
    throw ApiException(
      error['message'] ?? 'خطا در ارتباط با سرور',
      statusCode: response.statusCode,
      errors: error['errors'],
    );
  }
}
```

---

## 📱 Complete Payment Flow Example

```dart
class PaymentFlowScreen extends StatefulWidget {
  @override
  _PaymentFlowScreenState createState() => _PaymentFlowScreenState();
}

class _PaymentFlowScreenState extends State<PaymentFlowScreen> {
  List<SubscriptionPlan> _plans = [];
  SubscriptionPlan? _selectedPlan;
  CouponValidation? _appliedCoupon;
  bool _isLoading = false;

  @override
  void initState() {
    super.initState();
    _loadPlans();
  }

  Future<void> _loadPlans() async {
    setState(() => _isLoading = true);
    try {
      final plans = await SubscriptionService().getPlans();
      setState(() {
        _plans = plans;
        _selectedPlan = plans.first;
      });
    } catch (e) {
      _showError(e.toString());
    } finally {
      setState(() => _isLoading = false);
    }
  }

  Future<void> _proceedToPayment() async {
    if (_selectedPlan == null) return;

    setState(() => _isLoading = true);
    try {
      // Create subscription
      final subscription = await SubscriptionService().createSubscription(
        _selectedPlan!.slug,
        _appliedCoupon?.coupon.code,
      );

      // Initiate payment
      final paymentResult = await PaymentService().initiatePayment(
        subscription.id,
      );

      // Open payment webview
      final result = await Navigator.push<bool>(
        context,
        MaterialPageRoute(
          builder: (context) => PaymentWebView(
            paymentUrl: paymentResult.paymentUrl,
            onPaymentComplete: (authority, status) async {
              // Verify payment
              final verification = await PaymentService().verifyPayment(
                authority,
                status,
              );
              
              if (verification.payment.status == 'completed') {
                Navigator.pop(context, true);
                _showSuccess('پرداخت با موفقیت انجام شد');
              } else {
                _showError('پرداخت ناموفق بود');
              }
            },
          ),
        ),
      );

      if (result == true) {
        // Payment successful, refresh subscription status
        await _refreshSubscriptionStatus();
      }
    } catch (e) {
      _showError(e.toString());
    } finally {
      setState(() => _isLoading = false);
    }
  }

  void _showError(String message) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: Colors.red,
      ),
    );
  }

  void _showSuccess(String message) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: Colors.green,
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('خرید اشتراک'),
        backgroundColor: Colors.blue,
      ),
      body: _isLoading && _plans.isEmpty
          ? Center(child: CircularProgressIndicator())
          : Column(
              children: [
                Expanded(
                  child: ListView.builder(
                    padding: EdgeInsets.all(16),
                    itemCount: _plans.length,
                    itemBuilder: (context, index) {
                      final plan = _plans[index];
                      return Padding(
                        padding: EdgeInsets.only(bottom: 12),
                        child: SubscriptionPlanCard(
                          plan: plan,
                          isSelected: _selectedPlan?.id == plan.id,
                          onTap: () {
                            setState(() => _selectedPlan = plan);
                          },
                        ),
                      );
                    },
                  ),
                ),
                if (_selectedPlan != null) ...[
                  CouponInputWidget(
                    onApplyCoupon: _applyCoupon,
                    onRemoveCoupon: _removeCoupon,
                    appliedCoupon: _appliedCoupon,
                  ),
                  Container(
                    padding: EdgeInsets.all(16),
                    child: Column(
                      children: [
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Text('مبلغ نهایی:'),
                            Text(
                              _getFinalAmount().toStringAsFixed(0) + ' تومان',
                              style: TextStyle(
                                fontSize: 18,
                                fontWeight: FontWeight.bold,
                                color: Colors.green.shade700,
                              ),
                            ),
                          ],
                        ),
                        SizedBox(height: 16),
                        SizedBox(
                          width: double.infinity,
                          child: ElevatedButton(
                            onPressed: _isLoading ? null : _proceedToPayment,
                            style: ElevatedButton.styleFrom(
                              backgroundColor: Colors.blue,
                              padding: EdgeInsets.symmetric(vertical: 16),
                            ),
                            child: _isLoading
                                ? CircularProgressIndicator(color: Colors.white)
                                : Text(
                                    'پرداخت',
                                    style: TextStyle(
                                      fontSize: 16,
                                      fontWeight: FontWeight.bold,
                                    ),
                                  ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ],
            ),
    );
  }

  double _getFinalAmount() {
    if (_selectedPlan == null) return 0;
    
    double amount = _selectedPlan!.finalPrice;
    if (_appliedCoupon != null) {
      amount = _appliedCoupon!.finalAmount;
    }
    return amount;
  }

  Future<void> _applyCoupon(String code) async {
    try {
      final validation = await CouponService().validateCoupon(
        code,
        _selectedPlan!.finalPrice,
      );
      setState(() => _appliedCoupon = validation);
    } catch (e) {
      rethrow;
    }
  }

  void _removeCoupon() {
    setState(() => _appliedCoupon = null);
  }

  Future<void> _refreshSubscriptionStatus() async {
    // Refresh user's subscription status
    // This would typically update the app's state
  }
}
```

---

## 📋 Testing Checklist

### Payment Flow Testing
- [ ] Load subscription plans successfully
- [ ] Select different plans
- [ ] Apply valid coupon codes
- [ ] Apply invalid coupon codes
- [ ] Handle coupon validation errors
- [ ] Initiate payment successfully
- [ ] Handle payment initiation errors
- [ ] Complete payment flow
- [ ] Handle payment failures
- [ ] Verify payment status
- [ ] Handle network errors
- [ ] Test with different currencies
- [ ] Test subscription activation
- [ ] Test subscription cancellation

### Security Testing
- [ ] Verify HTTPS usage
- [ ] Test token expiration handling
- [ ] Test unauthorized access
- [ ] Test input validation
- [ ] Test error message sanitization
- [ ] Test payment amount validation

---

## 🔧 Configuration

### Environment Variables
```dart
class ApiConfig {
  static const String baseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'https://your-domain.com/api/v1',
  );
  
  static const String zarinpalMerchantId = String.fromEnvironment(
    'ZARINPAL_MERCHANT_ID',
    defaultValue: 'your-merchant-id',
  );
  
  static const bool isProduction = bool.fromEnvironment(
    'IS_PRODUCTION',
    defaultValue: false,
  );
}
```

### Dependencies
```yaml
dependencies:
  flutter:
    sdk: flutter
  http: ^1.1.0
  webview_flutter: ^4.4.2
  shared_preferences: ^2.2.2
  url_launcher: ^6.2.2
```

---

## 📞 Support

For technical support or questions about the payment integration:

- **Email**: support@sarvcast.com
- **Documentation**: https://docs.sarvcast.com
- **API Status**: https://status.sarvcast.com

---

## 📄 License

This documentation is provided for SarvCast Flutter integration. Please ensure compliance with your app's terms of service and privacy policy when implementing payment functionality.
