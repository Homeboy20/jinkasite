<?php
define('JINKA_ACCESS', true);
ob_start();
require_once 'includes/config.php';
require_once 'includes/CustomerAuth.php';

$auth = new CustomerAuth($conn);

// Redirect if already logged in
if ($auth->isLoggedIn()) {
    header('Location: customer-account.php');
    exit;
}

// Load Firebase config
$firebase_config = require_once 'includes/firebase-config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'login') {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);
        
        $result = $auth->login($email, $password, $remember);
        
        if ($result['success']) {
            header('Location: customer-account.php');
            exit;
        } else {
            $error = $result['message'];
        }
    } elseif ($_POST['action'] === 'otp_login') {
        // Handle OTP login (phone verified via Firebase)
        if (isset($_POST['firebase_uid']) && isset($_POST['phone_number'])) {
            $stmt = $conn->prepare("SELECT * FROM customers WHERE phone = ? AND firebase_uid = ?");
            $stmt->execute([$_POST['phone_number'], $_POST['firebase_uid']]);
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($customer) {
                $_SESSION['customer_id'] = $customer['customer_id'];
                $_SESSION['customer_email'] = $customer['email'];
                
                // Update last login
                $stmt = $conn->prepare("UPDATE customers SET last_login = NOW() WHERE customer_id = ?");
                $stmt->execute([$customer['customer_id']]);
                
                header('Location: customer-account.php');
                exit;
            } else {
                $error = 'No account found with this phone number.';
            }
        }
    }
}

$page_title = 'Login | ' . $site_name;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/header-modern.css">
    
    <!-- Firebase SDK -->
    <script src="https://www.gstatic.com/firebasejs/9.22.0/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.22.0/firebase-auth-compat.js"></script>
    
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
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 500px;
            width: 100%;
            padding: 3rem;
        }
        
        .auth-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .auth-header h1 {
            font-size: 1.875rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 0.5rem;
        }
        
        .auth-header p {
            color: #64748b;
            font-size: 0.9375rem;
        }
        
        .auth-tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .auth-tab {
            flex: 1;
            padding: 0.875rem 1rem;
            text-align: center;
            font-weight: 600;
            color: #64748b;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            margin-bottom: -2px;
            transition: all 0.3s;
        }
        
        .auth-tab.active {
            color: #ff5900;
            border-bottom-color: #ff5900;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            color: #334155;
            margin-bottom: 0.5rem;
            font-size: 0.9375rem;
        }
        
        .form-control {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #ff5900;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .remember-me {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .remember-me input {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        
        .remember-me label {
            font-size: 0.875rem;
            color: #64748b;
            cursor: pointer;
            margin: 0;
        }
        
        .forgot-password {
            color: #ff5900;
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .forgot-password:hover {
            text-decoration: underline;
        }
        
        .btn-primary {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #ff5900 0%, #e64f00 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.3s;
        }
        
        .btn-primary:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }
        
        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .btn-secondary {
            width: 100%;
            padding: 1rem;
            background: white;
            color: #ff5900;
            border: 2px solid #ff5900;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            margin-top: 1rem;
        }
        
        .btn-secondary:hover {
            background: #ff5900;
            color: white;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
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
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e2e8f0;
        }
        
        .auth-links a {
            color: #ff5900;
            text-decoration: none;
            font-size: 0.9375rem;
            font-weight: 500;
        }
        
        .auth-links a:hover {
            text-decoration: underline;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .otp-input-group {
            display: flex;
            gap: 0.75rem;
            justify-content: center;
            margin: 1.5rem 0;
        }
        
        .otp-input {
            width: 50px;
            height: 50px;
            text-align: center;
            font-size: 1.5rem;
            font-weight: 700;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
        }
        
        .otp-input:focus {
            outline: none;
            border-color: #ff5900;
        }
        
        #recaptcha-container {
            margin: 1rem 0;
        }
        
        @media (max-width: 640px) {
            .auth-card {
                padding: 2rem 1.5rem;
            }
            
            .otp-input {
                width: 40px;
                height: 40px;
                font-size: 1.25rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1>Welcome Back</h1>
                <p>Login to your account to continue</p>
            </div>
            
            <!-- Login Tabs -->
            <div class="auth-tabs">
                <div class="auth-tab active" data-tab="email-login">
                    <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24" style="display: inline-block; vertical-align: middle; margin-right: 0.5rem;"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
                    Email Login
                </div>
                <div class="auth-tab" data-tab="otp-login">
                    <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24" style="display: inline-block; vertical-align: middle; margin-right: 0.5rem;"><path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/></svg>
                    OTP Login
                </div>
            </div>
            
            <div id="message-container"></div>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <!-- Email/Password Login -->
            <div class="tab-content active" id="email-login">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="login">
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" class="form-control" required placeholder="your@email.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" class="form-control" required placeholder="Enter your password">
                    </div>
                    
                    <div class="form-options">
                        <div class="remember-me">
                            <input type="checkbox" id="remember" name="remember">
                            <label for="remember">Remember me</label>
                        </div>
                        <a href="customer-forgot-password.php" class="forgot-password">Forgot Password?</a>
                    </div>
                    
                    <button type="submit" class="btn-primary">Login</button>
                </form>
            </div>
            
            <!-- OTP Login -->
            <div class="tab-content" id="otp-login">
                <form id="otpLoginForm">
                    <div class="form-group">
                        <label for="otp_phone">Phone Number (with country code)</label>
                        <input type="tel" id="otp_phone" class="form-control" required placeholder="+254712345678">
                        <div style="font-size: 0.875rem; color: #64748b; margin-top: 0.5rem;">
                            Include country code (e.g., +254 for Kenya)
                        </div>
                    </div>
                    
                    <div id="recaptcha-container"></div>
                    
                    <button type="button" class="btn-primary" id="sendLoginOtpBtn">Send OTP</button>
                    
                    <div id="otp-verification-login" style="display: none; margin-top: 2rem;">
                        <div class="form-group">
                            <label>Enter 6-Digit OTP</label>
                            <div class="otp-input-group">
                                <input type="text" maxlength="1" class="otp-input-login" data-index="0">
                                <input type="text" maxlength="1" class="otp-input-login" data-index="1">
                                <input type="text" maxlength="1" class="otp-input-login" data-index="2">
                                <input type="text" maxlength="1" class="otp-input-login" data-index="3">
                                <input type="text" maxlength="1" class="otp-input-login" data-index="4">
                                <input type="text" maxlength="1" class="otp-input-login" data-index="5">
                            </div>
                        </div>
                        <button type="button" class="btn-primary" id="verifyLoginOtpBtn">Verify & Login</button>
                        <button type="button" class="btn-secondary" id="resendLoginOtpBtn">Resend OTP</button>
                    </div>
                </form>
            </div>
            
            <div class="auth-links">
                Don't have an account? <a href="customer-register-new.php">Create one here</a>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="js/firebase-auth.js"></script>
    <script>
        // Initialize Firebase
        const firebaseConfig = <?php echo json_encode($firebase_config); ?>;
        initializeFirebase(firebaseConfig);
        
        // Tab switching
        document.querySelectorAll('.auth-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                document.querySelectorAll('.auth-tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                
                this.classList.add('active');
                document.getElementById(this.dataset.tab).classList.add('active');
            });
        });
        
        // OTP Login functionality
        let loginPhoneNumber = null;
        
        document.getElementById('sendLoginOtpBtn').addEventListener('click', async function() {
            const phoneNumber = document.getElementById('otp_phone').value;
            
            if (!phoneNumber) {
                showMessage('Please enter phone number', 'error');
                return;
            }
            
            loginPhoneNumber = phoneNumber;
            
            this.disabled = true;
            this.textContent = 'Sending OTP...';
            
            const result = await sendPhoneOTP(phoneNumber);
            
            if (result.success) {
                showMessage(result.message, 'success');
                document.getElementById('otp-verification-login').style.display = 'block';
                this.style.display = 'none';
            } else {
                showMessage(result.message, 'error');
                this.disabled = false;
                this.textContent = 'Send OTP';
            }
        });
        
        // OTP Input handling
        const otpLoginInputs = document.querySelectorAll('.otp-input-login');
        otpLoginInputs.forEach((input, index) => {
            input.addEventListener('input', function() {
                if (this.value.length === 1 && index < otpLoginInputs.length - 1) {
                    otpLoginInputs[index + 1].focus();
                }
            });
            
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Backspace' && this.value === '' && index > 0) {
                    otpLoginInputs[index - 1].focus();
                }
            });
        });
        
        // Verify OTP and login
        document.getElementById('verifyLoginOtpBtn').addEventListener('click', async function() {
            const otp = Array.from(otpLoginInputs).map(input => input.value).join('');
            
            if (otp.length !== 6) {
                showMessage('Please enter complete OTP', 'error');
                return;
            }
            
            this.disabled = true;
            this.textContent = 'Verifying...';
            
            const result = await verifyPhoneOTP(otp);
            
            if (result.success) {
                // Login with Firebase UID
                const formData = new FormData();
                formData.append('action', 'otp_login');
                formData.append('firebase_uid', result.user.uid);
                formData.append('phone_number', result.user.phoneNumber);
                
                try {
                    const response = await fetch(window.location.href, {
                        method: 'POST',
                        body: formData
                    });
                    
                    window.location.href = 'customer-account.php';
                } catch (error) {
                    showMessage('Error logging in: ' + error.message, 'error');
                    this.disabled = false;
                    this.textContent = 'Verify & Login';
                }
            } else {
                showMessage(result.message, 'error');
                this.disabled = false;
                this.textContent = 'Verify & Login';
            }
        });
        
        // Resend OTP
        document.getElementById('resendLoginOtpBtn').addEventListener('click', async function() {
            if (!loginPhoneNumber) return;
            
            this.disabled = true;
            this.textContent = 'Resending...';
            
            // Clear OTP inputs
            otpLoginInputs.forEach(input => input.value = '');
            
            const result = await sendPhoneOTP(loginPhoneNumber);
            
            if (result.success) {
                showMessage('OTP resent successfully!', 'success');
            } else {
                showMessage(result.message, 'error');
            }
            
            this.disabled = false;
            this.textContent = 'Resend OTP';
        });
        
        // Helper function to show messages
        function showMessage(message, type) {
            const container = document.getElementById('message-container');
            const alertClass = type === 'error' ? 'alert-error' : 'alert-success';
            container.innerHTML = `<div class="alert ${alertClass}">${message}</div>`;
            
            setTimeout(() => {
                container.innerHTML = '';
            }, 5000);
        }
    </script>
</body>
</html>

