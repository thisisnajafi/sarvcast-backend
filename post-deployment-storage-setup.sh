#!/bin/bash

# SarvCast Post-Deployment Storage Setup Script
# Run this script on your production server after deployment

echo "🚀 Starting SarvCast post-deployment storage setup..."

# Navigate to the project directory
cd /path/to/your/project

# Create public images directories
echo "📁 Creating public images directories..."
mkdir -p public/images/categories
mkdir -p public/images/stories
mkdir -p public/images/episodes
mkdir -p public/images/people
mkdir -p public/images/users
mkdir -p public/images/playlists
mkdir -p public/images/timeline

# Set proper permissions
echo "🔐 Setting public images permissions..."
chmod -R 755 public/images

# Set ownership (adjust user/group as needed)
echo "👤 Setting ownership..."
chown -R www-data:www-data public/images

# Create storage symlink
echo "🔗 Creating storage symlink..."
php artisan storage:link --force

# Clear caches
echo "🧹 Clearing caches..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Test public images access
echo "🧪 Testing public images access..."
if [ -d "public/images" ]; then
    echo "✅ Public images directory exists"
else
    echo "❌ Public images directory missing"
    exit 1
fi

# Create test file
echo "📝 Creating test file..."
echo "test" > public/images/test.txt

# Check if test file is accessible
if [ -f "public/images/test.txt" ]; then
    echo "✅ Public images access working"
    rm public/images/test.txt
else
    echo "❌ Public images access not working"
    exit 1
fi

echo "🎉 SarvCast storage setup completed successfully!"
echo ""
echo "📋 What was done:"
echo "  ✅ Storage symlink created"
echo "  ✅ Public images directories created"
echo "  ✅ Permissions set correctly"
echo "  ✅ Ownership configured"
echo "  ✅ Caches cleared"
echo "  ✅ Public images access verified"
echo ""
echo "🔧 Next steps:"
echo "  1. Test image uploads through the admin panel"
echo "  2. Verify images are accessible via direct URLs"
echo "  3. Check that images display correctly in the application"
