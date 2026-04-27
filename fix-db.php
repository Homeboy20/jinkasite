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

// List tables before
echo "=== Tables BEFORE ===\n";
$res = $db->query("SHOW TABLES");
while ($row = $res->fetch_array()) echo "  - {$row[0]}\n";
echo "\n";

$files = [
    'database/schema.sql',
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

    $ok = 0; $fail = 0; $errs = [];
    if ($db->multi_query($sql)) {
        do {
            $ok++;
            // Drain any result set so the next statement can run
            if ($res = $db->store_result()) { $res->free(); }
        } while ($db->more_results() && ($more = $db->next_result()) !== false ? true : false);
    }
    // After multi_query, surface any errors at the connection level
    while ($db->errno) {
        $fail++;
        $errs[] = $db->error;
        if (!$db->more_results()) break;
        $db->next_result();
    }
    echo "  -> $ok statements ran, $fail errors\n";
    foreach (array_slice($errs, 0, 5) as $e) echo "     ERR: $e\n";
    echo "\n";
}

echo "=== Tables AFTER ===\n";
$res = $db->query("SHOW TABLES");
while ($row = $res->fetch_array()) echo "  - {$row[0]}\n";

echo "\nDONE.\n";
