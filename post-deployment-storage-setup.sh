#!/bin/bash

# SarvCast Post-Deployment Storage Setup Script
# Run this script on your production server after deployment

echo "🚀 Starting SarvCast post-deployment storage setup..."

# Navigate to the project directory
cd /path/to/your/project

# Create storage symlink
echo "🔗 Creating storage symlink..."
php artisan storage:link --force

# Set proper permissions
echo "🔐 Setting storage permissions..."
chmod -R 755 storage/app/public
chmod -R 755 public/storage

# Set ownership (adjust user/group as needed)
echo "👤 Setting ownership..."
chown -R www-data:www-data storage
chown -R www-data:www-data public/storage

# Clear caches
echo "🧹 Clearing caches..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Test storage access
echo "🧪 Testing storage access..."
if [ -L "public/storage" ]; then
    echo "✅ Storage symlink exists"
else
    echo "❌ Storage symlink missing"
    exit 1
fi

if [ -d "storage/app/public" ]; then
    echo "✅ Storage directory exists"
else
    echo "❌ Storage directory missing"
    exit 1
fi

# Create test file
echo "📝 Creating test file..."
echo "test" > storage/app/public/test.txt

# Check if test file is accessible
if [ -f "public/storage/test.txt" ]; then
    echo "✅ Storage access working"
    rm storage/app/public/test.txt
    rm public/storage/test.txt
else
    echo "❌ Storage access not working"
    exit 1
fi

echo "🎉 SarvCast storage setup completed successfully!"
echo ""
echo "📋 What was done:"
echo "  ✅ Storage symlink created"
echo "  ✅ Permissions set correctly"
echo "  ✅ Ownership configured"
echo "  ✅ Caches cleared"
echo "  ✅ Storage access verified"
echo ""
echo "🔧 Next steps:"
echo "  1. Configure your web server (Apache/Nginx) to serve /storage/ directly"
echo "  2. Test image uploads through the admin panel"
echo "  3. Verify images are accessible via direct URLs"
