<?php
define('JINKA_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';

mysqli_report(MYSQLI_REPORT_OFF);

echo "Adding missing columns to customers table...\n\n";

$columns = [
    ['name' => 'notes', 'definition' => 'TEXT NULL COMMENT \'Admin notes about customer\''],
];

foreach ($columns as $col) {
    $sql = "ALTER TABLE customers ADD COLUMN {$col['name']} {$col['definition']}";
    
    echo "Adding '{$col['name']}'... ";
    if (@$conn->query($sql)) {
        echo "✓ Added\n";
    } else {
        if (strpos($conn->error, 'Duplicate column') !== false) {
            echo "⚠ Already exists\n";
        } else {
            echo "✗ Error: " . $conn->error . "\n";
        }
    }
}

echo "\n✅ Done!\n";
