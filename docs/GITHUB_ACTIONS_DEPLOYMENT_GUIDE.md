# GitHub Actions Deployment Setup Guide

## 🚀 Overview

This guide explains how to set up the GitHub Actions workflow for SarvCast deployment. The workflow performs only the essential tasks:

1. **Run Composer** - Install production dependencies
2. **Deploy to FTP** - Upload files to production server
3. **Send Telegram Notification** - Notify team of deployment status

## 📋 Required Configuration

The workflow is configured with direct credentials (no GitHub secrets needed):

### **FTP Configuration**
- **Server**: `ftp.sarvcast.ir`
- **Username**: `my@sarvcast.ir`
- **Password**: `prof48017421@#`
- **Directory**: `/` (root directory)

### **Telegram Configuration**
- **Bot Token**: `7488407974:AAFl4Ek9IanbvlkKlRoikQAqdkDtFYbD0Gc`
- **Chat ID**: `-1002796302613_97`

## 🔧 Setup Instructions

### **No GitHub Secrets Required!**

The workflow is pre-configured with your credentials, so no additional setup is needed. Simply push code to the `main` or `production` branch to trigger deployment.

## 🎯 Workflow Triggers

The workflow runs automatically when:
- Code is pushed to `main` branch
- Code is pushed to `production` branch
- Manually triggered via GitHub Actions UI

## 📁 What Gets Deployed

The workflow deploys only production-ready files:

### **Included Files:**
- All PHP application files
- Composer dependencies (vendor folder)
- Configuration files
- Public assets
- Database migrations
- Documentation

### **Excluded Files:**
- `.git` folder
- `.github` folder
- `node_modules` folder
- `tests` folder
- Environment files (`.env*`)
- Log files
- Cache files
- Development files

## 🔄 Workflow Steps

1. **Checkout Code** - Downloads the latest code
2. **Setup PHP** - Installs PHP 8.2 with required extensions
3. **Install Composer Dependencies** - Runs `composer install --no-dev`
4. **Create Deployment Package** - Prepares clean production files
5. **Deploy to FTP** - Uploads files to production server
6. **Send Telegram Notification** - Notifies team of deployment
7. **Cleanup** - Removes temporary files

## 📊 Telegram Notification Format

The Telegram notification includes:
- Deployment status (success/failure)
- Branch name
- Commit hash
- Author
- Commit message
- Link to commit on GitHub

## 🛠️ Customization

### **Modify PHP Version**
```yaml
env:
  PHP_VERSION: '8.1'  # Change to your preferred version
```

### **Add More Exclusions**
```yaml
rsync -av \
  --exclude='.git' \
  --exclude='your-custom-folder' \
  . deployment/
```

### **Modify Telegram Message**
Edit the message section in the workflow file to customize the notification format.

## 🚨 Troubleshooting

### **Common Issues:**

1. **FTP Connection Failed**
   - Check FTP credentials
   - Verify server address
   - Ensure FTP directory exists

2. **Telegram Notification Failed**
   - Verify bot token
   - Check chat ID
   - Ensure bot is added to the chat

3. **Composer Install Failed**
   - Check `composer.json` syntax
   - Verify all dependencies are available
   - Check PHP version compatibility

### **Debug Steps:**

1. Check GitHub Actions logs
2. Verify all secrets are set correctly
3. Test FTP connection manually
4. Test Telegram bot manually

## 📈 Performance Optimization

The workflow is optimized for speed:
- Uses `--no-dev` flag for Composer (faster install)
- Excludes unnecessary files
- Uses efficient rsync for file copying
- Minimal steps for faster execution

## 🔒 Security Considerations

- All sensitive data is stored as GitHub secrets
- FTP credentials are encrypted
- Bot tokens are secure
- No sensitive data in workflow logs

## 📝 Example Secrets Configuration

```
FTP_SERVER: ftp.yourdomain.com
FTP_USERNAME: your_ftp_username
FTP_PASSWORD: your_ftp_password
FTP_DIRECTORY: /public_html
TELEGRAM_BOT_TOKEN: 1234567890:ABCdefGHIjklMNOpqrsTUVwxyz
TELEGRAM_CHAT_ID: -1001234567890
```

## ✅ Verification

After setup, test the workflow by:
1. Making a small change to your code
2. Pushing to `main` branch
3. Checking GitHub Actions tab
4. Verifying files are uploaded to FTP
5. Confirming Telegram notification is received

---

## 🎉 Ready to Deploy!

Your GitHub Actions workflow is now configured for automated deployment. The workflow will handle all the essential tasks automatically whenever you push code to the main branches.
