# Storage Link Deployment Guide

This guide ensures that Laravel's storage symlink is properly created during deployment, which is essential for serving audio files and other assets stored in `storage/app/public`.

## Problem

Audio files stored in `storage/app/public/audio/episodes/` need to be accessible via URLs like `/storage/audio/episodes/filename.mp3`. This requires a symbolic link from `public/storage` to `storage/app/public`.

## Solution

The storage symlink is now automatically created in all deployment workflows:

### 1. Production Deployment Script (`deploy-production.sh`)
- Automatically runs `php artisan storage:link --force` after migrations
- Ensures symlink exists before caching configuration

### 2. Standard Deployment Script (`deploy.sh`)
- Includes storage link creation in the deployment process
- Works with Docker Compose deployments

### 3. Post-Deployment Setup (`post-deployment-storage-setup.sh`)
- Creates storage symlink as part of post-deployment setup
- Sets proper permissions and verifies access

### 4. GitHub Actions Workflow (`.github/workflows/deploy.yml`)
- Automatically creates storage symlink during CI/CD deployment
- Runs both locally and on the server via SSH

### 5. Standalone Script (`ensure-storage-link.sh`)
- Can be run manually to ensure storage link exists
- Useful for troubleshooting or manual fixes

## Usage

### Automatic (Recommended)

The storage link is now created automatically during deployment. No manual action needed.

### Manual (If Needed)

If you need to manually create the storage link:

```bash
# Via SSH on production server
cd /path/to/laravel/project
php artisan storage:link --force

# Or use the standalone script
bash ensure-storage-link.sh
```

### Via SSH (Quick Fix)

If storage files are not accessible, connect via SSH and run:

```bash
cd /path/to/your/laravel/project
php artisan storage:link --force
chmod -R 755 storage
chmod -R 755 public/storage
```

## Verification

After deployment, verify the storage link exists:

```bash
# Check if symlink exists
ls -la public/storage

# Should show something like:
# lrwxrwxrwx 1 user user 20 Jan 1 12:00 public/storage -> ../storage/app/public

# Test access
curl -I https://your-domain.com/storage/audio/episodes/test.mp3
```

## Troubleshooting

### Symlink Not Created

1. Check if `storage/app/public` directory exists:
   ```bash
   ls -la storage/app/public
   ```

2. Check if `public/storage` already exists (may be a directory instead of symlink):
   ```bash
   ls -la public/storage
   ```

3. Remove existing `public/storage` if it's a directory:
   ```bash
   rm -rf public/storage
   php artisan storage:link --force
   ```

### Permission Issues

```bash
# Set proper permissions
chmod -R 755 storage
chmod -R 755 public/storage
chown -R www-data:www-data storage
chown -R www-data:www-data public/storage
```

### Files Not Accessible

1. Verify symlink points to correct location:
   ```bash
   readlink -f public/storage
   # Should point to: /path/to/project/storage/app/public
   ```

2. Check web server configuration allows following symlinks
3. Verify file exists in storage:
   ```bash
   ls -la storage/app/public/audio/episodes/
   ```

## Integration with Deployment Workflows

### Docker Deployment

The storage link is created inside the Docker container:
```bash
docker-compose exec app php artisan storage:link --force
```

### FTP Deployment

After FTP upload, SSH into server and run:
```bash
php artisan storage:link --force
```

### CI/CD Pipeline

The GitHub Actions workflow automatically:
1. Creates storage link locally (for testing)
2. Deploys code via SSH
3. Creates storage link on server
4. Verifies deployment

## Files Modified

- ✅ `deploy-production.sh` - Added storage:link command
- ✅ `deploy.sh` - Added storage:link command
- ✅ `post-deployment-storage-setup.sh` - Added storage:link command
- ✅ `.github/workflows/deploy.yml` - Created with storage:link
- ✅ `ensure-storage-link.sh` - Created standalone script
- ✅ `scripts/deploy.sh` - Updated instructions

## Next Steps

1. **Test the deployment**: Run a test deployment to verify storage link is created
2. **Verify audio access**: Test that audio files are accessible via browser
3. **Monitor**: Check logs if any issues occur during deployment

## Related Documentation

- [Laravel Storage Documentation](https://laravel.com/docs/storage#the-public-disk)
- [DEPLOYMENT.md](./DEPLOYMENT.md)
- [STORAGE_403_FIX_GUIDE.md](./STORAGE_403_FIX_GUIDE.md)

