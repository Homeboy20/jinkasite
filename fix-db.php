<?php
// Diagnostic + fix-up: re-runs every SQL file in database/ against the
// configured DB, printing per-statement errors so we can see what's failing.
// Hit via: https://ndosa.store/fix-db.php?token=<value of FIX_DB_TOKEN env var>
//
// Safe to leave in repo: gated by env-var token.

if (!defined('JINKA_ACCESS')) define('JINKA_ACCESS', true);
require_once __DIR__ . '/includes/config.php';

header('Content-Type: text/plain; charset=utf-8');

$expected = getenv('FIX_DB_TOKEN');
$got = $_GET['token'] ?? '';
if (!$expected || !hash_equals($expected, $got)) {
    http_response_code(403);
    echo "Forbidden. Set FIX_DB_TOKEN env var and pass ?token=<value>.\n";
    exit;
}

$db = Database::getInstance()->getConnection();
echo "DB host: " . DB_HOST . "\n";
echo "DB name: " . DB_NAME . "\n\n";

// One-off remediation: complete-deployment.sql defined product_images
// with image_url/display_order/is_primary but the rest of the app uses
// image_path/sort_order/is_featured (per admin/setup_product_gallery.php).
// If the table exists but has the wrong shape and is empty, drop and recreate.
$res = @$db->query("SHOW COLUMNS FROM product_images LIKE 'image_url'");
if ($res && $res->num_rows > 0) {
    $cnt = $db->query("SELECT COUNT(*) c FROM product_images")->fetch_assoc()['c'] ?? 0;
    if ((int)$cnt === 0) {
        echo "[remediate] product_images has wrong schema (image_url) and is empty — recreating\n";
        $db->query("DROP TABLE product_images");
        $db->query("CREATE TABLE `product_images` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `product_id` int(11) NOT NULL,
          `image_path` varchar(255) NOT NULL,
          `is_featured` tinyint(1) DEFAULT 0,
          `sort_order` int(11) DEFAULT 0,
          `alt_text` varchar(255) DEFAULT NULL,
          `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `product_id` (`product_id`),
          KEY `is_featured` (`is_featured`),
          KEY `sort_order` (`sort_order`),
          CONSTRAINT `product_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        if ($db->error) echo "[remediate] ERR: " . $db->error . "\n";
        else echo "[remediate] product_images recreated\n";
    } else {
        echo "[remediate] product_images has wrong schema but contains $cnt rows — left alone\n";
    }
}
echo "\n";

// List tables before
echo "=== Tables BEFORE ===\n";
$res = $db->query("SHOW TABLES");
while ($row = $res->fetch_array()) echo "  - {$row[0]}\n";
echo "\n";

$files = [
    'database/schema.sql',
    'database/complete-deployment.sql',
    'database/create-customer-tables.sql',
    'database/create-order-items-table.sql',
    'database/create_deliveries_table.sql',
    'database/support_system.sql',
    'database/theme_settings.sql',
    'database/product_relationships.sql',
    'database/add-firebase-auth-fields.sql',
    'database/add-phone-verification.sql',
    'database/add_mpesa_fields.sql',
    'database/add-indexes-safe.sql',
    'database/add-performance-indexes.sql',
];

foreach ($files as $file) {
    $path = __DIR__ . '/' . $file;
    if (!is_file($path)) {
        echo "SKIP $file (missing)\n";
        continue;
    }
    echo "=== $file ===\n";
    $sql = file_get_contents($path);
    // Strip CREATE DATABASE / USE / transaction wrappers (target the configured DB instead)
    $sql = preg_replace('/^\s*CREATE\s+DATABASE[^;]*;\s*$/im', '', $sql);
    $sql = preg_replace('/^\s*USE\s+[^;]*;\s*$/im', '', $sql);
    $sql = preg_replace('/^\s*START\s+TRANSACTION\s*;\s*$/im', '', $sql);
    $sql = preg_replace('/^\s*COMMIT\s*;\s*$/im', '', $sql);

    // Strip -- line comments and /* ... */ block comments first.
    $clean = preg_replace('!/\*.*?\*/!s', '', $sql);
    $clean = preg_replace('/^\s*--.*$/m', '', $clean);

    // Split on semicolon followed by end-of-line (handles multi-line CREATE TABLE).
    $statements = preg_split('/;\s*(?:\r?\n|$)/', $clean);
    $ok = 0; $fail = 0; $errs = [];
    foreach ($statements as $stmt) {
        $stmt = trim($stmt);
        if ($stmt === '') continue;
        if (@$db->query($stmt)) {
            $ok++;
        } else {
            $fail++;
            $first = strtok($stmt, "\n");
            $errs[] = $db->error . '  @ ' . substr($first, 0, 80);
        }
    }
    echo "  -> $ok statements ran, $fail errors\n";
    foreach (array_slice($errs, 0, 8) as $e) echo "     ERR: $e\n";
    echo "\n";
}

echo "=== Tables AFTER ===\n";
$res = $db->query("SHOW TABLES");
while ($row = $res->fetch_array()) echo "  - {$row[0]}\n";

echo "\nDONE.\n";
