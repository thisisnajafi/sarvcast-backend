# Coupon Code Management System Documentation

## 📋 Overview

The Coupon Code Management System is a comprehensive revenue sharing platform that enables SarvCast to distribute unique coupon codes to partners, influencers, teachers, and affiliates. The system automatically calculates and processes commission payments while providing detailed analytics and management capabilities.

## 🎯 System Architecture

### Core Components

1. **Coupon Code Generation**: Unique, trackable codes for each partner
2. **Revenue Sharing Engine**: Automatic commission calculation and tracking
3. **Payment Processing System**: Manual and automated payment processing
4. **Analytics Dashboard**: Comprehensive reporting and performance tracking
5. **Admin Management Interface**: Complete control over the system

## 💰 Revenue Sharing Structure

### Commission Rates by Partner Type

| Partner Type | Revenue Share | User Discount | Code Format | Payment Schedule |
|--------------|---------------|---------------|-------------|------------------|
| **Influencer** | 20% | 15-25% | INF + 6 chars | Monthly |
| **Teacher** | 15% | 10-20% | TCH + 6 chars | Monthly |
| **Partner** | 10% | 5-15% | PRT + 6 chars | Monthly |
| **Promotional** | 0% | 10-30% | PROMO + 6 chars | N/A |

### Example Revenue Calculation

**Scenario**: User purchases 1-year subscription (400,000 IRR) using influencer coupon (INFABC123)
- **Original Price**: 400,000 IRR
- **User Discount**: 20% (80,000 IRR)
- **Final Price**: 320,000 IRR
- **Influencer Commission**: 20% of 320,000 = 64,000 IRR
- **Platform Revenue**: 320,000 - 64,000 = 256,000 IRR

## 🔧 Admin Management Features

### 1. Coupon Code Creation

#### Creating New Coupon Codes
```php
// Example API call for creating a coupon
POST /api/v1/coupons/create
{
    "name": "Influencer Campaign 2024",
    "description": "Special discount for influencer promotion",
    "type": "percentage",
    "discount_value": 20,
    "partner_type": "influencer",
    "partner_id": 123,
    "usage_limit": 100,
    "user_limit": 1,
    "starts_at": "2024-01-01",
    "expires_at": "2024-12-31",
    "applicable_plans": ["1month", "3months", "6months", "1year"]
}
```

#### Coupon Code Configuration Options
- **Code Generation**: Automatic or manual code assignment
- **Discount Types**: Percentage, fixed amount, or free trial
- **Usage Limits**: Total usage limit and per-user limit
- **Validity Period**: Start and expiration dates
- **Applicable Plans**: Which subscription plans the coupon applies to
- **Minimum Amount**: Minimum purchase amount required

### 2. Partner Management

#### Partner Types and Permissions
- **Influencer Partners**: Content creators and social media influencers
- **Teacher Partners**: Educators and educational institutions
- **Business Partners**: Corporate and institutional partners
- **Promotional Partners**: Marketing campaigns and special offers

#### Partner Onboarding Process
1. **Application Review**: Verify partner credentials and audience
2. **Account Creation**: Create affiliate partner account
3. **Coupon Assignment**: Generate unique coupon codes
4. **Training**: Provide usage guidelines and best practices
5. **Monitoring**: Track performance and compliance

### 3. Payment Processing System

#### Payment Workflow
```
Pending → Processing → Paid
   ↓         ↓         ↓
Created   Admin      Completed
         Action
```

#### Manual Payment Processing
```php
// Process individual payment
POST /api/v1/commission-payments/process
{
    "payment_id": 456,
    "payment_reference": "BANK_TRANSFER_001",
    "notes": "Payment processed via bank transfer"
}

// Mark payment as completed
POST /api/v1/commission-payments/mark-paid
{
    "payment_id": 456,
    "payment_reference": "BANK_TRANSFER_001"
}
```

#### Bulk Payment Processing
```php
// Process multiple payments
POST /api/v1/commission-payments/bulk-process
{
    "payment_ids": [456, 457, 458, 459]
}
```

#### Payment Reset Functionality
```php
// Reset payment status for corrections
POST /api/v1/commission-payments/reset
{
    "payment_id": 456,
    "reason": "Bank account information incorrect",
    "new_status": "pending"
}
```

### 4. Analytics and Reporting

#### Key Performance Indicators (KPIs)
- **Coupon Usage Rate**: Percentage of coupons used vs. distributed
- **Conversion Rate**: Percentage of coupon users who subscribe
- **Revenue Generated**: Total revenue from coupon usage
- **Commission Paid**: Total commissions paid to partners
- **Partner Performance**: Individual partner metrics

#### Revenue Reports
- **Daily Revenue**: Revenue generated per day
- **Monthly Revenue**: Monthly revenue breakdown
- **Partner Revenue**: Revenue generated per partner
- **Commission Reports**: Commission payments and outstanding amounts

#### Usage Analytics
- **Coupon Performance**: Most/least used coupons
- **User Demographics**: Geographic and demographic data
- **Conversion Funnel**: User journey from coupon to subscription
- **Retention Rates**: User retention after coupon usage

## 🔐 Security and Permissions

### Admin Permissions
- **Super Admin**: Full access to all features
- **Payment Admin**: Can process payments and view financial data
- **Partner Admin**: Can manage partners and create coupons
- **Analytics Admin**: Can view reports and analytics

### Audit Trail
- **Payment History**: Complete log of all payment operations
- **Coupon Changes**: Track all modifications to coupon codes
- **Admin Actions**: Log all admin operations with timestamps
- **User Actions**: Track coupon usage and validation attempts

### Fraud Prevention
- **Usage Limits**: Prevent abuse through usage restrictions
- **Validation Rules**: Strict validation of coupon codes
- **Monitoring**: Real-time monitoring of suspicious activity
- **Blocking**: Ability to block fraudulent partners or codes

## 📊 Partner Dashboard Features

### Partner Login and Access
- **Secure Authentication**: Partner-specific login credentials
- **Role-based Access**: Different access levels for different partner types
- **Session Management**: Secure session handling and timeout

### Coupon Management
- **View My Coupons**: List all assigned coupon codes
- **Usage Statistics**: Real-time usage data and performance metrics
- **Code Status**: Active/inactive status and expiration information
- **Performance Metrics**: Conversion rates and revenue generated

### Commission Tracking
- **Payment History**: Complete history of commission payments
- **Pending Payments**: View pending commission payments
- **Payment Status**: Track payment processing status
- **Earnings Summary**: Total earnings and commission breakdown

### Analytics Dashboard
- **Usage Analytics**: Detailed analytics of coupon usage
- **Revenue Tracking**: Track revenue generated from coupons
- **User Demographics**: Analytics of users who used coupons
- **Performance Trends**: Historical performance data and trends

## 🚀 Implementation Guide

### Database Schema

#### coupon_codes Table
```sql
CREATE TABLE coupon_codes (
    id BIGINT PRIMARY KEY,
    code VARCHAR(50) UNIQUE,
    name VARCHAR(255),
    description TEXT,
    type ENUM('percentage', 'fixed_amount', 'free_trial'),
    discount_value DECIMAL(10,2),
    minimum_amount DECIMAL(10,2),
    maximum_discount DECIMAL(10,2),
    partner_type ENUM('influencer', 'teacher', 'partner', 'promotional'),
    partner_id BIGINT,
    created_by BIGINT,
    usage_limit INT,
    usage_count INT DEFAULT 0,
    user_limit INT,
    starts_at TIMESTAMP,
    expires_at TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    applicable_plans JSON,
    metadata JSON,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

#### coupon_usages Table
```sql
CREATE TABLE coupon_usages (
    id BIGINT PRIMARY KEY,
    coupon_code_id BIGINT,
    user_id BIGINT,
    subscription_id BIGINT,
    original_amount DECIMAL(10,2),
    discount_amount DECIMAL(10,2),
    final_amount DECIMAL(10,2),
    commission_amount DECIMAL(10,2),
    status ENUM('pending', 'completed', 'cancelled', 'refunded'),
    used_at TIMESTAMP,
    metadata JSON,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

#### commission_payments Table
```sql
CREATE TABLE commission_payments (
    id BIGINT PRIMARY KEY,
    affiliate_partner_id BIGINT,
    coupon_usage_id BIGINT,
    amount DECIMAL(10,2),
    currency VARCHAR(3) DEFAULT 'IRR',
    payment_type ENUM('coupon_commission', 'referral_commission', 'manual'),
    status ENUM('pending', 'processing', 'paid', 'failed', 'cancelled'),
    payment_method ENUM('bank_transfer', 'digital_wallet', 'manual'),
    payment_reference VARCHAR(255),
    payment_details JSON,
    notes TEXT,
    processed_at TIMESTAMP,
    paid_at TIMESTAMP,
    processed_by BIGINT,
    metadata JSON,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### API Endpoints

#### Coupon Management
- `POST /api/v1/coupons/create` - Create new coupon code
- `POST /api/v1/coupons/validate` - Validate coupon code
- `POST /api/v1/coupons/use` - Use coupon code for subscription
- `GET /api/v1/coupons/all` - Get all coupon codes (admin)
- `GET /api/v1/coupons/my-coupons` - Get partner's coupons

#### Commission Payments
- `GET /api/v1/commission-payments/pending` - Get pending payments
- `POST /api/v1/commission-payments/process` - Process payment
- `POST /api/v1/commission-payments/mark-paid` - Mark payment as paid
- `POST /api/v1/commission-payments/mark-failed` - Mark payment as failed
- `POST /api/v1/commission-payments/bulk-process` - Bulk process payments
- `POST /api/v1/commission-payments/create-manual` - Create manual payment

#### Analytics
- `GET /api/v1/coupons/global-statistics` - Global coupon statistics
- `GET /api/v1/commission-payments/statistics` - Payment statistics
- `GET /api/v1/coupons/my-statistics` - Partner statistics

## 📈 Business Benefits

### Revenue Growth
- **Partner Incentives**: Motivate partners to promote the platform
- **User Acquisition**: Attract new users through partner networks
- **Retention**: Increase user retention through discounted subscriptions
- **Market Expansion**: Reach new markets through partner networks

### Operational Efficiency
- **Automated Processing**: Reduce manual work in commission calculations
- **Real-time Tracking**: Immediate visibility into coupon performance
- **Bulk Operations**: Efficient management of multiple coupons and payments
- **Reporting**: Comprehensive reporting for business intelligence

### Partner Satisfaction
- **Transparent Tracking**: Clear visibility into earnings and performance
- **Timely Payments**: Regular and reliable commission payments
- **Performance Analytics**: Detailed analytics for optimization
- **Easy Management**: Simple interface for coupon and payment management

## 🔧 Maintenance and Support

### Regular Tasks
- **Payment Processing**: Process pending payments weekly/monthly
- **Performance Monitoring**: Monitor coupon performance and partner activity
- **Fraud Detection**: Review usage patterns for suspicious activity
- **Partner Communication**: Regular communication with partners

### Troubleshooting
- **Payment Issues**: Handle failed payments and bank account changes
- **Coupon Problems**: Resolve coupon validation and usage issues
- **Partner Disputes**: Address partner concerns and payment disputes
- **System Maintenance**: Regular system updates and maintenance

### Support Resources
- **Documentation**: Comprehensive documentation for all features
- **Training Materials**: Training guides for admin and partner users
- **Support Channels**: Multiple support channels for different user types
- **FAQ Section**: Frequently asked questions and answers

## 📞 Contact Information

For technical support or questions about the Coupon Code Management System:
- **Email**: support@sarvcast.com
- **Phone**: +98-21-1234-5678
- **Documentation**: https://docs.sarvcast.com/coupon-management
- **Admin Panel**: https://admin.sarvcast.com/coupons

---

*This documentation is regularly updated to reflect system changes and improvements. Last updated: January 2024*
