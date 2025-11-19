<?php
/**
 * Product Relationships Manager
 * Handles related products, upsells, cross-sells, and accessories
 */

class ProductRelationships {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Add a product relationship
     * 
     * @param int $productId Main product ID
     * @param int $relatedProductId Related product ID
     * @param string $type Relationship type (related, upsell, cross_sell, accessory, bundle)
     * @param int $displayOrder Display order (optional)
     * @return bool Success status
     */
    public function addRelationship($productId, $relatedProductId, $type = 'related', $displayOrder = 0) {
        // Prevent self-referencing
        if ($productId == $relatedProductId) {
            return false;
        }
        
        $stmt = $this->db->prepare("
            INSERT INTO product_relationships 
            (product_id, related_product_id, relationship_type, display_order) 
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            display_order = VALUES(display_order),
            is_active = 1
        ");
        
        $stmt->bind_param('iisi', $productId, $relatedProductId, $type, $displayOrder);
        return $stmt->execute();
    }
    
    /**
     * Remove a product relationship
     */
    public function removeRelationship($productId, $relatedProductId, $type = null) {
        if ($type) {
            $stmt = $this->db->prepare("
                DELETE FROM product_relationships 
                WHERE product_id = ? AND related_product_id = ? AND relationship_type = ?
            ");
            $stmt->bind_param('iis', $productId, $relatedProductId, $type);
        } else {
            $stmt = $this->db->prepare("
                DELETE FROM product_relationships 
                WHERE product_id = ? AND related_product_id = ?
            ");
            $stmt->bind_param('ii', $productId, $relatedProductId);
        }
        
        return $stmt->execute();
    }
    
    /**
     * Get related products for a specific product
     * 
     * @param int $productId Main product ID
     * @param string $type Relationship type (optional, null for all types)
     * @param int $limit Maximum number of products to return
     * @return array Array of related products with full details
     */
    public function getRelatedProducts($productId, $type = null, $limit = 6) {
        $sql = "
            SELECT 
                p.*,
                pr.relationship_type,
                pr.display_order,
                c.name as category_name
            FROM product_relationships pr
            INNER JOIN products p ON pr.related_product_id = p.id
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE pr.product_id = ?
            AND pr.is_active = 1
            AND p.is_active = 1
        ";
        
        if ($type) {
            $sql .= " AND pr.relationship_type = ?";
        }
        
        $sql .= " ORDER BY pr.display_order ASC, p.name ASC LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        
        if ($type) {
            $stmt->bind_param('isi', $productId, $type, $limit);
        } else {
            $stmt->bind_param('ii', $productId, $limit);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $products = [];
        while ($row = $result->fetch_assoc()) {
            // Parse JSON fields
            if ($row['specifications']) {
                $row['specifications'] = json_decode($row['specifications'], true);
            }
            if ($row['features']) {
                $row['features'] = json_decode($row['features'], true);
            }
            if ($row['images']) {
                $row['images'] = json_decode($row['images'], true);
            }
            $products[] = $row;
        }
        
        return $products;
    }
    
    /**
     * Get smart recommendations based on category, price range, and features
     * When no manual relationships exist
     */
    public function getSmartRecommendations($productId, $limit = 6) {
        // First get the main product details
        $stmt = $this->db->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->bind_param('i', $productId);
        $stmt->execute();
        $product = $stmt->get_result()->fetch_assoc();
        
        if (!$product) {
            return [];
        }
        
        // Get similar products based on:
        // 1. Same category
        // 2. Similar price range (+/- 30%)
        // 3. Exclude the current product
        // 4. Active products only
        
        $minPrice = $product['price_kes'] * 0.7;
        $maxPrice = $product['price_kes'] * 1.3;
        
        $sql = "
            SELECT 
                p.*,
                c.name as category_name,
                'smart_recommendation' as relationship_type
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.id != ?
            AND p.is_active = 1
            AND p.stock_quantity > 0
            AND (
                p.category_id = ?
                OR (p.price_kes BETWEEN ? AND ?)
            )
            ORDER BY 
                CASE WHEN p.category_id = ? THEN 1 ELSE 2 END,
                p.is_featured DESC,
                ABS(p.price_kes - ?) ASC,
                p.created_at DESC
            LIMIT ?
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param(
            'iiddidi',
            $productId,
            $product['category_id'],
            $minPrice,
            $maxPrice,
            $product['category_id'],
            $product['price_kes'],
            $limit
        );
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $products = [];
        while ($row = $result->fetch_assoc()) {
            // Parse JSON fields
            if ($row['specifications']) {
                $row['specifications'] = json_decode($row['specifications'], true);
            }
            if ($row['features']) {
                $row['features'] = json_decode($row['features'], true);
            }
            if ($row['images']) {
                $row['images'] = json_decode($row['images'], true);
            }
            $products[] = $row;
        }
        
        return $products;
    }
    
    /**
     * Get all related products (manual + smart recommendations)
     */
    public function getAllRecommendations($productId, $type = null, $limit = 6) {
        // First try to get manual relationships
        $manualProducts = $this->getRelatedProducts($productId, $type, $limit);
        
        // If we don't have enough manual relationships, fill with smart recommendations
        if (count($manualProducts) < $limit) {
            $remaining = $limit - count($manualProducts);
            $smartProducts = $this->getSmartRecommendations($productId, $remaining);
            
            // Merge and avoid duplicates
            $existingIds = array_column($manualProducts, 'id');
            foreach ($smartProducts as $smartProduct) {
                if (!in_array($smartProduct['id'], $existingIds)) {
                    $manualProducts[] = $smartProduct;
                }
            }
        }
        
        return array_slice($manualProducts, 0, $limit);
    }
    
    /**
     * Get all relationships for a product (for admin management)
     */
    public function getProductRelationships($productId) {
        $stmt = $this->db->prepare("
            SELECT 
                pr.*,
                p.name as related_product_name,
                p.sku as related_product_sku,
                p.price_kes,
                p.is_active as related_is_active
            FROM product_relationships pr
            INNER JOIN products p ON pr.related_product_id = p.id
            WHERE pr.product_id = ?
            ORDER BY pr.relationship_type, pr.display_order
        ");
        
        $stmt->bind_param('i', $productId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Update relationship display order
     */
    public function updateDisplayOrder($relationshipId, $displayOrder) {
        $stmt = $this->db->prepare("
            UPDATE product_relationships 
            SET display_order = ? 
            WHERE id = ?
        ");
        
        $stmt->bind_param('ii', $displayOrder, $relationshipId);
        return $stmt->execute();
    }
    
    /**
     * Bulk add relationships
     */
    public function bulkAddRelationships($productId, $relatedProductIds, $type = 'related') {
        $success = true;
        
        foreach ($relatedProductIds as $index => $relatedId) {
            if (!$this->addRelationship($productId, $relatedId, $type, $index)) {
                $success = false;
            }
        }
        
        return $success;
    }
    
    /**
     * Get popular products for upselling
     */
    public function getPopularProducts($limit = 6, $excludeId = null) {
        $sql = "
            SELECT 
                p.*,
                c.name as category_name,
                COUNT(oi.id) as order_count
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN order_items oi ON p.id = oi.product_id
            WHERE p.is_active = 1
            AND p.stock_quantity > 0
        ";
        
        if ($excludeId) {
            $sql .= " AND p.id != ?";
        }
        
        $sql .= "
            GROUP BY p.id
            ORDER BY order_count DESC, p.is_featured DESC, p.created_at DESC
            LIMIT ?
        ";
        
        $stmt = $this->db->prepare($sql);
        
        if ($excludeId) {
            $stmt->bind_param('ii', $excludeId, $limit);
        } else {
            $stmt->bind_param('i', $limit);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $products = [];
        while ($row = $result->fetch_assoc()) {
            if ($row['specifications']) {
                $row['specifications'] = json_decode($row['specifications'], true);
            }
            if ($row['features']) {
                $row['features'] = json_decode($row['features'], true);
            }
            if ($row['images']) {
                $row['images'] = json_decode($row['images'], true);
            }
            $products[] = $row;
        }
        
        return $products;
    }
}
