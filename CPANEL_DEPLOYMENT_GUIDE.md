# CPANEL DEPLOYMENT GUIDE - QR Attendance System

## 📋 Prerequisites

Before deploying, ensure you have:
- ✅ cPanel hosting account with Git Version Control enabled
- ✅ PHP 8.1 or higher
- ✅ MySQL database
- ✅ Composer installed on server
- ✅ SSH access (optional but recommended)

---

## 🚀 Step-by-Step Deployment Guide

### Step 1: Prepare Your Repository

1. **Commit all changes to Git:**
   ```bash
   git add .cpanel.yml .gitignore
   git commit -m "Add cPanel deployment configuration"
   git push origin master
   ```

### Step 2: Setup Database in cPanel

1. Log into your cPanel account
2. Go to **MySQL® Databases**
3. Create a new database:
   - Database name: `your_cpanel_username_qrattendance`
4. Create a database user:
   - Username: `your_cpanel_username_qruser`
   - Password: (create a strong password)
5. Add user to database with **ALL PRIVILEGES**
6. **Note down**: Database name, username, and password

### Step 3: Configure Git Version Control in cPanel

1. In cPanel, go to **Git™ Version Control**
2. Click **Create** button
3. Fill in the form:
   - **Clone a Repository**: Yes (toggle on)
   - **Clone URL**: `https://github.com/alfreddcm/Qrattendance.git`
   - **Repository Path**: `/home/YOUR_CPANEL_USERNAME/repositories/qrattendance`
   - **Repository Name**: `qrattendance`
4. Click **Create**

### Step 4: Edit .cpanel.yml Configuration

**IMPORTANT**: Before deployment, update the `.cpanel.yml` file:

1. Open `.cpanel.yml` in your local repository
2. Replace `YOUR_CPANEL_USERNAME` with your actual cPanel username
3. Save and commit:
   ```bash
   git add .cpanel.yml
   git commit -m "Update cPanel username in deployment config"
   git push origin master
   ```

### Step 5: Configure Environment Variables

1. In cPanel, go to **File Manager**
2. Navigate to `/home/YOUR_CPANEL_USERNAME/public_html`
3. Create/Edit `.env` file with these settings:

```env
APP_NAME="QR Attendance System"
APP_ENV=production
APP_KEY=base64:YOUR_APP_KEY_HERE
APP_DEBUG=false
APP_URL=https://yourdomain.com

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=your_cpanel_username_qrattendance
DB_USERNAME=your_cpanel_username_qruser
DB_PASSWORD=your_database_password

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120

# SMS Gateway Settings (if using)
SMS_GATEWAY_URL=
SMS_GATEWAY_USERNAME=
SMS_GATEWAY_PASSWORD=
```

4. Generate APP_KEY:
   - SSH into your server: `ssh your_cpanel_username@yourdomain.com`
   - Navigate to: `cd public_html`
   - Run: `php artisan key:generate`
   - Or manually generate and paste a base64 encoded 32-character string

### Step 6: Deploy from cPanel

1. Go back to **Git™ Version Control** in cPanel
2. Find your repository and click **Manage**
3. Click **Pull or Deploy** tab
4. Click **Update from Remote** button
5. After update, click **Deploy HEAD Commit** button
6. Wait for deployment to complete (this runs the `.cpanel.yml` tasks)

### Step 7: Run Database Migrations

1. SSH into your server or use **Terminal** in cPanel
2. Navigate to your application:
   ```bash
   cd /home/YOUR_CPANEL_USERNAME/public_html
   ```
3. Run migrations:
   ```bash
   php artisan migrate --force
   ```
4. (Optional) Seed database if you have seeders:
   ```bash
   php artisan db:seed --force
   ```

### Step 8: Set Correct Permissions

```bash
chmod -R 755 storage
chmod -R 755 bootstrap/cache
chown -R YOUR_CPANEL_USERNAME:YOUR_CPANEL_USERNAME storage
chown -R YOUR_CPANEL_USERNAME:YOUR_CPANEL_USERNAME bootstrap/cache
```

### Step 9: Configure Document Root

1. In cPanel, go to **Domains** or **Addon Domains**
2. Find your domain
3. Edit the **Document Root** to point to: `/home/YOUR_CPANEL_USERNAME/public_html/public`
4. Save changes

### Step 10: Create Storage Symlink

```bash
cd /home/YOUR_CPANEL_USERNAME/public_html
php artisan storage:link
```

---

## 🔄 Updating Your Application

When you need to update your application:

1. **Make changes locally** and commit to Git:
   ```bash
   git add .
   git commit -m "Your commit message"
   git push origin master
   ```

2. **In cPanel Git Version Control**:
   - Go to your repository
   - Click **Manage**
   - Click **Update from Remote**
   - Click **Deploy HEAD Commit**

3. **Clear Laravel caches** (via SSH):
   ```bash
   cd /home/YOUR_CPANEL_USERNAME/public_html
   php artisan config:clear
   php artisan cache:clear
   php artisan view:clear
   php artisan route:clear
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

---

## 📝 What `.cpanel.yml` Does

The `.cpanel.yml` file automates these tasks on each deployment:

1. ✅ Copies all files to `public_html`
2. ✅ Creates `.env` from `.env.example` (if not exists)
3. ✅ Installs Composer dependencies (production mode)
4. ✅ Caches configuration files
5. ✅ Caches routes
6. ✅ Caches views
7. ✅ Creates storage symlink
8. ✅ Sets correct permissions

---

## 🐛 Troubleshooting

### Issue: "500 Internal Server Error"

**Solution:**
```bash
# Check error logs
tail -f /home/YOUR_CPANEL_USERNAME/logs/error_log

# Or Laravel logs
tail -f /home/YOUR_CPANEL_USERNAME/public_html/storage/logs/laravel.log

# Clear all caches
php artisan optimize:clear
```

### Issue: "Composer not found"

**Solution:**
1. Check if Composer is installed: `composer --version`
2. If not, install it or contact hosting support
3. Update `.cpanel.yml` to use full Composer path: `/usr/local/bin/composer`

### Issue: "APP_KEY not set"

**Solution:**
```bash
cd /home/YOUR_CPANEL_USERNAME/public_html
php artisan key:generate
```

### Issue: "Storage files not writable"

**Solution:**
```bash
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

### Issue: "Database connection failed"

**Solution:**
1. Verify database credentials in `.env`
2. Check database name includes cPanel username prefix
3. Ensure database user has privileges
4. Use `localhost` for DB_HOST (not 127.0.0.1)

### Issue: "QR codes not displaying"

**Solution:**
```bash
# Recreate storage symlink
cd /home/YOUR_CPANEL_USERNAME/public_html
rm -rf public/storage
php artisan storage:link

# Set permissions
chmod -R 755 storage/app/public
```

---

## 🔐 Security Checklist

- [ ] `.env` file is NOT in Git repository (.gitignore includes it)
- [ ] `APP_DEBUG=false` in production
- [ ] `APP_ENV=production`
- [ ] Strong database password
- [ ] APP_KEY is generated and set
- [ ] File permissions are correct (755 for directories, 644 for files)
- [ ] HTTPS/SSL is enabled on your domain

---

## 📞 Support

If you encounter issues:
1. Check Laravel logs: `storage/logs/laravel.log`
2. Check cPanel error logs
3. Verify all environment variables are set correctly
4. Ensure Composer dependencies are installed
5. Contact your hosting provider for server-specific issues

---

## ✅ Post-Deployment Verification

Test these features:
- [ ] Homepage loads correctly
- [ ] Login works (teacher/admin)
- [ ] Database connection works
- [ ] Student records display
- [ ] QR codes generate and display
- [ ] File uploads work
- [ ] Reports generate
- [ ] SMS notifications (if configured)

---

## 🎯 Quick Commands Reference

```bash
# Navigate to app
cd /home/YOUR_CPANEL_USERNAME/public_html

# Clear all caches
php artisan optimize:clear

# Cache everything
php artisan config:cache && php artisan route:cache && php artisan view:cache

# Run migrations
php artisan migrate --force

# Check application status
php artisan about

# View logs
tail -f storage/logs/laravel.log
```

---

## 📦 Package Requirements

Ensure your server has these PHP extensions enabled:
- ✅ BCMath
- ✅ Ctype
- ✅ Fileinfo
- ✅ JSON
- ✅ Mbstring
- ✅ OpenSSL
- ✅ PDO
- ✅ Tokenizer
- ✅ XML
- ✅ GD (for QR code generation)

Check in cPanel: **Select PHP Version** → **Extensions**

---

**Deployment Complete! 🎉**

Your QR Attendance System should now be live at your domain.
