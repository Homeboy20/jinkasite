#!/usr/bin/env php
<?php
/**
 * Production Preparation Script
 * Run this before deploying to production
 */

echo "========================================\n";
echo "JINKA Plotter - Production Preparation\n";
echo "========================================\n\n";

$projectRoot = __DIR__;
$errors = [];
$warnings = [];
$success = [];

// Check if running from command line
if (php_sapi_name() !== 'cli') {
    die("This script must be run from command line\n");
}

// 1. Check for test/debug files
echo "[1/8] Checking for test/debug files...\n";
$testFiles = glob($projectRoot . '/test*.php');
$debugFiles = glob($projectRoot . '/debug*.php');
$setupFiles = glob($projectRoot . '/setup*.php');
$allTestFiles = array_merge($testFiles, $debugFiles, $setupFiles, [
    $projectRoot . '/phpinfo.php',
    $projectRoot . '/database/setup.php'
]);

foreach ($allTestFiles as $file) {
    if (file_exists($file)) {
        $warnings[] = "Found test/debug file: " . basename($file);
    }
}

if (empty($warnings)) {
    $success[] = "No test/debug files found";
}

// 2. Check config.php
echo "[2/8] Checking configuration...\n";
$configFile = $projectRoot . '/includes/config.php';
if (file_exists($configFile)) {
    $configContent = file_get_contents($configFile);
    
    // Check environment
    if (strpos($configContent, "define('ENVIRONMENT', 'development')") !== false) {
        $errors[] = "ENVIRONMENT is still set to 'development' in config.php";
    } else {
        $success[] = "ENVIRONMENT checked";
    }
    
    // Check debug mode
    if (strpos($configContent, "define('DEBUG_MODE', true)") !== false) {
        $errors[] = "DEBUG_MODE is still 'true' in config.php";
    } else {
        $success[] = "DEBUG_MODE checked";
    }
    
    // Check default keys
    if (strpos($configContent, 'jinka_2025_secure_key_change_in_production') !== false) {
        $errors[] = "SECRET_KEY is still using default value - MUST CHANGE!";
    }
    
    if (strpos($configContent, 'jinka_encryption_key_32_characters_min') !== false) {
        $errors[] = "ENCRYPTION_KEY is still using default value - MUST CHANGE!";
    }
    
    // Check site URL
    if (strpos($configContent, 'localhost/jinkaplotterwebsite') !== false) {
        $warnings[] = "SITE_URL still contains 'localhost' - update for production";
    }
    
    // Check payment sandbox
    if (strpos($configContent, "define('PAYMENT_USE_SANDBOX', true)") !== false) {
        $warnings[] = "PAYMENT_USE_SANDBOX is still 'true' - should be false for production";
    }
} else {
    $errors[] = "Config file not found: $configFile";
}

// 3. Check directory permissions
echo "[3/8] Checking directories...\n";
$dirsToCheck = [
    'uploads' => 0755,
    'cache' => 0755,
    'logs' => 0755
];

foreach ($dirsToCheck as $dir => $expectedPerms) {
    $dirPath = $projectRoot . '/' . $dir;
    if (!is_dir($dirPath)) {
        mkdir($dirPath, $expectedPerms, true);
        $success[] = "Created directory: $dir";
    }
    
    if (is_writable($dirPath)) {
        $success[] = "Directory '$dir' is writable";
    } else {
        $errors[] = "Directory '$dir' is not writable";
    }
}

// 4. Check for .env file
echo "[4/8] Checking environment files...\n";
if (file_exists($projectRoot . '/.env')) {
    $warnings[] = ".env file exists - ensure it's not uploaded to production";
}

if (!file_exists($projectRoot . '/.env.example')) {
    $warnings[] = ".env.example not found";
} else {
    $success[] = ".env.example exists";
}

// 5. Check .htaccess
echo "[5/8] Checking .htaccess...\n";
if (file_exists($projectRoot . '/.htaccess.production')) {
    $success[] = ".htaccess.production found";
    if (!file_exists($projectRoot . '/.htaccess')) {
        $warnings[] = ".htaccess not found - copy from .htaccess.production";
    }
} else {
    $warnings[] = ".htaccess.production not found";
}

// 6. Check robots.txt
echo "[6/8] Checking robots.txt...\n";
if (file_exists($projectRoot . '/robots.txt')) {
    $robotsContent = file_get_contents($projectRoot . '/robots.txt');
    if (strpos($robotsContent, 'yourdomain.com') !== false) {
        $warnings[] = "robots.txt still contains 'yourdomain.com' - update with actual domain";
    } else {
        $success[] = "robots.txt exists";
    }
} else {
    $warnings[] = "robots.txt not found";
}

// 7. Check Firebase configuration
echo "[7/8] Checking Firebase setup...\n";
if (file_exists($projectRoot . '/includes/firebase-config.php')) {
    $success[] = "Firebase config file exists";
} else {
    $warnings[] = "Firebase config file not found (optional if not using SMS OTP)";
}

// 8. Generate production checklist summary
echo "[8/8] Generating summary...\n\n";

// Display results
echo "========================================\n";
echo "PREPARATION SUMMARY\n";
echo "========================================\n\n";

if (!empty($errors)) {
    echo "❌ ERRORS (MUST FIX BEFORE DEPLOYMENT):\n";
    foreach ($errors as $error) {
        echo "   - $error\n";
    }
    echo "\n";
}

if (!empty($warnings)) {
    echo "⚠️  WARNINGS (RECOMMENDED TO FIX):\n";
    foreach ($warnings as $warning) {
        echo "   - $warning\n";
    }
    echo "\n";
}

if (!empty($success)) {
    echo "✅ SUCCESS:\n";
    foreach ($success as $item) {
        echo "   - $item\n";
    }
    echo "\n";
}

// Final recommendations
echo "========================================\n";
echo "NEXT STEPS:\n";
echo "========================================\n\n";

echo "1. Fix all ERRORS listed above\n";
echo "2. Review and fix WARNINGS\n";
echo "3. Update includes/config.php with production values:\n";
echo "   - Set ENVIRONMENT to 'production'\n";
echo "   - Set DEBUG_MODE to false\n";
echo "   - Change SECRET_KEY and ENCRYPTION_KEY\n";
echo "   - Update SITE_URL to production domain\n";
echo "   - Update database credentials\n";
echo "   - Set PAYMENT_USE_SANDBOX to false\n";
echo "\n";
echo "4. Export database:\n";
echo "   mysqldump -u root -p jinkaplotterwebsite > jinkaplotterwebsite.sql\n";
echo "\n";
echo "5. Create production archive:\n";
echo "   - ZIP entire project\n";
echo "   - Or use FTP/SFTP to upload files\n";
echo "\n";
echo "6. On production server:\n";
echo "   - Upload files to public_html/\n";
echo "   - Import database\n";
echo "   - Copy .htaccess.production to .htaccess\n";
echo "   - Set file permissions (chmod 755 folders, 644 files)\n";
echo "   - Test the site thoroughly\n";
echo "\n";
echo "7. Post-deployment:\n";
echo "   - Enable SSL certificate\n";
echo "   - Configure Firebase (add production domain)\n";
echo "   - Test payment gateways\n";
echo "   - Test email sending\n";
echo "   - Monitor error logs\n";
echo "\n";

// Summary status
if (!empty($errors)) {
    echo "❌ Status: NOT READY FOR PRODUCTION\n";
    echo "   Fix errors before deploying.\n\n";
    exit(1);
} elseif (!empty($warnings)) {
    echo "⚠️  Status: CAUTION - Review warnings\n";
    echo "   Site can be deployed but review warnings first.\n\n";
    exit(0);
} else {
    echo "✅ Status: READY FOR PRODUCTION\n";
    echo "   All checks passed. Follow next steps to deploy.\n\n";
    exit(0);
}
