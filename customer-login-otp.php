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
$firebase_config = require 'includes/firebase-config.php';

$error = '';
$success = '';
$step = 'input'; // input, verify

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['action'] === 'send_otp') {
        $phone = trim($_POST['phone'] ?? '');
        
        // Check if customer exists with this phone
        $stmt = $conn->prepare("SELECT id, first_name, phone_verified FROM customers WHERE phone = ? AND is_active = 1");
        $stmt->bind_param('s', $phone);
        $stmt->execute();
        $result = $stmt->get_result();
        $customer = $result->fetch_assoc();
        
        if ($customer) {
            $_SESSION['otp_login_phone'] = $phone;
            $_SESSION['otp_customer_id'] = $customer['id'];
            $step = 'verify';
            $success = 'OTP sent to your phone. Please enter the code.';
        } else {
            $error = 'No account found with this phone number. Please register first.';
        }
    } elseif ($_POST['action'] === 'verify_otp') {
        if (isset($_SESSION['otp_customer_id']) && isset($_POST['firebase_verified'])) {
            $customer_id = $_SESSION['otp_customer_id'];
            
            // Get customer details
            $stmt = $conn->prepare("SELECT * FROM customers WHERE id = ?");
            $stmt->bind_param('i', $customer_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $customer = $result->fetch_assoc();
            
            // Log them in
            $_SESSION['customer_id'] = $customer['id'];
            $_SESSION['customer_email'] = $customer['email'];
            
            // Update last login
            $stmt = $conn->prepare("UPDATE customers SET last_login = NOW() WHERE id = ?");
            $stmt->bind_param('i', $customer_id);
            $stmt->execute();
            
            unset($_SESSION['otp_login_phone']);
            unset($_SESSION['otp_customer_id']);
            
            $redirect = $_SESSION['redirect_after_login'] ?? 'customer-account.php';
            unset($_SESSION['redirect_after_login']);
            
            header('Location: ' . $redirect);
            exit;
        } else {
            $error = 'Verification failed. Please try again.';
        }
    }
}

// Check if we're in verification step
if (isset($_SESSION['otp_login_phone'])) {
    $step = 'verify';
}

$page_title = 'Login with OTP | ' . $site_name;
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
    <script src="https://www.gstatic.com/firebasejs/10.7.1/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/10.7.1/firebase-auth-compat.js"></script>
    
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

        .input-hint {
            font-size: 0.875rem;
            color: #64748b;
            margin-top: 0.5rem;
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

        .divider {
            text-align: center;
            margin: 1.5rem 0;
            position: relative;
        }

        .divider::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            width: 100%;
            height: 1px;
            background: #e2e8f0;
        }

        .divider span {
            background: white;
            padding: 0 1rem;
            position: relative;
            color: #64748b;
            font-size: 0.875rem;
        }

        #recaptcha-container {
            display: flex;
            justify-content: center;
            margin: 1.5rem 0;
        }

        .otp-input-group {
            display: flex;
            gap: 0.75rem;
            justify-content: center;
            margin-bottom: 1.5rem;
        }

        .otp-input {
            width: 50px;
            height: 56px;
            text-align: center;
            font-size: 1.5rem;
            font-weight: 600;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            transition: all 0.3s;
            background: #f8fafc;
        }

        .otp-input:focus {
            border-color: #ff5900;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
            outline: none;
            background: white;
        }

        .otp-input:not(:placeholder-shown) {
            background: white;
            border-color: #ff5900;
        }

        .resend-link {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.875rem;
            color: #64748b;
        }

        .resend-link button {
            background: none;
            border: none;
            color: #ff5900;
            font-weight: 600;
            cursor: pointer;
            text-decoration: underline;
        }

        .phone-display {
            text-align: center;
            margin-bottom: 1.5rem;
            padding: 1rem;
            background: #f1f5f9;
            border-radius: 12px;
        }

        .phone-display p {
            margin: 0;
            color: #475569;
            font-size: 0.9375rem;
        }

        .phone-display strong {
            color: #1e293b;
            font-weight: 600;
        }

        .hidden {
            display: none;
        }

        @media (max-width: 640px) {
            .auth-card {
                padding: 2rem 1.5rem;
            }

            .otp-input {
                width: 45px;
                height: 50px;
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
                <div class="auth-icon">
                    <svg fill="currentColor" viewBox="0 0 24 24">
                        <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z M7.58 4.08L6.15 2.65C3.75 4.48 2.17 7.3 2.03 10.5h2c.15-2.65 1.51-4.97 3.55-6.42zm12.39 6.42h2c-.15-3.2-1.73-6.02-4.12-7.85l-1.42 1.43c2.02 1.45 3.39 3.77 3.54 6.42zM18 11c0-3.07-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.63 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2v-5zm-6 11c.14 0 .27-.01.4-.04.65-.14 1.18-.58 1.44-1.18.1-.24.15-.5.15-.78h-4c.01 1.1.9 2 2.01 2z"/>
                    </svg>
                </div>
                <h1>Login with OTP</h1>
                <p>
                    <?php echo $step === 'verify' ? 'Enter the verification code sent to your phone' : 'Enter your phone number to receive an OTP'; ?>
                </p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <?php if ($step === 'input'): ?>
            <!-- Phone Input -->
            <form method="POST" action="" id="phoneForm">
                <input type="hidden" name="action" value="send_otp">
                
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" class="form-control" required 
                           placeholder="+254712345678" pattern="\+[0-9]{10,15}">
                    <div class="input-hint">Enter phone number with country code (e.g., +254...)</div>
                </div>
                
                <button type="submit" class="btn-primary">Send OTP</button>
            </form>

            <div class="divider"><span>OR</span></div>

            <div class="auth-links">
                <a href="customer-login.php">Login with Email & Password</a>
                <a href="customer-register-verified.php">Don't have an account? Sign up</a>
            </div>
            
            <?php else: ?>
            <!-- OTP Verification -->
            <div id="recaptcha-container"></div>
            
            <div class="phone-display">
                <p>Sending OTP to: <strong><?php echo htmlspecialchars($_SESSION['otp_login_phone'] ?? ''); ?></strong></p>
            </div>

            <button type="button" class="btn-primary" id="sendOtpBtn">Send OTP to Phone</button>
            
            <!-- OTP Input Form - Initially Hidden -->
            <div id="otpVerifySection" style="display: none;">
                <form method="POST" id="otpVerifyForm">
                    <input type="hidden" name="action" value="verify_otp">
                    <input type="hidden" name="firebase_verified" id="firebase_verified" value="">
                    
                    <div class="form-group">
                        <label for="otp-code">Enter 6-Digit Verification Code</label>
                        <div class="otp-input-group">
                            <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" autocomplete="off" required>
                            <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" autocomplete="off" required>
                            <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" autocomplete="off" required>
                            <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" autocomplete="off" required>
                            <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" autocomplete="off" required>
                            <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" autocomplete="off" required>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-primary" id="verifyBtn">Verify & Login</button>
                </form>
                
                <div class="resend-link">
                    Didn't receive the code? <button type="button" id="resendBtn">Resend OTP</button>
                </div>
            </div>

            <div class="auth-links">
                <a href="customer-login-otp.php">Try another phone number</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script>
    // Firebase Configuration
    const firebaseConfig = <?php echo json_encode($firebase_config); ?>;
    
    // Initialize Firebase
    firebase.initializeApp(firebaseConfig);
    const auth = firebase.auth();

    <?php if ($step === 'verify'): ?>
    let confirmationResult;
    let recaptchaVerifier;

    // OTP Input Auto-focus
    const otpInputs = document.querySelectorAll('.otp-input');
    otpInputs.forEach((input, index) => {
        input.addEventListener('input', (e) => {
            if (e.target.value.length === 1 && index < otpInputs.length - 1) {
                otpInputs[index + 1].focus();
            }
        });
        
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Backspace' && !e.target.value && index > 0) {
                otpInputs[index - 1].focus();
            }
        });
    });

    // Get OTP value
    function getOtpValue() {
        return Array.from(otpInputs).map(input => input.value).join('');
    }

    // Initialize reCAPTCHA
    let recaptchaInitialized = false;
    
    async function initRecaptcha() {
        if (recaptchaInitialized) return;
        
        try {
            // For development: Use invisible reCAPTCHA to avoid domain issues
            recaptchaVerifier = new firebase.auth.RecaptchaVerifier('recaptcha-container', {
                'size': 'invisible',
                'callback': (response) => {
                    console.log('reCAPTCHA solved:', response);
                },
                'expired-callback': () => {
                    console.log('reCAPTCHA expired, reinitializing...');
                    recaptchaInitialized = false;
                    recaptchaVerifier.clear();
                    initRecaptcha();
                }
            });
            
            await recaptchaVerifier.render();
            recaptchaInitialized = true;
            console.log('reCAPTCHA initialized successfully');
        } catch (error) {
            console.error('reCAPTCHA initialization error:', error);
            throw error;
        }
    }
    
    // Initialize when page loads
    window.addEventListener('load', function() {
        setTimeout(initRecaptcha, 500); // Small delay to ensure Firebase is ready
    });

    // Send OTP
    document.getElementById('sendOtpBtn').addEventListener('click', async function() {
        const phoneNumber = '<?php echo $_SESSION['otp_login_phone'] ?? ''; ?>';
        const btn = this;
        btn.disabled = true;
        btn.textContent = 'Sending OTP...';

        try {
            // Ensure reCAPTCHA is initialized
            if (!recaptchaInitialized) {
                await initRecaptcha();
            }
            
            // Validate phone number format
            if (!phoneNumber || phoneNumber.length < 10) {
                throw new Error('Invalid phone number');
            }
            
            console.log('Sending OTP to:', phoneNumber);
            
            confirmationResult = await auth.signInWithPhoneNumber(phoneNumber, recaptchaVerifier);
            
            // Hide send button, show verification section
            btn.style.display = 'none';
            const verifySection = document.getElementById('otpVerifySection');
            verifySection.style.display = 'block';
            
            console.log('OTP sent, showing input section');
            
            // Focus first input
            setTimeout(() => {
                const firstInput = document.querySelector('.otp-input');
                if (firstInput) {
                    firstInput.focus();
                    console.log('Focused first input');
                }
            }, 100);
            
            // Show success message
            const successDiv = document.createElement('div');
            successDiv.className = 'alert alert-success';
            successDiv.style.marginBottom = '1.5rem';
            successDiv.textContent = 'OTP sent successfully! Please check your phone.';
            document.getElementById('otpVerifyForm').insertAdjacentElement('beforebegin', successDiv);
            
            // Remove success message after 5 seconds
            setTimeout(() => successDiv.remove(), 5000);
        } catch (error) {
            console.error('OTP send error:', error);
            
            // Provide helpful error messages
            let errorMessage = 'Error sending OTP: ';
            if (error.code === 'auth/invalid-phone-number') {
                errorMessage = 'Invalid phone number format. Please check the number.';
            } else if (error.code === 'auth/too-many-requests') {
                errorMessage = 'Too many attempts. Please try again later.';
            } else if (error.code === 'auth/invalid-app-credential') {
                errorMessage = 'Configuration error. Please ensure:\n' +
                               '1. Your domain is authorized in Firebase Console\n' +
                               '2. Phone authentication is enabled\n' +
                               '3. Test phone numbers are configured (for development)';
            } else {
                errorMessage += error.message;
            }
            
            // Show error message
            const errorDiv = document.createElement('div');
            errorDiv.className = 'alert alert-error';
            errorDiv.style.marginBottom = '1.5rem';
            errorDiv.textContent = errorMessage;
            btn.insertAdjacentElement('beforebegin', errorDiv);
            
            btn.disabled = false;
            btn.textContent = 'Send OTP to Phone';
            
            // Remove error message after 7 seconds
            setTimeout(() => errorDiv.remove(), 7000);
            
            // Reset reCAPTCHA for retry
            if (recaptchaVerifier) {
                recaptchaVerifier.clear();
                recaptchaInitialized = false;
                await initRecaptcha();
            }
        }
    });

    // Verify OTP
    document.getElementById('otpVerifyForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const code = getOtpValue();
        
        if (code.length !== 6) {
            alert('Please enter the complete 6-digit code');
            return;
        }

        const btn = document.getElementById('verifyBtn');
        btn.disabled = true;
        btn.textContent = 'Verifying...';

        try {
            const result = await confirmationResult.confirm(code);
            document.getElementById('firebase_verified').value = '1';
            this.submit();
        } catch (error) {
            console.error('Verification error:', error);
            alert('Invalid OTP. Please try again.');
            btn.disabled = false;
            btn.textContent = 'Verify & Login';
        }
    });

    // Resend OTP
    document.getElementById('resendBtn').addEventListener('click', function() {
        document.getElementById('sendOtpBtn').click();
    });
    <?php endif; ?>
    </script>
</body>
</html>

