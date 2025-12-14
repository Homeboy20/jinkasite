<?php
/**
 * Shopping Cart Class
 * 
 * Manages session-based shopping cart for multiple products
 * 
 * @author ProCut Solutions
 * @version 1.0
 */

if (!defined('JINKA_ACCESS')) {
    define('JINKA_ACCESS', true);
}

class Cart {
    private $db;
    private $sessionKey = 'shopping_cart';
    private $productCache = [];
    private $currencyDetector;
    
    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $this->db = Database::getInstance()->getConnection();
        $this->currencyDetector = CurrencyDetector::getInstance();
        
        // Initialize cart if not exists
        if (!isset($_SESSION[$this->sessionKey])) {
            $_SESSION[$this->sessionKey] = [];
        }
    }
    
    /**
     * Add product to cart
     */
    public function addProduct($productId, $quantity = 1) {
        $productId = (int)$productId;
        $quantity = (int)$quantity;
        
        if ($quantity <= 0) {
            return ['success' => false, 'message' => 'Invalid quantity'];
        }
        
        // Get product details
        $product = $this->getProductDetails($productId);
        
        if (!$product) {
            return ['success' => false, 'message' => 'Product not found'];
        }
        
        if (!$product['is_active']) {
            return ['success' => false, 'message' => 'Product is not available'];
        }
        
        // Check stock if tracking is enabled
        if ($product['track_stock']) {
            $currentCartQty = isset($_SESSION[$this->sessionKey][$productId]) ? 
                            $_SESSION[$this->sessionKey][$productId]['quantity'] : 0;
            $totalQty = $currentCartQty + $quantity;
            
            if ($totalQty > $product['stock_quantity']) {
                if (!$product['allow_backorder']) {
                    return [
                        'success' => false, 
                        'message' => 'Insufficient stock. Only ' . $product['stock_quantity'] . ' available'
                    ];
                }
            }
        }
        
        // Add or update cart
        if (isset($_SESSION[$this->sessionKey][$productId])) {
            $_SESSION[$this->sessionKey][$productId]['quantity'] += $quantity;
        } else {
            $_SESSION[$this->sessionKey][$productId] = [
                'product_id' => $productId,
                'name' => $product['name'],
                'slug' => $product['slug'],
                'sku' => $product['sku'],
                'price_kes' => (float)$product['price_kes'],
                'price_tzs' => (float)$product['price_tzs'],
                'image' => normalize_product_image_url($product['image'] ?? ''),
                'quantity' => $quantity,
                'stock_quantity' => $product['stock_quantity'],
                'track_stock' => $product['track_stock']
            ];
        }
        
        return [
            'success' => true, 
            'message' => 'Product added to cart',
            'cart_count' => $this->getItemCount(),
            'totals' => $this->getTotals(),
            'item' => $_SESSION[$this->sessionKey][$productId]
        ];
    }
    
    /**
     * Update product quantity
     */
    public function updateQuantity($productId, $quantity) {
        $productId = (int)$productId;
        $quantity = (int)$quantity;
        
        if (!isset($_SESSION[$this->sessionKey][$productId])) {
            return ['success' => false, 'message' => 'Product not in cart'];
        }
        
        if ($quantity <= 0) {
            return $this->removeProduct($productId);
        }
        
        // Check stock
        $product = $this->getProductDetails($productId);
        if ($product && $product['track_stock'] && $quantity > $product['stock_quantity']) {
            if (!$product['allow_backorder']) {
                return [
                    'success' => false,
                    'message' => 'Insufficient stock. Only ' . $product['stock_quantity'] . ' available'
                ];
            }
        }
        
        $_SESSION[$this->sessionKey][$productId]['quantity'] = $quantity;
        
        return [
            'success' => true,
            'message' => 'Cart updated',
            'cart_count' => $this->getItemCount(),
            'totals' => $this->getTotals(),
            'item' => $_SESSION[$this->sessionKey][$productId]
        ];
    }
    
    /**
     * Remove product from cart
     */
    public function removeProduct($productId) {
        $productId = (int)$productId;
        
        if (isset($_SESSION[$this->sessionKey][$productId])) {
            unset($_SESSION[$this->sessionKey][$productId]);
            return [
                'success' => true,
                'message' => 'Product removed from cart',
                'cart_count' => $this->getItemCount(),
                'totals' => $this->getTotals(),
                'removed_product_id' => $productId
            ];
        }
        
        return ['success' => false, 'message' => 'Product not in cart'];
    }
    
    /**
     * Clear entire cart
     */
    public function clearCart() {
        $_SESSION[$this->sessionKey] = [];
        return [
            'success' => true,
            'message' => 'Cart cleared',
            'cart_count' => 0,
            'totals' => $this->getTotals()
        ];
    }
    
    /**
     * Get all cart items
     */
    public function getItems() {
        return $_SESSION[$this->sessionKey] ?? [];
    }
    
    /**
     * Get total number of items in cart
     */
    public function getItemCount() {
        $count = 0;
        foreach ($_SESSION[$this->sessionKey] as $item) {
            $count += $item['quantity'];
        }
        return $count;
    }
    
    /**
     * Get cart totals in current currency
     */
    public function getTotals() {
        $currency = $this->currencyDetector->getCurrency();
        $subtotal_kes = 0.0;
        $subtotal_tzs = 0.0;
        
        foreach ($_SESSION[$this->sessionKey] as $item) {
            $subtotal_kes += $item['price_kes'] * $item['quantity'];
            $subtotal_tzs += $item['price_tzs'] * $item['quantity'];
        }
        
        // Calculate tax based on currency
        $taxRate = match($currency) {
            'KES' => 0.16, // 16% VAT Kenya
            'TZS' => 0.18, // 18% VAT Tanzania
            'UGX' => 0.18, // 18% VAT Uganda
            'USD' => 0.00, // No tax for international
            default => 0.16
        };
        
        // Convert to current currency
        $subtotal = $this->currencyDetector->getPrice($subtotal_kes, $currency);
        $tax = $subtotal * $taxRate;
        $total = $subtotal + $tax;
        
        return [
            'currency' => $currency,
            'symbol' => $this->currencyDetector->getCurrencyDetails()['symbol'] ?? '$',
            'subtotal' => $subtotal,
            'tax' => $tax,
            'tax_rate' => $taxRate,
            'total' => $total,
            'item_count' => $this->getItemCount(),
            // Keep legacy format for backward compatibility
            'kes' => [
                'subtotal' => $subtotal_kes,
                'tax' => $subtotal_kes * 0.16,
                'total' => $subtotal_kes + ($subtotal_kes * 0.16)
            ],
            'tzs' => [
                'subtotal' => $subtotal_tzs,
                'tax' => $subtotal_tzs * 0.18,
                'total' => $subtotal_tzs + ($subtotal_tzs * 0.18)
            ]
        ];
    }
    
    /**
     * Validate cart items against current stock and prices
     */
    public function validateCart() {
        $errors = [];
        $updated = false;
        
        $productIds = array_keys($_SESSION[$this->sessionKey]);
        $products = $this->getProductsByIds($productIds);

        foreach ($_SESSION[$this->sessionKey] as $productId => $item) {
            $product = $products[$productId] ?? null;
            
            if (!$product) {
                $errors[] = $item['name'] . ' is no longer available';
                unset($_SESSION[$this->sessionKey][$productId]);
                $updated = true;
                continue;
            }
            
            if (!$product['is_active']) {
                $errors[] = $item['name'] . ' is no longer available';
                unset($_SESSION[$this->sessionKey][$productId]);
                $updated = true;
                continue;
            }
            
            // Check stock
            if ($product['track_stock'] && $item['quantity'] > $product['stock_quantity']) {
                if (!$product['allow_backorder']) {
                    $errors[] = $item['name'] . ' - only ' . $product['stock_quantity'] . ' available';
                    $_SESSION[$this->sessionKey][$productId]['quantity'] = $product['stock_quantity'];
                    $updated = true;
                }
            }
            
            // Update prices if changed
            if ($item['price_kes'] != $product['price_kes'] || 
                $item['price_tzs'] != $product['price_tzs']) {
                $_SESSION[$this->sessionKey][$productId]['price_kes'] = (float)$product['price_kes'];
                $_SESSION[$this->sessionKey][$productId]['price_tzs'] = (float)$product['price_tzs'];
                $updated = true;
                $errors[] = $item['name'] . ' price has been updated';
            }

            // Sync stock tracking flags and image preview for accuracy
            $_SESSION[$this->sessionKey][$productId]['stock_quantity'] = $product['stock_quantity'];
            $_SESSION[$this->sessionKey][$productId]['track_stock'] = $product['track_stock'];
            $_SESSION[$this->sessionKey][$productId]['image'] = normalize_product_image_url($product['image'] ?? '');
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'updated' => $updated
        ];
    }
    
    /**
     * Get product details from database
     */
    private function getProductDetails($productId) {
        $productId = (int)$productId;
        if (isset($this->productCache[$productId])) {
            return $this->productCache[$productId];
        }

        $stmt = $this->db->prepare("SELECT * FROM products WHERE id = ? LIMIT 1");
        $stmt->bind_param('i', $productId);
        $stmt->execute();
        $result = $stmt->get_result();
        $product = $result->fetch_assoc();
        if ($product) {
            $this->productCache[$productId] = $product;
        }
        return $product;
    }

    /**
     * Load multiple product records at once and hydrate cache
     */
    private function getProductsByIds(array $productIds) {
        $productIds = array_values(array_unique(array_map('intval', $productIds)));
        if (empty($productIds)) {
            return [];
        }

        $uncached = array_diff($productIds, array_keys($this->productCache));
        if (!empty($uncached)) {
            $idList = implode(',', $uncached);
            $query = "SELECT * FROM products WHERE id IN ($idList)";
            $result = $this->db->query($query);
            if ($result) {
                while ($product = $result->fetch_assoc()) {
                    $this->productCache[(int)$product['id']] = $product;
                }
            }
        }

        $indexed = [];
        foreach ($productIds as $productId) {
            if (isset($this->productCache[$productId])) {
                $indexed[$productId] = $this->productCache[$productId];
            }
        }

        return $indexed;
    }
    
    /**
     * Check if product is in cart
     */
    public function hasProduct($productId) {
        return isset($_SESSION[$this->sessionKey][$productId]);
    }
    
    /**
     * Get quantity of specific product in cart
     */
    public function getProductQuantity($productId) {
        return $_SESSION[$this->sessionKey][$productId]['quantity'] ?? 0;
    }
}
