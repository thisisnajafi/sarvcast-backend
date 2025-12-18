#!/bin/bash

# SarvCast Storage Link Script
# This script ensures the storage symlink exists
# Run this script after deployment or if storage files are not accessible

set -e

echo "🔗 Ensuring storage symlink exists..."

# Check if we're in a Laravel project
if [ ! -f "artisan" ]; then
    echo "❌ Error: This script must be run from the Laravel project root directory"
    exit 1
fi

# Check if storage directory exists
if [ ! -d "storage/app/public" ]; then
    echo "📁 Creating storage/app/public directory..."
    mkdir -p storage/app/public
    mkdir -p storage/app/public/audio/episodes
    mkdir -p storage/app/public/images
    echo "✅ Storage directories created"
fi

# Remove existing symlink if it exists and is broken
if [ -L "public/storage" ] && [ ! -e "public/storage" ]; then
    echo "🔧 Removing broken storage symlink..."
    rm public/storage
fi

# Create storage symlink
echo "🔗 Creating storage symlink..."
php artisan storage:link --force

# Verify symlink was created
if [ -L "public/storage" ] && [ -e "public/storage" ]; then
    echo "✅ Storage symlink created successfully!"
    echo "   Symlink: $(readlink -f public/storage)"
    echo "   Target: $(readlink -f storage/app/public)"
else
    echo "❌ Error: Failed to create storage symlink"
    exit 1
fi

# Set proper permissions
echo "🔐 Setting permissions..."
chmod -R 755 storage
chmod -R 755 public/storage 2>/dev/null || true

# Test storage access
echo "🧪 Testing storage access..."
if [ -d "public/storage" ]; then
    echo "✅ Storage is accessible via public/storage"
else
    echo "⚠️  Warning: Storage symlink may not be working correctly"
fi

echo ""
echo "🎉 Storage link setup completed!"
echo ""
echo "📋 Next steps:"
echo "   1. Verify audio files are accessible at: /storage/audio/episodes/"
echo "   2. Test image uploads in the admin panel"
echo "   3. Check that existing files are accessible via browser"

