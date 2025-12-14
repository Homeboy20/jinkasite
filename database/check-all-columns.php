<?php
define('JINKA_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';

$result = $conn->query('DESCRIBE customers');

echo "Full customers table structure:\n\n";
while ($row = $result->fetch_assoc()) {
    echo sprintf("%-30s %-20s %-10s\n", $row['Field'], $row['Type'], $row['Null']);
}
