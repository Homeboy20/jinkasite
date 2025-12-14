# ================================================================
# QUICK DEPLOYMENT STEPS FOR NDOSA.STORE
# ================================================================

## 1. PREPARE DATABASE (5 minutes)

### Create Database & User
```bash
# Login to DirectAdmin → MySQL Management → Create Database
Database Name: ndosa_store
Username: ndosa_user
Password: [Generate strong password]
```

### Import Database
```bash
# Via phpMyAdmin or command line:
mysql -u ndosa_user -p ndosa_store < database/complete-deployment.sql
```

## 2. UPLOAD FILES (10 minutes)

### Via FTP/SFTP
```
Upload to: /home/ndosa/public_html/
Include: All files EXCEPT .git, node_modules
```

### Via Git (Recommended)
```bash
cd /home/ndosa/public_html
git clone YOUR_REPO_URL .
```

## 3. CONFIGURE ENVIRONMENT (5 minutes)

```bash
# Copy production config
cp .env.production .env

# Edit configuration
nano .env

# Update REQUIRED fields:
DB_NAME=ndosa_store
DB_USER=ndosa_user
DB_PASS=your_database_password
SECRET_KEY=generate_random_64_chars
ENCRYPTION_KEY=generate_random_32_chars
SITE_URL=https://ndosa.store
```

### Generate Secure Keys
```bash
# SECRET_KEY (64 chars)
openssl rand -hex 32

# ENCRYPTION_KEY (32 chars)
openssl rand -hex 16
```

## 4. SET PERMISSIONS (2 minutes)

```bash
chmod 644 .env
chmod -R 777 uploads
mkdir -p uploads/products uploads/customers uploads/temp
```

## 5. VERIFY INSTALLATION (5 minutes)

### Test Homepage
```
https://ndosa.store
```

### Test Admin Panel
```
URL: https://ndosa.store/admin/
Username: admin
Password: Admin@2025!

⚠️ CHANGE PASSWORD IMMEDIATELY!
```

### Test Database
```
https://ndosa.store/check-db.php
Should show: ✓ Database connection successful
```

## 6. POST-DEPLOYMENT (10 minutes)

### Security
- [ ] Change admin password
- [ ] Remove test files (check-db.php, test-email.php)
- [ ] Verify .htaccess blocks .env access
- [ ] Test HTTPS redirect

### Configuration
- [ ] Update site settings in Admin → Settings
- [ ] Configure payment gateways
- [ ] Setup SMTP email
- [ ] Test email sending

### Content
- [ ] Add products
- [ ] Upload images
- [ ] Configure shipping zones
- [ ] Set tax rates

## 7. PAYMENT GATEWAYS

### Flutterwave
```env
FLUTTERWAVE_PUBLIC_KEY=your_public_key
FLUTTERWAVE_SECRET_KEY=your_secret_key
FLUTTERWAVE_ENVIRONMENT=live
```

### AzamPay (Tanzania)
```env
AZAMPAY_CLIENT_ID=your_client_id
AZAMPAY_CLIENT_SECRET=your_secret
AZAMPAY_ENVIRONMENT=live
```

### M-Pesa (Kenya)
```env
MPESA_CONSUMER_KEY=your_key
MPESA_CONSUMER_SECRET=your_secret
MPESA_SHORTCODE=your_shortcode
MPESA_ENVIRONMENT=live
```

## 8. BACKUP SETUP

### Create Backup Script
```bash
nano /home/ndosa/backup.sh
```

```bash
#!/bin/bash
DATE=$(date +%Y%m%d)
mysqldump -u ndosa_user -pYOUR_PASSWORD ndosa_store | gzip > /home/ndosa/backups/db_$DATE.sql.gz
tar -czf /home/ndosa/backups/files_$DATE.tar.gz /home/ndosa/public_html/uploads
find /home/ndosa/backups -mtime +30 -delete
```

```bash
chmod +x /home/ndosa/backup.sh
```

### Setup Daily Cron
```bash
crontab -e
0 2 * * * /home/ndosa/backup.sh
```

## TROUBLESHOOTING

### "Database connection failed"
```
- Check DB credentials in .env
- Verify database exists
- Test connection: mysql -u ndosa_user -p
```

### "Permission denied" on uploads
```bash
chmod -R 777 /home/ndosa/public_html/uploads
```

### Page not loading
```
- Check .htaccess exists
- Verify PHP version (8.0+)
- Check error logs: tail -f /home/ndosa/logs/error.log
```

### HTTPS not working
```
- Install SSL certificate in DirectAdmin
- Verify .htaccess HTTPS redirect
- Clear browser cache
```

## CREDENTIALS SUMMARY

### Database
```
Host: localhost
Database: ndosa_store
Username: ndosa_user
Password: [Set during creation]
```

### Admin Panel
```
URL: https://ndosa.store/admin/
Username: admin
Password: Admin@2025! (CHANGE THIS!)
```

### Email Accounts (Create in DirectAdmin)
```
info@ndosa.store
support@ndosa.store
noreply@ndosa.store
admin@ndosa.store
```

## COMPLETION CHECKLIST

- [ ] Database created and imported
- [ ] Files uploaded
- [ ] .env configured
- [ ] Permissions set
- [ ] Admin password changed
- [ ] HTTPS working
- [ ] Payments configured
- [ ] Emails sending
- [ ] Products added
- [ ] Backup automated

## SUPPORT

**Need help?**
- Check: DEPLOYMENT-GUIDE-NDOSA.md (detailed guide)
- Database: database/complete-deployment.sql
- Config: .env.production (template)

---

**Estimated Total Time: 30-40 minutes**

**Ready to launch? Follow steps 1-6 in order!**
