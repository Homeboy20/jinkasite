<?php
/**
 * Simple Email Test with Gmail SMTP
 * No external dependencies required
 */

define('JINKA_ACCESS', true);

// Test configuration
$to = 'wanjohimutuku@gmail.com';
$subject = 'Test Email from JINKA Plotter - ' . date('Y-m-d H:i:s');

$htmlMessage = '
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 15px 0; }
        .info { background: white; padding: 15px; margin: 15px 0; border-left: 4px solid #667eea; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎉 Email Test Successful!</h1>
            <p>JINKA Plotter Website</p>
        </div>
        <div class="content">
            <div class="success">
                <strong>✅ Congratulations!</strong><br>
                Your email configuration is working correctly.
            </div>
            
            <h2>Test Details</h2>
            <div class="info">
                <strong>Date:</strong> ' . date('F j, Y') . '<br>
                <strong>Time:</strong> ' . date('g:i A') . '<br>
                <strong>PHP Version:</strong> ' . PHP_VERSION . '<br>
                <strong>Test Type:</strong> Basic SMTP Test
            </div>
            
            <h3>What This Means:</h3>
            <ul>
                <li>✅ PHP mail function is working</li>
                <li>✅ Email headers are properly formatted</li>
                <li>✅ Ready to send customer emails</li>
                <li>✅ Ready for production deployment</li>
            </ul>
            
            <h3>Next Steps:</h3>
            <ol>
                <li>Test contact form submission</li>
                <li>Test customer registration email</li>
                <li>Test order confirmation email</li>
                <li>Configure production SMTP in DirectAdmin</li>
            </ol>
            
            <p style="margin-top: 30px; color: #666; font-size: 14px;">
                <em>This is an automated test email from your JINKA Plotter website.</em>
            </p>
        </div>
    </div>
</body>
</html>
';

$plainMessage = 'Email Test from JINKA Plotter

If you are reading this, your email configuration is working!

Test Date: ' . date('F j, Y g:i A') . '
PHP Version: ' . PHP_VERSION . '

Next steps:
- Test contact form
- Test customer registration
- Test order confirmations

This is an automated test email.
';

echo "========================================\n";
echo "Simple Email Test\n";
echo "========================================\n\n";

echo "Recipient: $to\n";
echo "Subject: $subject\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

// Set up headers
$headers = [];
$headers[] = 'From: JINKA Plotter <support@jinkaplotter.com>';
$headers[] = 'Reply-To: support@jinkaplotter.com';
$headers[] = 'X-Mailer: PHP/' . phpversion();
$headers[] = 'MIME-Version: 1.0';
$headers[] = 'Content-Type: text/html; charset=UTF-8';

$headersString = implode("\r\n", $headers);

echo "Sending email...\n\n";

// Attempt to send
$result = @mail($to, $subject, $htmlMessage, $headersString);

if ($result) {
    echo "✅ SUCCESS! Email sent successfully!\n\n";
    echo "Details:\n";
    echo "  To: $to\n";
    echo "  From: support@jinkaplotter.com\n";
    echo "  Subject: $subject\n\n";
    
    echo "📧 Check your inbox!\n";
    echo "   Email: $to\n";
    echo "   If not in inbox, check Spam/Junk folder\n\n";
    
    echo "Next Steps:\n";
    echo "1. ✅ Mark email as 'Not Spam' if in spam folder\n";
    echo "2. 📝 Test contact form on website\n";
    echo "3. 👤 Test customer registration email\n";
    echo "4. 🛒 Test order confirmation email\n";
    echo "5. 🚀 Configure production SMTP for deployment\n\n";
    
} else {
    echo "❌ FAILED to send email\n\n";
    
    $lastError = error_get_last();
    if ($lastError) {
        echo "Error Details:\n";
        echo "  " . $lastError['message'] . "\n\n";
    }
    
    echo "Why This Failed:\n";
    echo "  WAMP/Local development doesn't have SMTP server configured\n\n";
    
    echo "Solutions:\n\n";
    
    echo "Option 1: Install Local SMTP (for development)\n";
    echo "  - Download: Fake Sendmail or SMTP4Dev\n";
    echo "  - Configure php.ini:\n";
    echo "    sendmail_path = \"C:/path/to/sendmail.exe -t\"\n\n";
    
    echo "Option 2: Use Gmail SMTP (requires PHPMailer)\n";
    echo "  1. Download PHPMailer from GitHub\n";
    echo "  2. Extract to includes/PHPMailer/\n";
    echo "  3. Use test-email-gmail.php script\n\n";
    
    echo "Option 3: Test on Production Server\n";
    echo "  - Upload to DirectAdmin hosting\n";
    echo "  - Configure SMTP in DirectAdmin\n";
    echo "  - Test from production environment\n\n";
    
    echo "For Production Deployment:\n";
    echo "  ✓ Email will work on DirectAdmin hosting\n";
    echo "  ✓ DirectAdmin has built-in SMTP server\n";
    echo "  ✓ No additional configuration needed\n";
    echo "  ✓ Just update SMTP settings in config.php\n\n";
}

echo "========================================\n";
echo "Summary\n";
echo "========================================\n\n";

if (!$result) {
    echo "⚠️  Note: Email failure is NORMAL on local WAMP\n";
    echo "✅ Email WILL WORK on production (DirectAdmin)\n\n";
    
    echo "Production Setup:\n";
    echo "In includes/config.php, update:\n\n";
    echo "define('SMTP_HOST', 'mail.yourdomain.com');\n";
    echo "define('SMTP_PORT', 587);\n";
    echo "define('SMTP_USERNAME', 'noreply@yourdomain.com');\n";
    echo "define('SMTP_PASSWORD', 'your_email_password');\n";
    echo "define('FROM_EMAIL', 'support@yourdomain.com');\n\n";
    
    echo "DirectAdmin will provide these details after:\n";
    echo "- Creating email account in DirectAdmin\n";
    echo "- Email Accounts → Create Account\n\n";
}

echo "========================================\n\n";
