# Theme Customization - Installation Guide

## Quick Installation

### Step 1: Database Setup
Run the SQL file to add default theme settings:

```bash
# Using MySQL command line
mysql -u your_username -p your_database < database/theme_settings.sql

# Or using phpMyAdmin
# 1. Open phpMyAdmin
# 2. Select your database
# 3. Go to SQL tab
# 4. Copy contents of database/theme_settings.sql
# 5. Execute
```

### Step 2: Verify Files
Ensure these files exist:
- ✅ `admin/settings.php` (updated)
- ✅ `css/theme-variables.php` (new file)
- ✅ `index.php` (updated with theme CSS)
- ✅ `product-detail.php` (updated with theme CSS)
- ✅ `products.php` (updated with theme CSS)
- ✅ `cart.php` (updated with theme CSS)
- ✅ `checkout.php` (updated with theme CSS)

### Step 3: Test Access
1. Navigate to `/admin/login`
2. Log in with admin credentials
3. Go to **Settings**
4. Click **Theme Customization** tab
5. You should see the theme customization interface

### Step 4: Test Functionality
1. Change a color (e.g., Primary Color)
2. Watch live preview update
3. Click "Save Theme Settings"
4. You should see success message
5. Visit homepage - refresh with Ctrl+F5
6. Colors should reflect your changes

## Troubleshooting Installation

### Issue: Theme tab not appearing
**Solution**: 
- Clear browser cache
- Check if settings.php was updated correctly
- Look for JavaScript errors in console

### Issue: Changes not saving
**Solution**:
- Verify database connection
- Check if settings table exists
- Ensure admin user has proper permissions
- Check PHP error logs

### Issue: Theme CSS not loading
**Solution**:
- Verify `css/theme-variables.php` file exists
- Check file permissions (should be readable)
- Ensure database connection in theme-variables.php works
- Test by visiting: `yoursite.com/css/theme-variables.php` directly

### Issue: Colors not applying
**Solution**:
- Hard refresh browser (Ctrl+F5)
- Clear server cache (cache folder)
- Check if theme CSS is included in page source
- Verify CSS variables are being output

## Manual Database Setup

If you can't run the SQL file, add settings manually:

```sql
-- Run these queries one by one in phpMyAdmin or MySQL

INSERT INTO settings (setting_key, setting_value, setting_type) 
VALUES ('theme_primary_color', '#3b82f6', 'string');

INSERT INTO settings (setting_key, setting_value, setting_type) 
VALUES ('theme_secondary_color', '#8b5cf6', 'string');

INSERT INTO settings (setting_key, setting_value, setting_type) 
VALUES ('theme_accent_color', '#06b6d4', 'string');

INSERT INTO settings (setting_key, setting_value, setting_type) 
VALUES ('theme_success_color', '#10b981', 'string');

INSERT INTO settings (setting_key, setting_value, setting_type) 
VALUES ('theme_warning_color', '#f59e0b', 'string');

INSERT INTO settings (setting_key, setting_value, setting_type) 
VALUES ('theme_error_color', '#ef4444', 'string');

INSERT INTO settings (setting_key, setting_value, setting_type) 
VALUES ('theme_text_primary', '#1e293b', 'string');

INSERT INTO settings (setting_key, setting_value, setting_type) 
VALUES ('theme_text_secondary', '#64748b', 'string');

INSERT INTO settings (setting_key, setting_value, setting_type) 
VALUES ('theme_background', '#ffffff', 'string');

INSERT INTO settings (setting_key, setting_value, setting_type) 
VALUES ('theme_card_background', '#f8fafc', 'string');

INSERT INTO settings (setting_key, setting_value, setting_type) 
VALUES ('theme_border_color', '#e2e8f0', 'string');

INSERT INTO settings (setting_key, setting_value, setting_type) 
VALUES ('theme_link_color', '#3b82f6', 'string');

INSERT INTO settings (setting_key, setting_value, setting_type) 
VALUES ('theme_button_radius', '8', 'number');

INSERT INTO settings (setting_key, setting_value, setting_type) 
VALUES ('theme_card_radius', '12', 'number');

INSERT INTO settings (setting_key, setting_value, setting_type) 
VALUES ('theme_font_family', 'system-ui, -apple-system, sans-serif', 'string');

INSERT INTO settings (setting_key, setting_value, setting_type) 
VALUES ('theme_heading_font', 'system-ui, -apple-system, sans-serif', 'string');
```

## Verify Installation

### Check Database
```sql
SELECT setting_key, setting_value 
FROM settings 
WHERE setting_key LIKE 'theme_%';
```

You should see 16 rows returned.

### Check File Permissions
```bash
# On Linux/Mac
chmod 644 css/theme-variables.php

# On Windows
# Right-click file > Properties > Security
# Ensure "Read" permission is enabled
```

### Check Theme CSS Output
Visit: `http://yoursite.com/css/theme-variables.php`

You should see CSS like:
```css
:root {
    --theme-primary: #3b82f6;
    --theme-secondary: #8b5cf6;
    /* etc... */
}
```

## Post-Installation

### 1. Test Theme Changes
- Go to Admin → Settings → Theme Customization
- Change primary color to red (#ff0000)
- Save settings
- Hard refresh homepage (Ctrl+F5)
- Buttons should now be red

### 2. Reset to Defaults
- Click "Reset to Defaults" button
- Save settings
- Verify colors return to original

### 3. Document Your Setup
Make note of:
- Database name used
- Admin access URL
- Any custom modifications made
- Theme settings backup

## Backup Recommendations

### Before Customizing
Export current theme settings:
```sql
SELECT * FROM settings WHERE setting_key LIKE 'theme_%' 
INTO OUTFILE '/tmp/theme_backup.sql';
```

### After Customizing
Keep a copy of your custom theme values in case you need to restore them.

## Support

If you encounter issues:
1. Check PHP error logs
2. Check browser console for errors
3. Verify database connection
4. Review installation steps
5. Check file permissions

## Success Indicators

You'll know installation succeeded when:
- ✅ Theme tab visible in admin settings
- ✅ Color pickers working
- ✅ Live preview updating
- ✅ Save button works
- ✅ Changes reflect on frontend
- ✅ Cache auto-clears
- ✅ No PHP/JavaScript errors

## Next Steps

After successful installation:
1. Read `ADMIN_THEME_GUIDE.md` for usage instructions
2. Customize your theme to match your brand
3. Test on multiple devices
4. Train your team on theme customization
5. Document your custom theme choices

---

**Installation Complete! 🎉**

Your theme customization system is now ready to use.
