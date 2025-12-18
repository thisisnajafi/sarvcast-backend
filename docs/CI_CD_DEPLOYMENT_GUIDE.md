# CI/CD Deployment Guide with Database Backup

This guide explains the CI/CD pipeline setup for SarvCast that ensures safe deployments with database backups and data preservation.

## 📋 Overview

The CI/CD pipeline is designed to:
- ✅ **Automatically backup database** before any migration
- ✅ **Preserve existing data** during migrations
- ✅ **Deploy code updates** safely
- ✅ **Provide rollback capability** if something goes wrong
- ✅ **Use SSH** for secure server access

## 🔧 Architecture

### Deployment Flow

```
1. Code Push to Main Branch
   ↓
2. GitHub Actions Triggered
   ↓
3. Files Synced via FTP
   ↓
4. Database Backup Created (via SSH)
   ↓
5. Composer Dependencies Updated
   ↓
6. Safe Migration Execution
   ↓
7. Application Optimization
   ↓
8. Deployment Verification
```

## 🛠️ Components

### 1. GitHub Actions Workflow (`.github/workflows/main.yml`)

The main workflow that orchestrates the entire deployment process.

**Key Features:**
- Automatic backup before migrations
- Safe migration execution with rollback capability
- Error handling and notifications
- Telegram notifications on completion

### 2. Database Backup Script (`scripts/backup-database-ssh.sh`)

Creates compressed database backups with metadata.

**Usage:**
```bash
./scripts/backup-database-ssh.sh [backup_name]
```

**Features:**
- Supports MySQL, MariaDB, and PostgreSQL
- Automatic compression (gzip)
- Metadata tracking (git commit, branch, timestamp)
- Automatic cleanup of old backups (30 days retention)
- Works via SSH

**Backup Location:**
- Default: `/backups/sarvcast/`
- Format: `backup_YYYYMMDD_HHMMSS.sql.gz`

### 3. Safe Migration Script (`scripts/safe-migrate.sh`)

Runs migrations safely with automatic rollback on failure.

**Usage:**
```bash
./scripts/safe-migrate.sh [--rollback-on-failure]
```

**Features:**
- Pre-migration backup
- Migration validation
- Automatic rollback on failure
- Post-migration verification
- Optional post-migration backup

### 4. Database Restore Script (`scripts/restore-database.sh`)

Restores database from a backup file.

**Usage:**
```bash
./scripts/restore-database.sh <backup_file.sql.gz>
```

**Features:**
- Supports compressed backups (.gz)
- Safety confirmation prompt
- Automatic cache clearing
- Works with MySQL, MariaDB, and PostgreSQL

## 🚀 Setup Instructions

### 1. GitHub Secrets Configuration

Add these secrets to your GitHub repository:

```
SSH_HOST=your-server-ip-or-domain
SSH_USERNAME=your-ssh-username
SSH_PASSWORD=your-ssh-password
SSH_PORT=22 (optional, defaults to 22)
APP_PATH=/public_html/my (optional, your app path)
```

**To add secrets:**
1. Go to your GitHub repository
2. Settings → Secrets and variables → Actions
3. Click "New repository secret"
4. Add each secret above

### 2. Server Setup

#### Create Backup Directory

```bash
sudo mkdir -p /backups/sarvcast
sudo chown -R $USER:$USER /backups/sarvcast
chmod 755 /backups/sarvcast
```

#### Install Required Tools

```bash
# For MySQL/MariaDB
sudo apt-get update
sudo apt-get install -y mysql-client

# For PostgreSQL
sudo apt-get install -y postgresql-client

# Ensure gzip is available
sudo apt-get install -y gzip
```

#### Set Script Permissions

The scripts will be automatically made executable during deployment, but you can also do it manually:

```bash
chmod +x scripts/backup-database-ssh.sh
chmod +x scripts/safe-migrate.sh
chmod +x scripts/restore-database.sh
```

### 3. Database Configuration

Ensure your `.env` file has the correct database credentials:

```env
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

## 📝 Usage

### Automatic Deployment

Simply push to the `main` branch:

```bash
git add .
git commit -m "Your changes"
git push origin main
```

The CI/CD pipeline will:
1. Backup the database
2. Deploy code
3. Run migrations safely
4. Optimize the application

### Manual Backup

To create a manual backup:

```bash
cd /path/to/your/app
./scripts/backup-database-ssh.sh manual_backup_$(date +%Y%m%d)
```

### Manual Migration

To run migrations manually with backup:

```bash
cd /path/to/your/app
./scripts/safe-migrate.sh
```

### Manual Restore

To restore from a backup:

```bash
cd /path/to/your/app
./scripts/restore-database.sh /backups/sarvcast/backup_20250101_120000.sql.gz
```

### Rollback via GitHub Actions

1. Go to your GitHub repository
2. Actions → Rollback Deployment
3. Click "Run workflow"
4. Enter the backup file path
5. Click "Run workflow"

## 🔍 Monitoring

### Check Backup Status

```bash
ls -lh /backups/sarvcast/
```

### View Backup Metadata

```bash
cat /backups/sarvcast/backup_*.json
```

### Check Migration Status

```bash
php artisan migrate:status
```

### View Deployment Logs

GitHub Actions logs are available in:
- Repository → Actions → Latest workflow run

## 🛡️ Safety Features

### 1. Pre-Migration Backup
- Automatic backup before every migration
- Backup includes timestamp and git commit info
- Compressed to save space

### 2. Migration Validation
- Dry-run mode (when supported)
- Migration status checking
- Error detection and reporting

### 3. Automatic Rollback
- On migration failure, automatic rollback is attempted
- Backup file location is logged
- Manual restore instructions provided

### 4. Data Preservation
- Laravel migrations are designed to preserve data
- Column additions use `nullable()` by default
- Data migration scripts included where needed

## ⚠️ Important Notes

### Database Migrations Best Practices

1. **Always use nullable columns** when adding new fields:
   ```php
   $table->string('new_field')->nullable();
   ```

2. **Use data migration** for complex changes:
   ```php
   DB::statement('UPDATE table SET column = value WHERE condition');
   ```

3. **Test migrations locally** before pushing

4. **Review migration files** before deployment

### Backup Retention

- Backups are kept for **30 days** by default
- Old backups are automatically cleaned up
- Adjust `RETENTION_DAYS` in backup script if needed

### Storage Considerations

- Each backup is compressed (typically 10-50MB)
- Monitor disk space in `/backups/sarvcast/`
- Consider off-site backup storage for critical data

## 🔄 Rollback Procedure

If a deployment fails:

### Option 1: Automatic Rollback (if enabled)
The pipeline will attempt automatic rollback if migrations fail.

### Option 2: Manual Rollback via Script
```bash
cd /path/to/your/app
./scripts/restore-database.sh /backups/sarvcast/pre_deployment_YYYYMMDD_HHMMSS.sql.gz
```

### Option 3: Rollback via GitHub Actions
Use the "Rollback Deployment" workflow in GitHub Actions.

## 📊 Troubleshooting

### Backup Fails

**Issue:** Backup script fails to connect to database

**Solution:**
- Check database credentials in `.env`
- Verify database server is running
- Check network connectivity
- Verify user has backup permissions

### Migration Fails

**Issue:** Migration fails during deployment

**Solution:**
1. Check the error message in GitHub Actions logs
2. Review the migration file for issues
3. Test migration locally
4. Restore from backup if needed
5. Fix migration and redeploy

### SSH Connection Issues

**Issue:** Cannot connect to server via SSH

**Solution:**
- Verify SSH credentials in GitHub secrets
- Check server firewall allows SSH
- Verify SSH service is running
- Test SSH connection manually

### Permission Errors

**Issue:** Scripts cannot be executed

**Solution:**
```bash
chmod +x scripts/*.sh
```

## 🎯 Best Practices

1. **Always test migrations locally** before pushing
2. **Review migration files** in pull requests
3. **Keep backups** for at least 7 days
4. **Monitor disk space** for backup directory
5. **Document breaking changes** in migration comments
6. **Use feature flags** for risky changes
7. **Deploy during low-traffic periods** when possible

## 📞 Support

For issues or questions:
1. Check GitHub Actions logs
2. Review server logs: `/tmp/sarvcast-*.log`
3. Check Laravel logs: `storage/logs/laravel.log`
4. Review backup metadata files

## 🔐 Security Considerations

1. **Never commit** `.env` file or credentials
2. **Use GitHub Secrets** for sensitive data
3. **Restrict SSH access** to necessary IPs
4. **Use SSH keys** instead of passwords when possible
5. **Regularly rotate** database passwords
6. **Encrypt backups** for sensitive data (optional)

## 📈 Future Enhancements

Potential improvements:
- [ ] Automated backup verification
- [ ] Off-site backup storage (S3, etc.)
- [ ] Backup encryption
- [ ] Automated health checks after deployment
- [ ] Staging environment deployment
- [ ] Blue-green deployment strategy
- [ ] Database migration testing in CI

