<?php
$conn = new mysqli('localhost', 'root', '', 'jinka_plotter');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "📋 Tables in jinka_plotter database:\n";
echo "====================================\n";

$result = $conn->query('SHOW TABLES');
if ($result->num_rows > 0) {
    while ($row = $result->fetch_array()) {
        echo "- " . $row[0] . "\n";
    }
} else {
    echo "No tables found.\n";
}

echo "\n📊 Checking admin_users table structure:\n";
$result = $conn->query('DESC admin_users');
if ($result) {
    echo "Columns in admin_users:\n";
    while ($row = $result->fetch_assoc()) {
        echo "- {$row['Field']} ({$row['Type']})\n";
    }
} else {
    echo "❌ admin_users table does not exist: " . $conn->error . "\n";
}

$conn->close();
?>