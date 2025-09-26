# SarvCast Flutter Payment & Zarinpal Integration Guide

## Overview
This document provides comprehensive guidance for Flutter developers to integrate with the SarvCast payment system using Zarinpal payment gateway. It covers subscription plans, payment processing, and user-side payment management.

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

// For authenticated requests
Map<String, String> authHeaders = {
  ...headers,
  'Authorization': 'Bearer $userToken',
};
```

## Subscription Plans API

### 1. Get Available Subscription Plans

**Route:** `GET /subscriptions/plans`

**Purpose:** Fetch all available subscription plans

**Flutter Implementation:**
```dart
class SubscriptionPlanService {
  static const String baseUrl = 'https://my.sarvcast.ir/api/v1';
  
  Future<List<SubscriptionPlan>> getPlans() async {
    final uri = Uri.parse('$baseUrl/subscriptions/plans');
    
    final response = await http.get(uri, headers: headers);
    
    if (response.statusCode == 200) {
      final data = json.decode(response.body);
      if (data['success'] == true) {
        return (data['data']['plans'] as List)
            .map((json) => SubscriptionPlan.fromJson(json))
            .toList();
      }
    }
    
    throw Exception('Failed to load subscription plans');
  }
}
```

**Expected Response:**
```json
{
  "success": true,
  "message": "پلن‌های اشتراک دریافت شد",
  "data": {
    "plans": [
      {
        "id": 1,
        "name": "اشتراک یک ماهه",
        "slug": "1month",
        "description": "دسترسی کامل به تمام محتوا برای یک ماه",
        "duration_days": 30,
        "price": 50000.00,
        "currency": "IRT",
        "discount_percentage": 0,
        "is_active": true,
        "is_featured": false,
        "sort_order": 1,
        "features": [
          "دسترسی به تمام داستان‌ها",
          "پخش بدون محدودیت",
          "دانلود آفلاین",
          "پشتیبانی ۲۴/۷"
        ],
        "final_price": 50000.00,
        "formatted_price": "50,000 IRT",
        "formatted_final_price": "50,000 IRT",
        "created_at": "2024-01-15T10:30:00Z",
        "updated_at": "2024-01-15T10:30:00Z"
      },
      {
        "id": 2,
        "name": "اشتراک سه‌ماهه",
        "slug": "3months",
        "description": "دسترسی کامل به تمام محتوا برای سه ماه",
        "duration_days": 90,
        "price": 135000.00,
        "currency": "IRT",
        "discount_percentage": 10,
        "is_active": true,
        "is_featured": false,
        "sort_order": 2,
        "features": [
          "دسترسی به تمام داستان‌ها",
          "پخش بدون محدودیت",
          "دانلود آفلاین",
          "پشتیبانی ۲۴/۷",
          "۱۰٪ تخفیف"
        ],
        "final_price": 121500.00,
        "formatted_price": "135,000 IRT",
        "formatted_final_price": "121,500 IRT",
        "created_at": "2024-01-15T10:30:00Z",
        "updated_at": "2024-01-15T10:30:00Z"
      }
    ]
  }
}
```

### 2. Calculate Subscription Price

**Route:** `POST /subscriptions/calculate-price`

**Purpose:** Calculate subscription price with optional coupon

**Request Body:**
```json
{
  "type": "3months",
  "coupon_code": "SAVE10"
}
```

**Flutter Implementation:**
```dart
Future<PriceCalculation> calculatePrice({
  required String type,
  String? couponCode,
}) async {
  final uri = Uri.parse('$baseUrl/subscriptions/calculate-price');
  
  final body = {
    'type': type,
    if (couponCode != null) 'coupon_code': couponCode,
  };
  
  final response = await http.post(
    uri,
    headers: authHeaders,
    body: json.encode(body),
  );
  
  if (response.statusCode == 200) {
    final data = json.decode(response.body);
    if (data['success'] == true) {
      return PriceCalculation.fromJson(data['data']);
    }
  }
  
  throw Exception('Failed to calculate price');
}
```

**Expected Response:**
```json
{
  "success": true,
  "message": "محاسبه قیمت انجام شد",
  "data": {
    "original_price": 135000.00,
    "discount_amount": 13500.00,
    "final_price": 121500.00,
    "currency": "IRT",
    "coupon_applied": {
      "code": "SAVE10",
      "discount_percentage": 10,
      "discount_amount": 13500.00
    },
    "formatted_original_price": "135,000 IRT",
    "formatted_discount_amount": "13,500 IRT",
    "formatted_final_price": "121,500 IRT"
  }
}
```

## Payment Processing API

### 1. Create Subscription

**Route:** `POST /subscriptions/create`

**Purpose:** Create a new subscription for payment

**Request Body:**
```json
{
  "plan_slug": "3months",
  "coupon_code": "SAVE10",
  "auto_renew": true
}
```

**Flutter Implementation:**
```dart
Future<SubscriptionCreationResult> createSubscription({
  required String planSlug,
  String? couponCode,
  bool autoRenew = true,
}) async {
  final uri = Uri.parse('$baseUrl/subscriptions/create');
  
  final body = {
    'plan_slug': planSlug,
    'auto_renew': autoRenew,
    if (couponCode != null) 'coupon_code': couponCode,
  };
  
  final response = await http.post(
    uri,
    headers: authHeaders,
    body: json.encode(body),
  );
  
  if (response.statusCode == 200) {
    final data = json.decode(response.body);
    if (data['success'] == true) {
      return SubscriptionCreationResult.fromJson(data['data']);
    }
  }
  
  throw Exception('Failed to create subscription');
}
```

**Expected Response:**
```json
{
  "success": true,
  "message": "اشتراک با موفقیت ایجاد شد",
  "data": {
    "subscription": {
      "id": 123,
      "user_id": 456,
      "type": "3months",
      "amount": 121500.00,
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
      "amount": 121500.00,
      "currency": "IRR",
      "payment_method": "zarinpal",
      "status": "pending",
      "transaction_id": "SUB_1705312200_1234",
      "description": "پرداخت اشتراک اشتراک سه‌ماهه",
      "created_at": "2024-01-15T10:30:00Z"
    }
  }
}
```

### 2. Initiate Payment

**Route:** `POST /payments/initiate`

**Purpose:** Initiate payment with Zarinpal

**Request Body:**
```json
{
  "subscription_id": 123
}
```

**Flutter Implementation:**
```dart
class PaymentService {
  static const String baseUrl = 'https://my.sarvcast.ir/api/v1';
  
  Future<PaymentInitiationResult> initiatePayment({
    required int subscriptionId,
  }) async {
    final uri = Uri.parse('$baseUrl/payments/initiate');
    
    final body = {
      'subscription_id': subscriptionId,
    };
    
    final response = await http.post(
      uri,
      headers: authHeaders,
      body: json.encode(body),
    );
    
    if (response.statusCode == 200) {
      final data = json.decode(response.body);
      if (data['success'] == true) {
        return PaymentInitiationResult.fromJson(data['data']);
      }
    }
    
    throw Exception('Failed to initiate payment');
  }
}
```

**Expected Response:**
```json
{
  "success": true,
  "message": "درخواست پرداخت با موفقیت ایجاد شد",
  "data": {
    "payment": {
      "id": 789,
      "user_id": 456,
      "subscription_id": 123,
      "amount": 121500.00,
      "currency": "IRR",
      "payment_method": "zarinpal",
      "status": "pending",
      "transaction_id": "SUB_1705312200_1234",
      "description": "پرداخت اشتراک اشتراک سه‌ماهه",
      "created_at": "2024-01-15T10:30:00Z"
    },
    "payment_url": "https://www.zarinpal.com/pg/StartPay/A00000000000000000000000000000000000000000000",
    "authority": "A00000000000000000000000000000000000000000000"
  }
}
```

### 3. Verify Payment

**Route:** `POST /payments/verify`

**Purpose:** Verify payment after Zarinpal callback

**Request Body:**
```json
{
  "authority": "A00000000000000000000000000000000000000000000",
  "status": "OK"
}
```

**Flutter Implementation:**
```dart
Future<PaymentVerificationResult> verifyPayment({
  required String authority,
  required String status,
}) async {
  final uri = Uri.parse('$baseUrl/payments/verify');
  
  final body = {
    'authority': authority,
    'status': status,
  };
  
  final response = await http.post(
    uri,
    headers: authHeaders,
    body: json.encode(body),
  );
  
  if (response.statusCode == 200) {
    final data = json.decode(response.body);
    if (data['success'] == true) {
      return PaymentVerificationResult.fromJson(data['data']);
    }
  }
  
  throw Exception('Failed to verify payment');
}
```

**Expected Response:**
```json
{
  "success": true,
  "message": "پرداخت با موفقیت انجام شد",
  "data": {
    "payment": {
      "id": 789,
      "user_id": 456,
      "subscription_id": 123,
      "amount": 121500.00,
      "currency": "IRR",
      "payment_method": "zarinpal",
      "status": "completed",
      "transaction_id": "SUB_1705312200_1234",
      "description": "پرداخت اشتراک اشتراک سه‌ماهه",
      "paid_at": "2024-01-15T10:35:00Z",
      "created_at": "2024-01-15T10:30:00Z"
    },
    "subscription": {
      "id": 123,
      "user_id": 456,
      "type": "3months",
      "amount": 121500.00,
      "currency": "IRR",
      "status": "active",
      "start_date": "2024-01-15T10:35:00Z",
      "end_date": "2024-04-15T10:35:00Z",
      "created_at": "2024-01-15T10:30:00Z"
    }
  }
}
```

### 4. Get Payment History

**Route:** `GET /payments/history`

**Purpose:** Get user's payment history

**Parameters:**
- `per_page` (optional): Number of payments per page (default: 20)

**Flutter Implementation:**
```dart
Future<PaymentHistoryResult> getPaymentHistory({
  int? perPage,
}) async {
  final queryParams = <String, String>{};
  if (perPage != null) queryParams['per_page'] = perPage.toString();
  
  final uri = Uri.parse('$baseUrl/payments/history').replace(
    queryParameters: queryParams,
  );
  
  final response = await http.get(uri, headers: authHeaders);
  
  if (response.statusCode == 200) {
    final data = json.decode(response.body);
    if (data['success'] == true) {
      return PaymentHistoryResult.fromJson(data['data']);
    }
  }
  
  throw Exception('Failed to load payment history');
}
```

**Expected Response:**
```json
{
  "success": true,
  "data": {
    "payments": [
      {
        "id": 789,
        "user_id": 456,
        "subscription_id": 123,
        "amount": 121500.00,
        "currency": "IRR",
        "payment_method": "zarinpal",
        "status": "completed",
        "transaction_id": "SUB_1705312200_1234",
        "description": "پرداخت اشتراک اشتراک سه‌ماهه",
        "paid_at": "2024-01-15T10:35:00Z",
        "created_at": "2024-01-15T10:30:00Z",
        "subscription": {
          "id": 123,
          "type": "3months",
          "status": "active",
          "start_date": "2024-01-15T10:35:00Z",
          "end_date": "2024-04-15T10:35:00Z"
        }
      }
    ],
    "pagination": {
      "current_page": 1,
      "last_page": 3,
      "per_page": 20,
      "total": 45
    }
  }
}
```

## Data Models

### SubscriptionPlan Model
```dart
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
  final int sortOrder;
  final List<String> features;
  final double finalPrice;
  final String formattedPrice;
  final String formattedFinalPrice;
  final DateTime createdAt;
  final DateTime updatedAt;

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
    required this.sortOrder,
    required this.features,
    required this.finalPrice,
    required this.formattedPrice,
    required this.formattedFinalPrice,
    required this.createdAt,
    required this.updatedAt,
  });

  factory SubscriptionPlan.fromJson(Map<String, dynamic> json) {
    return SubscriptionPlan(
      id: json['id'],
      name: json['name'],
      slug: json['slug'],
      description: json['description'],
      durationDays: json['duration_days'],
      price: (json['price'] ?? 0.0).toDouble(),
      currency: json['currency'],
      discountPercentage: json['discount_percentage'],
      isActive: json['is_active'],
      isFeatured: json['is_featured'],
      sortOrder: json['sort_order'],
      features: List<String>.from(json['features'] ?? []),
      finalPrice: (json['final_price'] ?? 0.0).toDouble(),
      formattedPrice: json['formatted_price'],
      formattedFinalPrice: json['formatted_final_price'],
      createdAt: DateTime.parse(json['created_at']),
      updatedAt: DateTime.parse(json['updated_at']),
    );
  }
}
```

### Payment Model
```dart
class Payment {
  final int id;
  final int userId;
  final int subscriptionId;
  final double amount;
  final String currency;
  final String paymentMethod;
  final String status;
  final String transactionId;
  final String description;
  final DateTime? paidAt;
  final DateTime createdAt;
  final Subscription? subscription;

  Payment({
    required this.id,
    required this.userId,
    required this.subscriptionId,
    required this.amount,
    required this.currency,
    required this.paymentMethod,
    required this.status,
    required this.transactionId,
    required this.description,
    this.paidAt,
    required this.createdAt,
    this.subscription,
  });

  factory Payment.fromJson(Map<String, dynamic> json) {
    return Payment(
      id: json['id'],
      userId: json['user_id'],
      subscriptionId: json['subscription_id'],
      amount: (json['amount'] ?? 0.0).toDouble(),
      currency: json['currency'],
      paymentMethod: json['payment_method'],
      status: json['status'],
      transactionId: json['transaction_id'],
      description: json['description'],
      paidAt: json['paid_at'] != null ? DateTime.parse(json['paid_at']) : null,
      createdAt: DateTime.parse(json['created_at']),
      subscription: json['subscription'] != null 
          ? Subscription.fromJson(json['subscription']) 
          : null,
    );
  }
}
```

### Subscription Model
```dart
class Subscription {
  final int id;
  final int userId;
  final String type;
  final double amount;
  final String currency;
  final String status;
  final DateTime? startDate;
  final DateTime? endDate;
  final bool autoRenew;
  final DateTime createdAt;
  final DateTime updatedAt;

  Subscription({
    required this.id,
    required this.userId,
    required this.type,
    required this.amount,
    required this.currency,
    required this.status,
    this.startDate,
    this.endDate,
    required this.autoRenew,
    required this.createdAt,
    required this.updatedAt,
  });

  factory Subscription.fromJson(Map<String, dynamic> json) {
    return Subscription(
      id: json['id'],
      userId: json['user_id'],
      type: json['type'],
      amount: (json['amount'] ?? 0.0).toDouble(),
      currency: json['currency'],
      status: json['status'],
      startDate: json['start_date'] != null ? DateTime.parse(json['start_date']) : null,
      endDate: json['end_date'] != null ? DateTime.parse(json['end_date']) : null,
      autoRenew: json['auto_renew'] ?? true,
      createdAt: DateTime.parse(json['created_at']),
      updatedAt: DateTime.parse(json['updated_at']),
    );
  }

  bool get isActive => status == 'active';
  bool get isExpired => status == 'expired';
  bool get isPending => status == 'pending';
  
  Duration? get remainingDuration {
    if (endDate == null) return null;
    final now = DateTime.now();
    if (endDate!.isBefore(now)) return Duration.zero;
    return endDate!.difference(now);
  }
}
```

## Flutter UI Implementation

### Subscription Plans Widget
```dart
class SubscriptionPlansWidget extends StatefulWidget {
  final Function(SubscriptionPlan plan) onPlanSelected;

  const SubscriptionPlansWidget({
    Key? key,
    required this.onPlanSelected,
  }) : super(key: key);

  @override
  _SubscriptionPlansWidgetState createState() => _SubscriptionPlansWidgetState();
}

class _SubscriptionPlansWidgetState extends State<SubscriptionPlansWidget> {
  final SubscriptionPlanService _planService = SubscriptionPlanService();
  List<SubscriptionPlan> _plans = [];
  bool _isLoading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _loadPlans();
  }

  Future<void> _loadPlans() async {
    try {
      final plans = await _planService.getPlans();
      setState(() {
        _plans = plans;
        _isLoading = false;
      });
    } catch (e) {
      setState(() {
        _error = e.toString();
        _isLoading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_isLoading) {
      return Center(child: CircularProgressIndicator());
    }

    if (_error != null) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.error, size: 64, color: Colors.red),
            SizedBox(height: 16),
            Text('خطا در بارگذاری پلن‌ها'),
            SizedBox(height: 16),
            ElevatedButton(
              onPressed: _loadPlans,
              child: Text('تلاش مجدد'),
            ),
          ],
        ),
      );
    }

    return ListView.builder(
      itemCount: _plans.length,
      itemBuilder: (context, index) {
        final plan = _plans[index];
        return _buildPlanCard(plan);
      },
    );
  }

  Widget _buildPlanCard(SubscriptionPlan plan) {
    return Card(
      margin: EdgeInsets.all(8),
      child: Padding(
        padding: EdgeInsets.all(16),
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
              style: TextStyle(color: Colors.grey[600]),
            ),
            
            SizedBox(height: 16),
            
            // Features
            ...plan.features.map((feature) => Padding(
              padding: EdgeInsets.symmetric(vertical: 2),
              child: Row(
                children: [
                  Icon(Icons.check, color: Colors.green, size: 16),
                  SizedBox(width: 8),
                  Expanded(child: Text(feature)),
                ],
              ),
            )).toList(),
            
            SizedBox(height: 16),
            
            // Price
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    if (plan.discountPercentage > 0) ...[
                      Text(
                        plan.formattedPrice,
                        style: TextStyle(
                          decoration: TextDecoration.lineThrough,
                          color: Colors.grey,
                        ),
                      ),
                      Text(
                        plan.formattedFinalPrice,
                        style: TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.bold,
                          color: Colors.green,
                        ),
                      ),
                    ] else
                      Text(
                        plan.formattedPrice,
                        style: TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                  ],
                ),
                ElevatedButton(
                  onPressed: () => widget.onPlanSelected(plan),
                  child: Text('انتخاب'),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}
```

### Payment Processing Widget
```dart
class PaymentProcessingWidget extends StatefulWidget {
  final int subscriptionId;
  final Function(PaymentVerificationResult result) onPaymentComplete;

  const PaymentProcessingWidget({
    Key? key,
    required this.subscriptionId,
    required this.onPaymentComplete,
  }) : super(key: key);

  @override
  _PaymentProcessingWidgetState createState() => _PaymentProcessingWidgetState();
}

class _PaymentProcessingWidgetState extends State<PaymentProcessingWidget> {
  final PaymentService _paymentService = PaymentService();
  bool _isProcessing = false;
  String? _paymentUrl;
  String? _authority;

  @override
  void initState() {
    super.initState();
    _initiatePayment();
  }

  Future<void> _initiatePayment() async {
    setState(() {
      _isProcessing = true;
    });

    try {
      final result = await _paymentService.initiatePayment(
        subscriptionId: widget.subscriptionId,
      );

      setState(() {
        _paymentUrl = result.paymentUrl;
        _authority = result.authority;
        _isProcessing = false;
      });

      // Open Zarinpal payment page
      await _openPaymentPage(result.paymentUrl);
    } catch (e) {
      setState(() {
        _isProcessing = false;
      });
      
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('خطا در شروع پرداخت: ${e.toString()}')),
      );
    }
  }

  Future<void> _openPaymentPage(String paymentUrl) async {
    // Use url_launcher to open payment page
    if (await canLaunch(paymentUrl)) {
      await launch(paymentUrl);
      
      // Start listening for payment result
      _listenForPaymentResult();
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('نمی‌توان صفحه پرداخت را باز کرد')),
      );
    }
  }

  void _listenForPaymentResult() {
    // This would typically be handled by deep linking or app state changes
    // For now, we'll simulate the verification process
    Timer(Duration(seconds: 30), () {
      _verifyPayment();
    });
  }

  Future<void> _verifyPayment() async {
    if (_authority == null) return;

    try {
      final result = await _paymentService.verifyPayment(
        authority: _authority!,
        status: 'OK', // This would come from the actual callback
      );

      widget.onPaymentComplete(result);
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('خطا در تایید پرداخت: ${e.toString()}')),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('پرداخت'),
        leading: IconButton(
          icon: Icon(Icons.close),
          onPressed: () => Navigator.of(context).pop(),
        ),
      ),
      body: Center(
        child: _isProcessing
            ? Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  CircularProgressIndicator(),
                  SizedBox(height: 16),
                  Text('در حال آماده‌سازی پرداخت...'),
                ],
              )
            : Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Icon(Icons.payment, size: 64, color: Colors.blue),
                  SizedBox(height: 16),
                  Text('در حال انتقال به درگاه پرداخت...'),
                  SizedBox(height: 16),
                  ElevatedButton(
                    onPressed: _verifyPayment,
                    child: Text('بررسی وضعیت پرداخت'),
                  ),
                ],
              ),
      ),
    );
  }
}
```

## Error Handling

### Payment Error Handling
```dart
class PaymentException implements Exception {
  final String message;
  final int? statusCode;
  final String? errorCode;
  
  PaymentException(this.message, [this.statusCode, this.errorCode]);
  
  @override
  String toString() => 'PaymentException: $message';
}

Future<T> _handlePaymentCall<T>(Future<T> Function() apiCall) async {
  try {
    return await apiCall();
  } on SocketException {
    throw PaymentException('خطا در اتصال به اینترنت');
  } on TimeoutException {
    throw PaymentException('زمان اتصال به سرور به پایان رسید');
  } on FormatException {
    throw PaymentException('خطا در فرمت داده‌های دریافتی');
  } on PaymentException {
    rethrow;
  } catch (e) {
    throw PaymentException('خطای نامشخص: ${e.toString()}');
  }
}
```

## Testing

### Unit Tests
```dart
void main() {
  group('PaymentService', () {
    test('should initiate payment successfully', () async {
      // Mock HTTP response
      when(mockHttpClient.post(any, headers: anyNamed('headers'), body: anyNamed('body')))
          .thenAnswer((_) async => http.Response(
                jsonEncode({
                  'success': true,
                  'message': 'درخواست پرداخت با موفقیت ایجاد شد',
                  'data': {
                    'payment': {
                      'id': 789,
                      'user_id': 456,
                      'subscription_id': 123,
                      'amount': 121500.00,
                      'currency': 'IRR',
                      'payment_method': 'zarinpal',
                      'status': 'pending',
                      'transaction_id': 'SUB_1705312200_1234',
                      'description': 'پرداخت اشتراک اشتراک سه‌ماهه',
                      'created_at': '2024-01-15T10:30:00Z'
                    },
                    'payment_url': 'https://www.zarinpal.com/pg/StartPay/A00000000000000000000000000000000000000000000',
                    'authority': 'A00000000000000000000000000000000000000000000'
                  }
                }),
                200,
              ));
      
      final service = PaymentService();
      final result = await service.initiatePayment(subscriptionId: 123);
      
      expect(result.paymentUrl, contains('zarinpal.com'));
      expect(result.authority, isNotEmpty);
    });
  });
}
```

## Deployment Checklist

### Pre-deployment
- [ ] Test all payment endpoints
- [ ] Verify Zarinpal integration
- [ ] Test subscription plan creation
- [ ] Test payment verification
- [ ] Test error handling scenarios
- [ ] Verify authentication requirements
- [ ] Test offline functionality

### Post-deployment
- [ ] Monitor payment success rates
- [ ] Check Zarinpal callback handling
- [ ] Monitor subscription activations
- [ ] Track payment failures
- [ ] Monitor API response times
- [ ] Collect user feedback

## Troubleshooting

### Common Issues

1. **Payment Not Initiating**
   - Check authentication token
   - Verify subscription ID
   - Check network connectivity
   - Validate request body

2. **Payment Verification Failing**
   - Check authority code
   - Verify payment status
   - Check Zarinpal response
   - Monitor error logs

3. **Subscription Not Activating**
   - Check payment status
   - Verify subscription creation
   - Check database updates
   - Monitor service logs

## Support

For technical support or payment issues, contact the development team.

**Document Version:** 1.0  
**Last Updated:** January 2024  
**Next Review:** February 2024
