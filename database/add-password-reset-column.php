<?php
define('JINKA_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';

echo "Adding password_reset_expires column...\n";

mysqli_report(MYSQLI_REPORT_OFF);

$sql = "ALTER TABLE customers ADD COLUMN password_reset_expires DATETIME NULL COMMENT 'Password reset token expiry time'";

if (@$conn->query($sql)) {
    echo "✅ Column added successfully\n";
} else {
    if (strpos($conn->error, 'Duplicate column') !== false) {
        echo "⚠️  Column already exists\n";
    } else {
        echo "❌ Error: " . $conn->error . "\n";
    }
}
