# SarvCast Deployment Guide - Storage Configuration

## Overview
This guide explains how to properly configure storage for SarvCast during deployment to fix the 403 error on image access.

## Files Updated

### 1. GitHub Actions Workflow (`.github/workflows/main.yml`)
- ✅ Added storage symlink creation step
- ✅ Added storage permissions setup
- ✅ Updated deployment verification
- ✅ Updated project name from LGBTinder to SarvCast

### 2. Post-Deployment Scripts
- ✅ `post-deployment-storage-setup.sh` (Linux/Unix)
- ✅ `post-deployment-storage-setup.bat` (Windows)

## Deployment Process

### Step 1: GitHub Actions Deployment
The workflow now automatically:
1. Creates storage symlink: `php artisan storage:link --force`
2. Sets storage permissions: `chmod -R 755 storage/app/public public/storage`
3. Deploys files via FTP
4. Provides deployment verification

### Step 2: Production Server Setup
After deployment, run the post-deployment script on your production server:

#### For Linux/Unix Servers:
```bash
# Make script executable
chmod +x post-deployment-storage-setup.sh

# Run the script
./post-deployment-storage-setup.sh
```

#### For Windows Servers:
```cmd
post-deployment-storage-setup.bat
```

### Step 3: Web Server Configuration

#### Apache Configuration
Add this to your virtual host or `.htaccess`:

```apache
<Directory "/path/to/your/project/public/storage">
    Options -Indexes
    AllowOverride All
    Require all granted
</Directory>
```

#### Nginx Configuration
Add this to your server block:

```nginx
location /storage/ {
    try_files $uri =404;
    expires 1y;
    add_header Cache-Control "public, immutable";
}
```

## What the Scripts Do

### GitHub Actions Workflow
1. **Create Storage Link**: `php artisan storage:link --force`
2. **Set Permissions**: `chmod -R 755 storage/app/public public/storage`
3. **Deploy Files**: Upload via FTP
4. **Verify Deployment**: Check deployment status

### Post-Deployment Script
1. **Create Storage Link**: Ensure symlink exists
2. **Set Permissions**: Configure file permissions
3. **Set Ownership**: Configure file ownership (Linux)
4. **Clear Caches**: Clear Laravel caches
5. **Test Access**: Verify storage is working
6. **Cleanup**: Remove test files

## Testing

### 1. Upload Test Image
1. Go to admin panel
2. Upload a category image
3. Check if image displays correctly

### 2. Direct URL Test
1. Get the image URL from the database
2. Access directly: `https://my.sarvcast.ir/storage/categories/filename.webp`
3. Verify no 403 error

### 3. API Test
1. Check API responses include full image URLs
2. Verify URLs are accessible

## Troubleshooting

### Common Issues

#### 1. 403 Error Still Occurs
- Check web server configuration
- Verify file permissions
- Check if storage symlink exists

#### 2. Images Not Displaying
- Check if files exist in storage directory
- Verify symlink is working
- Check browser console for errors

#### 3. Permission Denied
- Run post-deployment script
- Check file ownership
- Verify web server user has access

### Debug Commands

```bash
# Check symlink
ls -la public/storage

# Check permissions
ls -la storage/app/public

# Test file access
curl -I https://my.sarvcast.ir/storage/categories/test.webp

# Check web server logs
tail -f /var/log/apache2/error.log
# or
tail -f /var/log/nginx/error.log
```

## File Structure After Deployment

```
project/
├── public/
│   ├── storage/ (symlink to storage/app/public)
│   │   ├── categories/
│   │   ├── episodes/
│   │   └── stories/
│   └── .htaccess
├── storage/
│   └── app/
│       └── public/
│           ├── categories/
│           ├── episodes/
│           └── stories/
└── .github/
    └── workflows/
        └── main.yml
```

## Success Indicators

- ✅ Storage symlink exists and points correctly
- ✅ Files are accessible via direct URLs
- ✅ No 403 errors on image access
- ✅ Images display correctly in admin panel
- ✅ API responses include full image URLs

## Next Steps

1. Deploy using the updated GitHub Actions workflow
2. Run the post-deployment script on production
3. Configure web server for direct storage access
4. Test image uploads and access
5. Monitor for any remaining issues

The storage 403 error should now be resolved with proper deployment and server configuration.
