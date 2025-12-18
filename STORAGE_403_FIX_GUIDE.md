# Storage 403 Error Fix Guide

## Problem
Images from storage return 403 error when accessing `https://my.sarvcast.ir/storage/...`

## Root Cause
The web server (Apache/Nginx) is not properly configured to serve files from the `public/storage` directory.

## Solutions

### 1. Apache Configuration

#### Option A: Update .htaccess (Already Done)
The main `.htaccess` file has been updated to exclude `/storage/` from Laravel routing:

```apache
# Send Requests To Front Controller...
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_URI} !^/storage/
RewriteRule ^ index.php [L]
```

#### Option B: Virtual Host Configuration
Add this to your Apache virtual host configuration:

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

### 2. Nginx Configuration

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

### 3. File Permissions

Ensure proper permissions on the storage directory:

```bash
# Set ownership
sudo chown -R www-data:www-data /path/to/your/project/storage
sudo chown -R www-data:www-data /path/to/your/project/public/storage

# Set permissions
sudo chmod -R 755 /path/to/your/project/storage
sudo chmod -R 755 /path/to/your/project/public/storage
```

### 4. Verify Storage Link

Ensure the storage link is properly created:

```bash
cd /path/to/your/project
php artisan storage:link
```

### 5. Test the Fix

1. Upload a test image through the admin panel
2. Check if the image is accessible via direct URL
3. Verify the image displays correctly in the admin interface

## Additional Notes

- The application now stores relative paths in the database
- Full URLs are generated using the app's base URL (`https://my.sarvcast.ir`)
- The `HasImageUrl` trait handles URL generation consistently
- All models with image fields have been updated

## Troubleshooting

If the issue persists:

1. Check web server error logs
2. Verify file permissions
3. Test with a simple HTML file in the storage directory
4. Check if mod_rewrite is enabled (Apache)
5. Verify the storage symlink exists and points to the correct location
