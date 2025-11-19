<?php
/**
 * Migration: Add image column to products table
 * Run this script once to add the image column
 */

// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'jinka_plotter';

// Create connection
$db = new mysqli($host, $username, $password, $database);

// Check connection
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

echo "Adding 'image' column to products table...\n";

try {
    // Check if column already exists
    $result = $db->query("SHOW COLUMNS FROM products LIKE 'image'");
    
    if ($result->num_rows > 0) {
        echo "✓ Column 'image' already exists in products table.\n";
    } else {
        // Add the image column
        $sql = "ALTER TABLE products ADD COLUMN image VARCHAR(255) DEFAULT NULL AFTER description";
        
        if ($db->query($sql)) {
            echo "✓ Successfully added 'image' column to products table!\n";
        } else {
            echo "✗ Error adding column: " . $db->error . "\n";
        }
    }
    
    // Show the updated table structure
    echo "\nCurrent products table structure:\n";
    echo "=================================\n";
    $result = $db->query("DESCRIBE products");
    while ($row = $result->fetch_assoc()) {
        echo sprintf("%-25s %-15s %s\n", 
            $row['Field'], 
            $row['Type'], 
            $row['Null'] === 'YES' ? 'NULL' : 'NOT NULL'
        );
    }
    
    echo "\n✓ Migration completed successfully!\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

$db->close();
