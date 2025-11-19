# Footer Customization Guide

## Overview
The footer now supports full customization through the database configuration. All settings can be managed through the admin panel or directly in the `site_settings` table.

## Configuration Settings

### Logo & Branding
- **`footer_logo`** - URL or path to footer logo image (e.g., `images/footer-logo.png`)
  - If empty, displays site name as text
  - Recommended size: 180x60px or similar aspect ratio
  - Supports: JPG, PNG, SVG, WEBP

- **`footer_about`** - About text displayed under logo/name
  - Default: "Professional printing equipment supplier serving Kenya and Tanzania..."
  - Accepts HTML for formatting

### Contact Information
- **`footer_phone_label_tz`** - Label for Tanzania phone (default: "Tanzania")
- **`footer_phone_label_ke`** - Label for Kenya phone (default: "Kenya")
- **`footer_address`** - Location text (default: "Kenya & Tanzania")
- **`contact_phone`** - Tanzania phone number (already exists)
- **`contact_phone_ke`** - Kenya phone number (already exists)
- **`contact_email`** - Contact email (already exists)

### Business Hours
- **`footer_hours_weekday`** - Monday-Friday hours (default: "8:00 AM - 6:00 PM")
- **`footer_hours_saturday`** - Saturday hours (default: "9:00 AM - 4:00 PM")
- **`footer_hours_sunday`** - Sunday hours (default: "Closed")
- **`footer_whatsapp_label`** - WhatsApp availability (default: "24/7 Available")

### Social Media
- **`facebook_url`** - Facebook page URL (already exists)
- **`instagram_url`** - Instagram profile URL (already exists)
- **`twitter_url`** - Twitter/X profile URL (already exists)
- **`linkedin_url`** - LinkedIn profile URL (already exists)
- **`whatsapp_number`** - WhatsApp number for footer button (already exists)

### Copyright
- **`footer_copyright`** - Custom copyright text
  - Default: "All rights reserved."
  - Automatically includes year and business name

## Database Configuration

### Using MySQL/phpMyAdmin

```sql
-- Add footer logo
INSERT INTO site_settings (setting_key, setting_value) 
VALUES ('footer_logo', 'images/my-footer-logo.png')
ON DUPLICATE KEY UPDATE setting_value = 'images/my-footer-logo.png';

-- Customize about text
INSERT INTO site_settings (setting_key, setting_value) 
VALUES ('footer_about', 'Your custom description here')
ON DUPLICATE KEY UPDATE setting_value = 'Your custom description here';

-- Set business hours
INSERT INTO site_settings (setting_key, setting_value) 
VALUES ('footer_hours_weekday', '9:00 AM - 5:00 PM')
ON DUPLICATE KEY UPDATE setting_value = '9:00 AM - 5:00 PM';

-- Add social media
INSERT INTO site_settings (setting_key, setting_value) 
VALUES ('facebook_url', 'https://facebook.com/yourpage')
ON DUPLICATE KEY UPDATE setting_value = 'https://facebook.com/yourpage';

-- Custom copyright
INSERT INTO site_settings (setting_key, setting_value) 
VALUES ('footer_copyright', 'All rights reserved. Made with ❤️ in East Africa')
ON DUPLICATE KEY UPDATE setting_value = 'All rights reserved. Made with ❤️ in East Africa';
```

## Features

### Conditional Display
- Social media icons only show if URLs are configured
- Phone numbers only display if set
- Email only shows if configured
- Business hours sections can be individually hidden by setting to empty string

### Smart Defaults
- All settings have sensible defaults
- Falls back to existing site settings where applicable
- Empty values hide corresponding sections

### Mobile Responsive
- Logo automatically scales for mobile devices
- Contact info stacks vertically on small screens
- Social icons remain accessible on all screen sizes

## Examples

### Minimal Footer (Logo Only)
```sql
UPDATE site_settings SET setting_value = 'images/logo-white.png' WHERE setting_key = 'footer_logo';
UPDATE site_settings SET setting_value = '' WHERE setting_key = 'footer_about';
```

### Custom Business Hours
```sql
UPDATE site_settings SET setting_value = 'Mon-Thu: 8AM-6PM, Fri: 8AM-4PM' WHERE setting_key = 'footer_hours_weekday';
UPDATE site_settings SET setting_value = '10:00 AM - 2:00 PM' WHERE setting_key = 'footer_hours_saturday';
UPDATE site_settings SET setting_value = 'By Appointment Only' WHERE setting_key = 'footer_hours_sunday';
```

### International Setup
```sql
UPDATE site_settings SET setting_value = 'Tanzania Office' WHERE setting_key = 'footer_phone_label_tz';
UPDATE site_settings SET setting_value = 'Kenya Office' WHERE setting_key = 'footer_phone_label_ke';
UPDATE site_settings SET setting_value = 'East Africa Operations' WHERE setting_key = 'footer_address';
```

## CSS Customization

Footer styles are in `css/style.css` starting at line 1665:

```css
/* Customize footer logo size */
.footer-logo-img {
    max-width: 200px;  /* Change logo width */
    max-height: 80px;  /* Change logo height */
}

/* Customize footer colors */
.footer {
    background: linear-gradient(135deg, #your-color1, #your-color2);
}

/* Customize social icon colors */
.footer-social a:hover {
    background: linear-gradient(135deg, #your-hover-color1, #your-hover-color2);
}
```

## Support

For additional customization or questions:
- Email: support@yoursite.com
- Check `includes/config.php` for the `site_setting()` function
- All settings are cached for performance
