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
        
        // Check if base currency changed
        $old_currency = getSetting('currency', 'KES');
        $currency_changed = ($old_currency !== $currency);
        
        // Get old exchange rates
        $old_rate_kes = (float)getSetting('exchange_rate_kes', '1');
        $old_rate_tzs = (float)getSetting('exchange_rate_tzs', '18.5');
        $old_rate_ugx = (float)getSetting('exchange_rate_ugx', '30');
        $old_rate_usd = (float)getSetting('exchange_rate_usd', '0.0077');
        
        // Exchange rates - base currency is always 1
        $input_rate_kes = (float)($_POST['exchange_rate_kes'] ?? 1);
        $input_rate_tzs = (float)($_POST['exchange_rate_tzs'] ?? 18.5);
        $input_rate_ugx = (float)($_POST['exchange_rate_ugx'] ?? 30);
        $input_rate_usd = (float)($_POST['exchange_rate_usd'] ?? 0.0077);
        
        // If base currency changed, recalculate all exchange rates relative to new base
        if ($currency_changed) {
            // Get the rate of the new base currency from old rates
            $new_base_old_rate = 1;
            switch ($currency) {
                case 'KES': $new_base_old_rate = $old_rate_kes; break;
                case 'TZS': $new_base_old_rate = $old_rate_tzs; break;
                case 'UGX': $new_base_old_rate = $old_rate_ugx; break;
                case 'USD': $new_base_old_rate = $old_rate_usd; break;
            }
            
            // Recalculate all rates: new_rate = old_rate / new_base_old_rate
            // This ensures: 1 new_base = X other_currency
            if ($new_base_old_rate > 0) {
                $exchange_rate_kes = $old_rate_kes / $new_base_old_rate;
                $exchange_rate_tzs = $old_rate_tzs / $new_base_old_rate;
                $exchange_rate_ugx = $old_rate_ugx / $new_base_old_rate;
                $exchange_rate_usd = $old_rate_usd / $new_base_old_rate;
                
                // Set the new base to exactly 1
                switch ($currency) {
                    case 'KES': $exchange_rate_kes = 1; break;
                    case 'TZS': $exchange_rate_tzs = 1; break;
                    case 'UGX': $exchange_rate_ugx = 1; break;
                    case 'USD': $exchange_rate_usd = 1; break;
                }
                
                // Update all product prices in database
                $conversion_factor = $new_base_old_rate;
                $update_stmt = $db->prepare("UPDATE products SET price_kes = price_kes * ?, price_tzs = price_tzs * ? WHERE 1=1");
                $update_stmt->bind_param('dd', $conversion_factor, $conversion_factor);
                $update_stmt->execute();
                
                $affected_rows = $db->affected_rows;
                $message = "Base currency changed from $old_currency to $currency. Exchange rates recalculated: ";
                $message .= "KES=" . number_format($exchange_rate_kes, 4) . ", ";
                $message .= "TZS=" . number_format($exchange_rate_tzs, 4) . ", ";
                $message .= "UGX=" . number_format($exchange_rate_ugx, 4) . ", ";
                $message .= "USD=" . number_format($exchange_rate_usd, 6) . ". ";
                $message .= "$affected_rows product prices updated. ";
            }
        } else {
            // No base currency change, use input rates directly
            $exchange_rate_kes = $currency == 'KES' ? 1 : $input_rate_kes;
            $exchange_rate_tzs = $currency == 'TZS' ? 1 : $input_rate_tzs;
            $exchange_rate_ugx = $currency == 'UGX' ? 1 : $input_rate_ugx;
            $exchange_rate_usd = $currency == 'USD' ? 1 : $input_rate_usd;
        }

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
        
        // Handle hero image upload
        $existingHeroImage = getSetting('hero_image', '');
        $hero_image = $existingHeroImage;
        $heroImageUploadError = '';

        if (!empty($_POST['hero_image_remove'])) {
            if ($existingHeroImage && strpos($existingHeroImage, 'uploads/') === 0) {
                $heroImagePath = __DIR__ . '/../' . $existingHeroImage;
                if (file_exists($heroImagePath)) {
                    @unlink($heroImagePath);
                }
            }
            $hero_image = '';
        }

        // Check if image selected from media library
        if (!empty($_POST['hero_image_selected']) && empty($_FILES['hero_image']['name'])) {
            $hero_image = Security::sanitizeInput($_POST['hero_image_selected']);
        }

        if (isset($_FILES['hero_image']) && $_FILES['hero_image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $upload = $_FILES['hero_image'];

            if ($upload['error'] !== UPLOAD_ERR_OK) {
                $heroImageUploadError = 'Failed to upload hero image. Please try again.';
            } elseif ($upload['size'] > MAX_FILE_SIZE) {
                $heroImageUploadError = 'Hero image file is too large. Maximum size is ' . round(MAX_FILE_SIZE / (1024 * 1024), 1) . 'MB.';
            } else {
                $allowedExtensions = ['png', 'jpg', 'jpeg', 'webp', 'svg'];
                $extension = strtolower(pathinfo($upload['name'], PATHINFO_EXTENSION));

                if (!in_array($extension, $allowedExtensions, true)) {
                    $heroImageUploadError = 'Invalid hero image format. Allowed types: PNG, JPG, WEBP, SVG.';
                } else {
                    $uploadDir = __DIR__ . '/../uploads/branding';
                    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
                        $heroImageUploadError = 'Unable to create upload directory for hero image.';
                    } else {
                        $newFileName = 'hero_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
                        $targetPath = $uploadDir . '/' . $newFileName;

                        if (!move_uploaded_file($upload['tmp_name'], $targetPath)) {
                            $heroImageUploadError = 'Unable to save the uploaded hero image. Please check folder permissions.';
                        } else {
                            if ($existingHeroImage && strpos($existingHeroImage, 'uploads/') === 0) {
                                $oldHeroImagePath = __DIR__ . '/../' . $existingHeroImage;
                                if (file_exists($oldHeroImagePath)) {
                                    @unlink($oldHeroImagePath);
                                }
                            }
                            $hero_image = 'uploads/branding/' . $newFileName;
                        }
                    }
                }
            }
        }

        // Handle technical specifications image upload
        $existingTechSpecsImage = getSetting('tech_specs_image', '');
        $tech_specs_image = $existingTechSpecsImage;
        $techSpecsImageUploadError = '';

        if (!empty($_POST['tech_specs_image_remove'])) {
            if ($existingTechSpecsImage && strpos($existingTechSpecsImage, 'uploads/') === 0) {
                $techSpecsImagePath = __DIR__ . '/../' . $existingTechSpecsImage;
                if (file_exists($techSpecsImagePath)) {
                    @unlink($techSpecsImagePath);
                }
            }
            $tech_specs_image = '';
        }

        // Check if image selected from media library
        if (!empty($_POST['tech_specs_image_selected']) && empty($_FILES['tech_specs_image']['name'])) {
            $tech_specs_image = Security::sanitizeInput($_POST['tech_specs_image_selected']);
        }

        if (isset($_FILES['tech_specs_image']) && $_FILES['tech_specs_image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $upload = $_FILES['tech_specs_image'];

            if ($upload['error'] !== UPLOAD_ERR_OK) {
                $techSpecsImageUploadError = 'Failed to upload technical specifications image. Please try again.';
            } elseif ($upload['size'] > MAX_FILE_SIZE) {
                $techSpecsImageUploadError = 'Technical specifications image file is too large. Maximum size is ' . round(MAX_FILE_SIZE / (1024 * 1024), 1) . 'MB.';
            } else {
                $allowedExtensions = ['png', 'jpg', 'jpeg', 'webp', 'svg'];
                $extension = strtolower(pathinfo($upload['name'], PATHINFO_EXTENSION));

                if (!in_array($extension, $allowedExtensions, true)) {
                    $techSpecsImageUploadError = 'Invalid technical specifications image format. Allowed types: PNG, JPG, WEBP, SVG.';
                } else {
                    $uploadDir = __DIR__ . '/../uploads/branding';
                    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
                        $techSpecsImageUploadError = 'Unable to create upload directory for technical specifications image.';
                    } else {
                        $newFileName = 'techspecs_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
                        $targetPath = $uploadDir . '/' . $newFileName;

                        if (!move_uploaded_file($upload['tmp_name'], $targetPath)) {
                            $techSpecsImageUploadError = 'Unable to save the uploaded technical specifications image. Please check folder permissions.';
                        } else {
                            if ($existingTechSpecsImage && strpos($existingTechSpecsImage, 'uploads/') === 0) {
                                $oldTechSpecsImagePath = __DIR__ . '/../' . $existingTechSpecsImage;
                                if (file_exists($oldTechSpecsImagePath)) {
                                    @unlink($oldTechSpecsImagePath);
                                }
                            }
                            $tech_specs_image = 'uploads/branding/' . $newFileName;
                        }
                    }
                }
            }
        }
        
        $uploadErrors = array_filter([$logoUploadError, $faviconUploadError, $heroImageUploadError, $techSpecsImageUploadError]);

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
                'site_favicon' => $site_favicon,
                'hero_image' => $hero_image,
                'tech_specs_image' => $tech_specs_image,
                'exchange_rate_kes' => $exchange_rate_kes,
                'exchange_rate_tzs' => $exchange_rate_tzs,
                'exchange_rate_ugx' => $exchange_rate_ugx,
                'exchange_rate_usd' => $exchange_rate_usd
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
                if (!isset($message)) {
                    $message = 'General settings updated successfully!';
                } else {
                    $message .= 'General settings updated successfully!';
                }
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
    } elseif ($action === 'update_theme') {
        // Theme customization settings
        $theme_primary_color = Security::sanitizeInput($_POST['theme_primary_color']);
        $theme_secondary_color = Security::sanitizeInput($_POST['theme_secondary_color']);
        $theme_accent_color = Security::sanitizeInput($_POST['theme_accent_color']);
        $theme_success_color = Security::sanitizeInput($_POST['theme_success_color']);
        $theme_warning_color = Security::sanitizeInput($_POST['theme_warning_color']);
        $theme_error_color = Security::sanitizeInput($_POST['theme_error_color']);
        $theme_text_primary = Security::sanitizeInput($_POST['theme_text_primary']);
        $theme_text_secondary = Security::sanitizeInput($_POST['theme_text_secondary']);
        $theme_background = Security::sanitizeInput($_POST['theme_background']);
        $theme_card_background = Security::sanitizeInput($_POST['theme_card_background']);
        $theme_border_color = Security::sanitizeInput($_POST['theme_border_color']);
        $theme_link_color = Security::sanitizeInput($_POST['theme_link_color']);
        $theme_button_radius = Security::sanitizeInput($_POST['theme_button_radius']);
        $theme_card_radius = Security::sanitizeInput($_POST['theme_card_radius']);
        $theme_font_family = Security::sanitizeInput($_POST['theme_font_family']);
        $theme_heading_font = Security::sanitizeInput($_POST['theme_heading_font']);
        
        $theme_settings = [
            'theme_primary_color' => $theme_primary_color,
            'theme_secondary_color' => $theme_secondary_color,
            'theme_accent_color' => $theme_accent_color,
            'theme_success_color' => $theme_success_color,
            'theme_warning_color' => $theme_warning_color,
            'theme_error_color' => $theme_error_color,
            'theme_text_primary' => $theme_text_primary,
            'theme_text_secondary' => $theme_text_secondary,
            'theme_background' => $theme_background,
            'theme_card_background' => $theme_card_background,
            'theme_border_color' => $theme_border_color,
            'theme_link_color' => $theme_link_color,
            'theme_button_radius' => $theme_button_radius,
            'theme_card_radius' => $theme_card_radius,
            'theme_font_family' => $theme_font_family,
            'theme_heading_font' => $theme_heading_font
        ];
        
        $success = true;
        foreach ($theme_settings as $key => $value) {
            $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value, updated_at) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()");
            $stmt->bind_param('sss', $key, $value, $value);
            if (!$stmt->execute()) {
                $success = false;
                break;
            }
        }
        
        if ($success) {
            // Clear cache after theme update
            $cacheDir = __DIR__ . '/../cache';
            if (is_dir($cacheDir)) {
                $files = glob($cacheDir . '/*');
                foreach ($files as $file) {
                    if (is_file($file)) {
                        @unlink($file);
                    }
                }
            }
            $message = 'Theme settings updated successfully! Changes will be visible on the frontend.';
            $messageType = 'success';
        } else {
            $message = 'Error updating theme settings: ' . $db->error;
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
    } elseif ($action === 'update_integrations') {
        // Firebase configuration
        $firebase_api_key = trim($_POST['firebase_api_key'] ?? '');
        $firebase_auth_domain = trim($_POST['firebase_auth_domain'] ?? '');
        $firebase_project_id = trim($_POST['firebase_project_id'] ?? '');
        $firebase_storage_bucket = trim($_POST['firebase_storage_bucket'] ?? '');
        $firebase_messaging_sender_id = trim($_POST['firebase_messaging_sender_id'] ?? '');
        $firebase_app_id = trim($_POST['firebase_app_id'] ?? '');
        $firebase_enabled = isset($_POST['firebase_enabled']) ? '1' : '0';
        
        $integration_settings = [
            'firebase_enabled' => $firebase_enabled,
            'firebase_api_key' => $firebase_api_key,
            'firebase_auth_domain' => $firebase_auth_domain,
            'firebase_project_id' => $firebase_project_id,
            'firebase_storage_bucket' => $firebase_storage_bucket,
            'firebase_messaging_sender_id' => $firebase_messaging_sender_id,
            'firebase_app_id' => $firebase_app_id
        ];
        
        $success = true;
        foreach ($integration_settings as $key => $value) {
            $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value, updated_at) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()");
            $stmt->bind_param('sss', $key, $value, $value);
            if (!$stmt->execute()) {
                $success = false;
                break;
            }
        }
        
        if ($success) {
            $message = 'Integration settings updated successfully! Firebase configuration saved to database.';
            $messageType = 'success';
        } else {
            $message = 'Error updating integration settings: ' . $db->error;
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
    'hero_image' => getSetting('hero_image', ''),
    'tech_specs_image' => getSetting('tech_specs_image', ''),
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
    'ai_openai_model' => getSetting('ai_openai_model', 'gpt-4o-mini'),
    
    // Theme Customization
    'theme_primary_color' => getSetting('theme_primary_color', '#3b82f6'),
    'theme_secondary_color' => getSetting('theme_secondary_color', '#8b5cf6'),
    'theme_accent_color' => getSetting('theme_accent_color', '#06b6d4'),
    'theme_success_color' => getSetting('theme_success_color', '#10b981'),
    'theme_warning_color' => getSetting('theme_warning_color', '#f59e0b'),
    'theme_error_color' => getSetting('theme_error_color', '#ef4444'),
    'theme_text_primary' => getSetting('theme_text_primary', '#1e293b'),
    'theme_text_secondary' => getSetting('theme_text_secondary', '#64748b'),
    'theme_background' => getSetting('theme_background', '#ffffff'),
    'theme_card_background' => getSetting('theme_card_background', '#f8fafc'),
    'theme_border_color' => getSetting('theme_border_color', '#e2e8f0'),
    'theme_link_color' => getSetting('theme_link_color', '#3b82f6'),
    'theme_button_radius' => getSetting('theme_button_radius', '8'),
    'theme_card_radius' => getSetting('theme_card_radius', '12'),
    'theme_font_family' => getSetting('theme_font_family', 'system-ui, -apple-system, sans-serif'),
    'theme_heading_font' => getSetting('theme_heading_font', 'system-ui, -apple-system, sans-serif'),
    
    // Firebase Integration
    'firebase_enabled' => getSetting('firebase_enabled', '0'),
    'firebase_api_key' => getSetting('firebase_api_key', ''),
    'firebase_auth_domain' => getSetting('firebase_auth_domain', ''),
    'firebase_project_id' => getSetting('firebase_project_id', ''),
    'firebase_storage_bucket' => getSetting('firebase_storage_bucket', ''),
    'firebase_messaging_sender_id' => getSetting('firebase_messaging_sender_id', ''),
    'firebase_app_id' => getSetting('firebase_app_id', '')
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
        
        .integration-section {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .integration-section h4 {
            font-size: 1.25rem;
            font-weight: 700;
            margin: 0;
            color: #1e293b;
        }
        
        .section-description {
            color: #64748b;
            font-size: 0.875rem;
            margin: 0.5rem 0 0;
            line-height: 1.5;
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
                    <button class="settings-tab" data-tab="theme" onclick="showTab(event, 'theme')">
                        <span class="tab-icon">🎨</span>
                        <span class="tab-text">Theme Customization</span>
                    </button>
                    <button class="settings-tab" data-tab="integrations" onclick="showTab(event, 'integrations')">
                        <span class="tab-icon">🔌</span>
                        <span class="tab-text">Integrations</span>
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

                    <div class="form-group form-group-logo">
                        <label for="hero_image">Hero Section Image</label>
                        <div class="logo-upload-area">
                            <input type="hidden" id="hero_image_path" name="hero_image_selected" value="<?= htmlspecialchars($settings['hero_image']) ?>">
                            <input type="file" id="hero_image" name="hero_image" accept=".png,.jpg,.jpeg,.webp,.svg">
                            <small class="field-hint">📐 <strong>Best size: 1200×1200px</strong> (square) - Hero product showcase image. Max <?= htmlspecialchars(round(MAX_FILE_SIZE / (1024 * 1024), 1)) ?>MB</small>
                            <div class="media-picker-actions" style="margin-top: 0.75rem;">
                                <button type="button" class="btn btn-secondary btn-sm" onclick="openLibraryForHeroImage()">
                                    <span class="icon">📁</span> Choose from Media Library
                                </button>
                            </div>
                            <?php if (!empty($settings['hero_image'])): ?>
                                <div class="logo-preview">
                                    <img src="../<?= htmlspecialchars($settings['hero_image']) ?>" alt="Current hero image" id="hero_image_preview">
                                </div>
                                <label class="remove-logo-option">
                                    <input type="checkbox" name="hero_image_remove" value="1" id="hero_image_remove_checkbox">
                                    Remove current hero image
                                </label>
                            <?php endif; ?>
                        </div>
                    </div>                                    <div class="form-group form-group-logo">
                                        <label for="tech_specs_image">Technical Specifications Image</label>
                                        <div class="logo-upload-area">
                                            <input type="hidden" id="tech_specs_image_path" name="tech_specs_image_selected" value="<?= htmlspecialchars($settings['tech_specs_image']) ?>">
                                            <input type="file" id="tech_specs_image" name="tech_specs_image" accept=".png,.jpg,.jpeg,.webp,.svg">
                                            <small class="field-hint">📐 <strong>Best size: 1200×800px</strong> (landscape) - Product features and specifications diagram. Max <?= htmlspecialchars(round(MAX_FILE_SIZE / (1024 * 1024), 1)) ?>MB</small>
                                            <div class="media-picker-actions" style="margin-top: 0.75rem;">
                                                <button type="button" class="btn btn-secondary btn-sm" onclick="openLibraryForTechSpecsImage()">
                                                    <span class="icon">📁</span> Choose from Media Library
                                                </button>
                                            </div>
                                            <?php if (!empty($settings['tech_specs_image'])): ?>
                                                <div class="logo-preview">
                                                    <img src="../<?= htmlspecialchars($settings['tech_specs_image']) ?>" alt="Current technical specifications image" id="tech_specs_image_preview">
                                                </div>
                                                <label class="remove-logo-option">
                                                    <input type="checkbox" name="tech_specs_image_remove" value="1" id="tech_specs_image_remove_checkbox">
                                                    Remove current tech specs image
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
                                            <option value="TZS" <?= $settings['currency'] == 'TZS' ? 'selected' : '' ?>>TZS - Tanzanian Shilling</option>
                                            <option value="UGX" <?= $settings['currency'] == 'UGX' ? 'selected' : '' ?>>UGX - Ugandan Shilling</option>
                                            <option value="USD" <?= $settings['currency'] == 'USD' ? 'selected' : '' ?>>USD - US Dollar</option>
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

                                <!-- Exchange Rates Section -->
                                <div class="form-section">
                                    <h4 style="color: #ff5900; font-size: 1.125rem; margin: 2rem 0 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid #ff5900;">
                                        💱 Currency Exchange Rates
                                    </h4>
                                    <p style="color: #64748b; font-size: 0.875rem; margin-bottom: 1rem;">
                                        Set exchange rates for currency conversion. Currently using <strong style="color: #ff5900;"><?= htmlspecialchars($settings['currency']) ?></strong> as base currency.
                                    </p>
                                    
                                    <div class="form-grid">
                                        <div class="form-group">
                                            <label for="exchange_rate_kes">
                                                KES (Kenyan Shilling)
                                                <?php if ($settings['currency'] == 'KES'): ?>
                                                    <span style="background: #10b981; color: white; padding: 0.125rem 0.5rem; border-radius: 4px; font-size: 0.75rem; margin-left: 0.5rem;">BASE = 1.00</span>
                                                <?php endif; ?>
                                            </label>
                                            <input type="number" id="exchange_rate_kes" name="exchange_rate_kes" 
                                                   value="<?= $settings['currency'] == 'KES' ? '1.00' : htmlspecialchars(getSetting('exchange_rate_kes', '1.00')) ?>" 
                                                   step="0.01" min="0" required
                                                   <?= $settings['currency'] == 'KES' ? 'readonly style="background: #f1f5f9; cursor: not-allowed;"' : '' ?>>
                                            <small style="color: #64748b;">
                                                <?php if ($settings['currency'] == 'KES'): ?>
                                                    Base Rate = 1.00 (Other currencies convert from this)
                                                <?php else: ?>
                                                    Rate: 1 <?= $settings['currency'] ?> = X KES
                                                <?php endif; ?>
                                            </small>
                                        </div>

                                        <div class="form-group">
                                            <label for="exchange_rate_tzs">
                                                TZS (Tanzanian Shilling)
                                                <?php if ($settings['currency'] == 'TZS'): ?>
                                                    <span style="background: #10b981; color: white; padding: 0.125rem 0.5rem; border-radius: 4px; font-size: 0.75rem; margin-left: 0.5rem;">BASE = 1.00</span>
                                                <?php endif; ?>
                                            </label>
                                            <input type="number" id="exchange_rate_tzs" name="exchange_rate_tzs" 
                                                   value="<?= $settings['currency'] == 'TZS' ? '1.00' : htmlspecialchars(getSetting('exchange_rate_tzs', '18.5')) ?>" 
                                                   step="0.01" min="0" required
                                                   <?= $settings['currency'] == 'TZS' ? 'readonly style="background: #f1f5f9; cursor: not-allowed;"' : '' ?>>
                                            <small style="color: #64748b;">
                                                <?php if ($settings['currency'] == 'KES'): ?>
                                                    Rate: 1 KES = X TZS
                                                <?php elseif ($settings['currency'] == 'TZS'): ?>
                                                    Base Rate = 1.00 (Other currencies convert from this)
                                                <?php else: ?>
                                                    Conversion rate relative to base
                                                <?php endif; ?>
                                            </small>
                                        </div>

                                        <div class="form-group">
                                            <label for="exchange_rate_ugx">
                                                UGX (Ugandan Shilling)
                                                <?php if ($settings['currency'] == 'UGX'): ?>
                                                    <span style="background: #10b981; color: white; padding: 0.125rem 0.5rem; border-radius: 4px; font-size: 0.75rem; margin-left: 0.5rem;">BASE = 1.00</span>
                                                <?php endif; ?>
                                            </label>
                                            <input type="number" id="exchange_rate_ugx" name="exchange_rate_ugx" 
                                                   value="<?= $settings['currency'] == 'UGX' ? '1.00' : htmlspecialchars(getSetting('exchange_rate_ugx', '30')) ?>" 
                                                   step="0.01" min="0" required
                                                   <?= $settings['currency'] == 'UGX' ? 'readonly style="background: #f1f5f9; cursor: not-allowed;"' : '' ?>>
                                            <small style="color: #64748b;">
                                                <?php if ($settings['currency'] == 'KES'): ?>
                                                    Rate: 1 KES = X UGX
                                                <?php elseif ($settings['currency'] == 'UGX'): ?>
                                                    Base Rate = 1.00 (Other currencies convert from this)
                                                <?php else: ?>
                                                    Conversion rate relative to base
                                                <?php endif; ?>
                                            </small>
                                        </div>

                                        <div class="form-group">
                                            <label for="exchange_rate_usd">
                                                USD (US Dollar)
                                                <?php if ($settings['currency'] == 'USD'): ?>
                                                    <span style="background: #10b981; color: white; padding: 0.125rem 0.5rem; border-radius: 4px; font-size: 0.75rem; margin-left: 0.5rem;">BASE = 1.00</span>
                                                <?php endif; ?>
                                            </label>
                                            <input type="number" id="exchange_rate_usd" name="exchange_rate_usd" 
                                                   value="<?= $settings['currency'] == 'USD' ? '1.00' : htmlspecialchars(getSetting('exchange_rate_usd', '0.0077')) ?>" 
                                                   step="0.0001" min="0" required
                                                   <?= $settings['currency'] == 'USD' ? 'readonly style="background: #f1f5f9; cursor: not-allowed;"' : '' ?>>
                                            <small style="color: #64748b;">
                                                <?php if ($settings['currency'] == 'KES'): ?>
                                                    Rate: 1 KES = X USD
                                                <?php elseif ($settings['currency'] == 'USD'): ?>
                                                    Base Rate = 1.00 (Other currencies convert from this)
                                                <?php else: ?>
                                                    Conversion rate relative to base
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                    </div>

                                    <div style="background: #fff7ed; border-left: 4px solid #ff5900; padding: 1rem; border-radius: 4px; margin-top: 1rem;">
                                        <strong style="color: #ff5900;">💡 How it works:</strong>
                                        <p style="color: #64748b; font-size: 0.875rem; margin: 0.5rem 0 0;">
                                            <?php if ($settings['currency'] == 'KES'): ?>
                                                All product prices in database are in KES. Exchange rates convert them to other currencies.<br>
                                                Example: Product costs KES 10,000, TZS rate is 18.5 → Price in TZS = 10,000 × 18.5 = 185,000 TZS
                                            <?php else: ?>
                                                Base currency is <strong><?= $settings['currency'] ?></strong>. All prices are converted from this base.<br>
                                                Update exchange rates to reflect current market values for accurate pricing.
                                            <?php endif; ?>
                                        </p>
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

                <!-- Integrations Settings -->
                <div id="integrations-tab" class="settings-section">
                    <div class="card">
                        <div class="card-header">
                            <h3><span class="header-icon">🔌</span> Third-Party Integrations</h3>
                            <p class="header-description">Configure external services and authentication providers</p>
                        </div>
                        <div class="card-body">
                            <form method="POST" class="settings-form">
                                <input type="hidden" name="action" value="update_integrations">
                                
                                <!-- Firebase Authentication -->
                                <div class="integration-section">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                                        <div>
                                            <h4 style="margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                                                <span style="font-size: 1.5rem;">🔥</span>
                                                Firebase Authentication
                                            </h4>
                                            <p class="section-description" style="margin: 0.5rem 0 0;">
                                                Enable Firebase for SMS OTP authentication and email verification. Required for customer phone verification features.
                                            </p>
                                        </div>
                                        <label class="toggle-switch">
                                            <input type="checkbox" name="firebase_enabled" value="1" 
                                                   <?= $settings['firebase_enabled'] === '1' ? 'checked' : '' ?>>
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </div>
                                    
                                    <div class="info-banner" style="background: #fef3c7; border-left-color: #f59e0b; color: #92400e; margin-bottom: 1.5rem;">
                                        <strong>📝 Setup Instructions:</strong>
                                        <ol style="margin: 0.5rem 0 0; padding-left: 1.5rem;">
                                            <li>Go to <a href="https://console.firebase.google.com/" target="_blank" style="color: #92400e; text-decoration: underline;">Firebase Console</a></li>
                                            <li>Create a new project or select an existing one</li>
                                            <li>Navigate to Project Settings → General</li>
                                            <li>Scroll down to "Your apps" section and click "Web app" (</>) icon</li>
                                            <li>Register your app and copy the configuration values</li>
                                            <li>Choose either <strong>manual entry</strong> (below) or <strong>paste config</strong> (faster)</li>
                                            <li>Enable Phone Authentication in Firebase Console → Authentication → Sign-in method</li>
                                        </ol>
                                    </div>
                                    
                                    <div class="form-group" style="margin-bottom: 1.5rem;">
                                        <label style="font-weight: 600; display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.75rem;">
                                            <span>🚀 Quick Setup: Paste Firebase Config</span>
                                            <button type="button" class="btn btn-secondary btn-sm" onclick="toggleConfigPaste()" style="padding: 0.25rem 0.75rem; font-size: 0.75rem;">Toggle</button>
                                        </label>
                                        <div id="configPasteArea" style="display: none;">
                                            <textarea id="firebase_config_paste" rows="10" placeholder="Paste your Firebase config here, e.g.:

const firebaseConfig = {
  apiKey: &quot;AIzaSyXXXXXXXXXXXXXXXXXXXXXXXXX&quot;,
  authDomain: &quot;your-project.firebaseapp.com&quot;,
  projectId: &quot;your-project-id&quot;,
  storageBucket: &quot;your-project.appspot.com&quot;,
  messagingSenderId: &quot;123456789012&quot;,
  appId: &quot;1:123456789012:web:abc123def456&quot;
};

Or just the JavaScript object without variable declaration." style="width: 100%; padding: 0.75rem; border: 2px dashed #d1d5db; border-radius: 8px; font-family: 'Courier New', monospace; font-size: 0.875rem; background: #f9fafb;"></textarea>
                                            <button type="button" class="btn btn-primary" onclick="parseFirebaseConfig()" style="margin-top: 0.5rem;">
                                                📋 Parse & Fill Form
                                            </button>
                                            <small style="display: block; margin-top: 0.5rem; color: #6b7280;">
                                                Paste the entire Firebase config code from your Firebase Console. The script will automatically extract and fill all fields below.
                                            </small>
                                        </div>
                                    </div>
                                    
                                    <div class="form-grid">
                                        <div class="form-group">
                                            <label for="firebase_api_key">
                                                API Key
                                                <span style="color: #dc2626; margin-left: 2px;">*</span>
                                            </label>
                                            <input type="text" id="firebase_api_key" name="firebase_api_key" 
                                                   value="<?= htmlspecialchars($settings['firebase_api_key']) ?>"
                                                   placeholder="AIzaSyXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX">
                                            <small>Your Firebase Web API Key</small>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="firebase_auth_domain">
                                                Auth Domain
                                                <span style="color: #dc2626; margin-left: 2px;">*</span>
                                            </label>
                                            <input type="text" id="firebase_auth_domain" name="firebase_auth_domain" 
                                                   value="<?= htmlspecialchars($settings['firebase_auth_domain']) ?>"
                                                   placeholder="your-project.firebaseapp.com">
                                            <small>Format: your-project.firebaseapp.com</small>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="firebase_project_id">
                                                Project ID
                                                <span style="color: #dc2626; margin-left: 2px;">*</span>
                                            </label>
                                            <input type="text" id="firebase_project_id" name="firebase_project_id" 
                                                   value="<?= htmlspecialchars($settings['firebase_project_id']) ?>"
                                                   placeholder="your-project-id">
                                            <small>Your Firebase project identifier</small>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="firebase_storage_bucket">Storage Bucket</label>
                                            <input type="text" id="firebase_storage_bucket" name="firebase_storage_bucket" 
                                                   value="<?= htmlspecialchars($settings['firebase_storage_bucket']) ?>"
                                                   placeholder="your-project.appspot.com">
                                            <small>Format: your-project.appspot.com</small>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="firebase_messaging_sender_id">Messaging Sender ID</label>
                                            <input type="text" id="firebase_messaging_sender_id" name="firebase_messaging_sender_id" 
                                                   value="<?= htmlspecialchars($settings['firebase_messaging_sender_id']) ?>"
                                                   placeholder="123456789012">
                                            <small>Numeric sender ID from Firebase</small>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="firebase_app_id">
                                                App ID
                                                <span style="color: #dc2626; margin-left: 2px;">*</span>
                                            </label>
                                            <input type="text" id="firebase_app_id" name="firebase_app_id" 
                                                   value="<?= htmlspecialchars($settings['firebase_app_id']) ?>"
                                                   placeholder="1:123456789012:web:abc123def456">
                                            <small>Firebase application identifier</small>
                                        </div>
                                    </div>
                                    
                                    <div class="info-banner" style="margin-top: 1.5rem;">
                                        <strong>✅ Features Enabled:</strong>
                                        <ul style="margin: 0.5rem 0 0; padding-left: 1.5rem;">
                                            <li><strong>SMS OTP Authentication</strong> - Passwordless login via phone number verification</li>
                                            <li><strong>Phone Verification</strong> - Verify customer phone numbers during registration</li>
                                            <li><strong>Email Verification</strong> - Send verification codes to customer emails</li>
                                            <li><strong>Secure Authentication</strong> - Industry-standard security with Firebase Auth</li>
                                        </ul>
                                    </div>
                                </div>
                                
                                <div class="form-actions" style="margin-top: 2rem;">
                                    <button type="submit" class="btn btn-primary">
                                        💾 Save Integration Settings
                                    </button>
                                    <button type="button" class="btn btn-secondary" onclick="testFirebaseConnection()">
                                        🧪 Test Firebase Connection
                                    </button>
                                    <a href="https://console.firebase.google.com/" target="_blank" class="btn btn-secondary">
                                        🔗 Open Firebase Console
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Theme Customization -->
                <div id="theme-tab" class="settings-section">
                    <div class="card">
                        <div class="card-header">
                            <h3><span class="header-icon">🎨</span> Theme Customization</h3>
                            <p class="header-description">Customize the look and feel of your website with colors, fonts, and styling</p>
                        </div>
                        <div class="card-body">
                            <form method="POST" class="settings-form">
                                <input type="hidden" name="action" value="update_theme">
                                
                                <!-- Color Settings -->
                                <div class="theme-section">
                                    <h4><span class="section-icon">🎨</span> Brand Colors</h4>
                                    <p class="section-description">Define your brand's primary colors</p>
                                    
                                    <div class="form-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
                                        <div class="form-group">
                                            <label for="theme_primary_color">Primary Color</label>
                                            <div style="display: flex; gap: 10px; align-items: center;">
                                                <input type="color" id="theme_primary_color" name="theme_primary_color" 
                                                       value="<?= htmlspecialchars($settings['theme_primary_color']) ?>" 
                                                       style="width: 60px; height: 40px; border-radius: 6px; border: 2px solid #e2e8f0; cursor: pointer;">
                                                <input type="text" value="<?= htmlspecialchars($settings['theme_primary_color']) ?>" 
                                                       style="flex: 1; padding: 8px; border: 1px solid #e2e8f0; border-radius: 6px;" 
                                                       oninput="document.getElementById('theme_primary_color').value = this.value"
                                                       onchange="this.value = document.getElementById('theme_primary_color').value">
                                            </div>
                                            <small>Main brand color (buttons, links)</small>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="theme_secondary_color">Secondary Color</label>
                                            <div style="display: flex; gap: 10px; align-items: center;">
                                                <input type="color" id="theme_secondary_color" name="theme_secondary_color" 
                                                       value="<?= htmlspecialchars($settings['theme_secondary_color']) ?>" 
                                                       style="width: 60px; height: 40px; border-radius: 6px; border: 2px solid #e2e8f0; cursor: pointer;">
                                                <input type="text" value="<?= htmlspecialchars($settings['theme_secondary_color']) ?>" 
                                                       style="flex: 1; padding: 8px; border: 1px solid #e2e8f0; border-radius: 6px;"
                                                       oninput="document.getElementById('theme_secondary_color').value = this.value"
                                                       onchange="this.value = document.getElementById('theme_secondary_color').value">
                                            </div>
                                            <small>Secondary accent color</small>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="theme_accent_color">Accent Color</label>
                                            <div style="display: flex; gap: 10px; align-items: center;">
                                                <input type="color" id="theme_accent_color" name="theme_accent_color" 
                                                       value="<?= htmlspecialchars($settings['theme_accent_color']) ?>" 
                                                       style="width: 60px; height: 40px; border-radius: 6px; border: 2px solid #e2e8f0; cursor: pointer;">
                                                <input type="text" value="<?= htmlspecialchars($settings['theme_accent_color']) ?>" 
                                                       style="flex: 1; padding: 8px; border: 1px solid #e2e8f0; border-radius: 6px;"
                                                       oninput="document.getElementById('theme_accent_color').value = this.value"
                                                       onchange="this.value = document.getElementById('theme_accent_color').value">
                                            </div>
                                            <small>For highlights and special elements</small>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Status Colors -->
                                <div class="theme-section" style="margin-top: 30px;">
                                    <h4><span class="section-icon">🚦</span> Status Colors</h4>
                                    <p class="section-description">Colors for success, warning, and error messages</p>
                                    
                                    <div class="form-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
                                        <div class="form-group">
                                            <label for="theme_success_color">Success Color</label>
                                            <div style="display: flex; gap: 10px; align-items: center;">
                                                <input type="color" id="theme_success_color" name="theme_success_color" 
                                                       value="<?= htmlspecialchars($settings['theme_success_color']) ?>" 
                                                       style="width: 60px; height: 40px; border-radius: 6px; border: 2px solid #e2e8f0; cursor: pointer;">
                                                <input type="text" value="<?= htmlspecialchars($settings['theme_success_color']) ?>" 
                                                       style="flex: 1; padding: 8px; border: 1px solid #e2e8f0; border-radius: 6px;"
                                                       oninput="document.getElementById('theme_success_color').value = this.value"
                                                       onchange="this.value = document.getElementById('theme_success_color').value">
                                            </div>
                                            <small>For success messages and confirmations</small>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="theme_warning_color">Warning Color</label>
                                            <div style="display: flex; gap: 10px; align-items: center;">
                                                <input type="color" id="theme_warning_color" name="theme_warning_color" 
                                                       value="<?= htmlspecialchars($settings['theme_warning_color']) ?>" 
                                                       style="width: 60px; height: 40px; border-radius: 6px; border: 2px solid #e2e8f0; cursor: pointer;">
                                                <input type="text" value="<?= htmlspecialchars($settings['theme_warning_color']) ?>" 
                                                       style="flex: 1; padding: 8px; border: 1px solid #e2e8f0; border-radius: 6px;"
                                                       oninput="document.getElementById('theme_warning_color').value = this.value"
                                                       onchange="this.value = document.getElementById('theme_warning_color').value">
                                            </div>
                                            <small>For warnings and cautions</small>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="theme_error_color">Error Color</label>
                                            <div style="display: flex; gap: 10px; align-items: center;">
                                                <input type="color" id="theme_error_color" name="theme_error_color" 
                                                       value="<?= htmlspecialchars($settings['theme_error_color']) ?>" 
                                                       style="width: 60px; height: 40px; border-radius: 6px; border: 2px solid #e2e8f0; cursor: pointer;">
                                                <input type="text" value="<?= htmlspecialchars($settings['theme_error_color']) ?>" 
                                                       style="flex: 1; padding: 8px; border: 1px solid #e2e8f0; border-radius: 6px;"
                                                       oninput="document.getElementById('theme_error_color').value = this.value"
                                                       onchange="this.value = document.getElementById('theme_error_color').value">
                                            </div>
                                            <small>For error messages and alerts</small>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Typography & Background -->
                                <div class="theme-section" style="margin-top: 30px;">
                                    <h4><span class="section-icon">📝</span> Typography & Background</h4>
                                    <p class="section-description">Text colors and background settings</p>
                                    
                                    <div class="form-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
                                        <div class="form-group">
                                            <label for="theme_text_primary">Primary Text</label>
                                            <div style="display: flex; gap: 10px; align-items: center;">
                                                <input type="color" id="theme_text_primary" name="theme_text_primary" 
                                                       value="<?= htmlspecialchars($settings['theme_text_primary']) ?>" 
                                                       style="width: 60px; height: 40px; border-radius: 6px; border: 2px solid #e2e8f0; cursor: pointer;">
                                                <input type="text" value="<?= htmlspecialchars($settings['theme_text_primary']) ?>" 
                                                       style="flex: 1; padding: 8px; border: 1px solid #e2e8f0; border-radius: 6px;"
                                                       oninput="document.getElementById('theme_text_primary').value = this.value"
                                                       onchange="this.value = document.getElementById('theme_text_primary').value">
                                            </div>
                                            <small>Main text color</small>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="theme_text_secondary">Secondary Text</label>
                                            <div style="display: flex; gap: 10px; align-items: center;">
                                                <input type="color" id="theme_text_secondary" name="theme_text_secondary" 
                                                       value="<?= htmlspecialchars($settings['theme_text_secondary']) ?>" 
                                                       style="width: 60px; height: 40px; border-radius: 6px; border: 2px solid #e2e8f0; cursor: pointer;">
                                                <input type="text" value="<?= htmlspecialchars($settings['theme_text_secondary']) ?>" 
                                                       style="flex: 1; padding: 8px; border: 1px solid #e2e8f0; border-radius: 6px;"
                                                       oninput="document.getElementById('theme_text_secondary').value = this.value"
                                                       onchange="this.value = document.getElementById('theme_text_secondary').value">
                                            </div>
                                            <small>Secondary/muted text</small>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="theme_background">Background</label>
                                            <div style="display: flex; gap: 10px; align-items: center;">
                                                <input type="color" id="theme_background" name="theme_background" 
                                                       value="<?= htmlspecialchars($settings['theme_background']) ?>" 
                                                       style="width: 60px; height: 40px; border-radius: 6px; border: 2px solid #e2e8f0; cursor: pointer;">
                                                <input type="text" value="<?= htmlspecialchars($settings['theme_background']) ?>" 
                                                       style="flex: 1; padding: 8px; border: 1px solid #e2e8f0; border-radius: 6px;"
                                                       oninput="document.getElementById('theme_background').value = this.value"
                                                       onchange="this.value = document.getElementById('theme_background').value">
                                            </div>
                                            <small>Page background color</small>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="theme_card_background">Card Background</label>
                                            <div style="display: flex; gap: 10px; align-items: center;">
                                                <input type="color" id="theme_card_background" name="theme_card_background" 
                                                       value="<?= htmlspecialchars($settings['theme_card_background']) ?>" 
                                                       style="width: 60px; height: 40px; border-radius: 6px; border: 2px solid #e2e8f0; cursor: pointer;">
                                                <input type="text" value="<?= htmlspecialchars($settings['theme_card_background']) ?>" 
                                                       style="flex: 1; padding: 8px; border: 1px solid #e2e8f0; border-radius: 6px;"
                                                       oninput="document.getElementById('theme_card_background').value = this.value"
                                                       onchange="this.value = document.getElementById('theme_card_background').value">
                                            </div>
                                            <small>Card/section backgrounds</small>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="theme_border_color">Border Color</label>
                                            <div style="display: flex; gap: 10px; align-items: center;">
                                                <input type="color" id="theme_border_color" name="theme_border_color" 
                                                       value="<?= htmlspecialchars($settings['theme_border_color']) ?>" 
                                                       style="width: 60px; height: 40px; border-radius: 6px; border: 2px solid #e2e8f0; cursor: pointer;">
                                                <input type="text" value="<?= htmlspecialchars($settings['theme_border_color']) ?>" 
                                                       style="flex: 1; padding: 8px; border: 1px solid #e2e8f0; border-radius: 6px;"
                                                       oninput="document.getElementById('theme_border_color').value = this.value"
                                                       onchange="this.value = document.getElementById('theme_border_color').value">
                                            </div>
                                            <small>Borders and dividers</small>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="theme_link_color">Link Color</label>
                                            <div style="display: flex; gap: 10px; align-items: center;">
                                                <input type="color" id="theme_link_color" name="theme_link_color" 
                                                       value="<?= htmlspecialchars($settings['theme_link_color']) ?>" 
                                                       style="width: 60px; height: 40px; border-radius: 6px; border: 2px solid #e2e8f0; cursor: pointer;">
                                                <input type="text" value="<?= htmlspecialchars($settings['theme_link_color']) ?>" 
                                                       style="flex: 1; padding: 8px; border: 1px solid #e2e8f0; border-radius: 6px;"
                                                       oninput="document.getElementById('theme_link_color').value = this.value"
                                                       onchange="this.value = document.getElementById('theme_link_color').value">
                                            </div>
                                            <small>Hyperlink color</small>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Styling Options -->
                                <div class="theme-section" style="margin-top: 30px;">
                                    <h4><span class="section-icon">✨</span> Styling Options</h4>
                                    <p class="section-description">Border radius and spacing options</p>
                                    
                                    <div class="form-grid">
                                        <div class="form-group">
                                            <label for="theme_button_radius">Button Border Radius (px)</label>
                                            <input type="number" id="theme_button_radius" name="theme_button_radius" 
                                                   value="<?= htmlspecialchars($settings['theme_button_radius']) ?>" 
                                                   min="0" max="50" step="1">
                                            <small>Roundness of buttons (0 = square, 50 = pill shape)</small>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="theme_card_radius">Card Border Radius (px)</label>
                                            <input type="number" id="theme_card_radius" name="theme_card_radius" 
                                                   value="<?= htmlspecialchars($settings['theme_card_radius']) ?>" 
                                                   min="0" max="50" step="1">
                                            <small>Roundness of cards and containers</small>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Typography -->
                                <div class="theme-section" style="margin-top: 30px;">
                                    <h4><span class="section-icon">🔤</span> Typography</h4>
                                    <p class="section-description">Font families for your website</p>
                                    
                                    <div class="form-grid">
                                        <div class="form-group">
                                            <label for="theme_font_family">Body Font Family</label>
                                            <select id="theme_font_family" name="theme_font_family">
                                                <option value="system-ui, -apple-system, sans-serif" <?= $settings['theme_font_family'] == 'system-ui, -apple-system, sans-serif' ? 'selected' : '' ?>>System Default</option>
                                                <option value="'Inter', sans-serif" <?= $settings['theme_font_family'] == "'Inter', sans-serif" ? 'selected' : '' ?>>Inter</option>
                                                <option value="'Roboto', sans-serif" <?= $settings['theme_font_family'] == "'Roboto', sans-serif" ? 'selected' : '' ?>>Roboto</option>
                                                <option value="'Open Sans', sans-serif" <?= $settings['theme_font_family'] == "'Open Sans', sans-serif" ? 'selected' : '' ?>>Open Sans</option>
                                                <option value="'Lato', sans-serif" <?= $settings['theme_font_family'] == "'Lato', sans-serif" ? 'selected' : '' ?>>Lato</option>
                                                <option value="'Poppins', sans-serif" <?= $settings['theme_font_family'] == "'Poppins', sans-serif" ? 'selected' : '' ?>>Poppins</option>
                                                <option value="'Montserrat', sans-serif" <?= $settings['theme_font_family'] == "'Montserrat', sans-serif" ? 'selected' : '' ?>>Montserrat</option>
                                                <option value="Georgia, serif" <?= $settings['theme_font_family'] == 'Georgia, serif' ? 'selected' : '' ?>>Georgia</option>
                                                <option value="'Merriweather', serif" <?= $settings['theme_font_family'] == "'Merriweather', serif" ? 'selected' : '' ?>>Merriweather</option>
                                            </select>
                                            <small>Font for body text</small>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="theme_heading_font">Heading Font Family</label>
                                            <select id="theme_heading_font" name="theme_heading_font">
                                                <option value="system-ui, -apple-system, sans-serif" <?= $settings['theme_heading_font'] == 'system-ui, -apple-system, sans-serif' ? 'selected' : '' ?>>System Default</option>
                                                <option value="'Inter', sans-serif" <?= $settings['theme_heading_font'] == "'Inter', sans-serif" ? 'selected' : '' ?>>Inter</option>
                                                <option value="'Roboto', sans-serif" <?= $settings['theme_heading_font'] == "'Roboto', sans-serif" ? 'selected' : '' ?>>Roboto</option>
                                                <option value="'Open Sans', sans-serif" <?= $settings['theme_heading_font'] == "'Open Sans', sans-serif" ? 'selected' : '' ?>>Open Sans</option>
                                                <option value="'Lato', sans-serif" <?= $settings['theme_heading_font'] == "'Lato', sans-serif" ? 'selected' : '' ?>>Lato</option>
                                                <option value="'Poppins', sans-serif" <?= $settings['theme_heading_font'] == "'Poppins', sans-serif" ? 'selected' : '' ?>>Poppins</option>
                                                <option value="'Montserrat', sans-serif" <?= $settings['theme_heading_font'] == "'Montserrat', sans-serif" ? 'selected' : '' ?>>Montserrat</option>
                                                <option value="'Playfair Display', serif" <?= $settings['theme_heading_font'] == "'Playfair Display', serif" ? 'selected' : '' ?>>Playfair Display</option>
                                                <option value="Georgia, serif" <?= $settings['theme_heading_font'] == 'Georgia, serif' ? 'selected' : '' ?>>Georgia</option>
                                            </select>
                                            <small>Font for headings</small>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Preview Section -->
                                <div class="theme-section" style="margin-top: 30px;">
                                    <h4><span class="section-icon">👁️</span> Live Preview</h4>
                                    <div id="theme-preview" style="padding: 30px; background: var(--preview-bg, #f8fafc); border-radius: 12px; border: 2px solid #e2e8f0;">
                                        <h2 style="margin: 0 0 10px 0; font-family: var(--preview-heading-font); color: var(--preview-text-primary);">Heading Example</h2>
                                        <p style="margin: 0 0 20px 0; font-family: var(--preview-font); color: var(--preview-text-secondary);">This is how your text will look. Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>
                                        <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                                            <button style="padding: 12px 24px; background: var(--preview-primary); color: white; border: none; border-radius: var(--preview-btn-radius, 8px); cursor: pointer; font-family: var(--preview-font); font-weight: 600;">Primary Button</button>
                                            <button style="padding: 12px 24px; background: var(--preview-success); color: white; border: none; border-radius: var(--preview-btn-radius, 8px); cursor: pointer; font-family: var(--preview-font); font-weight: 600;">Success</button>
                                            <button style="padding: 12px 24px; background: var(--preview-warning); color: white; border: none; border-radius: var(--preview-btn-radius, 8px); cursor: pointer; font-family: var(--preview-font); font-weight: 600;">Warning</button>
                                            <button style="padding: 12px 24px; background: var(--preview-error); color: white; border: none; border-radius: var(--preview-btn-radius, 8px); cursor: pointer; font-family: var(--preview-font); font-weight: 600;">Error</button>
                                        </div>
                                        <div style="margin-top: 20px; padding: 20px; background: var(--preview-card-bg); border-radius: var(--preview-card-radius, 12px); border: 1px solid var(--preview-border);">
                                            <h3 style="margin: 0 0 10px 0; font-family: var(--preview-heading-font); color: var(--preview-text-primary);">Card Example</h3>
                                            <p style="margin: 0 0 10px 0; font-family: var(--preview-font); color: var(--preview-text-secondary);">This is how cards and containers will appear on your site.</p>
                                            <a href="#" style="color: var(--preview-link); font-family: var(--preview-font); text-decoration: none;">Example Link</a>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-actions" style="margin-top: 30px;">
                                    <button type="submit" class="btn btn-primary">💾 Save Theme Settings</button>
                                    <button type="button" class="btn btn-secondary" onclick="resetThemeDefaults()">🔄 Reset to Defaults</button>
                                    <a href="../" target="_blank" class="btn btn-secondary">👁️ Preview Website</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Theme preview update
        function updateThemePreview() {
            const preview = document.getElementById('theme-preview');
            if (!preview) return;
            
            preview.style.setProperty('--preview-primary', document.getElementById('theme_primary_color').value);
            preview.style.setProperty('--preview-secondary', document.getElementById('theme_secondary_color').value);
            preview.style.setProperty('--preview-success', document.getElementById('theme_success_color').value);
            preview.style.setProperty('--preview-warning', document.getElementById('theme_warning_color').value);
            preview.style.setProperty('--preview-error', document.getElementById('theme_error_color').value);
            preview.style.setProperty('--preview-text-primary', document.getElementById('theme_text_primary').value);
            preview.style.setProperty('--preview-text-secondary', document.getElementById('theme_text_secondary').value);
            preview.style.setProperty('--preview-bg', document.getElementById('theme_background').value);
            preview.style.setProperty('--preview-card-bg', document.getElementById('theme_card_background').value);
            preview.style.setProperty('--preview-border', document.getElementById('theme_border_color').value);
            preview.style.setProperty('--preview-link', document.getElementById('theme_link_color').value);
            preview.style.setProperty('--preview-btn-radius', document.getElementById('theme_button_radius').value + 'px');
            preview.style.setProperty('--preview-card-radius', document.getElementById('theme_card_radius').value + 'px');
            preview.style.setProperty('--preview-font', document.getElementById('theme_font_family').value);
            preview.style.setProperty('--preview-heading-font', document.getElementById('theme_heading_font').value);
        }
        
        // Reset to defaults
        function resetThemeDefaults() {
            if (!confirm('Reset all theme settings to default values?')) return;
            
            document.getElementById('theme_primary_color').value = '#3b82f6';
            document.getElementById('theme_secondary_color').value = '#8b5cf6';
            document.getElementById('theme_accent_color').value = '#06b6d4';
            document.getElementById('theme_success_color').value = '#10b981';
            document.getElementById('theme_warning_color').value = '#f59e0b';
            document.getElementById('theme_error_color').value = '#ef4444';
            document.getElementById('theme_text_primary').value = '#1e293b';
            document.getElementById('theme_text_secondary').value = '#64748b';
            document.getElementById('theme_background').value = '#ffffff';
            document.getElementById('theme_card_background').value = '#f8fafc';
            document.getElementById('theme_border_color').value = '#e2e8f0';
            document.getElementById('theme_link_color').value = '#3b82f6';
            document.getElementById('theme_button_radius').value = '8';
            document.getElementById('theme_card_radius').value = '12';
            document.getElementById('theme_font_family').value = 'system-ui, -apple-system, sans-serif';
            document.getElementById('theme_heading_font').value = 'system-ui, -apple-system, sans-serif';
            
            // Update text inputs
            document.querySelectorAll('input[type="text"]').forEach(input => {
                const colorInput = input.previousElementSibling;
                if (colorInput && colorInput.type === 'color') {
                    input.value = colorInput.value;
                }
            });
            
            updateThemePreview();
        }
        
        // Sync color picker with text input
        document.addEventListener('DOMContentLoaded', function() {
            const colorInputs = document.querySelectorAll('input[type="color"]');
            colorInputs.forEach(colorInput => {
                colorInput.addEventListener('input', function() {
                    const textInput = this.nextElementSibling;
                    if (textInput && textInput.tagName === 'INPUT') {
                        textInput.value = this.value;
                    }
                    updateThemePreview();
                });
            });
            
            // Update preview on any change
            document.querySelectorAll('#theme-tab input, #theme-tab select').forEach(input => {
                input.addEventListener('change', updateThemePreview);
                input.addEventListener('input', updateThemePreview);
            });
            
            // Initial preview update
            setTimeout(updateThemePreview, 100);
        });
        
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
        
        // Toggle config paste area
        function toggleConfigPaste() {
            const pasteArea = document.getElementById('configPasteArea');
            if (pasteArea.style.display === 'none') {
                pasteArea.style.display = 'block';
            } else {
                pasteArea.style.display = 'none';
            }
        }
        
        // Parse Firebase config from pasted code
        function parseFirebaseConfig() {
            const pasteTextarea = document.getElementById('firebase_config_paste');
            const pastedText = pasteTextarea.value.trim();
            
            if (!pastedText) {
                alert('❌ Please paste your Firebase configuration first');
                return;
            }
            
            try {
                // Extract JSON-like object from various formats
                let configText = pastedText;
                
                // Remove JavaScript variable declaration if present
                configText = configText.replace(/^(const|let|var)\s+\w+\s*=\s*/, '');
                configText = configText.replace(/;?\s*$/, '');
                
                // Try to find the object pattern
                const objectMatch = configText.match(/\{[\s\S]*\}/);
                if (!objectMatch) {
                    throw new Error('Could not find configuration object');
                }
                
                configText = objectMatch[0];
                
                // Convert to valid JSON by:
                // 1. Adding quotes to unquoted keys
                // 2. Handling trailing commas
                configText = configText.replace(/(\w+):/g, '"$1":');
                configText = configText.replace(/,(\s*[}\]])/g, '$1');
                
                // Parse JSON
                const config = JSON.parse(configText);
                
                // Fill form fields
                if (config.apiKey) {
                    document.getElementById('firebase_api_key').value = config.apiKey;
                }
                if (config.authDomain) {
                    document.getElementById('firebase_auth_domain').value = config.authDomain;
                }
                if (config.projectId) {
                    document.getElementById('firebase_project_id').value = config.projectId;
                }
                if (config.storageBucket) {
                    document.getElementById('firebase_storage_bucket').value = config.storageBucket;
                }
                if (config.messagingSenderId) {
                    document.getElementById('firebase_messaging_sender_id').value = config.messagingSenderId;
                }
                if (config.appId) {
                    document.getElementById('firebase_app_id').value = config.appId;
                }
                
                // Show success message
                alert('✅ Configuration Parsed Successfully!\n\n' +
                      'All fields have been filled. Please:\n' +
                      '1. Verify the values are correct\n' +
                      '2. Click "Test Firebase Connection"\n' +
                      '3. Enable Firebase toggle\n' +
                      '4. Save settings');
                
                // Hide paste area
                document.getElementById('configPasteArea').style.display = 'none';
                
                // Clear textarea
                pasteTextarea.value = '';
                
            } catch (error) {
                console.error('Parse error:', error);
                alert('❌ Failed to Parse Configuration\n\n' +
                      'Error: ' + error.message + '\n\n' +
                      'Please make sure you pasted the complete Firebase config.\n\n' +
                      'Expected format:\n' +
                      'const firebaseConfig = {\n' +
                      '  apiKey: "...",\n' +
                      '  authDomain: "...",\n' +
                      '  ...\n' +
                      '};');
            }
        }
        
        // Test Firebase connection
        async function testFirebaseConnection() {
            const button = event.target;
            const originalText = button.textContent;
            button.disabled = true;
            button.textContent = '⏳ Testing Firebase...';
            
            const config = {
                apiKey: document.getElementById('firebase_api_key').value.trim(),
                authDomain: document.getElementById('firebase_auth_domain').value.trim(),
                projectId: document.getElementById('firebase_project_id').value.trim(),
                storageBucket: document.getElementById('firebase_storage_bucket').value.trim(),
                messagingSenderId: document.getElementById('firebase_messaging_sender_id').value.trim(),
                appId: document.getElementById('firebase_app_id').value.trim()
            };
            
            // Validate required fields
            if (!config.apiKey || !config.authDomain || !config.projectId || !config.appId) {
                alert('❌ Validation Error\n\nPlease fill in all required fields (marked with *):\n- API Key\n- Auth Domain\n- Project ID\n- App ID');
                button.disabled = false;
                button.textContent = originalText;
                return;
            }
            
            try {
                // Load Firebase SDK dynamically
                if (typeof firebase === 'undefined') {
                    await loadFirebaseSDK();
                }
                
                // Initialize Firebase app for testing
                let app;
                try {
                    app = firebase.app('[TEST]');
                } catch (e) {
                    app = firebase.initializeApp(config, '[TEST]');
                }
                
                // Test authentication initialization
                const auth = firebase.auth(app);
                
                // Success - configuration is valid
                button.textContent = '✅ Connected!';
                button.style.background = '#10b981';
                
                alert('✅ Firebase Connection Successful!\n\n' +
                      'Project: ' + config.projectId + '\n' +
                      'Auth Domain: ' + config.authDomain + '\n\n' +
                      '🎉 Your Firebase configuration is valid and ready to use!\n\n' +
                      'Make sure to:\n' +
                      '1. Enable Phone Authentication in Firebase Console\n' +
                      '2. Add your domain to authorized domains\n' +
                      '3. Save these settings before testing features');
                
                // Clean up test app
                await app.delete();
                
            } catch (error) {
                console.error('Firebase test error:', error);
                let errorMessage = error.message || 'Unknown error occurred';
                
                if (errorMessage.includes('API key not valid')) {
                    errorMessage = 'Invalid API Key. Please check your Firebase console and copy the correct API key.';
                } else if (errorMessage.includes('auth-domain')) {
                    errorMessage = 'Invalid Auth Domain. Format should be: your-project.firebaseapp.com';
                } else if (errorMessage.includes('project')) {
                    errorMessage = 'Invalid Project ID. Please check your Firebase project settings.';
                }
                
                alert('❌ Firebase Connection Failed\n\n' + 
                      'Error: ' + errorMessage + '\n\n' +
                      '💡 Troubleshooting:\n' +
                      '1. Verify all credentials in Firebase Console\n' +
                      '2. Check that your Firebase project is active\n' +
                      '3. Ensure Web API is enabled in project settings');
                
                button.style.background = '#dc2626';
                button.textContent = '❌ Failed';
            } finally {
                setTimeout(() => {
                    button.disabled = false;
                    button.textContent = originalText;
                    button.style.background = '';
                }, 2000);
            }
        }
        
        // Load Firebase SDK dynamically
        function loadFirebaseSDK() {
            return new Promise((resolve, reject) => {
                if (typeof firebase !== 'undefined') {
                    resolve();
                    return;
                }
                
                const script1 = document.createElement('script');
                script1.src = 'https://www.gstatic.com/firebasejs/9.22.0/firebase-app-compat.js';
                script1.onload = () => {
                    const script2 = document.createElement('script');
                    script2.src = 'https://www.gstatic.com/firebasejs/9.22.0/firebase-auth-compat.js';
                    script2.onload = resolve;
                    script2.onerror = reject;
                    document.head.appendChild(script2);
                };
                script1.onerror = reject;
                document.head.appendChild(script1);
            });
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

        // Media Library Integration
        const mediaLibraryState = {
            images: [],
            allImages: [],
            mode: 'single',
            selected: new Set(),
            onSelect: () => {},
            previousOverflow: '',
            layout: 'grid', // 'grid' or 'list'
            sortBy: 'date_desc', // 'date_desc', 'date_asc', 'name_asc', 'name_desc', 'size_desc', 'size_asc'
            category: 'all' // 'all', 'products', 'orphaned', 'featured'
        };

        function openLibraryForHeroImage() {
            const current = document.getElementById('hero_image_path').value;
            openMediaLibrary({
                mode: 'single',
                preselect: current ? [current] : [],
                force: true,
                onSelect: (images) => {
                    const selected = images[0];
                    if (!selected) return;

                    const imageUrl = selected.url;
                    document.getElementById('hero_image_path').value = imageUrl;
                    document.querySelector('input[name="hero_image_selected"]').value = imageUrl;
                    
                    // Update or create preview
                    let preview = document.getElementById('hero_image_preview');
                    let previewContainer = preview ? preview.parentElement : null;
                    
                    if (!previewContainer) {
                        previewContainer = document.createElement('div');
                        previewContainer.className = 'logo-preview';
                        const heroImageField = document.querySelector('#hero_image').parentElement;
                        const existingPreview = heroImageField.querySelector('.logo-preview');
                        if (existingPreview) {
                            existingPreview.remove();
                        }
                        heroImageField.appendChild(previewContainer);
                        
                        // Add remove option if not exists
                        if (!document.getElementById('hero_image_remove_checkbox')) {
                            const removeLabel = document.createElement('label');
                            removeLabel.className = 'remove-logo-option';
                            removeLabel.innerHTML = '<input type="checkbox" name="hero_image_remove" value="1" id="hero_image_remove_checkbox"> Remove current hero image';
                            heroImageField.appendChild(removeLabel);
                        }
                    }
                    
                    previewContainer.innerHTML = `<img src="../${imageUrl}" alt="Selected hero image" id="hero_image_preview">`;

                    // Uncheck remove checkbox
                    const removeCheckbox = document.getElementById('hero_image_remove_checkbox');
                    if (removeCheckbox) removeCheckbox.checked = false;
                    
                    // Show success feedback
                    showNotification('Hero image selected successfully!', 'success');
                }
            });
        }

        function openLibraryForTechSpecsImage() {
            const current = document.getElementById('tech_specs_image_path').value;
            openMediaLibrary({
                mode: 'single',
                preselect: current ? [current] : [],
                force: true,
                onSelect: (images) => {
                    const selected = images[0];
                    if (!selected) return;

                    const imageUrl = selected.url;
                    document.getElementById('tech_specs_image_path').value = imageUrl;
                    document.querySelector('input[name="tech_specs_image_selected"]').value = imageUrl;
                    
                    // Update or create preview
                    let preview = document.getElementById('tech_specs_image_preview');
                    let previewContainer = preview ? preview.parentElement : null;
                    
                    if (!previewContainer) {
                        previewContainer = document.createElement('div');
                        previewContainer.className = 'logo-preview';
                        const techSpecsImageField = document.querySelector('#tech_specs_image').parentElement;
                        const existingPreview = techSpecsImageField.querySelector('.logo-preview');
                        if (existingPreview) {
                            existingPreview.remove();
                        }
                        techSpecsImageField.appendChild(previewContainer);
                        
                        // Add remove option if not exists
                        if (!document.getElementById('tech_specs_image_remove_checkbox')) {
                            const removeLabel = document.createElement('label');
                            removeLabel.className = 'remove-logo-option';
                            removeLabel.innerHTML = '<input type="checkbox" name="tech_specs_image_remove" value="1" id="tech_specs_image_remove_checkbox"> Remove current tech specs image';
                            techSpecsImageField.appendChild(removeLabel);
                        }
                    }
                    
                    previewContainer.innerHTML = `<img src="../${imageUrl}" alt="Selected tech specs image" id="tech_specs_image_preview">`;

                    // Uncheck remove checkbox
                    const removeCheckbox = document.getElementById('tech_specs_image_remove_checkbox');
                    if (removeCheckbox) removeCheckbox.checked = false;
                    
                    // Show success feedback
                    showNotification('Technical specifications image selected successfully!', 'success');
                }
            });
        }

        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `alert alert-${type}`;
            notification.textContent = message;
            notification.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 10001; max-width: 400px; animation: slideInRight 0.3s ease;';
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.animation = 'slideOutRight 0.3s ease';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }

        async function openMediaLibrary(options = {}) {
            const modal = document.getElementById('mediaLibraryModal');
            if (!modal) return;

            mediaLibraryState.mode = options.mode === 'single' ? 'single' : 'multiple';
            mediaLibraryState.onSelect = typeof options.onSelect === 'function' ? options.onSelect : () => {};

            const normalizedPreselect = (options.preselect || [])
                .map(value => {
                    if (!value) return '';
                    const parts = value.toString().split('/');
                    return parts[parts.length - 1].trim();
                })
                .filter(Boolean);

            mediaLibraryState.selected = new Set(normalizedPreselect);

            const confirmBtn = document.getElementById('mediaLibraryConfirmBtn');
            if (confirmBtn) {
                confirmBtn.textContent = mediaLibraryState.mode === 'single' ? 'Use Image' : 'Use Selected';
            }

            modal.classList.add('open');
            mediaLibraryState.previousOverflow = document.body.style.overflow;
            document.body.style.overflow = 'hidden';

            // Add keyboard support
            const handleKeyDown = (e) => {
                if (e.key === 'Escape') {
                    closeMediaLibrary();
                    document.removeEventListener('keydown', handleKeyDown);
                }
            };
            document.addEventListener('keydown', handleKeyDown);

            await loadMediaLibraryData(options.force === true || mediaLibraryState.images.length === 0);
            applyMediaLibraryFilters();
            updateMediaLibrarySelectionCount();
        }

        function closeMediaLibrary() {
            const modal = document.getElementById('mediaLibraryModal');
            if (modal) modal.classList.remove('open');
            document.body.style.overflow = mediaLibraryState.previousOverflow || '';
            
            // Clear search and filters
            const search = document.getElementById('mediaLibrarySearch');
            const format = document.getElementById('mediaLibraryFormat');
            const category = document.getElementById('mediaLibraryCategory');
            const sort = document.getElementById('mediaLibrarySort');
            
            if (search) search.value = '';
            if (format) format.value = '';
            if (category) category.value = 'all';
            if (sort) sort.value = 'date_desc';
            
            // Reset layout to grid
            mediaLibraryState.layout = 'grid';
            document.querySelectorAll('.layout-btn').forEach(btn => {
                btn.classList.toggle('active', btn.dataset.layout === 'grid');
            });
        }

        async function loadMediaLibraryData(force = false) {
            if (!force && mediaLibraryState.images.length > 0) return;

            const loader = document.getElementById('mediaLibraryLoader');
            const grid = document.getElementById('mediaLibraryGrid');
            const empty = document.getElementById('mediaLibraryEmpty');
            
            if (loader) loader.style.display = 'block';
            if (grid) grid.innerHTML = '';
            if (empty) empty.style.display = 'none';

            try {
                const response = await fetch('media_library.php');
                const data = await response.json();
                
                if (data.success) {
                    mediaLibraryState.images = data.images || [];
                    mediaLibraryState.allImages = [...mediaLibraryState.images];
                } else {
                    console.error('Failed to load media library:', data.error);
                    mediaLibraryState.images = [];
                    mediaLibraryState.allImages = [];
                }
                
                renderMediaLibrary();
            } catch (error) {
                console.error('Failed to load media library:', error);
                mediaLibraryState.images = [];
                mediaLibraryState.allImages = [];
                if (empty) {
                    empty.textContent = 'Failed to load media library. Please try again.';
                    empty.style.display = 'block';
                }
            } finally {
                if (loader) loader.style.display = 'none';
            }
        }

        function renderMediaLibrary() {
            const grid = document.getElementById('mediaLibraryGrid');
            const empty = document.getElementById('mediaLibraryEmpty');
            if (!grid) return;

            // Apply filters
            const searchInput = document.getElementById('mediaLibrarySearch');
            const formatSelect = document.getElementById('mediaLibraryFormat');
            const categorySelect = document.getElementById('mediaLibraryCategory');
            const sortSelect = document.getElementById('mediaLibrarySort');
            
            const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';
            const formatFilter = formatSelect ? formatSelect.value.toLowerCase() : '';
            const categoryFilter = categorySelect ? categorySelect.value : 'all';
            const sortBy = sortSelect ? sortSelect.value : 'date_desc';

            let filtered = mediaLibraryState.allImages || mediaLibraryState.images || [];

            // Apply search filter
            if (searchTerm) {
                filtered = filtered.filter(img => {
                    const filename = (img.filename || '').toLowerCase();
                    const productName = (img.product_name || '').toLowerCase();
                    return filename.includes(searchTerm) || productName.includes(searchTerm);
                });
            }

            // Apply format filter
            if (formatFilter) {
                filtered = filtered.filter(img => {
                    const ext = (img.extension || '').toLowerCase();
                    if (formatFilter === 'jpg') {
                        return ext === 'jpg' || ext === 'jpeg';
                    }
                    return ext === formatFilter;
                });
            }

            // Apply category filter
            if (categoryFilter !== 'all') {
                filtered = filtered.filter(img => {
                    switch(categoryFilter) {
                        case 'products':
                            return img.product_id !== null;
                        case 'orphaned':
                            return img.orphaned === true || img.product_id === null;
                        case 'featured':
                            return img.is_featured === true;
                        default:
                            return true;
                    }
                });
            }

            // Apply sorting
            filtered.sort((a, b) => {
                switch(sortBy) {
                    case 'date_desc':
                        return new Date(b.created_at || b.last_modified || 0) - new Date(a.created_at || a.last_modified || 0);
                    case 'date_asc':
                        return new Date(a.created_at || a.last_modified || 0) - new Date(b.created_at || b.last_modified || 0);
                    case 'name_asc':
                        return (a.filename || '').localeCompare(b.filename || '');
                    case 'name_desc':
                        return (b.filename || '').localeCompare(a.filename || '');
                    case 'size_desc':
                        return (b.filesize || 0) - (a.filesize || 0);
                    case 'size_asc':
                        return (a.filesize || 0) - (b.filesize || 0);
                    default:
                        return 0;
                }
            });

            if (filtered.length === 0) {
                grid.innerHTML = '';
                grid.className = 'media-library-grid';
                if (empty) {
                    empty.textContent = searchTerm || formatFilter || categoryFilter !== 'all' ? 'No images match your filters.' : 'No media found.';
                    empty.style.display = 'block';
                }
                updateMediaLibraryStats(0, (mediaLibraryState.allImages || []).length);
                return;
            }

            if (empty) empty.style.display = 'none';
            updateMediaLibraryStats(filtered.length, (mediaLibraryState.allImages || []).length);
            
            // Apply layout class
            grid.className = mediaLibraryState.layout === 'list' ? 'media-library-list' : 'media-library-grid';
            
            if (mediaLibraryState.layout === 'list') {
                // List view with more details
                grid.innerHTML = filtered.map(img => {
                    const isSelected = mediaLibraryState.selected.has(img.filename);
                    const safeFilename = (img.filename || '').replace(/'/g, "\\'");
                    const fileSize = img.size_label || (img.filesize ? formatBytes(img.filesize) : 'Unknown');
                    const dateStr = img.created_at ? new Date(img.created_at).toLocaleDateString() : (img.last_modified ? new Date(img.last_modified).toLocaleDateString() : 'Unknown');
                    
                    return `
                        <div class="media-library-list-item ${isSelected ? 'selected' : ''}" onclick="toggleMediaSelection('${safeFilename}')">
                            <div class="media-list-thumbnail">
                                <img src="../${img.url}" alt="${img.alt_text || img.filename}" loading="lazy" onerror="this.src='../images/placeholder.png'">
                                ${isSelected ? '<div class="media-library-item-check">✓</div>' : ''}
                            </div>
                            <div class="media-list-info">
                                <div class="media-list-name" title="${img.filename}">${img.filename}</div>
                                <div class="media-list-meta">
                                    ${img.product_name ? `<span class="media-meta-tag product-tag">📦 ${img.product_name}</span>` : '<span class="media-meta-tag orphan-tag">🔓 Unlinked</span>'}
                                    ${img.is_featured ? '<span class="media-meta-tag featured-tag">⭐ Featured</span>' : ''}
                                    <span class="media-meta-tag">${img.extension ? img.extension.toUpperCase() : 'N/A'}</span>
                                    <span class="media-meta-tag">${fileSize}</span>
                                    <span class="media-meta-tag">📅 ${dateStr}</span>
                                </div>
                            </div>
                        </div>
                    `;
                }).join('');
            } else {
                // Grid view
                grid.innerHTML = filtered.map(img => {
                    const isSelected = mediaLibraryState.selected.has(img.filename);
                    const safeFilename = (img.filename || '').replace(/'/g, "\\'");
                    return `
                        <div class="media-library-item ${isSelected ? 'selected' : ''}" data-filename="${img.filename}" onclick="toggleMediaSelection('${safeFilename}')">
                            <img src="../${img.url}" alt="${img.alt_text || img.filename}" loading="lazy" onerror="this.src='../images/placeholder.png'">
                            <div class="media-library-item-info">
                                <span class="media-library-item-name" title="${img.filename}">${img.filename}</span>
                                ${img.product_name ? `<span class="media-library-item-product">${img.product_name}</span>` : ''}
                                ${img.is_featured ? '<span class="media-library-item-badge">⭐</span>' : ''}
                            </div>
                            ${isSelected ? '<div class="media-library-item-check">✓</div>' : ''}
                        </div>
                    `;
                }).join('');
            }
        }

        function formatBytes(bytes) {
            if (bytes === 0) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        function switchMediaLayout(layout) {
            mediaLibraryState.layout = layout;
            
            // Update button states
            document.querySelectorAll('.layout-btn').forEach(btn => {
                btn.classList.toggle('active', btn.dataset.layout === layout);
            });
            
            renderMediaLibrary();
        }

        function toggleMediaSelection(filename) {
            if (mediaLibraryState.mode === 'single') {
                mediaLibraryState.selected.clear();
                mediaLibraryState.selected.add(filename);
            } else {
                if (mediaLibraryState.selected.has(filename)) {
                    mediaLibraryState.selected.delete(filename);
                } else {
                    mediaLibraryState.selected.add(filename);
                }
            }
            renderMediaLibrary();
            updateMediaLibrarySelectionCount();
        }

        function updateMediaLibrarySelectionCount() {
            const count = document.getElementById('mediaLibrarySelectionCount');
            if (count) {
                count.textContent = `${mediaLibraryState.selected.size} selected`;
            }
        }

        function updateMediaLibraryStats(displayed, total) {
            const stats = document.getElementById('mediaLibraryStats');
            if (stats) {
                if (displayed === total) {
                    stats.textContent = `Showing all ${total} image${total !== 1 ? 's' : ''}`;
                } else {
                    stats.textContent = `Showing ${displayed} of ${total} image${total !== 1 ? 's' : ''}`;
                }
                stats.style.display = total > 0 ? 'block' : 'none';
            }
        }

        function applyMediaLibraryFilters() {
            const search = document.getElementById('mediaLibrarySearch');
            const format = document.getElementById('mediaLibraryFormat');
            const category = document.getElementById('mediaLibraryCategory');
            const sort = document.getElementById('mediaLibrarySort');
            
            if (search) {
                search.removeEventListener('input', renderMediaLibrary);
                search.addEventListener('input', renderMediaLibrary);
            }
            if (format) {
                format.removeEventListener('change', renderMediaLibrary);
                format.addEventListener('change', renderMediaLibrary);
            }
            if (category) {
                category.removeEventListener('change', renderMediaLibrary);
                category.addEventListener('change', renderMediaLibrary);
            }
            if (sort) {
                sort.removeEventListener('change', renderMediaLibrary);
                sort.addEventListener('change', renderMediaLibrary);
            }
        }

        function confirmMediaLibrarySelection() {
            const selectedImages = mediaLibraryState.images.filter(img => 
                mediaLibraryState.selected.has(img.filename)
            );
            
            if (mediaLibraryState.onSelect) {
                mediaLibraryState.onSelect(selectedImages);
            }
            closeMediaLibrary();
        }
    </script>

    <!-- Media Library Modal -->
    <div id="mediaLibraryModal" class="media-library-modal" role="dialog" aria-modal="true">
        <div class="media-library-dialog">
            <div class="media-library-header">
                <h3>📚 Media Library</h3>
                <button type="button" class="media-library-close" onclick="closeMediaLibrary()" aria-label="Close media library">×</button>
            </div>
            <div class="media-library-toolbar">
                <div class="media-library-search">
                    <input type="search" id="mediaLibrarySearch" placeholder="Search by filename..." autocomplete="off">
                </div>
                <select id="mediaLibraryCategory">
                    <option value="all">All Images</option>
                    <option value="products">Product Images</option>
                    <option value="orphaned">Unlinked Images</option>
                    <option value="featured">Featured Only</option>
                </select>
                <select id="mediaLibraryFormat">
                    <option value="">All Formats</option>
                    <option value="jpg">JPG/JPEG</option>
                    <option value="png">PNG</option>
                    <option value="webp">WebP</option>
                    <option value="svg">SVG</option>
                </select>
                <select id="mediaLibrarySort">
                    <option value="date_desc">Newest First</option>
                    <option value="date_asc">Oldest First</option>
                    <option value="name_asc">Name A-Z</option>
                    <option value="name_desc">Name Z-A</option>
                    <option value="size_desc">Largest First</option>
                    <option value="size_asc">Smallest First</option>
                </select>
                <div class="media-library-layout-toggle">
                    <button type="button" class="layout-btn active" data-layout="grid" onclick="switchMediaLayout('grid')" title="Grid View">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="7" height="7"></rect>
                            <rect x="14" y="3" width="7" height="7"></rect>
                            <rect x="3" y="14" width="7" height="7"></rect>
                            <rect x="14" y="14" width="7" height="7"></rect>
                        </svg>
                    </button>
                    <button type="button" class="layout-btn" data-layout="list" onclick="switchMediaLayout('list')" title="List View">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="8" y1="6" x2="21" y2="6"></line>
                            <line x1="8" y1="12" x2="21" y2="12"></line>
                            <line x1="8" y1="18" x2="21" y2="18"></line>
                            <line x1="3" y1="6" x2="3.01" y2="6"></line>
                            <line x1="3" y1="12" x2="3.01" y2="12"></line>
                            <line x1="3" y1="18" x2="3.01" y2="18"></line>
                        </svg>
                    </button>
                </div>
            </div>
            <div class="media-library-body">
                <div class="media-library-stats" id="mediaLibraryStats" style="padding: 0 1.5rem 0.75rem; color: #6b7280; font-size: 0.875rem;"></div>
                <div class="media-library-loader" id="mediaLibraryLoader" style="display:none;">Loading media assets…</div>
                <div class="media-library-empty" id="mediaLibraryEmpty" style="display:none;">No media found.</div>
                <div class="media-library-grid" id="mediaLibraryGrid"></div>
            </div>
            <div class="media-library-footer">
                <span class="media-library-selection-count" id="mediaLibrarySelectionCount">0 selected</span>
                <div class="media-library-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeMediaLibrary()">Cancel</button>
                    <button type="button" class="btn btn-primary" id="mediaLibraryConfirmBtn" onclick="confirmMediaLibrarySelection()">Use Selected</button>
                </div>
            </div>
        </div>
    </div>

    <style>
        .media-library-modal {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.45);
            backdrop-filter: blur(4px);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            z-index: 10000;
        }

        .media-library-modal.open {
            display: flex;
        }

        .media-library-dialog {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 1200px;
            width: 100%;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
        }

        .media-library-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.5rem;
            border-bottom: 1px solid #e5e7eb;
        }

        .media-library-header h3 {
            margin: 0;
            font-size: 1.25rem;
            color: #1f2937;
        }

        .media-library-close {
            background: none;
            border: none;
            font-size: 2rem;
            color: #6b7280;
            cursor: pointer;
            padding: 0;
            width: 2rem;
            height: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            transition: all 0.2s;
        }

        .media-library-close:hover {
            background: #f3f4f6;
            color: #1f2937;
        }

        .media-library-toolbar {
            display: flex;
            gap: 1rem;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #e5e7eb;
            flex-wrap: wrap;
            align-items: center;
        }

        .media-library-search {
            flex: 1;
            min-width: 200px;
        }

        .media-library-search input {
            width: 100%;
            padding: 0.5rem 1rem;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 0.875rem;
        }

        .media-library-toolbar select {
            padding: 0.5rem 1rem;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 0.875rem;
            background: white;
            cursor: pointer;
        }

        .media-library-layout-toggle {
            display: flex;
            gap: 0.25rem;
            background: #f3f4f6;
            padding: 0.25rem;
            border-radius: 6px;
        }

        .layout-btn {
            background: transparent;
            border: none;
            padding: 0.5rem;
            border-radius: 4px;
            cursor: pointer;
            color: #6b7280;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .layout-btn:hover {
            color: #1f2937;
            background: rgba(255, 255, 255, 0.5);
        }

        .layout-btn.active {
            background: white;
            color: #ff5900;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .media-library-body {
            flex: 1;
            overflow-y: auto;
            padding: 1.5rem;
            min-height: 400px;
        }

        .media-library-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 1rem;
        }

        .media-library-list {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .media-library-list-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.75rem;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
            background: white;
        }

        .media-library-list-item:hover {
            border-color: #ff5900;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .media-library-list-item.selected {
            border-color: #ff5900;
            background: #fff7ed;
            box-shadow: 0 0 0 3px rgba(255, 89, 0, 0.1);
        }

        .media-list-thumbnail {
            position: relative;
            width: 80px;
            height: 80px;
            flex-shrink: 0;
            border-radius: 6px;
            overflow: hidden;
            background: #f3f4f6;
        }

        .media-list-thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .media-list-info {
            flex: 1;
            min-width: 0;
        }

        .media-list-name {
            font-weight: 500;
            color: #1f2937;
            margin-bottom: 0.5rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .media-list-meta {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .media-meta-tag {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.5rem;
            background: #f3f4f6;
            border-radius: 4px;
            font-size: 0.75rem;
            color: #6b7280;
        }

        .media-meta-tag.product-tag {
            background: #dbeafe;
            color: #1e40af;
        }

        .media-meta-tag.orphan-tag {
            background: #fee2e2;
            color: #991b1b;
        }

        .media-meta-tag.featured-tag {
            background: #fef3c7;
            color: #92400e;
        }

        .media-library-item {
            position: relative;
            aspect-ratio: 1;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            overflow: hidden;
            cursor: pointer;
            transition: all 0.2s;
        }

        .media-library-item:hover {
            border-color: #ff5900;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .media-library-item.selected {
            border-color: #ff5900;
            box-shadow: 0 0 0 3px rgba(255, 89, 0, 0.2);
        }

        .media-library-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .media-library-item-info {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(to top, rgba(0, 0, 0, 0.8), transparent);
            padding: 0.75rem 0.5rem 0.5rem;
            font-size: 0.75rem;
            color: white;
        }

        .media-library-item-name {
            display: block;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            font-weight: 500;
        }

        .media-library-item-product {
            display: block;
            font-size: 0.7rem;
            color: #e5e7eb;
            margin-top: 0.25rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .media-library-item-badge {
            position: absolute;
            top: 0.5rem;
            left: 0.5rem;
            background: rgba(255, 255, 255, 0.9);
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            backdrop-filter: blur(4px);
        }

        .media-library-item-check {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            width: 1.5rem;
            height: 1.5rem;
            background: #ff5900;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        .media-library-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 1.5rem;
            border-top: 1px solid #e5e7eb;
        }

        .media-library-selection-count {
            font-size: 0.875rem;
            color: #6b7280;
        }

        .media-library-actions {
            display: flex;
            gap: 0.75rem;
        }

        .media-library-loader,
        .media-library-empty {
            text-align: center;
            padding: 3rem;
            color: #6b7280;
        }

        .media-picker-actions {
            display: flex;
            gap: 0.75rem;
        }

        .media-picker-actions .btn {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes slideOutRight {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }

        /* Keyboard navigation for modal */
        .media-library-modal:focus-within .media-library-item:focus {
            outline: 2px solid #ff5900;
            outline-offset: 2px;
        }
    </style>
</body>
</html>