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
$token = $_GET['token'] ?? '';
$validToken = false;
$customer = null;

// Verify token
if (!empty($token)) {
    $stmt = $conn->prepare("SELECT id, first_name, email FROM customers WHERE email_verification_token = ? AND password_reset_expires > NOW() AND is_active = 1");
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $result = $stmt->get_result();
    $customer = $result->fetch_assoc();
    
    if ($customer) {
        $validToken = true;
    } else {
        $error = 'Invalid or expired reset link. Please request a new one.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($password)) {
        $error = 'Please enter a new password';
    } elseif (strlen($password) < MIN_PASSWORD_LENGTH) {
        $error = 'Password must be at least ' . MIN_PASSWORD_LENGTH . ' characters long';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match';
    } else {
        // Validate password strength
        $passwordErrors = Security::validatePassword($password);
        if (!empty($passwordErrors)) {
            $error = implode('<br>', $passwordErrors);
        } else {
            // Hash and update password
            $hashedPassword = Security::hashPassword($password);
            
            $stmt = $conn->prepare("UPDATE customers SET password = ?, email_verification_token = NULL, password_reset_expires = NULL WHERE id = ?");
            $stmt->bind_param('si', $hashedPassword, $customer['id']);
            
            if ($stmt->execute()) {
                $success = 'Your password has been reset successfully. You can now login with your new password.';
                
                // Log the user in automatically
                $_SESSION['customer_id'] = $customer['id'];
                $_SESSION['customer_email'] = $customer['email'];
                
                // Redirect after 3 seconds
                header('refresh:3;url=customer-account.php');
            } else {
                $error = 'Failed to reset password. Please try again.';
            }
        }
    }
}

$page_title = 'Reset Password | ' . SITE_NAME;
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

        .password-requirements {
            font-size: 0.875rem;
            color: #64748b;
            margin-top: 0.5rem;
        }

        .password-requirements ul {
            margin: 0.5rem 0 0 0;
            padding-left: 1.5rem;
        }

        .password-requirements li {
            margin: 0.25rem 0;
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
            <?php if (!$validToken && empty($success)): ?>
                <!-- Invalid Token -->
                <div class="auth-header">
                    <div class="auth-icon">
                        <svg fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
                        </svg>
                    </div>
                    <h1>Invalid Reset Link</h1>
                    <p>This link has expired or is invalid</p>
                </div>
                
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                
                <div class="auth-links">
                    <a href="customer-forgot-password.php">Request a new reset link</a>
                </div>
            <?php elseif ($success): ?>
                <!-- Success Message -->
                <div class="auth-header">
                    <div class="auth-icon" style="background: #d1fae5;">
                        <svg fill="currentColor" viewBox="0 0 24 24" style="color: #065f46;">
                            <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/>
                        </svg>
                    </div>
                    <h1>Password Reset!</h1>
                    <p>Redirecting to your account...</p>
                </div>
                
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                
                <div style="text-align: center; margin-top: 2rem;">
                    <p style="color: #64748b;">You will be redirected automatically.</p>
                    <p style="margin-top: 1rem;">
                        <a href="customer-account.php" style="color: #ff5900; text-decoration: none; font-weight: 600;">Go to My Account →</a>
                    </p>
                </div>
            <?php else: ?>
                <!-- Reset Password Form -->
                <div class="auth-header">
                    <div class="auth-icon">
                        <svg fill="currentColor" viewBox="0 0 24 24">
                            <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zM12 17c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/>
                        </svg>
                    </div>
                    <h1>Reset Password</h1>
                    <p>Enter your new password for <?php echo htmlspecialchars($customer['email']); ?></p>
                </div>
                
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="password">New Password</label>
                        <input type="password" id="password" name="password" class="form-control" required 
                               minlength="<?php echo MIN_PASSWORD_LENGTH; ?>">
                        <div class="password-requirements">
                            Password must contain:
                            <ul>
                                <li>At least <?php echo MIN_PASSWORD_LENGTH; ?> characters</li>
                                <?php if (REQUIRE_PASSWORD_UPPERCASE): ?>
                                <li>One uppercase letter</li>
                                <?php endif; ?>
                                <?php if (REQUIRE_PASSWORD_LOWERCASE): ?>
                                <li>One lowercase letter</li>
                                <?php endif; ?>
                                <?php if (REQUIRE_PASSWORD_NUMBERS): ?>
                                <li>One number</li>
                                <?php endif; ?>
                                <?php if (REQUIRE_PASSWORD_SPECIAL): ?>
                                <li>One special character</li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                    </div>
                    
                    <button type="submit" class="btn-primary">Reset Password</button>
                </form>
                
                <div class="auth-links">
                    <a href="customer-login.php">Back to Login</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>

