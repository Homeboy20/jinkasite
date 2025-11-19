<?php
/**
 * Admin Authentication Class
 * 
 * Handles secure login, session management, and access control
 * for the admin dashboard.
 * 
 * @author ProCut Solutions
 * @version 1.0
 */

if (!defined('JINKA_ACCESS')) {
    define('JINKA_ACCESS', true);
}
require_once '../includes/config.php';

class AdminAuth {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Authenticate admin user
     */
    public function login($username, $password, $rememberMe = false) {
        try {
            // Check if user is locked out
            if ($this->isLockedOut($username)) {
                $lockoutTime = $this->getLockoutTime($username);
                return [
                    'success' => false,
                    'message' => 'Account locked due to too many failed attempts. Try again after ' . 
                                date('H:i:s', $lockoutTime)
                ];
            }
            
            // Get user from database using raw MySQLi
            $conn = $this->db->getConnection();
            $stmt = $conn->prepare("
                SELECT id, username, email, password_hash, full_name, role, is_active, login_attempts 
                FROM admin_users 
                WHERE (username = ? OR email = ?) AND is_active = 1
            ");
            $stmt->bind_param('ss', $username, $username);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result ? $result->fetch_assoc() : false;
            
            if (!$user) {
                $this->incrementLoginAttempts($username);
                return [
                    'success' => false,
                    'message' => 'Invalid username or password'
                ];
            }
            
            // Verify password
            if (!Security::verifyPassword($password, $user['password_hash'])) {
                $this->incrementLoginAttempts($username);
                return [
                    'success' => false,
                    'message' => 'Invalid username or password'
                ];
            }
            
            // Reset login attempts on successful login
            $this->resetLoginAttempts($user['id']);
            
            // Create session
            $this->createSession($user, $rememberMe);
            
            // Update last login
            $this->updateLastLogin($user['id']);
            
            // Log successful login
            Logger::info('Admin login successful', [
                'user_id' => $user['id'],
                'username' => $user['username'],
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            
            return [
                'success' => true,
                'message' => 'Login successful',
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'full_name' => $user['full_name'],
                    'role' => $user['role']
                ]
            ];
            
        } catch (Exception $e) {
            Logger::error('Login error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Login failed. Please try again.'
            ];
        }
    }
    
    /**
     * Create secure session
     */
    private function createSession($user, $rememberMe = false) {
        // Regenerate session ID for security
        session_regenerate_id(true);
        
        // Set session variables
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_id'] = $user['id'];
        $_SESSION['admin_username'] = $user['username'];
        $_SESSION['admin_role'] = $user['role'];
        $_SESSION['admin_full_name'] = $user['full_name'];
        $_SESSION['session_start'] = time();
        $_SESSION['last_activity'] = time();
        
        // Set remember me cookie if requested
        if ($rememberMe) {
            $token = bin2hex(random_bytes(32));
            $expiry = time() + (30 * 24 * 3600); // 30 days
            
            setcookie('remember_token', $token, $expiry, '/', '', false, true);
            
            // Store token in database
            $hashedToken = hash('sha256', $token);
            $stmt = $this->db->prepare("
                INSERT INTO admin_remember_tokens (user_id, token_hash, expires_at) 
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE token_hash = VALUES(token_hash), expires_at = VALUES(expires_at)
            ");
            $stmt->execute([$user['id'], $hashedToken, date('Y-m-d H:i:s', $expiry)]);
        }
    }
    
    /**
     * Check if user is authenticated
     */
    public function isAuthenticated() {
        if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
            return false;
        }
        
        // Check session timeout
        if (isset($_SESSION['last_activity']) && 
            (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
            $this->logout();
            return false;
        }
        
        // Update last activity
        $_SESSION['last_activity'] = time();
        
        return true;
    }
    
    /**
     * Check if user has required role
     */
    public function hasRole($requiredRole) {
        if (!$this->isAuthenticated()) {
            return false;
        }
        
        $userRole = $_SESSION['admin_role'] ?? '';
        $roleHierarchy = [
            'manager' => 1,
            'admin' => 2,
            'super_admin' => 3
        ];
        
        $userLevel = $roleHierarchy[$userRole] ?? 0;
        $requiredLevel = $roleHierarchy[$requiredRole] ?? 0;
        
        return $userLevel >= $requiredLevel;
    }
    
    /**
     * Get current user info
     */
    public function getCurrentUser() {
        if (!$this->isAuthenticated()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['admin_id'],
            'username' => $_SESSION['admin_username'],
            'full_name' => $_SESSION['admin_full_name'],
            'role' => $_SESSION['admin_role']
        ];
    }
    
    /**
     * Logout user
     */
    public function logout() {
        // Remove remember me cookie and token
        if (isset($_COOKIE['remember_token'])) {
            $token = $_COOKIE['remember_token'];
            $hashedToken = hash('sha256', $token);
            
            $stmt = $this->db->prepare("DELETE FROM admin_remember_tokens WHERE token_hash = ?");
            $stmt->execute([$hashedToken]);
            
            setcookie('remember_token', '', time() - 3600, '/', '', false, true);
        }
        
        // Log logout
        if (isset($_SESSION['admin_id'])) {
            Logger::info('Admin logout', [
                'user_id' => $_SESSION['admin_id'],
                'username' => $_SESSION['admin_username'] ?? 'unknown'
            ]);
        }
        
        // Clear session
        session_unset();
        session_destroy();
        
        // Start new session
        session_start();
        session_regenerate_id(true);
    }
    
    /**
     * Check if account is locked out
     */
    private function isLockedOut($username) {
        // Use raw MySQLi connection directly to bypass wrapper issues
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("
            SELECT lockout_until FROM admin_users 
            WHERE (username = ? OR email = ?) AND lockout_until > NOW()
        ");
        $stmt->bind_param('ss', $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result && $result->num_rows > 0;
    }
    
    /**
     * Get lockout time
     */
    private function getLockoutTime($username) {
        $stmt = $this->db->prepare("
            SELECT UNIX_TIMESTAMP(lockout_until) as lockout_time FROM admin_users 
            WHERE (username = ? OR email = ?) AND lockout_until > NOW()
        ");
        $stmt->execute([$username, $username]);
        $result = $stmt->fetch();
        
        return $result ? $result['lockout_time'] : 0;
    }
    
    /**
     * Increment login attempts
     */
    private function incrementLoginAttempts($username) {
        $stmt = $this->db->prepare("
            UPDATE admin_users 
            SET login_attempts = login_attempts + 1,
                lockout_until = CASE 
                    WHEN login_attempts + 1 >= ? THEN DATE_ADD(NOW(), INTERVAL ? SECOND)
                    ELSE lockout_until 
                END
            WHERE username = ? OR email = ?
        ");
        $stmt->execute([MAX_LOGIN_ATTEMPTS, LOCKOUT_DURATION, $username, $username]);
    }
    
    /**
     * Reset login attempts
     */
    private function resetLoginAttempts($userId) {
        $stmt = $this->db->prepare("
            UPDATE admin_users 
            SET login_attempts = 0, lockout_until = NULL 
            WHERE id = ?
        ");
        $stmt->execute([$userId]);
    }
    
    /**
     * Update last login time
     */
    private function updateLastLogin($userId) {
        $stmt = $this->db->prepare("UPDATE admin_users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$userId]);
    }
    
    /**
     * Change password
     */
    public function changePassword($userId, $currentPassword, $newPassword) {
        try {
            // Get current password hash
            $stmt = $this->db->prepare("SELECT password_hash FROM admin_users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            if (!$user) {
                return ['success' => false, 'message' => 'User not found'];
            }
            
            // Verify current password
            if (!Security::verifyPassword($currentPassword, $user['password_hash'])) {
                return ['success' => false, 'message' => 'Current password is incorrect'];
            }
            
            // Validate new password
            $passwordErrors = Security::validatePassword($newPassword);
            if (!empty($passwordErrors)) {
                return ['success' => false, 'message' => implode('. ', $passwordErrors)];
            }
            
            // Update password
            $newHash = Security::hashPassword($newPassword);
            $stmt = $this->db->prepare("UPDATE admin_users SET password_hash = ? WHERE id = ?");
            $stmt->execute([$newHash, $userId]);
            
            Logger::info('Password changed', ['user_id' => $userId]);
            
            return ['success' => true, 'message' => 'Password changed successfully'];
            
        } catch (Exception $e) {
            Logger::error('Password change error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to change password'];
        }
    }
    
    /**
     * Create new admin user
     */
    public function createUser($data) {
        try {
            // Validate required fields
            $required = ['username', 'email', 'password', 'full_name', 'role'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    return ['success' => false, 'message' => ucfirst($field) . ' is required'];
                }
            }
            
            // Validate password
            $passwordErrors = Security::validatePassword($data['password']);
            if (!empty($passwordErrors)) {
                return ['success' => false, 'message' => implode('. ', $passwordErrors)];
            }
            
            // Check if username/email already exists
            $stmt = $this->db->prepare("SELECT id FROM admin_users WHERE username = ? OR email = ?");
            $stmt->execute([$data['username'], $data['email']]);
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'Username or email already exists'];
            }
            
            // Create user
            $passwordHash = Security::hashPassword($data['password']);
            $stmt = $this->db->prepare("
                INSERT INTO admin_users (username, email, password_hash, full_name, role) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $data['username'],
                $data['email'],
                $passwordHash,
                $data['full_name'],
                $data['role']
            ]);
            
            $userId = $this->db->lastInsertId();
            
            Logger::info('New admin user created', [
                'user_id' => $userId,
                'username' => $data['username'],
                'created_by' => $_SESSION['admin_id'] ?? 'system'
            ]);
            
            return ['success' => true, 'message' => 'User created successfully', 'user_id' => $userId];
            
        } catch (Exception $e) {
            Logger::error('User creation error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to create user'];
        }
    }
    
    /**
     * Update user
     */
    public function updateUser($userId, $data) {
        try {
            $updates = [];
            $params = [];
            
            // Build dynamic update query
            $allowedFields = ['username', 'email', 'full_name', 'role', 'is_active'];
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updates[] = "$field = ?";
                    $params[] = $data[$field];
                }
            }
            
            if (empty($updates)) {
                return ['success' => false, 'message' => 'No data to update'];
            }
            
            $params[] = $userId;
            
            $sql = "UPDATE admin_users SET " . implode(', ', $updates) . " WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            Logger::info('Admin user updated', [
                'user_id' => $userId,
                'updated_by' => $_SESSION['admin_id'] ?? 'system'
            ]);
            
            return ['success' => true, 'message' => 'User updated successfully'];
            
        } catch (Exception $e) {
            Logger::error('User update error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to update user'];
        }
    }
    
    /**
     * Delete user
     */
    public function deleteUser($userId) {
        try {
            // Cannot delete current user
            if ($userId == ($_SESSION['admin_id'] ?? 0)) {
                return ['success' => false, 'message' => 'Cannot delete your own account'];
            }
            
            $stmt = $this->db->prepare("DELETE FROM admin_users WHERE id = ?");
            $stmt->execute([$userId]);
            
            Logger::info('Admin user deleted', [
                'user_id' => $userId,
                'deleted_by' => $_SESSION['admin_id'] ?? 'system'
            ]);
            
            return ['success' => true, 'message' => 'User deleted successfully'];
            
        } catch (Exception $e) {
            Logger::error('User deletion error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to delete user'];
        }
    }
}

/**
 * Initialize authentication for admin pages
 */
function requireAuth($requiredRole = 'admin') {
    $auth = new AdminAuth();
    
    if (!$auth->isAuthenticated()) {
        if (isAjaxRequest()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Authentication required']);
            exit;
        } else {
            redirect('login.php', 'Please login to continue', 'warning');
        }
    }
    
    if (!$auth->hasRole($requiredRole)) {
        if (isAjaxRequest()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Insufficient privileges']);
            exit;
        } else {
            redirect('dashboard.php', 'Insufficient privileges', 'error');
        }
    }
    
    return $auth;
}

/**
 * Check if request is AJAX
 */
function isAjaxRequest() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}
?>