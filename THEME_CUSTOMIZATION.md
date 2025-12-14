# Theme Customization Guide

## Overview
The website now includes a powerful theme customization system that allows you to change colors, fonts, and styling from the admin panel without touching any code.

## Accessing Theme Settings

1. Log in to the admin panel at `/admin/login`
2. Navigate to **Settings** from the sidebar
3. Click on the **Theme Customization** tab (🎨 icon)

## Theme Options

### Brand Colors
- **Primary Color**: Main brand color used for buttons, links, and key elements
- **Secondary Color**: Secondary accent color for variety
- **Accent Color**: Highlight color for special elements

### Status Colors
- **Success Color**: Used for success messages and confirmations (default: green)
- **Warning Color**: Used for warnings and cautions (default: orange)
- **Error Color**: Used for error messages and alerts (default: red)

### Typography & Background
- **Primary Text**: Main text color throughout the site
- **Secondary Text**: Muted/secondary text color
- **Background**: Main page background color
- **Card Background**: Background color for cards and sections
- **Border Color**: Color for borders and dividers
- **Link Color**: Color for hyperlinks

### Styling Options
- **Button Border Radius**: Roundness of buttons (0 = square, 50 = pill shape)
- **Card Border Radius**: Roundness of cards and containers

### Typography
- **Body Font Family**: Font used for body text
- **Heading Font Family**: Font used for headings

## Available Fonts

### Sans-serif Fonts
- System Default
- Inter
- Roboto
- Open Sans
- Lato
- Poppins
- Montserrat

### Serif Fonts
- Georgia
- Merriweather
- Playfair Display

## Live Preview

The theme customization page includes a live preview panel that shows how your changes will look before saving. As you adjust colors and settings, the preview updates in real-time.

## How It Works

### Technical Architecture

1. **Database Storage**: Theme settings are stored in the `settings` table
2. **Dynamic CSS**: `css/theme-variables.php` generates CSS custom properties from database settings
3. **Auto-applied**: Theme CSS is automatically included on all pages
4. **Cache Clearing**: When you save theme changes, the cache is automatically cleared

### CSS Variables Generated

The system generates the following CSS custom properties:
```css
--theme-primary
--theme-primary-light
--theme-primary-dark
--theme-secondary
--theme-accent
--theme-success
--theme-warning
--theme-error
--theme-text-primary
--theme-text-secondary
--theme-link
--theme-bg
--theme-card-bg
--theme-border
--theme-btn-radius
--theme-card-radius
--theme-font-family
--theme-heading-font
```

## Applying Theme Colors

### In HTML/PHP
You can use theme colors in your custom code:

```html
<div style="color: var(--theme-primary);">Primary colored text</div>
<button style="background: var(--theme-success);">Success Button</button>
```

### In CSS
```css
.custom-element {
    background-color: var(--theme-primary);
    border: 1px solid var(--theme-border);
    border-radius: var(--theme-card-radius);
    font-family: var(--theme-font-family);
}
```

## Reset to Defaults

Click the **Reset to Defaults** button on the theme customization page to restore the original theme colors and settings.

## Tips for Best Results

### Color Selection
1. **Contrast**: Ensure sufficient contrast between text and background colors
2. **Consistency**: Use colors from the same palette family
3. **Accessibility**: Test colors for WCAG compliance
4. **Brand Alignment**: Match your brand guidelines

### Typography
1. **Readability**: Choose readable fonts for body text
2. **Hierarchy**: Use different fonts for headings and body for visual hierarchy
3. **Loading Speed**: System fonts load fastest
4. **Compatibility**: Test fonts across different browsers

### Styling
1. **Modern Look**: Higher border radius (10-16px) for modern feel
2. **Corporate Look**: Lower border radius (4-8px) for professional feel
3. **Button Size**: Ensure buttons are easily clickable (min 44x44px)

## Example Theme Presets

### Modern Blue Theme
```
Primary: #3b82f6 (Blue)
Secondary: #8b5cf6 (Purple)
Accent: #06b6d4 (Cyan)
Success: #10b981 (Green)
Border Radius: 12px (buttons), 16px (cards)
Font: Inter
```

### Professional Dark Theme
```
Primary: #2563eb (Dark Blue)
Secondary: #1e40af (Navy)
Text Primary: #1e293b (Dark Gray)
Background: #f8fafc (Light Gray)
Border Radius: 6px (buttons), 8px (cards)
Font: Roboto
```

### Fresh Green Theme
```
Primary: #10b981 (Green)
Secondary: #059669 (Dark Green)
Accent: #34d399 (Light Green)
Font: Poppins
```

### Elegant Serif Theme
```
Primary: #7c3aed (Purple)
Secondary: #6d28d9 (Dark Purple)
Heading Font: Playfair Display
Body Font: Merriweather
Border Radius: 8px
```

## Troubleshooting

### Changes Not Showing
1. **Clear Browser Cache**: Press Ctrl+F5 (Windows) or Cmd+Shift+R (Mac)
2. **Check Cache**: The system automatically clears server cache on save
3. **Incognito Mode**: Test in incognito/private browsing mode

### Colors Look Wrong
1. **Verify Hex Codes**: Ensure hex codes start with # and are 6 characters
2. **Check Contrast**: Some color combinations may not work well
3. **Browser Compatibility**: Test in different browsers

### Preview Not Updating
1. **Refresh Page**: Reload the settings page
2. **JavaScript Errors**: Check browser console for errors
3. **Color Picker Support**: Ensure your browser supports color input type

## Advanced Customization

For developers who need more control, you can:

1. **Direct Database**: Update settings table directly
   ```sql
   UPDATE settings 
   SET setting_value = '#ff0000' 
   WHERE setting_key = 'theme_primary_color';
   ```

2. **Override in CSS**: Add custom CSS rules to override theme variables
   ```css
   :root {
       --theme-primary: #custom-color !important;
   }
   ```

3. **Custom PHP**: Generate additional theme variables in `theme-variables.php`

## Support

For questions or issues with theme customization:
- Check this documentation first
- Review the live preview to test changes
- Contact your developer for advanced customizations
- Backup your settings before major changes

## File Locations

- Theme Settings Form: `/admin/settings.php` (Theme Customization tab)
- CSS Generator: `/css/theme-variables.php`
- Database Table: `settings` (keys starting with `theme_`)

## Version History

- **v1.0** (2025-01-20): Initial theme customization system
  - 16 customizable color options
  - 9 font choices
  - Border radius controls
  - Live preview
  - Auto cache clearing
