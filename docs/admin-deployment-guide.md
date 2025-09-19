# راهنمای استقرار پنل مدیریت SarvCast

## فهرست مطالب

1. [معرفی استقرار](#معرفی-استقرار)
2. [آماده‌سازی محیط](#آماده‌سازی-محیط)
3. [نصب وابستگی‌ها](#نصب-وابستگی‌ها)
4. [تنظیمات پایگاه داده](#تنظیمات-پایگاه-داده)
5. [تنظیمات سرور](#تنظیمات-سرور)
6. [استقرار کد](#استقرار-کد)
7. [تنظیمات امنیتی](#تنظیمات-امنیتی)
8. [تنظیمات SSL](#تنظیمات-ssl)
9. [تنظیمات DNS](#تنظیمات-dns)
10. [تست و نظارت](#تست-و-نظارت)

## معرفی استقرار

استقرار (Deployment) فرآیند انتقال کد از محیط توسعه به محیط تولید است که شامل مراحل مختلفی می‌شود.

### انواع استقرار
- **استقرار سنتی**: انتقال دستی کد به سرور
- **استقرار خودکار**: استفاده از ابزارهای CI/CD
- **استقرار کانتینری**: استفاده از Docker
- **استقرار ابری**: استفاده از سرویس‌های ابری

### مراحل استقرار
1. **آماده‌سازی محیط**: تنظیم سرور و وابستگی‌ها
2. **نصب وابستگی‌ها**: نصب PHP, MySQL, Apache/Nginx
3. **تنظیمات پایگاه داده**: ایجاد پایگاه داده و کاربران
4. **تنظیمات سرور**: تنظیم Apache/Nginx و PHP
5. **استقرار کد**: انتقال کد و تنظیمات
6. **تنظیمات امنیتی**: تنظیم فایروال و امنیت
7. **تنظیمات SSL**: نصب گواهی SSL
8. **تنظیمات DNS**: تنظیم رکوردهای DNS
9. **تست و نظارت**: تست عملکرد و نظارت

## آماده‌سازی محیط

### سیستم عامل

#### نصب Ubuntu Server 22.04
```bash
# به‌روزرسانی سیستم
sudo apt update && sudo apt upgrade -y

# نصب پکیج‌های ضروری
sudo apt install -y curl wget git unzip software-properties-common apt-transport-https ca-certificates gnupg lsb-release

# تنظیم timezone
sudo timedatectl set-timezone Asia/Tehran

# تنظیم hostname
sudo hostnamectl set-hostname sarvcast-server
```

#### نصب CentOS 8
```bash
# به‌روزرسانی سیستم
sudo dnf update -y

# نصب پکیج‌های ضروری
sudo dnf install -y curl wget git unzip epel-release

# تنظیم timezone
sudo timedatectl set-timezone Asia/Tehran

# تنظیم hostname
sudo hostnamectl set-hostname sarvcast-server
```

### کاربران و مجوزها

#### ایجاد کاربر ادمین
```bash
# ایجاد کاربر جدید
sudo adduser adminuser

# افزودن به گروه sudo
sudo usermod -aG sudo adminuser

# تنظیم SSH key
sudo mkdir -p /home/adminuser/.ssh
sudo chmod 700 /home/adminuser/.ssh
sudo chown adminuser:adminuser /home/adminuser/.ssh

# کپی کلید عمومی
sudo cp /path/to/public_key /home/adminuser/.ssh/authorized_keys
sudo chmod 600 /home/adminuser/.ssh/authorized_keys
sudo chown adminuser:adminuser /home/adminuser/.ssh/authorized_keys
```

#### تنظیمات SSH
```bash
# ویرایش فایل SSH
sudo nano /etc/ssh/sshd_config

# تنظیمات امنیتی
Port 2222
PermitRootLogin no
PasswordAuthentication no
PubkeyAuthentication yes
MaxAuthTries 3
ClientAliveInterval 300
ClientAliveCountMax 2

# راه‌اندازی مجدد SSH
sudo systemctl restart sshd
```

## نصب وابستگی‌ها

### نصب PHP 8.1

#### Ubuntu
```bash
# افزودن repository PHP
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

# نصب PHP و extensions
sudo apt install -y php8.1 php8.1-fpm php8.1-mysql php8.1-xml php8.1-mbstring php8.1-curl php8.1-zip php8.1-gd php8.1-intl php8.1-bcmath php8.1-redis php8.1-memcached

# تنظیمات PHP
sudo nano /etc/php/8.1/fpm/php.ini
```

#### CentOS
```bash
# نصب EPEL repository
sudo dnf install -y epel-release

# نصب Remi repository
sudo dnf install -y https://rpms.remirepo.net/enterprise/remi-release-8.rpm

# فعال‌سازی PHP 8.1
sudo dnf module enable php:remi-8.1 -y

# نصب PHP و extensions
sudo dnf install -y php php-fpm php-mysqlnd php-xml php-mbstring php-curl php-zip php-gd php-intl php-bcmath php-redis php-memcached
```

### نصب MySQL 8.0

#### Ubuntu
```bash
# نصب MySQL
sudo apt install -y mysql-server

# تنظیمات امنیتی
sudo mysql_secure_installation

# راه‌اندازی سرویس
sudo systemctl start mysql
sudo systemctl enable mysql
```

#### CentOS
```bash
# نصب MySQL
sudo dnf install -y mysql-server

# راه‌اندازی سرویس
sudo systemctl start mysqld
sudo systemctl enable mysqld

# تنظیمات امنیتی
sudo mysql_secure_installation
```

### نصب Apache

#### Ubuntu
```bash
# نصب Apache
sudo apt install -y apache2

# فعال‌سازی ماژول‌ها
sudo a2enmod rewrite
sudo a2enmod ssl
sudo a2enmod headers
sudo a2enmod deflate
sudo a2enmod expires

# راه‌اندازی سرویس
sudo systemctl start apache2
sudo systemctl enable apache2
```

#### CentOS
```bash
# نصب Apache
sudo dnf install -y httpd

# فعال‌سازی ماژول‌ها
sudo systemctl start httpd
sudo systemctl enable httpd

# تنظیمات فایروال
sudo firewall-cmd --permanent --add-service=http
sudo firewall-cmd --permanent --add-service=https
sudo firewall-cmd --reload
```

### نصب Nginx

#### Ubuntu
```bash
# نصب Nginx
sudo apt install -y nginx

# راه‌اندازی سرویس
sudo systemctl start nginx
sudo systemctl enable nginx
```

#### CentOS
```bash
# نصب Nginx
sudo dnf install -y nginx

# راه‌اندازی سرویس
sudo systemctl start nginx
sudo systemctl enable nginx
```

### نصب Composer

```bash
# دانلود Composer
curl -sS https://getcomposer.org/installer | php

# انتقال به مسیر global
sudo mv composer.phar /usr/local/bin/composer

# تنظیم مجوزها
sudo chmod +x /usr/local/bin/composer

# تست نصب
composer --version
```

### نصب Node.js

```bash
# نصب Node.js 18
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install -y nodejs

# تست نصب
node --version
npm --version
```

## تنظیمات پایگاه داده

### ایجاد پایگاه داده

```sql
-- ورود به MySQL
mysql -u root -p

-- ایجاد پایگاه داده
CREATE DATABASE sarvcast CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- ایجاد کاربر
CREATE USER 'sarvcast_user'@'localhost' IDENTIFIED BY 'StrongPassword123!';

-- اعطای مجوزها
GRANT ALL PRIVILEGES ON sarvcast.* TO 'sarvcast_user'@'localhost';

-- اعمال تغییرات
FLUSH PRIVILEGES;

-- خروج
EXIT;
```

### تنظیمات MySQL

```bash
# ویرایش فایل تنظیمات
sudo nano /etc/mysql/mysql.conf.d/mysqld.cnf

# تنظیمات بهینه
[mysqld]
innodb_buffer_pool_size = 1G
innodb_log_file_size = 256M
innodb_log_buffer_size = 16M
innodb_flush_log_at_trx_commit = 2
query_cache_size = 64M
query_cache_type = 1
max_connections = 200
table_open_cache = 2000
tmp_table_size = 64M
max_heap_table_size = 64M
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow.log
long_query_time = 2

# راه‌اندازی مجدد MySQL
sudo systemctl restart mysql
```

## تنظیمات سرور

### تنظیمات Apache

#### VirtualHost
```apache
# فایل: /etc/apache2/sites-available/sarvcast.conf

<VirtualHost *:80>
    ServerName sarvcast.com
    ServerAlias www.sarvcast.com
    DocumentRoot /var/www/sarvcast/public
    
    <Directory /var/www/sarvcast/public>
        AllowOverride All
        Require all granted
    </Directory>
    
    # تنظیمات عملکرد
    <Location />
        SetOutputFilter DEFLATE
        SetEnvIfNoCase Request_URI \
            \.(?:gif|jpe?g|png)$ no-gzip dont-vary
        SetEnvIfNoCase Request_URI \
            \.(?:exe|t?gz|zip|bz2|sit|rar)$ no-gzip dont-vary
    </Location>
    
    # کش فایل‌های استاتیک
    <FilesMatch "\.(css|js|png|jpg|jpeg|gif|ico|svg)$">
        ExpiresActive On
        ExpiresDefault "access plus 1 month"
        Header set Cache-Control "public, immutable"
    </FilesMatch>
    
    # لاگ‌ها
    ErrorLog ${APACHE_LOG_DIR}/sarvcast_error.log
    CustomLog ${APACHE_LOG_DIR}/sarvcast_access.log combined
</VirtualHost>

# فعال‌سازی سایت
sudo a2ensite sarvcast.conf
sudo a2dissite 000-default.conf
sudo systemctl reload apache2
```

#### تنظیمات PHP-FPM
```bash
# ویرایش فایل تنظیمات
sudo nano /etc/apache2/sites-available/sarvcast.conf

# افزودن تنظیمات PHP-FPM
<VirtualHost *:80>
    ServerName sarvcast.com
    DocumentRoot /var/www/sarvcast/public
    
    <FilesMatch \.php$>
        SetHandler "proxy:unix:/run/php/php8.1-fpm.sock|fcgi://localhost"
    </FilesMatch>
    
    <Directory /var/www/sarvcast/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### تنظیمات Nginx

#### VirtualHost
```nginx
# فایل: /etc/nginx/sites-available/sarvcast

server {
    listen 80;
    server_name sarvcast.com www.sarvcast.com;
    root /var/www/sarvcast/public;
    index index.php index.html index.htm;
    
    # تنظیمات عملکرد
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_proxied any;
    gzip_comp_level 6;
    gzip_types
        text/plain
        text/css
        text/xml
        text/javascript
        application/json
        application/javascript
        application/xml+rss
        application/atom+xml
        image/svg+xml;
    
    # کش فایل‌های استاتیک
    location ~* \.(css|js|png|jpg|jpeg|gif|ico|svg)$ {
        expires 1M;
        add_header Cache-Control "public, immutable";
    }
    
    # PHP
    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    # Laravel
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    # امنیت
    location ~ /\.ht {
        deny all;
    }
    
    # لاگ‌ها
    access_log /var/log/nginx/sarvcast_access.log;
    error_log /var/log/nginx/sarvcast_error.log;
}

# فعال‌سازی سایت
sudo ln -s /etc/nginx/sites-available/sarvcast /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

## استقرار کد

### استقرار دستی

```bash
# ایجاد پوشه پروژه
sudo mkdir -p /var/www/sarvcast
sudo chown -R www-data:www-data /var/www/sarvcast

# کلون کردن repository
cd /var/www/sarvcast
sudo -u www-data git clone https://github.com/your-username/sarvcast.git .

# نصب وابستگی‌ها
sudo -u www-data composer install --optimize-autoloader --no-dev

# تنظیم مجوزها
sudo chown -R www-data:www-data /var/www/sarvcast
sudo chmod -R 755 /var/www/sarvcast
sudo chmod -R 775 /var/www/sarvcast/storage
sudo chmod -R 775 /var/www/sarvcast/bootstrap/cache
```

### استقرار خودکار

#### اسکریپت استقرار
```bash
#!/bin/bash
# فایل: /opt/scripts/deploy.sh

PROJECT_DIR="/var/www/sarvcast"
BACKUP_DIR="/opt/backups"
BRANCH="main"

echo "شروع استقرار..."

# ایجاد پشتیبان‌گیری
echo "ایجاد پشتیبان‌گیری..."
BACKUP_FILE="$BACKUP_DIR/backup_$(date +%Y%m%d_%H%M%S).tar.gz"
tar -czf $BACKUP_FILE $PROJECT_DIR

# انتقال به پوشه پروژه
cd $PROJECT_DIR

# دریافت آخرین تغییرات
echo "دریافت آخرین تغییرات..."
sudo -u www-data git fetch origin
sudo -u www-data git reset --hard origin/$BRANCH

# نصب وابستگی‌ها
echo "نصب وابستگی‌ها..."
sudo -u www-data composer install --optimize-autoloader --no-dev

# اجرای migration
echo "اجرای migration..."
sudo -u www-data php artisan migrate --force

# پاک کردن کش
echo "پاک کردن کش..."
sudo -u www-data php artisan cache:clear
sudo -u www-data php artisan config:clear
sudo -u www-data php artisan view:clear
sudo -u www-data php artisan route:clear

# بهینه‌سازی
echo "بهینه‌سازی..."
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache

# تنظیم مجوزها
echo "تنظیم مجوزها..."
sudo chown -R www-data:www-data $PROJECT_DIR
sudo chmod -R 755 $PROJECT_DIR
sudo chmod -R 775 $PROJECT_DIR/storage
sudo chmod -R 775 $PROJECT_DIR/bootstrap/cache

# راه‌اندازی مجدد سرویس‌ها
echo "راه‌اندازی مجدد سرویس‌ها..."
sudo systemctl reload apache2
sudo systemctl reload php8.1-fpm

echo "استقرار با موفقیت تکمیل شد"
```

#### تنظیم Cron Job
```bash
# ویرایش crontab
sudo crontab -e

# اجرای استقرار خودکار هر شب در ساعت 2 صبح
0 2 * * * /opt/scripts/deploy.sh >> /var/log/deploy.log 2>&1
```

### تنظیمات Laravel

#### فایل .env
```bash
# کپی فایل نمونه
sudo -u www-data cp .env.example .env

# ویرایش فایل .env
sudo -u www-data nano .env

# تنظیمات محیط
APP_NAME="SarvCast"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://sarvcast.com

# تنظیمات پایگاه داده
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sarvcast
DB_USERNAME=sarvcast_user
DB_PASSWORD=StrongPassword123!

# تنظیمات کش
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

# تنظیمات Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# تنظیمات ایمیل
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="${APP_NAME}"

# تولید کلید برنامه
sudo -u www-data php artisan key:generate
```

## تنظیمات امنیتی

### فایروال

#### تنظیمات UFW
```bash
# فعال‌سازی فایروال
sudo ufw enable

# قوانین پایه
sudo ufw default deny incoming
sudo ufw default allow outgoing

# مجاز کردن پورت‌های ضروری
sudo ufw allow 22/tcp    # SSH
sudo ufw allow 80/tcp    # HTTP
sudo ufw allow 443/tcp   # HTTPS

# مجاز کردن IP های خاص
sudo ufw allow from 192.168.1.0/24 to any port 22
sudo ufw allow from 10.0.0.0/8 to any port 22

# بررسی وضعیت
sudo ufw status verbose
```

#### تنظیمات iptables
```bash
# پاک کردن قوانین موجود
sudo iptables -F
sudo iptables -X

# قوانین پایه
sudo iptables -P INPUT DROP
sudo iptables -P FORWARD DROP
sudo iptables -P OUTPUT ACCEPT

# مجاز کردن loopback
sudo iptables -A INPUT -i lo -j ACCEPT

# مجاز کردن اتصالات موجود
sudo iptables -A INPUT -m state --state ESTABLISHED,RELATED -j ACCEPT

# مجاز کردن پورت‌های ضروری
sudo iptables -A INPUT -p tcp --dport 22 -j ACCEPT
sudo iptables -A INPUT -p tcp --dport 80 -j ACCEPT
sudo iptables -A INPUT -p tcp --dport 443 -j ACCEPT

# محدود کردن تلاش‌های SSH
sudo iptables -A INPUT -p tcp --dport 22 -m limit --limit 3/min --limit-burst 3 -j ACCEPT

# ذخیره قوانین
sudo iptables-save > /etc/iptables/rules.v4
```

### Fail2ban

```bash
# نصب Fail2ban
sudo apt install fail2ban

# تنظیمات Fail2ban
sudo nano /etc/fail2ban/jail.local

[DEFAULT]
bantime = 3600
findtime = 600
maxretry = 3

[sshd]
enabled = true
port = ssh
logpath = /var/log/auth.log
maxretry = 3

[apache-auth]
enabled = true
port = http,https
logpath = /var/log/apache2/error.log
maxretry = 3

[apache-dos]
enabled = true
port = http,https
logpath = /var/log/apache2/access.log
maxretry = 300
findtime = 300
bantime = 600

# راه‌اندازی سرویس
sudo systemctl enable fail2ban
sudo systemctl start fail2ban
```

## تنظیمات SSL

### Let's Encrypt

```bash
# نصب Certbot
sudo apt install certbot python3-certbot-apache

# دریافت گواهی SSL
sudo certbot --apache -d sarvcast.com -d www.sarvcast.com

# تمدید خودکار
sudo crontab -e

# اجرای تمدید خودکار
0 12 * * * /usr/bin/certbot renew --quiet
```

### تنظیمات Apache SSL

```apache
# فایل: /etc/apache2/sites-available/sarvcast-ssl.conf

<VirtualHost *:443>
    ServerName sarvcast.com
    ServerAlias www.sarvcast.com
    DocumentRoot /var/www/sarvcast/public
    
    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/sarvcast.com/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/sarvcast.com/privkey.pem
    
    # امنیت SSL
    SSLProtocol -all +TLSv1.2 +TLSv1.3
    SSLCipherSuite ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384
    SSLHonorCipherOrder on
    
    # Headers امنیتی
    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options DENY
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
    Header always set Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'"
    
    <Directory /var/www/sarvcast/public>
        AllowOverride All
        Require all granted
    </Directory>
    
    # لاگ‌ها
    ErrorLog ${APACHE_LOG_DIR}/sarvcast_ssl_error.log
    CustomLog ${APACHE_LOG_DIR}/sarvcast_ssl_access.log combined
</VirtualHost>

# فعال‌سازی سایت SSL
sudo a2ensite sarvcast-ssl.conf
sudo systemctl reload apache2
```

### تنظیمات Nginx SSL

```nginx
# فایل: /etc/nginx/sites-available/sarvcast-ssl

server {
    listen 443 ssl http2;
    server_name sarvcast.com www.sarvcast.com;
    root /var/www/sarvcast/public;
    index index.php index.html index.htm;
    
    # گواهی SSL
    ssl_certificate /etc/letsencrypt/live/sarvcast.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/sarvcast.com/privkey.pem;
    
    # امنیت SSL
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384;
    ssl_prefer_server_ciphers on;
    
    # Headers امنیتی
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
    add_header X-Content-Type-Options nosniff;
    add_header X-Frame-Options DENY;
    add_header X-XSS-Protection "1; mode=block";
    add_header Referrer-Policy "strict-origin-when-cross-origin";
    add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'";
    
    # تنظیمات عملکرد
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_proxied any;
    gzip_comp_level 6;
    gzip_types
        text/plain
        text/css
        text/xml
        text/javascript
        application/json
        application/javascript
        application/xml+rss
        application/atom+xml
        image/svg+xml;
    
    # کش فایل‌های استاتیک
    location ~* \.(css|js|png|jpg|jpeg|gif|ico|svg)$ {
        expires 1M;
        add_header Cache-Control "public, immutable";
    }
    
    # PHP
    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    # Laravel
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    # امنیت
    location ~ /\.ht {
        deny all;
    }
    
    # لاگ‌ها
    access_log /var/log/nginx/sarvcast_ssl_access.log;
    error_log /var/log/nginx/sarvcast_ssl_error.log;
}

# فعال‌سازی سایت SSL
sudo ln -s /etc/nginx/sites-available/sarvcast-ssl /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

## تنظیمات DNS

### رکوردهای DNS

```bash
# رکورد A
sarvcast.com.          A       your_server_ip
www.sarvcast.com.       A       your_server_ip

# رکورد CNAME
admin.sarvcast.com.     CNAME   sarvcast.com.
api.sarvcast.com.       CNAME   sarvcast.com.

# رکورد MX
sarvcast.com.           MX      10 mail.sarvcast.com.

# رکورد TXT
sarvcast.com.           TXT     "v=spf1 include:_spf.google.com ~all"
```

### تنظیمات CloudFlare

```bash
# تنظیمات DNS در CloudFlare
curl -X POST "https://api.cloudflare.com/client/v4/zones" \
  -H "X-Auth-Email: your-email@example.com" \
  -H "X-Auth-Key: your-api-key" \
  -H "Content-Type: application/json" \
  --data '{
    "name": "sarvcast.com",
    "type": "A",
    "content": "your_server_ip",
    "proxied": true
  }'
```

## تست و نظارت

### تست عملکرد

#### تست سرعت
```bash
# تست سرعت با curl
curl -w "@curl-format.txt" -o /dev/null -s "https://sarvcast.com"

# فایل curl-format.txt
     time_namelookup:  %{time_namelookup}\n
        time_connect:  %{time_connect}\n
     time_appconnect:  %{time_appconnect}\n
    time_pretransfer:  %{time_pretransfer}\n
       time_redirect:  %{time_redirect}\n
  time_starttransfer:  %{time_starttransfer}\n
                     ----------\n
          time_total:  %{time_total}\n
```

#### تست امنیت
```bash
# تست SSL با SSL Labs
curl -s "https://api.ssllabs.com/api/v3/analyze?host=sarvcast.com"

# تست امنیت با Nmap
nmap -sV -sC -O sarvcast.com
```

### نظارت بر سیستم

#### مانیتورینگ با htop
```bash
# نصب htop
sudo apt install htop

# اجرای htop
htop
```

#### مانیتورینگ با iotop
```bash
# نصب iotop
sudo apt install iotop

# اجرای iotop
sudo iotop
```

#### مانیتورینگ با netstat
```bash
# نمایش اتصالات فعال
netstat -tuln

# نمایش اتصالات با فرآیندها
netstat -tulnp

# نمایش آمار شبکه
netstat -s
```

### لاگ‌گیری

#### تنظیمات لاگ Laravel
```php
// در فایل config/logging.php
'channels' => [
    'production' => [
        'driver' => 'daily',
        'path' => storage_path('logs/production.log'),
        'level' => 'info',
        'days' => 30,
    ],
    'security' => [
        'driver' => 'daily',
        'path' => storage_path('logs/security.log'),
        'level' => 'warning',
        'days' => 90,
    ],
],
```

#### تنظیمات لاگ Apache
```apache
# فایل: /etc/apache2/apache2.conf
LogLevel warn
ErrorLog ${APACHE_LOG_DIR}/error.log
CustomLog ${APACHE_LOG_DIR}/access.log combined
```

#### تنظیمات لاگ Nginx
```nginx
# فایل: /etc/nginx/nginx.conf
error_log /var/log/nginx/error.log;
access_log /var/log/nginx/access.log;
```

---

**نکته**: این راهنما به صورت مداوم به‌روزرسانی می‌شود. برای آخرین نسخه، به بخش مستندات استقرار مراجعه کنید.
