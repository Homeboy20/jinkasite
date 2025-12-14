<?php
/**
 * SEO Helper Functions
 * Structured Data, Schema Markup, and SEO Utilities
 */

if (!defined('JINKA_ACCESS')) {
    die('Direct access not permitted');
}

/**
 * Generate JSON-LD structured data for a product
 */
function generate_product_schema($product) {
    $schema = [
        '@context' => 'https://schema.org/',
        '@type' => 'Product',
        'name' => $product['name'] ?? '',
        'description' => strip_tags($product['description'] ?? $product['short_description'] ?? ''),
        'sku' => $product['sku'] ?? '',
        'brand' => [
            '@type' => 'Brand',
            'name' => 'JINKA'
        ],
        'offers' => [
            '@type' => 'Offer',
            'url' => site_url('product-detail/' . ($product['slug'] ?? '')),
            'priceCurrency' => 'KES',
            'price' => $product['price_kes'] ?? 0,
            'availability' => ($product['stock_quantity'] ?? 0) > 0 ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
            'seller' => [
                '@type' => 'Organization',
                'name' => BUSINESS_NAME
            ]
        ]
    ];
    
    // Add image if available
    if (!empty($product['image'])) {
        $schema['image'] = normalize_product_image_url($product['image'], ['absolute' => true]);
    } elseif (!empty($product['images'])) {
        $images = is_string($product['images']) ? json_decode($product['images'], true) : $product['images'];
        if (is_array($images) && !empty($images)) {
            $schema['image'] = array_map(function($img) {
                return normalize_product_image_url($img, ['absolute' => true]);
            }, array_slice($images, 0, 3));
        }
    }
    
    // Add rating if available
    if (!empty($product['rating']) && !empty($product['review_count'])) {
        $schema['aggregateRating'] = [
            '@type' => 'AggregateRating',
            'ratingValue' => $product['rating'],
            'reviewCount' => $product['review_count']
        ];
    }
    
    return json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
}

/**
 * Generate breadcrumb schema
 */
function generate_breadcrumb_schema($items) {
    $schema = [
        '@context' => 'https://schema.org/',
        '@type' => 'BreadcrumbList',
        'itemListElement' => []
    ];
    
    foreach ($items as $index => $item) {
        $schema['itemListElement'][] = [
            '@type' => 'ListItem',
            'position' => $index + 1,
            'name' => $item['name'],
            'item' => $item['url']
        ];
    }
    
    return json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
}

/**
 * Generate organization schema
 */
function generate_organization_schema() {
    $schema = [
        '@context' => 'https://schema.org/',
        '@type' => 'Organization',
        'name' => BUSINESS_NAME,
        'url' => SITE_URL,
        'logo' => site_url('images/logo.png'),
        'contactPoint' => [
            '@type' => 'ContactPoint',
            'telephone' => BUSINESS_PHONE_KE,
            'contactType' => 'Customer Service',
            'areaServed' => ['KE', 'TZ'],
            'availableLanguage' => ['English', 'Swahili']
        ],
        'sameAs' => []
    ];
    
    // Add social media profiles
    $socials = [
        site_setting('facebook_url'),
        site_setting('twitter_url'),
        site_setting('instagram_url'),
        site_setting('linkedin_url'),
        site_setting('youtube_url')
    ];
    
    foreach ($socials as $social) {
        if (!empty($social)) {
            $schema['sameAs'][] = $social;
        }
    }
    
    return json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
}

/**
 * Generate website schema
 */
function generate_website_schema() {
    $schema = [
        '@context' => 'https://schema.org/',
        '@type' => 'WebSite',
        'name' => SITE_NAME,
        'url' => SITE_URL,
        'potentialAction' => [
            '@type' => 'SearchAction',
            'target' => [
                '@type' => 'EntryPoint',
                'urlTemplate' => site_url('products?search={search_term_string}')
            ],
            'query-input' => 'required name=search_term_string'
        ]
    ];
    
    return json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
}

/**
 * Output JSON-LD script tag
 */
function output_schema($schema_json) {
    echo '<script type="application/ld+json">' . "\n";
    echo $schema_json . "\n";
    echo '</script>' . "\n";
}

/**
 * Generate Open Graph meta tags
 */
function generate_og_tags($data) {
    $defaults = [
        'title' => site_setting('meta_title', SITE_NAME),
        'description' => site_setting('meta_description', ''),
        'image' => site_url('images/og-image.jpg'),
        'url' => current_url(false),
        'type' => 'website',
        'site_name' => SITE_NAME
    ];
    
    $og = array_merge($defaults, $data);
    
    echo '<meta property="og:title" content="' . esc_html($og['title']) . '">' . "\n";
    echo '<meta property="og:description" content="' . esc_html($og['description']) . '">' . "\n";
    echo '<meta property="og:image" content="' . esc_html($og['image']) . '">' . "\n";
    echo '<meta property="og:url" content="' . esc_html($og['url']) . '">' . "\n";
    echo '<meta property="og:type" content="' . esc_html($og['type']) . '">' . "\n";
    echo '<meta property="og:site_name" content="' . esc_html($og['site_name']) . '">' . "\n";
    
    // Twitter Card
    echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
    echo '<meta name="twitter:title" content="' . esc_html($og['title']) . '">' . "\n";
    echo '<meta name="twitter:description" content="' . esc_html($og['description']) . '">' . "\n";
    echo '<meta name="twitter:image" content="' . esc_html($og['image']) . '">' . "\n";
}

/**
 * Generate canonical URL
 */
function generate_canonical($url = null) {
    $canonical = $url ?? current_url(false);
    echo '<link rel="canonical" href="' . esc_html($canonical) . '">' . "\n";
}

/**
 * Generate meta robots tag
 */
function generate_robots_meta($index = true, $follow = true) {
    $content = ($index ? 'index' : 'noindex') . ', ' . ($follow ? 'follow' : 'nofollow');
    echo '<meta name="robots" content="' . $content . '">' . "\n";
}

/**
 * Generate XML sitemap entry
 */
function generate_sitemap_url($loc, $lastmod = null, $changefreq = 'weekly', $priority = '0.5') {
    $xml = '  <url>' . "\n";
    $xml .= '    <loc>' . htmlspecialchars($loc) . '</loc>' . "\n";
    
    if ($lastmod) {
        $xml .= '    <lastmod>' . date('Y-m-d', strtotime($lastmod)) . '</lastmod>' . "\n";
    }
    
    $xml .= '    <changefreq>' . $changefreq . '</changefreq>' . "\n";
    $xml .= '    <priority>' . $priority . '</priority>' . "\n";
    $xml .= '  </url>' . "\n";
    
    return $xml;
}

/**
 * Optimize meta description length
 */
function optimize_meta_description($text, $maxLength = 160) {
    $text = strip_tags($text);
    $text = preg_replace('/\s+/', ' ', $text);
    $text = trim($text);
    
    if (mb_strlen($text) <= $maxLength) {
        return $text;
    }
    
    $text = mb_substr($text, 0, $maxLength - 3);
    $lastSpace = mb_strrpos($text, ' ');
    
    if ($lastSpace !== false) {
        $text = mb_substr($text, 0, $lastSpace);
    }
    
    return $text . '...';
}

/**
 * Generate FAQ schema
 */
function generate_faq_schema($faqs) {
    $schema = [
        '@context' => 'https://schema.org/',
        '@type' => 'FAQPage',
        'mainEntity' => []
    ];
    
    foreach ($faqs as $faq) {
        $schema['mainEntity'][] = [
            '@type' => 'Question',
            'name' => $faq['question'],
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text' => $faq['answer']
            ]
        ];
    }
    
    return json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
}
