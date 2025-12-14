# Firebase Authentication Setup Instructions

## Overview
Your website now has a modern redesigned header and Firebase authentication with email/phone verification and OTP login.

## Step 1: Create Firebase Project

1. Go to [Firebase Console](https://console.firebase.google.com/)
2. Click "Add Project" or select an existing project
3. Enter your project name (e.g., "Jinka Plotter Website")
4. Enable Google Analytics (optional)
5. Click "Create Project"

## Step 2: Enable Authentication Methods

1. In Firebase Console, go to **Build** > **Authentication**
2. Click "Get Started"
3. Go to **Sign-in method** tab
4. Enable the following providers:
   - **Email/Password**: Click, toggle "Enable", click "Save"
   - **Phone**: Click, toggle "Enable", click "Save"

### Phone Authentication Setup
1. After enabling Phone auth, you'll see a warning about test phone numbers
2. Add test phone numbers for development (optional):
   - Click "Add test phone number"
   - Enter: `+254712345678` with OTP: `123456`
   - Click "Add"

## Step 3: Configure Your Domain

1. In Firebase Console, go to **Authentication** > **Settings** tab
2. Scroll to "Authorized domains"
3. Add your website domain (e.g., `yourwebsite.com`)
4. For local development, `localhost` is already added

## Step 4: Get Firebase Configuration

1. In Firebase Console, go to **Project Settings** (gear icon)
2. Scroll down to "Your apps" section
3. Click the web icon `</>` to add a web app
4. Enter app nickname (e.g., "Jinka Plotter Web")
5. Click "Register app"
6. Copy the `firebaseConfig` object

## Step 5: Update Firebase Configuration File

Edit `includes/firebase-config.php` and replace with your config:

```php
<?php
return [
    'apiKey' => 'YOUR_API_KEY_HERE',
    'authDomain' => 'YOUR_PROJECT_ID.firebaseapp.com',
    'projectId' => 'YOUR_PROJECT_ID',
    'storageBucket' => 'YOUR_PROJECT_ID.appspot.com',
    'messagingSenderId' => 'YOUR_MESSAGING_SENDER_ID',
    'appId' => 'YOUR_APP_ID'
];
```

## Step 6: Update Header References

Add the new header CSS to all pages that include the header. At the top of each page's `<head>` section, add:

```html
<link rel="stylesheet" href="css/header-modern.css">
```

Or update `includes/header.php` to include it automatically.

## Step 7: Update Navigation Links

Update these files to use the new authentication pages:
- Change `customer-login.php` links to `customer-login-new.php`
- Change `customer-register.php` links to `customer-register-new.php`

Or rename the files:
```bash
mv customer-login.php customer-login-old.php
mv customer-register.php customer-register-old.php
mv customer-login-new.php customer-login.php
mv customer-register-new.php customer-register.php
```

## Step 8: Database Schema Updates

Run this SQL to add Firebase fields to customers table:

```sql
ALTER TABLE customers 
ADD COLUMN firebase_uid VARCHAR(255) NULL,
ADD COLUMN phone_verified TINYINT(1) DEFAULT 0,
ADD INDEX idx_firebase_uid (firebase_uid);
```

## Features Implemented

### 1. Modern Redesigned Header
- **Top Bar**: Contact information and compact currency switcher
- **Main Header**: Logo, search bar, account menu, cart, WhatsApp button
- **Navigation Bar**: Clean navigation with categories
- **Mobile Menu**: Slide-in overlay menu for mobile devices
- **Responsive**: Optimized for all screen sizes

### 2. Email Registration with Verification
- Create account with email and password
- Firebase sends verification email automatically
- Account activated after email verification
- Verification status tracked in database

### 3. Phone Registration with OTP
- Register using phone number
- Firebase sends 6-digit OTP via SMS
- Real-time OTP input with auto-focus
- Account created after OTP verification
- Phone verification status saved

### 4. Email/Password Login
- Traditional login with email and password
- "Remember me" functionality (30-day cookie)
- Account lockout after 5 failed attempts
- Password reset link

### 5. OTP Login
- Login using phone number only
- Firebase sends OTP to phone
- No password required
- Quick and secure authentication

### 6. Security Features
- Firebase authentication tokens
- PHP session management
- Password hashing (bcrypt)
- Brute force protection
- Activity logging
- CSRF protection ready

## Testing

### Test Email Registration
1. Go to `/customer-register-new.php`
2. Click "Email Registration" tab
3. Fill in the form
4. Click "Create Account"
5. Check email for verification link
6. Click link to verify and login

### Test Phone Registration
1. Go to `/customer-register-new.php`
2. Click "Phone Registration" tab
3. Enter phone number with country code (e.g., +254712345678)
4. Click "Send OTP"
5. Enter the 6-digit OTP received
6. Click "Verify & Create Account"

### Test OTP Login
1. Go to `/customer-login-new.php`
2. Click "OTP Login" tab
3. Enter registered phone number
4. Click "Send OTP"
5. Enter OTP received
6. Click "Verify & Login"

## Troubleshooting

### Firebase Errors

**"Missing or insufficient permissions"**
- Check that Authentication is enabled in Firebase Console
- Verify your domain is in the authorized domains list

**"reCAPTCHA verification failed"**
- Phone authentication requires reCAPTCHA
- Make sure your domain is properly configured
- For localhost, use test phone numbers

**"SMS quota exceeded"**
- Firebase has daily SMS limits on free plan
- Upgrade to Blaze plan for higher limits
- Use test phone numbers for development

### Phone Number Format
- Always include country code (e.g., +254 for Kenya)
- Format: +[country code][number]
- Example: +254712345678

### Email Not Receiving
- Check spam/junk folder
- Verify email provider allows Firebase emails
- Check Firebase Console > Authentication > Templates for email settings

## Production Checklist

- [ ] Update Firebase configuration with production keys
- [ ] Add production domain to Firebase authorized domains
- [ ] Test all authentication flows on production
- [ ] Set up Firebase Analytics (optional)
- [ ] Configure email templates in Firebase Console
- [ ] Set up SMS provider (if using phone auth heavily)
- [ ] Enable reCAPTCHA v3 for better security
- [ ] Review Firebase security rules
- [ ] Set up monitoring and alerts
- [ ] Backup customer database regularly

## Support

For Firebase documentation:
- [Firebase Authentication Docs](https://firebase.google.com/docs/auth)
- [Phone Authentication](https://firebase.google.com/docs/auth/web/phone-auth)
- [Email Verification](https://firebase.google.com/docs/auth/web/manage-users#send_a_user_a_verification_email)

## File Structure

```
includes/
  ├── firebase-config.php        # Firebase configuration
  ├── header.php                 # Redesigned header component
  └── CustomerAuth.php           # Enhanced authentication class

js/
  └── firebase-auth.js           # Firebase authentication handlers

css/
  ├── style.css                  # Existing styles
  └── header-modern.css          # New modern header styles

customer-register-new.php        # New registration page with Firebase
customer-login-new.php           # New login page with OTP support
```

## Next Steps

1. Set up Firebase project and get configuration
2. Update `includes/firebase-config.php` with your keys
3. Test authentication flows
4. Update database schema
5. Update navigation links
6. Deploy to production
