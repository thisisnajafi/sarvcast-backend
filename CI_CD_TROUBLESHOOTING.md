# CI/CD Troubleshooting Guide

## Common Errors and Solutions

### Error: `cd: ***: No such file or directory`

**Problem:** The `APP_PATH` variable is not set correctly or the directory doesn't exist.

**Solutions:**

#### Solution 1: Set APP_PATH GitHub Secret

1. Go to **GitHub Repository → Settings → Secrets → Actions**
2. Add or update secret:
   - **Name**: `APP_PATH`
   - **Value**: `/public_html/my` (or your actual application path)

#### Solution 2: Verify Directory Exists on Server

SSH into your server and verify the path exists:

```bash
ssh your_username@your_server_ip
ls -la /public_html/my
```

If it doesn't exist, create it or use the correct path.

#### Solution 3: Check GitHub Secret Value

Make sure your `APP_PATH` secret:
- ✅ Is not empty
- ✅ Doesn't have trailing slashes (`/public_html/my` not `/public_html/my/`)
- ✅ Uses absolute path (starts with `/`)
- ✅ Matches the actual directory on your server

### Error: `Application path does not exist`

**Problem:** The directory specified in `APP_PATH` doesn't exist on the server.

**Solution:**

```bash
# SSH into server
ssh your_username@your_server_ip

# Check if directory exists
ls -la /public_html/my

# If it doesn't exist, create it
mkdir -p /public_html/my
chmod 755 /public_html/my
```

### Error: `Backup script not found`

**Problem:** The backup script wasn't uploaded to the server.

**Solution:**

The script should be uploaded automatically. If it's missing:

1. Check GitHub Actions logs for upload errors
2. Manually upload the script:

```bash
scp scripts/backup-database-ssh.sh your_username@your_server:/public_html/my/scripts/
```

### Error: `.env file not found`

**Problem:** The `.env` file doesn't exist in the application directory.

**Solution:**

```bash
# SSH into server
ssh your_username@your_server_ip
cd /public_html/my

# Check if .env exists
ls -la .env

# If missing, create it from .env.example
cp .env.example .env
# Then edit it with your actual values
nano .env
```

### Error: `mysqldump: command not found`

**Problem:** MySQL client is not installed on the server.

**Solution:**

```bash
# SSH into server
ssh your_username@your_server_ip

# Install MySQL client
sudo apt-get update
sudo apt-get install -y mysql-client

# Or for CentOS/RHEL
sudo yum install -y mysql
```

### Error: `Permission denied` when creating backup

**Problem:** The backup directory doesn't have write permissions.

**Solution:**

```bash
# SSH into server
ssh your_username@your_server_ip

# Create backup directory with proper permissions
mkdir -p /public_html/my/storage/backups
chmod 755 /public_html/my/storage/backups

# Or if using system-level backup
sudo mkdir -p /backups/sarvcast
sudo chown -R $USER:$USER /backups/sarvcast
sudo chmod 755 /backups/sarvcast
```

### Error: `Database connection failed`

**Problem:** Database credentials in `.env` are incorrect or database is not accessible.

**Solution:**

1. Check `.env` file on server:
   ```bash
   ssh your_username@your_server_ip
   cd /public_html/my
   cat .env | grep DB_
   ```

2. Verify database credentials are correct
3. Test database connection:
   ```bash
   mysql -h localhost -u your_username -p your_database
   ```

4. Check if database user has backup permissions:
   ```sql
   GRANT SELECT, LOCK TABLES ON your_database.* TO 'your_user'@'localhost';
   FLUSH PRIVILEGES;
   ```

### Error: `SSH connection failed`

**Problem:** Cannot connect to server via SSH.

**Solutions:**

1. **Verify SSH credentials:**
   - Check `SSH_HOST`, `SSH_USERNAME`, `SSH_PASSWORD` secrets
   - Test SSH connection manually:
     ```bash
     ssh your_username@your_server_ip
     ```

2. **Check SSH port:**
   - Default is 22
   - If different, set `SSH_PORT` secret

3. **Check firewall:**
   - Ensure SSH port is open
   - Check server firewall rules

4. **Verify password authentication is enabled:**
   - Some servers only allow key-based auth
   - Check `/etc/ssh/sshd_config` on server

## Debugging Steps

### Step 1: Check GitHub Actions Logs

1. Go to **GitHub → Actions**
2. Click on the failed workflow
3. Expand the failed step
4. Look for error messages

### Step 2: Test SSH Connection

```bash
ssh your_username@your_server_ip
```

If this fails, fix SSH access first.

### Step 3: Verify Application Path

```bash
ssh your_username@your_server_ip
ls -la /public_html/my
```

Should show your application files.

### Step 4: Test Backup Manually

```bash
ssh your_username@your_server_ip
cd /public_html/my
./scripts/backup-database-ssh.sh test_backup
```

### Step 5: Check Permissions

```bash
ssh your_username@your_server_ip
cd /public_html/my
ls -la scripts/
chmod +x scripts/*.sh
```

## Quick Fix Checklist

When deployment fails, check:

- [ ] `APP_PATH` GitHub secret is set correctly
- [ ] Application directory exists on server
- [ ] `.env` file exists in application directory
- [ ] Backup directory exists and is writable
- [ ] MySQL client is installed
- [ ] SSH connection works
- [ ] Database credentials are correct
- [ ] Scripts have execute permissions

## Getting Help

If you're still stuck:

1. **Check full logs** in GitHub Actions
2. **Review documentation**: `docs/CI_CD_DEPLOYMENT_GUIDE.md`
3. **Test manually** on server first
4. **Verify all secrets** are set correctly

## Common GitHub Secrets Issues

### Secret Not Set
- **Symptom**: Variable is empty or shows `***`
- **Fix**: Add the secret in GitHub Settings

### Secret Has Wrong Value
- **Symptom**: Path doesn't exist error
- **Fix**: Update secret with correct value

### Secret Has Extra Spaces
- **Symptom**: Path not found errors
- **Fix**: Remove leading/trailing spaces from secret value

### Secret Uses Relative Path
- **Symptom**: Directory not found
- **Fix**: Use absolute path (starts with `/`)

## Example: Complete Debug Session

```bash
# 1. SSH into server
ssh user@server

# 2. Check application path
ls -la /public_html/my
cd /public_html/my

# 3. Check .env exists
ls -la .env

# 4. Check database connection
php artisan tinker
# In tinker: DB::connection()->getPdo();

# 5. Test backup script
chmod +x scripts/backup-database-ssh.sh
./scripts/backup-database-ssh.sh test

# 6. Check backup directory
ls -la storage/backups/
```

If all these work, the CI/CD should work too!

