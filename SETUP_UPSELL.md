# Quick Setup: Upsell & Related Products

## 1. Install Database Table

Run this SQL command in your database:

```sql
-- Option A: Using phpMyAdmin
-- Copy and paste the content from database/product_relationships.sql

-- Option B: Using command line
mysql -u your_username -p jinka_plotter < database/product_relationships.sql
```

Or manually execute:

```sql
CREATE TABLE IF NOT EXISTS `product_relationships` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL COMMENT 'Main product',
  `related_product_id` int(11) NOT NULL COMMENT 'Related/upsell product',
  `relationship_type` enum('related','upsell','cross_sell','accessory','bundle') DEFAULT 'related',
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_related_product_id` (`related_product_id`),
  KEY `idx_relationship_type` (`relationship_type`),
  UNIQUE KEY `unique_relationship` (`product_id`, `related_product_id`, `relationship_type`),
  FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`related_product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## 2. Verify Installation

Test that the feature is working:

1. **View a product page**: Navigate to any product detail page
2. **Check for sections**: You should see:
   - "Upgrade Your Choice" (if upsells exist or similar products with higher prices)
   - "Complete Your Setup" (if accessories exist or complementary products)
   - "You May Also Like" (always shows with smart recommendations)

## 3. Add Manual Relationships (Optional)

Use PHP to add specific product relationships:

```php
require_once 'includes/ProductRelationships.php';
$relationships = new ProductRelationships();

// Example: Product ID 1 is a basic plotter
// Add premium plotter as upsell
$relationships->addRelationship(
    1,      // Main product ID
    5,      // Premium plotter ID
    'upsell',
    1       // Display order
);

// Add ink cartridges as accessories
$relationships->bulkAddRelationships(
    1,                  // Main product ID
    [10, 11, 12],      // Ink cartridge IDs
    'accessory'
);

// Add similar products as related items
$relationships->bulkAddRelationships(
    1,                  // Main product ID
    [2, 3, 4],         // Similar plotter IDs
    'related'
);
```

## 4. Smart Recommendations Work Automatically

Even without manual relationships, the system will show:
- Products from the same category
- Products in similar price range (±30%)
- Featured products
- In-stock items only

## 5. Customization

### Change Number of Recommendations

Edit `product-detail.php`:

```php
// Show more/fewer recommendations
$related_products = $relationships->getAllRecommendations($product['id'], 'related', 8);  // Changed from 6 to 8
$upsell_products = $relationships->getAllRecommendations($product['id'], 'upsell', 6);   // Changed from 4 to 6
$accessory_products = $relationships->getAllRecommendations($product['id'], 'accessory', 6);
```

### Modify Styling

Edit `css/style.css` - look for these sections:
- `.upsell-products` - Upsell section styling
- `.accessory-products` - Accessory section styling
- `.related-products` - Related products styling
- `.products-grid` - Grid layout

### Change Colors

```css
/* Upsell section - currently blue gradient */
.upsell-products {
    background: linear-gradient(135deg, #your-color-1 0%, #your-color-2 100%);
}

/* Accessory section - currently yellow/gold gradient */
.accessory-products {
    background: linear-gradient(135deg, #your-color-1 0%, #your-color-2 100%);
}
```

## 6. Troubleshooting

### No recommendations showing?
- **Check**: Do you have multiple products in the database?
- **Check**: Are products set to `is_active = 1`?
- **Check**: Do products have `stock_quantity > 0`?

### SQL Error on page load?
- **Fix**: Ensure `product_relationships` table exists
- **Fix**: Run the SQL migration again
- **Fix**: Check foreign key constraints match your products table

### Styling looks broken?
- **Fix**: Clear browser cache (Ctrl+F5)
- **Fix**: Check that `style.css` was updated
- **Fix**: Inspect element to see if CSS is loading

### PHP Error: Class 'ProductRelationships' not found
- **Fix**: Ensure `includes/ProductRelationships.php` exists
- **Fix**: Check that `require_once` statement is in `product-detail.php`

## 7. Testing Checklist

- [ ] Database table `product_relationships` created successfully
- [ ] Product detail page loads without errors
- [ ] At least one recommendation section appears
- [ ] Clicking on recommended product navigates correctly
- [ ] "Quick Add" button on accessories works
- [ ] Mobile responsive design works properly
- [ ] No console errors in browser developer tools

## 8. Performance Tips

For sites with many products:

1. **Add Database Indexes** (already included in migration):
   ```sql
   CREATE INDEX idx_active_relationships ON product_relationships (product_id, is_active, relationship_type);
   CREATE INDEX idx_display ON product_relationships (product_id, display_order);
   ```

2. **Limit Recommendations**:
   - Keep limits reasonable (4-8 products per section)
   - Too many recommendations slow page load

3. **Cache Results** (advanced):
   ```php
   // Add caching layer for high-traffic sites
   // Use Redis, Memcached, or file-based caching
   ```

## 9. Next Steps

### Immediate:
1. ✅ Verify table creation
2. ✅ Test product detail page
3. ⏳ Add manual relationships for best-selling products

### Future:
1. Create admin UI for managing relationships
2. Track which recommendations convert to sales
3. Implement A/B testing for different layouts
4. Add "Bundle Deal" pricing

## Need Help?

Check the full documentation: `docs/UPSELL_RELATED_PRODUCTS.md`
