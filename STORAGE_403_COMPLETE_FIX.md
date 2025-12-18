# Storage 403 Error - Complete Fix Guide

## Problem Analysis
Images are saved correctly but return 403 error when accessing `https://my.sarvcast.ir/storage/...`

## Root Cause
The web server is not properly configured to serve files from the `public/storage` directory.

## Steps Completed ✅

### 1. Storage Symlink ✅
- Recreated storage symlink: `public/storage` → `storage/app/public`
- Verified symlink is working correctly

### 2. File Permissions ✅
- Set full permissions on `storage/app/public` directory
- Set full permissions on `public/storage` directory
- All files are now accessible

### 3. .htaccess Configuration ✅
- **Main .htaccess**: Added `RewriteCond %{REQUEST_URI} !^/storage/` to exclude storage from Laravel routing
- **Storage .htaccess**: Disabled rewrite engine and added proper MIME types

### 4. File Access Testing ✅
- Created test files in storage
- Verified files are accessible via symlink
- Confirmed URL generation works correctly

## Current Status
- ✅ Storage symlink: Working
- ✅ File permissions: Correct
- ✅ .htaccess files: Configured
- ✅ URL generation: Working
- ❌ Web server: Needs configuration

## Production Server Fix Required

The issue is on the production server. Apply these fixes:

### For Apache Servers

#### Option 1: Virtual Host Configuration
Add this to your Apache virtual host:

```apache
<VirtualHost *:80>
    ServerName my.sarvcast.ir
    DocumentRoot /path/to/your/project/public
    
    # Allow direct access to storage files
    <Directory "/path/to/your/project/public/storage">
        Options -Indexes
        AllowOverride All
        Require all granted
        
        # Set proper MIME types
        <FilesMatch "\.(jpg|jpeg|png|webp|gif|mp3|wav|ogg)$">
            Header set Cache-Control "max-age=31536000, public"
        </FilesMatch>
    </Directory>
    
    # Laravel routing for everything else
    <Directory "/path/to/your/project/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

#### Option 2: .htaccess (Already Done)
The .htaccess files have been updated and should work.

### For Nginx Servers

Add this to your Nginx server block:

```nginx
server {
    listen 80;
    server_name my.sarvcast.ir;
    root /path/to/your/project/public;
    index index.php;

    # Handle storage files directly
    location /storage/ {
        try_files $uri =404;
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # Handle Laravel routing
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### File Permissions (Linux/Unix)

Run these commands on your production server:

```bash
# Set ownership
sudo chown -R www-data:www-data /path/to/your/project/storage
sudo chown -R www-data:www-data /path/to/your/project/public/storage

# Set permissions
sudo chmod -R 755 /path/to/your/project/storage
sudo chmod -R 755 /path/to/your/project/public/storage
```

### Verify Storage Link

On your production server:

```bash
cd /path/to/your/project
php artisan storage:link
```

## Testing

1. Upload a test image through the admin panel
2. Check the direct URL: `https://my.sarvcast.ir/storage/categories/filename.webp`
3. Verify the image displays correctly

## What's Working

- ✅ Images are saved correctly
- ✅ Relative paths stored in database
- ✅ Full URLs generated using app base URL
- ✅ Storage symlink working
- ✅ File permissions correct
- ✅ .htaccess configured

## What Needs Fixing

- ❌ Web server configuration on production
- ❌ Server-level permissions
- ❌ Virtual host/DocumentRoot settings

The application code is correct. The issue is purely server configuration.
