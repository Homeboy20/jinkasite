# 🚀 Quick Reference Card

## 📍 New Pages Created

| File | Purpose | URL |
|------|---------|-----|
| `customer-register-verified.php` | Registration with Email/SMS verification | `/customer-register-verified.php` |
| `customer-login-otp.php` | SMS OTP login | `/customer-login-otp.php` |
| `setup-verification-check.php` | Setup status checker | `/setup-verification-check.php` |

## 🔑 Firebase Setup (3 Steps)

```bash
1. Go to: https://console.firebase.google.com/
2. Enable: Authentication → Phone
3. Copy: Project Settings → Config → Web
```

**Update:** `includes/firebase-config.php`

## 💾 Database Setup (1 Command)

```sql
-- Run in phpMyAdmin or MySQL
source database/add-phone-verification.sql
```

## 🎨 Design Enhancements

### Header Top Bar
```css
Background: 3-level gradient (#0f172a → #1e293b → #334155)
Border: 1px solid rgba(59, 130, 246, 0.2)
Shadow: 0 2px 8px rgba(0,0,0,0.15)
```

### Currency Switcher
```css
Background: rgba(255, 255, 255, 0.12)
Backdrop: blur(12px) saturate(180%)
Border-radius: 12px
Hover: translateY(-2px) with glow
```

### Buttons
```css
Border-radius: 12px (forms), 50px (header)
Padding: 1.125rem 2rem
Hover: translateY(-3px) + shadow
Gradient: #667eea → #764ba2
```

## 🔐 Phone Format

**Required:** `+[country][number]`

**Examples:**
- 🇰🇪 Kenya: `+254712345678`
- 🇺🇬 Uganda: `+256701234567`
- 🇹🇿 Tanzania: `+255712345678`
- 🇳🇬 Nigeria: `+2348012345678`

## 🧪 Test Phone Numbers

**Firebase Console Setup:**
```
Authentication → Sign-in method → Phone → Test numbers

+254700000001 → 123456
+254700000002 → 654321
+254700000003 → 111111
```

## 📊 User Flow Diagram

### Registration:
```
Fill Form → Account Created (Inactive)
    ↓
Choose Verification: [Email] or [SMS]
    ↓
Enter 6-Digit Code
    ↓
Account Activated → Auto-Login → Dashboard
```

### OTP Login:
```
Enter Phone Number
    ↓
Firebase Sends SMS (6-digit code)
    ↓
Enter Code → Verify
    ↓
Logged In → Dashboard
```

## 🎯 Color Palette

```css
/* Primary Gradients */
--gradient-primary: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
--gradient-success: linear-gradient(135deg, #10b981 0%, #059669 100%);
--gradient-header: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);

/* Solid Colors */
--primary: #667eea;
--success: #10b981;
--error: #ef4444;
--dark: #1e293b;
--gray: #64748b;
--light-gray: #94a3b8;
```

## ⚡ Animation Speeds

```css
Fast: 0.2s
Normal: 0.3s
Slow: 0.5s

Easing: cubic-bezier(0.4, 0, 0.2, 1)
```

## 📱 Responsive Breakpoints

```css
Mobile: max-width: 640px
Tablet: max-width: 768px
Desktop: min-width: 1024px
```

## 🔍 Troubleshooting

### SMS Not Sending?
```bash
1. Check Firebase authorized domains
2. Verify phone format (+254...)
3. Check SMS quota in Firebase
4. Use test numbers for development
```

### Database Error?
```sql
-- Check if column exists
DESCRIBE customers;

-- Should show: phone_verified
```

### Styling Not Applied?
```bash
1. Clear browser cache (Ctrl + F5)
2. Check file: css/header-modern.css
3. Verify linked in <head>
```

## 📁 Files Modified

### New Files (5):
- ✅ `customer-register-verified.php`
- ✅ `customer-login-otp.php`
- ✅ `database/add-phone-verification.sql`
- ✅ `setup-verification-check.php`
- ✅ Documentation files (3)

### Modified Files (4):
- 🔧 `css/header-modern.css` (enhanced)
- 🔧 `css/style.css` (conflicts removed)
- 🔧 `customer-login.php` (OTP link added)
- 🔧 `customer-register.php` (verified link added)

## 🎯 Quick Commands

### Check Setup Status:
```
http://localhost/jinkaplotterwebsite/setup-verification-check.php
```

### Test New Features:
```
Registration: /customer-register-verified.php
OTP Login: /customer-login-otp.php
Standard Login: /customer-login.php
```

## 📚 Documentation

1. **Quick Start:** This file (QUICK-REFERENCE.md)
2. **Complete Guide:** SETUP-VERIFICATION.md
3. **Summary:** IMPLEMENTATION-SUMMARY.md
4. **Automated Check:** setup-verification-check.php

## ✅ Production Checklist

- [ ] Add Firebase credentials
- [ ] Run database migration
- [ ] Test with real phone numbers
- [ ] Configure authorized domains
- [ ] Set up email service
- [ ] Add rate limiting
- [ ] Security audit
- [ ] Enable HTTPS

## 🎊 That's It!

**2 Steps to Get Started:**
1. Update `includes/firebase-config.php`
2. Run `database/add-phone-verification.sql`

**Then test:**
- Visit `setup-verification-check.php`
- Try registering with phone number
- Test OTP login

---

**Need Help?** Check `SETUP-VERIFICATION.md` for detailed instructions.

**Status:** 🟢 Ready for Setup  
**Version:** 1.0.0  
**Date:** November 22, 2025
