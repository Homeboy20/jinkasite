<?php
define('JINKA_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';

$result = $conn->query('DESCRIBE customers');
$fields = ['id', 'phone', 'phone_verified', 'email_verified', 'email_verification_token', 'last_login', 'password'];

echo "Checking customers table columns:\n\n";
while ($row = $result->fetch_assoc()) {
    if (in_array($row['Field'], $fields)) {
        echo $row['Field'] . " - " . $row['Type'] . " - " . ($row['Null'] == 'YES' ? 'NULL' : 'NOT NULL') . "\n";
    }
}
