# 🔥 Firebase Integration Guide

## Overview

Firebase Authentication integration has been added to the admin settings panel, allowing you to manage Firebase credentials through the web interface instead of manually editing configuration files.

## ✨ Features

- **SMS OTP Authentication** - Passwordless login via phone number verification
- **Phone Number Verification** - Verify customer phone numbers during registration
- **Email Verification** - Send verification codes to customer emails
- **Admin-Managed Configuration** - Update Firebase credentials through admin panel
- **Database-Backed Storage** - Credentials stored securely in database
- **Connection Testing** - Built-in Firebase connection validator
- **Easy Enable/Disable** - Toggle Firebase features without changing code

## 🎯 What Was Created

### 1. **Admin Settings Integration** (`admin/settings.php`)
- New **Integrations** tab added to settings navigation
- Firebase configuration form with all required fields
- Real-time Firebase connection testing
- Toggle to enable/disable Firebase features
- Visual feedback and validation

### 2. **Updated Firebase Config** (`includes/firebase-config.php`)
**OLD (Hardcoded):**
```php
return [
    'apiKey' => 'YOUR_FIREBASE_API_KEY',
    'authDomain' => 'YOUR_PROJECT_ID.firebaseapp.com',
    // ... hardcoded values
];
```

**NEW (Database-Driven):**
```php
function getFirebaseConfig() {
    // Loads from database settings table
    // Automatic fallback if not configured
    // Returns enabled status and credentials
}
```

### 3. **Database Storage**
Firebase settings are stored in the existing `settings` table:

| Setting Key | Description | Example Value |
|-------------|-------------|---------------|
| `firebase_enabled` | Enable/disable Firebase | `1` or `0` |
| `firebase_api_key` | Web API Key | `AIzaSyXXXXXXXXXXXXXXXXX` |
| `firebase_auth_domain` | Authentication domain | `your-project.firebaseapp.com` |
| `firebase_project_id` | Project identifier | `your-project-id` |
| `firebase_storage_bucket` | Storage bucket URL | `your-project.appspot.com` |
| `firebase_messaging_sender_id` | FCM sender ID | `123456789012` |
| `firebase_app_id` | Application ID | `1:123456789012:web:abc123` |

## 📋 Setup Instructions

### Step 1: Access Admin Settings
1. Log in to admin panel
2. Navigate to **Settings** in sidebar
3. Click the **🔌 Integrations** tab

### Step 2: Get Firebase Credentials
1. Go to [Firebase Console](https://console.firebase.google.com/)
2. Create a new project or select existing one
3. Click the gear icon (⚙️) → **Project Settings**
4. Scroll to **Your apps** section
5. Click **Add app** → Select **Web** (`</>` icon)
6. Register your app with a nickname
7. Copy the configuration object values

### Step 3: Configure in Admin Panel
Fill in the Firebase configuration form with values from Firebase Console:

```javascript
// Firebase Console shows this config:
const firebaseConfig = {
  apiKey: "AIzaSyXXXXXXXXXXXXXXXXXXXXXXXX",           // → API Key field
  authDomain: "your-project.firebaseapp.com",        // → Auth Domain field
  projectId: "your-project-id",                      // → Project ID field
  storageBucket: "your-project.appspot.com",         // → Storage Bucket field
  messagingSenderId: "123456789012",                 // → Messaging Sender ID field
  appId: "1:123456789012:web:abc123def456"          // → App ID field
};
```

### Step 4: Enable Phone Authentication
1. In Firebase Console, go to **Authentication** → **Sign-in method**
2. Click **Phone** and enable it
3. Click **Save**

### Step 5: Add Authorized Domains
1. In Firebase Console → **Authentication** → **Settings**
2. Go to **Authorized domains** section
3. Add your website domain (e.g., `jinkaplotters.com`, `www.jinkaplotters.com`)
4. Add `localhost` for local testing

### Step 6: Test Connection
1. In admin panel, click **🧪 Test Firebase Connection** button
2. Verify success message appears
3. Toggle **Enable Firebase** switch ON
4. Click **💾 Save Integration Settings**

## 🧪 Testing Firebase Features

### Test SMS OTP Login
1. Navigate to `customer-login-otp.php`
2. Enter a valid phone number
3. Complete reCAPTCHA verification
4. Receive and enter OTP code
5. Verify successful login

### Test Phone Verification
1. Navigate to `customer-register-verified.php`
2. Fill registration form with phone number
3. Choose phone verification method
4. Receive and enter OTP code
5. Verify phone verification success

### Test Email Verification
1. Use same registration form
2. Choose email verification method
3. Check email for verification code
4. Enter code and verify success

## 🔧 How It Works

### Configuration Loading
```php
// Pages that use Firebase automatically load config from database
$firebaseConfig = require_once 'includes/firebase-config.php';

// Check if Firebase is enabled
if ($firebaseConfig['enabled']) {
    // Initialize Firebase SDK with database credentials
    echo "<script src='https://www.gstatic.com/firebasejs/9.22.0/firebase-app-compat.js'></script>";
    echo "<script src='https://www.gstatic.com/firebasejs/9.22.0/firebase-auth-compat.js'></script>";
    echo "<script>
        const firebaseConfig = " . json_encode($firebaseConfig) . ";
        firebase.initializeApp(firebaseConfig);
    </script>";
} else {
    // Show configuration required message
    echo "<!-- Firebase not configured -->";
}
```

### Admin Panel Updates
When you save Firebase settings in admin panel:

1. **Form Submission** → `admin/settings.php` receives POST data
2. **Validation** → Fields are sanitized and validated
3. **Database Storage** → Settings saved to `settings` table using INSERT ... ON DUPLICATE KEY UPDATE
4. **Success Message** → Confirmation displayed to admin
5. **Immediate Effect** → All pages using Firebase now use new credentials

### Connection Testing
The test button:
1. Loads Firebase SDK dynamically
2. Initializes temporary Firebase app with provided credentials
3. Tests authentication initialization
4. Reports success or specific error messages
5. Cleans up test app instance

## 🔒 Security Features

### Credential Protection
- Firebase API keys stored in database (not exposed in client-side code as config is loaded server-side)
- Admin authentication required to view/edit settings
- Role-based access control (admin/super_admin only)

### Database Security
```sql
-- Settings table uses ON DUPLICATE KEY UPDATE for safe upserts
INSERT INTO settings (setting_key, setting_value, updated_at) 
VALUES (?, ?, NOW()) 
ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()
```

### Error Handling
- Graceful fallback if database connection fails
- Detailed error messages in admin panel
- Error logging to PHP error log
- No Firebase errors exposed to end users

## 📊 Database Schema

### Settings Table Structure
```sql
CREATE TABLE `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL UNIQUE,
  `setting_value` longtext,
  `setting_type` enum('string','number','boolean','json','text') DEFAULT 'string',
  `description` varchar(255) NULL,
  `group_name` varchar(50) DEFAULT 'general',
  `is_public` tinyint(1) DEFAULT 0,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`),
  KEY `idx_group_name` (`group_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Firebase Settings Query
```sql
-- Retrieve all Firebase settings
SELECT setting_key, setting_value 
FROM settings 
WHERE setting_key LIKE 'firebase_%'
ORDER BY setting_key;
```

### Example Data
```sql
INSERT INTO settings (setting_key, setting_value) VALUES
('firebase_enabled', '1'),
('firebase_api_key', 'AIzaSyXXXXXXXXXXXXXXXXXXXXXXXX'),
('firebase_auth_domain', 'jinkaplotters.firebaseapp.com'),
('firebase_project_id', 'jinkaplotters'),
('firebase_storage_bucket', 'jinkaplotters.appspot.com'),
('firebase_messaging_sender_id', '123456789012'),
('firebase_app_id', '1:123456789012:web:abc123def456');
```

## 🚨 Troubleshooting

### Error: "Firebase is not configured"
**Solution:** 
1. Go to Admin Panel → Settings → Integrations
2. Fill in all required Firebase fields (marked with *)
3. Toggle **Enable Firebase** switch ON
4. Click **Save Integration Settings**

### Error: "Invalid API Key"
**Solution:**
1. Verify API key in Firebase Console → Project Settings
2. Ensure you copied the complete key (starts with `AIza`)
3. No extra spaces or quotes
4. Re-save in admin panel

### Error: "Auth domain mismatch"
**Solution:**
1. Check Auth Domain format: `your-project.firebaseapp.com`
2. Must match your Firebase project ID
3. No `https://` prefix
4. Add domain to Firebase authorized domains

### Error: "Phone authentication not enabled"
**Solution:**
1. Firebase Console → Authentication → Sign-in method
2. Enable **Phone** provider
3. Save changes
4. Wait 1-2 minutes for propagation

### SMS Not Sending
**Solution:**
1. Verify phone number format (include country code: +254...)
2. Check Firebase Console → Authentication → Phone numbers
3. For testing, add test phone numbers in Firebase Console
4. Verify reCAPTCHA is working (check browser console)
5. Check Firebase quota limits (free tier: 10 SMS/day)

### Connection Test Fails
**Solution:**
1. Open browser developer console (F12)
2. Check for JavaScript errors
3. Verify all required fields filled
4. Try in incognito/private window
5. Clear browser cache and retry

## 📱 Features Using Firebase

### 1. SMS OTP Login (`customer-login-otp.php`)
- Passwordless authentication
- reCAPTCHA protection
- Auto-resend OTP
- Session creation on success

### 2. Customer Registration with Verification (`customer-register-verified.php`)
- Dual verification (Email OR Phone)
- 6-digit OTP codes
- Auto-focus inputs
- Visual step indicators

### 3. Phone Verification System
- Backend: `includes/verify-phone.php`
- OTP generation and storage
- SMS sending via Firebase
- Expiration handling (10 minutes)

### 4. Email Verification System
- Backend: `includes/verify-email.php`
- Token generation and storage
- Email sending with templates
- Expiration handling (24 hours)

## 🎨 Admin UI Features

### Settings Page Updates
- **New Tab:** 🔌 Integrations
- **Toggle Switch:** Enable/Disable Firebase with visual feedback
- **Form Validation:** Required fields marked with red asterisk (*)
- **Inline Help:** Field descriptions and format hints
- **Info Banners:** 
  - Setup instructions (yellow banner)
  - Features enabled list (blue banner)
- **Action Buttons:**
  - 💾 Save Integration Settings
  - 🧪 Test Firebase Connection
  - 🔗 Open Firebase Console

### Visual Design
- Modern glassmorphism toggle switches
- Color-coded status indicators
- Responsive layout (mobile-friendly)
- Consistent with existing admin theme
- Accessible keyboard navigation

## 🔄 Migration from Hardcoded Config

### Before (Manual File Editing)
```php
// includes/firebase-config.php
return [
    'apiKey' => 'AIzaSyXXXXXXXXXXXXXXXXXXXXXXXX',
    'authDomain' => 'jinkaplotters.firebaseapp.com',
    // ... edit file directly
];
```

**Problems:**
- Requires file system access
- Version control conflicts
- No validation
- Hard to manage multiple environments
- Can't test without deploying

### After (Admin Panel)
```
Admin Panel → Settings → Integrations → Firebase
```

**Benefits:**
- ✅ Web-based configuration
- ✅ No file editing required
- ✅ Built-in validation and testing
- ✅ Database-backed (survives deployments)
- ✅ Role-based access control
- ✅ Audit trail (updated_at timestamp)
- ✅ Easy environment switching

### Migration Steps
1. Open `includes/firebase-config.php` in editor
2. Copy hardcoded values
3. Navigate to Admin Panel → Settings → Integrations
4. Paste values into corresponding fields
5. Click **Test Firebase Connection** to verify
6. Click **Save Integration Settings**
7. Old file is now ignored (database takes precedence)

## 🌐 Environment-Specific Configuration

### Development Environment
```
Admin Panel (Dev):
- Firebase Project: myproject-dev
- Auth Domain: myproject-dev.firebaseapp.com
- Test phone numbers configured
```

### Production Environment
```
Admin Panel (Prod):
- Firebase Project: myproject-prod
- Auth Domain: myproject-prod.firebaseapp.com
- Real phone authentication
```

**No code changes needed!** Just update values in admin panel for each environment.

## 📚 Related Documentation

- [SETUP-VERIFICATION.md](./SETUP-VERIFICATION.md) - Firebase setup verification steps
- [IMPLEMENTATION-SUMMARY.md](./IMPLEMENTATION-SUMMARY.md) - Technical implementation details
- [QUICK-REFERENCE.md](./QUICK-REFERENCE.md) - Command reference guide
- [README-IMPLEMENTATION.md](./README-IMPLEMENTATION.md) - Overall system documentation

## 🆘 Support

### Firebase Issues
- [Firebase Documentation](https://firebase.google.com/docs/auth)
- [Firebase Console](https://console.firebase.google.com/)
- [Firebase Status](https://status.firebase.google.com/)

### Project Issues
- Check browser console for JavaScript errors
- Review PHP error logs for backend issues
- Test with Firebase debug mode enabled
- Contact system administrator

## ✅ Best Practices

### Security
1. ✅ Keep Firebase credentials confidential
2. ✅ Use different projects for dev/staging/production
3. ✅ Restrict Firebase rules appropriately
4. ✅ Enable billing alerts in Firebase Console
5. ✅ Regular security audits

### Configuration
1. ✅ Test connection after any credential change
2. ✅ Document which Firebase project is used
3. ✅ Enable only required authentication methods
4. ✅ Set appropriate quota limits
5. ✅ Monitor Firebase usage dashboard

### Maintenance
1. ✅ Review Firebase usage monthly
2. ✅ Update Firebase SDK versions regularly
3. ✅ Test SMS/Email delivery periodically
4. ✅ Keep authorized domains list updated
5. ✅ Backup database settings regularly

---

**Created:** December 2024  
**Last Updated:** December 2024  
**Version:** 1.0.0
