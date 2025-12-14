# Firebase Domain Authorization Setup

## Error: auth/captcha-check-failed

This error means Firebase needs to whitelist your domain for phone authentication.

## Quick Fix Steps:

### 1. Go to Firebase Console
Visit: https://console.firebase.google.com/

### 2. Select Your Project
Click on your project (the one matching your API key)

### 3. Navigate to Authentication Settings
- Click **Authentication** in the left sidebar
- Click **Settings** tab at the top
- Click **Authorized domains** section

### 4. Add Your Domains

Add these domains (click **Add domain** button):

**For Local Development:**
```
localhost
127.0.0.1
```

**For Production (replace with your actual domain):**
```
jinkaplotterwebsite.com
www.jinkaplotterwebsite.com
```

**If using WAMP on LAN:**
```
192.168.x.x  (your local IP)
```

### 5. Enable Phone Sign-In Method

Still in **Authentication** section:
- Click **Sign-in method** tab
- Find **Phone** in the list
- Click on it and **Enable** it
- Click **Save**

### 6. Test Domains

You can also add test phone numbers for development:
- In Authentication → Sign-in method → Phone
- Scroll to **Phone numbers for testing**
- Add test numbers with OTP codes (e.g., +254700000000 with code 123456)

### 7. Verify Settings

Check that:
- ✅ Phone authentication is **Enabled**
- ✅ Your domain (localhost) is in **Authorized domains**
- ✅ reCAPTCHA is configured (automatic with phone auth)

## Alternative: Use Test Mode

For development, you can use test phone numbers without real SMS:

1. Authentication → Sign-in method → Phone
2. Add test phone number: `+254700000000`
3. Add test code: `123456`
4. Use these in your login form for testing

## Production Checklist

Before going live:
- [ ] Add production domain to authorized domains
- [ ] Remove test phone numbers
- [ ] Enable real SMS sending
- [ ] Consider SMS quota limits
- [ ] Set up billing for SMS (if needed)

## Common Issues

**Issue:** Still getting captcha-check-failed
**Fix:** Clear browser cache and cookies, or test in incognito mode

**Issue:** Domain not saving
**Fix:** Make sure you're using the correct Firebase project

**Issue:** SMS not sending
**Fix:** Check Firebase console for SMS quota and billing status

## Need Help?

Firebase Auth Documentation: https://firebase.google.com/docs/auth/web/phone-auth
