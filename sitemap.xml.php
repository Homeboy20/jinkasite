<?php
/**
 * Dynamic XML Sitemap Generator
 * Automatically generates sitemap from database
 */

define('JINKA_ACCESS', true);
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/seo-helpers.php';

header('Content-Type: application/xml; charset=utf-8');

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

// Homepage
echo generate_sitemap_url(SITE_URL, null, 'daily', '1.0');

// Static pages
$static_pages = [
    ['url' => 'products', 'changefreq' => 'daily', 'priority' => '0.9'],
    ['url' => 'contact', 'changefreq' => 'monthly', 'priority' => '0.7'],
    ['url' => 'cart', 'changefreq' => 'weekly', 'priority' => '0.6'],
    ['url' => 'customer-login', 'changefreq' => 'monthly', 'priority' => '0.5'],
    ['url' => 'customer-register', 'changefreq' => 'monthly', 'priority' => '0.5'],
];

foreach ($static_pages as $page) {
    echo generate_sitemap_url(
        site_url($page['url']),
        null,
        $page['changefreq'],
        $page['priority']
    );
}

// Products
try {
    $db = Database::getInstance()->getConnection();
    
    // Get all active products
    $products_query = "
        SELECT slug, updated_at 
        FROM products 
        WHERE is_active = 1 
        ORDER BY updated_at DESC
    ";
    
    $products_result = $db->query($products_query);
    
    if ($products_result) {
        while ($product = $products_result->fetch_assoc()) {
            echo generate_sitemap_url(
                site_url('product-detail/' . $product['slug']),
                $product['updated_at'],
                'weekly',
                '0.8'
            );
        }
    }
    
    // Categories
    $categories_query = "
        SELECT slug, updated_at 
        FROM categories 
        WHERE is_active = 1 AND parent_id IS NULL
        ORDER BY sort_order ASC
    ";
    
    $categories_result = $db->query($categories_query);
    
    if ($categories_result) {
        while ($category = $categories_result->fetch_assoc()) {
            echo generate_sitemap_url(
                site_url('products?category=' . $category['slug']),
                $category['updated_at'],
                'weekly',
                '0.7'
            );
        }
    }
    
} catch (Exception $e) {
    // Log error but continue
    if (LOG_ENABLED) {
        error_log('[Sitemap] Error: ' . $e->getMessage());
    }
}

echo '</urlset>';
