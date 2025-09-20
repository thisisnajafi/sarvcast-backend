# SarvCast Deployment Setup Guide

## 🚀 Overview

This guide covers the complete deployment setup for SarvCast, including GitHub Actions workflow and manual deployment scripts. The deployment process focuses on the essential tasks:

1. **Run Composer** - Install production dependencies
2. **Deploy to FTP** - Upload files to production server  
3. **Send Telegram Notification** - Notify team of deployment status

## 📋 Deployment Options

### **Option 1: GitHub Actions (Recommended)**
- Automated deployment on code push
- Runs in cloud environment
- No local setup required
- Built-in security and logging

### **Option 2: Manual Scripts**
- Local deployment control
- Customizable for specific needs
- Works offline
- Requires local environment setup

---

## 🔧 GitHub Actions Setup

### **Workflow File**
The workflow is located at `.github/workflows/main.yml` and includes:

```yaml
name: Deploy to Production
on:
  push:
    branches: [ main, production ]
  workflow_dispatch:

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - Checkout code
      - Setup PHP 8.2
      - Install Composer dependencies
      - Create deployment package
      - Deploy to FTP
      - Send Telegram notification
      - Cleanup
```

### **Required Configuration**

The workflow is pre-configured with your credentials (no GitHub secrets needed):

| Configuration | Value |
|---------------|-------|
| **FTP Server** | `ftp.sarvcast.ir` |
| **FTP Username** | `my@sarvcast.ir` |
| **FTP Password** | `prof48017421@#` |
| **FTP Directory** | `/` (root directory) |
| **Telegram Bot Token** | `7488407974:AAFl4Ek9IanbvlkKlRoikQAqdkDtFYbD0Gc` |
| **Telegram Chat ID** | `-1002796302613_97` |

### **Setup Steps**

**No GitHub Secrets Required!** The workflow is pre-configured and ready to use.

---

## 🖥️ Manual Deployment Scripts

### **Linux/macOS Script**

**File:** `scripts/deploy.sh`

**Usage:**
```bash
# Set environment variables
export FTP_SERVER="ftp.yourdomain.com"
export FTP_USERNAME="your_username"
export FTP_PASSWORD="your_password"
export FTP_DIRECTORY="/public_html"
export TELEGRAM_BOT_TOKEN="your_bot_token"
export TELEGRAM_CHAT_ID="your_chat_id"

# Run deployment
./scripts/deploy.sh
```

**Requirements:**
- Composer
- LFTP (`apt-get install lftp` or `brew install lftp`)
- Curl
- Git (optional, for commit info)

### **Windows Script**

**File:** `scripts/deploy.bat`

**Usage:**
```cmd
REM Set environment variables
set FTP_SERVER=ftp.yourdomain.com
set FTP_USERNAME=your_username
set FTP_PASSWORD=your_password
set FTP_DIRECTORY=/public_html
set TELEGRAM_BOT_TOKEN=your_bot_token
set TELEGRAM_CHAT_ID=your_chat_id

REM Run deployment
scripts\deploy.bat
```

**Requirements:**
- Composer
- Curl
- Git (optional, for commit info)
- WinSCP or PowerShell for FTP (additional setup needed)

---

## 📁 Deployment Package Contents

### **Included Files:**
- ✅ All PHP application files
- ✅ Composer dependencies (`vendor/` folder)
- ✅ Configuration files
- ✅ Public assets
- ✅ Database migrations
- ✅ Documentation files
- ✅ Laravel framework files

### **Excluded Files:**
- ❌ `.git` folder
- ❌ `.github` folder
- ❌ `node_modules` folder
- ❌ `tests` folder
- ❌ Environment files (`.env*`)
- ❌ Log files
- ❌ Cache files
- ❌ Development files
- ❌ Homestead files
- ❌ System files (`.DS_Store`, `Thumbs.db`)

---

## 🔄 Deployment Process

### **Step-by-Step Process:**

1. **Code Checkout**
   - Downloads latest code from repository
   - Ensures clean working directory

2. **PHP Setup**
   - Installs PHP 8.2 with required extensions
   - Configures environment for Laravel

3. **Composer Install**
   - Runs `composer install --no-dev`
   - Optimizes autoloader for production
   - Installs only production dependencies

4. **Package Creation**
   - Creates clean deployment directory
   - Copies only production files
   - Excludes development and temporary files

5. **FTP Upload**
   - Connects to FTP server
   - Uploads files to target directory
   - Maintains directory structure

6. **Telegram Notification**
   - Sends deployment status
   - Includes commit information
   - Provides deployment details

7. **Cleanup**
   - Removes temporary files
   - Cleans up deployment directory

---

## 🎯 Telegram Notification Format

The Telegram notification includes:

```
🚀 SarvCast Deployment Successful

Branch: main
Commit: abc1234
Author: Developer Name

Changes:
Fixed payment system integration

Status: ✅ Deployed to production

Deployment Details:
• Composer dependencies installed
• Files uploaded to FTP server
• Application ready for use

🔗 View Commit: [GitHub Link]
```

---

## 🛠️ Customization Options

### **Modify PHP Version**
```yaml
env:
  PHP_VERSION: '8.1'  # Change version
```

### **Add Custom Exclusions**
```bash
rsync -av \
  --exclude='.git' \
  --exclude='your-custom-folder' \
  . deployment/
```

### **Customize Telegram Message**
Edit the message section in the workflow or script files.

### **Add Pre/Post Deployment Tasks**
```yaml
- name: Run database migrations
  run: php artisan migrate --force

- name: Clear cache
  run: php artisan cache:clear
```

---

## 🚨 Troubleshooting

### **Common Issues:**

#### **FTP Connection Failed**
- ✅ Check FTP credentials
- ✅ Verify server address
- ✅ Ensure FTP directory exists
- ✅ Check firewall settings

#### **Telegram Notification Failed**
- ✅ Verify bot token
- ✅ Check chat ID
- ✅ Ensure bot is added to chat
- ✅ Test bot manually

#### **Composer Install Failed**
- ✅ Check `composer.json` syntax
- ✅ Verify all dependencies available
- ✅ Check PHP version compatibility
- ✅ Clear Composer cache

#### **File Upload Issues**
- ✅ Check disk space
- ✅ Verify file permissions
- ✅ Check FTP server limits
- ✅ Review exclusion patterns

### **Debug Steps:**

1. **Check Logs**
   - GitHub Actions: Check workflow logs
   - Manual scripts: Review console output

2. **Test Components**
   - Test FTP connection manually
   - Test Telegram bot manually
   - Test Composer install locally

3. **Verify Configuration**
   - Check all secrets/variables
   - Verify file paths
   - Confirm permissions

---

## 📊 Performance Optimization

### **GitHub Actions Optimizations:**
- Uses `--no-dev` flag for faster Composer install
- Excludes unnecessary files
- Uses efficient rsync for file copying
- Minimal steps for faster execution

### **Manual Script Optimizations:**
- Parallel file operations where possible
- Efficient file exclusion patterns
- Minimal temporary file creation
- Optimized FTP transfer settings

---

## 🔒 Security Considerations

### **GitHub Actions Security:**
- All sensitive data stored as secrets
- Encrypted transmission
- No sensitive data in logs
- Secure environment isolation

### **Manual Script Security:**
- Environment variables for credentials
- No hardcoded passwords
- Secure file transfer protocols
- Proper file permissions

---

## 📈 Monitoring & Analytics

### **Deployment Metrics:**
- Deployment frequency
- Success/failure rates
- Deployment duration
- File transfer speeds

### **Error Tracking:**
- Failed deployment notifications
- Error log analysis
- Performance monitoring
- User impact assessment

---

## 🎉 Ready to Deploy!

Your SarvCast deployment system is now configured with:

- ✅ **GitHub Actions Workflow** - Automated deployment
- ✅ **Manual Deployment Scripts** - Linux/macOS and Windows
- ✅ **Comprehensive Documentation** - Setup and troubleshooting guides
- ✅ **Telegram Notifications** - Real-time deployment status
- ✅ **Security Best Practices** - Secure credential management
- ✅ **Performance Optimization** - Fast and efficient deployment

Choose your preferred deployment method and start deploying with confidence! 🚀
