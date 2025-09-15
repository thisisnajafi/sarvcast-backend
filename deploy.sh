#!/bin/bash

# SarvCast Deployment Script
# This script handles the deployment of the Laravel application

set -e

echo "🚀 Starting SarvCast deployment..."

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if we're in the right directory
if [ ! -f "artisan" ]; then
    print_error "This script must be run from the Laravel project root directory"
    exit 1
fi

# Check if PHP is installed
if ! command -v php &> /dev/null; then
    print_error "PHP is not installed or not in PATH"
    exit 1
fi

# Check if Composer is installed
if ! command -v composer &> /dev/null; then
    print_error "Composer is not installed or not in PATH"
    exit 1
fi

print_status "Environment checks passed"

# Install/Update dependencies
print_status "Installing dependencies..."
composer install --no-dev --optimize-autoloader

# Generate application key if not exists
if [ -z "$(grep APP_KEY= .env 2>/dev/null || echo '')" ] || [ "$(grep APP_KEY= .env 2>/dev/null | cut -d'=' -f2)" = "" ]; then
    print_status "Generating application key..."
    php artisan key:generate
fi

# Clear and cache configuration
print_status "Optimizing application..."
php artisan config:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run database migrations
print_status "Running database migrations..."
php artisan migrate --force

# Seed database if needed
if [ "$1" = "--seed" ]; then
    print_status "Seeding database..."
    php artisan db:seed --force
fi

# Create storage symlink
print_status "Creating storage symlink..."
php artisan storage:link

# Set proper permissions
print_status "Setting permissions..."
chmod -R 755 storage
chmod -R 755 bootstrap/cache

# Clear old logs
print_status "Clearing old logs..."
find storage/logs -name "*.log" -mtime +7 -delete 2>/dev/null || true

# Run tests
print_status "Running tests..."
php artisan test --parallel

# Create backup of current deployment
if [ -d "storage/app/backups" ]; then
    print_status "Creating deployment backup..."
    tar -czf "storage/app/backups/deployment-$(date +%Y%m%d-%H%M%S).tar.gz" \
        --exclude="vendor" \
        --exclude="node_modules" \
        --exclude=".git" \
        --exclude="storage/logs" \
        --exclude="storage/app/backups" \
        .
fi

# Health check
print_status "Performing health check..."
php artisan about

print_status "✅ Deployment completed successfully!"

# Display next steps
echo ""
echo "📋 Next steps:"
echo "1. Update your web server configuration"
echo "2. Set up SSL certificate"
echo "3. Configure environment variables"
echo "4. Set up cron jobs for scheduled tasks"
echo "5. Configure log rotation"
echo "6. Set up monitoring and alerts"
echo ""
echo "🔧 Useful commands:"
echo "- php artisan queue:work (for background jobs)"
echo "- php artisan schedule:run (for scheduled tasks)"
echo "- php artisan optimize (for performance optimization)"
echo "- php artisan config:cache (for configuration caching)"
echo ""
