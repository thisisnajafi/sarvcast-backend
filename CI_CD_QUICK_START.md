# CI/CD Quick Start Guide

## 🚀 Quick Setup (5 minutes)

### 1. Configure GitHub Secrets

Go to: **Repository → Settings → Secrets and variables → Actions**

Add these secrets:
- `SSH_HOST` - Your server IP or domain
- `SSH_USERNAME` - SSH username
- `SSH_PASSWORD` - SSH password
- `SSH_PORT` - SSH port (optional, default: 22)
- `APP_PATH` - Application path (optional, default: `/public_html/my`)

### 2. Server Setup (One-time)

**Option A: System-level backup directory (Recommended if you have root access)**

```bash
# Create backup directory at system level
sudo mkdir -p /backups/sarvcast
sudo chown -R $USER:$USER /backups/sarvcast
```

**Option B: Application-level backup directory (For shared hosting or no root access)**

```bash
# Navigate to your application directory
cd /public_html/my  # or your actual app path

# Create backup directory inside your app (outside public access)
mkdir -p storage/backups
chmod 755 storage/backups
```

**Install MySQL client (if not already installed):**

```bash
# For Ubuntu/Debian
sudo apt-get install -y mysql-client

# For CentOS/RHEL
sudo yum install -y mysql
```

**Note:** If using Option B, you'll need to set the backup directory in your GitHub secret or modify the script. See "Custom Backup Location" section below.

### 3. Deploy!

Just push to `main` branch:

```bash
git push origin main
```

That's it! The pipeline will:
1. ✅ Backup your database
2. ✅ Deploy code
3. ✅ Run migrations safely
4. ✅ Optimize application

## 📋 What Gets Backed Up?

- **Database**: Full MySQL/MariaDB dump (compressed)
- **Default Location**: `/backups/sarvcast/` (system-level)
- **Alternative Location**: `storage/backups/` (inside your app)
- **Retention**: 30 days (auto-cleanup)
- **Format**: `pre_deployment_YYYYMMDD_HHMMSS.sql.gz`

### Custom Backup Location

If you want backups in `public_html/my/storage/backups` instead of `/backups/sarvcast`:

1. **Set GitHub Secret** (optional):
   - Add `BACKUP_DIR` secret with value: `/public_html/my/storage/backups`

2. **Or modify the script** on your server:
   ```bash
   # Edit the backup script
   nano /public_html/my/scripts/backup-database-ssh.sh
   # Change line 10: BACKUP_DIR="${BACKUP_DIR:-/public_html/my/storage/backups}"
   ```

## 🔄 Rollback (If Needed)

### Quick Rollback via Script

```bash
cd /path/to/your/app
./scripts/restore-database.sh /backups/sarvcast/pre_deployment_YYYYMMDD_HHMMSS.sql.gz
```

### Rollback via GitHub Actions

1. Go to **Actions** tab
2. Select **Rollback Deployment**
3. Click **Run workflow**
4. Enter backup file path
5. Click **Run workflow**

## 🛡️ Safety Features

- ✅ **Automatic backup** before every migration
- ✅ **Safe migrations** with rollback on failure
- ✅ **Data preservation** (Laravel migrations are non-destructive)
- ✅ **Error notifications** via Telegram

## 📞 Need Help?

See full documentation: `docs/CI_CD_DEPLOYMENT_GUIDE.md`

## ⚠️ Important Notes

1. **Test migrations locally** before pushing
2. **Review migration files** in pull requests
3. **Monitor disk space** for backups
4. **Keep backups** for at least 7 days

