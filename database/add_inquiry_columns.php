<?php
/**
 * Migration: Add admin_reply and admin_notes columns to inquiries table
 * Run this script once to add the missing columns
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

echo "Adding columns to inquiries table...\n\n";

try {
    // Check if admin_reply column exists
    $result = $db->query("SHOW COLUMNS FROM inquiries LIKE 'admin_reply'");
    
    if ($result->num_rows > 0) {
        echo "✓ Column 'admin_reply' already exists.\n";
    } else {
        $sql = "ALTER TABLE inquiries ADD COLUMN admin_reply TEXT DEFAULT NULL AFTER message";
        if ($db->query($sql)) {
            echo "✓ Successfully added 'admin_reply' column!\n";
        } else {
            echo "✗ Error adding admin_reply column: " . $db->error . "\n";
        }
    }
    
    // Check if admin_notes column exists
    $result = $db->query("SHOW COLUMNS FROM inquiries LIKE 'admin_notes'");
    
    if ($result->num_rows > 0) {
        echo "✓ Column 'admin_notes' already exists.\n";
    } else {
        $sql = "ALTER TABLE inquiries ADD COLUMN admin_notes TEXT DEFAULT NULL AFTER admin_reply";
        if ($db->query($sql)) {
            echo "✓ Successfully added 'admin_notes' column!\n";
        } else {
            echo "✗ Error adding admin_notes column: " . $db->error . "\n";
        }
    }
    
    // Check if replied_at column exists
    $result = $db->query("SHOW COLUMNS FROM inquiries LIKE 'replied_at'");
    
    if ($result->num_rows > 0) {
        echo "✓ Column 'replied_at' already exists.\n";
    } else {
        $sql = "ALTER TABLE inquiries ADD COLUMN replied_at TIMESTAMP NULL DEFAULT NULL AFTER admin_notes";
        if ($db->query($sql)) {
            echo "✓ Successfully added 'replied_at' column!\n";
        } else {
            echo "✗ Error adding replied_at column: " . $db->error . "\n";
        }
    }
    
    // Check if replied_by column exists
    $result = $db->query("SHOW COLUMNS FROM inquiries LIKE 'replied_by'");
    
    if ($result->num_rows > 0) {
        echo "✓ Column 'replied_by' already exists.\n";
    } else {
        $sql = "ALTER TABLE inquiries ADD COLUMN replied_by INT DEFAULT NULL AFTER replied_at";
        if ($db->query($sql)) {
            echo "✓ Successfully added 'replied_by' column!\n";
        } else {
            echo "✗ Error adding replied_by column: " . $db->error . "\n";
        }
    }
    
    // Show the updated table structure
    echo "\nCurrent inquiries table structure:\n";
    echo "==================================\n";
    $result = $db->query("DESCRIBE inquiries");
    while ($row = $result->fetch_assoc()) {
        echo sprintf("%-20s %-20s %s\n", 
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
