#!/bin/bash

# Fix Storage Link for cPanel Deployment
# CUSTOM SETUP: Web root is public_html (not public_html/public)
# Usage: bash fix-storage-link-cpanel.sh

echo "========================================="
echo "  Storage Link Fix for cPanel"
echo "  Custom Setup: public_html is web root"
echo "========================================="
echo ""

cd "$(dirname "$0")"
echo "Current directory: $(pwd)"
echo ""

# Ensure storage/app/public exists
if [ ! -d "storage/app/public" ]; then
    echo "Creating storage/app/public..."
    mkdir -p storage/app/public/{student_pictures,school_logos,qr_codes,qr-codes,generated,templates,imports,branding}
    echo "✓ Created storage/app/public"
fi

# Remove broken symlink inside storage if it exists
if [ -L "storage/app/public/public" ]; then
    echo "Removing broken symlink from storage/app/public..."
    rm -f storage/app/public/public
    echo "✓ Removed broken symlink"
fi

# Create symlinks in storage/ pointing to app/public/ subdirectories
echo ""
echo "Creating storage symlinks..."

# Array of folders to symlink
folders=("student_pictures" "school_logos" "qr_codes" "qr-codes" "generated" "templates" "imports" "branding")

for folder in "${folders[@]}"; do
    # Backup old folder if it exists and is not a symlink
    if [ -d "storage/$folder" ] && [ ! -L "storage/$folder" ]; then
        echo "Backing up storage/$folder..."
        mv "storage/$folder" "storage/${folder}_backup_$(date +%Y%m%d_%H%M%S)"
    fi
    
    # Remove old symlink
    rm -f "storage/$folder"
    
    # Create new symlink
    if [ -d "storage/app/public/$folder" ]; then
        ln -s "app/public/$folder" "storage/$folder"
        echo "✓ storage/$folder -> app/public/$folder"
    fi
done

echo ""
echo "Setting permissions..."
chmod -R 755 storage
chmod -R 755 bootstrap/cache
echo "✓ Permissions set"

echo ""
echo "========================================="
echo "✅ STORAGE LINKS CREATED!"
echo "========================================="
echo ""
echo "Symlinks created in storage/:"
ls -la storage/ | grep "^l"
echo ""
echo "Files accessible at:"
echo "  https://sgvihsscan.com/storage/student_pictures/..."
echo "  https://sgvihsscan.com/storage/school_logos/..."
echo "  https://sgvihsscan.com/storage/qr_codes/..."
echo ""
echo "Next: Run 'php artisan config:clear' and 'php artisan cache:clear'"
echo ""
