# IMMEDIATE FIX - Upload These Exact Files

## THE PROBLEM
Your .htaccess was configured for local WAMP, not production!

**Line causing issue:**
```apache
RewriteBase /jinkaplotterwebsite/  # ← This is WRONG for production!
```

**Should be:**
```apache
RewriteBase /  # ← Correct for ndosa.store
```

---

## SOLUTION: Upload These 5 Files

### 1. `.htaccess` (CRITICAL!)
**Location:** `/home/ndosa/public_html/.htaccess`  
**Action:** Upload and OVERWRITE  
**Why:** Fixes URL routing and enables HTTPS

### 2. `index.php` (CRITICAL!)
**Location:** `/home/ndosa/public_html/index.php`  
**Size:** 171,920 bytes  
**Why:** Contains enhanced CTA section with "Exclusive Offer"

### 3. `css/style.css` (CRITICAL!)
**Location:** `/home/ndosa/public_html/css/style.css`  
**Size:** ~56 KB  
**Why:** Contains styling fixes for CTA section

### 4. `admin/robots.txt`
**Location:** `/home/ndosa/public_html/admin/robots.txt`  
**Why:** Blocks search engines from indexing admin

### 5. `admin/.htaccess`
**Location:** `/home/ndosa/public_html/admin/.htaccess`  
**Why:** Adds X-Robots-Tag header

---

## UPLOAD INSTRUCTIONS

### Via DirectAdmin File Manager:

1. **Login to DirectAdmin**
2. **Go to:** File Manager

3. **Upload .htaccess:**
   - Navigate to: `/home/ndosa/public_html/`
   - Click "Upload Files"
   - Select `.htaccess` from your local folder
   - **IMPORTANT:** Enable "Overwrite if exists"
   - Upload

4. **Upload index.php:**
   - Same directory: `/home/ndosa/public_html/`
   - Upload `index.php`
   - Overwrite

5. **Upload style.css:**
   - Navigate to: `/home/ndosa/public_html/css/`
   - Upload `style.css`
   - Overwrite

6. **Upload admin files:**
   - Navigate to: `/home/ndosa/public_html/admin/`
   - Upload `robots.txt` and `.htaccess`
   - **Note:** `.htaccess` is hidden - enable "Show Hidden Files"

### Via FTP (FileZilla):

```
Local                              →  Remote
.htaccess                          →  /public_html/.htaccess
index.php                          →  /public_html/index.php
css/style.css                      →  /public_html/css/style.css
admin/robots.txt                   →  /public_html/admin/robots.txt
admin/.htaccess                    →  /public_html/admin/.htaccess
```

**Transfer Mode:** Binary  
**If File Exists:** Overwrite

---

## AFTER UPLOADING

### Step 1: Clear Server Cache

**Create this file:** `/home/ndosa/public_html/clear-cache.php`

```php
<?php
// Clear PHP opcache
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "✓ OpCache cleared<br>";
} else {
    echo "✓ No OpCache to clear<br>";
}

// Show current time to verify script ran
echo "✓ Server time: " . date('Y-m-d H:i:s') . "<br>";
echo "<br><strong>Cache cleared! Now delete this file immediately.</strong>";
?>
```

**Visit:** `https://ndosa.store/clear-cache.php`  
**Then:** Delete the file!

### Step 2: Clear Browser Cache

**Hard Refresh:**
- Windows: `Ctrl + Shift + R`
- Mac: `Cmd + Shift + R`

**Or Test in Incognito:**
- `Ctrl + Shift + N` (Chrome)
- `Ctrl + Shift + P` (Firefox)

### Step 3: If Using Cloudflare

1. Login to Cloudflare
2. Select domain: `ndosa.store`
3. Go to: **Caching** → **Configuration**
4. Click: **"Purge Everything"**
5. Confirm
6. Wait 2-3 minutes

---

## VERIFICATION CHECKLIST

### ✅ Check #1: .htaccess Uploaded Correctly

**SSH or Terminal:**
```bash
cd /home/ndosa/public_html
head -10 .htaccess
```

**Should show:**
```apache
# Enhanced .htaccess with Performance & Security Optimizations
# JINKA Plotter Website

# ============================================
# ENABLE REWRITE ENGINE
# ============================================
RewriteEngine On
# RewriteBase /jinkaplotterwebsite/  # For local WAMP
RewriteBase /  # For production
```

**Or via browser:**
Visit: `https://ndosa.store/` (should NOT show 404 errors)

---

### ✅ Check #2: CSS File Updated

Visit: `https://ndosa.store/css/style.css`

**Press Ctrl+F and search for:** `linear-gradient(135deg, #ff5900`

**Must find:** 
```css
.cta {
    padding: 5rem 0;
    background: linear-gradient(135deg, #ff5900 0%, #e04e00 100%);
    color: white;
}
```

**Must NOT find:** 
```css
background: #ff5900, var(--primary-dark));  ← Syntax error from old version
```

---

### ✅ Check #3: Homepage CTA Section

Visit: `https://ndosa.store/`

**Scroll to bottom, look for:**
- Orange/red gradient background (not plain color)
- "🎯 Exclusive Offer - Limited Time Only" badge
- "Ready to Transform Your Business?" heading
- 4 value boxes (Free Installation, Training, Warranty, Support)
- Pricing section visible
- "EXCLUSIVE BONUS" yellow box
- WhatsApp and call buttons

**On Mobile (F12 → Toggle Device Mode):**
- All text readable
- No overflow
- Buttons full width
- Grid stacks vertically

---

### ✅ Check #4: File Sizes Match

**Via DirectAdmin File Manager:**

| File | Expected Size |
|------|---------------|
| index.php | ~171 KB |
| css/style.css | ~56 KB |
| .htaccess | ~6 KB |

**If sizes don't match:** Files didn't upload correctly. Re-upload!

---

### ✅ Check #5: Admin Subdomain

Visit: `https://admin.ndosa.store/robots.txt`

**Should show:**
```
User-agent: *
Disallow: /
```

Visit: `https://admin.ndosa.store/`

**Should:** Redirect to login page (not 403 error)

---

## TROUBLESHOOTING

### Problem: Still seeing old site

**Solution 1: Check file upload**
```bash
# Via SSH
cd /home/ndosa/public_html
ls -lh index.php css/style.css .htaccess
# Verify dates are recent (today)
```

**Solution 2: Force browser refresh**
```
1. Close all browser tabs
2. Clear browser cache completely
3. Open incognito window
4. Visit https://ndosa.store
```

**Solution 3: Check .htaccess syntax**
```bash
# Via SSH
cd /home/ndosa/public_html
cat .htaccess | grep RewriteBase
# Should show: RewriteBase /
# Should NOT show: RewriteBase /jinkaplotterwebsite/
```

**Solution 4: Restart Apache**
```bash
# Ask hosting support OR via DirectAdmin:
# Server Manager → Service Monitor → httpd → Restart
```

---

### Problem: 404 errors everywhere

**Cause:** .htaccess not uploaded or wrong RewriteBase

**Solution:**
1. Re-upload `.htaccess` file
2. Ensure it has: `RewriteBase /`
3. Clear server cache
4. Restart Apache

---

### Problem: CSS not updating

**Solution: Rename CSS file**
```bash
# Via SSH
cd /home/ndosa/public_html/css
cp style.css style.v2.css
```

**Then update index.php line 430:**
```php
<link rel="stylesheet" href="css/style.v2.css?v=<?php echo time(); ?>">
```

This creates a NEW file that bypasses all caches.

---

## SUMMARY

**Critical Files (Must Upload):**
1. ✅ `.htaccess` - Fixes routing (RewriteBase /)
2. ✅ `index.php` - Has enhanced CTA section
3. ✅ `css/style.css` - Has styling fixes

**After Upload:**
1. Clear server cache (create clear-cache.php)
2. Hard refresh browser (Ctrl+Shift+R)
3. Test in incognito mode
4. Purge Cloudflare if applicable

**Test URLs:**
- Homepage: `https://ndosa.store`
- CSS file: `https://ndosa.store/css/style.css`
- Admin robots: `https://admin.ndosa.store/robots.txt`

---

## Quick Test Command (SSH)

```bash
cd /home/ndosa/public_html
echo "=== Checking .htaccess ==="
grep "RewriteBase" .htaccess
echo ""
echo "=== Checking file dates ==="
ls -lh index.php css/style.css .htaccess
echo ""
echo "=== Checking CSS content ==="
grep -c "linear-gradient(135deg, #ff5900" css/style.css
```

**Expected output:**
```
=== Checking .htaccess ===
RewriteBase /  # For production

=== Checking file dates ===
-rw-r--r-- 1 ndosa ndosa 171K Dec 15 2025 index.php
-rw-r--r-- 1 ndosa ndosa  56K Dec 15 2025 css/style.css
-rw-r--r-- 1 ndosa ndosa 6.1K Dec 15 2025 .htaccess

=== Checking CSS content ===
1
```

If any check fails, that file didn't upload correctly!

---

**Need Help?**
Check file contents directly:
```bash
cat .htaccess | head -20
cat css/style.css | grep -A5 "\.cta {"
```
