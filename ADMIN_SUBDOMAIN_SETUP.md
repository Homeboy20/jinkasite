# Admin Subdomain Setup Guide - admin.ndosa.store

## Overview
This guide will help you set up `admin.ndosa.store` as a subdomain that:
- Points to the `/admin` directory
- Is NOT indexed by search engines
- Has enhanced security measures

---

## 1. DNS Configuration (5 minutes)

### Add Subdomain DNS Record
In your domain registrar or DNS provider (e.g., Cloudflare, GoDaddy):

```
Type: A Record
Name: admin
Value: [Your server IP address]
TTL: Auto or 3600
```

**Example:**
```
A    admin    160.119.252.xxx    Auto
```

---

## 2. DirectAdmin Subdomain Setup (5 minutes)

### Create Subdomain
1. Login to DirectAdmin
2. Navigate to: **Account Manager → Subdomain Management**
3. Click **Create Subdomain**
4. Enter subdomain: `admin`
5. Set Document Root: `/home/ndosa/public_html/admin`
6. Click **Create**

### Verify Creation
- Subdomain created: `admin.ndosa.store`
- Points to: `/home/ndosa/public_html/admin/`

---

## 3. SSL Certificate Installation (5 minutes)

### Install Let's Encrypt Certificate
1. In DirectAdmin: **SSL Certificates → Let's Encrypt**
2. Select domain: `admin.ndosa.store`
3. Click **Install**
4. Certificate will auto-renew every 90 days

### Verify HTTPS
```bash
curl -I https://admin.ndosa.store
# Should show: HTTP/2 200
```

---

## 4. Search Engine Blocking (Already Configured)

### Files Created:
✅ `/admin/robots.txt` - Blocks all search engine crawlers
✅ `/admin/.htaccess` - Adds X-Robots-Tag header
✅ Meta tags in admin pages - Prevent indexing

### robots.txt Content:
```
User-agent: *
Disallow: /
```

### .htaccess Header:
```apache
Header set X-Robots-Tag "noindex, nofollow, noarchive, nosnippet"
```

---

## 5. Security Enhancements

### Option A: IP Whitelist (Recommended)
Add to `/admin/.htaccess`:
```apache
# Allow only specific IP addresses
Order Deny,Allow
Deny from all
Allow from 197.156.xxx.xxx  # Your office IP
Allow from 41.90.xxx.xxx    # Your home IP
```

### Option B: HTTP Basic Authentication
```bash
# Create password file
htpasswd -c /home/ndosa/.htpasswd admin

# Add to /admin/.htaccess (already has placeholder)
AuthType Basic
AuthName "Admin Access Required"
AuthUserFile /home/ndosa/.htpasswd
Require valid-user
```

### Option C: Two-Factor Authentication
Consider implementing 2FA in the admin login system using:
- Google Authenticator
- SMS OTP
- Email verification codes

---

## 6. Admin Panel Meta Tags (Add to all admin pages)

Add to `<head>` section of admin pages:
```php
<!-- Prevent Search Engine Indexing -->
<meta name="robots" content="noindex, nofollow, noarchive, nosnippet">
<meta name="googlebot" content="noindex, nofollow">
<meta name="bingbot" content="noindex, nofollow">
```

---

## 7. Verification Checklist

### DNS & Subdomain
- [ ] DNS A record created for `admin.ndosa.store`
- [ ] Subdomain points to `/admin` directory
- [ ] SSL certificate installed and active
- [ ] HTTPS redirect working

### Search Engine Blocking
- [ ] robots.txt accessible at `https://admin.ndosa.store/robots.txt`
- [ ] X-Robots-Tag header present (check with browser DevTools)
- [ ] Meta tags in admin pages
- [ ] Google Search Console: Request removal (optional)

### Security
- [ ] .htaccess blocks sensitive files
- [ ] Directory listing disabled
- [ ] HTTPS enforced
- [ ] Optional: IP whitelist or HTTP auth enabled

### Testing
```bash
# Test robots.txt
curl https://admin.ndosa.store/robots.txt

# Test X-Robots-Tag header
curl -I https://admin.ndosa.store

# Test admin access
curl https://admin.ndosa.store/login.php
```

---

## 8. Update Main robots.txt

Ensure main site robots.txt blocks admin:
```
# Already in /robots.txt
User-agent: *
Disallow: /admin/
```

---

## 9. Google Search Console (Optional Removal)

If admin pages were previously indexed:
1. Go to Google Search Console
2. Navigate to **Removals**
3. Request removal: `https://admin.ndosa.store/*`
4. Wait 24-48 hours for de-indexing

---

## 10. Monitoring

### Check for Indexed Pages (Monthly)
```
site:admin.ndosa.store
```
Should return: **No results found**

### Log Unauthorized Access Attempts
Check DirectAdmin logs:
```bash
tail -f /var/log/httpd/domains/admin.ndosa.store.error.log
```

---

## Quick Setup Commands

```bash
# SSH into server
ssh ndosa@your-server-ip

# Navigate to admin directory
cd /home/ndosa/public_html/admin

# Verify robots.txt exists
cat robots.txt

# Verify .htaccess exists
cat .htaccess

# Test HTTPS redirect
curl -I http://admin.ndosa.store
# Should show 301 redirect to https://

# Check X-Robots-Tag header
curl -I https://admin.ndosa.store | grep -i robot
# Should show: X-Robots-Tag: noindex, nofollow, noarchive, nosnippet
```

---

## Access URLs

**Production:**
- Admin Panel: `https://admin.ndosa.store/`
- Login: `https://admin.ndosa.store/login.php`
- Dashboard: `https://admin.ndosa.store/dashboard.php`

**Main Site:**
- Website: `https://ndosa.store`
- Old admin URL: `https://ndosa.store/admin/` (still works)

**Local Development:**
- `http://localhost/jinkaplotterwebsite/admin/`

---

## Credentials

```
Username: admin
Password: Admin@2025!
⚠️ CHANGE IMMEDIATELY AFTER FIRST LOGIN!
```

---

## Support

For issues:
1. Check DNS propagation: https://dnschecker.org
2. Verify DirectAdmin subdomain settings
3. Review error logs
4. Test with different browsers/devices

---

## Summary

✅ Subdomain: `admin.ndosa.store`  
✅ SSL: Auto-renewed Let's Encrypt  
✅ Search Blocking: robots.txt + X-Robots-Tag + meta tags  
✅ Security: HTTPS + .htaccess + optional auth  
✅ Not Indexed: Multiple layers of protection
