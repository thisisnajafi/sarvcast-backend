# SarvCast Environment Configuration Guide

## 🔧 Complete .env Configuration

Add these settings to your `.env` file for proper SarvCast functionality:

```env
# Application Settings
APP_NAME=SarvCast
APP_ENV=production
APP_KEY=base64:YOUR_APP_KEY_HERE
APP_DEBUG=false
APP_URL=https://my.sarvcast.ir

# Database Configuration
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_database_user
DB_PASSWORD=your_database_password

# Cache Settings (Disable All Caching)
DISABLE_ALL_CACHING=true
CACHE_DRIVER=array
SESSION_DRIVER=file
QUEUE_CONNECTION=sync

# Disable Specific Caching
VIEW_CACHE_ENABLED=false
ROUTE_CACHE_ENABLED=false
CONFIG_CACHE_ENABLED=false

# SMS Configuration (Melipayamak)
MELIPAYAMK_TOKEN=77c431b7-aec5-4313-b744-d2f16bf760ab
MELIPAYAMK_SENDER=50002710008883

# Payment Configuration (Zarinpal)
# Note: Merchant ID is hardcoded in the application
ZARINPAL_SANDBOX=false
PAYMENT_CALLBACK_URL=https://my.sarvcast.ir/payment/zarinpal/callback
PAYMENT_SUCCESS_URL=https://my.sarvcast.ir/payment/success
PAYMENT_FAILURE_URL=https://my.sarvcast.ir/payment/failure

# Logging
LOG_CHANNEL=stack
LOG_LEVEL=error

# Mail Configuration (if needed)
MAIL_MAILER=smtp
MAIL_HOST=your_smtp_host
MAIL_PORT=587
MAIL_USERNAME=your_email
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@sarvcast.ir
MAIL_FROM_NAME="SarvCast"

# File Storage
FILESYSTEM_DISK=local

# Broadcasting (if needed)
BROADCAST_DRIVER=log
PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_APP_CLUSTER=mt1
```

---

## 💳 Zarinpal Payment Configuration

### **Merchant ID Status:**
```
✅ HARDCODED: 1f8c6606-d923-4bdb-8d52-9affc9b877c8
```
**Note:** The merchant ID is now hardcoded in the application code and doesn't require environment configuration.

### **Payment URLs (Callback-based, NOT webhooks):**
- **Callback URL**: `https://my.sarvcast.ir/payment/zarinpal/callback` (Zarinpal redirects here after payment)
- **Success URL**: `https://my.sarvcast.ir/payment/success` (User sees this on successful payment)
- **Failure URL**: `https://my.sarvcast.ir/payment/failure` (User sees this on failed payment)

**Note:** Zarinpal uses callback redirects, not webhooks. The user is redirected back to your application after payment.

### **Subscription Plans Available:**
- **1 Month**: 50,000 IRR
- **3 Months**: 135,000 IRR (10% discount)
- **6 Months**: 240,000 IRR (20% discount)
- **1 Year**: 400,000 IRR (33% discount)

---

## 📱 SMS Configuration

### **Melipayamak Settings:**
```
MELIPAYAMK_TOKEN=77c431b7-aec5-4313-b744-d2f16bf760ab
MELIPAYAMK_SENDER=50002710008883
```

---

## 🚫 Caching Configuration

### **Disable All Caching:**
```env
DISABLE_ALL_CACHING=true
CACHE_DRIVER=array
SESSION_DRIVER=file
QUEUE_CONNECTION=sync
VIEW_CACHE_ENABLED=false
ROUTE_CACHE_ENABLED=false
CONFIG_CACHE_ENABLED=false
```

---

## 🔐 Security Settings

### **Production Security:**
```env
APP_ENV=production
APP_DEBUG=false
LOG_LEVEL=error
```

### **Generate Application Key:**
```bash
php artisan key:generate
```

---

## 📋 Server Setup Checklist

After deployment, ensure these are configured:

- [ ] **Database connection** working
- [ ] **Application key** generated
- [ ] **Zarinpal merchant ID** added
- [ ] **SMS configuration** set
- [ ] **Caching disabled** (if needed)
- [ ] **File permissions** set correctly
- [ ] **Storage symlink** created
- [ ] **Payment URLs** accessible

---

## 🧪 Testing Configuration

### **Test Payment Integration:**
```bash
# Test Zarinpal connection
php artisan tinker
>>> app(\App\Services\PaymentService::class)->testConnection();
```

### **Test SMS Integration:**
```bash
# Test SMS service
php artisan tinker
>>> app(\App\Services\SmsService::class)->sendSms('09123456789', 'Test message');
```

---

## 🚀 Deployment Commands

### **After Server Setup:**
```bash
# Navigate to application directory
cd /home/sarvca/public_html/my

# Install dependencies
composer install --no-dev --optimize-autoloader --no-interaction

# Clear caches
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Set permissions
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# Create storage symlink
php artisan storage:link
```

---

## ✅ Verification

### **Check Application Status:**
```bash
php artisan --version
php artisan route:list | head -5
```

### **Test Payment System:**
1. Visit: `https://my.sarvcast.ir/api/v1/subscriptions/plans`
2. Should return subscription plans with prices
3. Test payment initiation works

### **Test SMS System:**
1. Try user registration
2. Should receive SMS verification code
3. Check SMS logs for delivery status

---

## 🎯 Your SarvCast Application is Ready!

With these configurations:
- ✅ **Payment system** configured with Zarinpal
- ✅ **SMS system** configured with Melipayamak
- ✅ **Caching disabled** for real-time changes
- ✅ **Production-ready** settings
- ✅ **Security optimized** configuration

Your SarvCast application is now fully configured and ready for production use! 🚀
