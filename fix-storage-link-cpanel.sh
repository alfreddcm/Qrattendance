#!/bin/bash

# Fix Storage Link for cPanel Deployment
# Single-source setup: public/storage -> ../storage/app/public
# Usage: bash fix-storage-link-cpanel.sh

echo "========================================="
echo "  Storage Link Fix for cPanel"
echo "  Standard Laravel public storage link"
echo "========================================="
echo ""

cd "$(dirname "$0")"
echo "Current directory: $(pwd)"
echo ""

# Ensure storage/app/public exists
if [ ! -d "storage/app/public" ]; then
    echo "Creating storage/app/public..."
    mkdir -p storage/app/public
    echo "Created storage/app/public"
fi

PUBLIC_STORAGE="public/storage"
TARGET_RELATIVE="../storage/app/public"
BACKUP_SUFFIX="$(date +%Y%m%d_%H%M%S)"

# Remove existing link or back up real directory.
if [ -L "$PUBLIC_STORAGE" ]; then
    echo "Removing existing symlink: $PUBLIC_STORAGE"
    rm -f "$PUBLIC_STORAGE"
elif [ -d "$PUBLIC_STORAGE" ]; then
    echo "Backing up existing directory: $PUBLIC_STORAGE"
    mv "$PUBLIC_STORAGE" "public/storage_backup_$BACKUP_SUFFIX"
fi

echo "Creating symlink: $PUBLIC_STORAGE -> $TARGET_RELATIVE"
ln -s "$TARGET_RELATIVE" "$PUBLIC_STORAGE"

if [ $? -ne 0 ]; then
    echo ""
    echo "Failed to create symlink. Try running:"
    echo "  php artisan storage:link"
    exit 1
fi

echo ""
echo "Setting permissions..."
chmod -R 755 storage
chmod -R 755 bootstrap/cache
echo "Permissions set"

echo ""
echo "========================================="
echo "STORAGE LINK CREATED"
echo "========================================="
echo ""
echo "Symlink status:"
ls -la public | grep " storage"
echo ""
echo "Next steps:"
echo "  php artisan config:clear"
echo "  php artisan cache:clear"
echo ""
