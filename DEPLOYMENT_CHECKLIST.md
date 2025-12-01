# ✅ CPANEL DEPLOYMENT CHECKLIST

Use this checklist to ensure successful deployment to cPanel.

---

## BEFORE DEPLOYMENT

### 1. Update .cpanel.yml
- [ ] Open `.cpanel.yml` file
- [ ] Find `YOUR_CPANEL_USERNAME` (appears in line 4)
- [ ] Replace with your actual cPanel username
- [ ] Save the file
- [ ] Commit and push:
  ```bash
  git add .cpanel.yml
  git commit -m "Update cPanel username"
  git push origin master
  ```

### 2. Verify Repository Files
- [✓] `.cpanel.yml` exists
- [✓] `.gitignore` exists  
- [✓] `.env.example` exists
- [✓] `composer.json` exists
- [✓] All changes committed to Git
- [✓] Pushed to GitHub

---

## CPANEL SETUP

### 3. Database Setup
- [ ] Log into cPanel
- [ ] Go to **MySQL® Databases**
- [ ] Create database: `username_qrattendance`
- [ ] Create user: `username_qruser`
- [ ] Set strong password
- [ ] Add user to database with ALL PRIVILEGES
- [ ] **Write down credentials**:
  - Database: ___________________
  - Username: ___________________
  - Password: ___________________

### 4. Git Version Control Setup
- [ ] In cPanel, go to **Git™ Version Control**
- [ ] Click **Create**
- [ ] Fill form:
  - Clone URL: `https://github.com/alfreddcm/Qrattendance.git`
  - Repository Path: `/home/YOUR_USERNAME/repositories/qrattendance`
  - Repository Name: `qrattendance`
- [ ] Click **Create** and wait for cloning to complete

### 5. Initial Deployment
- [ ] In Git Version Control, click **Manage** on your repo
- [ ] Go to **Pull or Deploy** tab
- [ ] Click **Update from Remote**
- [ ] Click **Deploy HEAD Commit**
- [ ] Wait for deployment to complete (may take 2-5 minutes)
- [ ] Check for any errors in deployment log

---

## POST-DEPLOYMENT CONFIGURATION

### 6. Configure .env File
- [ ] In cPanel, go to **File Manager**
- [ ] Navigate to `/home/YOUR_USERNAME/public_html`
- [ ] Find `.env` file (if not exists, copy from `.env.example`)
- [ ] Edit `.env` file with these values:
  ```
  APP_ENV=production
  APP_DEBUG=false
  APP_URL=https://yourdomain.com
  
  DB_DATABASE=username_qrattendance
  DB_USERNAME=username_qruser
  DB_PASSWORD=your_password_here
  ```
- [ ] Save changes

### 7. Generate Application Key
Via SSH or Terminal:
- [ ] Connect: `ssh username@yourdomain.com`
- [ ] Navigate: `cd public_html`
- [ ] Generate: `php artisan key:generate`
- [ ] Verify `.env` has `APP_KEY=base64:...`

### 8. Run Migrations
- [ ] In SSH/Terminal: `php artisan migrate --force`
- [ ] Check for migration success
- [ ] (Optional) Seed: `php artisan db:seed --force`

### 9. Set Document Root
- [ ] In cPanel, go to **Domains**
- [ ] Find your domain
- [ ] Click **Manage**
- [ ] Change Document Root to: `/home/USERNAME/public_html/public`
- [ ] Save changes

### 10. Verify Permissions
Via SSH:
```bash
cd /home/USERNAME/public_html
chmod -R 755 storage
chmod -R 755 bootstrap/cache
php artisan storage:link
```
- [ ] Storage directory writable
- [ ] Bootstrap/cache writable
- [ ] Symlink created

---

## TESTING

### 11. Access Your Application
- [ ] Visit: `https://yourdomain.com`
- [ ] Homepage loads successfully
- [ ] No errors displayed

### 12. Test Core Features
- [ ] Admin login works
- [ ] Teacher login works
- [ ] Can view students
- [ ] Can create student
- [ ] QR codes generate
- [ ] QR codes display correctly
- [ ] File uploads work
- [ ] Reports generate

### 13. Check Logs (if errors occur)
- [ ] Laravel log: `storage/logs/laravel.log`
- [ ] cPanel error log: In File Manager or via logs interface
- [ ] Fix any issues found

---

## MAINTENANCE

### 14. Future Updates
When you need to update:
```bash
# Local machine
git add .
git commit -m "Update message"
git push origin master

# Then in cPanel:
# 1. Go to Git Version Control
# 2. Click Manage on repository
# 3. Click "Update from Remote"
# 4. Click "Deploy HEAD Commit"
```

### 15. Clear Caches After Updates
Via SSH:
```bash
cd /home/USERNAME/public_html
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 🚨 TROUBLESHOOTING

### Common Issues:

**500 Error:**
- Check `storage/logs/laravel.log`
- Verify file permissions (755 for storage)
- Check APP_KEY is set
- Clear caches: `php artisan optimize:clear`

**Database Connection Error:**
- Verify .env database credentials
- Check database name includes username prefix
- Ensure user has privileges

**QR Codes Not Showing:**
- Run: `php artisan storage:link`
- Check: `public/storage` symlink exists
- Verify: storage/app/public permissions (755)

**Composer Errors:**
- Update .cpanel.yml with full composer path
- Contact hosting support if composer not installed

---

## 📞 SUPPORT CONTACTS

- **Hosting Provider Support:** _________________
- **Domain Registrar:** _________________
- **Developer Contact:** _________________

---

## ✅ DEPLOYMENT COMPLETE

- [ ] All checklist items completed
- [ ] Application is live and working
- [ ] Credentials documented securely
- [ ] Backups scheduled (cPanel backup tools)

**Deployment Date:** ___________________
**Domain:** ___________________
**Deployed By:** ___________________

---

**Congratulations! Your QR Attendance System is now live! 🎉**
