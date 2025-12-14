<?php
/**
 * Email Test Script
 * Tests email functionality with current configuration
 */

define('JINKA_ACCESS', true);
require_once __DIR__ . '/includes/config.php';

// Test email address
$testEmail = 'wanjohimutuku@gmail.com';
$testSubject = 'Test Email from JINKA Plotter - ' . date('Y-m-d H:i:s');
$testMessage = '
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px; }
        .info-box { background: white; padding: 15px; margin: 15px 0; border-left: 4px solid #667eea; }
        .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎉 Email Test Successful!</h1>
            <p>JINKA Plotter Website</p>
        </div>
        <div class="content">
            <h2>Email Configuration Test</h2>
            <p>If you are reading this email, your email configuration is working correctly!</p>
            
            <div class="info-box">
                <strong>Test Details:</strong><br>
                Date: ' . date('F j, Y') . '<br>
                Time: ' . date('g:i A') . '<br>
                Server: ' . $_SERVER['SERVER_NAME'] . '<br>
                PHP Version: ' . PHP_VERSION . '
            </div>
            
            <div class="info-box">
                <strong>SMTP Configuration:</strong><br>
                Host: ' . SMTP_HOST . '<br>
                Port: ' . SMTP_PORT . '<br>
                From: ' . FROM_EMAIL . '
            </div>
            
            <p><strong>What to test next:</strong></p>
            <ul>
                <li>✅ Basic email sending (this test)</li>
                <li>📧 Contact form submission</li>
                <li>🛒 Order confirmation emails</li>
                <li>👤 Customer registration emails</li>
                <li>🔐 Password reset emails</li>
            </ul>
            
            <p style="color: #28a745; font-weight: bold;">
                ✅ Your email system is ready for production!
            </p>
        </div>
        <div class="footer">
            <p>&copy; 2025 JINKA Plotter. All rights reserved.</p>
            <p>This is an automated test email.</p>
        </div>
    </div>
</body>
</html>
';

echo "========================================\n";
echo "Email Test Script\n";
echo "========================================\n\n";

echo "Configuration:\n";
echo "  SMTP Host: " . SMTP_HOST . "\n";
echo "  SMTP Port: " . SMTP_PORT . "\n";
echo "  From Email: " . FROM_EMAIL . "\n";
echo "  From Name: " . FROM_NAME . "\n";
echo "  Test Recipient: " . $testEmail . "\n\n";

echo "Attempting to send test email...\n\n";

// Method 1: Using built-in mail() function
echo "[Method 1] Using PHP mail() function:\n";

$headers = [
    'From: ' . FROM_NAME . ' <' . FROM_EMAIL . '>',
    'Reply-To: ' . FROM_EMAIL,
    'X-Mailer: PHP/' . phpversion(),
    'MIME-Version: 1.0',
    'Content-type: text/html; charset=UTF-8'
];

$result1 = mail($testEmail, $testSubject, $testMessage, implode("\r\n", $headers));

if ($result1) {
    echo "✅ Email sent successfully using mail() function!\n";
    echo "   Check inbox: $testEmail\n";
    echo "   (Check spam folder if not in inbox)\n\n";
} else {
    echo "❌ Failed to send email using mail() function\n";
    echo "   This might be because SMTP is not configured on localhost\n\n";
}

// Method 2: Check if PHPMailer is available
echo "[Method 2] Checking for PHPMailer:\n";

$phpMailerPath = __DIR__ . '/vendor/phpmailer/phpmailer/src/PHPMailer.php';
if (file_exists($phpMailerPath)) {
    echo "✅ PHPMailer is installed\n";
    
    require $phpMailerPath;
    require __DIR__ . '/vendor/phpmailer/phpmailer/src/SMTP.php';
    require __DIR__ . '/vendor/phpmailer/phpmailer/src/Exception.php';
    
    try {
        $mailer = new PHPMailer\PHPMailer\PHPMailer(true);
        
        // SMTP settings
        $mailer->isSMTP();
        $mailer->Host = SMTP_HOST;
        $mailer->SMTPAuth = true;
        $mailer->Username = SMTP_USERNAME;
        $mailer->Password = SMTP_PASSWORD;
        $mailer->SMTPSecure = SMTP_ENCRYPTION;
        $mailer->Port = SMTP_PORT;
        
        // Email settings
        $mailer->setFrom(FROM_EMAIL, FROM_NAME);
        $mailer->addAddress($testEmail);
        $mailer->Subject = $testSubject . ' (PHPMailer)';
        $mailer->Body = $testMessage;
        $mailer->isHTML(true);
        
        $mailer->send();
        echo "✅ Email sent successfully using PHPMailer!\n\n";
    } catch (Exception $e) {
        echo "❌ PHPMailer error: " . $e->getMessage() . "\n\n";
    }
} else {
    echo "⚠️  PHPMailer not installed\n";
    echo "   For production, consider installing PHPMailer:\n";
    echo "   composer require phpmailer/phpmailer\n\n";
}

// Method 3: Test sendEmail() helper function
echo "[Method 3] Using sendEmail() helper function:\n";

try {
    $result3 = sendEmail($testEmail, $testSubject . ' (Helper)', $testMessage, true);
    
    if ($result3) {
        echo "✅ Email sent successfully using sendEmail() helper!\n\n";
    } else {
        echo "❌ Failed to send email using sendEmail() helper\n\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n\n";
}

// Summary
echo "========================================\n";
echo "Test Summary\n";
echo "========================================\n\n";

if ($result1) {
    echo "✅ At least one email method succeeded!\n";
    echo "\nNext Steps:\n";
    echo "1. Check your inbox: $testEmail\n";
    echo "2. If not in inbox, check spam/junk folder\n";
    echo "3. Mark as 'Not Spam' if found in spam\n";
    echo "4. Test other email features:\n";
    echo "   - Contact form\n";
    echo "   - Customer registration\n";
    echo "   - Order confirmations\n\n";
    
    echo "For Production:\n";
    echo "- Configure proper SMTP server in includes/config.php\n";
    echo "- Use authenticated SMTP (not localhost)\n";
    echo "- Consider using services like:\n";
    echo "  * SendGrid (free tier: 100 emails/day)\n";
    echo "  * Mailgun (free tier: 5000 emails/month)\n";
    echo "  * Amazon SES (very cheap)\n";
    echo "  * Your hosting provider's SMTP\n\n";
} else {
    echo "❌ Email sending failed\n\n";
    
    echo "Troubleshooting:\n";
    echo "1. WAMP/Local Development:\n";
    echo "   - PHP mail() doesn't work without SMTP configuration\n";
    echo "   - Install and configure an SMTP server\n";
    echo "   - Or use PHPMailer with Gmail SMTP for testing\n\n";
    
    echo "2. For Testing with Gmail:\n";
    echo "   a. Update includes/config.php:\n";
    echo "      define('SMTP_HOST', 'smtp.gmail.com');\n";
    echo "      define('SMTP_PORT', 587);\n";
    echo "      define('SMTP_USERNAME', 'your-email@gmail.com');\n";
    echo "      define('SMTP_PASSWORD', 'your-app-password');\n";
    echo "      define('SMTP_ENCRYPTION', 'tls');\n\n";
    
    echo "   b. Generate Gmail App Password:\n";
    echo "      - Go to Google Account settings\n";
    echo "      - Security → 2-Step Verification\n";
    echo "      - App passwords → Generate\n";
    echo "      - Use the 16-character password\n\n";
    
    echo "   c. Install PHPMailer:\n";
    echo "      composer require phpmailer/phpmailer\n\n";
    
    echo "3. For Production:\n";
    echo "   - Use your hosting provider's SMTP\n";
    echo "   - Or use email service provider (SendGrid, Mailgun, etc.)\n";
    echo "   - Configure in DirectAdmin → Email Accounts\n\n";
}

echo "========================================\n\n";
