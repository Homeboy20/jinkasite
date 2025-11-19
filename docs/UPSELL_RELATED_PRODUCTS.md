# Upsell & Related Products Feature

## Overview
Advanced product recommendation system with three types of product relationships:
- **Related Products**: Similar items customers might like
- **Upsells**: Premium alternatives with better features/higher price
- **Accessories/Cross-sells**: Complementary products to complete the purchase

## Features

### 1. Smart Recommendations
When no manual relationships are set, the system automatically suggests:
- Products from the same category
- Products in similar price range (±30%)
- Featured and in-stock items prioritized

### 2. Manual Relationship Management
Admins can manually define relationships between products for precise control.

### 3. Multiple Display Sections
**Upgrade Your Choice (Upsells)**
- Shows premium alternatives
- Displays price difference
- Highlights upgrade benefits

**Complete Your Setup (Accessories)**
- Quick-add functionality
- Complementary products
- Essential add-ons

**You May Also Like (Related)**
- Similar products
- Category-based recommendations
- Stock availability indicators

## Database Structure

### Table: `product_relationships`
```sql
- id: Primary key
- product_id: Main product
- related_product_id: Related/upsell product
- relationship_type: ENUM('related','upsell','cross_sell','accessory','bundle')
- display_order: Sort order
- is_active: Enable/disable relationship
```

## Usage

### For Developers

#### Get Related Products
```php
$relationships = new ProductRelationships();

// Get manual + smart recommendations
$relatedProducts = $relationships->getAllRecommendations($productId, 'related', 6);

// Get only upsells
$upsells = $relationships->getAllRecommendations($productId, 'upsell', 4);

// Get only accessories
$accessories = $relationships->getAllRecommendations($productId, 'accessory', 4);
```

#### Add Relationship
```php
$relationships->addRelationship(
    $productId,           // Main product
    $relatedProductId,    // Related product
    'upsell',            // Type: related, upsell, cross_sell, accessory, bundle
    1                    // Display order
);
```

#### Bulk Add Relationships
```php
$relationships->bulkAddRelationships(
    $productId,
    [12, 15, 18],  // Array of related product IDs
    'accessory'     // Relationship type
);
```

### For Admins

#### Managing Relationships (Future Admin Panel)
1. Navigate to Products > Edit Product
2. Scroll to "Related Products" section
3. Select relationship type
4. Search and add products
5. Reorder using drag-and-drop
6. Save changes

## Smart Recommendation Algorithm

### Priority Order:
1. **Same Category Match** (highest priority)
   - Products in the same category as the main product
   
2. **Price Range Match**
   - Products within 70%-130% of main product price
   
3. **Featured Products**
   - Featured products ranked higher
   
4. **Stock Availability**
   - Only in-stock products recommended
   
5. **Price Proximity**
   - Closer prices ranked higher
   
6. **Recency**
   - Newer products ranked higher

## Display Sections

### Upsell Section
- **Background**: Blue gradient
- **Badge**: Purple "Upgrade" badge
- **Special Features**:
  - Price difference calculator
  - "View Details" CTA
  - Premium border styling

### Accessory Section
- **Background**: Yellow/gold gradient
- **Badge**: Orange "Add-on" badge
- **Special Features**:
  - Quick-add to cart button
  - Complementary product indicator
  - Golden border styling

### Related Section
- **Background**: White
- **Badge**: Green "Featured" badge (if applicable)
- **Special Features**:
  - Full product details
  - Stock status indicators
  - Add to cart functionality

## Performance Considerations

### Caching Recommendations
For high-traffic sites, consider caching recommendations:

```php
// Example caching strategy
$cacheKey = "product_recommendations_{$productId}";
$cached = cache()->get($cacheKey);

if (!$cached) {
    $cached = $relationships->getAllRecommendations($productId, null, 6);
    cache()->set($cacheKey, $cached, 3600); // Cache for 1 hour
}
```

### Database Indexes
The following indexes are automatically created:
- `idx_product_id` - Fast lookup by main product
- `idx_related_product_id` - Fast lookup by related product
- `idx_relationship_type` - Filter by type
- `unique_relationship` - Prevent duplicates
- `idx_active_relationships` - Active relationships only
- `idx_display` - Sort by display order

## Styling Variables

### CSS Classes
```css
.upsell-products      - Upsell section container
.upsell-card          - Individual upsell card
.upgrade-badge        - Purple "Upgrade" badge

.accessory-products   - Accessory section container
.accessory-card       - Individual accessory card
.accessory-badge      - Orange "Add-on" badge

.related-products     - Related section container
.product-card         - Individual product card
.featured-badge       - Green "Featured" badge

.products-grid        - Responsive grid layout
.product-actions      - Action buttons container
```

## Future Enhancements

### Planned Features:
1. **Admin UI for Managing Relationships**
   - Drag-and-drop interface
   - Bulk import/export
   - Relationship analytics

2. **AI-Powered Recommendations**
   - Machine learning based on purchase patterns
   - Collaborative filtering
   - Seasonal recommendations

3. **A/B Testing**
   - Test different recommendation strategies
   - Conversion rate tracking
   - Performance metrics

4. **Bundle Deals**
   - Automatic discount calculation
   - "Buy together and save" section
   - Bundle inventory management

5. **Customer Behavior Tracking**
   - "Customers who bought X also bought Y"
   - View history based recommendations
   - Personalized suggestions

## Migration Guide

### Initial Setup
1. Run the SQL migration:
   ```bash
   mysql -u username -p database_name < database/product_relationships.sql
   ```

2. Include the class in your code:
   ```php
   require_once 'includes/ProductRelationships.php';
   ```

3. Test recommendations:
   ```php
   $relationships = new ProductRelationships();
   $recommendations = $relationships->getAllRecommendations(1, 'related', 6);
   var_dump($recommendations);
   ```

### Adding to Existing Products
```php
// Example: Add accessories for a plotter
$plotterId = 5;
$inkCartridges = [10, 11, 12];
$relationships->bulkAddRelationships($plotterId, $inkCartridges, 'accessory');

// Add premium upsell
$relationships->addRelationship($plotterId, 8, 'upsell', 1);
```

## Testing

### Test Cases:
1. **Empty Relationships**: Should show smart recommendations
2. **Partial Relationships**: Should mix manual + smart recommendations
3. **Full Relationships**: Should show only manual recommendations
4. **Out of Stock**: Should exclude unavailable products
5. **Same Category**: Should prioritize category matches
6. **Price Range**: Should filter by appropriate price range

## Support

For issues or questions:
- Check database indexes are created
- Verify ProductRelationships.php is included
- Ensure products table has required fields
- Check error logs for SQL errors

## Changelog

### Version 1.0 (November 2025)
- Initial release
- Smart recommendation algorithm
- Three relationship types
- Responsive design
- Admin-ready architecture
