<?php
/**
 * Firebase Configuration
 * 
 * This file loads Firebase project configuration from database settings
 * Fallback to default values if not configured in admin panel
 * 
 * Configure via: Admin Panel → Settings → Integrations → Firebase Authentication
 */

// Include database connection
if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
} else {
    // Fallback: Firebase not configured
    return [
        'enabled' => false,
        'apiKey' => 'YOUR_FIREBASE_API_KEY',
        'authDomain' => 'YOUR_PROJECT_ID.firebaseapp.com',
        'projectId' => 'YOUR_PROJECT_ID',
        'storageBucket' => 'YOUR_PROJECT_ID.appspot.com',
        'messagingSenderId' => 'YOUR_MESSAGING_SENDER_ID',
        'appId' => 'YOUR_APP_ID',
        'error' => 'Configuration file not found'
    ];
}

/**
 * Get Firebase configuration from database
 * @return array Firebase config array
 */
function getFirebaseConfig() {
    try {
        $db = Database::getInstance()->getConnection();
        
        // Fetch Firebase settings from database
        $settings = [];
        $keys = [
            'firebase_enabled',
            'firebase_api_key',
            'firebase_auth_domain',
            'firebase_project_id',
            'firebase_storage_bucket',
            'firebase_messaging_sender_id',
            'firebase_app_id'
        ];
        
        $placeholders = str_repeat('?,', count($keys) - 1) . '?';
        $stmt = $db->prepare("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ($placeholders)");
        $stmt->bind_param(str_repeat('s', count($keys)), ...$keys);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        
        // Check if Firebase is enabled and configured
        $enabled = isset($settings['firebase_enabled']) && $settings['firebase_enabled'] === '1';
        $apiKey = $settings['firebase_api_key'] ?? '';
        $authDomain = $settings['firebase_auth_domain'] ?? '';
        $projectId = $settings['firebase_project_id'] ?? '';
        $appId = $settings['firebase_app_id'] ?? '';
        
        // Return configuration if valid
        if ($enabled && !empty($apiKey) && !empty($authDomain) && !empty($projectId) && !empty($appId)) {
            return [
                'enabled' => true,
                'apiKey' => $apiKey,
                'authDomain' => $authDomain,
                'projectId' => $projectId,
                'storageBucket' => $settings['firebase_storage_bucket'] ?? $projectId . '.appspot.com',
                'messagingSenderId' => $settings['firebase_messaging_sender_id'] ?? '',
                'appId' => $appId
            ];
        }
        
        // Return disabled config with placeholder values
        return [
            'enabled' => false,
            'apiKey' => 'YOUR_FIREBASE_API_KEY',
            'authDomain' => 'YOUR_PROJECT_ID.firebaseapp.com',
            'projectId' => 'YOUR_PROJECT_ID',
            'storageBucket' => 'YOUR_PROJECT_ID.appspot.com',
            'messagingSenderId' => 'YOUR_MESSAGING_SENDER_ID',
            'appId' => 'YOUR_APP_ID',
            'error' => 'Firebase is not configured. Please configure in Admin Panel → Settings → Integrations.'
        ];
        
    } catch (Exception $e) {
        // Return fallback configuration on error
        error_log('Firebase config error: ' . $e->getMessage());
        return [
            'enabled' => false,
            'apiKey' => 'YOUR_FIREBASE_API_KEY',
            'authDomain' => 'YOUR_PROJECT_ID.firebaseapp.com',
            'projectId' => 'YOUR_PROJECT_ID',
            'storageBucket' => 'YOUR_PROJECT_ID.appspot.com',
            'messagingSenderId' => 'YOUR_MESSAGING_SENDER_ID',
            'appId' => 'YOUR_APP_ID',
            'error' => 'Database connection error: ' . $e->getMessage()
        ];
    }
}

// Return configuration (maintaining backward compatibility)
return getFirebaseConfig();
