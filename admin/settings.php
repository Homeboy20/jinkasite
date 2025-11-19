<?php
if (!defined('JINKA_ACCESS')) {
    define('JINKA_ACCESS', true);
}
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'];
$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$siteBaseUrl = $protocol . $host . ($basePath !== '' ? $basePath : '');
require_once 'includes/auth.php';
// Require authentication
$auth = requireAuth('admin');
$currentUser = $auth->getCurrentUser();

$db = Database::getInstance()->getConnection();
$message = '';
$messageType = '';

// Handle form submissions
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_general') {
        $site_name = Security::sanitizeInput($_POST['site_name']);
    $site_description = Security::sanitizeInput($_POST['site_description']);
    $site_tagline = Security::sanitizeInput($_POST['site_tagline'] ?? '');
        $site_url = Security::sanitizeInput($_POST['site_url']);
        $contact_email = Security::sanitizeInput($_POST['contact_email']);
        $contact_phone = Security::sanitizeInput($_POST['contact_phone']);
        $business_address = Security::sanitizeInput($_POST['business_address']);
        $business_hours = Security::sanitizeInput($_POST['business_hours']);
        $currency = Security::sanitizeInput($_POST['currency']);
        $timezone = Security::sanitizeInput($_POST['timezone']);

    $existingLogo = getSetting('site_logo', '');
    $site_logo = $existingLogo;
    $logoUploadError = '';

    $existingFavicon = getSetting('site_favicon', '');
    $site_favicon = $existingFavicon;
    $faviconUploadError = '';

        if (!empty($_POST['site_logo_remove'])) {
            if ($existingLogo && strpos($existingLogo, 'uploads/') === 0) {
                $logoPath = __DIR__ . '/../' . $existingLogo;
                if (file_exists($logoPath)) {
                    @unlink($logoPath);
                }
            }
            $site_logo = '';
        }

        if (isset($_FILES['site_logo']) && $_FILES['site_logo']['error'] !== UPLOAD_ERR_NO_FILE) {
            $upload = $_FILES['site_logo'];

            if ($upload['error'] !== UPLOAD_ERR_OK) {
                $logoUploadError = 'Failed to upload logo. Please try again.';
            } elseif ($upload['size'] > MAX_FILE_SIZE) {
                $logoUploadError = 'Logo file is too large. Maximum size is ' . round(MAX_FILE_SIZE / (1024 * 1024), 1) . 'MB.';
            } else {
                $allowedExtensions = ['png', 'jpg', 'jpeg', 'webp', 'svg'];
                $extension = strtolower(pathinfo($upload['name'], PATHINFO_EXTENSION));

                if (!in_array($extension, $allowedExtensions, true)) {
                    $logoUploadError = 'Invalid logo format. Allowed types: PNG, JPG, WEBP, SVG.';
                } else {
                    $uploadDir = __DIR__ . '/../uploads/branding';
                    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
                        $logoUploadError = 'Unable to create upload directory.';
                    } else {
                        $newFileName = 'logo_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
                        $targetPath = $uploadDir . '/' . $newFileName;

                        if (!move_uploaded_file($upload['tmp_name'], $targetPath)) {
                            $logoUploadError = 'Unable to save the uploaded logo. Please check folder permissions.';
                        } else {
                            if ($existingLogo && strpos($existingLogo, 'uploads/') === 0) {
                                $oldLogoPath = __DIR__ . '/../' . $existingLogo;
                                if (file_exists($oldLogoPath)) {
                                    @unlink($oldLogoPath);
                                }
                            }
                            $site_logo = 'uploads/branding/' . $newFileName;
                        }
                    }
                }
            }
        }

        if (!empty($_POST['site_favicon_remove'])) {
            if ($existingFavicon && strpos($existingFavicon, 'uploads/') === 0) {
                $faviconPath = __DIR__ . '/../' . $existingFavicon;
                if (file_exists($faviconPath)) {
                    @unlink($faviconPath);
                }
            }
            $site_favicon = '';
        }

        if (isset($_FILES['site_favicon']) && $_FILES['site_favicon']['error'] !== UPLOAD_ERR_NO_FILE) {
            $upload = $_FILES['site_favicon'];

            if ($upload['error'] !== UPLOAD_ERR_OK) {
                $faviconUploadError = 'Failed to upload favicon. Please try again.';
            } elseif ($upload['size'] > MAX_FILE_SIZE) {
                $faviconUploadError = 'Favicon file is too large. Maximum size is ' . round(MAX_FILE_SIZE / (1024 * 1024), 1) . 'MB.';
            } else {
                $allowedExtensions = ['png', 'jpg', 'jpeg', 'webp', 'svg', 'ico'];
                $extension = strtolower(pathinfo($upload['name'], PATHINFO_EXTENSION));

                if (!in_array($extension, $allowedExtensions, true)) {
                    $faviconUploadError = 'Invalid favicon format. Allowed types: ICO, PNG, JPG, WEBP, SVG.';
                } else {
                    $uploadDir = __DIR__ . '/../uploads/branding';
                    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
                        $faviconUploadError = 'Unable to create upload directory for favicon.';
                    } else {
                        $newFileName = 'favicon_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
                        $targetPath = $uploadDir . '/' . $newFileName;

                        if (!move_uploaded_file($upload['tmp_name'], $targetPath)) {
                            $faviconUploadError = 'Unable to save the uploaded favicon. Please check folder permissions.';
                        } else {
                            if ($existingFavicon && strpos($existingFavicon, 'uploads/') === 0) {
                                $oldFaviconPath = __DIR__ . '/../' . $existingFavicon;
                                if (file_exists($oldFaviconPath)) {
                                    @unlink($oldFaviconPath);
                                }
                            }
                            $site_favicon = 'uploads/branding/' . $newFileName;
                        }
                    }
                }
            }
        }
        
        $uploadErrors = array_filter([$logoUploadError, $faviconUploadError]);

        if (!empty($uploadErrors)) {
            $message = implode(' ', $uploadErrors);
            $messageType = 'error';
        } else {
            $settings = [
                'site_name' => $site_name,
                'site_description' => $site_description,
                'site_tagline' => $site_tagline,
                'site_url' => $site_url,
                'contact_email' => $contact_email,
                'contact_phone' => $contact_phone,
                'business_address' => $business_address,
                'business_hours' => $business_hours,
                'currency' => $currency,
                'timezone' => $timezone,
                'site_logo' => $site_logo,
                'site_favicon' => $site_favicon
            ];
            
            $success = true;
            foreach ($settings as $key => $value) {
                $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value, updated_at) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()");
                $stmt->bind_param('sss', $key, $value, $value);
                if (!$stmt->execute()) {
                    $success = false;
                    break;
                }
            }
            
            if ($success) {
                $message = 'General settings updated successfully!';
                $messageType = 'success';
            } else {
                $message = 'Error updating settings: ' . $db->error;
                $messageType = 'error';
            }
        }
    } elseif ($action === 'update_email') {
        $smtp_host = Security::sanitizeInput($_POST['smtp_host']);
        $smtp_port = (int)$_POST['smtp_port'];
        $smtp_username = Security::sanitizeInput($_POST['smtp_username']);
        $smtp_password = $_POST['smtp_password']; // Don't sanitize password
        $smtp_encryption = Security::sanitizeInput($_POST['smtp_encryption']);
        $from_email = Security::sanitizeInput($_POST['from_email']);
        $from_name = Security::sanitizeInput($_POST['from_name']);
        $admin_email = Security::sanitizeInput($_POST['admin_email']);
        
        $email_settings = [
            'smtp_host' => $smtp_host,
            'smtp_port' => $smtp_port,
            'smtp_username' => $smtp_username,
            'smtp_password' => $smtp_password,
            'smtp_encryption' => $smtp_encryption,
            'from_email' => $from_email,
            'from_name' => $from_name,
            'admin_email' => $admin_email
        ];
        
        $success = true;
        foreach ($email_settings as $key => $value) {
            $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value, updated_at) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()");
            $stmt->bind_param('sss', $key, $value, $value);
            if (!$stmt->execute()) {
                $success = false;
                break;
            }
        }
        
        if ($success) {
            $message = 'Email settings updated successfully!';
            $messageType = 'success';
        } else {
            $message = 'Error updating email settings: ' . $db->error;
            $messageType = 'error';
        }
    } elseif ($action === 'update_seo') {
        $meta_title = Security::sanitizeInput($_POST['meta_title']);
        $meta_description = Security::sanitizeInput($_POST['meta_description']);
        $meta_keywords = Security::sanitizeInput($_POST['meta_keywords']);
        $google_analytics = Security::sanitizeInput($_POST['google_analytics']);
        $facebook_pixel = Security::sanitizeInput($_POST['facebook_pixel']);
        $robots_txt = Security::sanitizeInput($_POST['robots_txt']);
        $sitemap_url = Security::sanitizeInput($_POST['sitemap_url']);
        
        $seo_settings = [
            'meta_title' => $meta_title,
            'meta_description' => $meta_description,
            'meta_keywords' => $meta_keywords,
            'google_analytics' => $google_analytics,
            'facebook_pixel' => $facebook_pixel,
            'robots_txt' => $robots_txt,
            'sitemap_url' => $sitemap_url
        ];
        
        $success = true;
        foreach ($seo_settings as $key => $value) {
            $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value, updated_at) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()");
            $stmt->bind_param('sss', $key, $value, $value);
            if (!$stmt->execute()) {
                $success = false;
                break;
            }
        }
        
        if ($success) {
            $message = 'SEO settings updated successfully!';
            $messageType = 'success';
        } else {
            $message = 'Error updating SEO settings: ' . $db->error;
            $messageType = 'error';
        }
    } elseif ($action === 'update_social') {
        $facebook_url = Security::sanitizeInput($_POST['facebook_url']);
        $twitter_url = Security::sanitizeInput($_POST['twitter_url']);
        $instagram_url = Security::sanitizeInput($_POST['instagram_url']);
        $linkedin_url = Security::sanitizeInput($_POST['linkedin_url']);
        $youtube_url = Security::sanitizeInput($_POST['youtube_url']);
        $whatsapp_number = Security::sanitizeInput($_POST['whatsapp_number']);
        
        $social_settings = [
            'facebook_url' => $facebook_url,
            'twitter_url' => $twitter_url,
            'instagram_url' => $instagram_url,
            'linkedin_url' => $linkedin_url,
            'youtube_url' => $youtube_url,
            'whatsapp_number' => $whatsapp_number
        ];
        
        $success = true;
        foreach ($social_settings as $key => $value) {
            $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value, updated_at) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()");
            $stmt->bind_param('sss', $key, $value, $value);
            if (!$stmt->execute()) {
                $success = false;
                break;
            }
        }
        
        if ($success) {
            $message = 'Social media settings updated successfully!';
            $messageType = 'success';
        } else {
            $message = 'Error updating social media settings: ' . $db->error;
            $messageType = 'error';
        }
    } elseif ($action === 'update_ai') {
        $ai_default_provider = Security::sanitizeInput($_POST['ai_default_provider']);
        $ai_deepseek_key = $_POST['ai_deepseek_key']; // Don't sanitize API keys
        $ai_kimi_key = $_POST['ai_kimi_key'];
        $ai_openai_key = $_POST['ai_openai_key'];
        $ai_openai_model = Security::sanitizeInput($_POST['ai_openai_model']);
        $ai_enabled = isset($_POST['ai_enabled']) ? '1' : '0';
        
        $ai_settings = [
            'ai_default_provider' => $ai_default_provider,
            'ai_deepseek_key' => $ai_deepseek_key,
            'ai_kimi_key' => $ai_kimi_key,
            'ai_openai_key' => $ai_openai_key,
            'ai_openai_model' => $ai_openai_model,
            'ai_enabled' => $ai_enabled
        ];
        
        $success = true;
        foreach ($ai_settings as $key => $value) {
            $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value, updated_at) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()");
            $stmt->bind_param('sss', $key, $value, $value);
            if (!$stmt->execute()) {
                $success = false;
                break;
            }
        }
        
        if ($success) {
            $message = 'AI settings updated successfully!';
            $messageType = 'success';
        } else {
            $message = 'Error updating AI settings: ' . $db->error;
            $messageType = 'error';
        }
    } elseif ($action === 'update_payments') {
        $payment_sandbox_mode = isset($_POST['payment_sandbox_mode']) ? '1' : '0';

        $payment_settings = [
            'payment_sandbox_mode' => $payment_sandbox_mode,
            'pesapal_enabled' => isset($_POST['pesapal_enabled']) ? '1' : '0',
            'pesapal_consumer_key' => trim($_POST['pesapal_consumer_key'] ?? ''),
            'pesapal_consumer_secret' => trim($_POST['pesapal_consumer_secret'] ?? ''),
            'pesapal_ipn_id' => trim($_POST['pesapal_ipn_id'] ?? ''),
            'pesapal_callback_url' => trim($_POST['pesapal_callback_url'] ?? ''),
            'flutterwave_enabled' => isset($_POST['flutterwave_enabled']) ? '1' : '0',
            'flutterwave_public_key' => trim($_POST['flutterwave_public_key'] ?? ''),
            'flutterwave_secret_key' => trim($_POST['flutterwave_secret_key'] ?? ''),
            'flutterwave_encryption_key' => trim($_POST['flutterwave_encryption_key'] ?? ''),
            'flutterwave_redirect_url' => trim($_POST['flutterwave_redirect_url'] ?? ''),
            'azampay_enabled' => isset($_POST['azampay_enabled']) ? '1' : '0',
            'azampay_app_name' => trim($_POST['azampay_app_name'] ?? ''),
            'azampay_client_id' => trim($_POST['azampay_client_id'] ?? ''),
            'azampay_client_secret' => trim($_POST['azampay_client_secret'] ?? ''),
            'azampay_api_key' => trim($_POST['azampay_api_key'] ?? ''),
            'azampay_account_number' => trim($_POST['azampay_account_number'] ?? ''),
            'azampay_callback_url' => trim($_POST['azampay_callback_url'] ?? ''),
            'mpesa_enabled' => isset($_POST['mpesa_enabled']) ? '1' : '0',
            'mpesa_consumer_key' => trim($_POST['mpesa_consumer_key'] ?? ''),
            'mpesa_consumer_secret' => trim($_POST['mpesa_consumer_secret'] ?? ''),
            'mpesa_shortcode' => trim($_POST['mpesa_shortcode'] ?? ''),
            'mpesa_passkey' => trim($_POST['mpesa_passkey'] ?? ''),
            'mpesa_callback_url' => trim($_POST['mpesa_callback_url'] ?? ''),
            'paypal_enabled' => isset($_POST['paypal_enabled']) ? '1' : '0',
            'paypal_client_id' => trim($_POST['paypal_client_id'] ?? ''),
            'paypal_client_secret' => trim($_POST['paypal_client_secret'] ?? ''),
            'paypal_webhook_id' => trim($_POST['paypal_webhook_id'] ?? ''),
            'paypal_return_url' => trim($_POST['paypal_return_url'] ?? ''),
            'paypal_cancel_url' => trim($_POST['paypal_cancel_url'] ?? ''),
            'stripe_enabled' => isset($_POST['stripe_enabled']) ? '1' : '0',
            'stripe_publishable_key' => trim($_POST['stripe_publishable_key'] ?? ''),
            'stripe_secret_key' => trim($_POST['stripe_secret_key'] ?? ''),
            'stripe_webhook_secret' => trim($_POST['stripe_webhook_secret'] ?? ''),
            'stripe_success_url' => trim($_POST['stripe_success_url'] ?? ''),
            'stripe_cancel_url' => trim($_POST['stripe_cancel_url'] ?? '')
        ];

        $success = true;
        foreach ($payment_settings as $key => $value) {
            $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value, updated_at) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()");
            $stmt->bind_param('sss', $key, $value, $value);
            if (!$stmt->execute()) {
                $success = false;
                break;
            }
        }

        if ($success) {
            $message = 'Payment gateway settings updated successfully!';
            $messageType = 'success';
        } else {
            $message = 'Error updating payment gateway settings: ' . $db->error;
            $messageType = 'error';
        }
    } elseif ($action === 'manage_cache') {
        $cacheAction = $_POST['cache_action'] ?? 'save';
        $cacheEnabled = isset($_POST['cache_enabled']) ? '1' : '0';
        $cacheDurationMinutes = isset($_POST['cache_duration']) ? (int)$_POST['cache_duration'] : 60;

        if ($cacheDurationMinutes < 5) {
            $cacheDurationMinutes = 5;
        } elseif ($cacheDurationMinutes > 1440) {
            $cacheDurationMinutes = 1440;
        }

        $cacheDurationSeconds = (string)($cacheDurationMinutes * 60);

        $cache_settings = [
            'cache_enabled' => $cacheEnabled,
            'cache_duration' => $cacheDurationSeconds
        ];

        $success = true;
        foreach ($cache_settings as $key => $value) {
            $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value, updated_at) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()");
            if (!$stmt) {
                $success = false;
                break;
            }
            $stmt->bind_param('sss', $key, $value, $value);
            if (!$stmt->execute()) {
                $success = false;
                break;
            }
        }

        if ($success) {
            if ($cacheAction === 'clear') {
                $clearStats = cache_clear();
                $lastCleared = date('Y-m-d H:i:s');
                $key = 'cache_last_cleared';
                $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value, updated_at) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()");

                if ($stmt) {
                    $stmt->bind_param('sss', $key, $lastCleared, $lastCleared);
                    if ($stmt->execute()) {
                        $removedFiles = number_format($clearStats['files']);
                        $freedSize = cache_format_bytes($clearStats['bytes']);
                        $message = 'Cache cleared successfully. Removed ' . $removedFiles . ' file' . ($clearStats['files'] === 1 ? '' : 's') . ' and freed ' . $freedSize . '.';
                        $messageType = 'success';
                    } else {
                        $message = 'Cache cleared but failed to record timestamp: ' . $db->error;
                        $messageType = 'error';
                    }
                } else {
                    $message = 'Cache cleared but failed to prepare timestamp storage: ' . $db->error;
                    $messageType = 'error';
                }
            } else {
                $message = 'Cache settings updated successfully!';
                $messageType = 'success';
            }
        } else {
            $message = 'Error updating cache settings: ' . $db->error;
            $messageType = 'error';
        }
    }
}

// Get current settings
function getSetting($key, $default = '') {
    global $db;
    $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->bind_param('s', $key);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return $result ? $result['setting_value'] : $default;
}

// Get all current settings for easier access
$settings = [
    // General
    'site_name' => getSetting('site_name', 'JINKA Plotter Solutions'),
    'site_description' => getSetting('site_description', 'Professional plotting and cutting solutions'),
    'site_tagline' => getSetting('site_tagline', 'Professional Printing Equipment'),
    'site_url' => getSetting('site_url', 'https://jinkaplotters.com'),
    'site_logo' => getSetting('site_logo', ''),
    'site_favicon' => getSetting('site_favicon', ''),
    'contact_email' => getSetting('contact_email', 'info@jinkaplotters.com'),
    'contact_phone' => getSetting('contact_phone', '+254 700 000 000'),
    'business_address' => getSetting('business_address', 'Nairobi, Kenya'),
    'business_hours' => getSetting('business_hours', 'Mon-Fri: 8AM-6PM, Sat: 9AM-4PM'),
    'currency' => getSetting('currency', 'KES'),
    'timezone' => getSetting('timezone', 'Africa/Nairobi'),

    // Cache
    'cache_enabled' => getSetting('cache_enabled', defined('CACHE_ENABLED') && CACHE_ENABLED ? '1' : '0'),
    'cache_duration' => getSetting('cache_duration', '3600'),
    'cache_last_cleared' => getSetting('cache_last_cleared', ''),
    
    // Email
    'smtp_host' => getSetting('smtp_host', 'smtp.gmail.com'),
    'smtp_port' => getSetting('smtp_port', '587'),
    'smtp_username' => getSetting('smtp_username'),
    'smtp_password' => getSetting('smtp_password'),
    'smtp_encryption' => getSetting('smtp_encryption', 'tls'),
    'from_email' => getSetting('from_email', 'noreply@jinkaplotters.com'),
    'from_name' => getSetting('from_name', 'JINKA Plotters'),
    'admin_email' => getSetting('admin_email', 'admin@jinkaplotters.com'),
    
    // SEO
    'meta_title' => getSetting('meta_title', 'JINKA Plotter Solutions - Professional Plotting Equipment'),
    'meta_description' => getSetting('meta_description', 'Leading provider of professional plotting and cutting solutions in Kenya. High-quality equipment, expert service, competitive prices.'),
    'meta_keywords' => getSetting('meta_keywords', 'plotter, cutting, vinyl, signage, Kenya, Nairobi'),
    'google_analytics' => getSetting('google_analytics'),
    'facebook_pixel' => getSetting('facebook_pixel'),
    'robots_txt' => getSetting('robots_txt', "User-agent: *\nAllow: /"),
    'sitemap_url' => getSetting('sitemap_url', '/sitemap.xml'),
    
    // Social Media
    'facebook_url' => getSetting('facebook_url'),
    'twitter_url' => getSetting('twitter_url'),
    'instagram_url' => getSetting('instagram_url'),
    'linkedin_url' => getSetting('linkedin_url'),
    'youtube_url' => getSetting('youtube_url'),
    'whatsapp_number' => getSetting('whatsapp_number', '+254700000000'),
    
    // Payment Gateways
    'payment_sandbox_mode' => getSetting('payment_sandbox_mode', defined('PAYMENT_USE_SANDBOX') && PAYMENT_USE_SANDBOX ? '1' : '0'),
    'pesapal_enabled' => getSetting('pesapal_enabled', '0'),
    'pesapal_consumer_key' => getSetting('pesapal_consumer_key', defined('PESAPAL_CONSUMER_KEY') ? PESAPAL_CONSUMER_KEY : ''),
    'pesapal_consumer_secret' => getSetting('pesapal_consumer_secret', defined('PESAPAL_CONSUMER_SECRET') ? PESAPAL_CONSUMER_SECRET : ''),
    'pesapal_ipn_id' => getSetting('pesapal_ipn_id', defined('PESAPAL_IPN_ID') ? PESAPAL_IPN_ID : ''),
    'pesapal_callback_url' => getSetting('pesapal_callback_url', defined('PESAPAL_CALLBACK_URL') ? PESAPAL_CALLBACK_URL : ''),
    'flutterwave_enabled' => getSetting('flutterwave_enabled', '1'),
    'flutterwave_public_key' => getSetting('flutterwave_public_key', defined('FLUTTERWAVE_PUBLIC_KEY') ? FLUTTERWAVE_PUBLIC_KEY : ''),
    'flutterwave_secret_key' => getSetting('flutterwave_secret_key', defined('FLUTTERWAVE_SECRET_KEY') ? FLUTTERWAVE_SECRET_KEY : ''),
    'flutterwave_encryption_key' => getSetting('flutterwave_encryption_key', defined('FLUTTERWAVE_ENCRYPTION_KEY') ? FLUTTERWAVE_ENCRYPTION_KEY : ''),
    'flutterwave_redirect_url' => getSetting('flutterwave_redirect_url', defined('FLUTTERWAVE_REDIRECT_URL') ? FLUTTERWAVE_REDIRECT_URL : ''),
    'azampay_enabled' => getSetting('azampay_enabled', '0'),
    'azampay_app_name' => getSetting('azampay_app_name', defined('AZAMPAY_APP_NAME') ? AZAMPAY_APP_NAME : ''),
    'azampay_client_id' => getSetting('azampay_client_id', defined('AZAMPAY_CLIENT_ID') ? AZAMPAY_CLIENT_ID : ''),
    'azampay_client_secret' => getSetting('azampay_client_secret', defined('AZAMPAY_CLIENT_SECRET') ? AZAMPAY_CLIENT_SECRET : ''),
    'azampay_api_key' => getSetting('azampay_api_key', defined('AZAMPAY_API_KEY') ? AZAMPAY_API_KEY : ''),
    'azampay_account_number' => getSetting('azampay_account_number', defined('AZAMPAY_ACCOUNT_NUMBER') ? AZAMPAY_ACCOUNT_NUMBER : ''),
    'azampay_callback_url' => getSetting('azampay_callback_url', defined('AZAMPAY_CALLBACK_URL') ? AZAMPAY_CALLBACK_URL : ''),
    'mpesa_enabled' => getSetting('mpesa_enabled', '0'),
    'mpesa_consumer_key' => getSetting('mpesa_consumer_key', defined('MPESA_CONSUMER_KEY') ? MPESA_CONSUMER_KEY : ''),
    'mpesa_consumer_secret' => getSetting('mpesa_consumer_secret', defined('MPESA_CONSUMER_SECRET') ? MPESA_CONSUMER_SECRET : ''),
    'mpesa_shortcode' => getSetting('mpesa_shortcode', defined('MPESA_SHORTCODE') ? MPESA_SHORTCODE : ''),
    'mpesa_passkey' => getSetting('mpesa_passkey', defined('MPESA_PASSKEY') ? MPESA_PASSKEY : ''),
    'mpesa_callback_url' => getSetting('mpesa_callback_url', defined('MPESA_CALLBACK_URL') ? MPESA_CALLBACK_URL : ''),
    'paypal_enabled' => getSetting('paypal_enabled', '0'),
    'paypal_client_id' => getSetting('paypal_client_id', defined('PAYPAL_CLIENT_ID') ? PAYPAL_CLIENT_ID : ''),
    'paypal_client_secret' => getSetting('paypal_client_secret', defined('PAYPAL_CLIENT_SECRET') ? PAYPAL_CLIENT_SECRET : ''),
    'paypal_webhook_id' => getSetting('paypal_webhook_id', defined('PAYPAL_WEBHOOK_ID') ? PAYPAL_WEBHOOK_ID : ''),
    'paypal_return_url' => getSetting('paypal_return_url', defined('PAYPAL_RETURN_URL') ? PAYPAL_RETURN_URL : SITE_URL . '/payment-callback/paypal/success'),
    'paypal_cancel_url' => getSetting('paypal_cancel_url', defined('PAYPAL_CANCEL_URL') ? PAYPAL_CANCEL_URL : SITE_URL . '/payment-callback/paypal/cancel'),
    'stripe_enabled' => getSetting('stripe_enabled', '0'),
    'stripe_publishable_key' => getSetting('stripe_publishable_key', defined('STRIPE_PUBLISHABLE_KEY') ? STRIPE_PUBLISHABLE_KEY : ''),
    'stripe_secret_key' => getSetting('stripe_secret_key', defined('STRIPE_SECRET_KEY') ? STRIPE_SECRET_KEY : ''),
    'stripe_webhook_secret' => getSetting('stripe_webhook_secret', defined('STRIPE_WEBHOOK_SECRET') ? STRIPE_WEBHOOK_SECRET : ''),
    'stripe_success_url' => getSetting('stripe_success_url', defined('STRIPE_SUCCESS_URL') ? STRIPE_SUCCESS_URL : SITE_URL . '/payment-callback/stripe/success'),
    'stripe_cancel_url' => getSetting('stripe_cancel_url', defined('STRIPE_CANCEL_URL') ? STRIPE_CANCEL_URL : SITE_URL . '/payment-callback/stripe/cancel'),

    // AI Configuration
    'ai_enabled' => getSetting('ai_enabled', '1'),
    'ai_default_provider' => getSetting('ai_default_provider', 'deepseek'),
    'ai_deepseek_key' => getSetting('ai_deepseek_key', ''),
    'ai_kimi_key' => getSetting('ai_kimi_key', ''),
    'ai_openai_key' => getSetting('ai_openai_key', ''),
    'ai_openai_model' => getSetting('ai_openai_model', 'gpt-4o-mini')
];

$cacheStats = cache_stats();
$cacheFilesFormatted = number_format($cacheStats['files']);
$cacheSizeFormatted = cache_format_bytes($cacheStats['size']);
$cacheDurationSeconds = (int)$settings['cache_duration'];
if ($cacheDurationSeconds <= 0) {
    $cacheDurationSeconds = 3600;
}
$cacheDurationMinutes = max(5, (int)round($cacheDurationSeconds / 60));
$cacheLastClearedRaw = $settings['cache_last_cleared'];
$cacheLastClearedDisplay = 'Never';
if (!empty($cacheLastClearedRaw)) {
    $timestamp = strtotime($cacheLastClearedRaw);
    if ($timestamp !== false) {
        $cacheLastClearedDisplay = date('M j, Y g:i A', $timestamp);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - JINKA Admin</title>
    <link href="../css/style.css" rel="stylesheet">
    <link href="css/admin.css" rel="stylesheet">
    <style>
        .payment-hero {
            background: linear-gradient(135deg, #2563eb 0%, #312e81 100%);
            border-radius: 16px;
            padding: 1.75rem;
            color: #fff;
            margin-bottom: 1.5rem;
        }
        .payment-summary h4 {
            margin: 0 0 0.5rem;
            font-size: 1.3rem;
            font-weight: 700;
        }
        .switch-label {
            display: flex;
            gap: 0.7rem;
            align-items: center;
            margin-top: 1rem;
            font-weight: 600;
        }
        .switch-label input[type="checkbox"] {
            width: 18px;
            height: 18px;
        }
        .payment-subnav {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin: 2rem 0 1.5rem;
        }
        .payment-subtab {
            border: 1px solid #dbe4ff;
            border-radius: 999px;
            padding: 0.6rem 1.4rem;
            background: #f8fafc;
            color: #1d4ed8;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .payment-subtab.active {
            background: #1d4ed8;
            color: #fff;
            box-shadow: 0 10px 25px rgba(29, 78, 216, 0.25);
        }
        .payment-subpanel {
            display: none;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 1.75rem;
            margin-bottom: 1.5rem;
            background: #f8fafc;
        }
        .payment-subpanel.active {
            display: block;
        }
        .info-banner {
            background: #e0f2fe;
            border-left: 4px solid #0284c7;
            padding: 0.85rem 1rem;
            border-radius: 10px;
            font-size: 0.95rem;
            color: #0369a1;
            margin-top: 1rem;
        }
        .info-banner.neutral {
            background: #ede9fe;
            border-left-color: #7c3aed;
            color: #5b21b6;
        }
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 52px;
            height: 28px;
            flex-shrink: 0;
        }
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #cbd5e1;
            transition: 0.3s;
            border-radius: 28px;
        }
        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 20px;
            width: 20px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: 0.3s;
            border-radius: 50%;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        .toggle-switch input:checked + .toggle-slider {
            background-color: #10b981;
        }
        .toggle-switch input:checked + .toggle-slider:before {
            transform: translateX(24px);
        }
        .toggle-switch input:focus + .toggle-slider {
            box-shadow: 0 0 1px #10b981;
        }
        .form-grid.full-width {
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        }
        .form-group-logo {
            grid-column: span 2;
        }
        .logo-upload-area {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        .logo-preview {
            background: #f8fafc;
            border: 1px dashed #cbd5f5;
            border-radius: 12px;
            padding: 0.75rem 1rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            max-width: 240px;
            min-height: 96px;
        }
        .logo-preview img {
            max-width: 200px;
            max-height: 72px;
            object-fit: contain;
        }
        .favicon-preview {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 72px;
            height: 72px;
            border-radius: 16px;
            background: #f1f5f9;
            border: 1px dashed #cbd5f5;
        }
        .favicon-preview img {
            width: 48px;
            height: 48px;
            object-fit: contain;
        }
        .remove-logo-option {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            color: #475569;
        }
        .field-hint {
            font-size: 0.85rem;
            color: #64748b;
        }
        .cache-metrics {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1.25rem;
            margin-bottom: 1.5rem;
        }
        .cache-metric {
            background: #f8fafc;
            border-radius: 12px;
            padding: 1.25rem;
            border: 1px solid #e2e8f0;
        }
        .cache-metric-label {
            font-size: 0.85rem;
            color: #475569;
            margin-bottom: 0.25rem;
        }
        .cache-metric-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e293b;
        }
        .cache-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
            margin-top: 1.5rem;
        }
        .cache-actions .btn-secondary {
            background: #f1f5f9;
            color: #1e293b;
            border: 1px solid #d1d5db;
        }
        @media (max-width: 768px) {
            .payment-hero {
                padding: 1.5rem;
            }
            .payment-subnav {
                gap: 0.5rem;
            }
            .payment-subtab {
                flex: 1 1 45%;
                text-align: center;
            }
            .form-group-logo {
                grid-column: span 1;
            }
        }
    </style>
</head>
<body class="admin-body">
    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="admin-main">
            <!-- Header -->
            <header class="admin-header">
                <div class="header-content">
                    <h1>System Settings</h1>
                    <div class="header-actions">
                        <span class="user-info">Welcome, <?= htmlspecialchars($currentUser['full_name']) ?></span>
                    </div>
                </div>
            </header>

            <!-- Content -->
            <div class="admin-content">
                <?php if ($message): ?>
                    <div class="alert alert-<?= $messageType ?>">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <!-- Modern Settings Navigation -->
                <div class="settings-nav">
                    <button class="settings-tab active" data-tab="general" onclick="showTab(event, 'general')">
                        <span class="tab-icon">🏢</span>
                        <span class="tab-text">General Settings</span>
                    </button>
                    <button class="settings-tab" data-tab="email" onclick="showTab(event, 'email')">
                        <span class="tab-icon">📧</span>
                        <span class="tab-text">Email Configuration</span>
                    </button>
                    <button class="settings-tab" data-tab="seo" onclick="showTab(event, 'seo')">
                        <span class="tab-icon">🔍</span>
                        <span class="tab-text">SEO & Analytics</span>
                    </button>
                    <button class="settings-tab" data-tab="social" onclick="showTab(event, 'social')">
                        <span class="tab-icon">📱</span>
                        <span class="tab-text">Social Media</span>
                    </button>
                    <button class="settings-tab" data-tab="payments" onclick="showTab(event, 'payments')">
                        <span class="tab-icon">💳</span>
                        <span class="tab-text">Payment Gateways</span>
                    </button>
                    <button class="settings-tab" data-tab="cache" onclick="showTab(event, 'cache')">
                        <span class="tab-icon">🧹</span>
                        <span class="tab-text">Cache Management</span>
                    </button>
                    <button class="settings-tab" data-tab="ai" onclick="showTab(event, 'ai')">
                        <span class="tab-icon">🤖</span>
                        <span class="tab-text">AI Configuration</span>
                    </button>
                </div>

                <!-- General Settings -->
                <div id="general-tab" class="settings-section active">
                    <div class="card">
                        <div class="card-header">
                            <h3><span class="header-icon">🏢</span> General Website Settings</h3>
                            <p class="header-description">Configure basic site information and contact details</p>
                        </div>
                        <div class="card-body">
                            <form method="POST" class="settings-form" enctype="multipart/form-data">
                                <input type="hidden" name="action" value="update_general">
                                
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label for="site_name">Site Name</label>
                                        <input type="text" id="site_name" name="site_name" required
                                               value="<?= htmlspecialchars($settings['site_name']) ?>">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="site_url">Site URL</label>
                                        <input type="url" id="site_url" name="site_url" required
                                               value="<?= htmlspecialchars($settings['site_url']) ?>">
                                    </div>
                                    <div class="form-group form-group-logo">
                                        <label for="site_logo">Site Logo</label>
                                        <div class="logo-upload-area">
                                            <input type="file" id="site_logo" name="site_logo" accept=".png,.jpg,.jpeg,.webp,.svg">
                                            <small class="field-hint">PNG, JPG, WEBP, or SVG up to <?= htmlspecialchars(round(MAX_FILE_SIZE / (1024 * 1024), 1)) ?>MB.</small>
                                            <?php if (!empty($settings['site_logo'])): ?>
                                                <div class="logo-preview">
                                                    <img src="../<?= htmlspecialchars($settings['site_logo']) ?>" alt="Current site logo">
                                                </div>
                                                <label class="remove-logo-option">
                                                    <input type="checkbox" name="site_logo_remove" value="1">
                                                    Remove current logo
                                                </label>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="form-group form-group-logo">
                                        <label for="site_favicon">Site Favicon</label>
                                        <div class="logo-upload-area">
                                            <input type="file" id="site_favicon" name="site_favicon" accept=".png,.jpg,.jpeg,.webp,.svg,.ico">
                                            <small class="field-hint">ICO or square PNG/SVG up to <?= htmlspecialchars(round(MAX_FILE_SIZE / (1024 * 1024), 1)) ?>MB.</small>
                                            <?php if (!empty($settings['site_favicon'])): ?>
                                                <div class="favicon-preview">
                                                    <img src="../<?= htmlspecialchars($settings['site_favicon']) ?>" alt="Current site favicon">
                                                </div>
                                                <label class="remove-logo-option">
                                                    <input type="checkbox" name="site_favicon_remove" value="1">
                                                    Remove current favicon
                                                </label>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="contact_email">Contact Email</label>
                                        <input type="email" id="contact_email" name="contact_email" required
                                               value="<?= htmlspecialchars($settings['contact_email']) ?>">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="contact_phone">Contact Phone</label>
                                        <input type="tel" id="contact_phone" name="contact_phone"
                                               value="<?= htmlspecialchars($settings['contact_phone']) ?>">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="currency">Currency</label>
                                        <select id="currency" name="currency">
                                            <option value="KES" <?= $settings['currency'] == 'KES' ? 'selected' : '' ?>>KES - Kenyan Shilling</option>
                                            <option value="USD" <?= $settings['currency'] == 'USD' ? 'selected' : '' ?>>USD - US Dollar</option>
                                            <option value="EUR" <?= $settings['currency'] == 'EUR' ? 'selected' : '' ?>>EUR - Euro</option>
                                            <option value="GBP" <?= $settings['currency'] == 'GBP' ? 'selected' : '' ?>>GBP - British Pound</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="timezone">Timezone</label>
                                        <select id="timezone" name="timezone">
                                            <option value="Africa/Nairobi" <?= $settings['timezone'] == 'Africa/Nairobi' ? 'selected' : '' ?>>Africa/Nairobi</option>
                                            <option value="UTC" <?= $settings['timezone'] == 'UTC' ? 'selected' : '' ?>>UTC</option>
                                            <option value="America/New_York" <?= $settings['timezone'] == 'America/New_York' ? 'selected' : '' ?>>America/New_York</option>
                                            <option value="Europe/London" <?= $settings['timezone'] == 'Europe/London' ? 'selected' : '' ?>>Europe/London</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="site_description">Site Description</label>
                                    <textarea id="site_description" name="site_description" rows="3"><?= htmlspecialchars($settings['site_description']) ?></textarea>
                                </div>

                                <div class="form-group">
                                    <label for="site_tagline">Site Tagline</label>
                                    <input type="text" id="site_tagline" name="site_tagline"
                                           value="<?= htmlspecialchars($settings['site_tagline']) ?>"
                                           placeholder="Short phrase displayed under the logo">
                                </div>
                                
                                <div class="form-group">
                                    <label for="business_address">Business Address</label>
                                    <textarea id="business_address" name="business_address" rows="3"><?= htmlspecialchars($settings['business_address']) ?></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <label for="business_hours">Business Hours</label>
                                    <textarea id="business_hours" name="business_hours" rows="2"><?= htmlspecialchars($settings['business_hours']) ?></textarea>
                                </div>
                                
                                <div class="form-actions">
                                    <button type="submit" class="btn btn-primary">Save General Settings</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Email Settings -->
                <div id="email-tab" class="settings-section">
                    <div class="card">
                        <div class="card-header">
                            <h3><span class="header-icon">📧</span> Email Configuration</h3>
                            <p class="header-description">Set up SMTP settings for automated emails</p>
                        </div>
                        <div class="card-body">
                            <form method="POST" class="settings-form">
                                <input type="hidden" name="action" value="update_email">
                                
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label for="smtp_host">SMTP Host</label>
                                        <input type="text" id="smtp_host" name="smtp_host"
                                               value="<?= htmlspecialchars($settings['smtp_host']) ?>"
                                               placeholder="smtp.gmail.com">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="smtp_port">SMTP Port</label>
                                        <input type="number" id="smtp_port" name="smtp_port"
                                               value="<?= htmlspecialchars($settings['smtp_port']) ?>"
                                               placeholder="587">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="smtp_username">SMTP Username</label>
                                        <input type="text" id="smtp_username" name="smtp_username"
                                               value="<?= htmlspecialchars($settings['smtp_username']) ?>"
                                               placeholder="your-email@gmail.com">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="smtp_password">SMTP Password</label>
                                        <input type="password" id="smtp_password" name="smtp_password"
                                               value="<?= htmlspecialchars($settings['smtp_password']) ?>"
                                               placeholder="Your email password or app password">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="smtp_encryption">Encryption</label>
                                        <select id="smtp_encryption" name="smtp_encryption">
                                            <option value="tls" <?= $settings['smtp_encryption'] == 'tls' ? 'selected' : '' ?>>TLS</option>
                                            <option value="ssl" <?= $settings['smtp_encryption'] == 'ssl' ? 'selected' : '' ?>>SSL</option>
                                            <option value="none" <?= $settings['smtp_encryption'] == 'none' ? 'selected' : '' ?>>None</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="from_email">From Email</label>
                                        <input type="email" id="from_email" name="from_email"
                                               value="<?= htmlspecialchars($settings['from_email']) ?>"
                                               placeholder="noreply@yoursite.com">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="from_name">From Name</label>
                                        <input type="text" id="from_name" name="from_name"
                                               value="<?= htmlspecialchars($settings['from_name']) ?>"
                                               placeholder="Your Company Name">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="admin_email">Admin Email</label>
                                        <input type="email" id="admin_email" name="admin_email"
                                               value="<?= htmlspecialchars($settings['admin_email']) ?>"
                                               placeholder="admin@yoursite.com">
                                    </div>
                                </div>
                                
                                <div class="form-actions">
                                    <button type="submit" class="btn btn-primary">Save Email Settings</button>
                                    <button type="button" class="btn btn-secondary" onclick="testEmail()">Test Email</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- SEO Settings -->
                <div id="seo-tab" class="settings-section">
                    <div class="card">
                        <div class="card-header">
                            <h3><span class="header-icon">🔍</span> SEO & Analytics Settings</h3>
                            <p class="header-description">Optimize search engine visibility and track performance</p>
                        </div>
                        <div class="card-body">
                            <form method="POST" class="settings-form">
                                <input type="hidden" name="action" value="update_seo">
                                
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label for="meta_title">Meta Title</label>
                                        <input type="text" id="meta_title" name="meta_title"
                                               value="<?= htmlspecialchars($settings['meta_title']) ?>"
                                               maxlength="60">
                                        <small>Recommended: 50-60 characters</small>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="meta_keywords">Meta Keywords</label>
                                        <input type="text" id="meta_keywords" name="meta_keywords"
                                               value="<?= htmlspecialchars($settings['meta_keywords']) ?>"
                                               placeholder="keyword1, keyword2, keyword3">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="google_analytics">Google Analytics ID</label>
                                        <input type="text" id="google_analytics" name="google_analytics"
                                               value="<?= htmlspecialchars($settings['google_analytics']) ?>"
                                               placeholder="G-XXXXXXXXXX">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="facebook_pixel">Facebook Pixel ID</label>
                                        <input type="text" id="facebook_pixel" name="facebook_pixel"
                                               value="<?= htmlspecialchars($settings['facebook_pixel']) ?>"
                                               placeholder="1234567890123456">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="sitemap_url">Sitemap URL</label>
                                        <input type="text" id="sitemap_url" name="sitemap_url"
                                               value="<?= htmlspecialchars($settings['sitemap_url']) ?>"
                                               placeholder="/sitemap.xml">
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="meta_description">Meta Description</label>
                                    <textarea id="meta_description" name="meta_description" rows="3"
                                              maxlength="160"><?= htmlspecialchars($settings['meta_description']) ?></textarea>
                                    <small>Recommended: 150-160 characters</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="robots_txt">Robots.txt Content</label>
                                    <textarea id="robots_txt" name="robots_txt" rows="5"><?= htmlspecialchars($settings['robots_txt']) ?></textarea>
                                </div>
                                
                                <div class="form-actions">
                                    <button type="submit" class="btn btn-primary">Save SEO Settings</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Social Media Settings -->
                <div id="social-tab" class="settings-section">
                    <div class="card">
                        <div class="card-header">
                            <h3><span class="header-icon">📱</span> Social Media Settings</h3>
                            <p class="header-description">Connect your social media accounts and contact information</p>
                        </div>
                        <div class="card-body">
                            <form method="POST" class="settings-form">
                                <input type="hidden" name="action" value="update_social">
                                
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label for="facebook_url">Facebook URL</label>
                                        <input type="url" id="facebook_url" name="facebook_url"
                                               value="<?= htmlspecialchars($settings['facebook_url']) ?>"
                                               placeholder="https://facebook.com/yourpage">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="twitter_url">Twitter URL</label>
                                        <input type="url" id="twitter_url" name="twitter_url"
                                               value="<?= htmlspecialchars($settings['twitter_url']) ?>"
                                               placeholder="https://twitter.com/yourhandle">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="instagram_url">Instagram URL</label>
                                        <input type="url" id="instagram_url" name="instagram_url"
                                               value="<?= htmlspecialchars($settings['instagram_url']) ?>"
                                               placeholder="https://instagram.com/yourhandle">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="linkedin_url">LinkedIn URL</label>
                                        <input type="url" id="linkedin_url" name="linkedin_url"
                                               value="<?= htmlspecialchars($settings['linkedin_url']) ?>"
                                               placeholder="https://linkedin.com/company/yourcompany">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="youtube_url">YouTube URL</label>
                                        <input type="url" id="youtube_url" name="youtube_url"
                                               value="<?= htmlspecialchars($settings['youtube_url']) ?>"
                                               placeholder="https://youtube.com/channel/yourchannel">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="whatsapp_number">WhatsApp Number</label>
                                        <input type="tel" id="whatsapp_number" name="whatsapp_number"
                                               value="<?= htmlspecialchars($settings['whatsapp_number']) ?>"
                                               placeholder="+254700000000">
                                        <small>Include country code (e.g., +254700000000)</small>
                                    </div>
                                </div>
                                
                                <div class="form-actions">
                                    <button type="submit" class="btn btn-primary">Save Social Media Settings</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Payment Gateway Settings -->
                <div id="payments-tab" class="settings-section">
                    <div class="card">
                        <div class="card-header">
                            <h3><span class="header-icon">💳</span> Payment Gateways</h3>
                            <p class="header-description">Manage regional payment providers and credentials. Toggle between gateway subtabs below.</p>
                        </div>
                        <div class="card-body">
                            <form method="POST" class="settings-form" id="paymentSettingsForm">
                                <input type="hidden" name="action" value="update_payments">

                                <div class="payment-hero">
                                    <div class="payment-summary">
                                        <h4>Regional Coverage</h4>
                                        <p>Pesapal, PayPal, Stripe, and Flutterwave cover global cards, while AzamPay and Lipa na M-Pesa target Tanzania and Kenya respectively. Enable sandbox when testing.</p>
                                        <label class="switch-label">
                                            <input type="checkbox" name="payment_sandbox_mode" value="1" <?= $settings['payment_sandbox_mode'] === '1' ? 'checked' : '' ?>>
                                            <span>Enable Sandbox Mode (Use test endpoints)</span>
                                        </label>
                                        <small class="supporting-text">Keep sandbox disabled in production to use live credentials.</small>
                                    </div>
                                </div>

                                <div class="payment-subnav">
                                    <button type="button" class="payment-subtab active" data-target="payment-overview">Overview</button>
                                    <button type="button" class="payment-subtab" data-target="payment-pesapal">Pesapal</button>
                                    <button type="button" class="payment-subtab" data-target="payment-flutterwave">Flutterwave</button>
                                    <button type="button" class="payment-subtab" data-target="payment-paypal">PayPal</button>
                                    <button type="button" class="payment-subtab" data-target="payment-stripe">Stripe</button>
                                    <button type="button" class="payment-subtab" data-target="payment-azampay">AzamPay</button>
                                    <button type="button" class="payment-subtab" data-target="payment-mpesa">Lipa na M-Pesa</button>
                                </div>

                                <div class="payment-subpanel active" id="payment-overview">
                                    <div class="form-grid full-width">
                                        <div class="form-group">
                                            <label for="pesapal_callback_url">Pesapal Callback URL</label>
                                            <input type="url" id="pesapal_callback_url" name="pesapal_callback_url" value="<?= htmlspecialchars($settings['pesapal_callback_url']) ?>" placeholder="<?= htmlspecialchars(SITE_URL . '/payment-callback/pesapal') ?>">
                                        </div>
                                        <div class="form-group">
                                            <label for="flutterwave_redirect_url">Flutterwave Redirect URL</label>
                                            <input type="url" id="flutterwave_redirect_url" name="flutterwave_redirect_url" value="<?= htmlspecialchars($settings['flutterwave_redirect_url']) ?>" placeholder="<?= htmlspecialchars(SITE_URL . '/payment-callback/flutterwave') ?>">
                                        </div>
                                        <div class="form-group">
                                            <label for="azampay_callback_url">AzamPay Callback URL</label>
                                            <input type="url" id="azampay_callback_url" name="azampay_callback_url" value="<?= htmlspecialchars($settings['azampay_callback_url']) ?>" placeholder="<?= htmlspecialchars(SITE_URL . '/payment-callback/azampay') ?>">
                                        </div>
                                        <div class="form-group">
                                            <label for="mpesa_callback_url">M-Pesa Callback URL</label>
                                            <input type="url" id="mpesa_callback_url" name="mpesa_callback_url" value="<?= htmlspecialchars($settings['mpesa_callback_url']) ?>" placeholder="<?= htmlspecialchars(SITE_URL . '/payment-callback/mpesa') ?>">
                                        </div>
                                    </div>
                                    <div class="info-banner">
                                        <strong>Heads up:</strong> Register these callback URLs inside each provider dashboard after switching to live mode.
                                    </div>
                                </div>

                                <div class="payment-subpanel" id="payment-pesapal">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                                        <div>
                                            <h4 style="margin: 0;">Pesapal Credentials</h4>
                                            <p class="supporting-text" style="margin: 4px 0 0 0;">Used for pan-African card & mobile payments. Required for both local and global checkout flows.</p>
                                        </div>
                                        <label class="toggle-switch">
                                            <input type="checkbox" name="pesapal_enabled" value="1" <?= $settings['pesapal_enabled'] === '1' ? 'checked' : '' ?>>
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </div>
                                    <div class="form-grid">
                                        <div class="form-group">
                                            <label for="pesapal_consumer_key">Consumer Key</label>
                                            <input type="text" id="pesapal_consumer_key" name="pesapal_consumer_key" value="<?= htmlspecialchars($settings['pesapal_consumer_key']) ?>">
                                        </div>
                                        <div class="form-group">
                                            <label for="pesapal_consumer_secret">Consumer Secret</label>
                                            <input type="text" id="pesapal_consumer_secret" name="pesapal_consumer_secret" value="<?= htmlspecialchars($settings['pesapal_consumer_secret']) ?>">
                                        </div>
                                        <div class="form-group">
                                            <label for="pesapal_ipn_id">IPN Notification ID</label>
                                            <input type="text" id="pesapal_ipn_id" name="pesapal_ipn_id" value="<?= htmlspecialchars($settings['pesapal_ipn_id']) ?>" placeholder="Optional but recommended">
                                        </div>
                                    </div>
                                    <div class="info-banner neutral">
                                        Create separate applications for Sandbox and Live inside Pesapal. The same callback URL can be reused in both environments.
                                    </div>
                                </div>

                                <div class="payment-subpanel" id="payment-flutterwave">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                                        <div>
                                            <h4 style="margin: 0;">Flutterwave Credentials</h4>
                                            <p class="supporting-text" style="margin: 4px 0 0 0;">Supports international cards plus regional wallets (M-Pesa, bank transfers, USSD).</p>
                                        </div>
                                        <label class="toggle-switch">
                                            <input type="checkbox" name="flutterwave_enabled" value="1" <?= $settings['flutterwave_enabled'] === '1' ? 'checked' : '' ?>>
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </div>
                                    <div class="form-grid">
                                        <div class="form-group">
                                            <label for="flutterwave_public_key">Public Key</label>
                                            <input type="text" id="flutterwave_public_key" name="flutterwave_public_key" value="<?= htmlspecialchars($settings['flutterwave_public_key']) ?>">
                                        </div>
                                        <div class="form-group">
                                            <label for="flutterwave_secret_key">Secret Key</label>
                                            <input type="text" id="flutterwave_secret_key" name="flutterwave_secret_key" value="<?= htmlspecialchars($settings['flutterwave_secret_key']) ?>">
                                        </div>
                                        <div class="form-group">
                                            <label for="flutterwave_encryption_key">Encryption Key</label>
                                            <input type="text" id="flutterwave_encryption_key" name="flutterwave_encryption_key" value="<?= htmlspecialchars($settings['flutterwave_encryption_key']) ?>">
                                        </div>
                                        <div class="form-group">
                                            <label for="flutterwave_callback_url">Callback URL (Webhook)</label>
                                            <div style="display: flex; gap: 8px; align-items: center;">
                                                <input type="text" id="flutterwave_callback_url" value="<?= htmlspecialchars(rtrim($siteBaseUrl, '/')) ?>/payment-callback/flutterwave.php" readonly style="flex: 1; background: #f5f5f5; cursor: text;">
                                                <button type="button" class="btn btn-secondary" onclick="copyCallbackUrl('flutterwave_callback_url')" style="white-space: nowrap;">
                                                    <i class="fas fa-copy"></i> Copy
                                                </button>
                                            </div>
                                            <small style="color: #666; display: block; margin-top: 4px;">
                                                Configure this URL in your Flutterwave dashboard to receive payment notifications
                                            </small>
                                        </div>
                                    </div>
                                    <div class="info-banner neutral">
                                        In live mode you must enable applicable payment options in Flutterwave Dashboard &mdash; e.g. Mpesa, card, or bank transfer.
                                    </div>
                                </div>

                                <div class="payment-subpanel" id="payment-paypal">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                                        <div>
                                            <h4 style="margin: 0;">PayPal Checkout</h4>
                                            <p class="supporting-text" style="margin: 4px 0 0 0;">Offer global card and PayPal wallet payments. Ensure your PayPal business account is verified before going live.</p>
                                        </div>
                                        <label class="toggle-switch">
                                            <input type="checkbox" name="paypal_enabled" value="1" <?= $settings['paypal_enabled'] === '1' ? 'checked' : '' ?>>
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </div>
                                    <div class="form-grid">
                                        <div class="form-group">
                                            <label for="paypal_client_id">Client ID</label>
                                            <input type="text" id="paypal_client_id" name="paypal_client_id" value="<?= htmlspecialchars($settings['paypal_client_id']) ?>">
                                        </div>
                                        <div class="form-group">
                                            <label for="paypal_client_secret">Client Secret</label>
                                            <input type="text" id="paypal_client_secret" name="paypal_client_secret" value="<?= htmlspecialchars($settings['paypal_client_secret']) ?>">
                                        </div>
                                        <div class="form-group">
                                            <label for="paypal_webhook_id">Webhook ID (Optional)</label>
                                            <input type="text" id="paypal_webhook_id" name="paypal_webhook_id" value="<?= htmlspecialchars($settings['paypal_webhook_id']) ?>" placeholder="Used for IPN/Webhook validation">
                                        </div>
                                        <div class="form-group">
                                            <label for="paypal_return_url">Return URL</label>
                                            <input type="url" id="paypal_return_url" name="paypal_return_url" value="<?= htmlspecialchars($settings['paypal_return_url']) ?>" placeholder="<?= htmlspecialchars(SITE_URL . '/payment-callback/paypal/success') ?>">
                                        </div>
                                        <div class="form-group">
                                            <label for="paypal_cancel_url">Cancel URL</label>
                                            <input type="url" id="paypal_cancel_url" name="paypal_cancel_url" value="<?= htmlspecialchars($settings['paypal_cancel_url']) ?>" placeholder="<?= htmlspecialchars(SITE_URL . '/payment-callback/paypal/cancel') ?>">
                                        </div>
                                    </div>
                                    <div class="info-banner neutral">
                                        Sandbox credentials follow the pattern <code>sb-xxx</code>. Create a separate live app once you are ready to process real payments.
                                    </div>
                                </div>

                                <div class="payment-subpanel" id="payment-stripe">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                                        <div>
                                            <h4 style="margin: 0;">Stripe Checkout</h4>
                                            <p class="supporting-text" style="margin: 4px 0 0 0;">Ideal for card payments in supported countries. Stripe Checkout handles SCA/3DS automatically.</p>
                                        </div>
                                        <label class="toggle-switch">
                                            <input type="checkbox" name="stripe_enabled" value="1" <?= $settings['stripe_enabled'] === '1' ? 'checked' : '' ?>>
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </div>
                                    <div class="form-grid">
                                        <div class="form-group">
                                            <label for="stripe_publishable_key">Publishable Key</label>
                                            <input type="text" id="stripe_publishable_key" name="stripe_publishable_key" value="<?= htmlspecialchars($settings['stripe_publishable_key']) ?>">
                                        </div>
                                        <div class="form-group">
                                            <label for="stripe_secret_key">Secret Key</label>
                                            <input type="text" id="stripe_secret_key" name="stripe_secret_key" value="<?= htmlspecialchars($settings['stripe_secret_key']) ?>">
                                        </div>
                                        <div class="form-group">
                                            <label for="stripe_webhook_secret">Webhook Signing Secret</label>
                                            <input type="text" id="stripe_webhook_secret" name="stripe_webhook_secret" value="<?= htmlspecialchars($settings['stripe_webhook_secret']) ?>" placeholder="whsec_...">
                                        </div>
                                        <div class="form-group">
                                            <label for="stripe_success_url">Success URL</label>
                                            <input type="url" id="stripe_success_url" name="stripe_success_url" value="<?= htmlspecialchars($settings['stripe_success_url']) ?>" placeholder="<?= htmlspecialchars(SITE_URL . '/payment-callback/stripe/success') ?>">
                                        </div>
                                        <div class="form-group">
                                            <label for="stripe_cancel_url">Cancel URL</label>
                                            <input type="url" id="stripe_cancel_url" name="stripe_cancel_url" value="<?= htmlspecialchars($settings['stripe_cancel_url']) ?>" placeholder="<?= htmlspecialchars(SITE_URL . '/payment-callback/stripe/cancel') ?>">
                                        </div>
                                    </div>
                                    <div class="info-banner neutral">
                                        Remember to switch the keys to your live mode values and update the webhook secret after flipping Stripe into production.
                                    </div>
                                </div>

                                <div class="payment-subpanel" id="payment-azampay">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                                        <div>
                                            <h4 style="margin: 0;">AzamPay Credentials</h4>
                                            <p class="supporting-text" style="margin: 4px 0 0 0;">Best suited for Tanzania: integrates Tigo Pesa, Airtel Money, M-Pesa (TZ), and local cards.</p>
                                        </div>
                                        <label class="toggle-switch">
                                            <input type="checkbox" name="azampay_enabled" value="1" <?= $settings['azampay_enabled'] === '1' ? 'checked' : '' ?>>
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </div>
                                    <div class="form-grid">
                                        <div class="form-group">
                                            <label for="azampay_app_name">App Name</label>
                                            <input type="text" id="azampay_app_name" name="azampay_app_name" value="<?= htmlspecialchars($settings['azampay_app_name']) ?>">
                                        </div>
                                        <div class="form-group">
                                            <label for="azampay_client_id">Client ID</label>
                                            <input type="text" id="azampay_client_id" name="azampay_client_id" value="<?= htmlspecialchars($settings['azampay_client_id']) ?>">
                                        </div>
                                        <div class="form-group">
                                            <label for="azampay_client_secret">Client Secret</label>
                                            <input type="text" id="azampay_client_secret" name="azampay_client_secret" value="<?= htmlspecialchars($settings['azampay_client_secret']) ?>">
                                        </div>
                                        <div class="form-group">
                                            <label for="azampay_api_key">API Key (X-API-Key Token)</label>
                                            <input type="text" id="azampay_api_key" name="azampay_api_key" value="<?= htmlspecialchars($settings['azampay_api_key']) ?>" placeholder="Optional - Used for some API endpoints">
                                        </div>
                                        <div class="form-group">
                                            <label for="azampay_account_number">Collection Account Number</label>
                                            <input type="text" id="azampay_account_number" name="azampay_account_number" value="<?= htmlspecialchars($settings['azampay_account_number']) ?>">
                                        </div>
                                        <div class="form-group">
                                            <label for="azampay_callback_url">Callback URL (Webhook)</label>
                                            <div style="display: flex; gap: 8px; align-items: center;">
                                                <input type="text" id="azampay_callback_url" value="<?= htmlspecialchars(rtrim($settings['site_url'] ?? SITE_URL, '/')) ?>/payment-callback/azampay.php" readonly style="flex: 1; background: #f5f5f5; cursor: text;">
                                                <button type="button" class="btn btn-secondary" onclick="copyCallbackUrl('azampay_callback_url')" style="white-space: nowrap;">
                                                    <i class="fas fa-copy"></i> Copy
                                                </button>
                                            </div>
                                            <small style="color: #666; display: block; margin-top: 4px;">
                                                Configure this URL in your AzamPay dashboard to receive payment notifications
                                            </small>
                                        </div>
                                    </div>
                                    <div class="info-banner neutral">
                                        Confirm with AzamPay support that your webhook endpoint is reachable before switching to production traffic.
                                    </div>
                                </div>

                                <div class="payment-subpanel" id="payment-mpesa">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                                        <div>
                                            <h4 style="margin: 0;">Lipa na M-Pesa (STK Push)</h4>
                                            <p class="supporting-text" style="margin: 4px 0 0 0;">Used for Kenya STK push. Requires a Paybill or Till number from Safaricom.</p>
                                        </div>
                                        <label class="toggle-switch">
                                            <input type="checkbox" name="mpesa_enabled" value="1" <?= $settings['mpesa_enabled'] === '1' ? 'checked' : '' ?>>
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </div>
                                    <div class="form-grid">
                                        <div class="form-group">
                                            <label for="mpesa_consumer_key">Consumer Key</label>
                                            <input type="text" id="mpesa_consumer_key" name="mpesa_consumer_key" value="<?= htmlspecialchars($settings['mpesa_consumer_key']) ?>">
                                        </div>
                                        <div class="form-group">
                                            <label for="mpesa_consumer_secret">Consumer Secret</label>
                                            <input type="text" id="mpesa_consumer_secret" name="mpesa_consumer_secret" value="<?= htmlspecialchars($settings['mpesa_consumer_secret']) ?>">
                                        </div>
                                        <div class="form-group">
                                            <label for="mpesa_shortcode">Shortcode / Till Number</label>
                                            <input type="text" id="mpesa_shortcode" name="mpesa_shortcode" value="<?= htmlspecialchars($settings['mpesa_shortcode']) ?>">
                                        </div>
                                        <div class="form-group">
                                            <label for="mpesa_passkey">Passkey</label>
                                            <input type="text" id="mpesa_passkey" name="mpesa_passkey" value="<?= htmlspecialchars($settings['mpesa_passkey']) ?>">
                                        </div>
                                    </div>
                                    <div class="info-banner neutral">
                                        SafariCom provides separate credentials for sandbox and production. Ensure the callback URL matches your registered domain.
                                    </div>
                                </div>

                                <div class="form-actions">
                                    <button type="submit" class="btn btn-primary">Save Payment Settings</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- AI Configuration Settings -->
                <!-- Cache Management -->
                <div id="cache-tab" class="settings-section">
                    <div class="card">
                        <div class="card-header">
                            <h3><span class="header-icon">🧹</span> Cache Management</h3>
                            <p class="header-description">Control server cache behaviour and purge generated files when content changes.</p>
                        </div>
                        <div class="card-body">
                            <form method="POST" class="settings-form">
                                <input type="hidden" name="action" value="manage_cache">
                                <div class="cache-metrics">
                                    <div class="cache-metric">
                                        <div class="cache-metric-label">Cached Files</div>
                                        <div class="cache-metric-value"><?= htmlspecialchars($cacheFilesFormatted) ?></div>
                                    </div>
                                    <div class="cache-metric">
                                        <div class="cache-metric-label">Disk Usage</div>
                                        <div class="cache-metric-value"><?= htmlspecialchars($cacheSizeFormatted) ?></div>
                                    </div>
                                    <div class="cache-metric">
                                        <div class="cache-metric-label">Last Cleared</div>
                                        <div class="cache-metric-value"><?= htmlspecialchars($cacheLastClearedDisplay) ?></div>
                                    </div>
                                </div>

                                <div class="form-grid">
                                    <div class="form-group">
                                        <label for="cache_enabled">Cache Status</label>
                                        <label class="switch-label">
                                            <input type="checkbox" id="cache_enabled" name="cache_enabled" value="1" <?= $settings['cache_enabled'] === '1' ? 'checked' : '' ?>>
                                            <span>Enable server-side cache</span>
                                        </label>
                                        <small class="field-hint">Disable caching while troubleshooting live content changes.</small>
                                    </div>
                                    <div class="form-group">
                                        <label for="cache_duration">Cache Duration (minutes)</label>
                                        <input type="number" id="cache_duration" name="cache_duration" min="5" max="1440" value="<?= htmlspecialchars($cacheDurationMinutes) ?>">
                                        <small class="field-hint">Valid range 5&ndash;1440 minutes. Cached data refreshes after this period.</small>
                                    </div>
                                </div>

                                <div class="cache-actions">
                                    <button type="submit" name="cache_action" value="save" class="btn btn-primary">Save Cache Settings</button>
                                    <button type="submit" name="cache_action" value="clear" class="btn btn-secondary">Clear Cache Now</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div id="ai-tab" class="settings-section">
                    <div class="card">
                        <div class="card-header">
                            <h3><span class="header-icon">🤖</span> AI Configuration</h3>
                            <p class="header-description">Configure AI providers for product optimization and content generation</p>
                        </div>
                        <div class="card-body">
                            <form method="POST" class="settings-form">
                                <input type="hidden" name="action" value="update_ai">
                                
                                <div class="ai-status-banner">
                                    <span class="ai-icon">✨</span>
                                    <div class="ai-info">
                                        <h4>AI-Powered Product Optimization</h4>
                                        <p>Enhance your products with AI-generated descriptions, keywords, and features. Configure your preferred AI provider below.</p>
                                    </div>
                                </div>
                                
                                <div class="form-grid">
                                    <div class="form-group full-width">
                                        <label class="checkbox-label">
                                            <input type="checkbox" name="ai_enabled" value="1" 
                                                   <?= $settings['ai_enabled'] == '1' ? 'checked' : '' ?>>
                                            <span>Enable AI Features</span>
                                        </label>
                                        <small>Enable AI-powered product optimization, description generation, and SEO enhancements</small>
                                    </div>
                                    
                                    <div class="form-group full-width">
                                        <label for="ai_default_provider">Default AI Provider</label>
                                        <select id="ai_default_provider" name="ai_default_provider" required>
                                            <option value="deepseek" <?= $settings['ai_default_provider'] == 'deepseek' ? 'selected' : '' ?>>
                                                DeepSeek AI (Recommended - Cost Effective)
                                            </option>
                                            <option value="openai" <?= $settings['ai_default_provider'] == 'openai' ? 'selected' : '' ?>>
                                                OpenAI (GPT-4 / GPT-3.5)
                                            </option>
                                            <option value="kimi" <?= $settings['ai_default_provider'] == 'kimi' ? 'selected' : '' ?>>
                                                Kimi AI (Moonshot)
                                            </option>
                                        </select>
                                        <small>Choose which AI provider to use by default for product optimization</small>
                                    </div>
                                </div>
                                
                                <div class="ai-provider-section">
                                    <h4><span class="provider-icon">🔷</span> DeepSeek AI</h4>
                                    <p class="provider-description">Cost-effective AI provider with excellent multilingual support. Recommended for most users.</p>
                                    
                                    <div class="form-grid">
                                        <div class="form-group full-width">
                                            <label for="ai_deepseek_key">DeepSeek API Key</label>
                                            <input type="password" id="ai_deepseek_key" name="ai_deepseek_key"
                                                   value="<?= htmlspecialchars($settings['ai_deepseek_key']) ?>"
                                                   placeholder="sk-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx">
                                            <small>Get your API key from <a href="https://platform.deepseek.com" target="_blank">platform.deepseek.com</a></small>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="ai-provider-section">
                                    <h4><span class="provider-icon">🟢</span> OpenAI</h4>
                                    <p class="provider-description">Industry-leading AI with GPT-4 and GPT-3.5 models. Higher quality but more expensive.</p>
                                    
                                    <div class="form-grid">
                                        <div class="form-group">
                                            <label for="ai_openai_key">OpenAI API Key</label>
                                            <input type="password" id="ai_openai_key" name="ai_openai_key"
                                                   value="<?= htmlspecialchars($settings['ai_openai_key']) ?>"
                                                   placeholder="sk-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx">
                                            <small>Get your API key from <a href="https://platform.openai.com" target="_blank">platform.openai.com</a></small>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="ai_openai_model">OpenAI Model</label>
                                            <select id="ai_openai_model" name="ai_openai_model">
                                                <option value="gpt-4o" <?= $settings['ai_openai_model'] == 'gpt-4o' ? 'selected' : '' ?>>
                                                    GPT-4o (Latest, Fastest)
                                                </option>
                                                <option value="gpt-4o-mini" <?= $settings['ai_openai_model'] == 'gpt-4o-mini' ? 'selected' : '' ?>>
                                                    GPT-4o Mini (Recommended - Cost Effective)
                                                </option>
                                                <option value="gpt-4-turbo" <?= $settings['ai_openai_model'] == 'gpt-4-turbo' ? 'selected' : '' ?>>
                                                    GPT-4 Turbo
                                                </option>
                                                <option value="gpt-3.5-turbo" <?= $settings['ai_openai_model'] == 'gpt-3.5-turbo' ? 'selected' : '' ?>>
                                                    GPT-3.5 Turbo (Budget Option)
                                                </option>
                                            </select>
                                            <small>Select which OpenAI model to use</small>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="ai-provider-section">
                                    <h4><span class="provider-icon">🌙</span> Kimi AI (Moonshot)</h4>
                                    <p class="provider-description">Chinese AI provider with excellent multilingual capabilities and long context support.</p>
                                    
                                    <div class="form-grid">
                                        <div class="form-group full-width">
                                            <label for="ai_kimi_key">Kimi AI API Key</label>
                                            <input type="password" id="ai_kimi_key" name="ai_kimi_key"
                                                   value="<?= htmlspecialchars($settings['ai_kimi_key']) ?>"
                                                   placeholder="sk-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx">
                                            <small>Get your API key from <a href="https://platform.moonshot.cn" target="_blank">platform.moonshot.cn</a></small>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="ai-features-info">
                                    <h4>🎯 AI Features Available</h4>
                                    <ul class="features-list">
                                        <li>✨ SEO-optimized product descriptions</li>
                                        <li>📝 Automatic short description generation</li>
                                        <li>🔑 Smart keyword extraction and SEO tags</li>
                                        <li>🎯 Key selling points identification</li>
                                        <li>📊 Feature generation from specifications</li>
                                        <li>🏆 Product title optimization</li>
                                        <li>🌐 Automatic Alibaba product import with AI enhancement</li>
                                    </ul>
                                </div>
                                
                                <div class="form-actions">
                                    <button type="submit" class="btn btn-primary">💾 Save AI Configuration</button>
                                    <button type="button" class="btn btn-secondary" onclick="testAIConnection()">🧪 Test AI Connection</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Test AI connection
        async function testAIConnection() {
            const provider = document.getElementById('ai_default_provider').value;
            const button = event.target;
            button.disabled = true;
            button.textContent = '⏳ Testing...';
            
            try {
                const response = await fetch('test_ai.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ provider: provider })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('✅ AI Connection Successful!\n\nProvider: ' + provider.toUpperCase() + '\nResponse: ' + result.message);
                } else {
                    alert('❌ AI Connection Failed\n\nError: ' + result.message);
                }
            } catch (error) {
                alert('❌ Connection Error\n\n' + error.message);
            } finally {
                button.disabled = false;
                button.textContent = '🧪 Test AI Connection';
            }
        }
        
        // Enhanced tab functionality with smooth animations
        function showTab(event, tabName) {
            if (event) {
                event.preventDefault();
            }

            const clickedTab = event ? (event.currentTarget || event.target) : null;

            document.querySelectorAll('.settings-tab').forEach(tab => {
                tab.classList.remove('active');
            });

            if (clickedTab) {
                clickedTab.classList.add('active');
            }

            document.querySelectorAll('.settings-section').forEach(section => {
                const shouldShow = section.id === `${tabName}-tab`;
                section.classList.toggle('active', shouldShow);
                if (!shouldShow) {
                    section.style.opacity = '';
                    section.style.transform = '';
                }
            });

            const targetSection = document.getElementById(`${tabName}-tab`);
            if (targetSection) {
                targetSection.style.opacity = '1';
                targetSection.style.transform = 'translateY(0)';
            }

            localStorage.setItem('activeSettingsTab', tabName);
        }

        // Restore active tab and initialise payment subtabs on page load
        document.addEventListener('DOMContentLoaded', function() {
            const storedTab = localStorage.getItem('activeSettingsTab');
            const fallbackTab = document.querySelector('.settings-tab[data-tab="general"]');

            if (storedTab) {
                const storedButton = document.querySelector(`.settings-tab[data-tab="${storedTab}"]`);
                if (storedButton) {
                    storedButton.click();
                } else if (fallbackTab) {
                    fallbackTab.click();
                }
            } else if (fallbackTab) {
                fallbackTab.click();
            }

            const paymentSubtabs = document.querySelectorAll('.payment-subtab');
            const paymentSubpanels = document.querySelectorAll('.payment-subpanel');

            function activatePaymentSubtab(targetId) {
                paymentSubtabs.forEach(subtab => {
                    subtab.classList.toggle('active', subtab.dataset.target === targetId);
                });

                paymentSubpanels.forEach(panel => {
                    panel.classList.toggle('active', panel.id === targetId);
                });

                sessionStorage.setItem('activePaymentSubtab', targetId);
            }

            if (paymentSubtabs.length && paymentSubpanels.length) {
                const storedPaymentSubtab = sessionStorage.getItem('activePaymentSubtab');
                const validInitial = storedPaymentSubtab && document.getElementById(storedPaymentSubtab)
                    ? storedPaymentSubtab
                    : paymentSubpanels[0].id;

                paymentSubtabs.forEach(subtab => {
                    subtab.addEventListener('click', evt => {
                        evt.preventDefault();
                        const targetId = subtab.dataset.target;
                        if (targetId) {
                            activatePaymentSubtab(targetId);
                        }
                    });
                });

                activatePaymentSubtab(validInitial);
            }
        });

        function testEmail() {
            const button = event.target;
            const originalText = button.textContent;
            
            // Add loading state
            button.disabled = true;
            button.innerHTML = '<span class="spinner" style="width: 1rem; height: 1rem; margin-right: 0.5rem;"></span>Sending...';
            
            // Simulate test email (replace with actual AJAX call)
            setTimeout(() => {
                button.disabled = false;
                button.textContent = '✓ Test Sent!';
                button.style.background = '#10b981';
                
                setTimeout(() => {
                    button.textContent = originalText;
                    button.style.background = '';
                }, 2000);
            }, 1500);
        }

        // Copy callback URL to clipboard
        function copyCallbackUrl(inputId) {
            const input = document.getElementById(inputId);
            const button = event.target.closest('button');
            const originalHtml = button.innerHTML;
            
            // Select and copy the text
            input.select();
            input.setSelectionRange(0, 99999); // For mobile devices
            
            try {
                // Try modern clipboard API first
                navigator.clipboard.writeText(input.value).then(() => {
                    button.innerHTML = '<i class="fas fa-check"></i> Copied!';
                    button.style.background = '#10b981';
                    
                    setTimeout(() => {
                        button.innerHTML = originalHtml;
                        button.style.background = '';
                    }, 2000);
                }).catch(() => {
                    // Fallback to older method
                    document.execCommand('copy');
                    button.innerHTML = '<i class="fas fa-check"></i> Copied!';
                    button.style.background = '#10b981';
                    
                    setTimeout(() => {
                        button.innerHTML = originalHtml;
                        button.style.background = '';
                    }, 2000);
                });
            } catch (err) {
                button.innerHTML = '<i class="fas fa-times"></i> Failed';
                button.style.background = '#dc2626';
                
                setTimeout(() => {
                    button.innerHTML = originalHtml;
                    button.style.background = '';
                }, 2000);
            }
        }

        // Character counter for meta fields
        function updateCharCount(field, maxChars) {
            const count = field.value.length;
            const counter = field.parentNode.querySelector('.char-count');
            if (counter) {
                counter.textContent = `${count}/${maxChars}`;
                counter.style.color = count > maxChars ? '#dc2626' : '#6b7280';
            }
        }

        // Add character counters
        document.addEventListener('DOMContentLoaded', function() {
            const metaTitle = document.getElementById('meta_title');
            const metaDesc = document.getElementById('meta_description');
            
            if (metaTitle) {
                const titleCounter = document.createElement('span');
                titleCounter.className = 'char-count';
                titleCounter.style.fontSize = '0.75rem';
                titleCounter.style.color = '#6b7280';
                metaTitle.parentNode.appendChild(titleCounter);
                
                metaTitle.addEventListener('input', () => updateCharCount(metaTitle, 60));
                updateCharCount(metaTitle, 60);
            }
            
            if (metaDesc) {
                const descCounter = document.createElement('span');
                descCounter.className = 'char-count';
                descCounter.style.fontSize = '0.75rem';
                descCounter.style.color = '#6b7280';
                metaDesc.parentNode.appendChild(descCounter);
                
                metaDesc.addEventListener('input', () => updateCharCount(metaDesc, 160));
                updateCharCount(metaDesc, 160);
            }
        });
    </script>
</body>
</html>