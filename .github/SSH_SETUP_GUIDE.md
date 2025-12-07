# SSH Setup Guide for GitHub Actions Deployment

This guide explains how to configure SSH access for automated file deployment and post-deployment commands in the GitHub Actions workflow.

## Overview

The workflow now uses SSH for both file deployment and running Laravel commands. This ensures:
- ✅ Files are synced to server via SSH (rsync) - faster and more reliable than FTP
- ✅ Composer dependencies are installed/updated
- ✅ Laravel caches are cleared and optimized
- ✅ Database migrations are run
- ✅ Application is optimized for production
- ✅ Permissions are set correctly

## Deployment Method

The workflow uses **rsync over SSH** for file deployment, which provides:
- **Faster transfers** - Only changed files are synced
- **More reliable** - No FTP connection timeouts
- **Secure** - All communication encrypted via SSH
- **Efficient** - Automatic compression and delta sync

## Required GitHub Secrets

You need to add the following secrets to your GitHub repository:

### 1. SSH_HOST
**Description**: Your server's hostname or IP address

**Example Values**:
- `2997021731.cloudylink.com`
- `my.sarvcast.ir`
- `123.456.789.0`

**How to Add**:
1. Go to your repository on GitHub
2. Click **Settings** → **Secrets and variables** → **Actions**
3. Click **New repository secret**
4. Name: `SSH_HOST`
5. Value: Your server hostname/IP
6. Click **Add secret**

### 2. SSH_USERNAME
**Description**: SSH username for server access

**Example Values**:
- `my@sarvcast.ir`
- `root`
- `sarvcast`

**How to Add**:
1. Go to **Settings** → **Secrets and variables** → **Actions**
2. Click **New repository secret**
3. Name: `SSH_USERNAME`
4. Value: Your SSH username
5. Click **Add secret**

### 3. SSH_PRIVATE_KEY
**Description**: Your SSH private key for authentication

**How to Generate SSH Key** (if you don't have one):

```bash
# Generate a new SSH key pair
ssh-keygen -t rsa -b 4096 -C "github-actions@sarvcast.ir" -f ~/.ssh/github_actions

# This creates two files:
# - ~/.ssh/github_actions (private key) - Add this to GitHub secrets
# - ~/.ssh/github_actions.pub (public key) - Add this to server
```

**How to Add Public Key to Server**:

```bash
# Copy public key to server
ssh-copy-id -i ~/.ssh/github_actions.pub my@sarvcast.ir@2997021731.cloudylink.com

# Or manually add to ~/.ssh/authorized_keys on server
cat ~/.ssh/github_actions.pub | ssh my@sarvcast.ir@2997021731.cloudylink.com "mkdir -p ~/.ssh && cat >> ~/.ssh/authorized_keys"
```

**How to Add Private Key to GitHub**:
1. Copy the private key content:
   ```bash
   cat ~/.ssh/github_actions
   ```
2. Go to **Settings** → **Secrets and variables** → **Actions**
3. Click **New repository secret**
4. Name: `SSH_PRIVATE_KEY`
5. Value: Paste the entire private key (including `-----BEGIN` and `-----END` lines)
6. Click **Add secret**

### 4. SSH_PORT (Optional)
**Description**: SSH port number (defaults to 22 if not set)

**Example Values**:
- `22` (default)
- `2222`
- `2200`

**How to Add** (if using non-standard port):
1. Go to **Settings** → **Secrets and variables** → **Actions**
2. Click **New repository secret**
3. Name: `SSH_PORT`
4. Value: Your SSH port number
5. Click **Add secret**

### 5. APP_PATH (Optional)
**Description**: Path to your Laravel application on the server

**Example Values**:
- `/public_html`
- `/var/www/html/sarvcast`
- `/home/my/public_html`

**Default**: `/public_html` (if not set)

**How to Add** (if your app is in a different location):
1. Go to **Settings** → **Secrets and variables** → **Actions**
2. Click **New repository secret**
3. Name: `APP_PATH`
4. Value: Full path to your Laravel application
5. Click **Add secret**

## Testing SSH Connection

Before adding secrets to GitHub, test your SSH connection:

```bash
# Test SSH connection
ssh -i ~/.ssh/github_actions my@sarvcast.ir@2997021731.cloudylink.com

# Test if you can run commands
ssh -i ~/.ssh/github_actions my@sarvcast.ir@2997021731.cloudylink.com "cd /public_html && php artisan --version"
```

## Deployment Process

The workflow performs these steps:

1. **File Sync via rsync**
   - Syncs all application files to server
   - Excludes unnecessary files (vendor, .git, tests, etc.)
   - Only transfers changed files (incremental sync)
   - Uses SSH for secure transfer

2. **Post-Deployment Commands**

The workflow automatically runs these commands on your server:

1. **Composer Install**
   ```bash
   composer install --no-dev --optimize-autoloader --no-interaction
   ```

2. **Clear Caches**
   ```bash
   php artisan config:clear
   php artisan route:clear
   php artisan view:clear
   php artisan cache:clear
   ```

3. **Run Migrations**
   ```bash
   php artisan migrate --force
   ```

4. **Optimize for Production**
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   php artisan optimize
   ```

5. **Set Permissions**
   ```bash
   chmod -R 755 storage bootstrap/cache
   chmod -R 755 public/images
   ```

6. **Create Storage Symlink**
   ```bash
   php artisan storage:link
   ```

## Troubleshooting

### Issue: SSH Connection Failed

**Symptoms**: Workflow fails with "SSH connection failed"

**Solutions**:
1. Verify SSH_HOST is correct
2. Verify SSH_USERNAME is correct
3. Check if SSH port is correct (if using non-standard port)
4. Verify SSH_PRIVATE_KEY is correctly formatted (must include BEGIN/END lines)
5. Ensure public key is added to server's `~/.ssh/authorized_keys`
6. Check server firewall allows SSH connections

### Issue: Permission Denied

**Symptoms**: "Permission denied (publickey)" error

**Solutions**:
1. Verify public key is in server's `~/.ssh/authorized_keys`
2. Check file permissions on server:
   ```bash
   chmod 700 ~/.ssh
   chmod 600 ~/.ssh/authorized_keys
   ```
3. Verify SSH key format is correct

### Issue: Command Not Found

**Symptoms**: "composer: command not found" or "php: command not found"

**Solutions**:
1. Verify Composer and PHP are installed on server
2. Check if they're in PATH
3. You may need to use full paths:
   ```bash
   /usr/local/bin/composer install
   /usr/bin/php artisan ...
   ```

### Issue: APP_PATH Not Found

**Symptoms**: "No such file or directory" when changing directory

**Solutions**:
1. Verify APP_PATH secret is set correctly
2. Verify the path exists on your server
3. Use absolute path (starting with `/`)

## Security Best Practices

1. **Use Dedicated SSH Key**: Create a separate SSH key pair specifically for GitHub Actions
2. **Restrict Key Permissions**: On server, restrict what the key can do if possible
3. **Rotate Keys Regularly**: Change SSH keys periodically
4. **Monitor Access**: Check server logs for SSH access
5. **Use SSH Agent**: Consider using SSH agent forwarding for additional security

## Alternative: Password Authentication

If you cannot use SSH keys, you can modify the workflow to use password authentication:

```yaml
- name: Run Post-Deployment Commands via SSH
  uses: appleboy/ssh-action@v1.0.3
  with:
    host: ${{ secrets.SSH_HOST }}
    username: ${{ secrets.SSH_USERNAME }}
    password: ${{ secrets.SSH_PASSWORD }}  # Instead of key
    port: ${{ secrets.SSH_PORT || 22 }}
    script: |
      # ... commands ...
```

**Note**: Password authentication is less secure than SSH keys. Use keys when possible.

## Verification

After setting up secrets, test the deployment:

1. Make a small change to your code
2. Commit and push to `main` branch
3. Check GitHub Actions workflow logs
4. Verify SSH step completes successfully
5. Check your server to confirm commands were executed

## Support

If you encounter issues:
1. Check GitHub Actions workflow logs
2. Test SSH connection manually
3. Verify all secrets are set correctly
4. Check server logs for errors

---

**Last Updated**: 2024-01-XX

