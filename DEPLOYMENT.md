# SarvCast Deployment Guide

This guide provides comprehensive instructions for deploying the SarvCast Laravel application.

## 📋 Prerequisites

- PHP 8.2 or higher
- Composer
- MySQL 8.0 or higher
- Redis (optional, for caching and queues)
- Web server (Apache/Nginx)
- SSL certificate (for production)

## 🚀 Quick Deployment

### 1. Clone and Setup

```bash
git clone <repository-url>
cd sarvcast
composer install
cp .env.example .env
```

### 2. Environment Configuration

Update your `.env` file with the following essential configurations:

```env
APP_NAME="سروکست"
APP_ENV=production
APP_KEY=base64:your-generated-key
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sarvcast
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-email
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@sarvcast.com
MAIL_FROM_NAME="سروکست"

# Payment Gateways
ZARINPAL_MERCHANT_ID=your-zarinpal-merchant-id
ZARINPAL_SANDBOX=false
PAYIR_MERCHANT_ID=your-payir-merchant-id
PAYIR_SANDBOX=false

# File Storage
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=your-aws-key
AWS_SECRET_ACCESS_KEY=your-aws-secret
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your-s3-bucket

# Notifications
FIREBASE_SERVER_KEY=your-firebase-server-key
FIREBASE_PROJECT_ID=your-firebase-project-id
SMS_API_KEY=your-sms-api-key
```

### 3. Run Deployment Script

```bash
chmod +x deploy.sh
./deploy.sh
```

## 🐳 Docker Deployment

### 1. Using Docker Compose

```bash
# Build and start all services
docker-compose up -d

# Run migrations
docker-compose exec app php artisan migrate

# Create storage symlink
docker-compose exec app php artisan storage:link

# Seed database (optional)
docker-compose exec app php artisan db:seed
```

### 2. Individual Docker Commands

```bash
# Build the application image
docker build -t sarvcast-app .

# Run the application
docker run -d -p 80:80 --name sarvcast-app sarvcast-app
```

## 🔧 Manual Deployment Steps

### 1. Install Dependencies

```bash
composer install --no-dev --optimize-autoloader
```

### 2. Generate Application Key

```bash
php artisan key:generate
```

### 3. Run Database Migrations

```bash
php artisan migrate --force
```

### 4. Cache Configuration

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 5. Create Storage Symlink

```bash
php artisan storage:link
```

### 6. Set Permissions

```bash
chmod -R 755 storage
chmod -R 755 bootstrap/cache
```

## 🌐 Web Server Configuration

### Apache Configuration

Create a virtual host configuration:

```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    DocumentRoot /path/to/sarvcast/public
    
    <Directory /path/to/sarvcast/public>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/sarvcast_error.log
    CustomLog ${APACHE_LOG_DIR}/sarvcast_access.log combined
</VirtualHost>
```

### Nginx Configuration

```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /path/to/sarvcast/public;
    
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    
    index index.php;
    
    charset utf-8;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }
    
    error_page 404 /index.php;
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

## 🔐 SSL Configuration

### Using Let's Encrypt

```bash
# Install Certbot
sudo apt install certbot python3-certbot-apache

# Get SSL certificate
sudo certbot --apache -d yourdomain.com

# Auto-renewal
sudo crontab -e
# Add: 0 12 * * * /usr/bin/certbot renew --quiet
```

## 📊 Monitoring and Maintenance

### 1. Set Up Cron Jobs

```bash
# Edit crontab
crontab -e

# Add Laravel scheduler
* * * * * cd /path/to/sarvcast && php artisan schedule:run >> /dev/null 2>&1

# Add log rotation
0 0 * * * find /path/to/sarvcast/storage/logs -name "*.log" -mtime +7 -delete
```

### 2. Queue Workers

```bash
# Start queue worker
php artisan queue:work --daemon

# Or use Supervisor for process management
```

### 3. Log Rotation

Create `/etc/logrotate.d/sarvcast`:

```
/path/to/sarvcast/storage/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    notifempty
    create 0644 www-data www-data
}
```

## 🧪 Testing

### Run Tests

```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Feature

# Run with coverage
php artisan test --coverage
```

### Test Database

```bash
# Create test database
mysql -u root -p -e "CREATE DATABASE sarvcast_test;"

# Run tests with test database
php artisan test --env=testing
```

## 🔧 Performance Optimization

### 1. Enable OPcache

Add to `php.ini`:

```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=4000
opcache.revalidate_freq=2
opcache.fast_shutdown=1
```

### 2. Database Optimization

```bash
# Optimize database
php artisan optimize

# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### 3. File Storage Optimization

```bash
# Optimize images
php artisan images:optimize

# Clean old files
php artisan storage:clean
```

## 🚨 Troubleshooting

### Common Issues

1. **Permission Errors**
   ```bash
   sudo chown -R www-data:www-data storage
   sudo chmod -R 755 storage
   ```

2. **Database Connection Issues**
   - Check database credentials in `.env`
   - Ensure database server is running
   - Verify network connectivity

3. **Storage Symlink Issues**
   ```bash
   php artisan storage:link
   ```

4. **Cache Issues**
   ```bash
   php artisan cache:clear
   php artisan config:clear
   ```

### Log Files

- Application logs: `storage/logs/laravel.log`
- Web server logs: `/var/log/apache2/` or `/var/log/nginx/`
- PHP logs: `/var/log/php/`

## 📈 Scaling

### Horizontal Scaling

1. **Load Balancer Configuration**
2. **Database Replication**
3. **Redis Cluster Setup**
4. **CDN Integration**

### Vertical Scaling

1. **Increase Server Resources**
2. **Optimize Database Queries**
3. **Implement Caching Strategies**
4. **Use Queue Workers**

## 🔒 Security Checklist

- [ ] SSL certificate installed
- [ ] Environment variables secured
- [ ] Database credentials protected
- [ ] File permissions set correctly
- [ ] Security headers configured
- [ ] Regular backups scheduled
- [ ] Monitoring and alerting setup
- [ ] Firewall configured
- [ ] Regular security updates

## 📞 Support

For deployment issues or questions:

1. Check the logs first
2. Review this documentation
3. Contact the development team
4. Create an issue in the repository

---

**Note**: This deployment guide assumes a Linux/Unix environment. Adjust commands accordingly for Windows or other operating systems.
