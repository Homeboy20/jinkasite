<?php
if (!defined('JINKA_ACCESS')) {
    define('JINKA_ACCESS', true);
}
require_once 'includes/auth.php';

$auth = requireAuth('admin');
$db = Database::getInstance()->getConnection();

// Get column names
$result = $db->query("SHOW COLUMNS FROM orders");
echo "Orders table columns:\n\n";
while ($row = $result->fetch_assoc()) {
    echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
}

// Get a sample order if exists
echo "\n\nSample order (if any):\n\n";
$orderResult = $db->query("SELECT * FROM orders LIMIT 1");
if ($order = $orderResult->fetch_assoc()) {
    foreach ($order as $key => $value) {
        echo "$key: " . (is_null($value) ? 'NULL' : substr($value, 0, 50)) . "\n";
    }
} else {
    echo "No orders in database yet\n";
}
