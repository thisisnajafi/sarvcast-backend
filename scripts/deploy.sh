#!/bin/bash

# SarvCast Deployment Script
# This script performs the essential deployment tasks:
# 1. Run Composer
# 2. Deploy to FTP
# 3. Send Telegram notification

set -e  # Exit on any error

# Configuration
FTP_SERVER="${FTP_SERVER:-ftp.sarvcast.ir}"
FTP_USERNAME="${FTP_USERNAME:-my@sarvcast.ir}"
FTP_PASSWORD="${FTP_PASSWORD:-prof48017421@#}"
FTP_DIRECTORY="${FTP_DIRECTORY:-/}"
TELEGRAM_BOT_TOKEN="${TELEGRAM_BOT_TOKEN:-7488407974:AAFl4Ek9IanbvlkKlRoikQAqdkDtFYbD0Gc}"
TELEGRAM_CHAT_ID="${TELEGRAM_CHAT_ID:--1002796302613_97}"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Functions
log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if required tools are installed
check_dependencies() {
    log_info "Checking dependencies..."
    
    if ! command -v composer &> /dev/null; then
        log_error "Composer is not installed"
        exit 1
    fi
    
    if ! command -v lftp &> /dev/null; then
        log_error "LFTP is not installed. Install with: apt-get install lftp (Ubuntu/Debian) or brew install lftp (macOS)"
        exit 1
    fi
    
    if ! command -v curl &> /dev/null; then
        log_error "Curl is not installed"
        exit 1
    fi
    
    log_success "All dependencies are available"
}

# Install Composer dependencies
install_composer() {
    log_info "Installing Composer dependencies..."
    
    if [ ! -f "composer.json" ]; then
        log_error "composer.json not found"
        exit 1
    fi
    
    composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist
    
    log_success "Composer dependencies installed"
}

# Create deployment package
create_deployment_package() {
    log_info "Creating deployment package..."
    
    # Create deployment directory
    rm -rf deployment
    mkdir -p deployment
    
    # Copy production files only (excluding vendor)
    rsync -av \
        --exclude='.git' \
        --exclude='.github' \
        --exclude='node_modules' \
        --exclude='tests' \
        --exclude='.env*' \
        --exclude='storage/logs/*' \
        --exclude='storage/framework/cache/*' \
        --exclude='storage/framework/sessions/*' \
        --exclude='storage/framework/views/*' \
        --exclude='bootstrap/cache/*' \
        --exclude='vendor' \
        --exclude='Homestead*' \
        --exclude='*.log' \
        --exclude='.DS_Store' \
        --exclude='Thumbs.db' \
        --exclude='deployment' \
        . deployment/
    
    log_success "Deployment package created (vendor excluded for faster upload)"
}

# Deploy to FTP
deploy_to_ftp() {
    log_info "Deploying to FTP server..."
    
    if [ -z "$FTP_SERVER" ] || [ -z "$FTP_USERNAME" ] || [ -z "$FTP_PASSWORD" ]; then
        log_error "FTP credentials not configured. Set FTP_SERVER, FTP_USERNAME, and FTP_PASSWORD environment variables."
        exit 1
    fi
    
    # Upload files to FTP
    lftp -c "
        set ftp:ssl-allow no;
        open -u $FTP_USERNAME,$FTP_PASSWORD $FTP_SERVER;
        lcd deployment;
        cd $FTP_DIRECTORY;
        mirror -R --delete --verbose --exclude-glob .git* --exclude-glob .env* --exclude-glob storage/logs/* --exclude-glob storage/framework/cache/* --exclude-glob storage/framework/sessions/* --exclude-glob storage/framework/views/* --exclude-glob bootstrap/cache/*;
        quit
    "
    
    log_success "Files deployed to FTP server"
    log_warning "Remember to run 'composer install --no-dev' on the server"
}

# Send Telegram notification
send_telegram_notification() {
    log_info "Sending Telegram notification..."
    
    if [ -z "$TELEGRAM_BOT_TOKEN" ] || [ -z "$TELEGRAM_CHAT_ID" ]; then
        log_warning "Telegram credentials not configured. Skipping notification."
        return
    fi
    
    # Get commit info
    COMMIT_HASH=$(git rev-parse --short HEAD 2>/dev/null || echo "unknown")
    COMMIT_MESSAGE=$(git log -1 --pretty=%B 2>/dev/null || echo "Manual deployment")
    BRANCH_NAME=$(git branch --show-current 2>/dev/null || echo "unknown")
    AUTHOR=$(git log -1 --pretty=%an 2>/dev/null || echo "unknown")
    
    # Create message
    MESSAGE="🚀 *SarvCast Deployment Successful*

*Branch:* \`$BRANCH_NAME\`
*Commit:* \`$COMMIT_HASH\`
*Author:* $AUTHOR

*Changes:*
$COMMIT_MESSAGE

*Status:* ✅ Deployed to production

*Deployment Details:*
• Composer dependencies installed
• Files uploaded to FTP server
• Application ready for use"

    # Send notification
    curl -s -X POST "https://api.telegram.org/bot$TELEGRAM_BOT_TOKEN/sendMessage" \
        -d chat_id="$TELEGRAM_CHAT_ID" \
        -d text="$MESSAGE" \
        -d parse_mode="Markdown" \
        -d disable_web_page_preview=true
    
    log_success "Telegram notification sent"
}

# Cleanup
cleanup() {
    log_info "Cleaning up..."
    rm -rf deployment
    log_success "Cleanup completed"
}

# Main deployment function
main() {
    log_info "Starting SarvCast deployment..."
    
    check_dependencies
    install_composer
    create_deployment_package
    deploy_to_ftp
    send_telegram_notification
    cleanup
    
    log_success "Deployment completed successfully!"
}

# Handle script interruption
trap cleanup EXIT

# Run main function
main "$@"
