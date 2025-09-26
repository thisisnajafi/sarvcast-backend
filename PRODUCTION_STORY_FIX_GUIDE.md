# Production Story Creation Fix Guide

## Problem
Getting syntax-highlight error when adding new story in production: `/home/sarvca/public_html/my/vendor/laravel/framework/src/Illuminate/Foundation/resources/exceptions/renderer/components/syntax-highlight.blade.php`

## Root Cause
The error occurs because the production server is missing the `public/images` directories that we moved image storage to. When the story controller tries to move uploaded files to `public/images/stories`, the directory doesn't exist, causing a PHP error.

## Solution Steps

### 1. Connect to Production Server
```bash
ssh your-server-username@your-server-ip
cd /home/sarvca/public_html/my
```

### 2. Create Required Directories
```bash
mkdir -p public/images/categories
mkdir -p public/images/stories
mkdir -p public/images/episodes
mkdir -p public/images/people
mkdir -p public/images/users
mkdir -p public/images/playlists
mkdir -p public/images/timeline
```

### 3. Set Proper Permissions
```bash
chmod -R 755 public/images
chown -R www-data:www-data public/images
```
*Note: Replace `www-data` with your web server user if different*

### 4. Verify Directory Structure
```bash
ls -la public/images/
```
Should show all directories with proper permissions.

### 5. Clear Laravel Caches
```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
```

### 6. Test Story Creation
1. Go to your admin panel
2. Try creating a new story
3. Upload an image
4. Verify the story saves successfully

## Alternative: Use Deployment Script

If you have the updated deployment scripts, run:

**Linux/Unix:**
```bash
./post-deployment-storage-setup.sh
```

**Windows:**
```cmd
post-deployment-storage-setup.bat
```

## Verification Commands

### Check if directories exist:
```bash
ls -la public/images/
```

### Test file creation:
```bash
touch public/images/stories/test.txt
ls -la public/images/stories/test.txt
rm public/images/stories/test.txt
```

### Check web server access:
```bash
curl -I https://my.sarvcast.ir/images/
```

## Common Issues & Solutions

### Issue 1: Permission Denied
```bash
sudo chown -R www-data:www-data public/images
sudo chmod -R 755 public/images
```

### Issue 2: Directory Already Exists
If some directories exist but others don't:
```bash
mkdir -p public/images/{categories,stories,episodes,people,users,playlists,timeline}
```

### Issue 3: Web Server Not Serving Images
Add to your web server config (Apache/Nginx) to ensure `/images/` is served directly.

**Apache (.htaccess already updated):**
```apache
# Already handled in public/.htaccess
RewriteCond %{REQUEST_URI} !^/images/
```

**Nginx:**
```nginx
location /images/ {
    try_files $uri $uri/ =404;
}
```

## Error Pattern Recognition

The syntax-highlight error typically indicates:
1. **Missing directories** - Most common cause
2. **Permission issues** - Second most common
3. **File system full** - Check disk space with `df -h`
4. **Web server configuration** - Ensure images are served directly

## Success Indicators

After applying the fix:
- ✅ Story creation works without errors
- ✅ Images upload successfully
- ✅ Images display in admin panel
- ✅ Direct image URLs work (e.g., `https://my.sarvcast.ir/images/stories/filename.webp`)

## Prevention

To prevent this issue in future deployments:
1. Use the updated GitHub Actions workflow
2. Run post-deployment scripts
3. Always verify directory structure after deployment
