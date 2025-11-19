<?php
if (!defined('JINKA_ACCESS')) {
    define('JINKA_ACCESS', true);
}
require_once 'includes/config.php';
require_once 'includes/Cart.php';
require_once 'includes/ProductRelationships.php';

$db = Database::getInstance()->getConnection();
$cart = new Cart();
$relationships = new ProductRelationships();

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

// Get product by slug
$slug = isset($_GET['slug']) ? $db->real_escape_string($_GET['slug']) : '';

if (empty($slug)) {
    header('Location: products.php');
    exit;
}

$query = "SELECT p.*, c.name as category_name, c.slug as category_slug 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          WHERE p.slug = ? AND p.is_active = 1";
$stmt = $db->prepare($query);
$stmt->bind_param('s', $slug);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: products.php');
    exit;
}

$product = $result->fetch_assoc();

// Parse JSON fields
$features = !empty($product['features']) ? json_decode($product['features'], true) : [];
$specifications = !empty($product['specifications']) ? json_decode($product['specifications'], true) : [];

// Get product gallery images
$gallery_query = "SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order ASC, is_featured DESC";
$gallery_stmt = $db->prepare($gallery_query);
$gallery_stmt->bind_param('i', $product['id']);
$gallery_stmt->execute();
$gallery_result = $gallery_stmt->get_result();
$gallery_images = [];
while ($img = $gallery_result->fetch_assoc()) {
    $gallery_images[] = $img;
}

// If no gallery images, use legacy image field as fallback
if (empty($gallery_images) && !empty($product['image'])) {
    $gallery_images[] = [
        'image_path' => $product['image'],
        'is_featured' => 1,
        'sort_order' => 0,
        'alt_text' => $product['name']
    ];
}

foreach ($gallery_images as &$galleryImage) {
    $path = $galleryImage['image_path'] ?? '';
    $galleryImage['image_url'] = normalize_product_image_url($path);
    $galleryImage['image_url_absolute'] = normalize_product_image_url($path, ['absolute' => true]);
}
unset($galleryImage);

$gallery_images = array_values(array_filter($gallery_images, function ($img) {
    return !empty($img['image_url']);
}));

// Get related products using smart recommendations
$related_products = $relationships->getAllRecommendations($product['id'], 'related', 6);

// Get upsell products (higher-priced alternatives)
$upsell_products = $relationships->getAllRecommendations($product['id'], 'upsell', 4);

// Get accessories/cross-sells
$accessory_products = $relationships->getAllRecommendations($product['id'], 'accessory', 4);

// Determine stock status
$stock_qty = (int)$product['stock_quantity'];
$stock_class = 'out-of-stock';
$stock_text = 'Out of Stock';

if ($stock_qty > 10) {
    $stock_class = 'in-stock';
    $stock_text = 'In Stock';
} elseif ($stock_qty > 0) {
    $stock_class = 'low-stock';
    $stock_text = "Only $stock_qty left";
}

$page_title = htmlspecialchars($product['seo_title'] ?? $product['name']) . " | " . $site_name;
$page_description = htmlspecialchars($product['seo_description'] ?? $product['short_description']);
$meta_keywords = htmlspecialchars($product['seo_keywords'] ?? '');

$site_logo_absolute = '';
if (!empty($site_logo)) {
    $site_logo_absolute = site_url($site_logo);
}
if (empty($site_logo_absolute)) {
    $site_logo_absolute = site_url('images/plotter-hero.webp');
}

$canonical_url = site_url('product-detail.php?slug=' . urlencode($product['slug']));

$product_primary_image = '';
if (!empty($gallery_images)) {
    $product_primary_image = $gallery_images[0]['image_url_absolute'] ?? '';
}
if (empty($product_primary_image)) {
    $product_primary_image = normalize_product_image_url($product['image'] ?? '', ['absolute' => true, 'fallback' => 'images/plotter-hero.webp']);
}
if (empty($product_primary_image)) {
    $product_primary_image = $site_logo_absolute;
}

$og_image = $product_primary_image;

$availability_url = ($product['stock_quantity'] ?? 0) > 0 ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock';

$price_kes_value = isset($product['price_kes']) ? (float)$product['price_kes'] : 0;
$price_tzs_value = isset($product['price_tzs']) ? (float)$product['price_tzs'] : 0;

$offers = [];
if ($price_kes_value > 0) {
    $offers[] = [
        '@type' => 'Offer',
        'priceCurrency' => 'KES',
        'price' => number_format($price_kes_value, 2, '.', ''),
        'availability' => $availability_url,
        'url' => $canonical_url,
        'priceValidUntil' => date('Y-m-d', strtotime('+6 months')),
        'seller' => [
            '@type' => 'Organization',
            'name' => $site_name,
            'url' => site_url('/')
        ]
    ];
}

if ($price_tzs_value > 0) {
    $offers[] = [
        '@type' => 'Offer',
        'priceCurrency' => 'TZS',
        'price' => number_format($price_tzs_value, 2, '.', ''),
        'availability' => $availability_url,
        'url' => $canonical_url,
        'priceValidUntil' => date('Y-m-d', strtotime('+6 months')),
        'seller' => [
            '@type' => 'Organization',
            'name' => $site_name,
            'url' => site_url('/')
        ]
    ];
}

$video_url = trim($product['video_url'] ?? '');
$video_id = '';
if (!empty($video_url)) {
    if (preg_match('/(?:youtube\.com\/(?:watch\?v=|embed\/)|youtu\.be\/)([A-Za-z0-9_-]+)/', $video_url, $videoMatches)) {
        $video_id = $videoMatches[1];
    }
}

$product_schema = [
    '@type' => 'Product',
    '@id' => $canonical_url . '#product',
    'name' => $product['name'],
    'description' => strip_tags($product['short_description'] ?: $product['description']),
    'sku' => $product['sku'],
    'image' => array_values(array_unique(array_filter(array_merge(
        array_map(function ($img) {
            return $img['image_url_absolute'] ?? null;
        }, $gallery_images),
        [$product_primary_image]
    )))),
    'brand' => [
        '@type' => 'Brand',
        'name' => $product['brand'] ?? 'JINKA'
    ],
    'url' => $canonical_url,
    'offers' => $offers
];

if (!empty($video_id)) {
    $product_schema['video'] = [
        '@type' => 'VideoObject',
        'name' => $product['name'] . ' Demo',
        'description' => 'Product demonstration video',
        'thumbnailUrl' => 'https://img.youtube.com/vi/' . $video_id . '/hqdefault.jpg',
        'embedUrl' => 'https://www.youtube.com/embed/' . $video_id
    ];
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
                '@id' => site_url('products.php'),
                'name' => 'Products'
            ]
        ],
        [
            '@type' => 'ListItem',
            'position' => 3,
            'item' => [
                '@id' => $canonical_url,
                'name' => $product['name']
            ]
        ]
    ]
];

$structured_data_graph = [
    '@context' => 'https://schema.org',
    '@graph' => [
        $product_schema,
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
    <title><?php echo $page_title; ?></title>
    <meta name="description" content="<?php echo $page_description; ?>">
    <?php if (!empty($meta_keywords)): ?>
    <meta name="keywords" content="<?php echo $meta_keywords; ?>">
    <?php endif; ?>
    <meta name="robots" content="index,follow">
    <link rel="canonical" href="<?php echo htmlspecialchars($canonical_url); ?>">
    <meta property="og:type" content="product">
    <meta property="og:title" content="<?php echo $page_title; ?>">
    <meta property="og:description" content="<?php echo $page_description; ?>">
    <meta property="og:url" content="<?php echo htmlspecialchars($canonical_url); ?>">
    <meta property="og:site_name" content="<?php echo htmlspecialchars($site_name); ?>">
    <?php if (!empty($og_image)): ?>
    <meta property="og:image" content="<?php echo htmlspecialchars($og_image); ?>">
    <?php endif; ?>
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo $page_title; ?>">
    <meta name="twitter:description" content="<?php echo $page_description; ?>">
    <?php if (!empty($og_image)): ?>
    <meta name="twitter:image" content="<?php echo htmlspecialchars($og_image); ?>">
    <?php endif; ?>
    <?php if (!empty($site_favicon)): ?>
    <link rel="icon" href="<?php echo htmlspecialchars($site_favicon); ?>">
    <?php endif; ?>
    <?php if (!empty($structured_data_json)): ?>
    <script type="application/ld+json">
        <?php echo $structured_data_json; ?>
    </script>
    <?php endif; ?>
    
    <link rel="stylesheet" href="css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        .product-detail {
            padding: 3rem 0;
            background: linear-gradient(180deg, #f8f9fa 0%, #ffffff 100%);
        }

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
        }

        .breadcrumb-list a:hover {
            text-decoration: underline;
        }

        .breadcrumb-separator {
            color: #94a3b8;
        }

        .product-main {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .product-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            margin-bottom: 3rem;
        }

        .product-image-gallery {
            position: sticky;
            top: 2rem;
        }

        .main-image {
            width: 100%;
            height: 500px;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border-radius: 16px;
            overflow: hidden;
            margin-bottom: 1rem;
            border: 2px solid #e5e7eb;
            position: relative;
            cursor: zoom-in;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        }

        .main-image img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            padding: 2rem;
            transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .main-image:hover img {
            transform: scale(1.1);
        }

        /* Image Counter Badge */
        .image-counter {
            position: absolute;
            bottom: 1rem;
            right: 1rem;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
        }

        /* Thumbnail Gallery */
        .thumbnail-gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }

        .thumbnail-item {
            aspect-ratio: 1;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            overflow: hidden;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
        }

        .thumbnail-item:hover {
            border-color: #3b82f6;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);
        }

        .thumbnail-item.active {
            border-color: #3b82f6;
            border-width: 3px;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .thumbnail-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Product Video */
        .product-video {
            margin-top: 2rem;
            background: #f8fafc;
            border-radius: 12px;
            padding: 1.5rem;
            border: 1px solid #e5e7eb;
        }

        .video-title {
            margin: 0 0 1rem 0;
            font-size: 1.1rem;
            font-weight: 700;
            color: #1e293b;
        }

        .video-wrapper {
            position: relative;
            padding-bottom: 56.25%; /* 16:9 aspect ratio */
            height: 0;
            overflow: hidden;
            border-radius: 8px;
            background: #000;
        }

        .video-wrapper iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
        }

        .product-details-section h1 {
            font-size: 2rem;
            font-weight: 800;
            color: #1e293b;
            margin-bottom: 0.5rem;
            line-height: 1.3;
        }

        .product-meta-bar {
            display: flex;
            gap: 1.5rem;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid #e5e7eb;
            margin-bottom: 1.5rem;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.95rem;
        }

        .category-badge {
            background: #eff6ff;
            color: #2563eb;
            padding: 0.375rem 0.75rem;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.875rem;
            text-decoration: none;
        }

        .category-badge:hover {
            background: #dbeafe;
        }

        .stock-badge {
            padding: 0.375rem 0.75rem;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.875rem;
        }

        .stock-badge.in-stock {
            background: #d1fae5;
            color: #065f46;
        }

        .stock-badge.low-stock {
            background: #fef3c7;
            color: #92400e;
        }

        .stock-badge.out-of-stock {
            background: #fee2e2;
            color: #991b1b;
        }

        .product-pricing-box {
            background: #f8fafc;
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            border: 2px solid #e5e7eb;
        }

        .price-label {
            font-size: 0.95rem;
            color: #64748b;
            margin-bottom: 0.75rem;
        }

        .price-display {
            display: flex;
            gap: 2rem;
            align-items: baseline;
            margin-bottom: 1rem;
        }

        .price {
            font-size: 2.25rem;
            font-weight: 800;
            color: #1e293b;
        }

        .price .currency {
            font-size: 1rem;
            font-weight: 600;
            color: #64748b;
            margin-right: 0.375rem;
        }

        .price-secondary {
            font-size: 1.5rem;
            color: #64748b;
        }

        .price-note {
            font-size: 0.875rem;
            color: #64748b;
            font-style: italic;
        }

        .short-description {
            font-size: 1.125rem;
            color: #475569;
            line-height: 1.7;
            margin-bottom: 1.5rem;
        }

        .cta-buttons {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .btn {
            padding: 1rem 2rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1rem;
            text-decoration: none;
            text-align: center;
            transition: all 0.3s;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.625rem;
            border: none;
        }

        .btn-primary {
            background: #2563eb;
            color: white;
            flex: 1;
        }

        .btn-primary:hover {
            background: #1d4ed8;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.3);
        }

        .btn-secondary {
            background: #10b981;
            color: white;
            flex: 1;
        }

        .btn-secondary:hover {
            background: #059669;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.3);
        }

        .btn-outline {
            background: white;
            border: 2px solid #e5e7eb;
            color: #475569;
        }

        .btn-outline:hover {
            border-color: #2563eb;
            color: #2563eb;
            background: #eff6ff;
        }

        .trust-features {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            padding: 1.5rem 0;
            border-top: 1px solid #e5e7eb;
        }

        .trust-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .trust-icon {
            width: 40px;
            height: 40px;
            background: #eff6ff;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #2563eb;
        }

        .trust-text {
            flex: 1;
        }

        .trust-text strong {
            display: block;
            font-size: 0.875rem;
            font-weight: 600;
            color: #1e293b;
        }

        .trust-text span {
            font-size: 0.8rem;
            color: #64748b;
        }

        .product-tabs {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .tab-nav {
            display: flex;
            gap: 0.5rem;
            border-bottom: 2px solid #e5e7eb;
            margin-bottom: 2rem;
        }

        .tab-button {
            padding: 1rem 1.5rem;
            background: none;
            border: none;
            border-bottom: 3px solid transparent;
            color: #64748b;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s;
            margin-bottom: -2px;
        }

        .tab-button.active {
            color: #2563eb;
            border-bottom-color: #2563eb;
        }

        .tab-button:hover {
            color: #2563eb;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
            animation: fadeIn 0.3s;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .description-content {
            font-size: 1.05rem;
            line-height: 1.8;
            color: #475569;
        }

        .description-content h2,
        .description-content h3 {
            color: #1e293b;
            margin: 1.5rem 0 1rem;
            font-weight: 700;
        }

        .description-content h2 {
            font-size: 1.5rem;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 0.5rem;
        }

        .description-content h3 {
            font-size: 1.25rem;
        }

        .description-content p {
            margin-bottom: 1.25rem;
        }

        .description-content ul,
        .description-content ol {
            margin: 1rem 0;
            padding-left: 2rem;
        }

        .description-content li {
            margin-bottom: 0.5rem;
            line-height: 1.6;
        }

        .description-content strong {
            color: #1e293b;
            font-weight: 600;
        }

        .description-content a {
            color: #2563eb;
            text-decoration: underline;
        }

        .description-content a:hover {
            color: #1d4ed8;
        }

        .description-content blockquote {
            border-left: 4px solid #2563eb;
            padding-left: 1rem;
            margin: 1.5rem 0;
            font-style: italic;
            color: #64748b;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.25rem;
        }

        .feature-item {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            padding: 1.25rem;
            background: #f8fafc;
            border-radius: 8px;
            border-left: 3px solid #2563eb;
        }

        .feature-icon {
            width: 36px;
            height: 36px;
            background: #2563eb;
            color: white;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .feature-text {
            flex: 1;
            font-size: 1rem;
            color: #475569;
            line-height: 1.6;
        }

        .specs-table {
            width: 100%;
            border-collapse: collapse;
        }

        .specs-table tr {
            border-bottom: 1px solid #e5e7eb;
        }

        .specs-table tr:last-child {
            border-bottom: none;
        }

        .specs-table td {
            padding: 1rem;
            font-size: 1rem;
        }

        .specs-table td:first-child {
            font-weight: 600;
            color: #475569;
            width: 35%;
            background: #f8fafc;
        }

        .specs-table td:last-child {
            color: #1e293b;
        }

        .related-products {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .related-products h2 {
            font-size: 1.75rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 1.5rem;
        }

        .related-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
        }

        .related-card {
            background: #f8fafc;
            border-radius: 8px;
            overflow: hidden;
            transition: all 0.3s;
        }

        .related-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.1);
        }

        .related-card-image {
            width: 100%;
            height: 180px;
            background: white;
            overflow: hidden;
        }

        .related-card-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .related-card-info {
            padding: 1rem;
        }

        .related-card-title {
            font-size: 0.95rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 0.5rem;
            line-height: 1.4;
        }

        .related-card-title a {
            color: inherit;
            text-decoration: none;
        }

        .related-card-title a:hover {
            color: #2563eb;
        }

        .related-card-price {
            font-size: 1.125rem;
            font-weight: 700;
            color: #2563eb;
        }

        @media (max-width: 1024px) {
            .product-grid {
                grid-template-columns: 1fr;
                gap: 2rem;
            }

            .product-image-gallery {
                position: static;
            }

            .related-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .trust-features {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .product-detail {
                padding: 1.5rem 0;
            }

            .product-main,
            .product-tabs,
            .related-products {
                padding: 1.5rem;
            }

            .cta-buttons {
                flex-direction: column;
            }

            .features-grid {
                grid-template-columns: 1fr;
            }

            .related-grid {
                grid-template-columns: 1fr;
            }

            .product-details-section h1 {
                font-size: 1.5rem;
            }

            .price {
                font-size: 1.75rem;
            }

            .tab-nav {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }

            .main-image {
                height: 350px;
            }

            .thumbnail-gallery {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        /* Sticky Mobile Add to Cart Bar */
        .sticky-cart-bar {
            display: none;
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            padding: 1rem;
            box-shadow: 0 -4px 12px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            border-top: 2px solid #e5e7eb;
        }

        .sticky-cart-bar.visible {
            display: block;
        }

        .sticky-cart-content {
            display: flex;
            gap: 1rem;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
        }

        .sticky-cart-info {
            flex: 1;
        }

        .sticky-cart-info h4 {
            margin: 0 0 0.25rem;
            font-size: 0.95rem;
            font-weight: 600;
            color: #1e293b;
        }

        .sticky-cart-info .price {
            font-size: 1.25rem;
            margin: 0;
        }

        .sticky-cart-bar .btn {
            padding: 0.875rem 1.5rem;
            white-space: nowrap;
        }

        @media (max-width: 768px) {
            .sticky-cart-bar {
                display: block;
                animation: slideUp 0.3s ease-out;
            }

            @keyframes slideUp {
                from {
                    transform: translateY(100%);
                }
                to {
                    transform: translateY(0);
                }
            }
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
                <li><a href="products.php">Products</a></li>
                <?php if (!empty($product['category_name'])): ?>
                <li class="breadcrumb-separator">›</li>
                <li><a href="products.php?category=<?php echo $product['category_id']; ?>"><?php echo htmlspecialchars($product['category_name']); ?></a></li>
                <?php endif; ?>
                <li class="breadcrumb-separator">›</li>
                <li><?php echo htmlspecialchars($product['name']); ?></li>
            </ul>
        </div>
    </div>

    <!-- Product Detail -->
    <section class="product-detail">
        <div class="container">
            <div class="product-main">
                <div class="product-grid">
                    <!-- Product Image Gallery -->
                    <div class="product-image-gallery">
                        <div class="main-image" id="mainImage">
                            <?php 
                                $galleryPlaceholder = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='500' height='500' viewBox='0 0 500 500'%3E%3Crect fill='%23f1f5f9' width='500' height='500'/%3E%3Ctext x='50%25' y='50%25' dominant-baseline='middle' text-anchor='middle' fill='%2394a3b8' font-family='Arial' font-size='24' font-weight='bold'%3ENo Image Available%3C/text%3E%3C/svg%3E";
                            ?>
                            <?php if (!empty($gallery_images)):
                                $featured_image = null;
                                foreach ($gallery_images as $img) {
                                    if (!empty($img['image_url'])) {
                                        if ($featured_image === null) {
                                            $featured_image = $img;
                                        }
                                        if (!empty($img['is_featured'])) {
                                            $featured_image = $img;
                                            break;
                                        }
                                    }
                                }
                                $featuredSrc = $featured_image['image_url'] ?? '';
                                $alt_text = !empty($featured_image['alt_text']) ? $featured_image['alt_text'] : $product['name'];
                                if (empty($featuredSrc)) {
                                    $featuredSrc = $galleryPlaceholder;
                                }
                            ?>
                                <img src="<?php echo htmlspecialchars($featuredSrc); ?>" 
                                     alt="<?php echo htmlspecialchars($alt_text); ?>"
                                     id="currentImage"
                                     itemproto="image">
                            <?php else: ?>
                          <img src="<?php echo htmlspecialchars($galleryPlaceholder); ?>" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?>">
                            <?php endif; ?>
                            
                            <!-- Image counter badge -->
                            <?php if (count($gallery_images) > 1): ?>
                            <div class="image-counter">
                                <span id="currentImageIndex">1</span> / <?php echo count($gallery_images); ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Thumbnail Gallery -->
                        <?php if (count($gallery_images) > 1): ?>
                        <div class="thumbnail-gallery">
                            <?php foreach ($gallery_images as $index => $img): 
                                $thumbSrc = $img['image_url'] ?? '';
                                if ($thumbSrc === '') {
                                    continue;
                                }
                                $alt_text = !empty($img['alt_text']) ? $img['alt_text'] : $product['name'];
                            ?>
                            <div class="thumbnail-item <?php echo $index === 0 ? 'active' : ''; ?>" 
                                 onclick="changeImage('<?php echo htmlspecialchars($thumbSrc, ENT_QUOTES); ?>', <?php echo $index + 1; ?>, this)">
                                <img src="<?php echo htmlspecialchars($thumbSrc); ?>" 
                                     alt="<?php echo htmlspecialchars($alt_text); ?>">
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        
                        <!-- YouTube Video (if exists) -->
                        <?php if (!empty($product['video_url'])): 
                            // Extract YouTube video ID
                            preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&\s]+)/', $product['video_url'], $matches);
                            $video_id = $matches[1] ?? '';
                            if ($video_id):
                        ?>
                        <div class="product-video">
                            <h3 class="video-title">📹 Product Video</h3>
                            <div class="video-wrapper">
                                <iframe width="100%" height="315" 
                                        src="https://www.youtube.com/embed/<?php echo htmlspecialchars($video_id); ?>" 
                                        frameborder="0" 
                                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                        allowfullscreen>
                                </iframe>
                            </div>
                        </div>
                        <?php endif; endif; ?>
                    </div>

                    <!-- Product Info -->
                    <div class="product-details-section">
                        <h1><?php echo htmlspecialchars($product['name']); ?></h1>

                        <div class="product-meta-bar">
                            <?php if (!empty($product['category_name'])): ?>
                            <a href="products.php?category=<?php echo $product['category_id']; ?>" class="category-badge">
                                <?php echo htmlspecialchars($product['category_name']); ?>
                            </a>
                            <?php endif; ?>
                            <div class="meta-item">
                                <span>SKU:</span>
                                <strong><?php echo htmlspecialchars($product['sku']); ?></strong>
                            </div>
                            <span class="stock-badge <?php echo $stock_class; ?>">
                                <?php echo $stock_text; ?>
                            </span>
                        </div>

                        <?php if (!empty($product['short_description'])): ?>
                        <div class="short-description">
                            <?php echo nl2br(htmlspecialchars($product['short_description'])); ?>
                        </div>
                        <?php endif; ?>

                        <div class="product-pricing-box">
                            <div class="price-label">Price</div>
                            <div class="price-display">
                                <div class="price">
                                    <span class="currency">KES</span>
                                    <?php echo number_format($product['price_kes'], 0); ?>
                                </div>
                                <div class="price price-secondary">
                                    <span class="currency">TZS</span>
                                    <?php echo number_format($product['price_tzs'], 0); ?>
                                </div>
                            </div>
                            <div class="price-note">Installation & training included • <?php echo $product['warranty_period']; ?> months warranty</div>
                        </div>

                        <!-- Quantity Selector -->
                        <div class="quantity-selector">
                            <label for="quantity">Quantity:</label>
                            <input type="number" 
                                   id="quantity" 
                                   name="quantity" 
                                   value="1" 
                                   min="1" 
                                   max="<?php echo $product['stock_quantity']; ?>"
                                   <?php echo ($product['stock_quantity'] <= 0) ? 'disabled' : ''; ?>>
                            <?php if ($product['stock_quantity'] > 0 && $product['stock_quantity'] <= 10): ?>
                            <span class="stock-warning">Only <?php echo $product['stock_quantity']; ?> left in stock</span>
                            <?php endif; ?>
                        </div>

                        <div class="cta-buttons">
                            <?php if ($product['stock_quantity'] > 0): ?>
                            <button onclick="addToCart(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['name'], ENT_QUOTES); ?>')" 
                                    class="btn btn-primary">
                                <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49c.08-.14.12-.31.12-.48 0-.55-.45-1-1-1H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z"/>
                                </svg>
                                Add to Cart
                            </button>
                            <?php else: ?>
                            <button class="btn btn-primary" disabled>
                                <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49c.08-.14.12-.31.12-.48 0-.55-.45-1-1-1H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z"/>
                                </svg>
                                Out of Stock
                            </button>
                            <?php endif; ?>
                            <a href="https://wa.me/255753098911?text=Hi, I'd like to order: <?php echo urlencode($product['name']); ?> (<?php echo urlencode($product['sku']); ?>)" 
                               class="btn btn-secondary" target="_blank">
                                <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                                </svg>
                                Order via WhatsApp
                            </a>
                            <a href="index.php#contact" class="btn btn-outline">
                                <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                                </svg>
                                Request Quote
                            </a>
                        </div>

                        <div class="trust-features">
                            <div class="trust-item">
                                <div class="trust-icon">
                                    <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 10.99h7c-.53 4.12-3.28 7.79-7 8.94V12H5V6.3l7-3.11v8.8z"/>
                                    </svg>
                                </div>
                                <div class="trust-text">
                                    <strong><?php echo $product['warranty_period']; ?> Months Warranty</strong>
                                    <span>Full parts & service</span>
                                </div>
                            </div>
                            <div class="trust-item">
                                <div class="trust-icon">
                                    <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M20 6h-2.18c.11-.31.18-.65.18-1a2.996 2.996 0 00-5.5-1.65l-.5.67-.5-.68C10.96 2.54 10.05 2 9 2 7.34 2 6 3.34 6 5c0 .35.07.69.18 1H4c-1.11 0-1.99.89-1.99 2L2 19c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V8c0-1.11-.89-2-2-2zm-5-2c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zM9 4c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm11 15H4v-2h16v2zm0-5H4V8h5.08L7 10.83 8.62 12 11 8.76l1-1.36 1 1.36L15.38 12 17 10.83 14.92 8H20v6z"/>
                                    </svg>
                                </div>
                                <div class="trust-text">
                                    <strong>Installation Included</strong>
                                    <span>Professional setup</span>
                                </div>
                            </div>
                            <div class="trust-item">
                                <div class="trust-icon">
                                    <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M11 17h2v-6h-2v6zm1-15C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zM11 9h2V7h-2v2z"/>
                                    </svg>
                                </div>
                                <div class="trust-text">
                                    <strong>Training Provided</strong>
                                    <span>Learn to operate</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Product Tabs -->
            <div class="product-tabs">
                <div class="tab-nav">
                    <button class="tab-button active" onclick="switchTab('description')">Description</button>
                    <?php if (count($specifications) > 0): ?>
                    <button class="tab-button" onclick="switchTab('specifications')">Specifications</button>
                    <?php endif; ?>
                    <?php if (count($features) > 0): ?>
                    <button class="tab-button" onclick="switchTab('features')">Features</button>
                    <?php endif; ?>
                </div>

                <div id="description" class="tab-content active">
                    <div class="description-content">
                        <?php echo $product['description']; ?>
                    </div>
                </div>

                <?php if (count($specifications) > 0): ?>
                <div id="specifications" class="tab-content">
                    <table class="specs-table">
                        <?php foreach ($specifications as $spec): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($spec['name']); ?></td>
                            <td><?php echo htmlspecialchars($spec['value']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                <?php endif; ?>

                <?php if (count($features) > 0): ?>
                <div id="features" class="tab-content">
                    <div class="features-grid">
                        <?php foreach ($features as $feature): ?>
                        <div class="feature-item">
                            <div class="feature-icon">
                                <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                                </svg>
                            </div>
                            <div class="feature-text">
                                <?php echo htmlspecialchars($feature); ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Upsell Products (Better Alternatives) -->
            <?php if (count($upsell_products) > 0): ?>
            <div class="upsell-products">
                <div class="section-header">
                    <h2>⬆️ Upgrade Your Choice</h2>
                    <p class="section-subtitle">Consider these premium alternatives with enhanced features</p>
                </div>
                <div class="products-grid">
                    <?php foreach ($upsell_products as $upsell): ?>
                    <div class="product-card upsell-card">
                        <div class="product-badge upgrade-badge">Upgrade</div>
                        <div class="product-image-container">
                            <a href="product-detail.php?slug=<?php echo urlencode($upsell['slug']); ?>">
                                <?php $upsellImage = normalize_product_image_url($upsell['image'] ?? ''); ?>
                                <?php if (!empty($upsellImage)): ?>
                                    <img src="<?php echo htmlspecialchars($upsellImage); ?>" 
                                         alt="<?php echo htmlspecialchars($upsell['name']); ?>" 
                                         class="product-image">
                                <?php else: ?>
                                    <div class="product-placeholder">
                                        <svg width="100" height="100" fill="#cbd5e1" viewBox="0 0 24 24">
                                            <path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/>
                                        </svg>
                                    </div>
                                <?php endif; ?>
                            </a>
                        </div>
                        <div class="product-info">
                            <h3 class="product-name">
                                <a href="product-detail.php?slug=<?php echo urlencode($upsell['slug']); ?>">
                                    <?php echo htmlspecialchars($upsell['name']); ?>
                                </a>
                            </h3>
                            <?php if (!empty($upsell['short_description'])): ?>
                                <p class="product-description"><?php echo htmlspecialchars(substr($upsell['short_description'], 0, 80)); ?>...</p>
                            <?php endif; ?>
                            <div class="product-price">
                                <span class="price-kes">KES <?php echo number_format($upsell['price_kes'], 0); ?></span>
                                <?php if ($upsell['price_tzs'] > 0): ?>
                                    <span class="price-tzs">TZS <?php echo number_format($upsell['price_tzs'], 0); ?></span>
                                <?php endif; ?>
                            </div>
                            <?php
                            $price_difference = $upsell['price_kes'] - $product['price_kes'];
                            if ($price_difference > 0):
                            ?>
                                <div class="price-difference">
                                    <small>+KES <?php echo number_format($price_difference, 0); ?> for upgraded features</small>
                                </div>
                            <?php endif; ?>
                            <div class="product-actions">
                                <a href="product-detail.php?slug=<?php echo urlencode($upsell['slug']); ?>" class="btn btn-outline">
                                    View Details
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Accessories & Add-ons -->
            <?php if (count($accessory_products) > 0): ?>
            <div class="accessory-products">
                <div class="section-header">
                    <h2>🔧 Complete Your Setup</h2>
                    <p class="section-subtitle">Recommended accessories and add-ons</p>
                </div>
                <div class="products-grid">
                    <?php foreach ($accessory_products as $accessory): ?>
                    <div class="product-card accessory-card">
                        <div class="product-badge accessory-badge">Add-on</div>
                        <div class="product-image-container">
                            <a href="product-detail.php?slug=<?php echo urlencode($accessory['slug']); ?>">
                                <?php $accessoryImage = normalize_product_image_url($accessory['image'] ?? ''); ?>
                                <?php if (!empty($accessoryImage)): ?>
                                    <img src="<?php echo htmlspecialchars($accessoryImage); ?>" 
                                         alt="<?php echo htmlspecialchars($accessory['name']); ?>" 
                                         class="product-image">
                                <?php else: ?>
                                    <div class="product-placeholder">
                                        <svg width="100" height="100" fill="#cbd5e1" viewBox="0 0 24 24">
                                            <path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/>
                                        </svg>
                                    </div>
                                <?php endif; ?>
                            </a>
                        </div>
                        <div class="product-info">
                            <h3 class="product-name">
                                <a href="product-detail.php?slug=<?php echo urlencode($accessory['slug']); ?>">
                                    <?php echo htmlspecialchars($accessory['name']); ?>
                                </a>
                            </h3>
                            <?php if (!empty($accessory['short_description'])): ?>
                                <p class="product-description"><?php echo htmlspecialchars(substr($accessory['short_description'], 0, 80)); ?>...</p>
                            <?php endif; ?>
                            <div class="product-price">
                                <span class="price-kes">KES <?php echo number_format($accessory['price_kes'], 0); ?></span>
                                <?php if ($accessory['price_tzs'] > 0): ?>
                                    <span class="price-tzs">TZS <?php echo number_format($accessory['price_tzs'], 0); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="product-actions">
                                <button onclick="addToCart(<?php echo $accessory['id']; ?>, '<?php echo htmlspecialchars($accessory['name'], ENT_QUOTES); ?>')" 
                                        class="btn btn-primary btn-sm">
                                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49c.08-.14.12-.31.12-.48 0-.55-.45-1-1-1H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z"/>
                                    </svg>
                                    Quick Add
                                </button>
                                <a href="product-detail.php?slug=<?php echo urlencode($accessory['slug']); ?>" class="btn btn-outline btn-sm">
                                    Details
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Related Products (You May Also Like) -->
            <?php if (count($related_products) > 0): ?>
            <div class="related-products">
                <div class="section-header">
                    <h2>💡 You May Also Like</h2>
                    <p class="section-subtitle">Similar products that might interest you</p>
                </div>
                <div class="products-grid">
                    <?php foreach ($related_products as $related): ?>
                    <div class="product-card">
                        <?php if ($related['is_featured']): ?>
                            <div class="product-badge featured-badge">Featured</div>
                        <?php endif; ?>
                        <div class="product-image-container">
                            <a href="product-detail.php?slug=<?php echo urlencode($related['slug']); ?>">
                                <?php $relatedImage = normalize_product_image_url($related['image'] ?? ''); ?>
                                <?php if (!empty($relatedImage)): ?>
                                    <img src="<?php echo htmlspecialchars($relatedImage); ?>" 
                                         alt="<?php echo htmlspecialchars($related['name']); ?>" 
                                         class="product-image">
                                <?php else: ?>
                                    <div class="product-placeholder">
                                        <svg width="100" height="100" fill="#cbd5e1" viewBox="0 0 24 24">
                                            <path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/>
                                        </svg>
                                    </div>
                                <?php endif; ?>
                            </a>
                        </div>
                        <div class="product-info">
                            <div class="product-meta">
                                <span class="product-sku"><?php echo htmlspecialchars($related['sku']); ?></span>
                                <?php if (!empty($related['category_name'])): ?>
                                    <span class="product-category"><?php echo htmlspecialchars($related['category_name']); ?></span>
                                <?php endif; ?>
                            </div>
                            <h3 class="product-name">
                                <a href="product-detail.php?slug=<?php echo urlencode($related['slug']); ?>">
                                    <?php echo htmlspecialchars($related['name']); ?>
                                </a>
                            </h3>
                            <?php if (!empty($related['short_description'])): ?>
                                <p class="product-description"><?php echo htmlspecialchars(substr($related['short_description'], 0, 100)); ?>...</p>
                            <?php endif; ?>
                            <div class="product-price">
                                <span class="price-kes">KES <?php echo number_format($related['price_kes'], 0); ?></span>
                                <?php if ($related['price_tzs'] > 0): ?>
                                    <span class="price-tzs">TZS <?php echo number_format($related['price_tzs'], 0); ?></span>
                                <?php endif; ?>
                            </div>
                            <?php
                            $stock_status = '';
                            if ($related['stock_quantity'] > 10) {
                                $stock_status = '<span class="stock-badge in-stock">In Stock</span>';
                            } elseif ($related['stock_quantity'] > 0) {
                                $stock_status = '<span class="stock-badge low-stock">Only ' . $related['stock_quantity'] . ' left</span>';
                            } else {
                                $stock_status = '<span class="stock-badge out-of-stock">Out of Stock</span>';
                            }
                            echo $stock_status;
                            ?>
                            <div class="product-actions">
                                <?php if ($related['stock_quantity'] > 0): ?>
                                    <button onclick="addToCart(<?php echo $related['id']; ?>, '<?php echo htmlspecialchars($related['name'], ENT_QUOTES); ?>')" 
                                            class="btn btn-primary">
                                        Add to Cart
                                    </button>
                                <?php endif; ?>
                                <a href="product-detail.php?slug=<?php echo urlencode($related['slug']); ?>" class="btn btn-outline">
                                    View Details
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
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

    <!-- Sticky Mobile Add to Cart Bar -->
    <div class="sticky-cart-bar" id="stickyCartBar">
        <div class="sticky-cart-content">
            <div class="sticky-cart-info">
                <h4><?php echo htmlspecialchars($product['name']); ?></h4>
                <div class="price">
                    <span class="currency">KES</span> <?php echo number_format($product['price_kes'], 0); ?>
                </div>
            </div>
            <?php if ($product['stock_quantity'] > 0): ?>
            <button onclick="addToCart(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['name'], ENT_QUOTES); ?>')" 
                    class="btn btn-primary">
                Add to Cart
            </button>
            <?php else: ?>
            <a href="https://wa.me/255753098911?text=Hi, I'd like to order: <?php echo urlencode($product['name']); ?>" 
               class="btn btn-secondary" target="_blank">
                WhatsApp
            </a>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Image Gallery Functions
        function changeImage(imageSrc, imageIndex, thumbnailElement) {
            // Update main image
            const mainImg = document.getElementById('currentImage');
            mainImg.src = imageSrc;
            
            // Update image counter
            const indexEl = document.getElementById('currentImageIndex');
            if (indexEl) {
                indexEl.textContent = imageIndex;
            }
            
            // Update active thumbnail
            document.querySelectorAll('.thumbnail-item').forEach(thumb => {
                thumb.classList.remove('active');
            });
            if (thumbnailElement) {
                thumbnailElement.classList.add('active');
            }
        }

        // Keyboard navigation for image gallery
        document.addEventListener('keydown', function(e) {
            const thumbnails = document.querySelectorAll('.thumbnail-item');
            if (thumbnails.length === 0) return;
            
            const activeThumbnail = document.querySelector('.thumbnail-item.active');
            let currentIndex = Array.from(thumbnails).indexOf(activeThumbnail);
            
            if (e.key === 'ArrowLeft' && currentIndex > 0) {
                thumbnails[currentIndex - 1].click();
            } else if (e.key === 'ArrowRight' && currentIndex < thumbnails.length - 1) {
                thumbnails[currentIndex + 1].click();
            }
        });
        
        function switchTab(tabName) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remove active class from all buttons
            document.querySelectorAll('.tab-button').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tabName).classList.add('active');
            
            // Add active class to clicked button
            event.target.classList.add('active');
        }
        
        // Add to Cart Function
        function addToCart(productId, productName) {
            const quantityInput = document.getElementById('quantity');
            const quantity = parseInt(quantityInput.value);
            
            if (quantity < 1) {
                alert('Please enter a valid quantity');
                return;
            }
            
            // Get selected currency from the page
            const currency = 'KES'; // Default currency, can be made dynamic
            
            fetch('cart_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=add&product_id=${productId}&quantity=${quantity}&currency=${currency}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    showNotification('✓ ' + productName + ' added to cart!', 'success');
                    
                    // Update cart count
                    updateCartCount();
                    
                    // Reset quantity to 1
                    quantityInput.value = 1;
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to add item to cart');
            });
        }
        
        // Update Cart Count in Badge
        function updateCartCount() {
            fetch('cart_handler.php?action=get_count')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const cartBadge = document.querySelector('#header-cart-badge');
                    const count = data.count || 0;
                    if (cartBadge) {
                        cartBadge.textContent = count;
                        if (count > 0) {
                            cartBadge.style.display = '';
                        }
                    }
                }
            })
            .catch(error => console.error('Error updating cart count:', error));
        }
        
        // Show Notification
        function showNotification(message, type = 'success') {
            // Create notification element
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.textContent = message;
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: ${type === 'success' ? '#4CAF50' : '#f44336'};
                color: white;
                padding: 15px 25px;
                border-radius: 5px;
                box-shadow: 0 4px 6px rgba(0,0,0,0.2);
                z-index: 10000;
                animation: slideIn 0.3s ease-out;
            `;
            
            document.body.appendChild(notification);
            
            // Remove after 3 seconds
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease-out';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }
        
        // Initialize cart count on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateCartCount();
        });
    </script>

    <style>
    @keyframes slideIn {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(400px);
            opacity: 0;
        }
    }
    
    .quantity-selector {
        margin: 25px 0;
        padding: 20px;
        background: #f8f9fa;
        border-radius: 8px;
    }
    
    .quantity-selector label {
        display: block;
        font-weight: 600;
        margin-bottom: 10px;
        color: #2c3e50;
    }
    
    .quantity-selector input[type="number"] {
        width: 120px;
        padding: 12px;
        font-size: 16px;
        border: 2px solid #ddd;
        border-radius: 5px;
        transition: border-color 0.3s;
    }
    
    .quantity-selector input[type="number"]:focus {
        outline: none;
        border-color: #007bff;
    }
    
    .quantity-selector .stock-warning {
        display: inline-block;
        margin-left: 15px;
        color: #ff9800;
        font-weight: 600;
        font-size: 14px;
    }

    /* Sticky Cart Bar Show/Hide on Scroll */
    window.addEventListener('scroll', function() {
        const stickyBar = document.getElementById('stickyCartBar');
        const ctaButtons = document.querySelector('.cta-buttons');
        
        if (stickyBar && ctaButtons) {
            const ctaRect = ctaButtons.getBoundingClientRect();
            // Show sticky bar when original CTA buttons are out of view
            if (ctaRect.bottom < 0) {
                stickyBar.classList.add('visible');
            } else {
                stickyBar.classList.remove('visible');
            }
        }
    });

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        updateCartCount();
    });
    </style>
</body>
</html>
