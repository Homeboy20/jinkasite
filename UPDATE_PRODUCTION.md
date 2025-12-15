# Update Production Site with Latest Changes

## Quick Update Commands

### SSH into your server:
```bash
ssh ndosa@your-server-ip
# or use DirectAdmin File Manager Terminal
```

### Navigate to website directory:
```bash
cd /home/ndosa/public_html
```

### Pull latest changes from GitHub:
```bash
git pull origin main
```

### Clear any cache (if applicable):
```bash
# Clear PHP opcache
php -r "opcache_reset();"

# Or restart Apache
sudo systemctl restart httpd
# OR in DirectAdmin: Server Manager → Service Monitor → httpd → Restart
```

---

## If Git Not Set Up Yet

If you haven't cloned the repository yet, do this:

```bash
# Backup existing files first
cd /home/ndosa
mv public_html public_html.backup

# Clone from GitHub
git clone https://github.com/Homeboy20/jinkasite.git public_html

# Navigate to directory
cd public_html

# Copy production environment
cp .env.production .env

# Edit with your credentials
nano .env

# Set permissions
chmod 644 .env
chmod -R 777 uploads
```

---

## Verify Update

### Check latest commit:
```bash
cd /home/ndosa/public_html
git log --oneline -1
# Should show: "Add changes summary documentation"
```

### Test the site:
1. **Homepage:** https://ndosa.store
2. **Admin:** https://ndosa.store/admin/ or https://admin.ndosa.store/
3. **Check CTA section** - should show styled "Exclusive Offer" section
4. **Check robots.txt:** https://admin.ndosa.store/robots.txt

---

## Troubleshooting

### If pull fails with conflicts:
```bash
# Stash local changes
git stash

# Pull updates
git pull origin main

# Reapply local changes if needed
git stash pop
```

### If "not a git repository":
```bash
# Initialize git
cd /home/ndosa/public_html
git init
git remote add origin https://github.com/Homeboy20/jinkasite.git
git fetch origin
git reset --hard origin/main
```

### Clear browser cache:
- Press `Ctrl + Shift + R` (or `Cmd + Shift + R` on Mac)
- Or open in incognito/private mode

### Check file permissions:
```bash
cd /home/ndosa/public_html
chmod 755 admin
chmod 644 admin/.htaccess
chmod 644 admin/robots.txt
```

---

## What Should Be Updated

After pulling latest changes, you should see:

✅ **Admin Subdomain Files:**
- `admin/robots.txt` - Search engine blocking
- `admin/.htaccess` - Security headers
- `admin/index.php` - Entry point

✅ **Styling Fixes:**
- Fixed CTA section background gradient
- Mobile responsive styles for "Exclusive Offer"
- Improved visibility on all devices

✅ **Documentation:**
- `ADMIN_SUBDOMAIN_SETUP.md`
- `CHANGES_SUMMARY.md`

---

## Quick Verification Commands

```bash
# Check if new files exist
ls -la admin/robots.txt admin/.htaccess admin/index.php

# View latest commit
git log -1 --oneline

# Check git status
git status

# View recent changes
git diff HEAD~1 css/style.css
```

---

## Alternative: Manual Update via DirectAdmin

If you can't use Git:

1. **Download from GitHub:**
   - Go to: https://github.com/Homeboy20/jinkasite
   - Click "Code" → "Download ZIP"

2. **Upload via DirectAdmin:**
   - Login to DirectAdmin
   - File Manager → public_html
   - Upload and extract ZIP
   - Overwrite existing files

3. **Upload specific changed files:**
   - `admin/robots.txt`
   - `admin/.htaccess`
   - `admin/index.php`
   - `css/style.css`
   - Documentation files (optional)

---

## Current Git Status

**Latest commits on GitHub:**
1. `3f3171d` - Add changes summary documentation
2. `1fba987` - Update deployment guide
3. `a2e5c60` - Add admin subdomain setup with SEO blocking and fix CTA section styling

**Your production should match commit:** `3f3171d`

Check with: `git log --oneline -1`
