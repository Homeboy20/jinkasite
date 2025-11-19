<?php
if (!defined('JINKA_ACCESS')) {
    define('JINKA_ACCESS', true);
}
require_once __DIR__ . '/../admin/includes/auth.php';

$auth = requireAuth('admin');
$db = Database::getInstance()->getConnection();

echo "<h2>Creating Deliveries Tables...</h2>";

// Read SQL file
$sql = file_get_contents(__DIR__ . '/create_deliveries_table.sql');

// Split into individual statements
$statements = array_filter(array_map('trim', explode(';', $sql)));

$success = 0;
$errors = 0;

foreach ($statements as $statement) {
    if (empty($statement)) continue;
    
    echo "<p>Executing: " . substr($statement, 0, 100) . "...</p>";
    
    if ($db->query($statement)) {
        echo "<p style='color: green;'>✓ Success</p>";
        $success++;
    } else {
        echo "<p style='color: red;'>✗ Error: " . $db->error . "</p>";
        $errors++;
    }
}

echo "<hr>";
echo "<h3>Summary:</h3>";
echo "<p>Successful: $success</p>";
echo "<p>Errors: $errors</p>";

if ($errors === 0) {
    echo "<p style='color: green; font-weight: bold;'>✓ All tables created successfully!</p>";
    echo "<p><a href='../admin/deliveries.php'>Go to Delivery Management</a></p>";
} else {
    echo "<p style='color: orange;'>⚠ Some operations failed. Check the errors above.</p>";
}
?>
