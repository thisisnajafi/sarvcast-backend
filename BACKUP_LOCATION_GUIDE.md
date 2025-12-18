# Backup Location Guide

## 📍 Where Should the Backup Folder Be Created?

You have **two options** depending on your server setup:

---

## ✅ Option 1: Inside Your Application Directory (Recommended for Shared Hosting)

**Location**: `/public_html/my/storage/backups/`

### Why This Option?
- ✅ No root/sudo access needed
- ✅ Works on shared hosting
- ✅ Backups stay with your application
- ✅ Easy to manage

### Setup Steps:

1. **SSH into your server:**
   ```bash
   ssh your_username@your_server_ip
   ```

2. **Navigate to your application:**
   ```bash
   cd /public_html/my
   ```

3. **Create backup directory:**
   ```bash
   mkdir -p storage/backups
   chmod 755 storage/backups
   ```

4. **Verify it was created:**
   ```bash
   ls -la storage/backups
   ```

5. **No GitHub Secret needed!** The pipeline will automatically use this location.

### Directory Structure:
```
/public_html/my/
├── app/
├── config/
├── database/
├── public/
├── scripts/
├── storage/
│   ├── app/
│   ├── framework/
│   ├── logs/
│   └── backups/          ← Backups will be stored here
│       └── pre_deployment_20250101_120000.sql.gz
└── .env
```

---

## ✅ Option 2: System-Level Directory (Recommended if you have root access)

**Location**: `/backups/sarvcast/`

### Why This Option?
- ✅ Separated from application files
- ✅ Better for multiple applications
- ✅ Easier to manage backups centrally
- ✅ Can set up automated backups

### Setup Steps:

1. **SSH into your server:**
   ```bash
   ssh your_username@your_server_ip
   ```

2. **Create backup directory (requires sudo):**
   ```bash
   sudo mkdir -p /backups/sarvcast
   sudo chown -R $USER:$USER /backups/sarvcast
   sudo chmod 755 /backups/sarvcast
   ```

3. **Verify it was created:**
   ```bash
   ls -la /backups/sarvcast
   ```

4. **Set GitHub Secret:**
   - Go to: Repository → Settings → Secrets → Actions
   - Add secret: `BACKUP_DIR` = `/backups/sarvcast`

---

## 🎯 Which Option Should You Choose?

### Choose Option 1 (App Directory) if:
- ❌ You don't have root/sudo access
- ❌ You're on shared hosting
- ✅ You want simplicity
- ✅ You want backups with your app

### Choose Option 2 (System Directory) if:
- ✅ You have root/sudo access
- ✅ You manage multiple applications
- ✅ You want centralized backups
- ✅ You plan to set up automated backups

---

## 📋 Quick Decision Guide

**For your case (`public_html/my`):**

Since your app is in `/public_html/my`, I recommend:

### **Option 1: Use App Directory** ✅

```bash
cd /public_html/my
mkdir -p storage/backups
chmod 755 storage/backups
```

**Why?**
- Your app path suggests shared hosting
- No root access needed
- Backups will be at: `/public_html/my/storage/backups/`
- Works automatically without GitHub secrets

---

## 🔍 How to Verify It's Working

After your first deployment:

1. **Check if backup was created:**
   ```bash
   # For Option 1 (app directory)
   ls -lh /public_html/my/storage/backups/
   
   # For Option 2 (system directory)
   ls -lh /backups/sarvcast/
   ```

2. **You should see:**
   ```
   pre_deployment_20250101_120000.sql.gz
   ```

3. **Check backup size:**
   ```bash
   du -h /public_html/my/storage/backups/*.sql.gz
   ```

---

## ⚙️ Default Behavior

**If you don't set any GitHub secrets:**

The pipeline will automatically use:
- **Backup Location**: `/public_html/my/storage/backups/`
- **App Path**: `/public_html/my`

**This means Option 1 is the default!** ✅

---

## 🛠️ Changing Backup Location Later

If you want to change the backup location:

1. **Set GitHub Secret:**
   - Name: `BACKUP_DIR`
   - Value: `/path/to/your/backup/directory`

2. **Create the directory on server:**
   ```bash
   mkdir -p /path/to/your/backup/directory
   chmod 755 /path/to/your/backup/directory
   ```

3. **Next deployment will use the new location**

---

## 📊 Summary

| Option | Location | Requires Root? | GitHub Secret? |
|--------|----------|----------------|----------------|
| **Option 1** | `/public_html/my/storage/backups/` | ❌ No | ❌ No (default) |
| **Option 2** | `/backups/sarvcast/` | ✅ Yes | ✅ Yes (`BACKUP_DIR`) |

**Recommendation for you:** Use **Option 1** (app directory) - it's simpler and works automatically! 🎯

---

## ✅ Next Steps

1. **Create the backup directory** (choose one option above)
2. **Configure GitHub Secrets** (see `CI_CD_QUICK_START.md`)
3. **Push to main branch** and watch it work!

Need more help? See `CI_CD_SETUP_GUIDE.md` for detailed step-by-step instructions.

