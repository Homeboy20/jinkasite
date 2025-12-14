<?php
define('JINKA_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';

echo "=== Adding Verification Columns ===\n\n";

$columns = [
    ['phone_verified', 'TINYINT(1) DEFAULT 0'],
    ['email_verified', 'TINYINT(1) DEFAULT 0'],
    ['email_verification_token', 'VARCHAR(64) NULL'],
    ['last_login', 'TIMESTAMP NULL'],
    ['password', 'VARCHAR(255) NULL']
];

$added = 0;
$skipped = 0;

mysqli_report(MYSQLI_REPORT_OFF); // Disable exception throwing

foreach ($columns as $col) {
    list($name, $type) = $col;
    $sql = "ALTER TABLE customers ADD COLUMN `$name` $type";
    
    echo "Adding '$name'... ";
    if (@$conn->query($sql)) {
        echo "✓ Added\n";
        $added++;
    } else {
        $error = $conn->error;
        if (strpos($error, 'Duplicate column') !== false) {
            echo "⚠ Already exists\n";
            $skipped++;
        } else {
            echo "✗ Error: $error\n";
        }
    }
}

echo "\n=== Complete ===\n";
echo "Added: $added\n";
echo "Skipped: $skipped\n";
