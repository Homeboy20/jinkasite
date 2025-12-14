# Production Deployment Checklist

## Pre-Deployment

### 1. Configuration Files

- [ ] **Update `includes/config.php`:**
  ```php
  define('ENVIRONMENT', 'production');
  define('DEBUG_MODE', false);
  define('SITE_URL', 'https://yourdomain.com');
  ```

- [ ] **Update Database Credentials:**
  ```php
  define('DB_HOST', 'localhost');
  define('DB_NAME', 'production_db_name');
  define('DB_USER', 'production_db_user');
  define('DB_PASS', 'strong_password_here');
  ```

- [ ] **Change Security Keys:**
  ```php
  define('SECRET_KEY', 'generate-unique-random-string-64-chars');
  define('ENCRYPTION_KEY', 'generate-unique-random-string-32-chars-minimum');
  ```

- [ ] **Update Payment Gateway Settings:**
  ```php
  define('PAYMENT_USE_SANDBOX', false);
  // Add production API keys for all payment gateways
  ```

### 2. Security Hardening

- [ ] **Disable Error Display:**
  - Verify `ENVIRONMENT` is set to `production`
  - Error reporting already configured in config.php

- [ ] **Create `.htaccess` for Apache:**
  - File created with security headers
  - Directory protection enabled

- [ ] **Create `robots.txt`:**
  - File created
  - Admin area blocked from search engines

- [ ] **Remove Development Files:**
  - [ ] Delete `test*.php` files
  - [ ] Delete `debug*.php` files
  - [ ] Delete `phpinfo.php`
  - [ ] Delete `setup*.php` files
  - [ ] Remove `database/setup.php`

- [ ] **Secure Sensitive Directories:**
  - [ ] Set `uploads/` to 755
  - [ ] Set `logs/` to 755
  - [ ] Set `cache/` to 755
  - [ ] Set `database/` to 700 (restrict access)

### 3. Database

- [ ] **Export from Development:**
  - Use phpMyAdmin or mysqldump
  - Save as `jinkaplotterwebsite.sql`

- [ ] **Create Production Database:**
  - Create database in DirectAdmin/cPanel
  - Create database user
  - Grant all privileges

- [ ] **Import Database:**
  - Upload SQL file
  - Import via phpMyAdmin
  - Verify all tables created

- [ ] **Run Migrations:**
  - Check `database/` folder for migration scripts
  - Run any pending migrations

### 4. File Upload

- [ ] **Prepare Files:**
  - Create ZIP of entire project
  - Or use FTP client (FileZilla, WinSCP)

- [ ] **Upload to Server:**
  - Upload to `public_html/` or domain folder
  - Verify all files uploaded

- [ ] **Set File Permissions:**
  ```bash
  chmod 755 public_html/
  chmod 644 *.php
  chmod 755 uploads/ cache/ logs/
  chmod 600 includes/config.php
  ```

### 5. Email Configuration

- [ ] **Update SMTP Settings:**
  ```php
  define('SMTP_HOST', 'mail.yourdomain.com');
  define('SMTP_PORT', 587);
  define('SMTP_USERNAME', 'noreply@yourdomain.com');
  define('SMTP_PASSWORD', 'your_email_password');
  ```

- [ ] **Test Email Sending:**
  - Test contact form
  - Test order confirmation emails
  - Test password reset emails

### 6. Firebase Configuration (for SMS OTP)

- [ ] **Update Firebase Config in Admin:**
  - Login to admin panel
  - Go to Settings → Integrations
  - Add production Firebase credentials

- [ ] **Authorize Production Domain:**
  - Go to Firebase Console
  - Authentication → Settings → Authorized domains
  - Add your production domain

- [ ] **Enable Phone Authentication:**
  - Firebase Console → Authentication → Sign-in method
  - Enable Phone provider

### 7. Payment Gateway Configuration

- [ ] **AzamPay (Tanzania):**
  - Add production API credentials
  - Test with small transaction

- [ ] **M-Pesa (Kenya):**
  - Add production API credentials
  - Configure callback URL
  - Test transaction

- [ ] **Pesapal:**
  - Switch to production mode
  - Add production credentials
  - Test payment flow

- [ ] **Flutterwave:**
  - Add production keys
  - Test payment

### 8. SSL Certificate

- [ ] **Install SSL Certificate:**
  - Use DirectAdmin/cPanel SSL manager
  - Or use Let's Encrypt (free)

- [ ] **Force HTTPS:**
  - Already configured in `.htaccess`
  - Verify redirection works

- [ ] **Update Session Cookie Settings:**
  - Already set to secure in production mode

### 9. Testing

- [ ] **Homepage:**
  - [ ] Loads correctly
  - [ ] Images display
  - [ ] CSS/JS loads

- [ ] **Products:**
  - [ ] Product list displays
  - [ ] Product details work
  - [ ] Add to cart works

- [ ] **Customer Features:**
  - [ ] Registration works
  - [ ] Login works
  - [ ] OTP login works (if Firebase configured)
  - [ ] Password reset works

- [ ] **Admin Panel:**
  - [ ] Admin login works
  - [ ] Dashboard displays
  - [ ] Product management works
  - [ ] Order management works
  - [ ] Settings work

- [ ] **Checkout:**
  - [ ] Cart displays correctly
  - [ ] Checkout form works
  - [ ] Payment gateway connects
  - [ ] Order confirmation displays

- [ ] **Email Notifications:**
  - [ ] Order confirmation sent
  - [ ] Customer registration email
  - [ ] Password reset email

### 10. Performance Optimization

- [ ] **Enable Caching:**
  - Verify cache directory writable
  - Test cache functionality

- [ ] **Optimize Images:**
  - Compress large images
  - Use WebP format where possible

- [ ] **Enable Gzip Compression:**
  - Already configured in `.htaccess`

- [ ] **Minify CSS/JS:**
  - Consider minifying for production

### 11. Monitoring & Backups

- [ ] **Set Up Log Monitoring:**
  - Check `logs/` directory regularly
  - Set up log rotation

- [ ] **Database Backups:**
  - Set up automatic backups in DirectAdmin
  - Or create cron job for mysqldump

- [ ] **File Backups:**
  - Backup `uploads/` directory regularly
  - Keep off-site backup

### 12. SEO & Analytics

- [ ] **Google Analytics:**
  - Add tracking code if needed

- [ ] **Google Search Console:**
  - Submit sitemap.xml
  - Verify domain ownership

- [ ] **Meta Tags:**
  - Verify page titles
  - Check meta descriptions

### 13. Legal & Compliance

- [ ] **Privacy Policy:**
  - Add privacy policy page
  - Link in footer

- [ ] **Terms & Conditions:**
  - Add terms page
  - Link during checkout

- [ ] **GDPR Compliance:**
  - Cookie consent banner (if serving EU)
  - Data protection measures

## Post-Deployment

### Immediate Checks (First 24 Hours)

- [ ] Monitor error logs
- [ ] Check payment gateway logs
- [ ] Verify email delivery
- [ ] Test all critical features
- [ ] Monitor server resources

### Week 1 Checks

- [ ] Review analytics
- [ ] Check for broken links
- [ ] Monitor user feedback
- [ ] Verify backups running
- [ ] Check server load

### Monthly Maintenance

- [ ] Review security logs
- [ ] Update dependencies
- [ ] Test backup restoration
- [ ] Review performance metrics
- [ ] Clean up old logs/cache

## Emergency Contacts

**Hosting Support:**
- DirectAdmin/cPanel support

**Payment Gateway Support:**
- AzamPay: [Support Contact]
- M-Pesa: [Support Contact]
- Pesapal: [Support Contact]

**Firebase Support:**
- Firebase Console: https://console.firebase.google.com

## Rollback Plan

If deployment fails:

1. **Database Rollback:**
   - Restore from backup SQL file
   - Re-import previous database

2. **File Rollback:**
   - Restore from backup ZIP
   - Or use version control (git)

3. **Verify Configuration:**
   - Check config.php settings
   - Verify database connection

4. **Test Core Functions:**
   - Homepage
   - Login
   - Checkout

## Security Incident Response

If security breach suspected:

1. **Immediate Actions:**
   - Change all passwords
   - Change SECRET_KEY and ENCRYPTION_KEY
   - Review access logs
   - Disable compromised accounts

2. **Investigation:**
   - Check `logs/` directory
   - Review database for suspicious activity
   - Check file modification dates

3. **Recovery:**
   - Restore from clean backup
   - Update all credentials
   - Patch vulnerabilities

## Support Resources

- **Documentation:** Check `/docs` folder
- **Firebase Setup:** `FIREBASE-DOMAIN-SETUP.md`
- **Quick Paste Guide:** `FIREBASE-QUICK-PASTE-GUIDE.md`
- **Database Migrations:** `database/` folder

---

## Quick Production Config Summary

**Critical Changes Required:**

```php
// includes/config.php
define('ENVIRONMENT', 'production');
define('DEBUG_MODE', false);
define('SITE_URL', 'https://yourdomain.com');
define('DB_NAME', 'production_db');
define('DB_USER', 'production_user');
define('DB_PASS', 'strong_password');
define('SECRET_KEY', 'change_this_64_char_random_string');
define('ENCRYPTION_KEY', 'change_this_32_char_random_string');
define('PAYMENT_USE_SANDBOX', false);
```

**Commands to Run:**

```bash
# Set permissions
chmod 755 public_html/
chmod 644 *.php
chmod 755 uploads/ cache/ logs/
chmod 600 includes/config.php

# Import database
mysql -u username -p database_name < jinkaplotterwebsite.sql

# Clear cache
rm -rf cache/*
```

---

**Deployment Date:** _________________

**Deployed By:** _________________

**Production URL:** _________________

**Notes:**
_________________________________________________________________
_________________________________________________________________
