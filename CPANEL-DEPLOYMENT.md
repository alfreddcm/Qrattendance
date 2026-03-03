# cPanel Deployment Guide - Storage Link Fix

## Problem Summary
Images and logs were not appearing on the website due to:
1. **Redundant file writes** - Files were being written to both `storage/app/public` AND `public/storage` directly
2. **Incorrect .env settings** - `FILESYSTEM_DISK=local` instead of `public`
3. **Missing or broken symlink** - `public/storage` must be a symbolic link to `storage/app/public`

## What Was Fixed

### 1. Removed Redundant File Writes
**Files Modified:**
- `app/Http/Controllers/AdminController.php`
- `app/Http/Controllers/StudentManagementController.php`

**Change:** Removed duplicate `file_put_contents()` calls that were writing QR codes directly to `public/storage`. Now files are only saved using `Storage::disk('public')->put()` which correctly saves to `storage/app/public`.

### 2. Correct Storage Structure
```
project-root/
├── storage/
│   └── app/
│       ├── private/      (for private files)
│       └── public/       ← PRIMARY STORAGE LOCATION
│           ├── student_pictures/
│           ├── school_logos/
│           ├── qr_codes/
│           ├── qr-codes/
│           ├── generated/
│           ├── imports/
│           └── templates/
│
└── public/
    └── storage/          ← SYMLINK to ../storage/app/public
```

**Key Point:** `public/storage` should be a **symbolic link**, NOT a real directory!

## Deployment Steps for cPanel

### Method 1: Using Bash Script (Recommended)

1. **Upload the fix script**
   - Upload `fix-storage-link-cpanel.sh` to your project root on cPanel

2. **Run via cPanel Terminal**
   ```bash
   cd /home/username/public_html  # Navigate to your project root
   bash fix-storage-link-cpanel.sh
   ```

3. **Verify the output**
   - Should show "✅ SUCCESS! Symbolic link created successfully!"

### Method 2: Using PHP Script (If Terminal Not Available)

1. **Upload the PHP script**
   - Upload `fix-storage-link.php` to your project root

2. **Run via browser**
   - Visit: `https://yourdomain.com/fix-storage-link.php`

3. **DELETE the script immediately**
   - Remove `fix-storage-link.php` from your server after running (security risk!)

### Method 3: Manual Terminal Commands

If both methods above fail, connect via SSH or cPanel Terminal:

```bash
# Navigate to project root
cd /home/username/public_html

# Remove old storage link/directory
rm -rf public/storage

# Create symbolic link (use relative path)
ln -s ../storage/app/public public/storage

# Set correct permissions
chmod -R 755 storage
chmod -R 755 bootstrap/cache

# Verify the link
ls -la public/ | grep storage
```

You should see output like:
```
lrwxrwxrwx ... storage -> ../storage/app/public
```

### Method 4: Laravel Artisan Command

```bash
cd /home/username/public_html
php artisan storage:link
```

## Update .env Configuration

1. **Copy the template**
   ```bash
   cp .env.cpanel .env
   ```

2. **Update these critical values** in `.env`:
   ```dotenv
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://youractualdomaincom
   
   # IMPORTANT: Set to 'public' not 'local'
   FILESYSTEM_DISK=public
   
   # Your database credentials
   DB_DATABASE=your_cpanel_database_name
   DB_USERNAME=your_cpanel_database_user
   DB_PASSWORD=your_database_password
   
   # Disable debug bar in production
   DEBUGBAR_ENABLED=false
   ```

3. **Generate APP_KEY** (if needed):
   ```bash
   php artisan key:generate
   ```

## Verify Everything Works

### 1. Check Symlink Status
```bash
ls -la public/storage
```
Should show:
```
lrwxrwxrwx ... public/storage -> ../storage/app/public
```

### 2. Check File Accessibility
Try accessing files in your browser:
- `https://yourdomain.com/storage/student_pictures/some_picture.jpg`
- `https://yourdomain.com/storage/qr_codes/some_qr.svg`
- `https://yourdomain.com/storage/school_logos/logo.png`

### 3. Check Storage Permissions
```bash
chmod -R 755 storage
chmod -R 755 bootstrap/cache
```

### 4. Test Upload Functionality
- Upload a student picture through your admin panel
- Verify it appears on the student list
- Check that the file exists in `storage/app/public/student_pictures/`

## Troubleshooting

### Images Still Not Showing?

**Problem:** 404 errors when accessing `/storage/...` URLs

**Solutions:**
1. **Check .htaccess** - Ensure it exists in `public/` directory
2. **Check symlink** - Run `ls -la public/storage` to verify
3. **Check permissions** - `chmod -R 755 storage`
4. **Clear cache** - Run `php artisan cache:clear` and `php artisan config:clear`
5. **Check APP_URL** - Must match your actual domain in `.env`

### Symlink Creation Fails?

**Error:** "Operation not permitted" or "Function not supported"

**Causes:**
- Shared hosting may restrict symlink() function
- File system doesn't support symlinks

**Alternative Solutions:**
1. Contact your hosting provider to enable symlinks
2. Use Laravel's artisan command: `php artisan storage:link`
3. Some cPanel hosts require you to request symlink permissions

### Files Exist But Show as Broken Links?

**Problem:** File paths are correct but images don't load

**Check:**
1. File permissions: `chmod -R 755 storage/app/public`
2. File ownership: May need to run `chown -R username:username storage`
3. SELinux settings (if enabled on server)
4. Browser console for actual error messages

## Important Notes

### File Upload Path
All files should be uploaded using:
```php
Storage::disk('public')->put('folder/filename.ext', $content);
```
This saves to: `storage/app/public/folder/filename.ext`
Accessible at: `/storage/folder/filename.ext`

### Never Use
```php
// DON'T DO THIS:
file_put_contents(public_path('storage/...'), $content);  // ✗ Wrong!
```

### URL Generation
Always use `asset()` helper:
```php
// In Blade templates:
<img src="{{ asset('storage/student_pictures/' . $student->picture) }}">

// In controllers:
$url = asset('storage/qr_codes/' . $filename);
```

## Security Checklist

After deployment:
- [ ] Delete `fix-storage-link.php` from server
- [ ] Set `APP_DEBUG=false` in `.env`
- [ ] Set `DEBUGBAR_ENABLED=false` in `.env`
- [ ] Set `APP_ENV=production` in `.env`
- [ ] Verify `.env` is not publicly accessible
- [ ] Check that `storage/` directory is not web-accessible (should be above/outside public/)
- [ ] Run `php artisan config:cache` to cache configuration

## Quick Reference Commands

```bash
# Navigate to project
cd /home/username/public_html

# Fix storage link
rm -rf public/storage && ln -s ../storage/app/public public/storage

# Set permissions
chmod -R 755 storage bootstrap/cache

# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear

# Cache for production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Check storage status
ls -la public/ | grep storage
ls -la storage/app/public/
```

## Files Included in This Fix

1. **fix-storage-link-cpanel.sh** - Bash script to fix symlink (recommended)
2. **fix-storage-link.php** - PHP web script to fix symlink (alternative)
3. **.env.cpanel** - Template .env file with correct settings for cPanel
4. **CPANEL-DEPLOYMENT.md** - This documentation file

## Need Help?

If you continue to experience issues:
1. Check your cPanel error logs
2. Check Laravel logs: `storage/logs/laravel.log`
3. Verify your hosting supports symbolic links
4. Contact your hosting provider if symlink() is disabled
