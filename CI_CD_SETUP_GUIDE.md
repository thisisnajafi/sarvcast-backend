# CI/CD Setup Guide - Step by Step

This guide will walk you through setting up the CI/CD pipeline for your SarvCast application.

## 📍 Prerequisites

- GitHub repository with your code
- SSH access to your server
- MySQL/MariaDB database
- Basic knowledge of Git and SSH

## 🚀 Step-by-Step Setup

### Step 1: Configure GitHub Secrets

1. Go to your GitHub repository
2. Click **Settings** → **Secrets and variables** → **Actions**
3. Click **New repository secret**
4. Add the following secrets one by one:

#### Required Secrets:

**SSH_HOST**
- **Value**: Your server IP address or domain name
- **Example**: `123.45.67.89` or `my.sarvcast.ir`

**SSH_USERNAME**
- **Value**: Your SSH username
- **Example**: `myuser` or `root`

**SSH_PASSWORD**
- **Value**: Your SSH password
- **⚠️ Important**: Keep this secure, never share it

#### Optional Secrets:

**SSH_PORT**
- **Value**: SSH port number (if not 22)
- **Example**: `2222`
- **Default**: `22` if not provided

**APP_PATH**
- **Value**: Full path to your application on the server
- **Example**: `/public_html/my` or `/var/www/sarvcast`
- **Default**: `/public_html/my` if not provided

**BACKUP_DIR** (Optional - for custom backup location)
- **Value**: Full path where backups should be stored
- **Example**: `/public_html/my/storage/backups` or `/backups/sarvcast`
- **Default**: `{APP_PATH}/storage/backups` if not provided

### Step 2: Server Setup

Connect to your server via SSH:

```bash
ssh your_username@your_server_ip
```

#### Option A: System-Level Backup Directory (Recommended if you have root/sudo access)

```bash
# Create backup directory at system level
sudo mkdir -p /backups/sarvcast
sudo chown -R $USER:$USER /backups/sarvcast
sudo chmod 755 /backups/sarvcast

# Verify it was created
ls -la /backups/sarvcast
```

**Then set GitHub Secret:**
- `BACKUP_DIR` = `/backups/sarvcast`

#### Option B: Application-Level Backup Directory (For shared hosting)

```bash
# Navigate to your application directory
cd /public_html/my  # Replace with your actual path

# Create backup directory inside your app (outside public access)
mkdir -p storage/backups
chmod 755 storage/backups

# Verify it was created
ls -la storage/backups
```

**No GitHub Secret needed** - it will use `{APP_PATH}/storage/backups` automatically.

### Step 3: Install MySQL Client (if not already installed)

```bash
# For Ubuntu/Debian
sudo apt-get update
sudo apt-get install -y mysql-client

# For CentOS/RHEL
sudo yum install -y mysql

# Verify installation
which mysqldump
```

### Step 4: Test SSH Connection

From your local machine, test SSH connection:

```bash
ssh your_username@your_server_ip
# Enter password when prompted
```

If connection works, you're good to go!

### Step 5: Verify Application Path

On your server, verify your application path:

```bash
# Check if .env file exists
ls -la /public_html/my/.env  # Replace with your actual path

# Check if artisan file exists
ls -la /public_html/my/artisan  # Replace with your actual path
```

Make sure the path matches what you set in `APP_PATH` GitHub secret.

### Step 6: Test Database Connection

On your server, test if you can connect to the database:

```bash
cd /public_html/my  # Your app path
php artisan tinker
# Then in tinker: DB::connection()->getPdo();
# Should return: PDO object
```

### Step 7: First Deployment Test

1. Make a small change to your code (e.g., add a comment)
2. Commit and push to `main` branch:

```bash
git add .
git commit -m "Test CI/CD pipeline"
git push origin main
```

3. Go to GitHub → **Actions** tab
4. Watch the workflow run
5. Check if it completes successfully

### Step 8: Verify Backup Was Created

After first deployment, check if backup was created:

```bash
# SSH into your server
ssh your_username@your_server_ip

# Check backup directory
ls -lh /backups/sarvcast/  # If using system-level
# OR
ls -lh /public_html/my/storage/backups/  # If using app-level
```

You should see a file like: `pre_deployment_20250101_120000.sql.gz`

## 🔍 Troubleshooting

### Issue: SSH Connection Fails

**Solution:**
1. Verify `SSH_HOST`, `SSH_USERNAME`, `SSH_PASSWORD` are correct
2. Check if SSH port is correct (default is 22)
3. Test SSH connection manually
4. Check server firewall allows SSH

### Issue: Backup Directory Permission Denied

**Solution:**
```bash
# Fix permissions
sudo chown -R $USER:$USER /backups/sarvcast
sudo chmod 755 /backups/sarvcast

# Or for app-level:
chmod 755 /public_html/my/storage/backups
```

### Issue: MySQL Client Not Found

**Solution:**
```bash
# Install MySQL client
sudo apt-get install -y mysql-client  # Ubuntu/Debian
sudo yum install -y mysql  # CentOS/RHEL
```

### Issue: Database Backup Fails

**Solution:**
1. Check database credentials in `.env` file
2. Verify database user has backup permissions
3. Test mysqldump manually:
   ```bash
   mysqldump -h localhost -u your_user -p your_database > test.sql
   ```

### Issue: Scripts Not Found

**Solution:**
The scripts are uploaded automatically during deployment. If they're missing:
1. Check GitHub Actions logs for upload errors
2. Manually upload scripts:
   ```bash
   scp scripts/backup-database-ssh.sh user@server:/public_html/my/scripts/
   ```

## ✅ Verification Checklist

After setup, verify:

- [ ] GitHub secrets are configured
- [ ] Backup directory exists and is writable
- [ ] MySQL client is installed
- [ ] SSH connection works
- [ ] Application path is correct
- [ ] Database connection works
- [ ] First deployment succeeded
- [ ] Backup file was created

## 📞 Next Steps

Once everything is working:

1. **Monitor first few deployments** - Check GitHub Actions logs
2. **Verify backups** - Make sure backups are being created
3. **Test rollback** - Try restoring from a backup
4. **Set up monitoring** - Consider setting up alerts

## 🎯 Quick Reference

### Backup Locations

- **System-level**: `/backups/sarvcast/` (requires root/sudo)
- **App-level**: `/public_html/my/storage/backups/` (no root needed)

### Important Paths

- **Application**: `/public_html/my` (or your `APP_PATH`)
- **Backups**: `/backups/sarvcast` or `{APP_PATH}/storage/backups`
- **Scripts**: `{APP_PATH}/scripts/`
- **Logs**: `/tmp/sarvcast-*.log`

### GitHub Secrets Summary

```
Required:
- SSH_HOST
- SSH_USERNAME  
- SSH_PASSWORD

Optional:
- SSH_PORT (default: 22)
- APP_PATH (default: /public_html/my)
- BACKUP_DIR (default: {APP_PATH}/storage/backups)
```

## 🚨 Important Notes

1. **Never commit** `.env` file or credentials
2. **Keep backups** for at least 7 days
3. **Monitor disk space** - backups can take up space
4. **Test migrations locally** before pushing
5. **Review migration files** before deployment

---

Need help? Check the full documentation: `docs/CI_CD_DEPLOYMENT_GUIDE.md`

