<?php
/**
 * Customer Authentication Class
 * Handles login, registration, password reset, and session management
 */

class CustomerAuth {
    private $conn;
    private $session_lifetime = 86400; // 24 hours
    
    public function __construct($db_connection) {
        $this->conn = $db_connection;
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    /**
     * Register new customer
     */
    public function register($data) {
        $required = ['first_name', 'last_name', 'email', 'password'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return ['success' => false, 'message' => ucfirst(str_replace('_', ' ', $field)) . ' is required'];
            }
        }
        
        // Validate email
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid email address'];
        }
        
        // Check if email already exists
        $stmt = $this->conn->prepare("SELECT id FROM customers WHERE email = ?");
        $stmt->bind_param('s', $data['email']);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            return ['success' => false, 'message' => 'Email already registered'];
        }
        
        // Validate password strength
        if (strlen($data['password']) < 8) {
            return ['success' => false, 'message' => 'Password must be at least 8 characters'];
        }
        
        // Hash password
        $password_hash = password_hash($data['password'], PASSWORD_DEFAULT);
        
        // Generate email verification token
        $verification_token = bin2hex(random_bytes(32));
        
        // Insert customer
        $stmt = $this->conn->prepare("
            INSERT INTO customers (first_name, last_name, email, password, phone, business_name, 
                                  email_verification_token, is_active, email_verified, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, 1, 0, NOW())
        ");
        
        $phone = $data['phone'] ?? null;
        $business = $data['business_name'] ?? null;
        
        $stmt->bind_param('sssssss', 
            $data['first_name'], 
            $data['last_name'], 
            $data['email'], 
            $password_hash,
            $phone,
            $business,
            $verification_token
        );
        
        if ($stmt->execute()) {
            $customer_id = $this->conn->insert_id;
            
            // Log activity
            $this->logActivity($customer_id, 'registration', 'Customer account created');
            
            // Auto-login after registration
            $this->createSession($customer_id);
            
            return [
                'success' => true, 
                'message' => 'Registration successful',
                'customer_id' => $customer_id,
                'verification_token' => $verification_token
            ];
        }
        
        return ['success' => false, 'message' => 'Registration failed. Please try again.'];
    }
    
    /**
     * Login customer
     */
    public function login($email, $password, $remember = false) {
        // Check if account is locked
        $stmt = $this->conn->prepare("
            SELECT id, password, is_active, locked_until, login_attempts 
            FROM customers 
            WHERE email = ?
        ");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return ['success' => false, 'message' => 'Invalid email or password'];
        }
        
        $customer = $result->fetch_assoc();
        
        // Check if account is active
        if (!$customer['is_active']) {
            return ['success' => false, 'message' => 'Account is disabled. Please contact support.'];
        }
        
        // Check if account is locked
        if ($customer['locked_until'] && strtotime($customer['locked_until']) > time()) {
            $minutes = ceil((strtotime($customer['locked_until']) - time()) / 60);
            return ['success' => false, 'message' => "Account locked. Try again in {$minutes} minutes."];
        }
        
        // Verify password
        if (!password_verify($password, $customer['password'])) {
            $this->incrementLoginAttempts($customer['id']);
            return ['success' => false, 'message' => 'Invalid email or password'];
        }
        
        // Reset login attempts
        $this->resetLoginAttempts($customer['id']);
        
        // Update last login
        $stmt = $this->conn->prepare("UPDATE customers SET last_login = NOW() WHERE id = ?");
        $stmt->bind_param('i', $customer['id']);
        $stmt->execute();
        
        // Create session
        $this->createSession($customer['id'], $remember);
        
        // Log activity
        $this->logActivity($customer['id'], 'login', 'Customer logged in');
        
        return ['success' => true, 'message' => 'Login successful', 'customer_id' => $customer['id']];
    }
    
    /**
     * Create customer session
     */
    private function createSession($customer_id, $remember = false) {
        $_SESSION['customer_id'] = $customer_id;
        $_SESSION['customer_logged_in'] = true;
        $_SESSION['login_time'] = time();
        
        if ($remember) {
            $token = bin2hex(random_bytes(32));
            $this->conn->prepare("UPDATE customers SET remember_token = ? WHERE id = ?")
                ->bind_param('si', $token, $customer_id)
                ->execute();
            
            setcookie('customer_remember', $token, time() + (86400 * 30), '/', '', true, true);
        }
    }
    
    /**
     * Check if customer is logged in
     */
    public function isLoggedIn() {
        if (isset($_SESSION['customer_logged_in']) && $_SESSION['customer_logged_in']) {
            // Check session timeout
            if (time() - $_SESSION['login_time'] > $this->session_lifetime) {
                $this->logout();
                return false;
            }
            return true;
        }
        
        // Check remember me cookie
        if (isset($_COOKIE['customer_remember'])) {
            $token = $_COOKIE['customer_remember'];
            $stmt = $this->conn->prepare("SELECT id FROM customers WHERE remember_token = ? AND is_active = 1");
            $stmt->bind_param('s', $token);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $customer = $result->fetch_assoc();
                $this->createSession($customer['id'], true);
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get current customer ID
     */
    public function getCustomerId() {
        return $_SESSION['customer_id'] ?? null;
    }
    
    /**
     * Get customer data
     */
    public function getCustomerData($customer_id = null) {
        $customer_id = $customer_id ?? $this->getCustomerId();
        
        if (!$customer_id) {
            return null;
        }
        
        $stmt = $this->conn->prepare("
            SELECT id, first_name, last_name, email, phone, business_name, address, 
                   city, state, postal_code, country, email_verified, created_at, last_login
            FROM customers 
            WHERE id = ? AND is_active = 1
        ");
        $stmt->bind_param('i', $customer_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }
    
    /**
     * Logout customer
     */
    public function logout() {
        if (isset($_SESSION['customer_id'])) {
            $this->logActivity($_SESSION['customer_id'], 'logout', 'Customer logged out');
        }
        
        $_SESSION = [];
        session_destroy();
        
        if (isset($_COOKIE['customer_remember'])) {
            setcookie('customer_remember', '', time() - 3600, '/');
        }
    }
    
    /**
     * Request password reset
     */
    public function requestPasswordReset($email) {
        $stmt = $this->conn->prepare("SELECT id, first_name FROM customers WHERE email = ? AND is_active = 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            // Don't reveal if email exists
            return ['success' => true, 'message' => 'If your email is registered, you will receive reset instructions.'];
        }
        
        $customer = $result->fetch_assoc();
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        $stmt = $this->conn->prepare("
            UPDATE customers 
            SET password_reset_token = ?, password_reset_expires = ? 
            WHERE id = ?
        ");
        $stmt->bind_param('ssi', $token, $expires, $customer['id']);
        $stmt->execute();
        
        $this->logActivity($customer['id'], 'password_reset_request', 'Password reset requested');
        
        return [
            'success' => true, 
            'message' => 'If your email is registered, you will receive reset instructions.',
            'token' => $token,
            'customer' => $customer
        ];
    }
    
    /**
     * Reset password with token
     */
    public function resetPassword($token, $new_password) {
        if (strlen($new_password) < 8) {
            return ['success' => false, 'message' => 'Password must be at least 8 characters'];
        }
        
        $stmt = $this->conn->prepare("
            SELECT id FROM customers 
            WHERE password_reset_token = ? 
            AND password_reset_expires > NOW()
            AND is_active = 1
        ");
        $stmt->bind_param('s', $token);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return ['success' => false, 'message' => 'Invalid or expired reset token'];
        }
        
        $customer = $result->fetch_assoc();
        $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        
        $stmt = $this->conn->prepare("
            UPDATE customers 
            SET password = ?, password_reset_token = NULL, password_reset_expires = NULL 
            WHERE id = ?
        ");
        $stmt->bind_param('si', $password_hash, $customer['id']);
        $stmt->execute();
        
        $this->logActivity($customer['id'], 'password_reset', 'Password was reset');
        
        return ['success' => true, 'message' => 'Password reset successful'];
    }
    
    /**
     * Increment login attempts and lock if needed
     */
    private function incrementLoginAttempts($customer_id) {
        $this->conn->prepare("UPDATE customers SET login_attempts = login_attempts + 1 WHERE id = ?")
            ->bind_param('i', $customer_id)
            ->execute();
        
        $stmt = $this->conn->prepare("SELECT login_attempts FROM customers WHERE id = ?");
        $stmt->bind_param('i', $customer_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        // Lock account after 5 failed attempts
        if ($result['login_attempts'] >= 5) {
            $locked_until = date('Y-m-d H:i:s', strtotime('+30 minutes'));
            $this->conn->prepare("UPDATE customers SET locked_until = ? WHERE id = ?")
                ->bind_param('si', $locked_until, $customer_id)
                ->execute();
            
            $this->logActivity($customer_id, 'account_locked', 'Account locked due to failed login attempts');
        }
    }
    
    /**
     * Reset login attempts
     */
    private function resetLoginAttempts($customer_id) {
        $stmt = $this->conn->prepare("UPDATE customers SET login_attempts = 0, locked_until = NULL WHERE id = ?");
        $stmt->bind_param('i', $customer_id);
        $stmt->execute();
    }
    
    /**
     * Log customer activity
     */
    private function logActivity($customer_id, $type, $description) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        
        $stmt = $this->conn->prepare("
            INSERT INTO customer_activity_log (customer_id, activity_type, activity_description, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param('issss', $customer_id, $type, $description, $ip, $user_agent);
        $stmt->execute();
    }
    
    /**
     * Require login - redirect if not logged in
     */
    public function requireLogin($redirect_to = 'customer-login.php') {
        if (!$this->isLoggedIn()) {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            header('Location: ' . $redirect_to);
            exit;
        }
    }
}
?>
