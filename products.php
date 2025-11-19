<?php
if (!defined('JINKA_ACCESS')) {
    define('JINKA_ACCESS', true);
}
require_once 'includes/config.php';
require_once 'includes/Cart.php';

$db = Database::getInstance()->getConnection();
$cart = new Cart();

$site_name = site_setting('site_name', 'ProCut Solutions');
$site_logo = site_setting('site_logo', '');
$site_favicon_setting = trim(site_setting('site_favicon', ''));
$default_favicon_path = 'images/favicon.ico';
$site_favicon = '';

if ($site_favicon_setting !== '') {
    if (preg_match('#^https?://#i', $site_favicon_setting)) {
        $site_favicon = $site_favicon_setting;
    } else {
        $site_favicon = site_url($site_favicon_setting);
    }
} elseif (file_exists(__DIR__ . '/' . $default_favicon_path)) {
    $site_favicon = site_url($default_favicon_path);
}
$site_tagline = site_setting('site_tagline', 'Professional Printing Equipment');
$contact_phone = site_setting('contact_phone', '+255753098911');
$contact_phone_ke = site_setting('contact_phone_ke', $contact_phone);
$whatsapp_number = site_setting('whatsapp_number', '+255753098911');

$contact_phone_link = preg_replace('/[^0-9+]/', '', $contact_phone);
if ($contact_phone_link !== '' && $contact_phone_link[0] !== '+') {
    $contact_phone_link = '+' . ltrim($contact_phone_link, '+');
}

$contact_phone_ke_link = preg_replace('/[^0-9+]/', '', $contact_phone_ke);
if ($contact_phone_ke_link !== '' && $contact_phone_ke_link[0] !== '+') {
    $contact_phone_ke_link = '+' . ltrim($contact_phone_ke_link, '+');
}

$whatsapp_number_link = preg_replace('/[^0-9]/', '', $whatsapp_number);

// Get active categories
$categories_query = "SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order ASC, name ASC";
$categories_result = $db->query($categories_query);
$categories = [];
while ($row = $categories_result->fetch_assoc()) {
    $categories[] = $row;
}

// Get filter parameters
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : null;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

// Build products query
$query = "SELECT p.*, c.name as category_name 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          WHERE p.is_active = 1";

if ($category_filter) {
    $query .= " AND p.category_id = " . $category_filter;
}

if ($search) {
    $search_term = $db->real_escape_string($search);
    $query .= " AND (p.name LIKE '%$search_term%' 
                OR p.short_description LIKE '%$search_term%' 
                OR p.description LIKE '%$search_term%'
                OR p.sku LIKE '%$search_term%')";
}

// Add sorting
switch ($sort) {
    case 'price_low':
        $query .= " ORDER BY p.price_kes ASC";
        break;
    case 'price_high':
        $query .= " ORDER BY p.price_kes DESC";
        break;
    case 'name':
        $query .= " ORDER BY p.name ASC";
        break;
    case 'featured':
        $query .= " ORDER BY p.is_featured DESC, p.created_at DESC";
        break;
    default: // newest
        $query .= " ORDER BY p.created_at DESC";
}

$products_result = $db->query($query);
$products = [];
while ($row = $products_result->fetch_assoc()) {
    $products[] = $row;
}

$page_title = $site_name . " | Products";
$page_description = "Browse our selection of professional printing equipment including cutting plotters, vinyl cutters, and sign making machines for Kenya and Tanzania.";

$site_logo_absolute = '';
if (!empty($site_logo)) {
    $site_logo_absolute = site_url($site_logo);
}
if (empty($site_logo_absolute)) {
    $site_logo_absolute = site_url('images/plotter-hero.webp');
}

$canonical_url = site_url('products.php');
$og_image = $site_logo_absolute;

$product_entities = [];
if (!empty($products)) {
    $sample_products = array_slice($products, 0, 6);
    foreach ($sample_products as $sampleProduct) {
        $product_url = site_url('product-detail.php?slug=' . urlencode($sampleProduct['slug']));
        $product_entities[] = [
            '@type' => 'Product',
            'name' => $sampleProduct['name'],
            'sku' => $sampleProduct['sku'],
            'url' => $product_url
        ];
    }
}

$collection_schema = [
    '@type' => 'CollectionPage',
    '@id' => $canonical_url . '#collection',
    'name' => $page_title,
    'url' => $canonical_url,
    'description' => $page_description,
    'isPartOf' => ['@id' => site_url('#website')],
    'image' => $og_image,
    'numberOfItems' => count($products)
];

if (!empty($product_entities)) {
    $collection_schema['mainEntity'] = $product_entities;
}

$breadcrumb_schema = [
    '@type' => 'BreadcrumbList',
    '@id' => $canonical_url . '#breadcrumb',
    'itemListElement' => [
        [
            '@type' => 'ListItem',
            'position' => 1,
            'item' => [
                '@id' => site_url('/'),
                'name' => 'Home'
            ]
        ],
        [
            '@type' => 'ListItem',
            'position' => 2,
            'item' => [
                '@id' => $canonical_url,
                'name' => 'Products'
            ]
        ]
    ]
];

$structured_data_graph = [
    '@context' => 'https://schema.org',
    '@graph' => [
        $collection_schema,
        $breadcrumb_schema
    ]
];

$structured_data_json = json_encode($structured_data_graph, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($page_description); ?>">
    <meta name="robots" content="index,follow">
    <link rel="canonical" href="<?php echo htmlspecialchars($canonical_url); ?>">
    <meta property="og:title" content="<?php echo htmlspecialchars($page_title); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($page_description); ?>">
    <meta property="og:url" content="<?php echo htmlspecialchars($canonical_url); ?>">
    <meta property="og:site_name" content="<?php echo htmlspecialchars($site_name); ?>">
    <meta property="og:type" content="website">
    <?php if (!empty($og_image)): ?>
    <meta property="og:image" content="<?php echo htmlspecialchars($og_image); ?>">
    <?php endif; ?>
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($page_title); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($page_description); ?>">
    <?php if (!empty($og_image)): ?>
    <meta name="twitter:image" content="<?php echo htmlspecialchars($og_image); ?>">
    <?php endif; ?>
    <?php if (!empty($site_favicon)): ?>
    <link rel="icon" href="<?php echo htmlspecialchars($site_favicon); ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <?php if (!empty($structured_data_json)): ?>
    <script type="application/ld+json">
        <?php echo $structured_data_json; ?>
    </script>
    <?php endif; ?>
    <style>
        /* Breadcrumb */
        .breadcrumb {
            background: white;
            padding: 1rem 0;
            margin-bottom: 2rem;
            border-bottom: 1px solid #e5e7eb;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .breadcrumb-list {
            display: flex;
            gap: 0.5rem;
            align-items: center;
            list-style: none;
            margin: 0;
            padding: 0;
            font-size: 0.95rem;
        }

        .breadcrumb-list li {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .breadcrumb-list a {
            color: #2563eb;
            text-decoration: none;
            transition: all 0.2s;
        }

        .breadcrumb-list a:hover {
            color: #1d4ed8;
            text-decoration: underline;
        }

        .breadcrumb-separator {
            color: #94a3b8;
        }

        /* Products Page Styles */
        .products-page {
            padding: 3rem 0;
            background: linear-gradient(180deg, #f8f9fa 0%, #ffffff 100%);
        }

        .page-header {
            text-align: center;
            margin-bottom: 3rem;
            padding: 3rem 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 16px;
        }

        .page-header h1 {
            font-size: 2.5rem;
            font-weight: 800;
            color: white;
            margin-bottom: 1rem;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .page-header p {
            font-size: 1.125rem;
            color: rgba(255, 255, 255, 0.95);
            max-width: 600px;
            margin: 0 auto;
        }

        .products-controls {
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .search-box {
            flex: 1;
            min-width: 250px;
        }

        .search-box input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 3rem;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="%23475569" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.35-4.35"></path></svg>') no-repeat 1rem center;
        }

        .search-box input:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .filter-group {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .filter-group label {
            font-weight: 600;
            color: #475569;
            white-space: nowrap;
        }

        .filter-group select {
            padding: 0.75rem 2.5rem 0.75rem 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 1rem;
            background: white url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="%23475569" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>') no-repeat right 0.75rem center;
            appearance: none;
            cursor: pointer;
            transition: all 0.3s;
        }

        .filter-group select:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .results-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding: 0 0.5rem;
        }

        .results-count {
            font-size: 0.95rem;
            color: #64748b;
        }

        .view-toggle {
            display: flex;
            gap: 0.5rem;
        }

        .view-btn {
            padding: 0.5rem;
            border: 2px solid #e5e7eb;
            background: white;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .view-btn.active,
        .view-btn:hover {
            border-color: #2563eb;
            background: #eff6ff;
        }

        .no-products {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 12px;
        }

        .no-products svg {
            opacity: 0.3;
            margin-bottom: 1.5rem;
        }

        .no-products h3 {
            font-size: 1.5rem;
            color: #475569;
            margin-bottom: 1rem;
        }

        .no-products p {
            color: #94a3b8;
            margin-bottom: 2rem;
        }

        @media (max-width: 768px) {
            .products-controls {
                flex-direction: column;
            }

            .filter-group {
                width: 100%;
                flex-direction: column;
                align-items: stretch;
            }

            .filter-group select {
                width: 100%;
            }

            .page-header h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <!-- Breadcrumb -->
    <div class="breadcrumb">
        <div class="container">
            <ul class="breadcrumb-list">
                <li><a href="index.php">Home</a></li>
                <li class="breadcrumb-separator">›</li>
                <li>Products</li>
                <?php if ($category_filter): ?>
                    <?php 
                    $current_category = null;
                    foreach ($categories as $cat) {
                        if ($cat['id'] == $category_filter) {
                            $current_category = $cat;
                            break;
                        }
                    }
                    if ($current_category):
                    ?>
                    <li class="breadcrumb-separator">›</li>
                    <li><?php echo htmlspecialchars($current_category['name']); ?></li>
                    <?php endif; ?>
                <?php endif; ?>
            </ul>
        </div>
    </div>

    <!-- Page Header -->
    <div class="page-header">
        <div class="container">
            <h1>Professional Printing Equipment</h1>
            <p>Quality cutting plotters, vinyl cutters, and sign making equipment for your business</p>
        </div>
    </div>

    <!-- Products Section -->
    <section class="products-page">
        <div class="container">
            <!-- Search & Filters -->
            <form method="GET" class="products-controls">
                <div class="search-box">
                    <input type="text" name="search" placeholder="Search products..." value="<?php echo htmlspecialchars($search); ?>">
                </div>

                <div class="filter-group">
                    <label for="category">Category:</label>
                    <select name="category" id="category" onchange="this.form.submit()">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo $category_filter == $cat['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="sort">Sort by:</label>
                    <select name="sort" id="sort" onchange="this.form.submit()">
                        <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>Newest First</option>
                        <option value="featured" <?php echo $sort == 'featured' ? 'selected' : ''; ?>>Featured</option>
                        <option value="price_low" <?php echo $sort == 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                        <option value="price_high" <?php echo $sort == 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                        <option value="name" <?php echo $sort == 'name' ? 'selected' : ''; ?>>Name A-Z</option>
                    </select>
                </div>
            </form>

            <!-- Results Info -->
            <div class="results-info">
                <div class="results-count">
                    Showing <strong><?php echo count($products); ?></strong> product<?php echo count($products) != 1 ? 's' : ''; ?>
                </div>
            </div>

            <!-- Products Grid -->
            <?php if (count($products) > 0): ?>
                <div class="products-grid">
                    <?php foreach ($products as $product): 
                        // Parse JSON data
                        $features = !empty($product['features']) ? json_decode($product['features'], true) : [];
                        $specifications = !empty($product['specifications']) ? json_decode($product['specifications'], true) : [];
                        
                        // Determine stock status
                        $stock_qty = (int)$product['stock_quantity'];
                        $stock_class = 'out-of-stock';
                        $stock_text = 'Out of Stock';
                        
                        if ($stock_qty > 10) {
                            $stock_class = 'in-stock';
                            $stock_text = 'In Stock';
                        } elseif ($stock_qty > 0) {
                            $stock_class = 'low-stock';
                            $stock_text = 'Low Stock';
                        }
                        
                        // Check if product is new (created within last 30 days)
                        $created = strtotime($product['created_at']);
                        $is_new = (time() - $created) < (30 * 24 * 60 * 60);
                    ?>
                        <div class="product-card">
                            <div class="product-image">
                                <a href="product-detail.php?slug=<?php echo urlencode($product['slug']); ?>">
                                    <?php 
                                        $productCardImage = normalize_product_image_url($product['image'] ?? '');
                                    ?>
                                    <?php if (!empty($productCardImage)): ?>
                                        <img src="<?php echo htmlspecialchars($productCardImage); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                    <?php else: ?>
                                        <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='400' height='300' viewBox='0 0 400 300'%3E%3Crect fill='%23f1f5f9' width='400' height='300'/%3E%3Ctext x='50%25' y='50%25' dominant-baseline='middle' text-anchor='middle' fill='%2394a3b8' font-family='Arial' font-size='18' font-weight='bold'%3ENo Image%3C/text%3E%3C/svg%3E" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                    <?php endif; ?>
                                </a>
                                
                                <?php if ($product['is_featured']): ?>
                                    <div class="product-badge featured">Featured</div>
                                <?php elseif ($is_new): ?>
                                    <div class="product-badge new">New</div>
                                <?php endif; ?>
                            </div>

                            <div class="product-info">
                                <?php if (!empty($product['category_name'])): ?>
                                    <div class="product-category"><?php echo htmlspecialchars($product['category_name']); ?></div>
                                <?php else: ?>
                                    <div class="product-category">Equipment</div>
                                <?php endif; ?>
                                
                                <h3 class="product-title">
                                    <a href="product-detail.php?slug=<?php echo urlencode($product['slug']); ?>">
                                        <?php echo htmlspecialchars($product['name']); ?>
                                    </a>
                                </h3>

                                <?php if (!empty($product['short_description'])): ?>
                                    <p class="product-description">
                                        <?php echo htmlspecialchars(substr($product['short_description'], 0, 80)) . (strlen($product['short_description']) > 80 ? '...' : ''); ?>
                                    </p>
                                <?php endif; ?>

                                <div class="product-meta">
                                    <span class="stock-badge <?php echo $stock_class; ?>">
                                        <?php echo $stock_text; ?>
                                    </span>
                                    <span style="color: #9ca3af; font-size: 0.75rem;">SKU: <?php echo htmlspecialchars($product['sku']); ?></span>
                                </div>
                            </div>

                            <div class="product-pricing">
                                <div class="price-row">
                                    <div class="price">
                                        <span class="currency">KES</span>
                                        <?php echo number_format($product['price_kes'], 0); ?>
                                    </div>
                                    <div class="price-secondary">
                                        <span class="currency">TZS</span>
                                        <?php echo number_format($product['price_tzs'], 0); ?>
                                    </div>
                                </div>

                                <div class="product-actions">
                                    <?php if ($stock_qty > 0 || $product['allow_backorder']): ?>
                                    <a href="product-detail.php?slug=<?php echo urlencode($product['slug']); ?>" class="btn btn-primary">
                                        View Details
                                    </a>
                                    <a href="https://wa.me/<?php echo str_replace('+', '', $whatsapp_number); ?>?text=Hi, I'm interested in <?php echo urlencode($product['name']); ?>" 
                                       class="btn btn-outline" target="_blank" title="Inquire on WhatsApp">
                                        <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                                        </svg>
                                    </a>
                                    <?php else: ?>
                                    <button class="btn btn-outline" disabled style="opacity: 0.5;">Out of Stock</button>
                                    <a href="product-detail.php?slug=<?php echo urlencode($product['slug']); ?>" class="btn btn-outline">
                                        View Details
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-products">
                    <svg width="120" height="120" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/>
                    </svg>
                    <h3>No Products Found</h3>
                    <p>Try adjusting your search or filter criteria</p>
                    <a href="products.php" class="btn btn-primary">View All Products</a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-col">
                    <h3>ProCut Solutions</h3>
                    <p>Professional printing equipment supplier serving Kenya and Tanzania. Quality products, expert support, and competitive pricing.</p>
                </div>
                <div class="footer-col">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="index.php">Home</a></li>
                        <li><a href="products.php">Products</a></li>
                        <li><a href="index.php#contact">Contact</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Contact</h4>
                    <ul>
                        <li>Tanzania: <a href="tel:+255753098911">+255 753 098 911</a></li>
                        <li>Kenya: <a href="tel:+254716522828">+254 716 522 828</a></li>
                        <li><a href="mailto:support@procutsolutions.com">support@procutsolutions.com</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Business Hours</h4>
                    <ul>
                        <li>Monday - Friday: 8am - 6pm</li>
                        <li>Saturday: 9am - 4pm</li>
                        <li>Sunday: Closed</li>
                        <li>WhatsApp: 24/7</li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> ProCut Solutions. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // Add to Cart functionality
        document.querySelectorAll('.add-to-cart-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const productId = this.dataset.productId;
                const productName = this.dataset.productName;
                const originalText = this.innerHTML;
                
                // Disable button and show loading
                this.disabled = true;
                this.innerHTML = 'Adding...';
                
                fetch('cart_handler.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `action=add&product_id=${productId}&quantity=1`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update cart count
                        document.getElementById('cart-count').textContent = data.cart_count;
                        
                        // Show success feedback
                        this.innerHTML = '✓ Added!';
                        this.style.background = '#10b981';
                        
                        // Reset button after 2 seconds
                        setTimeout(() => {
                            this.innerHTML = originalText;
                            this.disabled = false;
                            this.style.background = '';
                        }, 2000);
                        
                        // Show notification
                        showNotification(`${productName} added to cart!`, 'success');
                    } else {
                        this.innerHTML = originalText;
                        this.disabled = false;
                        showNotification(data.message, 'error');
                    }
                })
                .catch(error => {
                    this.innerHTML = originalText;
                    this.disabled = false;
                    showNotification('Error adding to cart', 'error');
                });
            });
        });

        // Simple notification function
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: ${type === 'success' ? '#10b981' : '#ef4444'};
                color: white;
                padding: 1rem 1.5rem;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                z-index: 10000;
                animation: slideIn 0.3s ease;
            `;
            notification.textContent = message;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }

        // Add animation styles
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from { transform: translateX(400px); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOut {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(400px); opacity: 0; }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>
