# Admin Management System Documentation

## 📋 Overview

The Admin Management System provides comprehensive control over all aspects of the SarvCast platform, including coin management, coupon codes, commission payments, and partner management. Since the user-facing side is handled by the Flutter app via API, all user interactions and management are centralized in the admin dashboard.

## 🎯 System Architecture

### Core Components

1. **Admin Dashboard**: Centralized overview of all system metrics
2. **Coin Management**: Complete control over the coin system
3. **Coupon Management**: Revenue sharing and discount code management
4. **Commission Payments**: Automated and manual payment processing
5. **Partner Management**: Affiliate, influencer, and teacher management
6. **Analytics Dashboard**: Comprehensive reporting and insights

## 🏗️ Admin Dashboard Structure

### Main Dashboard (`/admin`)
- **Quick Stats**: Real-time metrics for users, coins, coupons, and payments
- **Management Sections**: Quick access to all management features
- **Analytics Links**: Direct access to detailed analytics
- **Auto-refresh**: Updates every 5 minutes for real-time data

### Navigation Menu
- **Dashboard**: Main overview page
- **Content Management**: Stories, episodes, categories, people
- **User Management**: User accounts and subscriptions
- **Coin Management**: Complete coin system control
- **Coupon Management**: Revenue sharing platform
- **Commission Payments**: Payment processing and tracking
- **Partner Management**: Affiliate and influencer management
- **Analytics**: Detailed reporting and insights

## 💰 Coin Management System

### Features
- **User Coin Balances**: View and manage all user coin balances
- **Transaction History**: Complete audit trail of all coin transactions
- **Manual Coin Awards**: Admin can award coins to users manually
- **Redemption Options**: Manage coin redemption options (subscription days, premium content, etc.)
- **Statistics**: Real-time statistics and analytics

### Admin Capabilities
- **Award Coins**: Manually award coins to specific users
- **View Transactions**: Complete transaction history with filtering
- **Manage Redemption**: Create and manage coin redemption options
- **User Management**: View user coin balances and activity
- **Analytics**: Detailed coin system analytics

### API Endpoints
- `GET /api/v1/coins/admin-transactions` - Get all coin transactions
- `GET /api/v1/coins/admin-users` - Get users with coin balances
- `GET /api/v1/coins/admin-redemption-options` - Get redemption options
- `POST /api/v1/coins/award` - Award coins to users
- `POST /api/v1/coins/admin-redemption-options` - Create redemption option
- `PUT /api/v1/coins/admin-redemption-options/{id}/toggle` - Toggle redemption option
- `DELETE /api/v1/coins/admin-redemption-options/{id}` - Delete redemption option

## 🎫 Coupon Management System

### Features
- **Coupon Creation**: Create unique coupon codes for partners
- **Revenue Sharing**: Automatic commission calculation
- **Usage Tracking**: Complete analytics of coupon performance
- **Partner Assignment**: Assign coupons to specific partners
- **Bulk Operations**: Manage multiple coupons simultaneously

### Admin Capabilities
- **Create Coupons**: Generate unique coupon codes with specific parameters
- **Partner Management**: Assign coupons to influencers, teachers, and partners
- **Usage Monitoring**: Track coupon usage and performance
- **Revenue Tracking**: Monitor revenue generated from coupons
- **Commission Management**: View and manage commission calculations

### Coupon Types
- **Influencer Coupons**: 20% commission + 15-25% user discount
- **Teacher Coupons**: 15% commission + 10-20% user discount
- **Partner Coupons**: 10% commission + 5-15% user discount
- **Promotional Coupons**: 0% commission + 10-30% user discount

### API Endpoints
- `GET /api/v1/coupons/all` - Get all coupon codes
- `POST /api/v1/coupons/create` - Create new coupon code
- `PUT /api/v1/coupons/{id}/toggle-status` - Toggle coupon status
- `DELETE /api/v1/coupons/{id}` - Delete coupon code
- `GET /api/v1/coupons/global-statistics` - Get coupon statistics

## 💳 Commission Payment System

### Features
- **Payment Processing**: Manual and automated payment processing
- **Status Tracking**: Complete payment workflow management
- **Bulk Operations**: Process multiple payments simultaneously
- **Payment Reset**: Admin permission to reset payment status
- **Audit Trail**: Complete history of all payment operations

### Admin Capabilities
- **Manual Payments**: Create manual payments for partners
- **Bulk Processing**: Process multiple payments at once
- **Status Management**: Update payment status (pending, processing, paid, failed)
- **Payment Reset**: Reset payment status for corrections
- **Transaction Tracking**: Complete audit trail of all operations

### Payment Workflow
```
Pending → Processing → Paid
   ↓         ↓         ↓
Created   Admin      Completed
         Action
```

### API Endpoints
- `GET /api/v1/commission-payments/all` - Get all payments
- `GET /api/v1/commission-payments/pending` - Get pending payments
- `POST /api/v1/commission-payments/process` - Process payment
- `POST /api/v1/commission-payments/mark-paid` - Mark as paid
- `POST /api/v1/commission-payments/mark-failed` - Mark as failed
- `POST /api/v1/commission-payments/bulk-process` - Bulk process payments
- `POST /api/v1/commission-payments/create-manual` - Create manual payment
- `GET /api/v1/commission-payments/statistics` - Get payment statistics

## 🤝 Partner Management System

### Features
- **Affiliate Partners**: Manage affiliate partnerships
- **Influencer Management**: Track influencer campaigns and content
- **Teacher Accounts**: Manage educator accounts and student licenses
- **School Partnerships**: Handle institutional partnerships
- **Corporate Sponsorships**: Manage corporate partnerships

### Admin Capabilities
- **Partner Onboarding**: Review and approve partner applications
- **Performance Monitoring**: Track partner performance and compliance
- **Commission Management**: Calculate and process partner commissions
- **Content Approval**: Approve partner-generated content
- **Analytics**: Detailed partner performance analytics

## 📊 Analytics and Reporting

### Coin Analytics
- **Overview**: Total coins in circulation, users with coins, earning/spending patterns
- **Earning Sources**: Breakdown by quiz rewards, referrals, story completion
- **Spending Patterns**: Analysis of coin usage and redemption trends
- **Transaction Trends**: Historical transaction data and patterns
- **User Distribution**: Coin balance distribution among users
- **Quiz Performance**: Quiz-related coin earning analytics
- **Referral Performance**: Referral-based coin earning analytics
- **Top Earners**: Users with highest coin earnings
- **System Health**: Overall coin system health metrics

### Referral Analytics
- **Overview**: Total referral codes, completed referrals, conversion rates
- **Trends**: Historical referral performance and growth trends
- **Top Referrers**: Users with most successful referrals
- **Sources**: Referral source analysis and performance
- **Performance by Timeframe**: Time-based referral analysis
- **Funnel Analysis**: User journey from referral to conversion
- **Geographic Distribution**: Geographic analysis of referrals
- **Revenue Analysis**: Revenue impact of referral system
- **System Health**: Overall referral system health metrics

## 🔐 Security and Permissions

### Admin Roles
- **Super Admin**: Full access to all features
- **Payment Admin**: Can process payments and view financial data
- **Partner Admin**: Can manage partners and create coupons
- **Analytics Admin**: Can view reports and analytics
- **Coin Admin**: Can manage coin system and award coins

### Security Features
- **Role-based Access Control**: Different access levels for different admin types
- **Audit Logging**: Complete audit trail of all admin operations
- **Payment Security**: Secure payment processing and data protection
- **Fraud Prevention**: Usage limits and validation to prevent abuse
- **Session Management**: Secure session handling and timeout

## 🚀 Implementation Guide

### Database Schema
All necessary database tables are already created:
- `user_coins`: User coin balances
- `coin_transactions`: Coin transaction history
- `coupon_codes`: Coupon code information
- `coupon_usages`: Coupon usage tracking
- `commission_payments`: Commission payment processing
- `affiliate_partners`: Partner information
- `referral_codes`: Referral code management
- `referrals`: Referral tracking

### API Integration
All API endpoints are implemented and ready for Flutter app integration:
- **Authentication**: Laravel Sanctum for API authentication
- **Rate Limiting**: API rate limiting for security
- **Validation**: Comprehensive input validation
- **Error Handling**: Proper error responses and status codes
- **Documentation**: Complete API documentation available

### Admin Interface
- **Responsive Design**: Works on all device sizes
- **Real-time Updates**: Auto-refresh for live data
- **Interactive Charts**: Visual analytics and reporting
- **Bulk Operations**: Efficient management of multiple items
- **Search and Filtering**: Advanced search and filter capabilities

## 📱 Flutter App Integration

### User-facing Features (Flutter App)
- **Coin Balance**: View current coin balance
- **Transaction History**: View coin transaction history
- **Quiz Participation**: Take quizzes to earn coins
- **Referral System**: Share referral codes with friends
- **Coupon Usage**: Use coupon codes for discounts
- **Redemption**: Convert coins to subscription days or premium content

### Admin Management (Web Dashboard)
- **All Management**: Complete control over all systems
- **User Support**: Handle user issues and support requests
- **System Monitoring**: Monitor system health and performance
- **Financial Management**: Process payments and manage commissions
- **Content Management**: Manage all platform content
- **Analytics**: Comprehensive reporting and insights

## 🔧 Maintenance and Support

### Regular Tasks
- **Payment Processing**: Process pending payments weekly/monthly
- **Performance Monitoring**: Monitor system performance and user activity
- **Fraud Detection**: Review usage patterns for suspicious activity
- **Partner Communication**: Regular communication with partners
- **System Updates**: Regular system updates and maintenance

### Troubleshooting
- **Payment Issues**: Handle failed payments and bank account changes
- **Coupon Problems**: Resolve coupon validation and usage issues
- **Partner Disputes**: Address partner concerns and payment disputes
- **System Maintenance**: Regular system updates and maintenance
- **User Support**: Handle user support requests and issues

### Support Resources
- **Documentation**: Comprehensive documentation for all features
- **Training Materials**: Training guides for admin users
- **Support Channels**: Multiple support channels for different user types
- **FAQ Section**: Frequently asked questions and answers

## 📞 Contact Information

For technical support or questions about the Admin Management System:
- **Email**: admin-support@sarvcast.com
- **Phone**: +98-21-1234-5678
- **Documentation**: https://docs.sarvcast.com/admin-management
- **Admin Panel**: https://admin.sarvcast.com

---

*This documentation is regularly updated to reflect system changes and improvements. Last updated: January 2024*
