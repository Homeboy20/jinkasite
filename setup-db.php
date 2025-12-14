<?php
/**
 * One-Click Database Setup
 * Creates the database and imports the schema automatically
 */

define('JINKA_ACCESS', true);
require_once __DIR__ . '/includes/config.php';

$status = [];
$error = null;

// Check if setup was requested
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['setup'])) {
    try {
        // Step 1: Connect to MySQL (without database)
        $conn = @new mysqli(DB_HOST, DB_USER, DB_PASS);
        
        if ($conn->connect_error) {
            throw new Exception("Cannot connect to MySQL: " . $conn->connect_error);
        }
        
        $status[] = ['type' => 'success', 'message' => 'Connected to MySQL server'];
        
        // Step 2: Create database
        $db_name = DB_NAME;
        $sql = "CREATE DATABASE IF NOT EXISTS `{$db_name}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
        
        if (!$conn->query($sql)) {
            throw new Exception("Error creating database: " . $conn->error);
        }
        
        $status[] = ['type' => 'success', 'message' => "Database '{$db_name}' created successfully"];
        
        // Step 3: Select the database
        if (!$conn->select_db($db_name)) {
            throw new Exception("Error selecting database: " . $conn->error);
        }
        
        // Step 4: Import schema.sql
        $schema_file = __DIR__ . '/database/schema.sql';
        
        if (!file_exists($schema_file)) {
            throw new Exception("Schema file not found: {$schema_file}");
        }
        
        $sql_content = file_get_contents($schema_file);
        
        // Remove comments and split into individual queries
        $sql_content = preg_replace('/--.*$/m', '', $sql_content);
        $sql_content = preg_replace('/\/\*.*?\*\//s', '', $sql_content);
        
        // Split by semicolon but keep them
        $queries = array_filter(array_map('trim', explode(';', $sql_content)));
        
        $executed = 0;
        $skipped = 0;
        
        foreach ($queries as $query) {
            if (empty($query)) {
                continue;
            }
            
            // Skip database creation/use statements (we already did that)
            if (stripos($query, 'CREATE DATABASE') !== false || 
                stripos($query, 'USE ') === 0 ||
                stripos($query, 'START TRANSACTION') !== false ||
                stripos($query, 'COMMIT') !== false ||
                stripos($query, 'SET ') === 0) {
                $skipped++;
                continue;
            }
            
            if (!$conn->query($query)) {
                // Log error but continue (some tables might already exist)
                $status[] = ['type' => 'warning', 'message' => 'Query warning: ' . $conn->error];
            } else {
                $executed++;
            }
        }
        
        $status[] = ['type' => 'success', 'message' => "Executed {$executed} SQL statements successfully"];
        
        if ($skipped > 0) {
            $status[] = ['type' => 'info', 'message' => "Skipped {$skipped} configuration statements"];
        }
        
        // Step 5: Verify tables were created
        $result = $conn->query("SHOW TABLES");
        $table_count = $result ? $result->num_rows : 0;
        
        $status[] = ['type' => 'success', 'message' => "Found {$table_count} tables in database"];
        
        // Create default admin if admin_users table exists
        $admin_check = $conn->query("SHOW TABLES LIKE 'admin_users'");
        if ($admin_check && $admin_check->num_rows > 0) {
            // Check if admin exists
            $admin_exists = $conn->query("SELECT COUNT(*) as count FROM admin_users");
            if ($admin_exists) {
                $row = $admin_exists->fetch_assoc();
                if ($row['count'] == 0) {
                    // Create default admin
                    $default_password = password_hash('admin123', PASSWORD_DEFAULT);
                    $insert_admin = $conn->prepare("INSERT INTO admin_users (username, email, password_hash, full_name, role) VALUES (?, ?, ?, ?, ?)");
                    $username = 'admin';
                    $email = 'admin@jinkaplotter.com';
                    $full_name = 'Administrator';
                    $role = 'super_admin';
                    
                    $insert_admin->bind_param('sssss', $username, $email, $default_password, $full_name, $role);
                    
                    if ($insert_admin->execute()) {
                        $status[] = ['type' => 'success', 'message' => 'Default admin created (username: admin, password: admin123)'];
                    }
                }
            }
        }
        
        $conn->close();
        
        $status[] = ['type' => 'success', 'message' => '✅ Setup completed successfully!'];
        
    } catch (Exception $e) {
        $error = $e->getMessage();
        $status[] = ['type' => 'error', 'message' => $error];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            margin-top: 0;
        }
        .status {
            padding: 15px;
            margin: 10px 0;
            border-radius: 4px;
            font-weight: 500;
        }
        .status.success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .status.error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .status.warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
        }
        .status.info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
        }
        .btn {
            background: #007bff;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            display: inline-block;
        }
        .btn:hover {
            background: #0056b3;
        }
        .btn.success {
            background: #28a745;
        }
        .btn.success:hover {
            background: #218838;
        }
        .info-box {
            background: #f8f9fa;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
            border-left: 4px solid #007bff;
        }
        .warning-box {
            background: #fff3cd;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
            border-left: 4px solid #ffc107;
        }
        code {
            background: #f8f9fa;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }
        ol {
            line-height: 1.8;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🗄️ Database Setup</h1>
        
        <?php if (empty($status)): ?>
            <p>This tool will automatically:</p>
            <ol>
                <li>Create the database <code><?php echo htmlspecialchars(DB_NAME); ?></code></li>
                <li>Import all tables from <code>database/schema.sql</code></li>
                <li>Create a default admin user (if needed)</li>
            </ol>
            
            <div class="info-box">
                <strong>Current Configuration:</strong><br>
                <strong>Host:</strong> <?php echo htmlspecialchars(DB_HOST); ?><br>
                <strong>Database:</strong> <?php echo htmlspecialchars(DB_NAME); ?><br>
                <strong>User:</strong> <?php echo htmlspecialchars(DB_USER); ?><br>
                <strong>Password:</strong> <?php echo DB_PASS === '' ? '(empty)' : '••••••••'; ?>
            </div>
            
            <div class="warning-box">
                ⚠️ <strong>Important:</strong> Make sure MySQL is running before clicking the button below.
            </div>
            
            <form method="POST">
                <button type="submit" name="setup" class="btn">🚀 Run Database Setup</button>
            </form>
            
        <?php else: ?>
            <h2>Setup Results</h2>
            <?php foreach ($status as $item): ?>
                <div class="status <?php echo htmlspecialchars($item['type']); ?>">
                    <?php echo htmlspecialchars($item['message']); ?>
                </div>
            <?php endforeach; ?>
            
            <?php if ($error === null): ?>
                <div style="margin-top: 30px; text-align: center;">
                    <a href="<?php echo htmlspecialchars(SITE_URL); ?>" class="btn success">✅ Go to Website</a>
                    <a href="check-db.php" class="btn">🔍 Check Database Status</a>
                </div>
                
                <div class="info-box" style="margin-top: 30px;">
                    <strong>Next Steps:</strong><br>
                    1. Visit the <a href="<?php echo htmlspecialchars(SITE_URL); ?>">homepage</a> to see your site<br>
                    2. Login to <a href="<?php echo htmlspecialchars(ADMIN_URL); ?>">admin panel</a> (username: admin, password: admin123)<br>
                    3. Change the default admin password immediately!
                </div>
            <?php else: ?>
                <div style="margin-top: 20px;">
                    <a href="setup-db.php" class="btn">🔄 Try Again</a>
                    <a href="check-db.php" class="btn">🔍 Check Connection</a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>
