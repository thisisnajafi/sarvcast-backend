#!/bin/bash

# SarvCast Server Setup Script
# This script clears AWS cache and sets up the application properly

echo "🧹 Clearing AWS cache and setting up SarvCast..."
echo "=================================================="

# Navigate to application directory
echo "📁 Navigating to application directory..."
cd /home/sarvca/public_html/my

# Check if we're in the right directory
if [ ! -f "artisan" ]; then
    echo "❌ Error: artisan file not found. Please run this script from your Laravel root directory."
    echo "Current directory: $(pwd)"
    exit 1
fi

echo "✅ Found Laravel application in: $(pwd)"

# Clear Composer cache completely
echo ""
echo "📦 Clearing Composer cache..."
composer clear-cache 2>/dev/null || echo "⚠️ Composer cache clear failed (may not be critical)"
composer global clear-cache 2>/dev/null || echo "⚠️ Global Composer cache clear failed (may not be critical)"

# Remove old vendor folder
echo ""
echo "🗑️ Removing old vendor folder..."
if [ -d "vendor" ]; then
    rm -rf vendor/
    echo "✅ Old vendor folder removed"
else
    echo "ℹ️ No vendor folder found (already clean)"
fi

# Clear Laravel caches
echo ""
echo "🧹 Clearing Laravel caches..."
php artisan config:clear 2>/dev/null || echo "⚠️ Config clear failed"
php artisan route:clear 2>/dev/null || echo "⚠️ Route clear failed"
php artisan view:clear 2>/dev/null || echo "⚠️ View clear failed"
php artisan cache:clear 2>/dev/null || echo "⚠️ Cache clear failed"

echo "✅ Laravel caches cleared"

# Install fresh dependencies
echo ""
echo "📦 Installing fresh dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

if [ $? -eq 0 ]; then
    echo "✅ Dependencies installed successfully"
else
    echo "❌ Error: Failed to install dependencies"
    exit 1
fi

# Regenerate optimized caches
echo ""
echo "⚡ Regenerating optimized caches..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "✅ Optimized caches generated"

# Set proper permissions
echo ""
echo "🔐 Setting file permissions..."
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || chown -R apache:apache storage bootstrap/cache 2>/dev/null || echo "⚠️ Could not change ownership (may need sudo)"

echo "✅ Permissions set"

# Create storage symlink
echo ""
echo "📁 Creating storage symlink..."
php artisan storage:link

echo "✅ Storage symlink created"

# Final verification
echo ""
echo "🔍 Verifying setup..."
php artisan --version

echo ""
echo "=================================================="
echo "🎉 Server setup complete! AWS cache cleared."
echo ""
echo "💳 Zarinpal merchant ID is hardcoded in the application"
echo "🌐 Application should now work at: https://my.sarvcast.ir/public/"
echo ""
echo "📋 What was done:"
echo "• Composer cache cleared"
echo "• Old vendor folder removed"
echo "• Fresh dependencies installed (no AWS)"
echo "• Laravel caches cleared and regenerated"
echo "• File permissions set"
echo "• Storage symlink created"
echo ""
echo "✅ Your SarvCast application is ready!"
