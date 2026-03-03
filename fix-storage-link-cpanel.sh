#!/bin/bash

# Fix Storage Link for cPanel Deployment
# This script properly creates the storage symlink for Laravel on cPanel
# Usage: Upload to your project root on cPanel, then run: bash fix-storage-link-cpanel.sh

echo "========================================="
echo "  Laravel Storage Link Fix for cPanel"
echo "========================================="
echo ""

# Navigate to project root (adjust if needed)
cd "$(dirname "$0")"

echo "Current directory: $(pwd)"
echo ""

# Check if storage/app/public exists
if [ ! -d "storage/app/public" ]; then
    echo "✗ Error: storage/app/public directory not found!"
    echo "Creating storage/app/public directory..."
    mkdir -p storage/app/public
    echo "✓ Created storage/app/public"
fi

# Remove existing storage link/directory if it exists
if [ -L "public/storage" ]; then
    echo "Removing existing symbolic link..."
    rm -f public/storage
    echo "✓ Old symlink removed"
elif [ -d "public/storage" ]; then
    echo "⚠ Warning: public/storage is a real directory (not a symlink)!"
    echo "This directory will be backed up then removed..."
    
    # Backup existing directory
    BACKUP_DIR="public/storage_backup_$(date +%Y%m%d_%H%M%S)"
    mv public/storage "$BACKUP_DIR"
    echo "✓ Backed up to: $BACKUP_DIR"
fi

# Create fresh symbolic link using relative path (works better on cPanel)
echo ""
echo "Creating symbolic link..."
ln -s ../storage/app/public public/storage

# Verify the link was created
if [ -L "public/storage" ]; then
    echo ""
    echo "✅ SUCCESS! Symbolic link created successfully!"
    echo ""
    echo "Link details:"
    ls -la public/ | grep storage
    echo ""
    
    # Show storage contents
    echo "Files in storage/app/public:"
    ls -la storage/app/public/
    echo ""
    
    # Set proper permissions
    echo "Setting permissions..."
    chmod -R 755 storage
    chmod -R 755 bootstrap/cache
    echo "✓ Permissions set"
    echo ""
    
    echo "========================================="
    echo "✅ STORAGE LINK FIXED!"
    echo "========================================="
    echo ""
    echo "Your files are now accessible at:"
    echo "https://yourdomain.com/storage/..."
    echo ""
    echo "Examples:"
    echo "- Student pictures: /storage/student_pictures/..."
    echo "- School logos: /storage/school_logos/..."
    echo "- QR codes: /storage/qr_codes/..."
    echo ""
else
    echo ""
    echo "❌ FAILED to create symbolic link automatically"
    echo ""
    echo "Please try manually:"
    echo "1. SSH into your cPanel"
    echo "2. Navigate to your project directory"
    echo "3. Run these commands:"
    echo ""
    echo "   cd $(pwd)"
    echo "   rm -rf public/storage"
    echo "   ln -s ../storage/app/public public/storage"
    echo "   chmod -R 755 storage"
    echo ""
    echo "Or try Laravel's artisan command:"
    echo "   php artisan storage:link"
    echo ""
fi
