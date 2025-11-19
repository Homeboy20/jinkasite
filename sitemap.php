<?php
if (!defined('JINKA_ACCESS')) {
    define('JINKA_ACCESS', true);
}

require_once __DIR__ . '/includes/config.php';

header('Content-Type: application/xml; charset=UTF-8');

$baseUrl = rtrim(SITE_URL, '/');
$now = gmdate('Y-m-d');

$staticUrls = [
    [
        'loc' => site_url('/'),
        'changefreq' => 'weekly',
        'priority' => '1.0',
        'lastmod' => $now
    ],
    [
        'loc' => site_url('products.php'),
        'changefreq' => 'daily',
        'priority' => '0.9',
        'lastmod' => $now
    ]
];

$dynamicUrls = [];

try {
    $connection = Database::getInstance()->getConnection();

    $productQuery = "SELECT slug, updated_at, created_at FROM products WHERE is_active = 1";
    if ($result = $connection->query($productQuery)) {
        while ($row = $result->fetch_assoc()) {
            if (empty($row['slug'])) {
                continue;
            }

            $loc = site_url('product-detail.php?slug=' . urlencode($row['slug']));
            $lastmodSource = $row['updated_at'] ?? $row['created_at'] ?? null;
            $lastmod = $lastmodSource ? gmdate('Y-m-d', strtotime($lastmodSource)) : $now;

            $dynamicUrls[] = [
                'loc' => $loc,
                'changefreq' => 'weekly',
                'priority' => '0.8',
                'lastmod' => $lastmod
            ];
        }
    }

    $categoryQuery = "SELECT id, name FROM categories WHERE is_active = 1";
    if ($categoryResult = $connection->query($categoryQuery)) {
        while ($category = $categoryResult->fetch_assoc()) {
            $categoryId = (int)$category['id'];
            if ($categoryId <= 0) {
                continue;
            }

            $dynamicUrls[] = [
                'loc' => site_url('products.php?category=' . $categoryId),
                'changefreq' => 'weekly',
                'priority' => '0.6',
                'lastmod' => $now
            ];
        }
    }
} catch (Throwable $exception) {
    // Fail silently; sitemap should still render static URLs
}

$urls = array_merge($staticUrls, $dynamicUrls);

echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

foreach ($urls as $url) {
    if (empty($url['loc'])) {
        continue;
    }

    echo "  <url>\n";
    echo '    <loc>' . htmlspecialchars($url['loc'], ENT_QUOTES, 'UTF-8') . "</loc>\n";

    if (!empty($url['lastmod'])) {
        echo '    <lastmod>' . htmlspecialchars($url['lastmod'], ENT_QUOTES, 'UTF-8') . "</lastmod>\n";
    }

    if (!empty($url['changefreq'])) {
        echo '    <changefreq>' . htmlspecialchars($url['changefreq'], ENT_QUOTES, 'UTF-8') . "</changefreq>\n";
    }

    if (!empty($url['priority'])) {
        echo '    <priority>' . htmlspecialchars($url['priority'], ENT_QUOTES, 'UTF-8') . "</priority>\n";
    }

    echo "  </url>\n";
}

echo '</urlset>';
