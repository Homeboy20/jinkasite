<?php
$conn = new mysqli('localhost', 'root', '', 'jinka_plotter');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "🔓 Resetting Admin Account Lockout\n";
echo "==================================\n\n";

// Reset login attempts and lockout
$sql = "UPDATE admin_users SET login_attempts = 0, lockout_until = NULL WHERE username = 'admin'";

if ($conn->query($sql)) {
    echo "✅ Admin account lockout reset successfully!\n";
    echo "   Login attempts cleared\n";
    echo "   Lockout time removed\n\n";
    echo "You can now try logging in again with:\n";
    echo "Username: admin\n";
    echo "Password: Admin@123456\n";
} else {
    echo "❌ Error resetting lockout: " . $conn->error . "\n";
}

// Verify the changes
$result = $conn->query("SELECT username, login_attempts, lockout_until FROM admin_users WHERE username = 'admin'");
if ($result) {
    $user = $result->fetch_assoc();
    echo "\nCurrent status:\n";
    echo "Username: {$user['username']}\n";
    echo "Login attempts: {$user['login_attempts']}\n";
    echo "Lockout until: " . ($user['lockout_until'] ? $user['lockout_until'] : 'None') . "\n";
}

$conn->close();
?>