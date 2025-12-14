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
$step = 'register'; // register, verify_email, verify_phone, complete

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['action'] === 'register') {
        // Step 1: Create account
        $result = $auth->register($_POST);
        
        if ($result['success']) {
            $_SESSION['pending_verification'] = [
                'customer_id' => $result['customer_id'],
                'email' => $_POST['email'],
                'phone' => $_POST['phone'] ?? null,
                'first_name' => $_POST['first_name']
            ];
            $step = !empty($_POST['phone']) ? 'verify_phone' : 'verify_email';
            $success = 'Account created! Please verify your contact details.';
        } else {
            $error = $result['message'];
        }
    } elseif ($_POST['action'] === 'verify_complete') {
        // Step 2: Complete verification
        if (isset($_SESSION['pending_verification']) && isset($_POST['firebase_verified'])) {
            $customer_id = $_SESSION['pending_verification']['customer_id'];
            $verification_method = $_POST['verification_method'] ?? 'email';
            
            // Update customer as verified
            if ($verification_method === 'phone') {
                $stmt = $conn->prepare("UPDATE customers SET phone_verified = 1, is_active = 1 WHERE id = ?");
            } else {
                $stmt = $conn->prepare("UPDATE customers SET email_verified = 1, is_active = 1, email_verification_token = NULL WHERE id = ?");
            }
            $stmt->execute([$customer_id]);
            
            // Log them in
            $stmt = $conn->prepare("SELECT * FROM customers WHERE id = ?");
            $stmt->execute([$customer_id]);
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $_SESSION['customer_id'] = $customer['id'];
            $_SESSION['customer_email'] = $customer['email'];
            
            unset($_SESSION['pending_verification']);
            
            $success = 'Verification successful! Redirecting to your account...';
            header('refresh:2;url=customer-account.php');
            $step = 'complete';
        } else {
            $error = 'Verification failed. Please try again.';
        }
    }
}

// Check if we have pending verification
if (isset($_SESSION['pending_verification'])) {
    $pending = $_SESSION['pending_verification'];
    if (!empty($pending['phone'])) {
        $step = 'verify_phone';
    } else {
        $step = 'verify_email';
    }
}

$page_title = 'Create Account | ' . $site_name;
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
            max-width: 700px;
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
        
        .step-indicator {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 2.5rem;
        }

        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: #64748b;
            position: relative;
            transition: all 0.3s;
        }

        .step.active {
            background: linear-gradient(135deg, #ff5900, #e64f00);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        .step.completed {
            background: #10b981;
            color: white;
        }

        .step::after {
            content: '';
            position: absolute;
            left: 100%;
            top: 50%;
            width: 40px;
            height: 2px;
            background: #e2e8f0;
            transform: translateY(-50%);
        }

        .step:last-child::after {
            display: none;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.25rem;
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
        
        .form-group label .required {
            color: #ef4444;
        }
        
        .form-group label .optional {
            font-weight: 400;
            color: #94a3b8;
            font-size: 0.875rem;
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

        .form-control::placeholder {
            color: #94a3b8;
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
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 28px rgba(102, 126, 234, 0.4);
        }

        .btn-primary:active {
            transform: translateY(-1px);
        }

        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .alert {
            padding: 1.125rem 1.25rem;
            border-radius: 12px;
            margin-bottom: 1.75rem;
            font-size: 0.9375rem;
            animation: slideDown 0.3s ease-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
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

        /* Verification Section */
        .verification-section {
            text-align: center;
            padding: 2rem 0;
        }

        .verification-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 1.5rem;
            background: linear-gradient(135deg, #ff5900, #e64f00);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% {
                box-shadow: 0 0 0 0 rgba(102, 126, 234, 0.7);
            }
            50% {
                box-shadow: 0 0 0 20px rgba(102, 126, 234, 0);
            }
        }

        .verification-icon svg {
            width: 40px;
            height: 40px;
            color: white;
        }

        .verification-info {
            font-size: 1rem;
            color: #64748b;
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        .verification-info strong {
            color: #1e293b;
            display: block;
            margin-top: 0.5rem;
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
            margin: 1.5rem 0;
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
        }

        .otp-input:focus {
            border-color: #ff5900;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
            outline: none;
        }

        .resend-link {
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

        .hidden {
            display: none;
        }
        
        @media (max-width: 640px) {
            .form-row {
                grid-template-columns: 1fr;
            }

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
                <h1>
                    <?php if ($step === 'complete'): ?>
                        Verification Complete!
                    <?php elseif ($step === 'verify_phone' || $step === 'verify_email'): ?>
                        Verify Your Account
                    <?php else: ?>
                        Create Your Account
                    <?php endif; ?>
                </h1>
                <p>
                    <?php if ($step === 'complete'): ?>
                        Your account has been successfully verified
                    <?php elseif ($step === 'verify_phone' || $step === 'verify_email'): ?>
                        We've sent a verification code to your <?php echo $step === 'verify_phone' ? 'phone' : 'email'; ?>
                    <?php else: ?>
                        Join us and enjoy exclusive benefits with verified account
                    <?php endif; ?>
                </p>
            </div>

            <?php if ($step !== 'complete'): ?>
            <div class="step-indicator">
                <div class="step <?php echo $step === 'register' ? 'active' : 'completed'; ?>">1</div>
                <div class="step <?php echo ($step === 'verify_phone' || $step === 'verify_email') ? 'active' : ''; ?>">2</div>
                <div class="step">3</div>
            </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <?php if ($step === 'register'): ?>
            <!-- Registration Form -->
            <form method="POST" action="" id="registerForm">
                <input type="hidden" name="action" value="register">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">First Name <span class="required">*</span></label>
                        <input type="text" id="first_name" name="first_name" class="form-control" required 
                               placeholder="John" value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="last_name">Last Name <span class="required">*</span></label>
                        <input type="text" id="last_name" name="last_name" class="form-control" required 
                               placeholder="Doe" value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="email">Email Address <span class="required">*</span></label>
                    <input type="email" id="email" name="email" class="form-control" required 
                           placeholder="your@email.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    <div class="input-hint">We'll send a verification code to this email</div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="phone">Phone Number <span class="optional">(Optional)</span></label>
                        <input type="tel" id="phone" name="phone" class="form-control" 
                               placeholder="+254712345678" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                        <div class="input-hint">For SMS OTP verification (Format: +254...)</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="business_name">Business Name <span class="optional">(Optional)</span></label>
                        <input type="text" id="business_name" name="business_name" class="form-control" 
                               placeholder="Your Business" value="<?php echo htmlspecialchars($_POST['business_name'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">Password <span class="required">*</span></label>
                    <input type="password" id="password" name="password" class="form-control" required 
                           placeholder="Create a strong password" minlength="8">
                    <div class="input-hint">Must be at least 8 characters with uppercase, lowercase, and numbers</div>
                </div>
                
                <div class="form-group">
                    <label for="password_confirm">Confirm Password <span class="required">*</span></label>
                    <input type="password" id="password_confirm" name="password_confirm" class="form-control" required 
                           placeholder="Confirm your password">
                </div>
                
                <button type="submit" class="btn-primary">Create Account & Verify</button>
            </form>
            
            <?php elseif ($step === 'verify_phone'): ?>
            <!-- Phone Verification -->
            <div class="verification-section">
                <div class="verification-icon">
                    <svg fill="currentColor" viewBox="0 0 24 24">
                        <path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/>
                    </svg>
                </div>
                <div class="verification-info">
                    Enter the 6-digit code sent to:<br>
                    <strong><?php echo htmlspecialchars($_SESSION['pending_verification']['phone'] ?? ''); ?></strong>
                </div>

                <div id="recaptcha-container"></div>
                
                <form method="POST" id="phoneVerifyForm" class="hidden">
                    <input type="hidden" name="action" value="verify_complete">
                    <input type="hidden" name="verification_method" value="phone">
                    <input type="hidden" name="firebase_verified" id="firebase_verified_phone" value="">
                    
                    <div class="otp-input-group">
                        <input type="text" class="otp-input" maxlength="1" pattern="[0-9]">
                        <input type="text" class="otp-input" maxlength="1" pattern="[0-9]">
                        <input type="text" class="otp-input" maxlength="1" pattern="[0-9]">
                        <input type="text" class="otp-input" maxlength="1" pattern="[0-9]">
                        <input type="text" class="otp-input" maxlength="1" pattern="[0-9]">
                        <input type="text" class="otp-input" maxlength="1" pattern="[0-9]">
                    </div>
                    
                    <button type="submit" class="btn-primary" id="verifyPhoneBtn">Verify Phone Number</button>
                </form>

                <button type="button" class="btn-primary" id="sendPhoneOtpBtn">Send SMS Code</button>
                
                <div class="resend-link">
                    Didn't receive the code? <button type="button" id="resendPhoneBtn">Resend SMS</button>
                </div>

                <div class="auth-links">
                    <a href="?switch=email">Verify via email instead</a>
                </div>
            </div>
            
            <?php elseif ($step === 'verify_email'): ?>
            <!-- Email Verification -->
            <div class="verification-section">
                <div class="verification-icon">
                    <svg fill="currentColor" viewBox="0 0 24 24">
                        <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                    </svg>
                </div>
                <div class="verification-info">
                    Enter the verification code sent to:<br>
                    <strong><?php echo htmlspecialchars($_SESSION['pending_verification']['email'] ?? ''); ?></strong>
                </div>
                
                <form method="POST" id="emailVerifyForm">
                    <input type="hidden" name="action" value="verify_complete">
                    <input type="hidden" name="verification_method" value="email">
                    <input type="hidden" name="firebase_verified" id="firebase_verified_email" value="1">
                    
                    <div class="otp-input-group">
                        <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" required>
                        <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" required>
                        <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" required>
                        <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" required>
                        <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" required>
                        <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" required>
                    </div>
                    
                    <button type="submit" class="btn-primary">Verify Email</button>
                </form>
                
                <div class="resend-link">
                    Didn't receive the code? <button type="button" id="resendEmailBtn">Resend Email</button>
                </div>

                <?php if (!empty($_SESSION['pending_verification']['phone'])): ?>
                <div class="auth-links">
                    <a href="?switch=phone">Verify via SMS instead</a>
                </div>
                <?php endif; ?>
            </div>
            
            <?php else: ?>
            <!-- Completion Message -->
            <div class="verification-section">
                <div class="verification-icon" style="background: #10b981; animation: none;">
                    <svg fill="currentColor" viewBox="0 0 24 24">
                        <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                    </svg>
                </div>
                <div class="verification-info">
                    <strong>Welcome to <?php echo htmlspecialchars($site_name); ?>!</strong><br>
                    Your account is now fully verified and active.
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($step === 'register'): ?>
            <div class="auth-links">
                Already have an account? <a href="customer-login.php">Login here</a>
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

    // Phone Verification
    <?php if ($step === 'verify_phone'): ?>
    let confirmationResult;
    let recaptchaVerifier;
    let recaptchaInitialized = false;

    // Initialize reCAPTCHA
    async function initRecaptcha() {
        if (recaptchaInitialized) return;
        
        try {
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
    document.getElementById('sendPhoneOtpBtn').addEventListener('click', async function() {
        const phoneNumber = '<?php echo $_SESSION['pending_verification']['phone'] ?? ''; ?>';
        const btn = this;
        btn.disabled = true;
        btn.textContent = 'Sending SMS...';

        try {
            // Ensure reCAPTCHA is initialized
            if (!recaptchaInitialized) {
                await initRecaptcha();
            }
            
            // Validate phone number format
            if (!phoneNumber || phoneNumber.length < 10) {
                throw new Error('Invalid phone number');
            }
            
            console.log('Sending SMS to:', phoneNumber);
            
            // Configure test phone numbers for development (if needed)
            // Firebase Console > Authentication > Sign-in method > Phone > Test phone numbers
            
            confirmationResult = await auth.signInWithPhoneNumber(phoneNumber, recaptchaVerifier);
            
            // Show verification form
            document.getElementById('phoneVerifyForm').classList.remove('hidden');
            btn.classList.add('hidden');
            
            alert('SMS sent! Please check your phone for the verification code.');
        } catch (error) {
            console.error('SMS send error:', error);
            
            // Provide helpful error messages
            let errorMessage = 'Error sending SMS: ';
            if (error.code === 'auth/invalid-phone-number') {
                errorMessage += 'Invalid phone number format. Please check the number.';
            } else if (error.code === 'auth/too-many-requests') {
                errorMessage += 'Too many attempts. Please try again later.';
            } else if (error.code === 'auth/invalid-app-credential') {
                errorMessage += 'Configuration error. Please ensure:\n' +
                               '1. Your domain is authorized in Firebase Console\n' +
                               '2. Phone authentication is enabled\n' +
                               '3. Test phone numbers are configured (for development)';
            } else {
                errorMessage += error.message;
            }
            
            alert(errorMessage);
            
            // Reset button and reCAPTCHA
            btn.disabled = false;
            btn.textContent = 'Send SMS Code';
            
            // Reset reCAPTCHA for retry
            if (recaptchaVerifier) {
                recaptchaVerifier.clear();
                recaptchaInitialized = false;
                await initRecaptcha();
            }
        }
    });

    // Verify OTP
    document.getElementById('phoneVerifyForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const code = getOtpValue();
        
        if (code.length !== 6) {
            alert('Please enter the complete 6-digit code');
            return;
        }

        const btn = document.getElementById('verifyPhoneBtn');
        btn.disabled = true;
        btn.textContent = 'Verifying...';

        try {
            const result = await confirmationResult.confirm(code);
            document.getElementById('firebase_verified_phone').value = '1';
            this.submit();
        } catch (error) {
            console.error('Verification error:', error);
            alert('Invalid code. Please try again.');
            btn.disabled = false;
            btn.textContent = 'Verify Phone Number';
        }
    });

    // Resend SMS
    document.getElementById('resendPhoneBtn').addEventListener('click', function() {
        document.getElementById('sendPhoneOtpBtn').click();
    });
    <?php endif; ?>

    // Email Verification
    <?php if ($step === 'verify_email'): ?>
    // In a real implementation, you would:
    // 1. Send email with code via server
    // 2. Verify code against database
    // For now, this is a placeholder
    document.getElementById('resendEmailBtn').addEventListener('click', function() {
        alert('Verification email resent! Please check your inbox and spam folder.');
    });
    <?php endif; ?>

    // Registration form validation
    <?php if ($step === 'register'): ?>
    document.getElementById('registerForm').addEventListener('submit', function(e) {
        const password = document.getElementById('password').value;
        const confirm = document.getElementById('password_confirm').value;
        
        if (password !== confirm) {
            e.preventDefault();
            alert('Passwords do not match!');
            return false;
        }

        // Password strength check
        const strongPassword = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/;
        if (!strongPassword.test(password)) {
            e.preventDefault();
            alert('Password must contain at least 8 characters with uppercase, lowercase, and numbers');
            return false;
        }
    });
    <?php endif; ?>
    </script>
</body>
</html>

