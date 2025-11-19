# Upsell & Related Products - Quick Reference

## 🚀 Quick Start (3 Steps)

```bash
# 1. Install database table
mysql -u username -p database < database/product_relationships.sql

# 2. Include the class
require_once 'includes/ProductRelationships.php';

# 3. Use it!
$relationships = new ProductRelationships();
$recommendations = $relationships->getAllRecommendations($productId, 'related', 6);
```

## 📖 Common Tasks

### Get Recommendations
```php
$relationships = new ProductRelationships();

// Related products (manual + smart)
$related = $relationships->getAllRecommendations($productId, 'related', 6);

// Upsell products
$upsells = $relationships->getAllRecommendations($productId, 'upsell', 4);

// Accessories
$accessories = $relationships->getAllRecommendations($productId, 'accessory', 4);
```

### Add Relationships
```php
// Add single relationship
$relationships->addRelationship(
    $productId,          // Main product
    $relatedProductId,   // Related product
    'upsell',           // Type
    1                   // Display order
);

// Add multiple accessories
$relationships->bulkAddRelationships(
    $productId,
    [10, 11, 12],  // Related product IDs
    'accessory'
);
```

### Remove Relationships
```php
// Remove specific relationship
$relationships->removeRelationship($productId, $relatedProductId, 'upsell');

// Remove all relationships of a type
$relationships->removeRelationship($productId, $relatedProductId);
```

## 🎨 Display on Frontend

### Basic Display Loop
```php
<?php foreach ($recommendations as $product): ?>
<div class="product-card">
    <img src="<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>">
    <h3><?php echo $product['name']; ?></h3>
    <div class="price">KES <?php echo number_format($product['price_kes'], 0); ?></div>
    <a href="product-detail.php?slug=<?php echo $product['slug']; ?>">View Details</a>
</div>
<?php endforeach; ?>
```

### With Stock Check
```php
<?php foreach ($recommendations as $product): ?>
    <?php if ($product['stock_quantity'] > 0): ?>
        <!-- Show product with "Add to Cart" -->
    <?php else: ?>
        <!-- Show "Out of Stock" -->
    <?php endif; ?>
<?php endforeach; ?>
```

## 🔧 API Reference

### Core Methods

| Method | Parameters | Returns | Description |
|--------|-----------|---------|-------------|
| `getAllRecommendations()` | `$productId, $type, $limit` | Array | Manual + Smart recommendations |
| `getRelatedProducts()` | `$productId, $type, $limit` | Array | Manual relationships only |
| `getSmartRecommendations()` | `$productId, $limit` | Array | Algorithm-based recommendations |
| `addRelationship()` | `$id, $relatedId, $type, $order` | Boolean | Add single relationship |
| `bulkAddRelationships()` | `$id, $relatedIds[], $type` | Boolean | Add multiple relationships |
| `removeRelationship()` | `$id, $relatedId, $type?` | Boolean | Remove relationship |
| `getProductRelationships()` | `$productId` | Array | All relationships for product |

### Relationship Types
```php
'related'     // Similar products
'upsell'      // Premium alternatives
'cross_sell'  // Complementary products
'accessory'   // Add-ons/accessories
'bundle'      // Package deals
```

## 💾 Database Queries

### Get All Relationships for a Product
```sql
SELECT * FROM product_relationships 
WHERE product_id = ? 
AND is_active = 1;
```

### Get Products of Specific Type
```sql
SELECT p.*, pr.relationship_type
FROM product_relationships pr
JOIN products p ON pr.related_product_id = p.id
WHERE pr.product_id = ?
AND pr.relationship_type = 'upsell'
AND pr.is_active = 1;
```

### Insert Relationship
```sql
INSERT INTO product_relationships 
(product_id, related_product_id, relationship_type, display_order)
VALUES (?, ?, ?, ?);
```

## 🎨 CSS Classes

### Layout Classes
```css
.upsell-products       /* Upsell section container */
.accessory-products    /* Accessory section container */
.related-products      /* Related section container */
.products-grid         /* Grid layout for products */
```

### Card Classes
```css
.product-card          /* Individual product card */
.upsell-card           /* Upsell-specific styling */
.accessory-card        /* Accessory-specific styling */
.product-image-container  /* Image wrapper */
.product-info          /* Product details */
.product-actions       /* Button container */
```

### Badge Classes
```css
.product-badge         /* Generic badge */
.upgrade-badge         /* Purple "Upgrade" badge */
.accessory-badge       /* Orange "Add-on" badge */
.featured-badge        /* Green "Featured" badge */
```

## 🔍 Testing

### Verify Installation
```php
// Check if table exists
$result = $db->query("SHOW TABLES LIKE 'product_relationships'");
if ($result->num_rows > 0) {
    echo "✅ Table exists";
}

// Test class
$relationships = new ProductRelationships();
$test = $relationships->getAllRecommendations(1, 'related', 6);
var_dump($test);
```

### Debug Mode
```php
// Enable error display
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Test recommendations
$recommendations = $relationships->getAllRecommendations($productId, 'related', 6);
echo "<pre>";
print_r($recommendations);
echo "</pre>";
```

## ⚠️ Common Issues

### No Recommendations Showing
```php
// Check: Do you have products?
$products = $db->query("SELECT COUNT(*) FROM products WHERE is_active = 1");

// Check: Does the product exist?
$product = $db->query("SELECT * FROM products WHERE id = $productId");

// Check: Are related products active?
$related = $db->query("SELECT * FROM products WHERE is_active = 1 AND stock_quantity > 0");
```

### SQL Errors
```php
// Check foreign keys
$db->query("SHOW CREATE TABLE product_relationships");

// Verify products table structure
$db->query("DESCRIBE products");
```

### Performance Issues
```php
// Add limit to queries
$recommendations = $relationships->getAllRecommendations($productId, 'related', 6); // Not 50!

// Check indexes
$db->query("SHOW INDEX FROM product_relationships");
```

## 📊 Performance Tips

### Optimize Queries
```php
// Good: Specific type, limited results
$upsells = $relationships->getRelatedProducts($productId, 'upsell', 4);

// Bad: All types, unlimited
$all = $relationships->getRelatedProducts($productId, null, 999);
```

### Cache Results
```php
// Example caching (pseudo-code)
$cacheKey = "recs_{$productId}_related";
if ($cached = cache()->get($cacheKey)) {
    return $cached;
}
$recommendations = $relationships->getAllRecommendations($productId, 'related', 6);
cache()->set($cacheKey, $recommendations, 3600); // 1 hour
```

### Database Indexes (Already Created)
```sql
✅ idx_product_id
✅ idx_related_product_id  
✅ idx_relationship_type
✅ idx_active_relationships
✅ idx_display
```

## 🎯 Best Practices

### Manual Relationships
- Add 3-6 manually for best results
- Use specific products you know sell together
- Set appropriate relationship types
- Order by importance (display_order)

### Smart Recommendations
- Work automatically with no setup
- Fill gaps when manual relationships < limit
- Based on category and price
- Prioritize in-stock and featured

### Display
- Show 4-8 products per section
- Use clear section headers
- Enable quick-add for accessories
- Show price differences for upsells

## 📚 File Locations

```
project/
├── database/
│   └── product_relationships.sql          # Database migration
├── includes/
│   └── ProductRelationships.php           # Core class
├── product-detail.php                     # Display implementation
├── css/
│   └── style.css (lines 1201-1550)       # Styling
├── docs/
│   ├── UPSELL_RELATED_PRODUCTS.md        # Full documentation
│   └── UPSELL_ARCHITECTURE.md            # System diagrams
├── SETUP_UPSELL.md                        # Setup guide
├── IMPLEMENTATION_SUMMARY_UPSELL.md       # Implementation summary
└── test_upsell.php                        # Testing script
```

## 🆘 Support

### Run Tests
```bash
# Open in browser
http://your-site.com/test_upsell.php
```

### Check Documentation
- Full docs: `docs/UPSELL_RELATED_PRODUCTS.md`
- Setup guide: `SETUP_UPSELL.md`
- Architecture: `docs/UPSELL_ARCHITECTURE.md`

### Debug Checklist
- [ ] Table `product_relationships` exists?
- [ ] File `ProductRelationships.php` exists?
- [ ] Class included in product-detail.php?
- [ ] Products exist and are active?
- [ ] CSS file updated?
- [ ] Browser cache cleared?

---

**Version**: 1.0  
**Last Updated**: November 7, 2025  
**Status**: Production Ready ✅
