# 🚀 Quick Start: Firebase Admin Integration

## What's New?

Firebase credentials can now be managed through the admin panel instead of editing files!

## 📍 Where to Find It

```
Admin Panel → Settings (sidebar) → Integrations (tab) → Firebase Authentication
```

## ⚡ 5-Minute Setup

### Step 1: Get Firebase Credentials (2 min)
1. Go to https://console.firebase.google.com/
2. Select your project (or create new one)
3. Click gear icon ⚙️ → **Project Settings**
4. Scroll to **Your apps** → Click **Add app** → Select **Web** (`</>`)
5. Copy the config values

### Step 2: Configure in Admin Panel (2 min)
1. Log in to admin panel
2. Click **Settings** in sidebar
3. Click **🔌 Integrations** tab
4. Fill in Firebase form:
   - **API Key** (required)
   - **Auth Domain** (required)
   - **Project ID** (required)
   - **Storage Bucket** (optional)
   - **Messaging Sender ID** (optional)
   - **App ID** (required)

### Step 3: Test & Save (1 min)
1. Click **🧪 Test Firebase Connection**
2. See ✅ success message
3. Toggle **Enable Firebase** switch ON
4. Click **💾 Save Integration Settings**

## ✅ Verification

### Check Configuration Worked
1. Navigate to `customer-login-otp.php`
2. Page should load without errors
3. Enter phone number → Should show reCAPTCHA
4. SMS should be sent (if phone auth enabled in Firebase)

### Common Issues

**❌ "Firebase is not configured"**
→ Fill form in Admin Panel → Enable toggle → Save

**❌ "Invalid API Key"**
→ Verify key in Firebase Console → Copy exact value → No spaces

**❌ "Connection test failed"**
→ Check all required fields (*) are filled → Open browser console for errors

## 🎯 What Changed

### Before (Manual)
```
1. SSH to server
2. Edit includes/firebase-config.php
3. Copy credentials
4. Commit and push
5. Deploy
6. Hope it works 😰
```

### After (Admin Panel)
```
1. Open browser
2. Admin → Settings → Integrations
3. Fill form and test
4. Click save
5. Done! 🎉
```

## 📖 Documentation

- **Full Guide:** [FIREBASE-INTEGRATION-GUIDE.md](./FIREBASE-INTEGRATION-GUIDE.md)
- **Quick Summary:** [FIREBASE-ADMIN-SUMMARY.md](./FIREBASE-ADMIN-SUMMARY.md)
- **Visual Guide:** [FIREBASE-VISUAL-GUIDE.md](./FIREBASE-VISUAL-GUIDE.md)
- **Implementation:** [FIREBASE-ADMIN-IMPLEMENTATION-SUMMARY.md](./FIREBASE-ADMIN-IMPLEMENTATION-SUMMARY.md)

## 🔥 Features Enabled

When Firebase is configured:
- ✅ SMS OTP login (passwordless)
- ✅ Phone number verification
- ✅ Email verification codes
- ✅ Secure authentication

## 🆘 Need Help?

1. Check [FIREBASE-INTEGRATION-GUIDE.md](./FIREBASE-INTEGRATION-GUIDE.md) Troubleshooting section
2. Verify Firebase Console configuration
3. Test with different browser
4. Contact system administrator

---

**Status:** ✅ Ready to Use  
**Version:** 1.0.0  
**Setup Time:** ~5 minutes
