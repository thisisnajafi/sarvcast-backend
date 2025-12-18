# SarvCast Production Deployment Guide

## Overview
This guide provides step-by-step instructions for deploying the SarvCast Laravel application to a production server.

## Prerequisites

### Server Requirements
- **Operating System**: Ubuntu 20.04 LTS or CentOS 8+
- **PHP**: 8.2+ with required extensions
- **Web Server**: Nginx 1.18+ or Apache 2.4+
- **Database**: MySQL 8.0+ or MariaDB 10.6+
- **Cache**: Redis 6.0+
- **SSL**: Let's Encrypt certificate
- **Domain**: api.sarvcast.com (for API)
- **Subdomain**: admin.sarvcast.com (for admin dashboard)

### Required PHP Extensions
```bash
php8.2-cli php8.2-fpm php8.2-mysql php8.2-xml php8.2-gd php8.2-curl php8.2-zip php8.2-mbstring php8.2-bcmath php8.2-redis php8.2-intl
```

## Server Setup

### 1. Update System
```bash
sudo apt update && sudo apt upgrade -y
```

### 2. Install Required Software
```bash
# Install PHP 8.2
sudo apt install software-properties-common -y
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install php8.2-cli php8.2-fpm php8.2-mysql php8.2-xml php8.2-gd php8.2-curl php8.2-zip php8.2-mbstring php8.2-bcmath php8.2-redis php8.2-intl -y

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install Node.js and NPM
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install nodejs -y

# Install Nginx
sudo apt install nginx -y

# Install MySQL
sudo apt install mysql-server -y

# Install Redis
sudo apt install redis-server -y

# Install Git
sudo apt install git -y
```

### 3. Configure MySQL
```bash
sudo mysql_secure_installation
```

Create database and user:
```sql
CREATE DATABASE sarvcast_production CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'sarvcast_user'@'localhost' IDENTIFIED BY 'YOUR_SECURE_PASSWORD';
GRANT ALL PRIVILEGES ON sarvcast_production.* TO 'sarvcast_user'@'localhost';
FLUSH PRIVILEGES;
```

### 4. Configure Redis
```bash
sudo systemctl enable redis-server
sudo systemctl start redis-server
```

## Application Deployment

### 1. Clone Repository
```bash
cd /var/www
sudo git clone https://github.com/your-username/sarvcast.git
sudo chown -R www-data:www-data sarvcast
cd sarvcast
```

### 2. Install Dependencies
```bash
composer install --optimize-autoloader --no-dev
npm install
npm run build
```

### 3. Environment Configuration
```bash
cp .env.example .env
```

Edit `.env` file with production values:
```env
APP_NAME="SarvCast"
APP_ENV=production
APP_KEY=base64:YOUR_APP_KEY_HERE
APP_DEBUG=false
APP_URL=https://api.sarvcast.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sarvcast_production
DB_USERNAME=sarvcast_user
DB_PASSWORD=YOUR_SECURE_DB_PASSWORD

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=noreply@sarvcast.com
MAIL_PASSWORD=YOUR_EMAIL_PASSWORD
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@sarvcast.com
MAIL_FROM_NAME="SarvCast"

SMS_API_KEY=YOUR_SMS_API_KEY
SMS_SENDER_NUMBER=10008663

ZARINPAL_MERCHANT_ID=YOUR_ZARINPAL_MERCHANT_ID
ZARINPAL_SANDBOX=false
ZARINPAL_CALLBACK_URL=https://api.sarvcast.com/payment/zarinpal/callback

FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=YOUR_AWS_ACCESS_KEY
AWS_SECRET_ACCESS_KEY=YOUR_AWS_SECRET_KEY
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=sarvcast-storage
AWS_URL=https://sarvcast-storage.s3.amazonaws.com

FIREBASE_SERVER_KEY=YOUR_FIREBASE_SERVER_KEY
FIREBASE_PROJECT_ID=sarvcast-app

LOG_CHANNEL=daily
LOG_LEVEL=info

FORCE_HTTPS=true
SECURE_COOKIES=true
```

### 4. Generate Application Key
```bash
php artisan key:generate
```

### 5. Run Database Migrations
```bash
php artisan migrate --force
```

### 6. Seed Database
```bash
php artisan db:seed --force
```

### 7. Set Permissions
```bash
sudo chown -R www-data:www-data /var/www/sarvcast
sudo chmod -R 755 /var/www/sarvcast
sudo chmod -R 775 /var/www/sarvcast/storage
sudo chmod -R 775 /var/www/sarvcast/bootstrap/cache
```

## Nginx Configuration

### 1. Create Nginx Configuration
```bash
sudo nano /etc/nginx/sites-available/sarvcast
```

Add the following configuration:
```nginx
# API Server Configuration
server {
    listen 80;
    server_name api.sarvcast.com;
    root /var/www/sarvcast/public;
    index index.php;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;

    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_proxied expired no-cache no-store private must-revalidate auth;
    gzip_types text/plain text/css text/xml text/javascript application/x-javascript application/xml+rss application/json;

    # Rate limiting
    limit_req_zone $binary_remote_addr zone=api:10m rate=10r/s;
    limit_req_zone $binary_remote_addr zone=auth:10m rate=5r/s;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location /api/ {
        limit_req zone=api burst=20 nodelay;
        try_files $uri $uri/ /index.php?$query_string;
    }

    location /api/auth/ {
        limit_req zone=auth burst=10 nodelay;
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Static files caching
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|woff|woff2|ttf|svg)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # Audio files caching
    location ~* \.(mp3|m4a|wav)$ {
        expires 1M;
        add_header Cache-Control "public";
    }
}

# Admin Dashboard Configuration
server {
    listen 80;
    server_name admin.sarvcast.com;
    root /var/www/sarvcast/public;
    index index.php;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;

    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_proxied expired no-cache no-store private must-revalidate auth;
    gzip_types text/plain text/css text/xml text/javascript application/x-javascript application/xml+rss application/json;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Static files caching
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|woff|woff2|ttf|svg)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

### 2. Enable Site
```bash
sudo ln -s /etc/nginx/sites-available/sarvcast /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

## SSL Certificate Setup

### 1. Install Certbot
```bash
sudo apt install certbot python3-certbot-nginx -y
```

### 2. Obtain SSL Certificates
```bash
sudo certbot --nginx -d api.sarvcast.com -d admin.sarvcast.com
```

### 3. Auto-renewal Setup
```bash
sudo crontab -e
```

Add the following line:
```bash
0 12 * * * /usr/bin/certbot renew --quiet
```

## PHP-FPM Configuration

### 1. Configure PHP-FPM
```bash
sudo nano /etc/php/8.2/fpm/pool.d/www.conf
```

Update the following settings:
```ini
user = www-data
group = www-data
listen = /var/run/php/php8.2-fpm.sock
listen.owner = www-data
listen.group = www-data
listen.mode = 0660

pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35
pm.max_requests = 1000

php_admin_value[memory_limit] = 256M
php_admin_value[max_execution_time] = 300
php_admin_value[upload_max_filesize] = 100M
php_admin_value[post_max_size] = 100M
```

### 2. Restart PHP-FPM
```bash
sudo systemctl restart php8.2-fpm
```

## Queue Worker Setup

### 1. Create Queue Worker Service
```bash
sudo nano /etc/systemd/system/sarvcast-worker.service
```

Add the following content:
```ini
[Unit]
Description=SarvCast Queue Worker
After=network.target

[Service]
User=www-data
Group=www-data
Restart=always
ExecStart=/usr/bin/php /var/www/sarvcast/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
WorkingDirectory=/var/www/sarvcast

[Install]
WantedBy=multi-user.target
```

### 2. Enable and Start Service
```bash
sudo systemctl enable sarvcast-worker
sudo systemctl start sarvcast-worker
```

## Cron Jobs Setup

### 1. Add Laravel Scheduler
```bash
sudo crontab -e
```

Add the following line:
```bash
* * * * * cd /var/www/sarvcast && php artisan schedule:run >> /dev/null 2>&1
```

## Monitoring Setup

### 1. Install Monitoring Tools
```bash
# Install htop for system monitoring
sudo apt install htop -y

# Install fail2ban for security
sudo apt install fail2ban -y
```

### 2. Configure Fail2ban
```bash
sudo nano /etc/fail2ban/jail.local
```

Add the following configuration:
```ini
[DEFAULT]
bantime = 3600
findtime = 600
maxretry = 5

[nginx-http-auth]
enabled = true

[nginx-limit-req]
enabled = true
filter = nginx-limit-req
action = iptables-multiport[name=ReqLimit, port="http,https", protocol=tcp]
logpath = /var/log/nginx/error.log
maxretry = 10
```

### 3. Start Fail2ban
```bash
sudo systemctl enable fail2ban
sudo systemctl start fail2ban
```

## Backup Setup

### 1. Create Backup Directory
```bash
sudo mkdir -p /var/backups/sarvcast
sudo chown www-data:www-data /var/backups/sarvcast
```

### 2. Configure Automated Backups
```bash
sudo crontab -e
```

Add the following lines:
```bash
# Daily full backup at 2 AM
0 2 * * * cd /var/www/sarvcast && php artisan sarvcast:backup --type=full >> /var/log/sarvcast-backup.log 2>&1

# Weekly database backup on Sundays at 3 AM
0 3 * * 0 cd /var/www/sarvcast && php artisan sarvcast:backup --type=database >> /var/log/sarvcast-backup.log 2>&1

# Cleanup old backups monthly
0 4 1 * * cd /var/www/sarvcast && php artisan sarvcast:backup --cleanup >> /var/log/sarvcast-backup.log 2>&1
```

## Performance Optimization

### 1. Configure OPcache
```bash
sudo nano /etc/php/8.2/fpm/conf.d/10-opcache.ini
```

Add the following configuration:
```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=4000
opcache.revalidate_freq=2
opcache.fast_shutdown=1
opcache.enable_cli=1
```

### 2. Configure Redis
```bash
sudo nano /etc/redis/redis.conf
```

Update the following settings:
```conf
maxmemory 256mb
maxmemory-policy allkeys-lru
save 900 1
save 300 10
save 60 10000
```

### 3. Restart Services
```bash
sudo systemctl restart php8.2-fpm
sudo systemctl restart redis-server
```

## Security Hardening

### 1. Configure Firewall
```bash
sudo ufw enable
sudo ufw allow 22
sudo ufw allow 80
sudo ufw allow 443
sudo ufw deny 3306
sudo ufw deny 6379
```

### 2. Secure SSH
```bash
sudo nano /etc/ssh/sshd_config
```

Update the following settings:
```conf
Port 22
PermitRootLogin no
PasswordAuthentication no
PubkeyAuthentication yes
MaxAuthTries 3
ClientAliveInterval 300
ClientAliveCountMax 2
```

### 3. Restart SSH
```bash
sudo systemctl restart ssh
```

## Testing Deployment

### 1. Test API Endpoints
```bash
# Test health check
curl https://api.sarvcast.com/api/v1/health

# Test categories endpoint
curl https://api.sarvcast.com/api/v1/categories
```

### 2. Test Admin Dashboard
```bash
# Test admin login page
curl https://admin.sarvcast.com/admin/auth/login
```

### 3. Test SSL Certificate
```bash
# Check SSL certificate
openssl s_client -connect api.sarvcast.com:443 -servername api.sarvcast.com
```

## Maintenance Commands

### 1. Application Maintenance
```bash
# Clear application cache
php artisan cache:clear

# Clear configuration cache
php artisan config:clear

# Clear route cache
php artisan route:clear

# Clear view cache
php artisan view:clear

# Optimize application
php artisan optimize

# Run performance optimization
php artisan sarvcast:optimize-performance
```

### 2. Database Maintenance
```bash
# Run database migrations
php artisan migrate --force

# Seed database
php artisan db:seed --force

# Create backup
php artisan sarvcast:backup --type=full

# List backups
php artisan sarvcast:backup --list
```

### 3. Monitoring Commands
```bash
# Check application health
php artisan sarvcast:monitor

# View system metrics
php artisan sarvcast:monitor --metrics

# Check error rates
php artisan sarvcast:monitor --errors
```

## Troubleshooting

### Common Issues

#### 1. Permission Issues
```bash
sudo chown -R www-data:www-data /var/www/sarvcast
sudo chmod -R 755 /var/www/sarvcast
sudo chmod -R 775 /var/www/sarvcast/storage
sudo chmod -R 775 /var/www/sarvcast/bootstrap/cache
```

#### 2. Database Connection Issues
```bash
# Check MySQL status
sudo systemctl status mysql

# Check database connection
php artisan tinker
>>> DB::connection()->getPdo();
```

#### 3. Redis Connection Issues
```bash
# Check Redis status
sudo systemctl status redis-server

# Test Redis connection
redis-cli ping
```

#### 4. Queue Worker Issues
```bash
# Check queue worker status
sudo systemctl status sarvcast-worker

# Restart queue worker
sudo systemctl restart sarvcast-worker
```

#### 5. SSL Certificate Issues
```bash
# Check certificate status
sudo certbot certificates

# Renew certificate
sudo certbot renew --dry-run
```

## Post-Deployment Checklist

- [ ] API endpoints responding correctly
- [ ] Admin dashboard accessible
- [ ] SSL certificates installed and working
- [ ] Database migrations completed
- [ ] Queue workers running
- [ ] Cron jobs configured
- [ ] Backup system working
- [ ] Monitoring setup complete
- [ ] Security measures implemented
- [ ] Performance optimization applied
- [ ] Error logging configured
- [ ] Rate limiting working
- [ ] File uploads working
- [ ] SMS service configured
- [ ] Payment gateway configured
- [ ] Email service configured
- [ ] Push notifications configured

## Support and Maintenance

### Regular Maintenance Tasks
1. **Daily**: Monitor system health and logs
2. **Weekly**: Check backup integrity and performance
3. **Monthly**: Update dependencies and security patches
4. **Quarterly**: Review and optimize database performance

### Monitoring Endpoints
- **Health Check**: https://api.sarvcast.com/api/v1/health
- **Metrics**: https://api.sarvcast.com/api/v1/health/metrics
- **Admin Dashboard**: https://admin.sarvcast.com

### Support Contacts
- **Technical Support**: support@sarvcast.com
- **System Administrator**: admin@sarvcast.com
- **Emergency Contact**: +98-XXX-XXX-XXXX

## Conclusion

This deployment guide provides comprehensive instructions for setting up SarvCast in a production environment. Follow all steps carefully and test thoroughly before going live. Regular monitoring and maintenance are essential for optimal performance and security.
