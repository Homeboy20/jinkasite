# DEPLOYMENT CHECKLIST - Fix "Old Site" Issue

## Why You're Seeing the Old Site

When you upload files directly, the issue is **BROWSER & SERVER CACHING**:

1. ✅ **Browser Cache** - Your browser cached the old CSS for 1 month
2. ✅ **Server Cache** - Apache/PHP opcache may cache old files
3. ✅ **CDN Cache** - If using Cloudflare or CDN

---

## SOLUTION: 3-Step Process

### Step 1: Upload These Files to Production

**CRITICAL FILES (Must upload):**
```
css/style.css                    ← Contains the styling fixes
admin/robots.txt                 ← Search blocking
admin/.htaccess                  ← Security headers
admin/index.php                  ← Entry redirect
ADMIN_SUBDOMAIN_SETUP.md        ← Documentation
```

**How to Upload:**
- **Via DirectAdmin File Manager:**
  1. Login → File Manager
  2. Navigate to `/home/ndosa/public_html/css/`
  3. Upload `style.css` (select "Overwrite")
  4. Navigate to `/home/ndosa/public_html/admin/`
  5. Upload `robots.txt`, `.htaccess`, `index.php`

- **Via FTP (FileZilla):**
  1. Connect to your server
  2. Navigate to `public_html/css/`
  3. Upload `style.css` (overwrite mode)
  4. Navigate to `public_html/admin/`
  5. Upload admin files

---

### Step 2: Clear Server-Side Caches

**Option A: Via DirectAdmin Terminal**
```bash
# Login to DirectAdmin → Terminal
cd /home/ndosa/public_html

# Clear PHP opcache
php -r "if (function_exists('opcache_reset')) opcache_reset();"

# Restart Apache
# (Ask hosting support or use DirectAdmin Service Manager)
```

**Option B: Via SSH**
```bash
ssh ndosa@your-server-ip
cd /home/ndosa/public_html
php -r "opcache_reset();"
```

**Option C: Create a Cache Clear Script**

Upload this file as `clear-cache.php` to your site root:

```php
<?php
// Clear all caches
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "✓ OpCache cleared<br>";
}
if (function_exists('apc_clear_cache')) {
    apc_clear_cache();
    echo "✓ APC Cache cleared<br>";
}
echo "✓ Cache cleared successfully!<br>";
echo "Now delete this file for security.";
?>
```

Then visit: `https://ndosa.store/clear-cache.php`  
**Delete the file immediately after!**

---

### Step 3: Clear Browser Cache

**For You (Admin):**
- **Chrome/Edge:** `Ctrl + Shift + Delete` → Clear cached images and files
- **Firefox:** `Ctrl + Shift + Delete` → Clear cache
- **Safari:** `Cmd + Option + E`
- **Or:** Open in Incognito/Private mode: `Ctrl + Shift + N`

**For All Visitors:**
The cache will clear automatically because `index.php` uses:
```php
<link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
```
This adds a timestamp that changes every second, forcing fresh downloads.

---

## VERIFICATION

### 1. Check CSS File on Server
Visit: `https://ndosa.store/css/style.css`

Search for (Ctrl+F): `linear-gradient(135deg, #ff5900`

**Should find:** 
```css
.cta {
    padding: 5rem 0;
    background: linear-gradient(135deg, #ff5900 0%, #e04e00 100%);
    color: white;
}
```

**Should NOT find:** 
```css
background: #ff5900, var(--primary-dark));  ← Old syntax error
```

---

### 2. Check CTA Section on Frontend
Visit: `https://ndosa.store`

Scroll to "Exclusive Offer" section:
- ✅ Background should be orange/red gradient
- ✅ All text should be visible and readable
- ✅ "🎯 Exclusive Offer - Limited Time Only" badge visible
- ✅ Value points grid (4 boxes) properly displayed
- ✅ Pricing section visible
- ✅ Buttons working

---

### 3. Check Admin Files
Visit: `https://admin.ndosa.store/robots.txt`

**Should show:**
```
User-agent: *
Disallow: /
```

Visit: `https://admin.ndosa.store/`
**Should:** Redirect to login page

---

## TROUBLESHOOTING

### Still Seeing Old Site?

**1. Hard Refresh:**
```
Windows: Ctrl + Shift + R
Mac: Cmd + Shift + R
```

**2. Clear Browser Data:**
- Chrome: `chrome://settings/clearBrowserData`
- Select "Cached images and files"
- Time range: "All time"
- Clear data

**3. Test in Incognito:**
```
Ctrl + Shift + N (Chrome)
Ctrl + Shift + P (Firefox)
```

**4. Check File Upload Timestamp:**
Via DirectAdmin File Manager:
- Navigate to `public_html/css/`
- Check `style.css` modification date
- Should be: December 15, 2025 (today)

**5. Verify File Size:**
- Old `style.css`: ~53 KB
- New `style.css`: ~56 KB (with responsive fixes)

**6. Use Browser DevTools:**
- Press `F12`
- Go to Network tab
- Reload page (`Ctrl + Shift + R`)
- Find `style.css` request
- Check "Status" column: Should be `200` (not `304`)
- Check "Size" column: Should show actual size (not "from cache")

**7. Check CSS in DevTools:**
- Press `F12` → Elements tab
- Find element with class `cta`
- In Styles panel, check background property
- Should show: `background: linear-gradient(...)`

---

## IF CLOUDFLARE IS ENABLED

If you're using Cloudflare:

1. **Login to Cloudflare**
2. **Select your domain:** `ndosa.store`
3. **Go to:** Caching → Configuration
4. **Click:** "Purge Everything"
5. **Confirm purge**
6. **Wait:** 1-2 minutes
7. **Test:** Visit site in incognito mode

**Or Purge Specific Files:**
- Purge URL: `https://ndosa.store/css/style.css`
- Purge URL: `https://admin.ndosa.store/robots.txt`

---

## FASTEST FIX: Rename CSS File

If nothing else works, rename the CSS file to force a new version:

**On server:**
```bash
cd /home/ndosa/public_html/css
cp style.css style.v2.css
```

**In index.php** (line 430):
Change:
```php
<link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
```

To:
```php
<link rel="stylesheet" href="css/style.v2.css?v=<?php echo time(); ?>">
```

This creates a completely new file that bypasses all caches.

---

## FILES TO VERIFY AFTER UPLOAD

Run this checklist via DirectAdmin File Manager:

### CSS Directory (`/public_html/css/`):
- [ ] `style.css` - Size: ~56 KB, Date: Dec 15, 2025

### Admin Directory (`/public_html/admin/`):
- [ ] `robots.txt` - Exists
- [ ] `.htaccess` - Exists (may be hidden)
- [ ] `index.php` - Exists

### Root Directory:
- [ ] `index.php` - Has cache-busting (`?v=<?php echo time(); ?>`)

---

## QUICK TEST SCRIPT

Upload this as `test-cache.php`:

```php
<?php
header('Content-Type: text/html; charset=utf-8');
echo "<h1>Cache Test</h1>";
echo "<p>Current Server Time: " . date('Y-m-d H:i:s') . "</p>";

// Check CSS file
$css_file = 'css/style.css';
if (file_exists($css_file)) {
    $css_content = file_get_contents($css_file);
    $has_fix = strpos($css_content, 'linear-gradient(135deg, #ff5900') !== false;
    $has_error = strpos($css_content, '#ff5900, var(--primary-dark)') !== false;
    
    echo "<h2>CSS File Check:</h2>";
    echo "File exists: ✓<br>";
    echo "File size: " . filesize($css_file) . " bytes<br>";
    echo "Last modified: " . date('Y-m-d H:i:s', filemtime($css_file)) . "<br>";
    echo "Contains fix: " . ($has_fix ? '✓ YES' : '✗ NO') . "<br>";
    echo "Contains error: " . ($has_error ? '✗ YES (PROBLEM!)' : '✓ NO') . "<br>";
} else {
    echo "<p style='color:red'>CSS file not found!</p>";
}

// Check admin files
echo "<h2>Admin Files:</h2>";
echo "robots.txt: " . (file_exists('admin/robots.txt') ? '✓' : '✗') . "<br>";
echo ".htaccess: " . (file_exists('admin/.htaccess') ? '✓' : '✗') . "<br>";
echo "index.php: " . (file_exists('admin/index.php') ? '✓' : '✗') . "<br>";

echo "<hr><p><strong>Delete this file after testing!</strong></p>";
?>
```

Visit: `https://ndosa.store/test-cache.php`

---

## SUMMARY

**What to Upload:**
1. `css/style.css` (MUST!)
2. `admin/robots.txt`
3. `admin/.htaccess`
4. `admin/index.php`

**After Upload:**
1. Clear server cache (opcache)
2. Hard refresh browser (`Ctrl + Shift + R`)
3. Test in incognito mode
4. If using Cloudflare: Purge cache

**Expected Result:**
- CTA section styled with orange gradient
- All text visible on mobile
- Admin subdomain blocked from search engines

---

**Need Help?**
- Check: `test-cache.php` (upload script above)
- Verify: File modification dates in File Manager
- Test: Incognito mode to bypass your cache
