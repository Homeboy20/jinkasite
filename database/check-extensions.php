<?php
echo "🔍 PHP Database Extension Check\n";
echo "===============================\n\n";

echo "PHP Version: " . phpversion() . "\n";
echo "Available PDO drivers: " . implode(', ', PDO::getAvailableDrivers()) . "\n\n";

if (extension_loaded('pdo')) {
    echo "✅ PDO extension is loaded\n";
} else {
    echo "❌ PDO extension is NOT loaded\n";
}

if (extension_loaded('pdo_mysql')) {
    echo "✅ PDO MySQL extension is loaded\n";
} else {
    echo "❌ PDO MySQL extension is NOT loaded\n";
}

if (extension_loaded('mysqli')) {
    echo "✅ MySQLi extension is loaded\n";
} else {
    echo "❌ MySQLi extension is NOT loaded\n";
}

echo "\nTo enable PDO MySQL in WAMP:\n";
echo "1. Click WAMP icon in system tray\n";
echo "2. Go to PHP > PHP Extensions\n";
echo "3. Check 'pdo_mysql' to enable it\n";
echo "4. Restart WAMP services\n\n";

echo "Or manually edit php.ini and uncomment:\n";
echo "extension=pdo_mysql\n";
?>