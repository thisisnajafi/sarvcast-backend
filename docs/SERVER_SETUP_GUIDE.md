# Server Setup Guide for Manji Application

## 🚀 Post-Deployment Server Setup

After the GitHub Actions workflow completes the FTP deployment, follow these steps to ensure your Laravel application works properly on the server.

---

## 📋 **Step-by-Step Server Setup**

### **1. Access Your Server**
```bash
# Option A: SSH Access
ssh user@your-server.com

# Option B: cPanel Terminal
# Login to cPanel → Terminal

# Option C: Hosting Panel Terminal
# Use your hosting provider's terminal feature
```

### **2. Navigate to Application Directory**
```bash
cd /path/to/your/manji/application
# Example: cd /public_html/manji
# Example: cd /var/www/html/manji
```

### **3. Install Composer Dependencies**
```bash
# Install production dependencies only
composer install --no-dev --optimize-autoloader --no-interaction

# This will:
# ✅ Install only production packages
# ✅ Optimize autoloader for better performance
# ✅ Skip development dependencies
```

### **4. Clear Laravel Caches**
```bash
# Clear all existing caches
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
```

### **5. Generate Fresh Caches**
```bash
# Generate optimized caches for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### **6. Set Proper File Permissions**
```bash
# Set correct permissions for Laravel
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# Alternative for different web server users:
# chown -R apache:apache storage bootstrap/cache
# chown -R nginx:nginx storage bootstrap/cache
```

### **7. Create Storage Symlink (if needed)**
```bash
# Create symbolic link for public storage
php artisan storage:link
```

### **8. Verify Application**
```bash
# Test if Laravel is working
php artisan --version
php artisan route:list | head -5
```

---

## 🔧 **Complete Setup Script**

Create this script on your server for easy setup:

```bash
#!/bin/bash
# save as: setup-manji.sh

echo "🚀 Setting up Manji Application..."

# Navigate to application directory
cd /path/to/your/manji

# Install dependencies
echo "📦 Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

# Clear caches
echo "🧹 Clearing Laravel caches..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Generate fresh caches
echo "⚡ Generating optimized caches..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set permissions
echo "🔐 Setting file permissions..."
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# Create storage symlink
echo "📁 Creating storage symlink..."
php artisan storage:link

# Verify setup
echo "✅ Verifying setup..."
php artisan --version

echo "🎉 Manji setup complete!"
```

**Make it executable and run:**
```bash
chmod +x setup-manji.sh
./setup-manji.sh
```

---

## 🛠️ **Troubleshooting Common Issues**

### **Issue 1: Composer Not Found**
```bash
# Install Composer globally
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
```

### **Issue 2: Permission Denied**
```bash
# Fix ownership and permissions
sudo chown -R www-data:www-data /path/to/manji
sudo chmod -R 755 /path/to/manji
sudo chmod -R 775 /path/to/manji/storage
sudo chmod -R 775 /path/to/manji/bootstrap/cache
```

### **Issue 3: Missing PHP Extensions**
```bash
# Install required PHP extensions
sudo apt-get install php-mbstring php-xml php-zip php-gd php-curl php-mysql
# or for CentOS/RHEL:
sudo yum install php-mbstring php-xml php-zip php-gd php-curl php-mysql
```

### **Issue 4: Database Connection Issues**
```bash
# Check database configuration
php artisan config:show database

# Test database connection
php artisan migrate:status
```

### **Issue 5: Storage Link Issues**
```bash
# Remove existing link and recreate
rm public/storage
php artisan storage:link
```

---

## 📊 **Environment Configuration**

### **Production .env Settings**
Ensure your `.env` file has these production settings:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=your-db-host
DB_PORT=3306
DB_DATABASE=your-database
DB_USERNAME=your-username
DB_PASSWORD=your-password

CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=database

# SMS Configuration
MELIPAYAMK_TOKEN=your-token
MELIPAYAMK_SENDER=your-sender

# Payment Configuration
ZARINPAL_MERCHANT_ID=your-merchant-id
```

---

## 🔍 **Verification Checklist**

After setup, verify these items:

- [ ] **Composer dependencies installed** (`composer install` completed)
- [ ] **Laravel caches generated** (`php artisan config:cache` successful)
- [ ] **File permissions set** (storage and bootstrap/cache writable)
- [ ] **Storage symlink created** (`php artisan storage:link` successful)
- [ ] **Application accessible** (website loads without errors)
- [ ] **Admin panel working** (`/admin` route accessible)
- [ ] **API endpoints responding** (`/api/v1/health` returns success)
- [ ] **Database connected** (no database errors in logs)

---

## 🚨 **Important Notes**

### **Security Considerations:**
- Never commit `.env` file to version control
- Use strong database passwords
- Enable HTTPS in production
- Set proper file permissions

### **Performance Optimization:**
- Use `--no-dev` flag for Composer
- Generate optimized caches
- Enable OPcache if available
- Use CDN for static assets

### **Monitoring:**
- Check application logs regularly
- Monitor server resources
- Set up error tracking
- Monitor database performance

---

## 📞 **Support**

If you encounter issues:

1. **Check Laravel logs**: `storage/logs/laravel.log`
2. **Verify file permissions**: Ensure storage is writable
3. **Test database connection**: `php artisan migrate:status`
4. **Check web server configuration**: Ensure proper document root
5. **Review error logs**: Check web server error logs

---

## ✅ **Success Indicators**

Your Manji application is properly set up when:

- ✅ Website loads without errors
- ✅ Admin panel is accessible
- ✅ API endpoints respond correctly
- ✅ File uploads work
- ✅ Database operations succeed
- ✅ No PHP errors in logs
- ✅ Performance is optimal

**🎉 Congratulations! Your Manji application is now ready for production use!**
