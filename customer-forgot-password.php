<?php
define('JINKA_ACCESS', true);
require_once 'includes/config.php';
require_once 'includes/CustomerAuth.php';

$auth = new CustomerAuth($conn);

// Redirect if already logged in
if ($auth->isLoggedIn()) {
    header('Location: customer-account.php');
    exit;
}

$error = '';
$success = '';
$step = 'email'; // email or reset

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['action'] === 'send_reset_link') {
        $email = Security::sanitizeInput($_POST['email'], 'email');
        
        if (empty($email)) {
            $error = 'Please enter your email address';
        } else {
            // Check if email exists
            $stmt = $conn->prepare("SELECT id, first_name, email FROM customers WHERE email = ? AND is_active = 1");
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $customer = $result->fetch_assoc();
            
            if ($customer) {
                // Generate reset token
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                // Store token in database
                $stmt = $conn->prepare("UPDATE customers SET email_verification_token = ?, password_reset_expires = ? WHERE id = ?");
                $stmt->bind_param('ssi', $token, $expires, $customer['id']);
                $stmt->execute();
                
                // Create reset link
                $resetLink = SITE_URL . '/customer-reset-password.php?token=' . $token;
                
                // Send email
                $emailSubject = 'Password Reset Request - ' . SITE_NAME;
                $emailBody = '
                <!DOCTYPE html>
                <html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { background: linear-gradient(135deg, #ff5900 0%, #e64f00 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                        .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px; }
                        .button { display: inline-block; padding: 15px 30px; background: #ff5900; color: white; text-decoration: none; border-radius: 8px; margin: 20px 0; }
                        .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
                    </style>
                </head>
                <body>
                    <div class="container">
                        <div class="header">
                            <h1>Password Reset Request</h1>
                        </div>
                        <div class="content">
                            <p>Hello ' . htmlspecialchars($customer['first_name']) . ',</p>
                            
                            <p>We received a request to reset your password. Click the button below to create a new password:</p>
                            
                            <p style="text-align: center;">
                                <a href="' . $resetLink . '" class="button">Reset Password</a>
                            </p>
                            
                            <p>Or copy and paste this link into your browser:</p>
                            <p style="word-break: break-all; background: #e9ecef; padding: 10px; border-radius: 5px;">
                                ' . $resetLink . '
                            </p>
                            
                            <p><strong>This link will expire in 1 hour.</strong></p>
                            
                            <p>If you did not request a password reset, please ignore this email. Your password will remain unchanged.</p>
                        </div>
                        <div class="footer">
                            <p>&copy; ' . date('Y') . ' ' . SITE_NAME . '. All rights reserved.</p>
                        </div>
                    </div>
                </body>
                </html>
                ';
                
                if (sendEmail($email, $emailSubject, $emailBody, true)) {
                    $success = 'Password reset instructions have been sent to your email.';
                    $step = 'sent';
                } else {
                    $error = 'Failed to send email. Please try again later.';
                }
            } else {
                // Don't reveal if email exists for security
                $success = 'If an account exists with that email, you will receive password reset instructions.';
                $step = 'sent';
            }
        }
    }
}

$page_title = 'Forgot Password | ' . SITE_NAME;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/header-modern.css">
    
    <style>
        .auth-container {
            min-height: calc(100vh - 200px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem 1rem;
            background: linear-gradient(135deg, #ff5900 0%, #e64f00 100%);
        }
        
        .auth-card {
            background: white;
            border-radius: 24px;
            box-shadow: 0 25px 70px rgba(0, 0, 0, 0.35);
            max-width: 500px;
            width: 100%;
            padding: 3rem;
            animation: slideUp 0.5s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .auth-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .auth-icon {
            width: 70px;
            height: 70px;
            margin: 0 auto 1.5rem;
            background: linear-gradient(135deg, #ff5900, #e64f00);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 24px rgba(102, 126, 234, 0.3);
        }

        .auth-icon svg {
            width: 35px;
            height: 35px;
            color: white;
        }
        
        .auth-header h1 {
            font-size: 2rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 0.75rem;
        }
        
        .auth-header p {
            color: #64748b;
            font-size: 1rem;
        }
        
        .form-group {
            margin-bottom: 1.75rem;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            color: #334155;
            margin-bottom: 0.625rem;
            font-size: 0.9375rem;
        }
        
        .form-control {
            width: 100%;
            padding: 1rem 1.25rem;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-family: inherit;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #ff5900;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
            transform: translateY(-2px);
        }
        
        .btn-primary {
            width: 100%;
            padding: 1.125rem 2rem;
            background: linear-gradient(135deg, #ff5900 0%, #e64f00 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1.0625rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            letter-spacing: 0.3px;
        }
        
        .btn-primary:hover:not(:disabled) {
            transform: translateY(-3px);
            box-shadow: 0 12px 28px rgba(102, 126, 234, 0.4);
        }

        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .alert {
            padding: 1.125rem 1.25rem;
            border-radius: 12px;
            margin-bottom: 1.75rem;
            font-size: 0.9375rem;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        
        .auth-links {
            text-align: center;
            margin-top: 2rem;
            padding-top: 1.75rem;
            border-top: 1px solid #e2e8f0;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        
        .auth-links a {
            color: #ff5900;
            text-decoration: none;
            font-size: 0.9375rem;
            font-weight: 600;
        }
        
        .auth-links a:hover {
            text-decoration: underline;
        }

        .success-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 1.5rem;
            background: #d1fae5;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .success-icon svg {
            width: 45px;
            height: 45px;
            color: #065f46;
        }

        @media (max-width: 640px) {
            .auth-card {
                padding: 2rem 1.5rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="auth-container">
        <div class="auth-card">
            <?php if ($step === 'sent'): ?>
                <!-- Success Message -->
                <div class="auth-header">
                    <div class="success-icon">
                        <svg fill="currentColor" viewBox="0 0 24 24">
                            <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/>
                        </svg>
                    </div>
                    <h1>Check Your Email</h1>
                    <p>We've sent you password reset instructions</p>
                </div>
                
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success); ?>
                </div>
                
                <div style="text-align: center; color: #64748b; margin: 2rem 0;">
                    <p>Check your inbox and click the reset link.</p>
                    <p style="margin-top: 1rem;">
                        <strong>Don't see the email?</strong><br>
                        Check your spam folder
                    </p>
                </div>
                
                <div class="auth-links">
                    <a href="customer-login.php">Back to Login</a>
                    <a href="customer-forgot-password.php">Try another email</a>
                </div>
            <?php else: ?>
                <!-- Email Input Form -->
                <div class="auth-header">
                    <div class="auth-icon">
                        <svg fill="currentColor" viewBox="0 0 24 24">
                            <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zM9 6c0-1.66 1.34-3 3-3s3 1.34 3 3v2H9V6zm9 14H6V10h12v10zm-6-3c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2z"/>
                        </svg>
                    </div>
                    <h1>Forgot Password?</h1>
                    <p>Enter your email and we'll send you reset instructions</p>
                </div>
                
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <input type="hidden" name="action" value="send_reset_link">
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" class="form-control" required 
                               placeholder="your@email.com" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                    
                    <button type="submit" class="btn-primary">Send Reset Link</button>
                </form>
                
                <div class="auth-links">
                    <a href="customer-login.php">Back to Login</a>
                    <a href="customer-register-verified.php">Don't have an account? Sign up</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>

