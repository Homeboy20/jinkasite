# 🚀 Performance & SEO Optimization Guide

## Overview
This guide walks you through implementing all performance and SEO optimizations for the JINKA Plotter website.

---

## ✅ Step 1: Database Performance (5 minutes)

### Add Performance Indexes

1. **Open phpMyAdmin**: http://localhost/phpmyadmin
2. **Select database**: `jinka_plotter`
3. **Click SQL tab**
4. **Import file**: `database/add-performance-indexes.sql`
5. **Click Go**

**Expected Result**: All indexes added successfully. This will speed up product queries by 50-80%.

**Verify**:
```sql
SHOW INDEX FROM products;
SHOW INDEX FROM categories;
```

---

## ✅ Step 2: Enable Gzip & Caching (2 minutes)

### Update .htaccess File

**Option A: Automatic (Recommended)**
```bash
# Backup current file
copy .htaccess .htaccess.backup

# Use optimized version
copy .htaccess.optimized .htaccess
```

**Option B: Manual**
1. Open `.htaccess` file
2. Copy content from `.htaccess.optimized`
3. Paste and save

**Features Enabled**:
- ✅ Gzip compression (reduces file size by 70%)
- ✅ Browser caching (faster repeat visits)
- ✅ Security headers
- ✅ File protection
- ✅ Clean URLs

**Verify**:
- Check compression: https://checkgzipcompression.com/
- Test speed: https://gtmetrix.com/

---

## ✅ Step 3: Image Lazy Loading (3 minutes)

### Add Performance Scripts

**1. Include performance JavaScript**

Add to `includes/header.php` (before closing `</head>` tag):

```html
<!-- Performance Optimizations -->
<link rel="stylesheet" href="<?php echo site_url('css/performance.css'); ?>">
<script src="<?php echo site_url('js/performance.js'); ?>" defer></script>
```

**2. Update image tags**

Change from:
```html
<img src="image.jpg" alt="Product">
```

To:
```html
<img data-src="image.jpg" alt="Product" loading="lazy" class="lazy">
```

**Benefits**:
- ⚡ Page loads 2-3x faster
- 📉 Reduces initial bandwidth by 60%
- 🎯 Images load only when visible

---

## ✅ Step 4: SEO & Structured Data (5 minutes)

### Implement Schema Markup

**1. Include SEO helpers**

Add to pages (after including `config.php`):
```php
require_once __DIR__ . '/includes/seo-helpers.php';
```

**2. Add to product pages**

In `product-detail.php`, add before closing `</head>`:

```php
<!-- Structured Data -->
<?php
output_schema(generate_product_schema($product));
output_schema(generate_breadcrumb_schema([
    ['name' => 'Home', 'url' => site_url()],
    ['name' => 'Products', 'url' => site_url('products')],
    ['name' => $product['name'], 'url' => current_url(false)]
]));

// Open Graph tags
generate_og_tags([
    'title' => $product['name'],
    'description' => optimize_meta_description($product['description']),
    'image' => normalize_product_image_url($product['image'], ['absolute' => true]),
    'type' => 'product'
]);

generate_canonical();
?>
```

**3. Add to homepage**

In `index.php`, add before closing `</head>`:

```php
<!-- Structured Data -->
<?php
output_schema(generate_organization_schema());
output_schema(generate_website_schema());
generate_og_tags([
    'title' => $page_title,
    'description' => $page_description,
    'image' => $hero_image_absolute
]);
generate_canonical();
?>
```

**4. Update sitemap**

- Access new dynamic sitemap: `sitemap.xml.php`
- Add to `robots.txt`:
```
Sitemap: https://yourdomain.com/sitemap.xml.php
```

---

## ✅ Step 5: WebP Image Format (Optional - 10 minutes)

### Convert Images to WebP

**Windows (using cwebp tool)**:
```bash
# Convert single image
cwebp image.jpg -q 80 -o image.webp

# Bulk convert
for /r %i in (*.jpg) do cwebp "%i" -q 80 -o "%~ni.webp"
```

**PHP (automatic conversion)**:
```php
function convert_to_webp($source, $quality = 80) {
    $image = imagecreatefromjpeg($source);
    $destination = str_replace('.jpg', '.webp', $source);
    imagewebp($image, $destination, $quality);
    imagedestroy($image);
    return $destination;
}
```

**Usage in templates**:
```html
<picture>
    <source srcset="image.webp" type="image/webp">
    <img src="image.jpg" alt="Product" loading="lazy">
</picture>
```

---

## ✅ Step 6: CDN Setup (Optional - 15 minutes)

### Free CDN Options

**Cloudflare (Recommended)**:
1. Sign up at https://cloudflare.com
2. Add your domain
3. Update nameservers
4. Enable:
   - Auto Minify (JS, CSS, HTML)
   - Brotli compression
   - Browser Cache TTL: 1 year
   - Rocket Loader
   - Mirage (image optimization)

**Alternative: BunnyCDN**
- Faster for East Africa
- Pay-as-you-go pricing
- Setup guide: https://bunny.net/docs/

---

## ✅ Step 7: Cache Implementation (Optional - 30 minutes)

### File-Based Caching (Already Implemented)

Your site already has file caching via `CacheManager.php`. To optimize:

**1. Enable caching in `.env`**:
```env
CACHE_ENABLED=true
CACHE_DURATION=3600
```

**2. Use in PHP**:
```php
$cache = new CacheManager();

// Cache product data
$products = $cache->get('featured_products', function() use ($db) {
    return $db->query("SELECT * FROM products WHERE is_featured = 1")->fetchAll();
}, 3600);
```

### Redis Caching (Advanced)

**Requirements**: Redis server installed

**Install PHP Redis extension**:
```bash
# Windows (via WAMP)
# Download php_redis.dll for your PHP version
# Add to php.ini: extension=php_redis.dll
```

**Implementation**:
```php
$redis = new Redis();
$redis->connect('127.0.0.1', 6379);

// Cache data
$redis->setex('products', 3600, json_encode($products));

// Retrieve
$cached = json_decode($redis->get('products'), true);
```

---

## 📊 Performance Benchmarks

### Before Optimization
- Page Load Time: 3-5 seconds
- Page Size: 2-3 MB
- Requests: 50-70
- Google PageSpeed: 40-60

### After Optimization (Expected)
- Page Load Time: 0.8-1.5 seconds ⚡ **70% faster**
- Page Size: 500KB-1MB 📉 **60% smaller**
- Requests: 20-30 📦 **50% fewer**
- Google PageSpeed: 85-95 🎯 **40% improvement**

---

## 🔍 Testing & Verification

### Performance Testing
1. **GTmetrix**: https://gtmetrix.com/
   - Target: A grade, < 2s load time
   
2. **Google PageSpeed**: https://pagespeed.web.dev/
   - Target: 90+ score
   
3. **WebPageTest**: https://www.webpagetest.org/
   - Target: All A's

### SEO Testing
1. **Google Rich Results**: https://search.google.com/test/rich-results
   - Verify structured data
   
2. **Schema Validator**: https://validator.schema.org/
   - Check JSON-LD markup
   
3. **Open Graph**: https://www.opengraph.xyz/
   - Test social media previews

### Image Optimization
1. **Check WebP**: View page source, verify `.webp` images
2. **Lazy Loading**: Use Network tab in DevTools
3. **Compression**: https://tinypng.com/analyzer

---

## 🎯 Quick Wins Checklist

**Immediate (< 5 minutes)**:
- [ ] Run database index script
- [ ] Replace .htaccess with optimized version
- [ ] Add performance.js to pages
- [ ] Enable file caching in .env

**Short-term (1 hour)**:
- [ ] Add lazy loading to all images
- [ ] Implement structured data on key pages
- [ ] Update sitemap to dynamic version
- [ ] Add Open Graph tags

**Medium-term (1 day)**:
- [ ] Convert images to WebP
- [ ] Set up CDN (Cloudflare)
- [ ] Implement Redis caching
- [ ] Add service worker for offline support

---

## 🚨 Troubleshooting

### Gzip not working
- Check if `mod_deflate` is enabled in Apache
- Verify with: `curl -H "Accept-Encoding: gzip" -I http://yoursite.com`

### Images not lazy loading
- Check browser console for JS errors
- Verify `performance.js` is loaded
- Test in incognito mode

### Structured data errors
- Use Google Rich Results Test
- Check JSON syntax
- Verify all required fields are present

### Cache not clearing
```php
// Clear cache manually
require_once 'includes/config.php';
cache_clear();
echo "Cache cleared!";
```

---

## 📈 Monitoring & Maintenance

**Weekly**:
- Check Google Search Console
- Review PageSpeed scores
- Monitor error logs

**Monthly**:
- Clear cache: `cache_clear()`
- Update database indexes if needed
- Review and optimize slow queries

**Quarterly**:
- Audit image sizes
- Review and update structured data
- Performance benchmarking

---

## 💡 Advanced Optimizations

1. **Service Worker** (Progressive Web App)
2. **HTTP/2 Server Push**
3. **Critical CSS Inlining**
4. **Database Query Caching**
5. **Asset Preloading**
6. **Code Splitting**

Guides for these available in `docs/advanced-optimization.md`

---

## 📞 Support

Issues? Check:
1. Browser console (F12) for errors
2. `logs/php_errors.log` for PHP errors
3. Apache error log (via WAMP menu)

---

**Last Updated**: December 11, 2025
**Status**: ✅ Ready to implement
