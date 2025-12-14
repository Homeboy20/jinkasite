<?php
/**
 * Database Migration Runner
 * Run this script to apply the phone verification migration
 */

define('JINKA_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';

echo "=== Database Migration: Add Verification Columns ===\n\n";

// Read the SQL file
$sqlFile = __DIR__ . '/add-phone-verification.sql';
if (!file_exists($sqlFile)) {
    die("ERROR: Migration file not found: $sqlFile\n");
}

$sql = file_get_contents($sqlFile);
if ($sql === false) {
    die("ERROR: Could not read migration file\n");
}

// Split by semicolon to get individual statements
$statements = array_filter(array_map('trim', explode(';', $sql)));

$success = 0;
$errors = 0;

foreach ($statements as $statement) {
    // Skip empty statements and comments
    if (empty($statement) || substr($statement, 0, 2) === '--') {
        continue;
    }
    
    echo "Executing: " . substr($statement, 0, 60) . "...\n";
    
    try {
        $conn->exec($statement);
        echo "✓ Success\n\n";
        $success++;
    } catch (PDOException $e) {
        // Check if error is "Duplicate column name" - which means column already exists
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "⚠ Column already exists (skipping)\n\n";
            $success++;
        } else {
            echo "✗ ERROR: " . $e->getMessage() . "\n\n";
            $errors++;
        }
    }
}

echo "=== Migration Complete ===\n";
echo "Successful: $success\n";
echo "Errors: $errors\n";

if ($errors === 0) {
    echo "\n✓ All migrations applied successfully!\n";
    echo "\nYou can now use the SMS OTP login and verification features.\n";
} else {
    echo "\n⚠ Some migrations failed. Please check the errors above.\n";
}
