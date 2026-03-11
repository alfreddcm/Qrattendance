# Deployment Notes - Custom cPanel Setup

## ⚠️ Important: Non-Standard Laravel Setup

**Your cPanel has a cfustom configuration:**
- Web root: `public_html` (NOT `public_html/public`)
- Laravel files: Located directly in `public_html/`
- Storage files: `public_html/storage/app/public/`

## Storage Structure

```
public_html/                    ← Web root (!)
├── index.php                   ← Laravel entry point
├── storage/
│   ├── app/
│   │   └── public/             ← FILES STORED HERE
│   │       ├── student_pictures/
│   │       ├── school_logos/
│   │       ├── qr_codes/
│   │       ├── qr-codes/
│   │       ├── generated/
│   │       ├── templates/
│   │       └── imports/
│   │
│   ├── student_pictures/ → app/public/student_pictures/  ← SYMLINKS
│   ├── school_logos/ → app/public/school_logos/
│   ├── qr_codes/ → app/public/qr_codes/
│   ├── qr-codes/ → app/public/qr-codes/
│   ├── generated/ → app/public/generated/
│   ├── templates/ → app/public/templates/
│   └── imports/ → app/public/imports/
│
├── public/
│   └── storage/ → /home/sgvihssc/public_html/storage/app/public
└── ...
```

## URL Mapping

When templates use `asset('storage/student_pictures/photo.jpg')`:

```
URL: https://sgvihsscan.com/storage/student_pictures/photo.jpg
         ↓
File: public_html/storage/student_pictures/photo.jpg (symlink)
         ↓
Real file: public_html/storage/app/public/student_pictures/photo.jpg
```

## Quick Deployment

### On cPanel Terminal:

```bash
# 1. Navigate to project
cd /home/sgvihssc/public_html

# 2. Pull latest changes
git pull origin master

# 3. Run storage fix script
bash fix-storage-link-cpanel.sh

# 4. Clear caches
php artisan config:clear
php artisan cache:clear
```

## Manual Fix (if script fails)

```bash
cd /home/sgvihssc/public_html

# Remove broken symlink from storage/app/public if exists
rm -f storage/app/public/public

# Create symlinks for each folder
rm -rf storage/student_pictures
ln -s app/public/student_pictures storage/student_pictures

rm -rf storage/school_logos  
ln -s app/public/school_logos storage/school_logos

rm -rf storage/qr_codes
ln -s app/public/qr_codes storage/qr_codes

rm -rf storage/qr-codes
ln -s app/public/qr-codes storage/qr-codes

rm -rf storage/generated
ln -s app/public/generated storage/generated

rm -rf storage/templates
ln -s app/public/templates storage/templates

rm -rf storage/imports
ln -s app/public/imports storage/imports

# Set permissions
chmod -R 755 storage bootstrap/cache

# Clear cache
php artisan config:clear
php artisan cache:clear
```

## Verify Setup

```bash
# Check symlinks
ls -la storage/ | grep "^l"

# Should show:
# lrwxrwxrwx  1 sgvihssc sgvihssc   27 Mar  3 10:55 student_pictures -> app/public/student_pictures
# lrwxrwxrwx  1 sgvihssc sgvihssc   23 Mar  3 10:54 school_logos -> app/public/school_logos
# etc...

# Check .env
grep FILESYSTEM_DISK .env
# Should show: FILESYSTEM_DISK=public

# Test file access
curl -I https://sgvihsscan.com/storage/school_logos/YOUR_LOGO_FILE.png
# Should return: HTTP/2 200
```

## Common Issues

### Logo not appearing
- Check: `ls -la storage/school_logos/`
- Should be symlink to `app/public/school_logos/`
- If broken: Re-run `bash fix-storage-link-cpanel.sh`

### New uploads not appearing  
- Check .env: `FILESYSTEM_DISK=public`
- Clear config: `php artisan config:clear`

### 404 on storage files
- Verify symlinks: `ls -la storage/ | grep "^l"`
- Check permissions: `chmod -R 755 storage`

## Why This Setup?

Standard Laravel expects:
- Web root: `public/` folder  
- Storage symlink: `public/storage → ../storage/app/public`

Your setup:
- Web root: Root of project (public_html)
- Multiple symlinks: `storage/* → app/public/*`

This is because cPanel typically uses `public_html` as the document root, not a `public` subfolder.
