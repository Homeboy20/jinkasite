# POST-UPLOAD CONFIGURATION GUIDE
# Step-by-Step Setup After Uploading Files to ndosa.store

## STEP 1: Upload All Files ✅

**Files to upload (via DirectAdmin File Manager or FTP):**
```
📁 /home/ndosa/public_html/
├── .htaccess
├── index.php
├── admin/
│   ├── .htaccess
│   ├── robots.txt
│   └── index.php
├── css/
│   └── style.css
├── includes/
│   ├── EmailHandler.php
│   └── email_templates/
│       ├── order_confirmation.php
│       ├── admin_new_order.php
│       ├── order_status_update.php
│       └── admin_new_inquiry.php
└── (all other files from GitHub)
```

---

## STEP 2: Configure Database

### A. Create Database via DirectAdmin

1. **Login to DirectAdmin**
2. **Go to:** MySQL Management
3. **Click:** Create new database

**Enter:**
```
Database Name: ndosa_store
Username: ndosa_user
Password: [Generate strong password - save it!]
```

**Example strong password:**
```
egT4kbWgcNhTW24TYeV5
```

4. **Click:** Create
5. **Save your password** - you'll need it for .env!

### B. Import Database Schema

**Via phpMyAdmin:**
1. Open phpMyAdmin in DirectAdmin
2. Select database: `ndosa_store`
3. Click **Import** tab
4. Choose file: `database/complete-deployment.sql`
5. Click **Go**
6. Wait for success message

**Via SSH:**
```bash
cd /home/ndosa/public_html
mysql -u ndosa_user -p ndosa_store < database/complete-deployment.sql
# Enter your database password when prompted
```

---

## STEP 3: Configure .env File

### A. Create .env from Template

**Via DirectAdmin File Manager:**
1. Navigate to `/home/ndosa/public_html/`
2. Copy `.env.production` → rename to `.env`
3. Right-click `.env` → Edit

**Via SSH:**
```bash
cd /home/ndosa/public_html
cp .env.production .env
nano .env
```

### B. Update Required Settings

**CRITICAL - Must change these:**

```bash
# 1. DATABASE (use password from Step 2)
DB_HOST=localhost
DB_NAME=ndosa_store
DB_USER=ndosa_user
DB_PASS=egT4kbWgcNhTW24TYeV5  ← YOUR DATABASE PASSWORD

# 2. SECURITY KEYS (generate new ones)
# Run this on your local machine or SSH:
openssl rand -hex 32
```

**Generate keys and paste:**
```env
SECRET_KEY=a1b2c3d4e5f6...  ← Paste output from openssl rand -hex 32
ENCRYPTION_KEY=f6e5d4c3b2a1...  ← Paste output from openssl rand -hex 16
```

**Example:**
```bash
# On your computer or SSH terminal:
$ openssl rand -hex 32
5f8d2e9c4b7a6e3d1f0a8b9c2d3e4f5a6b7c8d9e0f1a2b3c4d5e6f7a8b9c0d1e

# Copy this ^ and paste as SECRET_KEY
```

### C. Optional Settings (can update later)

**Email (for order confirmations):**
```env
MAIL_HOST=mail.ndosa.store
MAIL_PORT=587
MAIL_USERNAME=noreply@ndosa.store
MAIL_PASSWORD=your_email_password
MAIL_ENCRYPTION=tls
```

**Payment Gateways (add when ready):**
```env
FLUTTERWAVE_PUBLIC_KEY=FLWPUBK-...
FLUTTERWAVE_SECRET_KEY=FLWSECK-...
```

### D. Save .env File

**DirectAdmin:** Click "Save"  
**SSH:** Press `Ctrl+X` → `Y` → `Enter`

---

## STEP 4: Set File Permissions

**Via DirectAdmin Terminal or SSH:**

```bash
cd /home/ndosa/public_html

# Secure .env file
chmod 644 .env

# Make uploads writable
chmod -R 777 uploads
mkdir -p uploads/products uploads/customers uploads/temp

# Secure directories
chmod 755 admin includes css js

# Secure files
chmod 644 index.php admin/*.php includes/*.php
```

**Quick permission script:**
```bash
cd /home/ndosa/public_html
find . -type d -exec chmod 755 {} \;
find . -type f -exec chmod 644 {} \;
chmod 777 uploads uploads/* -R
chmod 644 .env .htaccess admin/.htaccess
```

---

## STEP 5: Clear All Caches

### A. Clear Server Cache

**Create:** `/home/ndosa/public_html/clear-cache.php`

```php
<?php
// Clear PHP opcache
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "✓ OpCache cleared<br>";
}

// Clear realpath cache
clearstatcache(true);
echo "✓ Stat cache cleared<br>";

echo "<br>✓ All caches cleared!<br>";
echo "<strong style='color:red;'>DELETE THIS FILE NOW!</strong>";
?>
```

**Visit:** `https://ndosa.store/clear-cache.php`  
**Then delete it immediately!**

### B. Clear Browser Cache

- **Hard refresh:** `Ctrl + Shift + R`
- **Or test in incognito:** `Ctrl + Shift + N`

### C. Clear Cloudflare Cache (if using)

1. Login to Cloudflare
2. Select `ndosa.store`
3. **Caching** → **Configuration**
4. **Purge Everything**

---

## STEP 6: Test Installation

### ✅ Test #1: Homepage
**Visit:** `https://ndosa.store`

**Check:**
- ✅ Page loads (no errors)
- ✅ CTA section has orange gradient background
- ✅ "Exclusive Offer" badge visible
- ✅ All images loading
- ✅ Navigation menu works

### ✅ Test #2: Database Connection
**Visit:** `https://ndosa.store/check-db.php` (if exists)

**Should show:**
```
✓ Database connection successful
✓ Connected to: ndosa_store
```

**Delete check-db.php after testing!**

### ✅ Test #3: Admin Panel
**Visit:** `https://ndosa.store/admin/` or `https://admin.ndosa.store/`

**Login with default credentials:**
```
Username: admin
Password: Admin@2025!
```

**⚠️ CHANGE PASSWORD IMMEDIATELY!**

### ✅ Test #4: Admin Subdomain
**Visit:** `https://admin.ndosa.store/robots.txt`

**Should show:**
```
User-agent: *
Disallow: /
```

### ✅ Test #5: HTTPS Redirect
**Visit:** `http://ndosa.store` (without s)

**Should:** Automatically redirect to `https://ndosa.store`

### ✅ Test #6: Mobile Responsive
- Press `F12` → Toggle device toolbar
- Check CTA section on mobile
- All content should be visible and readable

---

## STEP 7: Security Steps

### A. Change Admin Password

1. Login to admin: `https://ndosa.store/admin/`
2. Go to **Settings** or **Profile**
3. Change password from `Admin@2025!` to strong password
4. **Save**

### B. Remove Test Files

**Delete these files if they exist:**
```bash
cd /home/ndosa/public_html
rm -f check-db.php
rm -f test-email.php
rm -f clear-cache.php
rm -f setup-welcome.php
rm -f phpinfo.php
rm -f test-*.php
```

**Via DirectAdmin:** File Manager → Select files → Delete

### C. Verify .env is Protected

**Visit:** `https://ndosa.store/.env`

**Should show:** 403 Forbidden or 404 Not Found  
**Should NOT show:** File contents

---

## STEP 8: Configure Email (Optional - can do later)

### A. Create Email Accounts in DirectAdmin

1. **Email Accounts** → **Create Mail Account**

**Create these:**
```
noreply@ndosa.store
info@ndosa.store
support@ndosa.store
admin@ndosa.store
```

### B. Update .env with SMTP Details

```bash
nano /home/ndosa/public_html/.env
```

**Update:**
```env
MAIL_HOST=mail.ndosa.store
MAIL_PORT=587
MAIL_USERNAME=noreply@ndosa.store
MAIL_PASSWORD=password_for_noreply_account
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@ndosa.store
```

### C. Test Email

**Create:** `test-email-simple.php`
```php
<?php
require_once 'includes/config.php';

$to = 'your-email@gmail.com';  // Your email
$subject = 'Test Email from Ndosa Store';
$message = 'If you receive this, email is working!';

if (mail($to, $subject, $message)) {
    echo "✓ Email sent successfully to $to";
} else {
    echo "✗ Email failed to send";
}

echo "<br><strong>Delete this file now!</strong>";
?>
```

**Visit:** `https://ndosa.store/test-email-simple.php`  
**Check your email**  
**Delete the file**

---

## STEP 9: Payment Gateway Setup (Optional - can do later)

### A. Flutterwave (Kenya & Tanzania)

1. Login to Flutterwave Dashboard
2. Go to **Settings** → **API Keys**
3. Copy **Public Key** and **Secret Key** (LIVE mode)

**Update .env:**
```env
FLUTTERWAVE_PUBLIC_KEY=FLWPUBK-xxxxxxxx
FLUTTERWAVE_SECRET_KEY=FLWSECK-xxxxxxxx
FLUTTERWAVE_ENCRYPTION_KEY=FLWSECK3-xxxxxxxx
FLUTTERWAVE_ENVIRONMENT=live
```

### B. AzamPay (Tanzania)

1. Get credentials from AzamPay
2. Update .env:

```env
AZAMPAY_CLIENT_ID=your_client_id
AZAMPAY_CLIENT_SECRET=your_client_secret
AZAMPAY_ENVIRONMENT=live
```

### C. M-Pesa (Kenya)

1. Get Safaricom M-Pesa API credentials
2. Update .env:

```env
MPESA_CONSUMER_KEY=your_consumer_key
MPESA_CONSUMER_SECRET=your_consumer_secret
MPESA_SHORTCODE=174379  # Your shortcode
MPESA_PASSKEY=your_passkey
MPESA_ENVIRONMENT=live
```

---

## STEP 10: Setup Automated Backups

### A. Create Backup Script

**Create:** `/home/ndosa/backup.sh`

```bash
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/home/ndosa/backups"
DB_NAME="ndosa_store"
DB_USER="ndosa_user"
DB_PASS="your_database_password"  # Your actual password

# Create backup directory
mkdir -p $BACKUP_DIR

# Backup database
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME | gzip > $BACKUP_DIR/db_$DATE.sql.gz

# Backup uploads folder
tar -czf $BACKUP_DIR/uploads_$DATE.tar.gz /home/ndosa/public_html/uploads

# Delete backups older than 30 days
find $BACKUP_DIR -name "*.gz" -mtime +30 -delete

echo "Backup completed: $DATE"
```

### B. Make Executable

```bash
chmod +x /home/ndosa/backup.sh
```

### C. Setup Cron Job

**In DirectAdmin:** Cron Jobs → Add Cron Job

```
Minute: 0
Hour: 2
Day: *
Month: *
Weekday: *
Command: /home/ndosa/backup.sh
```

**Or via SSH:**
```bash
crontab -e
```

**Add:**
```
0 2 * * * /home/ndosa/backup.sh
```

This runs backup daily at 2 AM.

---

## STEP 11: Final Verification

### Run This Checklist:

```
✅ Homepage loads at https://ndosa.store
✅ CTA section styled correctly with orange gradient
✅ Admin login works at https://ndosa.store/admin/
✅ Admin password changed from default
✅ Database connection successful
✅ Email sending works (if configured)
✅ HTTPS redirect working (http → https)
✅ .env file is protected (403/404 error)
✅ Test files deleted (check-db.php, etc.)
✅ File permissions correct (uploads writable)
✅ Admin subdomain not indexed (robots.txt blocks)
✅ Backup script scheduled
```

---

## TROUBLESHOOTING

### Issue: "Database connection failed"

**Check:**
```bash
# Verify .env has correct password
cat /home/ndosa/public_html/.env | grep DB_PASS

# Test database login
mysql -u ndosa_user -p ndosa_store
# Enter password, should connect
```

**Fix:**
- Update `DB_PASS` in `.env` with correct password
- Verify database name is `ndosa_store`
- Verify user `ndosa_user` exists

### Issue: "Page not found" or 404 errors

**Check:**
```bash
# Verify .htaccess has correct RewriteBase
cat /home/ndosa/public_html/.htaccess | grep RewriteBase
# Should show: RewriteBase /
```

**Fix:**
- Re-upload `.htaccess` file
- Ensure mod_rewrite is enabled
- Check file permissions

### Issue: CSS not loading/old style

**Fix:**
```bash
# Check file date
ls -lh /home/ndosa/public_html/css/style.css

# Clear cache
# Visit clear-cache.php or run:
php -r "opcache_reset();"

# Hard refresh browser
Ctrl + Shift + R
```

### Issue: Email not sending

**Check:**
```bash
# Test mail function
php -r "var_dump(mail('test@example.com', 'Test', 'Test'));"
```

**Fix:**
- Verify SMTP settings in `.env`
- Create email account in DirectAdmin
- Check spam folder
- Contact hosting support about SMTP

---

## QUICK CONFIGURATION SCRIPT

**Create:** `configure.sh`

```bash
#!/bin/bash
echo "=== Ndosa Store Configuration ==="
echo ""

# Check if we're in the right directory
if [ ! -f "index.php" ]; then
    echo "Error: Run this from /home/ndosa/public_html/"
    exit 1
fi

# Check .env exists
if [ ! -f ".env" ]; then
    echo "Creating .env from template..."
    cp .env.production .env
    echo "✓ .env created"
else
    echo "✓ .env already exists"
fi

# Set permissions
echo "Setting permissions..."
chmod 644 .env
chmod -R 777 uploads
find . -type d -exec chmod 755 {} \;
find . -type f -exec chmod 644 {} \;
echo "✓ Permissions set"

# Check database connection
echo "Checking database..."
php -r "
require 'includes/config.php';
\$db = Database::getInstance()->getConnection();
if (\$db) {
    echo '✓ Database connection successful\n';
} else {
    echo '✗ Database connection failed\n';
}
"

echo ""
echo "=== Configuration Complete ==="
echo "Next steps:"
echo "1. Edit .env and update DB_PASS, SECRET_KEY, ENCRYPTION_KEY"
echo "2. Visit https://ndosa.store to test"
echo "3. Login to admin and change password"
echo ""
```

**Run:**
```bash
chmod +x configure.sh
./configure.sh
```

---

## SUPPORT CONTACTS

**Need Help?**

1. **Check logs:**
   ```bash
   tail -f /var/log/httpd/error_log
   ```

2. **Verify configuration:**
   ```bash
   cd /home/ndosa/public_html
   ./configure.sh
   ```

3. **Contact hosting support** for:
   - Database access issues
   - SMTP/email setup
   - SSL certificate problems
   - Apache configuration

---

## SUMMARY OF WHAT TO DO

1. ✅ **Upload files** from GitHub to `/home/ndosa/public_html/`
2. ✅ **Create database** `ndosa_store` via DirectAdmin
3. ✅ **Import** `database/complete-deployment.sql`
4. ✅ **Create .env** from `.env.production`
5. ✅ **Edit .env** - Add DB password, generate SECRET_KEY & ENCRYPTION_KEY
6. ✅ **Set permissions** - chmod 644 .env, chmod 777 uploads
7. ✅ **Clear cache** - Visit clear-cache.php
8. ✅ **Test** - Visit https://ndosa.store
9. ✅ **Login admin** - Change default password
10. ✅ **Delete test files** - Remove check-db.php, clear-cache.php

**That's it! Your site is live! 🚀**
