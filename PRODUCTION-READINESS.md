
# Production Readiness Checklist

This document tracks the security and production readiness enhancements made to the JINKA Plotter Website.

## ✅ Completed Enhancements

### 1. Environment-Driven Configuration
- [x] Added `.env` loader to keep secrets out of code
- [x] All sensitive configuration now comes from environment variables
- [x] Production-safe defaults (ENVIRONMENT defaults to 'production')
- [x] Separated development and production configurations
- [x] Created `.env.example` as template
- [x] Protected `.env` file in `.gitignore`

### 2. Database Security
- [x] Database credentials moved to environment variables
- [x] Default DB user changed from 'root' to 'jinka_app' (least privilege)
- [x] Enhanced connection error handling with dev/prod modes
- [x] Created database connection checker (`check-db.php`)
- [x] Connection errors logged (not exposed in production)
- [x] Graceful error pages for connection failures

### 3. Session & Cookie Security
- [x] Configurable session name (SESSION_NAME)
- [x] Secure cookie flags (HttpOnly, Secure, SameSite=Lax)
- [x] Session timeout enforcement
- [x] Strict session mode enabled
- [x] Production forces secure cookies (HTTPS only)

### 4. Secret Management
- [x] SECRET_KEY moved to environment (no default in code)
- [x] ENCRYPTION_KEY moved to environment (min 32 chars)
- [x] All payment gateway keys moved to environment
  - AzamPay (client ID, secret, API key)
  - M-Pesa (consumer key, secret, shortcode, passkey)
  - PayPal (client ID, secret, webhook ID)
  - Stripe (publishable key, secret key, webhook secret)
  - Pesapal (consumer key, secret, IPN ID)
  - Flutterwave (public key, secret key, encryption key)
- [x] SMTP credentials moved to environment

### 5. File & Path Security
- [x] Upload path configurable via environment
- [x] Cache path configurable (defaults outside document root)
- [x] Log path configurable (defaults outside document root)
- [x] Log directory auto-created with restricted permissions
- [x] PHP error log properly configured for production

### 6. Production Configuration
- [x] DEBUG_MODE defaults to false in production
- [x] Error display disabled in production
- [x] Error logging enabled to file
- [x] SITE_URL requires explicit configuration (no localhost default)
- [x] PAYMENT_USE_SANDBOX auto-disables in production

## 🔨 Implementation Steps Completed

1. **Environment Loader** (`includes/config.php`)
   - Simple .env parser (no external dependencies)
   - Helper functions: `env_get()`, `env_bool()`
   - Loads from project root `.env` file

2. **Enhanced Database Class** (`includes/config.php`)
   - Suppresses raw connection warnings
   - Detailed error messages in development
   - Generic error page in production
   - Error logging for debugging
   - Visual error page with troubleshooting steps

3. **Database Check Tool** (`check-db.php`)
   - Tests MySQL server connection
   - Verifies database existence
   - Lists tables if present
   - Provides setup instructions
   - Shows current configuration
   - Links to phpMyAdmin for easy fixes

## 🚀 Deployment Requirements

### Before Going to Production

1. **Create `.env` file from `.env.example`**
   ```bash
   cp .env.example .env
   ```

2. **Update Critical Settings in `.env`**
   - Set `ENVIRONMENT=production`
   - Set `DEBUG_MODE=false`
   - Change `SECRET_KEY` to random 64-char string
   - Change `ENCRYPTION_KEY` to random 32+ char string
   - Update `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`
   - Set `SITE_URL` to actual domain (with HTTPS)
   - Configure SMTP credentials
   - Add production payment gateway keys
   - Set `PAYMENT_USE_SANDBOX=false`

3. **Generate Secure Keys**
   ```bash
   # On Linux/Mac:
   openssl rand -base64 48    # For SECRET_KEY
   openssl rand -base64 32    # For ENCRYPTION_KEY
   
   # On Windows PowerShell:
   [Convert]::ToBase64String((1..48 | ForEach-Object { Get-Random -Minimum 0 -Maximum 256 }))
   ```

4. **Database Setup**
   - Create database: `CREATE DATABASE jinka_plotter CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;`
   - Create DB user with limited privileges (not root)
   - Import schema: `mysql -u user -p jinka_plotter < database/schema.sql`
   - Run migrations in `database/` folder as needed

5. **File Permissions**
   ```bash
   # Protect .env file
   chmod 600 .env
   
   # Writable directories
   chmod 755 uploads/ cache/ logs/
   
   # Prevent web access to sensitive folders
   # (handled by .htaccess or nginx config)
   ```

6. **Web Server Configuration**
   - Enable HTTPS with valid SSL certificate
   - Redirect HTTP to HTTPS
   - Add HSTS header
   - Deny access to: `/includes`, `/logs`, `/cache`, `/database`, `.env`
   - Configure upload directory restrictions
   - Enable gzip compression
   - Set security headers (CSP, X-Frame-Options, etc.)

7. **Verification Steps**
   - Run `check-db.php` to verify database connection
   - Test user registration/login
   - Test product browsing
   - Test checkout flow
   - Test payment gateways (use test cards first)
   - Verify email sending works
   - Check error logs for issues

## 🔒 Security Best Practices Applied

- **Secrets Management**: All sensitive data in environment variables
- **Least Privilege**: Database user should have minimum required permissions
- **Defense in Depth**: Multiple layers of security (code, config, server)
- **Secure Defaults**: Production-safe values out of the box
- **Error Handling**: Different messages for dev vs production
- **Session Security**: Industry-standard cookie flags
- **Input Validation**: Prepared statements throughout (already present)
- **CSRF Protection**: Token system already implemented
- **Password Security**: Strong hashing (already present)

## 📝 Additional Recommendations

### High Priority (Before Production)
- [ ] Rotate all payment gateway keys (AzamPay keys were committed to git)
- [ ] Set up automated database backups
- [ ] Configure fail2ban or similar for brute force protection
- [ ] Set up monitoring/alerting for errors
- [ ] Test all payment flows end-to-end
- [ ] Verify email delivery (SMTP working)

### Medium Priority
- [ ] Add rate limiting for API endpoints
- [ ] Implement CSP (Content Security Policy) headers
- [ ] Add virus scanning for file uploads
- [ ] Set up CDN for static assets
- [ ] Configure WAF (Web Application Firewall)
- [ ] Add health check endpoint for monitoring

### Low Priority (Nice to Have)
- [ ] Add Redis/Memcached for session storage
- [ ] Implement queue system for email sending
- [ ] Add two-factor authentication for admin
- [ ] Set up log aggregation (ELK stack, etc.)
- [ ] Implement automated security scanning

## 🐛 Known Issues Addressed

1. **Database connection error** - Fixed with:
   - Proper .env file for WAMP defaults
   - Enhanced error handling
   - Database checker tool

2. **Exposed secrets in git** - Fixed with:
   - Environment-driven configuration
   - All keys moved to .env
   - .env properly gitignored

3. **Hardcoded credentials** - Fixed with:
   - DB credentials from environment
   - Payment keys from environment
   - SMTP credentials from environment

## 📞 Support

For issues or questions:
1. Check `check-db.php` for database problems
2. Review logs in `logs/` directory
3. Verify `.env` configuration
4. Check server error logs

---
**Last Updated**: December 11, 2025
**Status**: Ready for production deployment after completing requirements above
