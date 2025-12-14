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
// Determine primary hero product (lock to JINKA PRO 1350)
$default_hero_product = [
    'id' => null,
    'slug' => 'jinka-pro-1350',
    'name' => 'JINKA PRO 1350 Cutting Plotter',
    'sku' => 'JINKA-PRO-1350',
    'category_name' => 'Professional Vinyl Cutter',
    'price_kes' => 580,
    'price_tzs' => 1656800,
    'short_description' => '53-inch JINKA PRO 1350 vinyl cutting plotter with precision cutting, engineered for professional sign makers.',
    'description' => 'Meet the JINKA PRO 1350 — a 53-inch vinyl cutting plotter purpose-built for sign shops and branding studios across East Africa. Delivered with installation, calibration, and operator training so your team can start producing wraps, decals, signage, and apparel graphics immediately.',
    'stock_quantity' => 12,
    'features' => json_encode([
        'High precision cutting for professional results',
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

$hero_slug_candidates[] = 'jinka-pro-1350';
$hero_slug_candidates[] = 'jinka-1350';
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
$configured_hero_image = site_setting('hero_image', '');
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
$hero_product_url = $hero_product_slug ? site_url('product-detail?slug=' . urlencode($hero_product_slug)) : $canonical_url;

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
        'target' => site_url('products') . '?search={search_term_string}',
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
    <link rel="stylesheet" href="css/header-modern.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/theme-variables.php?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/responsive-global.css?v=<?php echo time(); ?>">
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
    <section class="hero" style="padding: 4rem 0; background: linear-gradient(135deg, #fef3ed 0%, #fff7ed 50%, #ffffff 100%); position: relative; overflow: hidden;">
        <!-- Decorative Background Elements -->
        <div style="position: absolute; top: -100px; right: -100px; width: 400px; height: 400px; background: radial-gradient(circle, rgba(255, 89, 0, 0.08) 0%, transparent 70%); border-radius: 50%; pointer-events: none;"></div>
        <div style="position: absolute; bottom: -150px; left: -150px; width: 500px; height: 500px; background: radial-gradient(circle, rgba(255, 89, 0, 0.05) 0%, transparent 70%); border-radius: 50%; pointer-events: none;"></div>
        
        <div class="container" style="position: relative; z-index: 1;">
            <div class="hero-content" style="display: grid; grid-template-columns: 1fr 1fr; gap: 4rem; align-items: center;">
                <!-- Left Column: Product Info -->
                <div class="hero-text" style="animation: fadeInLeft 0.8s ease-out;">
                    <div style="margin-bottom: 1.5rem;">
                        <span class="badge" style="background: linear-gradient(135deg, #ff5900 0%, #e64f00 100%); color: white; padding: 0.625rem 1.25rem; border-radius: 50px; font-size: 0.875rem; font-weight: 700; display: inline-flex; align-items: center; gap: 0.5rem; box-shadow: 0 4px 12px rgba(255, 89, 0, 0.25);">
                            <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                            <?php echo htmlspecialchars($hero_product_category); ?>
                        </span>
                        <?php if ($hero_stock_qty > 0 && $hero_stock_qty <= 5): ?>
                        <span style="background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); color: white; padding: 0.625rem 1.25rem; border-radius: 50px; font-size: 0.875rem; font-weight: 700; display: inline-flex; align-items: center; gap: 0.5rem; margin-left: 0.75rem; box-shadow: 0 4px 12px rgba(251, 191, 36, 0.25); animation: pulse 2s infinite;">
                            🔥 Only <?php echo $hero_stock_qty; ?> Left
                        </span>
                        <?php endif; ?>
                    </div>
                    
                    <h1 class="hero-title" style="font-size: 3rem; font-weight: 800; line-height: 1.1; margin-bottom: 1.5rem; color: #0f172a; letter-spacing: -0.02em;">
                        <?php echo htmlspecialchars($hero_product_name); ?>
                    </h1>
                    
                    <p class="hero-description" style="font-size: 1.25rem; color: #475569; margin-bottom: 2rem; line-height: 1.7; font-weight: 400;">
                        <?php echo htmlspecialchars($hero_product_description); ?>
                    </p>

                    <!-- Dynamic Currency Price -->
                    <div class="hero-price" style="margin-bottom: 2rem; padding: 1.5rem; background: white; border-radius: 16px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08); border: 1px solid #e2e8f0;">
                        <?php
                        $hero_currency = $currencyDetector->getCurrentCurrency();
                        $hero_price = $currencyDetector->getPrice($hero_product['price_kes']);
                        $hero_formatted_price = $currencyDetector->formatPrice($hero_price);
                        $hero_stock_available = ($hero_stock_qty !== null && $hero_stock_qty > 0);
                        ?>
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <div>
                                <div style="font-size: 0.875rem; color: #64748b; margin-bottom: 0.5rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">Starting From</div>
                                <div style="font-size: 3rem; font-weight: 800; background: linear-gradient(135deg, #ff5900 0%, #e64f00 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; line-height: 1;">
                                    <?php echo $hero_formatted_price; ?>
                                </div>
                                <div style="font-size: 0.875rem; color: #94a3b8; margin-top: 0.5rem;">
                                    Price shown in <?php echo $hero_currency; ?> • 
                                    <a href="#" onclick="document.querySelector('.currency-toggle').click(); return false;" style="color: #ff5900; text-decoration: underline;">Change Currency</a>
                                </div>
                            </div>
                            <div style="text-align: right;">
                                <?php if ($hero_stock_available): ?>
                                    <div style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1.25rem; background: #dcfce7; color: #166534; border-radius: 12px; font-size: 0.875rem; font-weight: 700;">
                                        <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z"/></svg>
                                        <?php echo htmlspecialchars($hero_stock_text); ?>
                                    </div>
                                    <div style="font-size: 0.75rem; color: #64748b; margin-top: 0.5rem;">
                                        <?php if ($hero_stock_qty > 10): ?>
                                            Ships in 1-2 days
                                        <?php elseif ($hero_stock_qty > 0): ?>
                                            Limited stock • Order soon
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <div style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1.25rem; background: #fef3c7; color: #d97706; border-radius: 12px; font-size: 0.875rem; font-weight: 700;">
                                        <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
                                        Available on Backorder
                                    </div>
                                    <div style="font-size: 0.75rem; color: #64748b; margin-top: 0.5rem;">Contact us for availability</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Trust Signals -->
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; margin-bottom: 2.5rem;">
                        <div style="display: flex; align-items: center; gap: 0.75rem; padding: 1rem; background: white; border-radius: 12px; border: 1px solid #e2e8f0; transition: all 0.3s ease;">
                            <div style="flex-shrink: 0; width: 40px; height: 40px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                <svg width="20" height="20" fill="white" viewBox="0 0 24 24"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/></svg>
                            </div>
                            <div>
                                <div style="font-weight: 700; color: #0f172a; font-size: 0.875rem;">12 Month Warranty</div>
                                <div style="font-size: 0.75rem; color: #64748b;">Full protection</div>
                            </div>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.75rem; padding: 1rem; background: white; border-radius: 12px; border: 1px solid #e2e8f0; transition: all 0.3s ease;">
                            <div style="flex-shrink: 0; width: 40px; height: 40px; background: #ff5900; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                <svg width="20" height="20" fill="white" viewBox="0 0 24 24"><path d="M12 2L4 5v6.09c0 5.05 3.41 9.76 8 10.91 4.59-1.15 8-5.86 8-10.91V5l-8-3zm6 9.09c0 4-2.55 7.7-6 8.83-3.45-1.13-6-4.82-6-8.83V6.31l6-2.12 6 2.12v4.78z"/></svg>
                            </div>
                            <div>
                                <div style="font-weight: 700; color: #0f172a; font-size: 0.875rem;">Free Installation</div>
                                <div style="font-size: 0.75rem; color: #64748b;">Expert setup</div>
                            </div>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.75rem; padding: 1rem; background: white; border-radius: 12px; border: 1px solid #e2e8f0; transition: all 0.3s ease;">
                            <div style="flex-shrink: 0; width: 40px; height: 40px; background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                <svg width="20" height="20" fill="white" viewBox="0 0 24 24"><path d="M11 17h2v-6h-2v6zm1-15C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zM11 9h2V7h-2v2z"/></svg>
                            </div>
                            <div>
                                <div style="font-weight: 700; color: #0f172a; font-size: 0.875rem;">Full Training</div>
                                <div style="font-size: 0.75rem; color: #64748b;">Hands-on course</div>
                            </div>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.75rem; padding: 1rem; background: white; border-radius: 12px; border: 1px solid #e2e8f0; transition: all 0.3s ease;">
                            <div style="flex-shrink: 0; width: 40px; height: 40px; background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                <svg width="20" height="20" fill="white" viewBox="0 0 24 24"><path d="M20 2H4c-1.1 0-1.99.9-1.99 2L2 22l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-2 12H6v-2h12v2zm0-3H6V9h12v2zm0-3H6V6h12v2z"/></svg>
                            </div>
                            <div>
                                <div style="font-weight: 700; color: #0f172a; font-size: 0.875rem;">24/7 Support</div>
                                <div style="font-size: 0.75rem; color: #64748b;">Always here</div>
                            </div>
                        </div>
                    </div>

                    <!-- CTAs -->
                    <div style="display: flex; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap;">
                        <?php if ($hero_product_id): ?>
                            <button class="btn btn-primary btn-lg" id="heroAddToCart"
                                data-product-id="<?php echo (int)$hero_product_id; ?>"
                                data-product-name="<?php echo htmlspecialchars($hero_product_name, ENT_QUOTES); ?>"
                                style="flex: 1; min-width: 200px; font-size: 1.125rem; padding: 1.25rem 2.5rem; display: flex; align-items: center; justify-content: center; gap: 0.75rem; box-shadow: 0 10px 25px rgba(255, 89, 0, 0.3); font-weight: 700; position: relative; overflow: hidden;">
                                <svg width="22" height="22" fill="currentColor" viewBox="0 0 24 24"><path d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49c.08-.14.12-.31.12-.48 0-.55-.45-1-1-1H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z"/></svg>
                                Add to Cart
                            </button>
                            <?php if (!empty($whatsapp_number_link)): ?>
                                <a href="https://wa.me/<?php echo htmlspecialchars($whatsapp_number_link); ?>?text=<?php echo urlencode('Hi, I\'m interested in ' . $hero_product_name . '. Can you provide more details?'); ?>" 
                                   class="btn btn-outline btn-lg" target="_blank"
                                   style="flex: 1; min-width: 200px; font-size: 1.125rem; padding: 1.25rem 2.5rem; display: flex; align-items: center; justify-content: center; gap: 0.75rem; background: linear-gradient(135deg, #25d366 0%, #128c7e 100%); color: white; border: none; font-weight: 700; box-shadow: 0 10px 25px rgba(37, 211, 102, 0.25);">
                                    <svg width="22" height="22" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
                                    WhatsApp Quote
                                </a>
                            <?php endif; ?>
                        <?php else: ?>
                            <a href="products" class="btn btn-primary btn-lg" style="font-size: 1.125rem; padding: 1.25rem 2.5rem; box-shadow: 0 10px 25px rgba(255, 89, 0, 0.3);">
                                Browse All Products
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <div style="display: flex; gap: 2rem; font-size: 0.875rem; flex-wrap: wrap;">
                        <?php if (!empty($hero_product_slug)): ?>
                            <a href="product-detail?slug=<?php echo urlencode($hero_product_slug); ?>" style="color: #ff5900; text-decoration: none; font-weight: 600; display: flex; align-items: center; gap: 0.5rem; transition: gap 0.3s;">
                                View Full Specifications 
                                <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8z"/></svg>
                            </a>
                        <?php endif; ?>
                        <a href="#contact" style="color: #ff5900; text-decoration: none; font-weight: 600; display: flex; align-items: center; gap: 0.5rem; transition: gap 0.3s;">
                            Request Custom Quote 
                            <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8z"/></svg>
                        </a>
                    </div>
                </div>
                
                <!-- Right Column: Product Image -->
                <div class="hero-image" style="position: relative; animation: fadeInRight 0.8s ease-out;">
                    <div style="position: relative; background: white; border-radius: 24px; padding: 2rem; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.12); transform: perspective(1000px) rotateY(-5deg); transition: transform 0.3s ease;">
                        <img src="<?php echo htmlspecialchars($hero_image); ?>" 
                             alt="<?php echo htmlspecialchars($hero_product_name); ?>"
                             style="width: 100%; height: auto; display: block; border-radius: 16px;">
                        <!-- Floating Badge -->
                        <div style="position: absolute; top: 2rem; right: 2rem; background: linear-gradient(135deg, #ff5900 0%, #e64f00 100%); color: white; padding: 1rem 1.5rem; border-radius: 16px; box-shadow: 0 10px 30px rgba(255, 89, 0, 0.4); text-align: center; animation: float 3s ease-in-out infinite;">
                            <div style="font-size: 1.5rem; font-weight: 800; line-height: 1;">⭐ 4.9</div>
                            <div style="font-size: 0.75rem; opacity: 0.9; margin-top: 0.25rem;">150+ Reviews</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        @keyframes fadeInLeft {
            from { opacity: 0; transform: translateX(-30px); }
            to { opacity: 1; transform: translateX(0); }
        }
        @keyframes fadeInRight {
            from { opacity: 0; transform: translateX(30px); }
            to { opacity: 1; transform: translateX(0); }
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        .hero-image > div:hover {
            transform: perspective(1000px) rotateY(0deg) scale(1.02);
        }
        </style>
    </section>

    <!-- Stats Section -->
    <section class="stats">
        <div class="container">
            <!-- Social Proof Banner -->
            <div class="social-proof-banner">
                <div class="proof-item">
                    <svg width="24" height="24" fill="#10b981" viewBox="0 0 24 24"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
                    <span><strong>Sarah K.</strong> from Nairobi just purchased</span>
                    <span class="time-ago">2 hours ago</span>
                </div>
                <div class="proof-item">
                    <svg width="24" height="24" fill="#10b981" viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                    <span><strong>4.9/5</strong> average rating from 150+ customers</span>
                </div>
                <div class="proof-item">
                    <svg width="24" height="24" fill="#10b981" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
                    <span><strong>10+ Years</strong> serving Kenya & Tanzania</span>
                </div>
            </div>
            
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
                <a href="products" class="view-all-link">View All Products →</a>
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
                    <div class="product-card" style="background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.06); transition: all 0.3s ease; border: 1px solid #e2e8f0; position: relative;">
                        <!-- Product Image -->
                        <div class="product-image" style="position: relative; overflow: hidden; height: 180px; background: #f8fafc;">
                            <a href="product-detail?slug=<?php echo urlencode($product['slug']); ?>" style="display: block; height: 100%;">
                                <?php 
                                    $cardImage = normalize_product_image_url($product['image'] ?? '');
                                ?>
                                <?php if (!empty($cardImage)): ?>
                                    <img src="<?php echo htmlspecialchars($cardImage); ?>" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>"
                                         style="width: 100%; height: 100%; object-fit: contain; padding: 1rem; transition: transform 0.3s ease;">
                                <?php else: ?>
                                    <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='400' height='300' viewBox='0 0 400 300'%3E%3Crect fill='%23f1f5f9' width='400' height='300'/%3E%3Ctext x='50%25' y='50%25' dominant-baseline='middle' text-anchor='middle' fill='%2394a3b8' font-family='Arial' font-size='18' font-weight='bold'%3ENo Image%3C/text%3E%3C/svg%3E" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>"
                                         style="width: 100%; height: 100%; object-fit: contain;">
                                <?php endif; ?>
                            </a>
                            
                            <!-- Stock Badge Only -->
                            <?php if ($stock_qty > 0 && $stock_qty <= 5): ?>
                            <div style="position: absolute; top: 0.75rem; right: 0.75rem;">
                                <span style="background: #ff5900; color: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.7rem; font-weight: 700;">
                                    <?php echo $stock_qty; ?> Left
                                </span>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Product Info -->
                        <div class="product-info" style="padding: 1rem;">
                            <h3 style="margin: 0 0 0.75rem 0; font-size: 1rem; font-weight: 600; line-height: 1.3;">
                                <a href="product-detail?slug=<?php echo urlencode($product['slug']); ?>" 
                                   style="color: #1e293b; text-decoration: none; transition: color 0.2s;">
                                    <?php echo htmlspecialchars($product['name']); ?>
                                </a>
                            </h3>
                            
                            <!-- Pricing -->
                            <div style="margin-bottom: 0.75rem;">
                                <?php
                                $currency = $currencyDetector->getCurrentCurrency();
                                $price = $currencyDetector->getPrice($product['price_kes']);
                                ?>
                                <div style="font-size: 1.25rem; font-weight: 700; color: #ff5900; margin-bottom: 0.25rem;">
                                    <?php echo $currencyDetector->formatPrice($price); ?>
                                </div>
                            </div>
                            
                            <!-- Actions -->
                            <div style="display: flex; gap: 0.5rem;">
                                <a href="product-detail?slug=<?php echo urlencode($product['slug']); ?>" 
                                   class="btn btn-primary" 
                                   style="flex: 1; text-align: center; padding: 0.625rem 1rem; font-size: 0.875rem; font-weight: 600; display: flex; align-items: center; justify-content: center; gap: 0.375rem;">
                                    View Details
                                </a>
                                <a href="https://wa.me/<?php echo str_replace('+', '', $whatsapp_number); ?>?text=<?php echo urlencode('Hi, I\'m interested in ' . $product['name']); ?>" 
                                   class="btn" target="_blank" title="WhatsApp"
                                   style="padding: 0.625rem 0.75rem; display: flex; align-items: center; justify-content: center; background: #25d366; color: white; border: none; border-radius: 8px;">
                                    <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24">
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
                    <?php if ($hero_product_id): ?>
                    <button class="btn btn-primary btn-pulse" id="specAddToCart"
                        data-product-id="<?php echo (int)$hero_product_id; ?>"
                        data-product-name="<?php echo htmlspecialchars($hero_product_name, ENT_QUOTES); ?>">
                        <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24"><path d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49c.08-.14.12-.31.12-.48 0-.55-.45-1-1-1H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z"/></svg>
                        Add to Cart - <?php echo $hero_formatted_price; ?>
                    </button>
                    <?php endif; ?>
                    <a href="https://wa.me/<?php echo htmlspecialchars($whatsapp_number_link); ?>?text=<?php echo urlencode('Hi, I need more information about the ' . $hero_product_name . ' specifications.'); ?>" 
                       class="btn btn-outline" target="_blank" style="background: #25d366; color: white; border-color: #25d366;">
                        <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
                        Ask Questions
                    </a>
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
                        <?php 
                        $tech_specs_image = site_setting('tech_specs_image', '');
                        $specs_image_src = !empty($tech_specs_image) ? $tech_specs_image : ($hero_image_source ?? 'images/plotter-hero.webp');
                        ?>
                        <img src="<?php echo htmlspecialchars($specs_image_src); ?>" alt="JINKA Cutting Plotter Specifications" id="specImage">
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

    <!-- Price Comparison Section -->
    <section id="price-comparison" style="padding: 5rem 0; background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);">
        <div class="container">
            <div class="section-header" style="text-align: center; margin-bottom: 3rem;">
                <span style="display: inline-block; background: linear-gradient(135deg, #ff5900 0%, #e64f00 100%); color: white; padding: 0.5rem 1.25rem; border-radius: 50px; font-size: 0.875rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 1rem;">
                    🏆 Industry-Leading Performance
                </span>
                <h2 class="section-title" style="font-size: 2.5rem; font-weight: 800; color: #1e293b; margin-bottom: 1rem;">
                    Why Choose Our Cutting Plotters?
                </h2>
                <p class="section-description" style="font-size: 1.125rem; color: #64748b; max-width: 800px; margin: 0 auto; line-height: 1.8;">
                    Trusted by <strong style="color: #ff5900;">500+ sign shops</strong> across East Africa. Our JINKA plotters deliver professional results with complete support, making them the smart choice for serious businesses.
                </p>
            </div>

            <!-- Key Benefits Grid -->
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; margin-bottom: 4rem; max-width: 1400px; margin-left: auto; margin-right: auto;">
                <div style="background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%); padding: 1.75rem; border-radius: 16px;">
                    <div style="font-size: 2rem; margin-bottom: 0.75rem;">🎯</div>
                    <h3 style="font-size: 1.125rem; font-weight: 700; color: #1e293b; margin-bottom: 0.5rem;">Precision Cutting</h3>
                    <p style="color: #64748b; font-size: 0.95rem; line-height: 1.6;">±0.1mm accuracy with ARM9 processor ensures clean cuts on vinyl, heat transfer, and reflective materials every time.</p>
                </div>
                <div style="background: #f0fdf4; padding: 1.75rem; border-radius: 16px;">
                    <div style="font-size: 2rem; margin-bottom: 0.75rem;">⚡</div>
                    <h3 style="font-size: 1.125rem; font-weight: 700; color: #1e293b; margin-bottom: 0.5rem;">Fast Production</h3>
                    <p style="color: #64748b; font-size: 0.95rem; line-height: 1.6;">Up to 1200mm/s cutting speed means you can handle rush orders and increase daily output significantly.</p>
                </div>
                <div style="background: #fff4ed; padding: 1.75rem; border-radius: 16px;">
                    <div style="font-size: 2rem; margin-bottom: 0.75rem;">💪</div>
                    <h3 style="font-size: 1.125rem; font-weight: 700; color: #1e293b; margin-bottom: 0.5rem;">Heavy-Duty Built</h3>
                    <p style="color: #64748b; font-size: 0.95rem; line-height: 1.6;">600g cutting force handles thick materials including sandblast stencils, reflective sheeting, and layered graphics.</p>
                </div>
                <div style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); padding: 1.75rem; border-radius: 16px;">
                    <div style="font-size: 2rem; margin-bottom: 0.75rem;">🛠️</div>
                    <h3 style="font-size: 1.125rem; font-weight: 700; color: #1e293b; margin-bottom: 0.5rem;">Full Setup & Training</h3>
                    <p style="color: #64748b; font-size: 0.95rem; line-height: 1.6;">Professional installation at your location plus 2-day hands-on training so your team is productive from day one.</p>
                </div>
                <div style="background: #fff4ed; padding: 1.75rem; border-radius: 16px;">
                    <div style="font-size: 2rem; margin-bottom: 0.75rem;">🔧</div>
                    <h3 style="font-size: 1.125rem; font-weight: 700; color: #1e293b; margin-bottom: 0.5rem;">Local Support Network</h3>
                    <p style="color: #64748b; font-size: 0.95rem; line-height: 1.6;">24/7 technical support with spare parts readily available across Kenya, Tanzania, and Uganda. No waiting weeks for international shipping.</p>
                </div>
                <div style="background: linear-gradient(135deg, #fce7f3 0%, #fbcfe8 100%); padding: 1.75rem; border-radius: 16px;">
                    <div style="font-size: 2rem; margin-bottom: 0.75rem;">✅</div>
                    <h3 style="font-size: 1.125rem; font-weight: 700; color: #1e293b; margin-bottom: 0.5rem;">12-Month Warranty</h3>
                    <p style="color: #64748b; font-size: 0.95rem; line-height: 1.6;">Comprehensive coverage with free repairs and replacement parts. Extended warranty options available for peace of mind.</p>
                </div>
            </div>

            <style>
                @media (max-width: 1024px) {
                    .container > div[style*="grid-template-columns: repeat(3, 1fr)"] {
                        grid-template-columns: repeat(2, 1fr) !important;
                    }
                }
                @media (max-width: 640px) {
                    .container > div[style*="grid-template-columns: repeat(3, 1fr)"] {
                        grid-template-columns: 1fr !important;
                    }
                }
            </style>

            <!-- Comparison Table Header -->
            <div style="text-align: center; margin-bottom: 2rem;">
                <h3 style="font-size: 1.75rem; font-weight: 700; color: #1e293b; margin-bottom: 0.75rem;">
                    📊 Side-by-Side Comparison
                </h3>
                <p style="color: #64748b; font-size: 1rem;">
                    See how we stack up against leading competitors - better specs, better support, better price
                </p>
            </div>

            <div style="background: white; border-radius: 20px; box-shadow: 0 20px 50px -20px rgba(0,0,0,0.15); overflow: hidden; max-width: 1100px; margin: 0 auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: linear-gradient(135deg, #1e293b 0%, #334155 100%); color: white;">
                            <th style="padding: 1.5rem; text-align: left; font-size: 1.1rem; font-weight: 700;">Feature</th>
                            <th style="padding: 1.5rem; text-align: center; font-size: 1.1rem; font-weight: 700; background: linear-gradient(135deg, #ff5900 0%, #e64f00 100%);">
                                <div style="display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                                    <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                                    <span>JINKA Plotter</span>
                                </div>
                                <div style="font-size: 0.8rem; font-weight: 500; opacity: 0.95; margin-top: 0.25rem;">Premium Quality + Full Support</div>
                            </th>
                            <th style="padding: 1.5rem; text-align: center; font-size: 1.1rem; font-weight: 700; opacity: 0.8;">Generic Brand A</th>
                            <th style="padding: 1.5rem; text-align: center; font-size: 1.1rem; font-weight: 700; opacity: 0.8;">Import Brand B</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr style="border-bottom: 1px solid #e5e7eb;">
                            <td style="padding: 1.25rem; font-weight: 600; color: #475569;">
                                <div>Cutting Width</div>
                                <div style="font-size: 0.8rem; color: #94a3b8; font-weight: 400;">Maximum material width</div>
                            </td>
                            <td style="padding: 1.25rem; text-align: center; background: #fff7ed; font-weight: 700; color: #ff5900; font-size: 1.1rem;">1350mm (53")</td>
                            <td style="padding: 1.25rem; text-align: center; color: #64748b;">1220mm (48")</td>
                            <td style="padding: 1.25rem; text-align: center; color: #64748b;">1200mm (47")</td>
                        </tr>
                        <tr style="border-bottom: 1px solid #e5e7eb;">
                            <td style="padding: 1.25rem; font-weight: 600; color: #475569;">
                                <div>Cutting Speed</div>
                                <div style="font-size: 0.8rem; color: #94a3b8; font-weight: 400;">Maximum production speed</div>
                            </td>
                            <td style="padding: 1.25rem; text-align: center; background: #fff7ed; font-weight: 700; color: #ff5900; font-size: 1.1rem;">10-800mm/s</td>
                            <td style="padding: 1.25rem; text-align: center; color: #64748b;">10-600mm/s</td>
                            <td style="padding: 1.25rem; text-align: center; color: #64748b;">10-700mm/s</td>
                        </tr>
                        <tr style="border-bottom: 1px solid #e5e7eb;">
                            <td style="padding: 1.25rem; font-weight: 600; color: #475569;">
                                <div>Cutting Force</div>
                                <div style="font-size: 0.8rem; color: #94a3b8; font-weight: 400;">Adjustable pressure range</div>
                            </td>
                            <td style="padding: 1.25rem; text-align: center; background: #fff7ed; font-weight: 700; color: #ff5900; font-size: 1.1rem;">10-500g</td>
                            <td style="padding: 1.25rem; text-align: center; color: #64748b;">10-350g</td>
                            <td style="padding: 1.25rem; text-align: center; color: #64748b;">10-400g</td>
                        </tr>
                        <tr style="border-bottom: 1px solid #e5e7eb;">
                            <td style="padding: 1.25rem; font-weight: 600; color: #475569;">
                                <div>Cutting Accuracy</div>
                                <div style="font-size: 0.8rem; color: #94a3b8; font-weight: 400;">Cutting precision</div>
                            </td>
                            <td style="padding: 1.25rem; text-align: center; background: #fff7ed; font-weight: 700; color: #ff5900; font-size: 1.1rem;">±0.1mm</td>
                            <td style="padding: 1.25rem; text-align: center; color: #64748b;">±0.2mm</td>
                            <td style="padding: 1.25rem; text-align: center; color: #64748b;">±0.15mm</td>
                        </tr>
                        <tr style="border-bottom: 1px solid #e5e7eb;">
                            <td style="padding: 1.25rem; font-weight: 600; color: #475569;">
                                <div>Cutting Length</div>
                                <div style="font-size: 0.8rem; color: #94a3b8; font-weight: 400;">Maximum continuous cut</div>
                            </td>
                            <td style="padding: 1.25rem; text-align: center; background: #fff7ed; font-weight: 700; color: #ff5900; font-size: 1.1rem;">2000mm (78")</td>
                            <td style="padding: 1.25rem; text-align: center; color: #64748b;">1500mm</td>
                            <td style="padding: 1.25rem; text-align: center; color: #64748b;">1800mm</td>
                        </tr>
                        <tr style="border-bottom: 1px solid #e5e7eb;">
                            <td style="padding: 1.25rem; font-weight: 600; color: #475569;">
                                <div>Memory/Buffer</div>
                                <div style="font-size: 0.8rem; color: #94a3b8; font-weight: 400;">Design storage capacity</div>
                            </td>
                            <td style="padding: 1.25rem; text-align: center; background: #fff7ed; font-weight: 700; color: #ff5900; font-size: 1.1rem;">1-4MB</td>
                            <td style="padding: 1.25rem; text-align: center; color: #64748b;">512KB</td>
                            <td style="padding: 1.25rem; text-align: center; color: #64748b;">1MB</td>
                        </tr>
                        <tr style="border-bottom: 1px solid #e5e7eb;">
                            <td style="padding: 1.25rem; font-weight: 600; color: #475569;">
                                <div>Connectivity</div>
                                <div style="font-size: 0.8rem; color: #94a3b8; font-weight: 400;">Interface options</div>
                            </td>
                            <td style="padding: 1.25rem; text-align: center; background: #fff7ed;">
                                <div style="font-weight: 700; color: #ff5900; font-size: 1rem; margin-bottom: 0.25rem;">USB + Serial</div>
                                <div style="font-size: 0.8rem; color: #10b981; font-weight: 600;">Dual Options</div>
                            </td>
                            <td style="padding: 1.25rem; text-align: center; color: #64748b; font-size: 0.9rem;">USB Only</td>
                            <td style="padding: 1.25rem; text-align: center; color: #64748b; font-size: 0.9rem;">USB Only</td>
                        </tr>
                        <tr style="border-bottom: 1px solid #e5e7eb;">
                            <td style="padding: 1.25rem; font-weight: 600; color: #475569;">
                                <div>Repeat Cutting</div>
                                <div style="font-size: 0.8rem; color: #94a3b8; font-weight: 400;">Batch production feature</div>
                            </td>
                            <td style="padding: 1.25rem; text-align: center; background: #fff7ed;">
                                <div style="color: #10b981; font-size: 1.75rem; margin-bottom: 0.25rem;">✓</div>
                                <div style="font-size: 0.8rem; color: #10b981; font-weight: 600;">Memory Function</div>
                            </td>
                            <td style="padding: 1.25rem; text-align: center; color: #ef4444; font-size: 1.75rem;">✗</td>
                            <td style="padding: 1.25rem; text-align: center;">
                                <div style="color: #f59e0b; font-size: 0.9rem; font-weight: 600;">Manual Only</div>
                            </td>
                        </tr>
                        <tr style="border-bottom: 1px solid #e5e7eb;">
                            <td style="padding: 1.25rem; font-weight: 600; color: #475569;">
                                <div>Free Installation</div>
                                <div style="font-size: 0.8rem; color: #94a3b8; font-weight: 400;">Professional setup included</div>
                            </td>
                            <td style="padding: 1.25rem; text-align: center; background: #fff7ed; color: #10b981; font-size: 1.75rem;">✓</td>
                            <td style="padding: 1.25rem; text-align: center; color: #ef4444; font-size: 1.75rem;">✗</td>
                            <td style="padding: 1.25rem; text-align: center; color: #ef4444; font-size: 1.75rem;">✗</td>
                        </tr>
                        <tr style="border-bottom: 1px solid #e5e7eb;">
                            <td style="padding: 1.25rem; font-weight: 600; color: #475569;">
                                <div>Operator Training</div>
                                <div style="font-size: 0.8rem; color: #94a3b8; font-weight: 400;">On-site hands-on training</div>
                            </td>
                            <td style="padding: 1.25rem; text-align: center; background: #fff7ed;">
                                <div style="color: #10b981; font-size: 1.75rem; margin-bottom: 0.25rem;">✓</div>
                                <div style="font-size: 0.8rem; color: #10b981; font-weight: 600;">2 Full Days</div>
                            </td>
                            <td style="padding: 1.25rem; text-align: center; color: #ef4444; font-size: 1.75rem;">✗</td>
                            <td style="padding: 1.25rem; text-align: center;">
                                <div style="color: #f59e0b; font-size: 0.9rem; font-weight: 600;">Video Only</div>
                            </td>
                        </tr>
                        <tr style="border-bottom: 1px solid #e5e7eb;">
                            <td style="padding: 1.25rem; font-weight: 600; color: #475569;">
                                <div>Warranty Coverage</div>
                                <div style="font-size: 0.8rem; color: #94a3b8; font-weight: 400;">Parts & labor included</div>
                            </td>
                            <td style="padding: 1.25rem; text-align: center; background: #fff7ed; font-weight: 700; color: #ff5900; font-size: 1.1rem;">12 Months</td>
                            <td style="padding: 1.25rem; text-align: center; color: #64748b;">6 Months</td>
                            <td style="padding: 1.25rem; text-align: center; color: #64748b;">3 Months</td>
                        </tr>
                        <tr style="border-bottom: 1px solid #e5e7eb;">
                            <td style="padding: 1.25rem; font-weight: 600; color: #475569;">
                                <div>Technical Support</div>
                                <div style="font-size: 0.8rem; color: #94a3b8; font-weight: 400;">Access to expert help</div>
                            </td>
                            <td style="padding: 1.25rem; text-align: center; background: #fff7ed;">
                                <div style="font-weight: 700; color: #ff5900; font-size: 1.1rem; margin-bottom: 0.25rem;">24/7</div>
                                <div style="font-size: 0.8rem; color: #10b981; font-weight: 600;">Phone + WhatsApp</div>
                            </td>
                            <td style="padding: 1.25rem; text-align: center; color: #64748b; font-size: 0.9rem;">Business Hours</td>
                            <td style="padding: 1.25rem; text-align: center; color: #64748b; font-size: 0.9rem;">Email Only</td>
                        </tr>
                        <tr style="border-bottom: 1px solid #e5e7eb;">
                            <td style="padding: 1.25rem; font-weight: 600; color: #475569;">
                                <div>Local Parts Stock</div>
                                <div style="font-size: 0.8rem; color: #94a3b8; font-weight: 400;">Spare parts availability</div>
                            </td>
                            <td style="padding: 1.25rem; text-align: center; background: #fff7ed;">
                                <div style="color: #10b981; font-size: 1.75rem; margin-bottom: 0.25rem;">✓</div>
                                <div style="font-size: 0.8rem; color: #10b981; font-weight: 600;">KE, TZ, UG</div>
                            </td>
                            <td style="padding: 1.25rem; text-align: center; color: #64748b; font-size: 0.9rem;">Limited</td>
                            <td style="padding: 1.25rem; text-align: center; color: #ef4444; font-size: 0.9rem;">Import Wait</td>
                        </tr>
                        <tr style="background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);">
                            <td style="padding: 1.5rem; font-weight: 700; color: #1e293b; font-size: 1.125rem;">
                                <div>Starting Price</div>
                                <div style="font-size: 0.8rem; color: #64748b; font-weight: 400; margin-top: 0.25rem;">Complete package value</div>
                            </td>
                            <td style="padding: 1.5rem; text-align: center; background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%);">
                                <div style="font-weight: 800; color: #ff5900; font-size: 1.75rem; margin-bottom: 0.5rem;"><?php echo $hero_formatted_price; ?></div>
                                <div style="font-size: 0.85rem; font-weight: 700; color: #10b981; background: #dcfce7; padding: 0.25rem 0.75rem; border-radius: 6px; display: inline-block;">✓ Best Total Value</div>
                            </td>
                            <td style="padding: 1.5rem; text-align: center;">
                                <div style="color: #64748b; font-size: 1.5rem; font-weight: 700; margin-bottom: 0.25rem; text-decoration: line-through; opacity: 0.6;">
                                    <?php 
                                    if ($hero_currency === 'KES') {
                                        $competitor_price = $hero_price_kes_value + 80000;
                                        echo 'KES ' . number_format($competitor_price, 0);
                                    } elseif ($hero_currency === 'TZS') {
                                        $competitor_price = $hero_price_tzs_value + 512000; // 80000 KES * 6.4
                                        echo 'TZS ' . number_format($competitor_price, 0);
                                    } elseif ($hero_currency === 'UGX') {
                                        $competitor_price = $hero_price_ugx_value + 760000; // 80000 KES * 9.5
                                        echo 'UGX ' . number_format($competitor_price, 0);
                                    } else {
                                        echo '$' . number_format(($hero_price_usd_value + 620), 0);
                                    }
                                    ?>
                                </div>
                                <div style="font-size: 0.8rem; color: #ef4444;">+ Installation Extra</div>
                            </td>
                            <td style="padding: 1.5rem; text-align: center;">
                                <div style="color: #64748b; font-size: 1.5rem; font-weight: 700; margin-bottom: 0.25rem; text-decoration: line-through; opacity: 0.6;">
                                    <?php 
                                    if ($hero_currency === 'KES') {
                                        $competitor2_price = $hero_price_kes_value + 120000;
                                        echo 'KES ' . number_format($competitor2_price, 0);
                                    } elseif ($hero_currency === 'TZS') {
                                        $competitor2_price = $hero_price_tzs_value + 768000; // 120000 KES * 6.4
                                        echo 'TZS ' . number_format($competitor2_price, 0);
                                    } elseif ($hero_currency === 'UGX') {
                                        $competitor2_price = $hero_price_ugx_value + 1140000; // 120000 KES * 9.5
                                        echo 'UGX ' . number_format($competitor2_price, 0);
                                    } else {
                                        echo '$' . number_format(($hero_price_usd_value + 930), 0);
                                    }
                                    ?>
                                </div>
                                <div style="font-size: 0.8rem; color: #ef4444;">+ Setup Extra</div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div style="text-align: center; margin-top: 2.5rem;">
                <div style="background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%); border: 2px solid #10b981; padding: 1.75rem; border-radius: 16px; max-width: 800px; margin: 0 auto 1.5rem; box-shadow: 0 10px 30px -10px rgba(16, 185, 129, 0.3);">
                    <div style="display: flex; align-items: center; justify-content: center; gap: 1.5rem; flex-wrap: wrap; margin-bottom: 1rem;">
                        <div style="text-align: center;">
                            <p style="font-size: 0.9rem; color: #15803d; margin: 0 0 0.25rem 0; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">💰 Total Savings</p>
                            <p style="font-size: 2rem; color: #ff5900; margin: 0; font-weight: 800; line-height: 1;">
                                <?php 
                                $savings_low = 80000;  // ~$620 saved vs competitor 1
                                $savings_high = 120000; // ~$930 saved vs competitor 2
                                if ($hero_currency === 'KES') {
                                    echo 'KES ' . number_format($savings_low, 0) . ' - ' . number_format($savings_high, 0);
                                } elseif ($hero_currency === 'TZS') {
                                    echo 'TZS ' . number_format($savings_low * 6.4, 0) . ' - ' . number_format($savings_high * 6.4, 0);
                                } elseif ($hero_currency === 'UGX') {
                                    echo 'UGX ' . number_format($savings_low * 9.5, 0) . ' - ' . number_format($savings_high * 9.5, 0);
                                } else {
                                    echo '$620 - $930';
                                }
                                ?>
                            </p>
                        </div>
                        <div style="font-size: 2rem; color: #10b981; font-weight: 300;">|</div>
                        <div style="text-align: center;">
                            <p style="font-size: 0.9rem; color: #15803d; margin: 0 0 0.25rem 0; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">🎁 Free Value Included</p>
                            <p style="font-size: 1.5rem; color: #166534; margin: 0; font-weight: 800; line-height: 1;">
                                <?php 
                                $free_value = 65000; // ~$500+ for installation & training
                                if ($hero_currency === 'KES') {
                                    echo 'KES ' . number_format($free_value, 0) . '+';
                                } elseif ($hero_currency === 'TZS') {
                                    echo 'TZS ' . number_format($free_value * 6.4, 0) . '+';
                                } elseif ($hero_currency === 'UGX') {
                                    echo 'UGX ' . number_format($free_value * 9.5, 0) . '+';
                                } else {
                                    echo '$500+';
                                }
                                ?>
                            </p>
                        </div>
                    </div>
                    <p style="font-size: 1rem; color: #15803d; margin: 0; font-weight: 600; line-height: 1.5;">
                        Equipment costs <span style="color: #ff5900; font-weight: 700;">less</span> + Professional installation + 2-day training + 12-month warranty + 24/7 support
                    </p>
                    <div style="display: flex; gap: 0.75rem; justify-content: center; flex-wrap: wrap; margin-top: 1rem;">
                        <div style="background: #fff; border: 2px solid #10b981; padding: 0.5rem 1rem; border-radius: 8px;">
                            <span style="color: #15803d; font-weight: 700; font-size: 0.9rem;">✓ Lower Initial Cost</span>
                        </div>
                        <div style="background: #fff; border: 2px solid #10b981; padding: 0.5rem 1rem; border-radius: 8px;">
                            <span style="color: #15803d; font-weight: 700; font-size: 0.9rem;">✓ No Hidden Fees</span>
                        </div>
                        <div style="background: #fff; border: 2px solid #10b981; padding: 0.5rem 1rem; border-radius: 8px;">
                            <span style="color: #15803d; font-weight: 700; font-size: 0.9rem;">✓ Complete Package</span>
                        </div>
                    </div>
                </div>
                <a href="#contact" class="btn-primary" style="display: inline-flex; align-items: center; gap: 0.75rem; background: linear-gradient(135deg, #ff5900 0%, #e64f00 100%); color: white; padding: 1.25rem 2.5rem; border-radius: 12px; font-weight: 700; text-decoration: none; font-size: 1.125rem; transition: transform 0.2s, box-shadow 0.2s; box-shadow: 0 10px 30px -10px rgba(255, 89, 0, 0.4);">
                    <svg width="22" height="22" fill="currentColor" viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
                    Get Your Custom Quote Now
                    <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24"><path d="M13.025 1l-2.847 2.828 6.176 6.176h-16.354v3.992h16.354l-6.176 6.176 2.847 2.828 10.975-11z"/></svg>
                </a>
            </div>
        </div>
    </section>

    <!-- ROI Calculator Section -->
    <section id="roi-calculator" style="padding: 5rem 0; background: linear-gradient(135deg, #1e293b 0%, #334155 100%); color: white; position: relative; overflow: hidden;">
        <!-- Background Pattern -->
        <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; opacity: 0.05; background-image: radial-gradient(circle at 2px 2px, white 1px, transparent 0); background-size: 40px 40px;"></div>
        
        <div class="container" style="position: relative; z-index: 1;">
            <div class="section-header" style="text-align: center; margin-bottom: 3.5rem;">
                <span style="display: inline-block; background: linear-gradient(135deg, #ff5900 0%, #e64f00 100%); color: white; padding: 0.5rem 1.25rem; border-radius: 50px; font-size: 0.875rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 1rem; box-shadow: 0 10px 30px -10px rgba(255, 89, 0, 0.5);">
                    📊 Calculate Your Returns
                </span>
                <h2 class="section-title" style="font-size: 2.75rem; font-weight: 800; margin-bottom: 1rem; background: linear-gradient(135deg, #fff 0%, #cbd5e1 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
                    ROI Calculator: See Your Profit Potential
                </h2>
                <p class="section-description" style="font-size: 1.2rem; opacity: 0.9; max-width: 750px; margin: 0 auto; line-height: 1.6;">
                    Discover how quickly your JINKA plotter investment turns into pure profit. Real numbers, real results.
                </p>
            </div>

            <div style="max-width: 1000px; margin: 0 auto;">
                <!-- Quick Stats Overview -->
                <?php
                // Region-specific ROI stats based on currency
                $roi_stats = [
                    'KES' => ['payback' => '1-2 Months', 'annual' => '2.5M+', 'roi' => '800%+', 'jobs' => '20-25'],
                    'TZS' => ['payback' => '2-3 Months', 'annual' => '16M+', 'roi' => '750%+', 'jobs' => '25-30'],
                    'UGX' => ['payback' => '2-3 Months', 'annual' => '24M+', 'roi' => '750%+', 'jobs' => '25-30'],
                    'USD' => ['payback' => '1-2 Months', 'annual' => '$25K+', 'roi' => '900%+', 'jobs' => '20-30']
                ];
                $current_stats = $roi_stats[$hero_currency] ?? $roi_stats['USD'];
                ?>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.5rem; margin-bottom: 2.5rem;">
                    <div style="background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(10px); border-radius: 16px; padding: 1.5rem; text-align: center; border: 1px solid rgba(255, 255, 255, 0.1);">
                        <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">⚡</div>
                        <div style="font-size: 1.75rem; font-weight: 800; color: #10b981; margin-bottom: 0.25rem;"><?php echo $current_stats['payback']; ?></div>
                        <div style="font-size: 0.9rem; opacity: 0.8;">Average Payback Period</div>
                    </div>
                    <div style="background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(10px); border-radius: 16px; padding: 1.5rem; text-align: center; border: 1px solid rgba(255, 255, 255, 0.1);">
                        <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">💰</div>
                        <div style="font-size: 1.75rem; font-weight: 800; color: #10b981; margin-bottom: 0.25rem;"><?php echo $current_stats['annual']; ?></div>
                        <div style="font-size: 0.9rem; opacity: 0.8;">Annual Profit Potential</div>
                    </div>
                    <div style="background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(10px); border-radius: 16px; padding: 1.5rem; text-align: center; border: 1px solid rgba(255, 255, 255, 0.1);">
                        <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">📈</div>
                        <div style="font-size: 1.75rem; font-weight: 800; color: #10b981; margin-bottom: 0.25rem;"><?php echo $current_stats['roi']; ?></div>
                        <div style="font-size: 0.9rem; opacity: 0.8;">3-Year ROI</div>
                    </div>
                    <div style="background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(10px); border-radius: 16px; padding: 1.5rem; text-align: center; border: 1px solid rgba(255, 255, 255, 0.1);">
                        <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">🎯</div>
                        <div style="font-size: 1.75rem; font-weight: 800; color: #10b981; margin-bottom: 0.25rem;"><?php echo $current_stats['jobs']; ?></div>
                        <div style="font-size: 0.9rem; opacity: 0.8;">Jobs to Break Even</div>
                    </div>
                </div>

                <!-- Interactive Calculator -->
                <div style="background: white; border-radius: 20px; padding: 2.5rem; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.4); border: 1px solid rgba(255, 255, 255, 0.1);">
                    <h3 style="color: #1e293b; font-size: 1.5rem; font-weight: 800; margin-bottom: 0.5rem; text-align: center;">Customize Your Scenario</h3>
                    <p style="color: #64748b; text-align: center; margin-bottom: 2rem; font-size: 0.95rem;">Adjust the inputs to match your business model</p>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 2rem; margin-bottom: 2.5rem;">
                        <!-- Input: Jobs per Month -->
                        <div>
                            <label style="display: flex; align-items: center; gap: 0.5rem; color: #475569; font-weight: 600; margin-bottom: 0.75rem; font-size: 1rem;">
                                <span style="display: inline-flex; align-items: center; justify-content: center; width: 24px; height: 24px; background: #ff5900; color: white; border-radius: 50%; font-size: 0.75rem; font-weight: 700;">1</span>
                                Jobs per Month
                            </label>
                            <div style="position: relative;">
                                <input type="number" id="jobsPerMonth" value="20" min="1" max="200" style="width: 100%; padding: 1rem 1rem 1rem 3rem; border: 2px solid #e5e7eb; border-radius: 10px; font-size: 1.25rem; font-weight: 700; color: #1e293b; transition: border-color 0.3s;" oninput="calculateROI()" onfocus="this.style.borderColor='#ff5900'" onblur="this.style.borderColor='#e5e7eb'">
                                <span style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); font-size: 1.25rem; color: #ff5900;">📋</span>
                            </div>
                            <div style="display: flex; align-items: start; gap: 0.5rem; margin-top: 0.75rem;">
                                <span style="color: #10b981; font-size: 1.1rem; line-height: 1;">💡</span>
                                <p style="font-size: 0.875rem; color: #64748b; margin: 0; line-height: 1.4;">Average cutting projects completed monthly</p>
                            </div>
                        </div>

                        <!-- Input: Average Job Value -->
                        <div>
                            <label style="display: flex; align-items: center; gap: 0.5rem; color: #475569; font-weight: 600; margin-bottom: 0.75rem; font-size: 1rem;">
                                <span style="display: inline-flex; align-items: center; justify-content: center; width: 24px; height: 24px; background: #ff5900; color: white; border-radius: 50%; font-size: 0.75rem; font-weight: 700;">2</span>
                                Average Job Value (<span id="currencyLabel"><?php echo $hero_currency; ?></span>)
                            </label>
                            <div style="position: relative;">
                                <input type="number" id="avgJobValue" value="<?php echo ($hero_currency === 'KES' ? '15000' : ($hero_currency === 'TZS' ? '350000' : ($hero_currency === 'UGX' ? '550000' : '150'))); ?>" min="10" style="width: 100%; padding: 1rem 1rem 1rem 3rem; border: 2px solid #e5e7eb; border-radius: 10px; font-size: 1.25rem; font-weight: 700; color: #1e293b; transition: border-color 0.3s;" oninput="calculateROI()" onfocus="this.style.borderColor='#ff5900'" onblur="this.style.borderColor='#e5e7eb'">
                                <span style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); font-size: 1.25rem; color: #ff5900;">💵</span>
                            </div>
                            <div style="display: flex; align-items: start; gap: 0.5rem; margin-top: 0.75rem;">
                                <span style="color: #10b981; font-size: 1.1rem; line-height: 1;">💡</span>
                                <p style="font-size: 0.875rem; color: #64748b; margin: 0; line-height: 1.4;">Revenue per cutting project (signs, decals, stickers)</p>
                            </div>
                        </div>

                        <!-- Input: Material Cost % -->
                        <div>
                            <label style="display: flex; align-items: center; gap: 0.5rem; color: #475569; font-weight: 600; margin-bottom: 0.75rem; font-size: 1rem;">
                                <span style="display: inline-flex; align-items: center; justify-content: center; width: 24px; height: 24px; background: #ff5900; color: white; border-radius: 50%; font-size: 0.75rem; font-weight: 700;">3</span>
                                Material Cost (%)
                            </label>
                            <div style="position: relative;">
                                <input type="number" id="materialCost" value="30" min="0" max="90" style="width: 100%; padding: 1rem 1rem 1rem 3rem; border: 2px solid #e5e7eb; border-radius: 10px; font-size: 1.25rem; font-weight: 700; color: #1e293b; transition: border-color 0.3s;" oninput="calculateROI()" onfocus="this.style.borderColor='#ff5900'" onblur="this.style.borderColor='#e5e7eb'">
                                <span style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); font-size: 1.25rem; color: #ff5900;">📊</span>
                            </div>
                            <div style="display: flex; align-items: start; gap: 0.5rem; margin-top: 0.75rem;">
                                <span style="color: #10b981; font-size: 1.1rem; line-height: 1;">💡</span>
                                <p style="font-size: 0.875rem; color: #64748b; margin: 0; line-height: 1.4;">Percentage of revenue spent on vinyl & materials</p>
                            </div>
                        </div>
                    </div>

                    <!-- Results Dashboard -->
                    <div style="background: linear-gradient(135deg, #ff5900 0%, #e64f00 100%); border-radius: 16px; padding: 2.5rem; margin-bottom: 1.5rem; box-shadow: 0 20px 40px -10px rgba(255, 89, 0, 0.4); position: relative; overflow: hidden;">
                        <div style="position: absolute; top: -50%; right: -10%; width: 400px; height: 400px; background: rgba(255, 255, 255, 0.05); border-radius: 50%; filter: blur(60px);"></div>
                        
                        <h4 style="text-align: center; font-size: 1.25rem; font-weight: 700; margin-bottom: 2rem; opacity: 0.9; text-transform: uppercase; letter-spacing: 0.1em;">Your Profit Forecast</h4>
                        
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 2rem; text-align: center; position: relative;">
                            <div style="background: rgba(255, 255, 255, 0.15); backdrop-filter: blur(10px); border-radius: 12px; padding: 1.5rem; border: 1px solid rgba(255, 255, 255, 0.2);">
                                <div style="font-size: 0.875rem; text-transform: uppercase; letter-spacing: 0.05em; opacity: 0.9; margin-bottom: 0.75rem; font-weight: 600;">💰 Monthly Profit</div>
                                <div id="monthlyProfit" style="font-size: 2.5rem; font-weight: 800; text-shadow: 0 2px 10px rgba(0,0,0,0.2);">$2,100</div>
                                <div style="font-size: 0.8rem; opacity: 0.8; margin-top: 0.5rem;">After material costs</div>
                            </div>
                            <div style="background: rgba(255, 255, 255, 0.15); backdrop-filter: blur(10px); border-radius: 12px; padding: 1.5rem; border: 1px solid rgba(255, 255, 255, 0.2);">
                                <div style="font-size: 0.875rem; text-transform: uppercase; letter-spacing: 0.05em; opacity: 0.9; margin-bottom: 0.75rem; font-weight: 600;">📈 Yearly Revenue</div>
                                <div id="yearlyRevenue" style="font-size: 2.5rem; font-weight: 800; text-shadow: 0 2px 10px rgba(0,0,0,0.2);">$36,000</div>
                                <div style="font-size: 0.8rem; opacity: 0.8; margin-top: 0.5rem;">Total gross income</div>
                            </div>
                            <div style="background: rgba(255, 255, 255, 0.15); backdrop-filter: blur(10px); border-radius: 12px; padding: 1.5rem; border: 1px solid rgba(255, 255, 255, 0.2);">
                                <div style="font-size: 0.875rem; text-transform: uppercase; letter-spacing: 0.05em; opacity: 0.9; margin-bottom: 0.75rem; font-weight: 600;">⚡ Payback Period</div>
                                <div id="paybackPeriod" style="font-size: 2.5rem; font-weight: 800; text-shadow: 0 2px 10px rgba(0,0,0,0.2);">1.3 months</div>
                                <div style="font-size: 0.8rem; opacity: 0.8; margin-top: 0.5rem;">Investment recovery</div>
                            </div>
                        </div>
                    </div>

                    <!-- Investment Breakdown -->
                    <div style="background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); border-radius: 12px; padding: 2rem; border: 2px solid #e5e7eb; margin-bottom: 1.5rem;">
                        <div style="display: flex; align-items: start; gap: 1.25rem;">
                            <div style="font-size: 2.5rem; flex-shrink: 0;">💡</div>
                            <div style="flex: 1;">
                                <h4 style="color: #1e293b; margin: 0 0 0.75rem 0; font-size: 1.25rem; font-weight: 800;">Investment Breakdown</h4>
                                <p style="color: #475569; margin: 0 0 1rem 0; line-height: 1.7; font-size: 1rem;">
                                    With just <strong id="breakEvenJobs" style="color: #ff5900; font-size: 1.1rem;">27 jobs</strong>, this plotter pays for itself. 
                                    After that, every single job contributes directly to your bottom line!
                                </p>
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                                    <div style="background: white; padding: 1rem; border-radius: 8px; border-left: 4px solid #10b981;">
                                        <div style="font-size: 0.875rem; color: #64748b; margin-bottom: 0.25rem; font-weight: 600;">Annual Net Profit</div>
                                        <div id="yearlyProfit" style="font-size: 1.75rem; color: #10b981; font-weight: 800;">$25,200</div>
                                    </div>
                                    <div style="background: white; padding: 1rem; border-radius: 8px; border-left: 4px solid #ff5900;">
                                        <div style="font-size: 0.875rem; color: #64748b; margin-bottom: 0.25rem; font-weight: 600;">3-Year Profit</div>
                                        <div id="threeYearProfit" style="font-size: 1.75rem; color: #ff5900; font-weight: 800;">$75,600</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Common Use Cases -->
                    <?php
                    // Region-specific examples based on currency
                    $use_cases = [
                        'KES' => [
                            ['type' => 'Sign Shop', 'jobs' => 40, 'value' => '20,000', 'profit' => '560,000'],
                            ['type' => 'Print Shop', 'jobs' => 30, 'value' => '15,000', 'profit' => '315,000'],
                            ['type' => 'Custom Decals', 'jobs' => 50, 'value' => '10,000', 'profit' => '350,000']
                        ],
                        'TZS' => [
                            ['type' => 'Sign Shop', 'jobs' => 35, 'value' => '450,000', 'profit' => '11M'],
                            ['type' => 'Print Shop', 'jobs' => 25, 'value' => '350,000', 'profit' => '6.1M'],
                            ['type' => 'Custom Decals', 'jobs' => 45, 'value' => '250,000', 'profit' => '7.9M']
                        ],
                        'UGX' => [
                            ['type' => 'Sign Shop', 'jobs' => 35, 'value' => '700,000', 'profit' => '17M'],
                            ['type' => 'Print Shop', 'jobs' => 25, 'value' => '550,000', 'profit' => '9.6M'],
                            ['type' => 'Custom Decals', 'jobs' => 45, 'value' => '400,000', 'profit' => '12.6M']
                        ],
                        'USD' => [
                            ['type' => 'Sign Shop', 'jobs' => 40, 'value' => '200', 'profit' => '5,600'],
                            ['type' => 'Print Shop', 'jobs' => 30, 'value' => '150', 'profit' => '3,150'],
                            ['type' => 'Custom Decals', 'jobs' => 50, 'value' => '100', 'profit' => '3,500']
                        ]
                    ];
                    $examples = $use_cases[$hero_currency] ?? $use_cases['USD'];
                    $currency_symbol = ($hero_currency === 'USD') ? '$' : '';
                    $currency_suffix = ($hero_currency !== 'USD') ? ' ' . $hero_currency : '';
                    ?>
                    <div style="background: #fffbeb; border-radius: 12px; padding: 1.5rem; border: 2px solid #fbbf24;">
                        <div style="display: flex; align-items: start; gap: 1rem;">
                            <div style="font-size: 2rem; flex-shrink: 0;">📌</div>
                            <div>
                                <h5 style="color: #78350f; margin: 0 0 0.75rem 0; font-size: 1.1rem; font-weight: 700;">Real-World Examples (<?php echo $hero_currency; ?>)</h5>
                                <div style="display: grid; gap: 0.75rem;">
                                    <?php foreach ($examples as $example): ?>
                                    <div style="display: flex; gap: 0.75rem; color: #92400e; font-size: 0.9rem;">
                                        <span>✓</span>
                                        <span><strong><?php echo $example['type']; ?>:</strong> <?php echo $example['jobs']; ?> jobs/month × <?php echo $currency_symbol . $example['value'] . $currency_suffix; ?>/job = <?php echo $currency_symbol . $example['profit'] . $currency_suffix; ?> monthly profit</span>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div style="text-align: center; margin-top: 2.5rem;">
                    <a href="#contact" class="btn-primary" style="display: inline-flex; align-items: center; gap: 0.75rem; background: white; color: #ff5900; padding: 1.25rem 2.5rem; border-radius: 12px; font-weight: 700; text-decoration: none; font-size: 1.25rem; transition: all 0.3s; box-shadow: 0 10px 30px -10px rgba(255, 255, 255, 0.4);">
                        <svg width="22" height="22" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
                        Start Earning Today - Get Your Quote
                        <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24"><path d="M13.025 1l-2.847 2.828 6.176 6.176h-16.354v3.992h16.354l-6.176 6.176 2.847 2.828 10.975-11z"/></svg>
                    </a>
                    <p style="margin-top: 1rem; font-size: 0.9rem; opacity: 0.7;">⏱️ Free installation & 2-day training included • 📞 24/7 expert support</p>
                </div>
            </div>
        </div>
    </section>

    <script>
    // Currency configuration from PHP
    const roiConfig = {
        currency: '<?php echo $hero_currency; ?>',
        plotterCostUSD: <?php echo round($hero_price); ?>,
        plotterCost: <?php echo round($hero_price); ?>,
        currencySymbol: '<?php echo ($hero_currency === "USD" ? "$" : ""); ?>',
        currencySuffix: '<?php echo ($hero_currency !== "USD" ? " " . $hero_currency : ""); ?>'
    };
    
    function calculateROI() {
        const jobs = parseFloat(document.getElementById('jobsPerMonth').value) || 20;
        const avgValue = parseFloat(document.getElementById('avgJobValue').value) || (roiConfig.currency === 'KES' ? 15000 : (roiConfig.currency === 'TZS' ? 350000 : (roiConfig.currency === 'UGX' ? 550000 : 150)));
        const materialPercent = parseFloat(document.getElementById('materialCost').value) || 30;
        const plotterCost = roiConfig.plotterCost;
        
        // Calculate monthly revenue and profit
        const monthlyRevenue = jobs * avgValue;
        const materialCostAmount = monthlyRevenue * (materialPercent / 100);
        const monthlyProfit = monthlyRevenue - materialCostAmount;
        
        // Calculate yearly
        const yearlyRev = monthlyRevenue * 12;
        const yearlyProf = monthlyProfit * 12;
        const threeYearProf = yearlyProf * 3;
        
        // Calculate payback period
        const paybackMonths = plotterCost / monthlyProfit;
        const breakEven = Math.ceil(plotterCost / (avgValue * (1 - materialPercent / 100)));
        
        // Format numbers based on currency
        const formatCurrency = (amount) => {
            const formatted = Math.round(amount).toLocaleString('en-US', {maximumFractionDigits: 0});
            return roiConfig.currencySymbol + formatted + roiConfig.currencySuffix;
        };
        
        // Update display
        document.getElementById('monthlyProfit').textContent = formatCurrency(monthlyProfit);
        document.getElementById('yearlyRevenue').textContent = formatCurrency(yearlyRev);
        document.getElementById('paybackPeriod').textContent = paybackMonths.toFixed(1) + ' months';
        document.getElementById('breakEvenJobs').textContent = breakEven + ' jobs';
        document.getElementById('yearlyProfit').textContent = formatCurrency(yearlyProf);
        document.getElementById('threeYearProfit').textContent = formatCurrency(threeYearProf);
    }
    
    // Initialize calculator
    document.addEventListener('DOMContentLoaded', function() {
        calculateROI();
    });
    </script>

    <!-- CTA Section -->
    <section class="cta" style="position: relative; overflow: hidden;">
        <!-- Animated Background Elements -->
        <div style="position: absolute; top: -50%; left: -10%; width: 500px; height: 500px; background: radial-gradient(circle, rgba(255, 89, 0, 0.15) 0%, transparent 70%); border-radius: 50%; filter: blur(80px); animation: float 20s infinite ease-in-out;"></div>
        <div style="position: absolute; bottom: -30%; right: -5%; width: 400px; height: 400px; background: radial-gradient(circle, rgba(255, 193, 7, 0.12) 0%, transparent 70%); border-radius: 50%; filter: blur(60px); animation: float 15s infinite ease-in-out reverse;"></div>
        
        <style>
        @keyframes float {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33% { transform: translate(30px, -30px) scale(1.1); }
            66% { transform: translate(-20px, 20px) scale(0.9); }
        }
        @keyframes pulse-glow {
            0%, 100% { box-shadow: 0 4px 20px rgba(255, 255, 255, 0.3), 0 0 40px rgba(255, 89, 0, 0.4); }
            50% { box-shadow: 0 8px 30px rgba(255, 255, 255, 0.5), 0 0 60px rgba(255, 89, 0, 0.6); }
        }
        </style>
        
        <div class="container" style="position: relative; z-index: 1;">
            <div class="cta-content" style="max-width: 1100px; margin: 0 auto;">
                <!-- Value Proposition -->
                <div style="text-align: center; margin-bottom: 2.5rem;">
                    <span style="display: inline-block; background: linear-gradient(135deg, rgba(255, 255, 255, 0.25) 0%, rgba(255, 255, 255, 0.15) 100%); color: white; padding: 0.6rem 1.5rem; border-radius: 50px; font-size: 0.875rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 1.5rem; backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.3); box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);">
                        🎯 Exclusive Offer - Limited Time Only
                    </span>
                    <h2 class="cta-title" style="font-size: 3rem; margin-bottom: 1.25rem; line-height: 1.15; font-weight: 800; text-shadow: 0 2px 20px rgba(0, 0, 0, 0.3);">
                        Ready to Transform Your Business?
                    </h2>
                    <p class="cta-description" style="font-size: 1.35rem; margin-bottom: 0.75rem; opacity: 0.95; line-height: 1.6; font-weight: 500; max-width: 850px; margin-left: auto; margin-right: auto;">
                        Start cutting vinyl like a professional with industrial-grade equipment, <span style="color: #fbbf24; font-weight: 700;">FREE</span> on-site installation & comprehensive 2-day hands-on training
                    </p>
                    <p style="font-size: 1.1rem; opacity: 0.85; margin-top: 0.5rem;">
                        Join 500+ successful businesses across Kenya, Tanzania & Uganda
                    </p>
                </div>

                <!-- Enhanced Value Points Grid -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.5rem; margin-bottom: 3rem;">
                    <div style="background: linear-gradient(135deg, rgba(255, 255, 255, 0.2) 0%, rgba(255, 255, 255, 0.1) 100%); padding: 1.75rem; border-radius: 16px; backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2); text-align: center; transition: transform 0.3s, box-shadow 0.3s;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 10px 30px rgba(0,0,0,0.2)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                        <div style="display: inline-flex; align-items: center; justify-content: center; width: 70px; height: 70px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); border-radius: 50%; margin-bottom: 1rem; box-shadow: 0 8px 20px rgba(16, 185, 129, 0.4);">
                            <span style="font-size: 2rem;">✅</span>
                        </div>
                        <div style="font-weight: 700; font-size: 1.2rem; margin-bottom: 0.5rem; color: #fff;">Free Installation</div>
                        <div style="font-size: 0.95rem; opacity: 0.9; line-height: 1.5;">Professional setup at your location (Value: <?php echo $hero_currency === 'KES' ? 'KES 30,000' : ($hero_currency === 'TZS' ? 'TZS 180,000' : ($hero_currency === 'UGX' ? 'UGX 280,000' : '$300')); ?>)</div>
                    </div>
                    <div style="background: linear-gradient(135deg, rgba(255, 255, 255, 0.2) 0%, rgba(255, 255, 255, 0.1) 100%); padding: 1.75rem; border-radius: 16px; backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2); text-align: center; transition: transform 0.3s, box-shadow 0.3s;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 10px 30px rgba(0,0,0,0.2)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                        <div style="display: inline-flex; align-items: center; justify-content: center; width: 70px; height: 70px; background: #ff5900; border-radius: 50%; margin-bottom: 1rem; box-shadow: 0 8px 20px rgba(59, 130, 246, 0.4);">
                            <span style="font-size: 2rem;">🎓</span>
                        </div>
                        <div style="font-weight: 700; font-size: 1.2rem; margin-bottom: 0.5rem; color: #fff;">2-Day Full Training</div>
                        <div style="font-size: 0.95rem; opacity: 0.9; line-height: 1.5;">Expert hands-on guidance & software mastery (Value: <?php echo $hero_currency === 'KES' ? 'KES 35,000' : ($hero_currency === 'TZS' ? 'TZS 220,000' : ($hero_currency === 'UGX' ? 'UGX 330,000' : '$350')); ?>)</div>
                    </div>
                    <div style="background: linear-gradient(135deg, rgba(255, 255, 255, 0.2) 0%, rgba(255, 255, 255, 0.1) 100%); padding: 1.75rem; border-radius: 16px; backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2); text-align: center; transition: transform 0.3s, box-shadow 0.3s;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 10px 30px rgba(0,0,0,0.2)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                        <div style="display: inline-flex; align-items: center; justify-content: center; width: 70px; height: 70px; background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); border-radius: 50%; margin-bottom: 1rem; box-shadow: 0 8px 20px rgba(139, 92, 246, 0.4);">
                            <span style="font-size: 2rem;">🛡️</span>
                        </div>
                        <div style="font-weight: 700; font-size: 1.2rem; margin-bottom: 0.5rem; color: #fff;">12-Month Warranty</div>
                        <div style="font-size: 0.95rem; opacity: 0.9; line-height: 1.5;">Comprehensive parts & labor coverage with local support</div>
                    </div>
                    <div style="background: linear-gradient(135deg, rgba(255, 255, 255, 0.2) 0%, rgba(255, 255, 255, 0.1) 100%); padding: 1.75rem; border-radius: 16px; backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2); text-align: center; transition: transform 0.3s, box-shadow 0.3s;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 10px 30px rgba(0,0,0,0.2)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                        <div style="display: inline-flex; align-items: center; justify-content: center; width: 70px; height: 70px; background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); border-radius: 50%; margin-bottom: 1rem; box-shadow: 0 8px 20px rgba(245, 158, 11, 0.4);">
                            <span style="font-size: 2rem;">📞</span>
                        </div>
                        <div style="font-weight: 700; font-size: 1.2rem; margin-bottom: 0.5rem; color: #fff;">24/7 Support</div>
                        <div style="font-size: 0.95rem; opacity: 0.9; line-height: 1.5;">Phone + WhatsApp technical assistance anytime, anywhere</div>
                    </div>
                </div>

                <!-- Pricing & CTA -->
                <div style="background: linear-gradient(135deg, rgba(255, 255, 255, 0.25) 0%, rgba(255, 255, 255, 0.15) 100%); padding: 2.5rem; border-radius: 20px; margin-bottom: 2.5rem; backdrop-filter: blur(15px); border: 2px solid rgba(255, 255, 255, 0.3); box-shadow: 0 20px 50px rgba(0, 0, 0, 0.2);">
                    <div class="cta-price" style="margin-bottom: 2rem;">
                        <div class="price-label" style="font-size: 1.1rem; opacity: 0.9; margin-bottom: 1rem; font-weight: 600;">Complete Package Investment</div>
                        <div style="display: flex; align-items: center; justify-content: center; gap: 2rem; flex-wrap: wrap;">
                            <div>
                                <div class="price-amount" style="font-size: 3.5rem; font-weight: 800; line-height: 1; text-shadow: 0 2px 15px rgba(0, 0, 0, 0.3);"><?php echo $hero_formatted_price; ?></div>
                                <div style="font-size: 0.9rem; opacity: 0.8; margin-top: 0.5rem; text-align: center;">Starting price in <?php echo $hero_currency; ?></div>
                            </div>
                            <div style="text-align: left; background: rgba(255, 255, 255, 0.15); padding: 1.25rem; border-radius: 12px; backdrop-filter: blur(10px);">
                                <div style="font-size: 0.95rem; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem;">
                                    <span style="color: #10b981; font-size: 1.2rem;">✓</span>
                                    <span><strong>FREE</strong> Professional Installation</span>
                                </div>
                                <div style="font-size: 0.95rem; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem;">
                                    <span style="color: #10b981; font-size: 1.2rem;">✓</span>
                                    <span><strong>FREE</strong> 2-Day Comprehensive Training</span>
                                </div>
                                <div style="font-size: 0.95rem; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem;">
                                    <span style="color: #10b981; font-size: 1.2rem;">✓</span>
                                    <span><strong>12-Month</strong> Full Warranty Coverage</span>
                                </div>
                                <div style="font-size: 0.95rem; display: flex; align-items: center; gap: 0.5rem;">
                                    <span style="color: #10b981; font-size: 1.2rem;">✓</span>
                                    <span><strong>24/7</strong> Technical Support Access</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div style="text-align: center; margin-bottom: 2rem;">
                        <div style="display: inline-block; background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); padding: 1rem 2rem; border-radius: 12px; border: 2px solid rgba(255, 255, 255, 0.4); box-shadow: 0 8px 25px rgba(251, 191, 36, 0.4); animation: pulse-glow 2s infinite;">
                            <div style="font-weight: 700; font-size: 1.15rem; color: #78350f; margin-bottom: 0.25rem;">⚡ EXCLUSIVE BONUS - Order Today!</div>
                            <div style="font-size: 1rem; color: #92400e; font-weight: 600;">
                                Get FREE premium vinyl samples worth <?php echo $hero_currency === 'KES' ? 'KES 8,000' : ($hero_currency === 'TZS' ? 'TZS 50,000' : ($hero_currency === 'UGX' ? 'UGX 75,000' : '$80')); ?> + FREE shipping!
                            </div>
                        </div>
                    </div>

                    <div class="cta-buttons" style="display: flex; gap: 1.25rem; justify-content: center; flex-wrap: wrap; max-width: 700px; margin: 0 auto 1.5rem;">
                        <a href="https://wa.me/<?php echo htmlspecialchars($whatsapp_number_link); ?>?text=<?php echo urlencode('Hi! I\'m ready to transform my business with the ' . $hero_product_name . '. Please provide complete package details including free installation and training. My preferred currency is ' . $hero_currency . '.'); ?>" 
                           class="btn btn-primary btn-lg btn-pulse" target="_blank"
                           style="flex: 1; min-width: 280px; font-size: 1.3rem; padding: 1.5rem 2.5rem; background: white; color: #ff5900; border: none; box-shadow: 0 8px 30px rgba(255, 255, 255, 0.3); border-radius: 12px; font-weight: 700; display: inline-flex; align-items: center; justify-content: center; gap: 0.75rem; text-decoration: none; transition: all 0.3s;">
                            <svg width="26" height="26" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
                            Order Now via WhatsApp
                        </a>
                    </div>
                    
                    <div style="text-align: center;">
                        <a href="#contact" style="color: white; text-decoration: underline; font-size: 1rem; opacity: 0.9; font-weight: 600; transition: opacity 0.3s;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.9'">
                            Prefer email? Request a detailed custom quote here →
                        </a>
                    </div>
                </div>

                <!-- Enhanced Trust Indicators -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.5rem; padding: 1.5rem; background: rgba(255, 255, 255, 0.1); border-radius: 16px; backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2);">
                    <div style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem;">
                        <div style="display: inline-flex; align-items: center; justify-content: center; width: 45px; height: 45px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); border-radius: 50%; flex-shrink: 0; box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);">
                            <svg width="24" height="24" fill="white" viewBox="0 0 24 24"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/></svg>
                        </div>
                        <div>
                            <div style="font-weight: 700; font-size: 1rem;">100% Secure</div>
                            <div style="font-size: 0.85rem; opacity: 0.85;">Verified Payment</div>
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem;">
                        <div style="display: inline-flex; align-items: center; justify-content: center; width: 45px; height: 45px; background: #ff5900; border-radius: 50%; flex-shrink: 0; box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);">
                            <svg width="24" height="24" fill="white" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
                        </div>
                        <div>
                            <div style="font-weight: 700; font-size: 1rem;">500+ Businesses</div>
                            <div style="font-size: 0.85rem; opacity: 0.85;">Trust Us</div>
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem;">
                        <div style="display: inline-flex; align-items: center; justify-content: center; width: 45px; height: 45px; background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); border-radius: 50%; flex-shrink: 0; box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3);">
                            <svg width="24" height="24" fill="white" viewBox="0 0 24 24"><path d="M19 8l-4 4h3c0 3.31-2.69 6-6 6-1.01 0-1.97-.25-2.8-.7l-1.46 1.46C8.97 19.54 10.43 20 12 20c4.42 0 8-3.58 8-8h3l-4-4zM6 12c0-3.31 2.69-6 6-6 1.01 0 1.97.25 2.8.7l1.46-1.46C15.03 4.46 13.57 4 12 4c-4.42 0-8 3.58-8 8H1l4 4 4-4H6z"/></svg>
                        </div>
                        <div>
                            <div style="font-weight: 700; font-size: 1rem;">Fast Delivery</div>
                            <div style="font-size: 0.85rem; opacity: 0.85;">KE, TZ, UG</div>
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem;">
                        <div style="display: inline-flex; align-items: center; justify-content: center; width: 45px; height: 45px; background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); border-radius: 50%; flex-shrink: 0; box-shadow: 0 4px 15px rgba(139, 92, 246, 0.3);">
                            <svg width="24" height="24" fill="white" viewBox="0 0 24 24"><path d="M20 8h-3V4H3c-1.1 0-2 .9-2 2v11h2c0 1.66 1.34 3 3 3s3-1.34 3-3h6c0 1.66 1.34 3 3 3s3-1.34 3-3h2v-5l-3-4zM6 18.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zm13.5-9l1.96 2.5H17V9.5h2.5zm-1.5 9c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"/></svg>
                        </div>
                        <div>
                            <div style="font-weight: 700; font-size: 1rem;">Free Shipping</div>
                            <div style="font-size: 0.85rem; opacity: 0.85;">On All Orders</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="testimonials">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">What Our Customers Say</h2>
                <p class="section-description">Real experiences from businesses across East Africa</p>
            </div>
            <div class="testimonials-grid">
                <div class="testimonial-card">
                    <div class="stars">
                        <svg width="20" height="20" fill="#f59e0b" viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                        <svg width="20" height="20" fill="#f59e0b" viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                        <svg width="20" height="20" fill="#f59e0b" viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                        <svg width="20" height="20" fill="#f59e0b" viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                        <svg width="20" height="20" fill="#f59e0b" viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                    </div>
                    <p class="testimonial-text">"The JINKA plotter has transformed our sign-making business. The precision is incredible and the training was thorough. ROI achieved in 6 months!"</p>
                    <div class="testimonial-author">
                        <div class="author-avatar">JM</div>
                        <div class="author-info">
                            <div class="author-name">James Mwangi</div>
                            <div class="author-role">SignPro Kenya, Nairobi</div>
                        </div>
                    </div>
                </div>
                <div class="testimonial-card featured">
                    <div class="featured-badge">Top Review</div>
                    <div class="stars">
                        <svg width="20" height="20" fill="#f59e0b" viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                        <svg width="20" height="20" fill="#f59e0b" viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                        <svg width="20" height="20" fill="#f59e0b" viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                        <svg width="20" height="20" fill="#f59e0b" viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                        <svg width="20" height="20" fill="#f59e0b" viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                    </div>
                    <p class="testimonial-text">"Outstanding quality and exceptional support. The team installed everything and trained our staff. Three years later, still running perfectly. Best investment we made!"</p>
                    <div class="testimonial-author">
                        <div class="author-avatar">AK</div>
                        <div class="author-info">
                            <div class="author-name">Amina Karim</div>
                            <div class="author-role">Creative Prints, Dar es Salaam</div>
                        </div>
                    </div>
                </div>
                <div class="testimonial-card">
                    <div class="stars">
                        <svg width="20" height="20" fill="#f59e0b" viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                        <svg width="20" height="20" fill="#f59e0b" viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                        <svg width="20" height="20" fill="#f59e0b" viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                        <svg width="20" height="20" fill="#f59e0b" viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                        <svg width="20" height="20" fill="#f59e0b" viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                    </div>
                    <p class="testimonial-text">"Reliable, accurate, and fast. Perfect for our high-volume production. Customer service responds quickly whenever we have questions."</p>
                    <div class="testimonial-author">
                        <div class="author-avatar">DO</div>
                        <div class="author-info">
                            <div class="author-name">David Omondi</div>
                            <div class="author-role">VinylCraft, Mombasa</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="testimonial-cta">
                <p class="cta-text">Join 150+ satisfied businesses across East Africa</p>
                <a href="#contact" class="btn btn-primary btn-lg">Get Your Quote Today</a>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="contact">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Get In Touch</h2>
                <p class="section-description">Contact us for inquiries, demonstrations, or to place your order</p>
                <div style="display: flex; justify-content: center; gap: 8px; margin-top: 12px; flex-wrap: wrap;">
                    <span style="display: inline-flex; align-items: center; gap: 6px; font-size: 14px; color: #059669; font-weight: 500;">
                        <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20" style="flex-shrink: 0;">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        24/7 Available
                    </span>
                    <span style="display: inline-flex; align-items: center; gap: 6px; font-size: 14px; color: #059669; font-weight: 500;">
                        <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20" style="flex-shrink: 0;">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        Fast Response
                    </span>
                    <span style="display: inline-flex; align-items: center; gap: 6px; font-size: 14px; color: #059669; font-weight: 500;">
                        <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20" style="flex-shrink: 0;">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        Free Consultation
                    </span>
                </div>
            </div>
            
            <!-- Quick Contact CTA -->
            <div style="text-align: center; margin-bottom: 40px;">
                <a href="https://wa.me/<?php echo str_replace('+', '', $whatsapp_number); ?>?text=Hi%2C%20I%27d%20like%20to%20discuss%20cutting%20plotters%20for%20my%20business" 
                   target="_blank" 
                   class="btn btn-lg" 
                   style="background: linear-gradient(135deg, #25D366 0%, #128C7E 100%); color: white; padding: 18px 40px; font-size: 18px; border-radius: 12px; display: inline-flex; align-items: center; gap: 12px; box-shadow: 0 4px 20px rgba(37, 211, 102, 0.3); transition: all 0.3s ease; animation: pulse 2s infinite;">
                    <svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                    </svg>
                    <span style="font-weight: 600;">Chat on WhatsApp - Get Instant Response</span>
                </a>
                <p style="margin-top: 16px; color: #6b7280; font-size: 14px;">Or fill out the form below for detailed inquiries</p>
            </div>

            <div class="contact-content" style="display: grid; grid-template-columns: 1fr 1.5fr; gap: 40px; align-items: start;">
                <!-- Contact Info Cards -->
                <div style="display: flex; flex-direction: column; gap: 20px;">
                    <!-- Tanzania Contact Card -->
                    <div style="background: linear-gradient(135deg, #fff5f0 0%, #ffe8dc 100%); border-radius: 16px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.06);">
                        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
                            <div style="width: 48px; height: 48px; background: linear-gradient(135deg, #ff5900 0%, #e64f00 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <svg width="24" height="24" fill="white" viewBox="0 0 24 24">
                                    <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                                </svg>
                            </div>
                            <div>
                                <h4 style="margin: 0; font-size: 18px; font-weight: 600; color: #cc4700;">Tanzania Office</h4>
                                <p style="margin: 4px 0 0 0; font-size: 13px; color: #64748b;">Main Headquarters</p>
                            </div>
                        </div>
                        <div style="display: flex; flex-direction: column; gap: 12px;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <svg width="18" height="18" fill="#ff5900" viewBox="0 0 24 24" style="flex-shrink: 0;">
                                    <path d="M20.01 15.38c-1.23 0-2.42-.2-3.53-.56a.977.977 0 00-1.01.24l-1.57 1.97c-2.83-1.35-5.48-3.9-6.89-6.83l1.95-1.66c.27-.28.35-.67.24-1.02-.37-1.11-.56-2.3-.56-3.53 0-.54-.45-.99-.99-.99H4.19C3.65 3 3 3.24 3 3.99 3 13.28 10.73 21 20.01 21c.71 0 .99-.63.99-1.18v-3.45c0-.54-.45-.99-.99-.99z"/>
                                </svg>
                                <a href="tel:<?php echo $phone_number; ?>" style="color: #cc4700; font-weight: 500; font-size: 15px;"><?php echo $phone_number; ?></a>
                            </div>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <svg width="18" height="18" fill="#25D366" viewBox="0 0 24 24" style="flex-shrink: 0;">
                                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                                </svg>
                                <a href="https://wa.me/<?php echo str_replace('+', '', $whatsapp_number); ?>" target="_blank" style="color: #059669; font-weight: 500; font-size: 15px;">WhatsApp: <?php echo $whatsapp_number; ?></a>
                            </div>
                        </div>
                    </div>

                    <!-- Kenya Contact Card -->
                    <div style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border-radius: 16px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.06);">
                        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
                            <div style="width: 48px; height: 48px; background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <svg width="24" height="24" fill="white" viewBox="0 0 24 24">
                                    <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                                </svg>
                            </div>
                            <div>
                                <h4 style="margin: 0; font-size: 18px; font-weight: 600; color: #92400e;">Kenya Office</h4>
                                <p style="margin: 4px 0 0 0; font-size: 13px; color: #78716c;">Regional Branch</p>
                            </div>
                        </div>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <svg width="18" height="18" fill="#d97706" viewBox="0 0 24 24" style="flex-shrink: 0;">
                                <path d="M20.01 15.38c-1.23 0-2.42-.2-3.53-.56a.977.977 0 00-1.01.24l-1.57 1.97c-2.83-1.35-5.48-3.9-6.89-6.83l1.95-1.66c.27-.28.35-.67.24-1.02-.37-1.11-.56-2.3-.56-3.53 0-.54-.45-.99-.99-.99H4.19C3.65 3 3 3.24 3 3.99 3 13.28 10.73 21 20.01 21c.71 0 .99-.63.99-1.18v-3.45c0-.54-.45-.99-.99-.99z"/>
                            </svg>
                            <a href="tel:<?php echo $phone_number_ke; ?>" style="color: #92400e; font-weight: 500; font-size: 15px;"><?php echo $phone_number_ke; ?></a>
                        </div>
                    </div>

                    <!-- Email Card -->
                    <div style="background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%); border-radius: 16px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.06);">
                        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
                            <div style="width: 48px; height: 48px; background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <svg width="24" height="24" fill="white" viewBox="0 0 24 24">
                                    <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                                </svg>
                            </div>
                            <div>
                                <h4 style="margin: 0; font-size: 18px; font-weight: 600; color: #374151;">Email Us</h4>
                                <p style="margin: 4px 0 0 0; font-size: 13px; color: #6b7280;">Send detailed inquiries</p>
                            </div>
                        </div>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <svg width="18" height="18" fill="#4b5563" viewBox="0 0 24 24" style="flex-shrink: 0;">
                                <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                            </svg>
                            <a href="mailto:<?php echo $email; ?>" style="color: #374151; font-weight: 500; font-size: 15px; word-break: break-all;"><?php echo $email; ?></a>
                        </div>
                    </div>

                    <!-- Response Time Indicator -->
                    <div style="background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%); border-radius: 12px; padding: 16px; text-align: center;">
                        <div style="display: flex; align-items: center; justify-content: center; gap: 8px; margin-bottom: 8px;">
                            <div style="width: 10px; height: 10px; background: #10b981; border-radius: 50%; animation: pulse 2s infinite;"></div>
                            <span style="color: #059669; font-weight: 600; font-size: 14px;">We're Online Now!</span>
                        </div>
                        <p style="margin: 0; color: #047857; font-size: 13px;">Average response time: <strong>Under 5 minutes</strong></p>
                    </div>
                </div>

                <!-- Contact Form -->
                <div style="background: white; border-radius: 20px; padding: 40px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); border: 1px solid #e5e7eb;">
                    <div style="text-align: center; margin-bottom: 32px;">
                        <h3 style="margin: 0 0 8px 0; font-size: 24px; font-weight: 700; color: #111827;">Send Us a Message</h3>
                        <p style="margin: 0; color: #6b7280; font-size: 15px;">Fill out the form and we'll get back to you within 24 hours</p>
                    </div>
                    
                    <form action="contact" method="POST" class="contact-form">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                            <div class="form-group" style="margin: 0;">
                                <label for="name" style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151; font-size: 14px;">Full Name *</label>
                                <input type="text" id="name" name="name" required style="width: 100%; padding: 14px 16px; border: 2px solid #e5e7eb; border-radius: 10px; font-size: 15px; transition: all 0.3s ease; box-sizing: border-box;" onfocus="this.style.borderColor='#ff5900'; this.style.boxShadow='0 0 0 3px rgba(255, 89, 0, 0.1)'" onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'">
                            </div>
                            <div class="form-group" style="margin: 0;">
                                <label for="phone" style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151; font-size: 14px;">Phone Number *</label>
                                <input type="tel" id="phone" name="phone" required style="width: 100%; padding: 14px 16px; border: 2px solid #e5e7eb; border-radius: 10px; font-size: 15px; transition: all 0.3s ease; box-sizing: border-box;" onfocus="this.style.borderColor='#ff5900'; this.style.boxShadow='0 0 0 3px rgba(255, 89, 0, 0.1)'" onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'">
                            </div>
                        </div>
                        <div class="form-group" style="margin-bottom: 20px;">
                            <label for="email" style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151; font-size: 14px;">Email Address *</label>
                            <input type="email" id="email" name="email" required style="width: 100%; padding: 14px 16px; border: 2px solid #e5e7eb; border-radius: 10px; font-size: 15px; transition: all 0.3s ease; box-sizing: border-box;" onfocus="this.style.borderColor='#ff5900'; this.style.boxShadow='0 0 0 3px rgba(255, 89, 0, 0.1)'" onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'">
                        </div>
                        <div class="form-group" style="margin-bottom: 20px;">
                            <label for="business" style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151; font-size: 14px;">Business Name <span style="color: #9ca3af; font-weight: 400;">(Optional)</span></label>
                            <input type="text" id="business" name="business" style="width: 100%; padding: 14px 16px; border: 2px solid #e5e7eb; border-radius: 10px; font-size: 15px; transition: all 0.3s ease; box-sizing: border-box;" onfocus="this.style.borderColor='#ff5900'; this.style.boxShadow='0 0 0 3px rgba(255, 89, 0, 0.1)'" onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'">
                        </div>
                        <div class="form-group" style="margin-bottom: 28px;">
                            <label for="message" style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151; font-size: 14px;">Your Message *</label>
                            <textarea id="message" name="message" rows="5" required style="width: 100%; padding: 14px 16px; border: 2px solid #e5e7eb; border-radius: 10px; font-size: 15px; resize: vertical; min-height: 120px; transition: all 0.3s ease; box-sizing: border-box; font-family: inherit;" onfocus="this.style.borderColor='#ff5900'; this.style.boxShadow='0 0 0 3px rgba(255, 89, 0, 0.1)'" onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'" placeholder="Tell us about your requirements..."></textarea></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary btn-lg" style="width: 100%; padding: 18px; font-size: 17px; font-weight: 600; border-radius: 12px; background: linear-gradient(135deg, #ff5900 0%, #e64f00 100%); color: white; border: none; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 4px 14px rgba(255, 89, 0, 0.4);" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(255, 89, 0, 0.5)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 14px rgba(255, 89, 0, 0.4)'">
                            Send Message
                            <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24" style="display: inline-block; margin-left: 8px; vertical-align: middle;">
                                <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                            </svg>
                        </button>
                        <p style="margin-top: 16px; text-align: center; color: #9ca3af; font-size: 13px;">
                            <svg width="14" height="14" fill="currentColor" viewBox="0 0 20 20" style="display: inline-block; vertical-align: middle; margin-right: 4px;">
                                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                            </svg>
                            Your information is secure and will never be shared
                        </p>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>

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

