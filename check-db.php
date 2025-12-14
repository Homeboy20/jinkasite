<?php
/**
 * Database Connection Checker
 * Run this file to verify database connection and setup
 */

// Load environment configuration
define('JINKA_ACCESS', true);
require_once __DIR__ . '/includes/config.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Connection Check</title>
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
            margin: 15px 0;
            border-radius: 4px;
            font-weight: 500;
        }
        .success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
        }
        .info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
        }
        .details {
            background: #f8f9fa;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
            font-family: monospace;
            font-size: 14px;
        }
        .details dt {
            font-weight: bold;
            margin-top: 10px;
        }
        .details dd {
            margin-left: 0;
            margin-bottom: 5px;
        }
        .action {
            background: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin-top: 10px;
        }
        .action:hover {
            background: #0056b3;
        }
        code {
            background: #f8f9fa;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }
        pre {
            background: #282c34;
            color: #abb2bf;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Database Connection Check</h1>
        
        <?php
        $checks = [];
        
        // Check 1: Test MySQL connection
        echo '<h2>1. MySQL Server Connection</h2>';
        try {
            $test_conn = @new mysqli(DB_HOST, DB_USER, DB_PASS);
            
            if ($test_conn->connect_error) {
                echo '<div class="status error">❌ Cannot connect to MySQL server</div>';
                echo '<div class="details">';
                echo '<strong>Error:</strong> ' . htmlspecialchars($test_conn->connect_error) . '<br>';
                echo '<strong>Error Number:</strong> ' . $test_conn->connect_errno;
                echo '</div>';
                
                echo '<div class="status warning">';
                echo '<strong>Common Fixes:</strong><br>';
                echo '1. Make sure WAMP/MySQL is running (check the WAMP icon in system tray)<br>';
                echo '2. Verify credentials in .env file<br>';
                echo '3. For WAMP default: user=<code>root</code>, password=<code>(empty)</code>';
                echo '</div>';
                $checks['mysql'] = false;
            } else {
                echo '<div class="status success">✅ MySQL server connected successfully</div>';
                echo '<div class="details">';
                echo '<strong>MySQL Version:</strong> ' . htmlspecialchars($test_conn->server_info);
                echo '</div>';
                $checks['mysql'] = true;
                
                // Check 2: Database existence
                echo '<h2>2. Database Existence</h2>';
                $db_check = $test_conn->select_db(DB_NAME);
                
                if (!$db_check) {
                    echo '<div class="status error">❌ Database "' . htmlspecialchars(DB_NAME) . '" does not exist</div>';
                    echo '<div class="status warning">';
                    echo '<strong>Action Required:</strong><br>';
                    echo 'Run this SQL command in phpMyAdmin or MySQL command line:';
                    echo '</div>';
                    echo '<pre>CREATE DATABASE ' . htmlspecialchars(DB_NAME) . ' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;</pre>';
                    echo '<p><a href="http://localhost/phpmyadmin" class="action" target="_blank">Open phpMyAdmin</a></p>';
                    $checks['database'] = false;
                } else {
                    echo '<div class="status success">✅ Database "' . htmlspecialchars(DB_NAME) . '" exists</div>';
                    $checks['database'] = true;
                    
                    // Check 3: Tables existence
                    echo '<h2>3. Database Tables</h2>';
                    $tables_query = "SHOW TABLES";
                    $tables_result = $test_conn->query($tables_query);
                    
                    if ($tables_result) {
                        $table_count = $tables_result->num_rows;
                        if ($table_count > 0) {
                            echo '<div class="status success">✅ Found ' . $table_count . ' tables</div>';
                            echo '<div class="details">';
                            echo '<strong>Tables:</strong><br>';
                            while ($row = $tables_result->fetch_array()) {
                                echo '• ' . htmlspecialchars($row[0]) . '<br>';
                            }
                            echo '</div>';
                            $checks['tables'] = true;
                        } else {
                            echo '<div class="status warning">⚠️ Database exists but no tables found</div>';
                            echo '<div class="status info">';
                            echo '<strong>Action Required:</strong><br>';
                            echo 'Import the database schema from the SQL file in the database/ folder.';
                            echo '</div>';
                            $checks['tables'] = false;
                        }
                    }
                }
                
                $test_conn->close();
            }
        } catch (Exception $e) {
            echo '<div class="status error">❌ Connection test failed: ' . htmlspecialchars($e->getMessage()) . '</div>';
            $checks['mysql'] = false;
        }
        
        // Check 4: Configuration
        echo '<h2>4. Configuration Details</h2>';
        echo '<div class="details">';
        echo '<dl>';
        echo '<dt>Environment:</dt><dd>' . htmlspecialchars(ENVIRONMENT) . '</dd>';
        echo '<dt>Debug Mode:</dt><dd>' . (DEBUG_MODE ? 'Enabled' : 'Disabled') . '</dd>';
        echo '<dt>Database Host:</dt><dd>' . htmlspecialchars(DB_HOST) . '</dd>';
        echo '<dt>Database Name:</dt><dd>' . htmlspecialchars(DB_NAME) . '</dd>';
        echo '<dt>Database User:</dt><dd>' . htmlspecialchars(DB_USER) . '</dd>';
        echo '<dt>Database Password:</dt><dd>' . (DB_PASS === '' ? '(empty)' : '••••••••') . '</dd>';
        echo '<dt>Site URL:</dt><dd>' . htmlspecialchars(SITE_URL) . '</dd>';
        echo '</dl>';
        echo '</div>';
        
        // Summary
        echo '<h2>Summary</h2>';
        $all_good = !in_array(false, $checks, true);
        
        if ($all_good && isset($checks['tables']) && $checks['tables']) {
            echo '<div class="status success">';
            echo '✅ <strong>All checks passed!</strong> Your database is ready to use.';
            echo '</div>';
            echo '<p><a href="' . htmlspecialchars(SITE_URL) . '" class="action">Go to Website</a></p>';
        } elseif (isset($checks['mysql']) && $checks['mysql'] && isset($checks['database']) && !$checks['database']) {
            echo '<div class="status warning">';
            echo '⚠️ <strong>Database needs to be created.</strong> Follow the instructions above.';
            echo '</div>';
        } elseif (isset($checks['mysql']) && $checks['mysql'] && isset($checks['database']) && $checks['database'] && (!isset($checks['tables']) || !$checks['tables'])) {
            echo '<div class="status warning">';
            echo '⚠️ <strong>Database tables need to be imported.</strong> Import the SQL schema file.';
            echo '</div>';
            echo '<p>Look for SQL files in the <code>database/</code> folder and import them via phpMyAdmin.</p>';
        } else {
            echo '<div class="status error">';
            echo '❌ <strong>Database connection issues detected.</strong> Follow the fixes above.';
            echo '</div>';
        }
        ?>
        
        <hr style="margin: 30px 0; border: none; border-top: 1px solid #ddd;">
        <p style="color: #666; font-size: 14px;">
            <strong>Tip:</strong> After fixing issues, refresh this page to re-check the connection.
        </p>
    </div>
</body>
</html>
