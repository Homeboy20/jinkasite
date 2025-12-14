# Quick Production Setup Guide

## Step 1: Run Preparation Check
```bash
php prepare-production.php
```

## Step 2: Generate Security Keys

### Option A: Using OpenSSL (Recommended)
```bash
# Generate SECRET_KEY (64 characters)
openssl rand -base64 48

# Generate ENCRYPTION_KEY (32 characters minimum)
openssl rand -base64 32
```

### Option B: Using PHP
```php
<?php
// Run this in PHP to generate keys
echo "SECRET_KEY: " . bin2hex(random_bytes(32)) . "\n";
echo "ENCRYPTION_KEY: " . bin2hex(random_bytes(32)) . "\n";
?>
```

### Option C: Online Generator
Visit: https://randomkeygen.com/
- Use "CodeIgniter Encryption Keys" or "256-bit WPA Key"

## Step 3: Update Configuration

Edit `includes/config.php`:

```php
// Change these lines:
define('ENVIRONMENT', 'production');  // Was: 'development'
define('DEBUG_MODE', false);          // Was: true

// Update database:
define('DB_HOST', 'localhost');
define('DB_NAME', 'youruser_jinka');  // Your DirectAdmin DB name
define('DB_USER', 'youruser_jinka');  // Your DirectAdmin DB user
define('DB_PASS', 'strong_password'); // Your DB password

// Update security keys:
define('SECRET_KEY', 'paste_generated_key_here');
define('ENCRYPTION_KEY', 'paste_generated_key_here');

// Update site URL:
define('SITE_URL', 'https://yourdomain.com');

// Disable sandbox mode:
define('PAYMENT_USE_SANDBOX', false);
```

## Step 4: Export Database

### Option A: phpMyAdmin
1. Open http://localhost/phpmyadmin
2. Select `jinkaplotterwebsite` database
3. Click **Export** tab
4. Click **Go**
5. Save as `jinkaplotterwebsite.sql`

### Option B: Command Line
```bash
mysqldump -u root -p jinkaplotterwebsite > jinkaplotterwebsite.sql
```

## Step 5: Remove Test Files

```bash
# Windows PowerShell
Remove-Item test*.php, debug*.php, phpinfo.php, setup*.php -ErrorAction SilentlyContinue
Remove-Item database\setup.php -ErrorAction SilentlyContinue
```

## Step 6: Create Deployment Package

### Option A: ZIP Archive
```powershell
# Create ZIP excluding unnecessary files
Compress-Archive -Path * -DestinationPath jinkaplotterwebsite-production.zip -Force
```

### Option B: Use FTP Client
- Download FileZilla: https://filezilla-project.org/
- Connect to your server
- Upload all files to `public_html/`

## Step 7: Deploy to DirectAdmin

### A. Upload Files
1. Login to DirectAdmin
2. Go to **File Manager**
3. Navigate to `public_html/`
4. Upload ZIP file or use FTP

### B. Create Database
1. Go to **MySQL Management**
2. Click **Create new Database**
3. Database name: `youruser_jinka`
4. Create user with strong password
5. Grant all privileges

### C. Import Database
1. Go to **phpMyAdmin** in DirectAdmin
2. Select your new database
3. Click **Import** tab
4. Choose `jinkaplotterwebsite.sql`
5. Click **Go**

### D. Configure Web Server
1. Copy `.htaccess.production` to `.htaccess`
2. Update domain in `.htaccess` if needed
3. Set file permissions:
   ```bash
   chmod 755 public_html/
   chmod 644 *.php
   chmod 755 uploads/ cache/ logs/
   chmod 600 includes/config.php
   ```

## Step 8: SSL Certificate

1. In DirectAdmin: **SSL Certificates**
2. Click **Let's Encrypt**
3. Select your domain
4. Click **Save**

## Step 9: Test Everything

- [ ] Homepage loads: https://yourdomain.com
- [ ] Products page works
- [ ] Customer registration works
- [ ] Customer login works
- [ ] Admin login works: https://yourdomain.com/admin
- [ ] Add to cart works
- [ ] Checkout process works
- [ ] Email sending works (test contact form)

## Step 10: Firebase Configuration (if using SMS OTP)

1. Login to admin panel
2. Go to **Settings → Integrations**
3. Add Firebase configuration
4. In Firebase Console:
   - Authentication → Settings → Authorized domains
   - Add your production domain

## Step 11: Payment Gateway Setup

Update each payment gateway with production credentials:

**In Admin Panel:**
- Settings → Payment Gateways
- Add production API keys for each gateway

**Test with small transactions**

## Step 12: Monitoring

### Check Logs Regularly
```bash
# View error logs
tail -f logs/php_errors.log
tail -f logs/$(date +%Y-%m-%d).log
```

### Set Up Backups
- Database: Daily automatic backup in DirectAdmin
- Files: Weekly backup of `uploads/` folder

## Common Issues & Solutions

### Issue: "Database connection failed"
**Solution:** Check DB credentials in `config.php`

### Issue: "500 Internal Server Error"
**Solution:** Check file permissions, review error logs

### Issue: Images not displaying
**Solution:** Upload `images/` and `uploads/` folders

### Issue: CSS/JS not loading
**Solution:** Check `.htaccess` file permissions

### Issue: Firebase auth/captcha-check-failed
**Solution:** Add production domain to Firebase authorized domains

## Security Checklist

- [ ] Changed SECRET_KEY and ENCRYPTION_KEY
- [ ] Set ENVIRONMENT to 'production'
- [ ] Disabled DEBUG_MODE
- [ ] Removed test files
- [ ] Set restrictive file permissions
- [ ] SSL certificate installed
- [ ] .htaccess security headers enabled
- [ ] Admin area password is strong
- [ ] Database user has strong password
- [ ] SMTP credentials secured

## Performance Optimization

- [ ] Enable cache in admin settings
- [ ] Test site speed: https://pagespeed.web.dev/
- [ ] Optimize images (compress large files)
- [ ] Enable Gzip compression (in `.htaccess`)

## Rollback Plan

If something goes wrong:

1. **Restore database:**
   ```bash
   mysql -u username -p database_name < backup.sql
   ```

2. **Restore files:**
   - Extract backup ZIP
   - Upload via FTP

3. **Check error logs:**
   - `logs/php_errors.log`
   - DirectAdmin error logs

## Support

**Documentation:**
- `PRODUCTION-DEPLOYMENT-CHECKLIST.md` - Complete checklist
- `FIREBASE-DOMAIN-SETUP.md` - Firebase configuration
- `FIREBASE-QUICK-PASTE-GUIDE.md` - Quick paste feature

**Need Help?**
- Check error logs first
- Review DirectAdmin documentation
- Contact hosting support for server issues

---

## Quick Command Reference

```bash
# Generate security key
openssl rand -base64 48

# Export database
mysqldump -u root -p dbname > backup.sql

# Import database
mysql -u username -p dbname < backup.sql

# Set permissions
chmod 755 folder/
chmod 644 file.php
chmod 600 config.php

# View logs
tail -f logs/php_errors.log

# Check PHP version
php -v

# Test database connection
php -r "new mysqli('host','user','pass','db');"
```
