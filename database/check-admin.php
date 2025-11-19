<?php
$conn = new mysqli('localhost', 'root', '', 'jinka_plotter');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "📊 Admin Users Table Content:\n";
echo "=============================\n";

$result = $conn->query('SELECT * FROM admin_users');
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "ID: {$row['id']}\n";
        echo "Username: {$row['username']}\n";
        echo "Email: {$row['email']}\n";
        echo "Full Name: {$row['full_name']}\n";
        echo "Role: {$row['role']}\n";
        echo "Active: {$row['is_active']}\n";
        echo "Password Hash: " . substr($row['password_hash'], 0, 30) . "...\n";
        echo "Created: {$row['created_at']}\n";
        echo "---\n";
    }
} else {
    echo "No admin users found in database.\n\n";
    echo "Creating admin user...\n";
    
    $passwordHash = password_hash('Admin@123456', PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO admin_users (username, email, password_hash, full_name, role, is_active) VALUES (?, ?, ?, ?, ?, 1)");
    $username = 'admin';
    $email = 'admin@procutsolutions.com';
    $fullName = 'System Administrator';
    $role = 'super_admin';
    
    $stmt->bind_param('sssss', $username, $email, $passwordHash, $fullName, $role);
    
    if ($stmt->execute()) {
        echo "✅ Admin user created successfully!\n";
        echo "   Username: admin\n";
        echo "   Password: Admin@123456\n";
    } else {
        echo "❌ Error creating admin user: " . $stmt->error . "\n";
    }
    $stmt->close();
}

$conn->close();
?>