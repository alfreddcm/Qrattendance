# Deployment Notes - Custom cPanel Setup

## ⚠️ Important: Non-Standard Laravel Setup

**Your cPanel has a custom configuration:**
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

## Deployment Steps

### 1. Pull latest changes
```bash
cd /home/sgvihssc/public_html
git pull origin main
```

### 2. Run storage fix script
```bash
bash fix-storage-link-cpanel.sh
```

### 3. Clear caches
```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

### 4. Verify .env settings
```bash
grep FILESYSTEM_DISK .env
# Should show: FILESYSTEM_DISK=public
```

## Common Issues

### Logo not appearing
- Check: `ls -la storage/school_logos/`
- Should be symlink to `app/public/school_logos/`
- If broken: Re-run `fix-storage-link-cpanel.sh`

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
