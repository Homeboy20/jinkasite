<?php
if (!defined('JINKA_ACCESS')) {
    define('JINKA_ACCESS', true);
}
require_once 'includes/config.php';
require_once 'includes/Cart.php';

$db = Database::getInstance()->getConnection();
$cart = new Cart();

// Configuration
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
$site_title = $site_name . ' | Professional Vinyl Cutter Kenya & Tanzania';
$business_name = $site_name;
$site_description_text = site_setting('site_description', 'Professional plotting and cutting solutions for East Africa.');
$meta_title_setting = site_setting('meta_title', '');
$meta_description_setting = site_setting('meta_description', 'Professional JINKA XL-1351E vinyl cutting plotter for sign makers in Kenya and Tanzania with installation and training.');
$meta_keywords_setting = site_setting('meta_keywords', 'vinyl cutter, cutting plotter, JINKA plotter, sign making equipment, Kenya printing equipment, Tanzania vinyl cutter');
$whatsapp_number = site_setting('whatsapp_number', '+255753098911');
$phone_number = site_setting('contact_phone', '+255753098911');
$phone_number_ke = site_setting('contact_phone_ke', '+254716522828');
$email = site_setting('contact_email', 'support@procutsolutions.com');

if (!empty($meta_title_setting)) {
    $site_title = $meta_title_setting;
}

$page_title = $site_title;
$page_description = $meta_description_setting ?: 'Professional JINKA XL-1351E 53-inch vinyl cutting plotter for commercial sign shops across Kenya and Tanzania.';
$meta_keywords = $meta_keywords_setting;

$facebook_url = trim(site_setting('facebook_url', ''));
$instagram_url = trim(site_setting('instagram_url', ''));
$twitter_url = trim(site_setting('twitter_url', ''));
$linkedin_url = trim(site_setting('linkedin_url', ''));

// Footer Configuration
$footer_logo = site_setting('footer_logo', $site_logo);
$footer_about = site_setting('footer_about', 'Professional printing equipment supplier serving Kenya and Tanzania. Quality products, expert support, and competitive pricing for all your printing needs.');
$footer_address = site_setting('footer_address', 'Kenya & Tanzania');
$footer_phone_label_tz = site_setting('footer_phone_label_tz', 'Tanzania');
$footer_phone_label_ke = site_setting('footer_phone_label_ke', 'Kenya');
$footer_hours_weekday = site_setting('footer_hours_weekday', '8:00 AM - 6:00 PM');
$footer_hours_saturday = site_setting('footer_hours_saturday', '9:00 AM - 4:00 PM');
$footer_hours_sunday = site_setting('footer_hours_sunday', 'Closed');
$footer_whatsapp_label = site_setting('footer_whatsapp_label', '24/7 Available');
$footer_copyright = site_setting('footer_copyright', '');
$youtube_url = trim(site_setting('youtube_url', ''));

$social_profiles = array_values(array_filter([
    $facebook_url,
    $instagram_url,
    $twitter_url,
    $linkedin_url,
    $youtube_url
]));

$contact_phone_link = preg_replace('/[^0-9+]/', '', $phone_number);
if ($contact_phone_link !== '' && $contact_phone_link[0] !== '+') {
    $contact_phone_link = '+' . ltrim($contact_phone_link, '+');
}

$contact_phone_ke_link = preg_replace('/[^0-9+]/', '', $phone_number_ke);
if ($contact_phone_ke_link !== '' && $contact_phone_ke_link[0] !== '+') {
    $contact_phone_ke_link = '+' . ltrim($contact_phone_ke_link, '+');
}

$whatsapp_number_link = preg_replace('/[^0-9]/', '', $whatsapp_number);

// Featured and hero product data
$featured_query = "SELECT * FROM products WHERE is_featured = 1 AND is_active = 1 ORDER BY created_at DESC LIMIT 6";
$featured_result = $db->query($featured_query);
$featured_products = [];
if ($featured_result) {
    while ($row = $featured_result->fetch_assoc()) {
        $featured_products[] = $row;
    }
}
// Determine primary hero product (lock to JINKA XL-1351E)
$default_hero_product = [
    'id' => null,
    'slug' => 'jinka-xl-1351e-cutting-plotter',
    'name' => 'JINKA XL-1351E Vinyl Cutting Plotter',
    'sku' => 'JINKA-XL-1351E',
    'category_name' => 'Flagship Vinyl Cutter',
    'price_kes' => 120000,
    'price_tzs' => 2400000,
    'short_description' => '53-inch JINKA XL-1351E vinyl cutting plotter with ±0.1mm accuracy, engineered for Kenyan and Tanzanian sign makers.',
    'description' => 'Meet the JINKA XL-1351E — a 53-inch vinyl cutting plotter purpose-built for sign shops and branding studios across East Africa. Delivered with installation, calibration, and operator training so your team can start producing wraps, decals, signage, and apparel graphics immediately.',
    'stock_quantity' => 12,
    'features' => json_encode([
        '±0.1mm cutting accuracy on 53" media',
        'Industrial stepper motor with dual pinch-roller tracking',
        'CE-certified electronics with USB and RS-232 connectivity'
    ]),
    'image' => 'images/plotter-hero.webp'
];

$hero_product_slug_setting = trim(site_setting('hero_product_slug', ''));
$hero_slug_candidates = [];

if ($hero_product_slug_setting !== '') {
    $hero_slug_candidates[] = $hero_product_slug_setting;
}

$hero_slug_candidates[] = 'jinka-xl-1351e-cutting-plotter';
$hero_slug_candidates[] = 'jinka-xl-1351e';
$hero_slug_candidates = array_values(array_unique(array_filter($hero_slug_candidates)));

$hero_record = null;

foreach ($hero_slug_candidates as $candidateSlug) {
    $stmt = $db->prepare("SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.slug = ? AND p.is_active = 1 LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('s', $candidateSlug);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            if ($result && $result->num_rows > 0) {
                $hero_record = $result->fetch_assoc();
                $stmt->close();
                break;
            }
        }
        $stmt->close();
    }
}

if ($hero_record) {
    $default_hero_product['id'] = isset($hero_record['id']) ? (int)$hero_record['id'] : $default_hero_product['id'];
    $default_hero_product['slug'] = $hero_record['slug'] ?? $default_hero_product['slug'];
    $default_hero_product['name'] = !empty($hero_record['name']) ? $hero_record['name'] : $default_hero_product['name'];
    $default_hero_product['sku'] = !empty($hero_record['sku']) ? $hero_record['sku'] : $default_hero_product['sku'];
    $default_hero_product['category_name'] = !empty($hero_record['category_name']) ? $hero_record['category_name'] : $default_hero_product['category_name'];
    $default_hero_product['price_kes'] = isset($hero_record['price_kes']) && $hero_record['price_kes'] !== '' ? (float)$hero_record['price_kes'] : $default_hero_product['price_kes'];
    $default_hero_product['price_tzs'] = isset($hero_record['price_tzs']) && $hero_record['price_tzs'] !== '' ? (float)$hero_record['price_tzs'] : $default_hero_product['price_tzs'];
    $default_hero_product['short_description'] = !empty($hero_record['short_description']) ? $hero_record['short_description'] : $default_hero_product['short_description'];
    $default_hero_product['description'] = !empty($hero_record['description']) ? $hero_record['description'] : $default_hero_product['description'];
    $default_hero_product['stock_quantity'] = isset($hero_record['stock_quantity']) ? (int)$hero_record['stock_quantity'] : $default_hero_product['stock_quantity'];
    if (!empty($hero_record['features'])) {
        $default_hero_product['features'] = $hero_record['features'];
    }
    if (!empty($hero_record['image'])) {
        $default_hero_product['image'] = $hero_record['image'];
    }
}

$hero_product = $default_hero_product;

$hero_product_id = $hero_product['id'];
$hero_product_slug = $hero_product['slug'];
$hero_product_name = $hero_product['name'];
$hero_product_sku = $hero_product['sku'];
$hero_product_category = $hero_product['category_name'];

$hero_price_kes_value = isset($hero_product['price_kes']) ? (float)$hero_product['price_kes'] : 120000;
$hero_price_tzs_value = isset($hero_product['price_tzs']) ? (float)$hero_product['price_tzs'] : 2400000;
$hero_price_kes = number_format($hero_price_kes_value, 0);
$hero_price_tzs = number_format($hero_price_tzs_value, 0);

$hero_product_description = '';
if (!empty($hero_product['short_description'])) {
    $hero_product_description = strip_tags($hero_product['short_description']);
} elseif (!empty($hero_product['description'])) {
    $hero_product_description = strip_tags($hero_product['description']);
} else {
    $hero_product_description = 'Professional vinyl cutting plotter for sign making, vehicle branding, and commercial printing projects across East Africa.';
}

if (function_exists('mb_substr')) {
    $hero_product_description = mb_substr($hero_product_description, 0, 260);
} else {
    $hero_product_description = substr($hero_product_description, 0, 260);
}

$default_hero_image = 'images/plotter-hero.webp';
$configured_hero_image = site_setting('hero_plotter_image', '');
$hero_image_source = $hero_product['image'] ?? $default_hero_image;

if (!empty($configured_hero_image)) {
    $hero_image_source = $configured_hero_image;
}

$hero_image = normalize_product_image_url($hero_image_source, ['fallback' => $default_hero_image]);
$hero_image_absolute = normalize_product_image_url($hero_image_source, ['fallback' => $default_hero_image, 'absolute' => true]);

$hero_stock_qty = isset($hero_product['stock_quantity']) ? (int)$hero_product['stock_quantity'] : null;
$hero_stock_text = 'Available for Order';
if ($hero_stock_qty !== null) {
    if ($hero_stock_qty > 10) {
        $hero_stock_text = 'In Stock';
    } elseif ($hero_stock_qty > 0) {
        $hero_stock_text = 'Low Stock';
    } else {
        $hero_stock_text = 'Backorder';
    }
}

$hero_features_raw = [];
if (!empty($hero_product['features'])) {
    $decoded_features = json_decode($hero_product['features'], true);
    if (is_array($decoded_features)) {
        foreach ($decoded_features as $featureItem) {
            if (is_string($featureItem)) {
                $hero_features_raw[] = trim($featureItem);
            } elseif (is_array($featureItem)) {
                $title = $featureItem['title'] ?? ($featureItem['name'] ?? '');
                $detail = $featureItem['description'] ?? ($featureItem['detail'] ?? '');
                $compiled = trim($title);
                if ($compiled !== '' && $detail !== '') {
                    $compiled .= ' — ' . trim($detail);
                } elseif ($compiled === '' && $detail !== '') {
                    $compiled = trim($detail);
                }

                if ($compiled !== '') {
                    $hero_features_raw[] = $compiled;
                }
            }
        }
    }
}

$hero_badges = array_slice(array_filter($hero_features_raw), 0, 3);
if (empty($hero_badges)) {
    $hero_badges = [
        '±0.1mm cutting accuracy on 53" media',
        'Industrial stepper motor with dual pinch rollers',
        'Installation & training included (Kenya & Tanzania)'
    ];
}

$hero_metrics = [];
if (!empty($hero_product_sku)) {
    $hero_metrics[] = ['label' => 'SKU', 'value' => $hero_product_sku];
}
if (!empty($hero_stock_text)) {
    $hero_metrics[] = ['label' => 'Availability', 'value' => $hero_stock_text];
}
if (!empty($hero_product_category)) {
    $hero_metrics[] = ['label' => 'Category', 'value' => $hero_product_category];
}

$site_logo_absolute = '';
if (!empty($site_logo)) {
    $site_logo_absolute = site_url($site_logo);
}
if (empty($site_logo_absolute)) {
    $site_logo_absolute = $hero_image_absolute ?: site_url('images/plotter-hero.webp');
}

$canonical_url = site_url('/');
$og_image = $hero_image_absolute ?: $site_logo_absolute;
$availability_url = ($hero_stock_qty === null || $hero_stock_qty > 0) ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock';
$hero_product_url = $hero_product_slug ? site_url('product-detail.php?slug=' . urlencode($hero_product_slug)) : $canonical_url;

$organization_schema = [
    '@type' => 'Organization',
    '@id' => site_url('#organization'),
    'name' => $site_name,
    'url' => $canonical_url,
    'logo' => $site_logo_absolute,
    'email' => $email
];

if (!empty($social_profiles)) {
    $organization_schema['sameAs'] = $social_profiles;
}

if (!empty($contact_phone_link)) {
    $organization_schema['contactPoint'] = [[
        '@type' => 'ContactPoint',
        'telephone' => $contact_phone_link,
        'contactType' => 'sales',
        'areaServed' => ['KE', 'TZ'],
        'availableLanguage' => ['en']
    ]];
}

$website_schema = [
    '@type' => 'WebSite',
    '@id' => site_url('#website'),
    'url' => $canonical_url,
    'name' => $site_name,
    'description' => $site_description_text,
    'publisher' => ['@id' => site_url('#organization')],
    'potentialAction' => [
        '@type' => 'SearchAction',
        'target' => site_url('products.php') . '?search={search_term_string}',
        'query-input' => 'required name=search_term_string'
    ]
];

$hero_offers = [];
if ($hero_price_kes_value > 0) {
    $hero_offers[] = [
        '@type' => 'Offer',
        'priceCurrency' => 'KES',
        'price' => number_format($hero_price_kes_value, 2, '.', ''),
        'availability' => $availability_url,
        'url' => $hero_product_url,
        'priceValidUntil' => date('Y-m-d', strtotime('+6 months')),
        'seller' => ['@id' => site_url('#organization')]
    ];
}

if ($hero_price_tzs_value > 0) {
    $hero_offers[] = [
        '@type' => 'Offer',
        'priceCurrency' => 'TZS',
        'price' => number_format($hero_price_tzs_value, 2, '.', ''),
        'availability' => $availability_url,
        'url' => $hero_product_url,
        'priceValidUntil' => date('Y-m-d', strtotime('+6 months')),
        'seller' => ['@id' => site_url('#organization')]
    ];
}

$hero_product_schema = [
    '@type' => 'Product',
    '@id' => $hero_product_url . '#product',
    'name' => $hero_product_name,
    'description' => $hero_product_description,
    'sku' => $hero_product_sku,
    'image' => array_values(array_unique(array_filter([$og_image, $site_logo_absolute]))),
    'brand' => [
        '@type' => 'Brand',
        'name' => 'JINKA'
    ],
    'url' => $hero_product_url
];

if (!empty($hero_offers)) {
    $hero_product_schema['offers'] = $hero_offers;
}

if (!empty($hero_badges)) {
    $hero_product_schema['additionalProperty'] = array_map(function ($badgeText) {
        return [
            '@type' => 'PropertyValue',
            'name' => 'Key Feature',
            'value' => $badgeText
        ];
    }, $hero_badges);
}

$structured_data_graph = [
    '@context' => 'https://schema.org',
    '@graph' => [
        $organization_schema,
        $website_schema,
        $hero_product_schema
    ]
];

$structured_data_json = json_encode($structured_data_graph, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

$total_products_count = 0;
$total_products_result = $db->query("SELECT COUNT(*) AS total FROM products WHERE is_active = 1");
if ($total_products_result && ($countRow = $total_products_result->fetch_assoc())) {
    $total_products_count = (int)$countRow['total'];
}

$experience_metric = site_setting('business_years_experience', '19 Years');
$installations_metric = site_setting('installations_completed', '250+');
$customer_rating_metric = site_setting('customer_rating_score', '4.7★');

$category_cards = [];
$category_query = "SELECT c.id, c.name, c.slug, c.sort_order, COUNT(p.id) AS product_count
    FROM categories c
    LEFT JOIN products p ON p.category_id = c.id AND p.is_active = 1
    WHERE c.is_active = 1
    GROUP BY c.id, c.name, c.slug, c.sort_order
    ORDER BY c.sort_order ASC, c.name ASC
    LIMIT 6";
$category_result = $db->query($category_query);
if ($category_result) {
    while ($row = $category_result->fetch_assoc()) {
        $category_cards[] = $row;
    }
}

$featured_count = count($featured_products);
$category_count = count($category_cards);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($page_description); ?>">
    <?php if (!empty($meta_keywords)): ?>
    <meta name="keywords" content="<?php echo htmlspecialchars($meta_keywords); ?>">
    <?php endif; ?>
    <meta name="robots" content="index,follow">
    <link rel="canonical" href="<?php echo htmlspecialchars($canonical_url); ?>">
    <meta property="og:title" content="<?php echo htmlspecialchars($page_title); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($page_description); ?>">
    <meta property="og:url" content="<?php echo htmlspecialchars($canonical_url); ?>">
    <meta property="og:site_name" content="<?php echo htmlspecialchars($site_name); ?>">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="en_KE">
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
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <div class="hero-text">
                    <span class="badge"><?php echo htmlspecialchars($hero_product_category); ?></span>
                    <h2 class="hero-title">
                        <?php echo htmlspecialchars($hero_product_name); ?>
                        <br>
                        <span class="highlight"><?php echo htmlspecialchars($site_tagline ?: 'Professional Printing Equipment'); ?></span>
                    </h2>
                    <p class="hero-description">
                        <?php echo htmlspecialchars($hero_product_description); ?>
                    </p>
                    <div class="hero-price">
                        <div class="price-item">
                            <span class="currency">KES</span>
                            <span class="amount"><?php echo $hero_price_kes; ?></span>
                        </div>
                        <?php if ($hero_price_tzs_value > 0): ?>
                            <div class="price-divider">|</div>
                            <div class="price-item">
                                <span class="currency">TZS</span>
                                <span class="amount"><?php echo $hero_price_tzs; ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($hero_metrics)): ?>
                        <div class="hero-meta">
                            <?php foreach ($hero_metrics as $metric): ?>
                                <div class="hero-meta-item">
                                    <span class="hero-meta-label"><?php echo htmlspecialchars($metric['label']); ?></span>
                                    <span class="hero-meta-value"><?php echo htmlspecialchars($metric['value']); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <div class="hero-cta">
                        <?php if ($hero_product_id): ?>
                            <button class="btn btn-primary btn-lg" id="heroAddToCart"
                                data-product-id="<?php echo (int)$hero_product_id; ?>"
                                data-product-name="<?php echo htmlspecialchars($hero_product_name, ENT_QUOTES); ?>">
                                <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24"><path d="M7 4V2c0-.55-.45-1-1-1s-1 .45-1 1v2H3c-.55 0-1 .45-1 1s.45 1 1 1h2v2c0 .55.45 1 1 1s1-.45 1-1V6h2c.55 0 1-.45 1-1s-.45-1-1-1H7z"/></svg>
                                Add to Cart
                            </button>
                            <?php if (!empty($hero_product_slug)): ?>
                                <a href="product-detail.php?slug=<?php echo urlencode($hero_product_slug); ?>" class="btn btn-outline btn-lg">
                                    <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2a10 10 0 1 1 0 20 10 10 0 0 1 0-20zm1 5h-2v2h2V7zm0 4h-2v6h2v-6z"/></svg>
                                    View Details
                                </a>
                            <?php endif; ?>
                        <?php else: ?>
                            <a href="products.php" class="btn btn-primary btn-lg">
                                <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l8 8h-6v9h-4v-9H4l8-8z"/></svg>
                                Browse Products
                            </a>
                        <?php endif; ?>
                        <?php if (!empty($whatsapp_number_link)): ?>
                            <a href="https://wa.me/<?php echo htmlspecialchars($whatsapp_number_link); ?>?text=<?php echo urlencode('Hi, I\'m interested in ' . $hero_product_name); ?>" class="btn btn-outline btn-lg" target="_blank">
                                <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
                                WhatsApp
                            </a>
                        <?php endif; ?>
                        <?php if (!empty($contact_phone_link)): ?>
                            <a href="tel:<?php echo htmlspecialchars($contact_phone_link); ?>" class="btn btn-outline btn-lg">
                                <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24"><path d="M20.01 15.38c-1.23 0-2.42-.2-3.53-.56a.977.977 0 00-1.01.24l-1.57 1.97c-2.83-1.35-5.48-3.9-6.89-6.83l1.95-1.66c.27-.28.35-.67.24-1.02-.37-1.11-.56-2.3-.56-3.53 0-.54-.45-.99-.99-.99H4.19C3.65 3 3 3.24 3 3.99 3 13.28 10.73 21 20.01 21c.71 0 .99-.63.99-1.18v-3.45c0-.54-.45-.99-.99-.99z"/></svg>
                                Call Tanzania
                            </a>
                        <?php endif; ?>
                        <?php if (!empty($contact_phone_ke_link)): ?>
                            <a href="tel:<?php echo htmlspecialchars($contact_phone_ke_link); ?>" class="btn btn-outline btn-lg">
                                <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24"><path d="M20.01 15.38c-1.23 0-2.42-.2-3.53-.56a.977.977 0 00-1.01.24l-1.57 1.97c-2.83-1.35-5.48-3.9-6.89-6.83l1.95-1.66c.27-.28.35-.67.24-1.02-.37-1.11-.56-2.3-.56-3.53 0-.54-.45-.99-.99-.99H4.19C3.65 3 3 3.24 3 3.99 3 13.28 10.73 21 20.01 21c.71 0 .99-.63.99-1.18v-3.45c0-.54-.45-.99-.99-.99z"/></svg>
                                Call Kenya
                            </a>
                        <?php endif; ?>
                        <a href="#contact" class="btn btn-outline btn-lg">
                            <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24"><path d="M14,2A8,8 0 0,1 22,10A8,8 0 0,1 14,18H13L9,22V18H8A8,8 0 0,1 0,10A8,8 0 0,1 8,2H14Z"/></svg>
                            Get Quote
                        </a>
                    </div>
                    <?php if (!empty($hero_badges)): ?>
                        <div class="trust-badges">
                            <?php foreach ($hero_badges as $index => $badge_text): ?>
                                <div class="badge-item">
                                    <?php if ($index === 0): ?>
                                        <svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 10.99h7c-.53 4.12-3.28 7.79-7 8.94V12H5V6.3l7-3.11v8.8z"/></svg>
                                    <?php elseif ($index === 1): ?>
                                        <svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
                                    <?php else: ?>
                                        <svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24"><path d="M20 6h-2.18c.11-.31.18-.65.18-1a2.996 2.996 0 00-5.5-1.65l-.5.67-.5-.68C10.96 2.54 10.05 2 9 2 7.34 2 6 3.34 6 5c0 .35.07.69.18 1H4c-1.11 0-1.99.89-1.99 2L2 19c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V8c0-1.11-.89-2-2-2zm-5-2c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zM9 4c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm11 15H4v-2h16v2zm0-5H4V8h5.08L7 10.83 8.62 12 11 8.76l1-1.36 1 1.36L15.38 12 17 10.83 14.92 8H20v6z"/></svg>
                                    <?php endif; ?>
                                    <span><?php echo htmlspecialchars($badge_text); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="hero-image">
                    <img src="<?php echo htmlspecialchars($hero_image); ?>" alt="<?php echo htmlspecialchars($hero_product_name); ?>">
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-number"><?php echo number_format($total_products_count); ?></div>
                    <div class="stat-label">Active SKUs</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo $category_count > 0 ? number_format($category_count) : '&mdash;'; ?></div>
                    <div class="stat-label">Solution Categories</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo htmlspecialchars($installations_metric); ?></div>
                    <div class="stat-label">Installations Delivered</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo htmlspecialchars($customer_rating_metric); ?></div>
                    <div class="stat-label">Customer Rating</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Products Section -->
    <?php if (count($featured_products) > 0): ?>
    <section class="featured-products">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Featured Equipment</h2>
                <p class="section-description">Discover our most popular professional printing equipment</p>
                <a href="products.php" class="view-all-link">View All Products →</a>
            </div>
            <div class="products-grid">
                <?php foreach ($featured_products as $product): 
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
                ?>
                    <div class="product-card">
                        <div class="product-image">
                            <a href="product-detail.php?slug=<?php echo urlencode($product['slug']); ?>">
                                <?php 
                                    $cardImage = normalize_product_image_url($product['image'] ?? '');
                                ?>
                                <?php if (!empty($cardImage)): ?>
                                    <img src="<?php echo htmlspecialchars($cardImage); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                <?php else: ?>
                                    <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='400' height='300' viewBox='0 0 400 300'%3E%3Crect fill='%23f1f5f9' width='400' height='300'/%3E%3Ctext x='50%25' y='50%25' dominant-baseline='middle' text-anchor='middle' fill='%2394a3b8' font-family='Arial' font-size='18' font-weight='bold'%3ENo Image%3C/text%3E%3C/svg%3E" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                <?php endif; ?>
                            </a>
                            <div class="product-badge featured">Featured</div>
                        </div>
                        <div class="product-info">
                            <div class="product-category">Cutting Equipment</div>
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
                                <a href="product-detail.php?slug=<?php echo urlencode($product['slug']); ?>" class="btn btn-primary">
                                    View Details
                                </a>
                                <a href="https://wa.me/<?php echo str_replace('+', '', $whatsapp_number); ?>?text=Hi, I'm interested in <?php echo urlencode($product['name']); ?>" 
                                   class="btn btn-outline" target="_blank" title="Inquire on WhatsApp">
                                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Features Section -->
    <section id="features" class="features">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Professional Features</h2>
                <p class="section-description">Engineered for precision and reliability in commercial environments</p>
            </div>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg width="40" height="40" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
                    </div>
                    <h3 class="feature-title">High Precision Stepper Motor</h3>
                    <p class="feature-description">Brand stepper motor ensures high precision cutting with low noise operation for professional results every time.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg width="40" height="40" fill="currentColor" viewBox="0 0 24 24"><path d="M20 8h-2.81c-.45-.78-1.07-1.45-1.82-1.96L17 4.41 15.59 3l-2.17 2.17C12.96 5.06 12.49 5 12 5c-.49 0-.96.06-1.41.17L8.41 3 7 4.41l1.62 1.63C7.88 6.55 7.26 7.22 6.81 8H4v2h2.09c-.05.33-.09.66-.09 1v1H4v2h2v1c0 .34.04.67.09 1H4v2h2.81c1.04 1.79 2.97 3 5.19 3s4.15-1.21 5.19-3H20v-2h-2.09c.05-.33.09-.66.09-1v-1h2v-2h-2v-1c0-.34-.04-.67-.09-1H20V8zm-6 8h-4v-2h4v2zm0-4h-4v-2h4v2z"/></svg>
                    </div>
                    <h3 class="feature-title">Universal Software Support</h3>
                    <p class="feature-description">Supports universal engraving languages and works with various design software on both Windows and MAC.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg width="40" height="40" fill="currentColor" viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
                    </div>
                    <h3 class="feature-title">Adjustable Settings</h3>
                    <p class="feature-description">Fully adjustable cutting speed (up to 800mm/s) and force (500g) for different materials and applications.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg width="40" height="40" fill="currentColor" viewBox="0 0 24 24"><path d="M4 6h18V4H4c-1.1 0-2 .9-2 2v11H0v3h14v-3H4V6zm19 2h-6c-.55 0-1 .45-1 1v10c0 .55.45 1 1 1h6c.55 0 1-.45 1-1V9c0-.55-.45-1-1-1zm-1 9h-4v-7h4v7z"/></svg>
                    </div>
                    <h3 class="feature-title">Dual Connectivity</h3>
                    <p class="feature-description">USB 2.0 and RS-232C ports provide flexible connectivity options for modern and legacy systems.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg width="40" height="40" fill="currentColor" viewBox="0 0 24 24"><path d="M17 10.5V7c0-.55-.45-1-1-1H4c-.55 0-1 .45-1 1v10c0 .55.45 1 1 1h12c.55 0 1-.45 1-1v-3.5l4 4v-11l-4 4z"/></svg>
                    </div>
                    <h3 class="feature-title">Large Format Cutting</h3>
                    <p class="feature-description">53-inch (1350mm) paper feed width and 2-meter cutting length handle large commercial projects with ease.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg width="40" height="40" fill="currentColor" viewBox="0 0 24 24"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 10.99h7c-.53 4.12-3.28 7.79-7 8.94V12H5V6.3l7-3.11v8.8z"/></svg>
                    </div>
                    <h3 class="feature-title">CE Certified Quality</h3>
                    <p class="feature-description">CE certification ensures compliance with EU safety standards and provides quality assurance for your investment.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Specifications Section -->
    <section id="specifications" class="specifications">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Technical Specifications</h2>
                <p class="section-description">Complete technical details of the JINKA XL-1351E with interactive comparison tools</p>
                <div class="spec-actions">
                    <button class="btn btn-outline" onclick="downloadSpecSheet()">
                        <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24"><path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20Z"/></svg>
                        Download Spec Sheet
                    </button>
                    <button class="btn btn-primary" onclick="requestQuote()">
                        <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24"><path d="M14,2A8,8 0 0,1 22,10A8,8 0 0,1 14,18H13L9,22V18H8A8,8 0 0,1 0,10A8,8 0 0,1 8,2H14Z"/></svg>
                        Request Quote
                    </button>
                </div>
            </div>
            
            <!-- Specification Categories -->
            <div class="specs-categories">
                <button class="spec-category-btn active" data-category="general" onclick="showSpecCategory('general')">General</button>
                <button class="spec-category-btn" data-category="cutting" onclick="showSpecCategory('cutting')">Cutting Performance</button>
                <button class="spec-category-btn" data-category="connectivity" onclick="showSpecCategory('connectivity')">Connectivity</button>
                <button class="spec-category-btn" data-category="physical" onclick="showSpecCategory('physical')">Physical</button>
            </div>
            
                <div class="specs-content">
                <div class="specs-visual">
                    <div class="specs-image">
                        <img src="images/plotter-hero.webp" alt="JINKA Cutting Plotter Specifications" id="specImage">
                    </div>
                </div>
                <div class="spec-highlights">
                    <div class="highlight-item">
                        <div class="highlight-icon">
                            <svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
                        </div>
                        <div class="highlight-content">
                            <h4>High Precision</h4>
                            <p>±0.1mm cutting accuracy</p>
                        </div>
                    </div>
                    <div class="highlight-item">
                        <div class="highlight-icon">
                            <svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24"><path d="M13,14H11V10H13M13,18H11V16H13M1,21H23L12,2L1,21Z"/></svg>
                        </div>
                        <div class="highlight-content">
                            <h4>Professional Grade</h4>
                            <p>Industrial stepper motor</p>
                        </div>
                    </div>
                    <div class="highlight-item">
                        <div class="highlight-icon">
                            <svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 10.99h7c-.53 4.12-3.28 7.79-7 8.94V12H5V6.3l7-3.11v8.8z"/></svg>
                        </div>
                        <div class="highlight-content">
                            <h4>CE Certified</h4>
                            <p>European safety standards</p>
                        </div>
                    </div>
                </div>
                
                <div class="specs-tables">
                    <!-- General Specifications -->
                    <div class="spec-category active" data-category="general">
                        <div class="specs-table">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Specification</th>
                                        <th>Details</th>
                                        <th>Benefits</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Model Number</td>
                                        <td>XL-1351E</td>
                                        <td>Latest generation model</td>
                                    </tr>
                                    <tr>
                                        <td>Brand Name</td>
                                        <td>JINKA</td>
                                        <td>19 years manufacturing experience</td>
                                    </tr>
                                    <tr>
                                        <td>Certification</td>
                                        <td>CE Certified</td>
                                        <td>European safety compliance</td>
                                    </tr>
                                    <tr>
                                        <td>Warranty</td>
                                        <td>12 Months</td>
                                        <td>Full parts & service coverage</td>
                                    </tr>
                                    <tr>
                                        <td>Place of Origin</td>
                                        <td>Anhui, China</td>
                                        <td>ISO certified manufacturing facility</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Cutting Performance -->
                    <div class="spec-category" data-category="cutting">
                        <div class="specs-table">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Specification</th>
                                        <th>Details</th>
                                        <th>Performance Impact</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Cutting Width</td>
                                        <td>1210mm (47.6")</td>
                                        <td>Large format capability</td>
                                    </tr>
                                    <tr>
                                        <td>Paper Feed Width</td>
                                        <td>1350mm (53.1")</td>
                                        <td>Handles wide material rolls</td>
                                    </tr>
                                    <tr>
                                        <td>Cutting Length</td>
                                        <td>2000mm (6.5 feet)</td>
                                        <td>Long continuous cuts</td>
                                    </tr>
                                    <tr>
                                        <td>Cutting Speed</td>
                                        <td>10-800mm/s</td>
                                        <td>Variable speed control</td>
                                    </tr>
                                    <tr>
                                        <td>Cutter Pressure</td>
                                        <td>10-500g</td>
                                        <td>Adjustable for material types</td>
                                    </tr>
                                    <tr>
                                        <td>Cutting Accuracy</td>
                                        <td>±0.1mm</td>
                                        <td>Professional precision</td>
                                    </tr>
                                    <tr>
                                        <td>Driver Motor</td>
                                        <td>Stepper Motor</td>
                                        <td>High precision, low noise</td>
                                    </tr>
                                    <tr>
                                        <td>Repeat Cutting</td>
                                        <td>Yes (Memory)</td>
                                        <td>Batch production efficiency</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Connectivity -->
                    <div class="spec-category" data-category="connectivity">
                        <div class="specs-table">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Specification</th>
                                        <th>Details</th>
                                        <th>Compatibility</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>USB Connection</td>
                                        <td>USB 2.0</td>
                                        <td>Plug & play connectivity</td>
                                    </tr>
                                    <tr>
                                        <td>Serial Port</td>
                                        <td>RS-232C</td>
                                        <td>Legacy system support</td>
                                    </tr>
                                    <tr>
                                        <td>Operating System</td>
                                        <td>Windows & macOS</td>
                                        <td>Cross-platform compatibility</td>
                                    </tr>
                                    <tr>
                                        <td>Cache Capacity</td>
                                        <td>1MB / 4MB</td>
                                        <td>Stores complex designs</td>
                                    </tr>
                                    <tr>
                                        <td>File Formats</td>
                                        <td>PLT, DXF, AI, EPS</td>
                                        <td>Industry standard formats</td>
                                    </tr>
                                    <tr>
                                        <td>Software Support</td>
                                        <td>Universal Engraving</td>
                                        <td>Works with most design software</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Physical Specifications -->
                    <div class="spec-category" data-category="physical">
                        <div class="specs-table">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Specification</th>
                                        <th>Details</th>
                                        <th>Installation Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Dimensions (L×W×H)</td>
                                        <td>163 × 34 × 44 cm</td>
                                        <td>Requires 2m × 1m workspace</td>
                                    </tr>
                                    <tr>
                                        <td>Weight</td>
                                        <td>35 kg</td>
                                        <td>Stable, portable design</td>
                                    </tr>
                                    <tr>
                                        <td>Power Supply</td>
                                        <td>AC 110-240V, 50/60Hz</td>
                                        <td>Universal power compatibility</td>
                                    </tr>
                                    <tr>
                                        <td>Power Consumption</td>
                                        <td>≤ 100W</td>
                                        <td>Energy efficient operation</td>
                                    </tr>
                                    <tr>
                                        <td>Operating Temperature</td>
                                        <td>5-40°C</td>
                                        <td>Suitable for most environments</td>
                                    </tr>
                                    <tr>
                                        <td>Storage Temperature</td>
                                        <td>-10 to 60°C</td>
                                        <td>Safe storage range</td>
                                    </tr>
                                    <tr>
                                        <td>Humidity Range</td>
                                        <td>20-80% RH</td>
                                        <td>Non-condensing conditions</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Material Compatibility -->
            <div class="material-compatibility">
                <h3>Material Compatibility</h3>
                <div class="materials-grid">
                    <div class="material-item">
                        <div class="material-icon">📄</div>
                        <h4>Vinyl</h4>
                        <p>Adhesive vinyl, decorative films</p>
                    </div>
                    <div class="material-item">
                        <div class="material-icon">👕</div>
                        <h4>Heat Transfer</h4>
                        <p>HTV, flock, glitter vinyl</p>
                    </div>
                    <div class="material-item">
                        <div class="material-icon">🏷️</div>
                        <h4>Paper</h4>
                        <p>Sticker paper, cardstock</p>
                    </div>
                    <div class="material-item">
                        <div class="material-icon">✨</div>
                        <h4>Reflective</h4>
                        <p>Reflective sheeting, safety materials</p>
                    </div>
                    <div class="material-item">
                        <div class="material-icon">🎯</div>
                        <h4>Magnetic</h4>
                        <p>Magnetic sheeting, flexible magnets</p>
                    </div>
                    <div class="material-item">
                        <div class="material-icon">🔧</div>
                        <h4>Sandblast</h4>
                        <p>Sandblast resist, stencil material</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Applications Section -->
    <section id="applications" class="applications">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Applications & Use Cases</h2>
                <p class="section-description">Perfect for a wide range of commercial and business applications</p>
            </div>
            <div class="applications-grid">
                <div class="application-card">
                    <div class="application-number">01</div>
                    <h3 class="application-title">Sign Making</h3>
                    <p class="application-description">Create professional signage for storefronts, events, exhibitions, and commercial properties with precision cutting.</p>
                </div>
                <div class="application-card">
                    <div class="application-number">02</div>
                    <h3 class="application-title">Vehicle Branding</h3>
                    <p class="application-description">Cut vinyl graphics for car wraps, fleet branding, vehicle decals, and automotive customization projects.</p>
                </div>
                <div class="application-card">
                    <div class="application-number">03</div>
                    <h3 class="application-title">Advertising Materials</h3>
                    <p class="application-description">Produce marketing materials, promotional stickers, window graphics, and advertising displays for clients.</p>
                </div>
                <div class="application-card">
                    <div class="application-number">04</div>
                    <h3 class="application-title">Custom Stickers & Decals</h3>
                    <p class="application-description">Design and cut custom stickers, labels, decals, and graphics for products, packaging, and branding.</p>
                </div>
                <div class="application-card">
                    <div class="application-number">05</div>
                    <h3 class="application-title">Heat Transfer Vinyl</h3>
                    <p class="application-description">Cut heat transfer vinyl for t-shirt printing, apparel customization, and textile decoration businesses.</p>
                </div>
                <div class="application-card">
                    <div class="application-number">06</div>
                    <h3 class="application-title">Wall Graphics</h3>
                    <p class="application-description">Create large-format wall decals, murals, and interior graphics for offices, retail spaces, and homes.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta">
        <div class="container">
            <div class="cta-content">
                <h2 class="cta-title">Ready to Grow Your Business?</h2>
                <p class="cta-description">Get professional-grade vinyl cutting equipment with installation, training, and support included.</p>
                <div class="cta-price">
                    <div class="price-label">Starting at</div>
                    <div class="price-amount">KES <?php echo $hero_price_kes; ?></div>
                    <div class="price-note">Installation & Training Included</div>
                </div>
                <div class="cta-buttons">
                    <a href="https://wa.me/<?php echo htmlspecialchars($whatsapp_number_link); ?>?text=Hi,%20I'm%20interested%20in%20the%20JINKA%20XL-1351E%20Cutting%20Plotter" class="btn btn-primary btn-lg" target="_blank">
                        <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
                        Order via WhatsApp
                    </a>
                    <a href="tel:<?php echo htmlspecialchars($contact_phone_link); ?>" class="btn btn-outline-light btn-lg">
                        <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24"><path d="M20.01 15.38c-1.23 0-2.42-.2-3.53-.56a.977.977 0 00-1.01.24l-1.57 1.97c-2.83-1.35-5.48-3.9-6.89-6.83l1.95-1.66c.27-.28.35-.67.24-1.02-.37-1.11-.56-2.3-.56-3.53 0-.54-.45-.99-.99-.99H4.19C3.65 3 3 3.24 3 3.99 3 13.28 10.73 21 20.01 21c.71 0 .99-.63.99-1.18v-3.45c0-.54-.45-.99-.99-.99z"/></svg>
                        Call Tanzania
                    </a>
                    <a href="tel:<?php echo htmlspecialchars($contact_phone_ke_link); ?>" class="btn btn-outline-light btn-lg">
                        <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24"><path d="M20.01 15.38c-1.23 0-2.42-.2-3.53-.56a.977.977 0 00-1.01.24l-1.57 1.97c-2.83-1.35-5.48-3.9-6.89-6.83l1.95-1.66c.27-.28.35-.67.24-1.02-.37-1.11-.56-2.3-.56-3.53 0-.54-.45-.99-.99-.99H4.19C3.65 3 3 3.24 3 3.99 3 13.28 10.73 21 20.01 21c.71 0 .99-.63.99-1.18v-3.45c0-.54-.45-.99-.99-.99z"/></svg>
                        Call Kenya
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="contact">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Get In Touch</h2>
                <p class="section-description">Contact us for inquiries, demonstrations, or to place your order</p>
            </div>
            <div class="contact-content">
                <div class="contact-info">
                    <div class="contact-item">
                        <div class="contact-icon">
                            <svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24"><path d="M20.01 15.38c-1.23 0-2.42-.2-3.53-.56a.977.977 0 00-1.01.24l-1.57 1.97c-2.83-1.35-5.48-3.9-6.89-6.83l1.95-1.66c.27-.28.35-.67.24-1.02-.37-1.11-.56-2.3-.56-3.53 0-.54-.45-.99-.99-.99H4.19C3.65 3 3 3.24 3 3.99 3 13.28 10.73 21 20.01 21c.71 0 .99-.63.99-1.18v-3.45c0-.54-.45-.99-.99-.99z"/></svg>
                        </div>
                        <div>
                            <h4>Phone</h4>
                            <p>
                                Tanzania: <a href="tel:<?php echo $phone_number; ?>"><?php echo $phone_number; ?></a><br>
                                Kenya: <a href="tel:<?php echo $phone_number_ke; ?>"><?php echo $phone_number_ke; ?></a>
                            </p>
                        </div>
                    </div>
                    <div class="contact-item">
                        <div class="contact-icon">
                            <svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
                        </div>
                        <div>
                            <h4>WhatsApp</h4>
                            <p><a href="https://wa.me/<?php echo str_replace('+', '', $whatsapp_number); ?>" target="_blank"><?php echo $whatsapp_number; ?></a></p>
                        </div>
                    </div>
                    <div class="contact-item">
                        <div class="contact-icon">
                            <svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
                        </div>
                        <div>
                            <h4>Email</h4>
                            <p><a href="mailto:<?php echo $email; ?>"><?php echo $email; ?></a></p>
                        </div>
                    </div>
                    <div class="contact-item">
                        <div class="contact-icon">
                            <svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
                        </div>
                        <div>
                            <h4>Service Areas</h4>
                            <p>Kenya & Tanzania</p>
                        </div>
                    </div>
                </div>
                <div class="contact-form-wrapper">
                    <form action="contact.php" method="POST" class="contact-form">
                        <div class="form-group">
                            <label for="name">Full Name</label>
                            <input type="text" id="name" name="name" required>
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label for="business">Business Name (Optional)</label>
                            <input type="text" id="business" name="business">
                        </div>
                        <div class="form-group">
                            <label for="message">Message</label>
                            <textarea id="message" name="message" rows="4" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary btn-lg">Send Inquiry</button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-col footer-col-brand">
                    <?php if (!empty($footer_logo)): ?>
                        <div class="footer-logo">
                            <img src="<?php echo htmlspecialchars($footer_logo); ?>" alt="<?php echo htmlspecialchars($business_name); ?>" class="footer-logo-img">
                        </div>
                    <?php else: ?>
                        <h3><?php echo htmlspecialchars($business_name); ?></h3>
                    <?php endif; ?>
                    <p><?php echo htmlspecialchars($footer_about); ?></p>
                    <div class="footer-social">
                        <?php if (!empty($whatsapp_number)): ?>
                        <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $whatsapp_number); ?>" target="_blank" title="WhatsApp">
                            <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                            </svg>
                        </a>
                        <?php endif; ?>
                        <?php if (!empty($facebook_url)): ?>
                        <a href="<?php echo htmlspecialchars($facebook_url); ?>" target="_blank" title="Facebook">
                            <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                            </svg>
                        </a>
                        <?php endif; ?>
                        <?php if (!empty($twitter_url)): ?>
                        <a href="<?php echo htmlspecialchars($twitter_url); ?>" target="_blank" title="Twitter">
                            <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
                            </svg>
                        </a>
                        <?php endif; ?>
                        <?php if (!empty($instagram_url)): ?>
                        <a href="<?php echo htmlspecialchars($instagram_url); ?>" target="_blank" title="Instagram">
                            <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 0C8.74 0 8.333.015 7.053.072 5.775.132 4.905.333 4.14.63c-.789.306-1.459.717-2.126 1.384S.935 3.35.63 4.14C.333 4.905.131 5.775.072 7.053.012 8.333 0 8.74 0 12s.015 3.667.072 4.947c.06 1.277.261 2.148.558 2.913.306.788.717 1.459 1.384 2.126.667.666 1.336 1.079 2.126 1.384.766.296 1.636.499 2.913.558C8.333 23.988 8.74 24 12 24s3.667-.015 4.947-.072c1.277-.06 2.148-.262 2.913-.558.788-.306 1.459-.718 2.126-1.384.666-.667 1.079-1.335 1.384-2.126.296-.765.499-1.636.558-2.913.06-1.28.072-1.687.072-4.947s-.015-3.667-.072-4.947c-.06-1.277-.262-2.149-.558-2.913-.306-.789-.718-1.459-1.384-2.126C21.319 1.347 20.651.935 19.86.63c-.765-.297-1.636-.499-2.913-.558C15.667.012 15.26 0 12 0zm0 2.16c3.203 0 3.585.016 4.85.071 1.17.055 1.805.249 2.227.415.562.217.96.477 1.382.896.419.42.679.819.896 1.381.164.422.36 1.057.413 2.227.057 1.266.07 1.646.07 4.85s-.015 3.585-.074 4.85c-.061 1.17-.256 1.805-.421 2.227-.224.562-.479.96-.899 1.382-.419.419-.824.679-1.38.896-.42.164-1.065.36-2.235.413-1.274.057-1.649.07-4.859.07-3.211 0-3.586-.015-4.859-.074-1.171-.061-1.816-.256-2.236-.421-.569-.224-.96-.479-1.379-.899-.421-.419-.69-.824-.9-1.38-.165-.42-.359-1.065-.42-2.235-.045-1.26-.061-1.649-.061-4.844 0-3.196.016-3.586.061-4.861.061-1.17.255-1.814.42-2.234.21-.57.479-.96.9-1.381.419-.419.81-.689 1.379-.898.42-.166 1.051-.361 2.221-.421 1.275-.045 1.65-.06 4.859-.06l.045.03zm0 3.678c-3.405 0-6.162 2.76-6.162 6.162 0 3.405 2.76 6.162 6.162 6.162 3.405 0 6.162-2.76 6.162-6.162 0-3.405-2.76-6.162-6.162-6.162zM12 16c-2.21 0-4-1.79-4-4s1.79-4 4-4 4 1.79 4 4-1.79 4-4 4zm7.846-10.405c0 .795-.646 1.44-1.44 1.44-.795 0-1.44-.646-1.44-1.44 0-.794.646-1.439 1.44-1.439.793-.001 1.44.645 1.44 1.439z"/>
                            </svg>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="footer-col">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="index.php">Home</a></li>
                        <li><a href="products.php">Products</a></li>
                        <li><a href="#features">Features</a></li>
                        <li><a href="#applications">Applications</a></li>
                        <li><a href="contact.php">Contact Us</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Get in Touch</h4>
                    <?php if (!empty($phone_number) || !empty($phone_number_ke)): ?>
                    <div class="footer-contact-item">
                        <div class="footer-contact-icon">
                            <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M20.01 15.38c-1.23 0-2.42-.2-3.53-.56a.977.977 0 00-1.01.24l-1.57 1.97c-2.83-1.35-5.48-3.9-6.89-6.83l1.95-1.66c.27-.28.35-.67.24-1.02-.37-1.11-.56-2.3-.56-3.53 0-.54-.45-.99-.99-.99H4.19C3.65 3 3 3.24 3 3.99 3 13.28 10.73 21 20.01 21c.71 0 .99-.63.99-1.18v-3.45c0-.54-.45-.99-.99-.99z"/>
                            </svg>
                        </div>
                        <div>
                            <?php if (!empty($phone_number)): ?>
                            <div><?php echo htmlspecialchars($footer_phone_label_tz); ?>: <a href="tel:<?php echo $phone_number; ?>"><?php echo htmlspecialchars($phone_number); ?></a></div>
                            <?php endif; ?>
                            <?php if (!empty($phone_number_ke)): ?>
                            <div style="margin-top: 0.25rem;"><?php echo htmlspecialchars($footer_phone_label_ke); ?>: <a href="tel:<?php echo $phone_number_ke; ?>"><?php echo htmlspecialchars($phone_number_ke); ?></a></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($email)): ?>
                    <div class="footer-contact-item">
                        <div class="footer-contact-icon">
                            <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                            </svg>
                        </div>
                        <a href="mailto:<?php echo $email; ?>"><?php echo htmlspecialchars($email); ?></a>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($footer_address)): ?>
                    <div class="footer-contact-item">
                        <div class="footer-contact-icon">
                            <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                            </svg>
                        </div>
                        <span><?php echo htmlspecialchars($footer_address); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="footer-col">
                    <h4>Business Hours</h4>
                    <?php if (!empty($footer_hours_weekday)): ?>
                    <div class="footer-hours-item">
                        <span>Mon - Fri</span>
                        <strong><?php echo htmlspecialchars($footer_hours_weekday); ?></strong>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($footer_hours_saturday)): ?>
                    <div class="footer-hours-item">
                        <span>Saturday</span>
                        <strong><?php echo htmlspecialchars($footer_hours_saturday); ?></strong>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($footer_hours_sunday)): ?>
                    <div class="footer-hours-item">
                        <span>Sunday</span>
                        <strong><?php echo htmlspecialchars($footer_hours_sunday); ?></strong>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($footer_whatsapp_label)): ?>
                    <div class="footer-hours-item">
                        <span>WhatsApp</span>
                        <strong style="color: #10b981;"><?php echo htmlspecialchars($footer_whatsapp_label); ?></strong>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <div class="footer-bottom-content">
                <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($business_name); ?>. <?php echo !empty($footer_copyright) ? htmlspecialchars($footer_copyright) : 'All rights reserved.'; ?></p>
                <div class="footer-bottom-links">
                    <a href="#">Privacy Policy</a>
                    <a href="#">Terms of Service</a>
                    <a href="#">Shipping Policy</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Shopping Cart Modal -->
    <div class="cart-modal" id="cartModal">
        <div class="cart-overlay" onclick="toggleCart()"></div>
        <div class="cart-content">
            <div class="cart-header">
                <h3>Shopping Cart</h3>
                <button class="cart-close" onclick="toggleCart()">
                    <svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                    </svg>
                </button>
            </div>
            <div class="cart-body">
                <div id="cartItems" class="cart-items">
                    <!-- Cart items will be populated here -->
                </div>
                <div class="cart-empty" id="cartEmpty" style="display: none;">
                    <svg width="64" height="64" fill="currentColor" viewBox="0 0 24 24" opacity="0.3">
                        <path d="M7 4V2c0-.55-.45-1-1-1s-1 .45-1 1v2H3c-.55 0-1 .45-1 1s.45 1 1 1h2v2c0 .55.45 1 1 1s1-.45 1-1V6h2c.55 0 1-.45 1-1s-.45-1-1-1H7z"/>
                    </svg>
                    <p>Your cart is empty</p>
                    <button class="btn btn-primary" onclick="toggleCart()">Continue Shopping</button>
                </div>
            </div>
            <div class="cart-footer" id="cartFooter">
                <div class="cart-total">
                    <div class="total-row">
                        <span>Subtotal:</span>
                        <span id="cartSubtotal">KES 0</span>
                    </div>
                    <div class="total-row">
                        <span>Tax (16%):</span>
                        <span id="cartTax">KES 0</span>
                    </div>
                    <div class="total-row total-main">
                        <span>Total:</span>
                        <span id="cartTotal">KES 0</span>
                    </div>
                </div>
                <div class="cart-actions">
                    <button class="btn btn-outline" onclick="clearCart()">Clear Cart</button>
                    <button class="btn btn-primary" onclick="proceedToCheckout()">Proceed to Checkout</button>
                </div>
            </div>
        </div>
    </div>

    <script src="js/script.js"></script>
</body>
</html>
