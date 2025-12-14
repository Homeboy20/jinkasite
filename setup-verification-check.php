<?php
/**
 * Quick Setup Script for SMS OTP & Verification System
 * 
 * This script helps verify your setup is correct
 * Run this file in your browser: http://localhost/jinkaplotterwebsite/setup-verification-check.php
 */

define('JINKA_ACCESS', true);
require_once 'includes/config.php';

$checks = [];
$allPassed = true;

// Check 1: Firebase Config
$checks['firebase'] = [
    'name' => 'Firebase Configuration',
    'status' => false,
    'message' => ''
];

if (file_exists('includes/firebase-config.php')) {
    $firebase_config = require 'includes/firebase-config.php';
    if ($firebase_config['apiKey'] !== 'YOUR_FIREBASE_API_KEY') {
        $checks['firebase']['status'] = true;
        $checks['firebase']['message'] = 'Firebase config found and configured';
    } else {
        $checks['firebase']['message'] = 'Firebase config exists but not configured. Update includes/firebase-config.php';
    }
} else {
    $checks['firebase']['message'] = 'Firebase config file not found';
}

// Check 2: Database Structure
$checks['database'] = [
    'name' => 'Database Structure',
    'status' => false,
    'message' => ''
];

try {
    $stmt = $conn->query("DESCRIBE customers");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $hasPhoneVerified = false;
    $hasEmailVerified = false;
    
    foreach ($columns as $column) {
        if ($column['Field'] === 'phone_verified') $hasPhoneVerified = true;
        if ($column['Field'] === 'email_verified') $hasEmailVerified = true;
    }
    
    if ($hasPhoneVerified && $hasEmailVerified) {
        $checks['database']['status'] = true;
        $checks['database']['message'] = 'Database has all required columns';
    } else {
        $missing = [];
        if (!$hasPhoneVerified) $missing[] = 'phone_verified';
        if (!$hasEmailVerified) $missing[] = 'email_verified';
        $checks['database']['message'] = 'Missing columns: ' . implode(', ', $missing) . '. Run database/add-phone-verification.sql';
    }
} catch (PDOException $e) {
    $checks['database']['message'] = 'Database connection error: ' . $e->getMessage();
}

// Check 3: Required Files
$checks['files'] = [
    'name' => 'Required Files',
    'status' => false,
    'message' => ''
];

$requiredFiles = [
    'customer-register-verified.php',
    'customer-login-otp.php',
    'css/header-modern.css',
    'includes/CustomerAuth.php'
];

$missingFiles = [];
foreach ($requiredFiles as $file) {
    if (!file_exists($file)) {
        $missingFiles[] = $file;
    }
}

if (empty($missingFiles)) {
    $checks['files']['status'] = true;
    $checks['files']['message'] = 'All required files present';
} else {
    $checks['files']['message'] = 'Missing files: ' . implode(', ', $missingFiles);
}

// Check 4: Header CSS
$checks['css'] = [
    'name' => 'Header CSS',
    'status' => false,
    'message' => ''
];

if (file_exists('css/header-modern.css')) {
    $cssContent = file_get_contents('css/header-modern.css');
    if (strpos($cssContent, 'glassmorphism') !== false || strpos($cssContent, 'backdrop-filter') !== false) {
        $checks['css']['status'] = true;
        $checks['css']['message'] = 'Header CSS has modern enhancements';
    } else {
        $checks['css']['message'] = 'Header CSS exists but may need updates';
    }
} else {
    $checks['css']['message'] = 'Header CSS file not found';
}

foreach ($checks as $check) {
    if (!$check['status']) {
        $allPassed = false;
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMS OTP Setup Verification</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .container {
            background: white;
            border-radius: 24px;
            box-shadow: 0 25px 70px rgba(0, 0, 0, 0.35);
            max-width: 800px;
            width: 100%;
            padding: 3rem;
        }

        h1 {
            font-size: 2rem;
            color: #1e293b;
            margin-bottom: 1rem;
            text-align: center;
        }

        .subtitle {
            text-align: center;
            color: #64748b;
            margin-bottom: 3rem;
            font-size: 1rem;
        }

        .status-badge {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            margin-bottom: 2rem;
            text-align: center;
            width: 100%;
            font-size: 1.125rem;
        }

        .status-badge.success {
            background: #d1fae5;
            color: #065f46;
            border: 2px solid #a7f3d0;
        }

        .status-badge.error {
            background: #fee2e2;
            color: #991b1b;
            border: 2px solid #fecaca;
        }

        .check-item {
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: all 0.3s;
        }

        .check-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }

        .check-item.pass {
            border-color: #10b981;
            background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
        }

        .check-item.fail {
            border-color: #ef4444;
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
        }

        .check-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 0.75rem;
        }

        .check-icon {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .check-icon.pass {
            background: #10b981;
            color: white;
        }

        .check-icon.fail {
            background: #ef4444;
            color: white;
        }

        .check-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: #1e293b;
        }

        .check-message {
            color: #64748b;
            margin-left: 3rem;
            line-height: 1.6;
        }

        .next-steps {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 16px;
            padding: 2rem;
            margin-top: 2rem;
        }

        .next-steps h2 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .next-steps ul {
            list-style: none;
            padding-left: 0;
        }

        .next-steps li {
            padding: 0.5rem 0;
            padding-left: 1.75rem;
            position: relative;
        }

        .next-steps li::before {
            content: '→';
            position: absolute;
            left: 0;
            font-weight: bold;
        }

        .btn {
            display: inline-block;
            padding: 1rem 2rem;
            background: white;
            color: #667eea;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 600;
            margin-top: 1.5rem;
            transition: all 0.3s;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(255, 255, 255, 0.3);
        }

        .link-section {
            background: #f8fafc;
            border-radius: 16px;
            padding: 2rem;
            margin-top: 2rem;
        }

        .link-section h3 {
            color: #1e293b;
            margin-bottom: 1rem;
            font-size: 1.25rem;
        }

        .link-list {
            display: grid;
            gap: 0.75rem;
        }

        .link-list a {
            display: block;
            padding: 0.875rem 1.25rem;
            background: white;
            color: #667eea;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 500;
            transition: all 0.3s;
            border: 2px solid #e2e8f0;
        }

        .link-list a:hover {
            border-color: #667eea;
            transform: translateX(5px);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 SMS OTP Setup Verification</h1>
        <p class="subtitle">Checking your installation and configuration</p>

        <?php if ($allPassed): ?>
            <div class="status-badge success">✅ All Checks Passed!</div>
        <?php else: ?>
            <div class="status-badge error">⚠️ Some Checks Failed</div>
        <?php endif; ?>

        <?php foreach ($checks as $key => $check): ?>
            <div class="check-item <?php echo $check['status'] ? 'pass' : 'fail'; ?>">
                <div class="check-header">
                    <div class="check-icon <?php echo $check['status'] ? 'pass' : 'fail'; ?>">
                        <?php echo $check['status'] ? '✓' : '✗'; ?>
                    </div>
                    <div class="check-title"><?php echo $check['name']; ?></div>
                </div>
                <div class="check-message"><?php echo $check['message']; ?></div>
            </div>
        <?php endforeach; ?>

        <?php if (!$allPassed): ?>
            <div class="next-steps">
                <h2>📋 Next Steps</h2>
                <ul>
                    <?php if (!$checks['firebase']['status']): ?>
                        <li>Update Firebase configuration in <code>includes/firebase-config.php</code></li>
                        <li>Get credentials from <a href="https://console.firebase.google.com/" target="_blank" style="color: white; text-decoration: underline;">Firebase Console</a></li>
                    <?php endif; ?>
                    
                    <?php if (!$checks['database']['status']): ?>
                        <li>Run the SQL migration: <code>database/add-phone-verification.sql</code></li>
                        <li>Execute in phpMyAdmin or MySQL command line</li>
                    <?php endif; ?>
                    
                    <?php if (!$checks['files']['status']): ?>
                        <li>Ensure all required files are uploaded to the server</li>
                    <?php endif; ?>
                    
                    <li>Read <code>SETUP-VERIFICATION.md</code> for detailed instructions</li>
                </ul>
                <a href="SETUP-VERIFICATION.md" class="btn" target="_blank">📖 View Setup Guide</a>
            </div>
        <?php else: ?>
            <div class="next-steps">
                <h2>🎉 Setup Complete!</h2>
                <ul>
                    <li>All checks passed successfully</li>
                    <li>Configure Firebase test phone numbers for development</li>
                    <li>Test the registration and OTP login flows</li>
                    <li>Review security settings before going live</li>
                </ul>
            </div>
        <?php endif; ?>

        <div class="link-section">
            <h3>🔗 Quick Access Links</h3>
            <div class="link-list">
                <a href="customer-register-verified.php">📝 Registration with Verification</a>
                <a href="customer-login-otp.php">📱 SMS OTP Login</a>
                <a href="customer-login.php">🔐 Standard Login (Email/Password)</a>
                <a href="index.php">🏠 Home Page</a>
                <a href="SETUP-VERIFICATION.md" target="_blank">📚 Setup Documentation</a>
            </div>
        </div>
    </div>
</body>
</html>
