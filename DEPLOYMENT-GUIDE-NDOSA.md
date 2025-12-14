# ================================================================
# NDOSA.STORE DEPLOYMENT GUIDE
# Domain: ndosa.store
# Created: December 15, 2025
# ================================================================

## DEPLOYMENT CHECKLIST

### 1. PRE-DEPLOYMENT PREPARATION

#### A. Local Preparation
- [ ] Test all features locally
- [ ] Update all configuration files
- [ ] Generate secure keys for production
- [ ] Create database backup
- [ ] Document any custom changes

#### B. Server Requirements
- [ ] PHP 8.0 or higher
- [ ] MySQL 5.7 or higher / MariaDB 10.3+
- [ ] Apache/Nginx web server
- [ ] SSL certificate installed
- [ ] Composer (optional, for dependencies)
- [ ] Git (for version control)

### 2. SERVER SETUP (DirectAdmin)

#### A. Create Database
```bash
# Using DirectAdmin MySQL Management or phpMyAdmin
# Import: database/complete-deployment.sql
```

**Or via command line:**
```bash
mysql -u root -p < database/complete-deployment.sql
```

#### B. Create Database User
```sql
CREATE USER 'ndosa_user'@'localhost' IDENTIFIED BY 'STRONG_PASSWORD_HERE';
GRANT ALL PRIVILEGES ON ndosa_store.* TO 'ndosa_user'@'localhost';
FLUSH PRIVILEGES;
```

#### C. Configure Domain
1. Log into DirectAdmin
2. Go to "Domain Setup"
3. Add domain: ndosa.store
4. Point DocumentRoot to: public_html/
5. Enable SSL certificate (Let's Encrypt)
6. Configure PHP version (8.0+)

### 3. FILE DEPLOYMENT

#### Option A: Direct Upload (FTP/SFTP)
```bash
# Upload all files to: /home/ndosa/public_html/
# Exclude:
- .git/
- node_modules/
- .env (use .env.production instead)
- database/ (optional, for reference only)
```

#### Option B: Git Deployment
```bash
# On server (SSH)
cd /home/ndosa/public_html
git init
git remote add origin YOUR_GITHUB_REPO_URL
git pull origin main

# Copy production environment
cp .env.production .env
```

### 4. CONFIGURATION

#### A. Environment File
```bash
cd /home/ndosa/public_html
cp .env.production .env
nano .env
```

**Update these critical values:**
```env
# Database
DB_NAME=ndosa_store
DB_USER=ndosa_user
DB_PASS=your_actual_database_password

# Security Keys (Generate with: openssl rand -hex 32)
SECRET_KEY=your_64_char_random_string
ENCRYPTION_KEY=your_32_char_random_string

# Site URL
SITE_URL=https://ndosa.store

# Email Configuration
MAIL_HOST=smtp.ndosa.store
MAIL_USERNAME=noreply@ndosa.store
MAIL_PASSWORD=your_email_password

# Payment Gateways
FLUTTERWAVE_PUBLIC_KEY=your_key
FLUTTERWAVE_SECRET_KEY=your_secret
```

#### B. File Permissions
```bash
# Set proper permissions
chmod 755 /home/ndosa/public_html
chmod 644 /home/ndosa/public_html/.env
chmod -R 755 /home/ndosa/public_html/includes
chmod -R 755 /home/ndosa/public_html/admin
chmod -R 777 /home/ndosa/public_html/uploads
```

#### C. Create Required Directories
```bash
mkdir -p /home/ndosa/public_html/uploads/{products,customers,temp}
mkdir -p /home/ndosa/logs
mkdir -p /home/ndosa/backups
chmod -R 777 /home/ndosa/public_html/uploads
```

### 5. APACHE CONFIGURATION

#### .htaccess (Root Directory)
```apache
# Already included in project, verify it exists:
# /home/ndosa/public_html/.htaccess

<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /

    # Force HTTPS
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

    # Remove www
    RewriteCond %{HTTP_HOST} ^www\.(.*)$ [NC]
    RewriteRule ^(.*)$ https://%1/$1 [R=301,L]

    # Block access to sensitive files
    RewriteRule ^\.env$ - [F,L]
    RewriteRule ^includes/ - [F,L]
    RewriteRule ^database/ - [F,L]
    RewriteRule ^\.git/ - [F,L]

    # PHP security
    php_flag display_errors Off
    php_value upload_max_filesize 10M
    php_value post_max_size 10M
    php_value max_execution_time 300
</IfModule>

# Security Headers
<IfModule mod_headers.c>
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-XSS-Protection "1; mode=block"
    Header set Referrer-Policy "strict-origin-when-cross-origin"
    Header set Permissions-Policy "geolocation=(), microphone=(), camera=()"
</IfModule>

# Deny access to sensitive files
<FilesMatch "^\.">
    Order allow,deny
    Deny from all
</FilesMatch>
```

### 6. DATABASE INITIALIZATION

#### A. Import Complete Database
```bash
cd /home/ndosa/public_html
mysql -u ndosa_user -p ndosa_store < database/complete-deployment.sql
```

#### B. Verify Tables
```sql
USE ndosa_store;
SHOW TABLES;
# Should show 30+ tables

SELECT * FROM settings WHERE setting_key = 'site_name';
# Should return: Ndosa Store
```

#### C. Default Admin Credentials
```
URL: https://ndosa.store/admin/
Username: admin
Password: Admin@2025!

⚠️ CHANGE IMMEDIATELY AFTER FIRST LOGIN!
```

### 7. POST-DEPLOYMENT TESTS

#### A. Basic Functionality Tests
- [ ] Homepage loads correctly
- [ ] Products page displays items
- [ ] Shopping cart works
- [ ] Checkout process completes
- [ ] Customer registration works
- [ ] Admin panel accessible
- [ ] Email sending works
- [ ] Payment gateway connects

#### B. Security Tests
```bash
# Test HTTPS redirect
curl -I http://ndosa.store
# Should redirect to https://

# Verify .env is blocked
curl https://ndosa.store/.env
# Should return 403 Forbidden

# Check database connection
# Visit: https://ndosa.store/check-db.php
# Should show: Database connection successful
```

#### C. Performance Tests
- [ ] Page load time < 3 seconds
- [ ] Images optimized and loading
- [ ] CSS/JS minified
- [ ] Gzip compression enabled
- [ ] Browser caching configured

### 8. PAYMENT GATEWAY SETUP

#### A. Flutterwave (Kenya & Tanzania)
1. Log into Flutterwave Dashboard
2. Get API keys from Settings > API
3. Add webhook URL: https://ndosa.store/payment-callback.php
4. Update .env with live keys
5. Test with small transaction

#### B. AzamPay (Tanzania)
1. Register at AzamPay merchant portal
2. Get Client ID and Secret
3. Configure callback: https://ndosa.store/azampay-callback.php
4. Update .env configuration
5. Test mobile money payment

#### C. M-Pesa (Kenya)
1. Register M-Pesa Paybill/Till
2. Get credentials from Safaricom
3. Configure callback: https://ndosa.store/mpesa-callback.php
4. Update .env with production keys
5. Test with KES 1 transaction

### 9. EMAIL CONFIGURATION

#### A. Setup Email Accounts
```
Create these email accounts in DirectAdmin:
- info@ndosa.store (general inquiries)
- support@ndosa.store (customer support)
- noreply@ndosa.store (automated emails)
- admin@ndosa.store (admin notifications)
```

#### B. SMTP Configuration
Update .env:
```env
MAIL_HOST=mail.ndosa.store
MAIL_PORT=587
MAIL_USERNAME=noreply@ndosa.store
MAIL_PASSWORD=your_email_password
MAIL_ENCRYPTION=tls
```

#### C. Test Email Sending
```bash
# Visit: https://ndosa.store/test-email.php
# Should send test email successfully
```

### 10. BACKUP STRATEGY

#### A. Automated Daily Backup Script
Create: `/home/ndosa/backup.sh`
```bash
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/home/ndosa/backups"
DB_NAME="ndosa_store"
DB_USER="ndosa_user"
DB_PASS="your_db_password"

# Backup database
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME | gzip > $BACKUP_DIR/db_$DATE.sql.gz

# Backup files
tar -czf $BACKUP_DIR/files_$DATE.tar.gz /home/ndosa/public_html/uploads

# Delete backups older than 30 days
find $BACKUP_DIR -name "*.gz" -mtime +30 -delete

echo "Backup completed: $DATE"
```

#### B. Setup Cron Job
```bash
crontab -e
# Add line:
0 2 * * * /home/ndosa/backup.sh >> /home/ndosa/logs/backup.log 2>&1
```

### 11. MONITORING & MAINTENANCE

#### A. Setup Monitoring
- [ ] Configure uptime monitoring (UptimeRobot, Pingdom)
- [ ] Setup error logging
- [ ] Configure Google Analytics
- [ ] Install security scanner (Wordfence, Sucuri)

#### B. Regular Maintenance Tasks
**Daily:**
- Check error logs
- Monitor server resources
- Review failed transactions

**Weekly:**
- Update stock levels
- Review customer inquiries
- Check backup integrity
- Update content

**Monthly:**
- Security updates
- Performance optimization
- Database cleanup
- Backup verification

### 12. SECURITY HARDENING

#### A. Additional Security Steps
```bash
# Disable directory listing
echo "Options -Indexes" >> .htaccess

# Protect admin directory
# Create: /admin/.htaccess
echo "AuthType Basic
AuthName \"Admin Area\"
AuthUserFile /home/ndosa/.htpasswd
Require valid-user" > /home/ndosa/public_html/admin/.htaccess

# Create password file
htpasswd -c /home/ndosa/.htpasswd adminuser
```

#### B. SSL/TLS Configuration
- [ ] Force HTTPS (already in .htaccess)
- [ ] Configure HSTS header
- [ ] Setup auto-renewal for Let's Encrypt
- [ ] Test SSL configuration (SSLLabs.com)

### 13. FINAL VERIFICATION

#### Complete Site Audit
- [ ] All links working
- [ ] Forms submitting correctly
- [ ] Payments processing
- [ ] Emails sending
- [ ] Mobile responsive
- [ ] Cross-browser compatible
- [ ] SEO meta tags present
- [ ] Sitemap accessible
- [ ] robots.txt configured
- [ ] 404 page working

### 14. GO LIVE CHECKLIST

- [ ] Backup current site
- [ ] Point DNS to new server
- [ ] Test all functionality
- [ ] Monitor error logs
- [ ] Send test orders
- [ ] Verify email delivery
- [ ] Test payment gateways
- [ ] Check analytics tracking
- [ ] Update Google Search Console
- [ ] Announce launch

## SUPPORT & TROUBLESHOOTING

### Common Issues

**Database Connection Error:**
```
- Verify DB credentials in .env
- Check database exists: SHOW DATABASES;
- Ensure user has permissions: SHOW GRANTS;
```

**Payment Gateway Errors:**
```
- Verify API keys in .env
- Check webhook URLs are accessible
- Review payment gateway logs
- Test with sandbox/test mode first
```

**Email Not Sending:**
```
- Verify SMTP credentials
- Check firewall/port 587 open
- Test with telnet: telnet mail.ndosa.store 587
- Review email logs
```

**Permission Errors:**
```
- Check uploads directory: chmod -R 777 uploads/
- Verify .env readable: chmod 644 .env
- Check PHP user ownership
```

### Emergency Contacts
- Hosting Support: [Your hosting provider]
- Developer: [Your contact]
- Payment Gateway Support: [Gateway support]

## MAINTENANCE MODE

To enable maintenance mode:
```php
# In .env:
MAINTENANCE_MODE=true
```

To disable:
```php
MAINTENANCE_MODE=false
```

---

## DEPLOYMENT COMPLETION

Once deployed successfully:
1. Change all default passwords
2. Remove test files (check-db.php, test-email.php)
3. Enable production error logging
4. Configure monitoring
5. Document custom configurations
6. Create admin user guide
7. Train staff on admin panel

**Deployment Date:** _______________
**Deployed By:** _______________
**Verified By:** _______________

---

**For technical support or questions:**
- Documentation: See project README.md files
- Database Schema: database/complete-deployment.sql
- Configuration: .env file

**Next Steps:**
1. Add products via admin panel
2. Configure payment gateways
3. Test complete checkout flow
4. Launch marketing campaigns
5. Monitor and optimize
