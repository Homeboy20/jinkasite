# 🚀 Quick Setup Guide - WAMP Development

This guide will help you fix the "Database connection failed" error and get your site running.

## ✅ Step 1: Check WAMP Status

1. Look for the **WAMP icon** in your system tray (bottom-right corner of Windows)
2. The icon should be **GREEN** (both Apache and MySQL running)
3. If it's **ORANGE** or **RED**, click it and select:
   - "Start All Services" or
   - Click "MySQL" → "Service" → "Start/Resume Service"

## ✅ Step 2: Create the Database

### Option A: Using phpMyAdmin (Easiest)

1. Open phpMyAdmin: http://localhost/phpmyadmin
2. Click "**SQL**" tab at the top
3. Copy and paste this command:
   ```sql
   CREATE DATABASE jinka_plotter CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
4. Click "**Go**"
5. You should see a success message

### Option B: Quick SQL Script

1. Open phpMyAdmin: http://localhost/phpmyadmin
2. Click "**Import**" tab
3. Click "**Choose File**"
4. Select: `database/quick-create-db.sql`
5. Click "**Go**"

## ✅ Step 3: Import Database Schema

1. In phpMyAdmin, select "**jinka_plotter**" database from the left sidebar
2. Click "**Import**" tab
3. Click "**Choose File**"
4. Navigate to: `database/schema.sql`
5. Click "**Go**"
6. Wait for import to complete (may take 30-60 seconds)

## ✅ Step 4: Verify Connection

1. Open the database checker: http://localhost/jinkaplotterwebsite/check-db.php
2. You should see green checkmarks ✅ for all checks
3. If everything is green, click "**Go to Website**"

## ✅ Step 5: Access Your Site

Your site should now be available at:
- **Homepage**: http://localhost/jinkaplotterwebsite/
- **Admin Panel**: http://localhost/jinkaplotterwebsite/admin/

## 🔧 Troubleshooting

### Issue: WAMP icon is RED
**Solution**: 
- Port 80 might be in use by another program (Skype, IIS, etc.)
- Click WAMP icon → Tools → Check Port 80
- If blocked, change Apache port or stop the conflicting program

### Issue: MySQL won't start
**Solution**:
- Port 3306 might be in use
- Click WAMP icon → MySQL → Service → Test Port 3306
- Restart your computer
- Check Windows Services (services.msc) for mysql service

### Issue: "Access denied for user 'root'@'localhost'"
**Solution**:
1. Open `.env` file in the project root
2. Check these lines:
   ```
   DB_USER=root
   DB_PASS=
   ```
3. If you've set a MySQL password, update `DB_PASS=your_password`
4. Save and refresh

### Issue: Database exists but no tables
**Solution**:
- Import the schema: `database/schema.sql` via phpMyAdmin
- Then import additional tables:
  - `database/create-customer-tables.sql`
  - `database/support_system.sql`
  - `database/create_deliveries_table.sql`
  - `database/theme_settings.sql`

### Issue: "Table doesn't exist" errors
**Solution**:
1. Check which tables are missing: http://localhost/jinkaplotterwebsite/database/check-tables.php
2. Import missing table SQL files from `database/` folder

## 📝 Configuration Files

### `.env` File (Already Created)
Location: Project root (`c:\wamp\www\jinkaplotterwebsite\.env`)

Current settings (WAMP defaults):
```env
ENVIRONMENT=development
DEBUG_MODE=true
DB_HOST=localhost
DB_NAME=jinka_plotter
DB_USER=root
DB_PASS=
```

### If you need to reset everything:
1. Delete `.env` file
2. Copy `.env.example` to `.env`
3. Edit `.env` with your settings

## 🎯 Quick Commands

### Check Database Connection:
http://localhost/jinkaplotterwebsite/check-db.php

### Check Tables:
http://localhost/jinkaplotterwebsite/database/check-tables.php

### phpMyAdmin:
http://localhost/phpmyadmin

### Your Website:
http://localhost/jinkaplotterwebsite/

## ✨ Next Steps After Setup

1. **Create Admin User**
   - Run: `database/check-admin.php` to see if admin exists
   - Create admin via: `database/setup.php`

2. **Test Website**
   - Browse products
   - Test cart functionality
   - Check contact form

3. **Configure Email** (Optional for testing)
   - Update SMTP settings in `.env`
   - Test with: `test-email-simple.php`

## 📞 Still Having Issues?

1. **Check the database checker**: Shows exactly what's wrong
2. **Check PHP errors**: Look in `logs/` folder
3. **Check WAMP logs**: 
   - Click WAMP icon → Apache → Apache error log
   - Click WAMP icon → MySQL → MySQL error log

---

**Quick Links**:
- Database Checker: http://localhost/jinkaplotterwebsite/check-db.php
- phpMyAdmin: http://localhost/phpmyadmin
- Your Site: http://localhost/jinkaplotterwebsite/

**Files Updated**:
- ✅ Created `.env` with WAMP defaults
- ✅ Enhanced database error handling
- ✅ Added database connection checker
- ✅ Updated configuration to use environment variables
