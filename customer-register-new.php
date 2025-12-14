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

// Load Firebase config
$firebase_config = require_once 'includes/firebase-config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'register') {
        // Handle registration with Firebase verification
        $result = $auth->register($_POST);
        
        if ($result['success']) {
            $_SESSION['pending_verification'] = [
                'customer_id' => $result['customer_id'],
                'email' => $_POST['email'],
                'phone' => $_POST['phone'] ?? null
            ];
            $success = 'Account created! Please verify your email or phone number.';
        } else {
            $error = $result['message'];
        }
    } elseif ($_POST['action'] === 'verify_complete') {
        // Complete verification and activate account
        if (isset($_SESSION['pending_verification']) && isset($_POST['firebase_token'])) {
            $customer_id = $_SESSION['pending_verification']['customer_id'];
            
            // Update customer as verified
            $stmt = $conn->prepare("UPDATE customers SET email_verified = 1, email_verification_token = NULL WHERE customer_id = ?");
            $stmt->execute([$customer_id]);
            
            // Log them in
            $stmt = $conn->prepare("SELECT * FROM customers WHERE customer_id = ?");
            $stmt->execute([$customer_id]);
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $_SESSION['customer_id'] = $customer['customer_id'];
            $_SESSION['customer_email'] = $customer['email'];
            
            unset($_SESSION['pending_verification']);
            
            header('Location: customer-account.php');
            exit;
        }
    }
}

$page_title = 'Create Account | ' . $site_name;
$pending_verification = isset($_SESSION['pending_verification']);
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
            max-width: 700px;
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
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
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
        
        .form-group label .optional {
            font-weight: 400;
            color: #94a3b8;
            font-size: 0.875rem;
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
        
        .alert-info {
            background: #dbeafe;
            color: #1e40af;
            border: 1px solid #bfdbfe;
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
        
        .verification-section {
            text-align: center;
            padding: 2rem;
        }
        
        .verification-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 1.5rem;
            background: linear-gradient(135deg, #ff5900 0%, #e64f00 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .verification-icon svg {
            width: 40px;
            height: 40px;
            fill: white;
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
            .form-row {
                grid-template-columns: 1fr;
            }
            
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
                <h1>Create Your Account</h1>
                <p>Join us and enjoy exclusive benefits</p>
            </div>
            
            <?php if (!$pending_verification): ?>
            <!-- Registration Tabs -->
            <div class="auth-tabs">
                <div class="auth-tab active" data-tab="email-register">
                    <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24" style="display: inline-block; vertical-align: middle; margin-right: 0.5rem;"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
                    Email Registration
                </div>
                <div class="auth-tab" data-tab="phone-register">
                    <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24" style="display: inline-block; vertical-align: middle; margin-right: 0.5rem;"><path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/></svg>
                    Phone Registration
                </div>
            </div>
            
            <div id="message-container"></div>
            
            <!-- Email Registration Form -->
            <div class="tab-content active" id="email-register">
                <form method="POST" action="" id="emailRegisterForm">
                    <input type="hidden" name="action" value="register">
                    <input type="hidden" name="registration_type" value="email">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="first_name">First Name</label>
                            <input type="text" id="first_name" name="first_name" class="form-control" required placeholder="John">
                        </div>
                        
                        <div class="form-group">
                            <label for="last_name">Last Name</label>
                            <input type="text" id="last_name" name="last_name" class="form-control" required placeholder="Doe">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" class="form-control" required placeholder="your@email.com">
                    </div>
                    
                    <div class="form-group">
                        <label for="business_name">Business Name <span class="optional">(Optional)</span></label>
                        <input type="text" id="business_name" name="business_name" class="form-control" placeholder="Your Business">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" id="password" name="password" class="form-control" required placeholder="Min. 8 characters">
                        </div>
                        
                        <div class="form-group">
                            <label for="password_confirm">Confirm Password</label>
                            <input type="password" id="password_confirm" name="password_confirm" class="form-control" required placeholder="Confirm password">
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-primary" id="emailRegisterBtn">Create Account</button>
                </form>
            </div>
            
            <!-- Phone Registration Form -->
            <div class="tab-content" id="phone-register">
                <form id="phoneRegisterForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="phone_first_name">First Name</label>
                            <input type="text" id="phone_first_name" class="form-control" required placeholder="John">
                        </div>
                        
                        <div class="form-group">
                            <label for="phone_last_name">Last Name</label>
                            <input type="text" id="phone_last_name" class="form-control" required placeholder="Doe">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone_number">Phone Number (with country code)</label>
                        <input type="tel" id="phone_number" class="form-control" required placeholder="+254712345678">
                        <div style="font-size: 0.875rem; color: #64748b; margin-top: 0.5rem;">
                            Include country code (e.g., +254 for Kenya)
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone_email">Email Address <span class="optional">(Optional)</span></label>
                        <input type="email" id="phone_email" class="form-control" placeholder="your@email.com">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="phone_password">Password</label>
                            <input type="password" id="phone_password" class="form-control" required placeholder="Min. 8 characters">
                        </div>
                        
                        <div class="form-group">
                            <label for="phone_password_confirm">Confirm Password</label>
                            <input type="password" id="phone_password_confirm" class="form-control" required placeholder="Confirm password">
                        </div>
                    </div>
                    
                    <div id="recaptcha-container"></div>
                    
                    <button type="button" class="btn-primary" id="sendOtpBtn">Send OTP</button>
                    
                    <div id="otp-verification" style="display: none; margin-top: 2rem;">
                        <div class="form-group">
                            <label>Enter 6-Digit OTP</label>
                            <div class="otp-input-group">
                                <input type="text" maxlength="1" class="otp-input" data-index="0">
                                <input type="text" maxlength="1" class="otp-input" data-index="1">
                                <input type="text" maxlength="1" class="otp-input" data-index="2">
                                <input type="text" maxlength="1" class="otp-input" data-index="3">
                                <input type="text" maxlength="1" class="otp-input" data-index="4">
                                <input type="text" maxlength="1" class="otp-input" data-index="5">
                            </div>
                        </div>
                        <button type="button" class="btn-primary" id="verifyOtpBtn">Verify & Create Account</button>
                        <button type="button" class="btn-secondary" id="resendOtpBtn">Resend OTP</button>
                    </div>
                </form>
            </div>
            
            <?php else: ?>
            <!-- Verification Step -->
            <div class="verification-section">
                <div class="verification-icon">
                    <svg fill="currentColor" viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
                </div>
                
                <h2 style="font-size: 1.5rem; margin-bottom: 1rem; color: #1e293b;">Verify Your Account</h2>
                <p style="color: #64748b; margin-bottom: 2rem;">
                    We've sent a verification link to <strong><?php echo htmlspecialchars($_SESSION['pending_verification']['email']); ?></strong>
                </p>
                
                <div class="alert alert-info">
                    Please check your email and click the verification link to activate your account.
                </div>
                
                <button type="button" class="btn-secondary" id="resendEmailBtn">Resend Verification Email</button>
            </div>
            <?php endif; ?>
            
            <div class="auth-links">
                Already have an account? <a href="customer-login.php">Login here</a>
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
        
        // Email Registration with Firebase
        document.getElementById('emailRegisterForm').addEventListener('submit', async function(e) {
            const password = document.getElementById('password').value;
            const confirm = document.getElementById('password_confirm').value;
            
            if (password !== confirm) {
                e.preventDefault();
                showMessage('Passwords do not match!', 'error');
                return;
            }
            
            if (password.length < 8) {
                e.preventDefault();
                showMessage('Password must be at least 8 characters long!', 'error');
                return;
            }
        });
        
        // Phone OTP Registration
        let phoneVerificationData = null;
        
        document.getElementById('sendOtpBtn').addEventListener('click', async function() {
            const firstName = document.getElementById('phone_first_name').value;
            const lastName = document.getElementById('phone_last_name').value;
            const phoneNumber = document.getElementById('phone_number').value;
            const email = document.getElementById('phone_email').value;
            const password = document.getElementById('phone_password').value;
            const confirm = document.getElementById('phone_password_confirm').value;
            
            if (!firstName || !lastName || !phoneNumber || !password) {
                showMessage('Please fill in all required fields', 'error');
                return;
            }
            
            if (password !== confirm) {
                showMessage('Passwords do not match!', 'error');
                return;
            }
            
            if (password.length < 8) {
                showMessage('Password must be at least 8 characters long!', 'error');
                return;
            }
            
            phoneVerificationData = { firstName, lastName, phoneNumber, email, password };
            
            this.disabled = true;
            this.textContent = 'Sending OTP...';
            
            const result = await sendPhoneOTP(phoneNumber);
            
            if (result.success) {
                showMessage(result.message, 'success');
                document.getElementById('otp-verification').style.display = 'block';
                this.style.display = 'none';
            } else {
                showMessage(result.message, 'error');
                this.disabled = false;
                this.textContent = 'Send OTP';
            }
        });
        
        // OTP Input handling
        const otpInputs = document.querySelectorAll('.otp-input');
        otpInputs.forEach((input, index) => {
            input.addEventListener('input', function() {
                if (this.value.length === 1 && index < otpInputs.length - 1) {
                    otpInputs[index + 1].focus();
                }
            });
            
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Backspace' && this.value === '' && index > 0) {
                    otpInputs[index - 1].focus();
                }
            });
        });
        
        // Verify OTP and create account
        document.getElementById('verifyOtpBtn').addEventListener('click', async function() {
            const otp = Array.from(otpInputs).map(input => input.value).join('');
            
            if (otp.length !== 6) {
                showMessage('Please enter complete OTP', 'error');
                return;
            }
            
            this.disabled = true;
            this.textContent = 'Verifying...';
            
            const result = await verifyPhoneOTP(otp);
            
            if (result.success) {
                // Create account in database
                const formData = new FormData();
                formData.append('action', 'register');
                formData.append('registration_type', 'phone');
                formData.append('first_name', phoneVerificationData.firstName);
                formData.append('last_name', phoneVerificationData.lastName);
                formData.append('phone', phoneVerificationData.phoneNumber);
                formData.append('email', phoneVerificationData.email || '');
                formData.append('password', phoneVerificationData.password);
                formData.append('password_confirm', phoneVerificationData.password);
                formData.append('firebase_uid', result.user.uid);
                formData.append('firebase_token', result.user.idToken);
                formData.append('phone_verified', '1');
                
                try {
                    const response = await fetch(window.location.href, {
                        method: 'POST',
                        body: formData
                    });
                    
                    window.location.href = 'customer-account.php';
                } catch (error) {
                    showMessage('Error creating account: ' + error.message, 'error');
                    this.disabled = false;
                    this.textContent = 'Verify & Create Account';
                }
            } else {
                showMessage(result.message, 'error');
                this.disabled = false;
                this.textContent = 'Verify & Create Account';
            }
        });
        
        // Resend OTP
        document.getElementById('resendOtpBtn').addEventListener('click', async function() {
            if (!phoneVerificationData) return;
            
            this.disabled = true;
            this.textContent = 'Resending...';
            
            // Clear OTP inputs
            otpInputs.forEach(input => input.value = '');
            
            const result = await sendPhoneOTP(phoneVerificationData.phoneNumber);
            
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
        
        // Resend email verification
        <?php if ($pending_verification): ?>
        document.getElementById('resendEmailBtn').addEventListener('click', async function() {
            const email = '<?php echo $_SESSION['pending_verification']['email']; ?>';
            
            this.disabled = true;
            this.textContent = 'Sending...';
            
            const result = await sendEmailVerification(email);
            
            if (result.success) {
                alert('Verification email sent! Please check your inbox.');
            } else {
                alert(result.message);
            }
            
            this.disabled = false;
            this.textContent = 'Resend Verification Email';
        });
        <?php endif; ?>
    </script>
</body>
</html>

