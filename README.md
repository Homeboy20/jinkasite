# JINKA Cutting Plotter Website

Professional PHP website for selling JINKA XL-1351E Cutting Plotter in Kenya and Tanzania.

## Features

### Core Features
- **Responsive Design**: Mobile-first design that works on all devices
- **Professional Layout**: B2B-focused design for commercial equipment
- **Contact Integration**: WhatsApp, phone, and email contact options
- **Contact Form**: PHP-powered contact form with email notifications
- **SEO Optimized**: Proper meta tags and semantic HTML
- **Fast Loading**: Optimized CSS and minimal JavaScript
- **Modern UI**: Clean, professional design with smooth animations

### AI-Powered Features 🤖
- **DeepSeek AI Integration**:
  - Product title optimization (SEO-friendly, 50-60 chars)
  - Description enhancement (150-300 words with keywords)
  - Selling points extraction (5-8 key benefits)
  - Feature generation from specifications
  
- **Kimi AI (Moonshot) Integration**:
  - Meta description generation (160 chars)
  - SEO keyword research (10-15 targeted keywords)
  - Local market optimization (Kenya/Tanzania)
  
- **Full Optimization**: Complete product enhancement in one click
- **Demo Mode**: Test AI features without API keys
- **Production Mode**: Real AI optimization with API integration

### Product Management
- **Alibaba Import**: Auto-fetch product data from Alibaba URLs
- **Smart Data Extraction**: Names, descriptions, specs, images
- **AI Enhancement**: Optimize imported products with AI
- **Batch Operations**: Efficient multi-product workflows
- **Image Management**: Upload and organize product images

## Installation

### Requirements

- PHP 7.0 or higher
- Web server (Apache, Nginx, or PHP built-in server)
- Mail server configured (for contact form emails)

### Setup Instructions

1. **Upload files to your web server**
   - Upload all files to your web hosting public_html or www directory
   - Ensure proper file permissions (644 for files, 755 for directories)

2. **Configure contact information**
   - Edit `index.php` and update the following variables:
     ```php
     $business_name = "Your Business Name";
     $whatsapp_number = "+254700000000"; // Your WhatsApp number
     $phone_number = "+254700000000"; // Your phone number
     $email = "your@email.com"; // Your email address
     ```

3. **Configure email settings**
   - Edit `contact.php` and update:
     ```php
     $to_email = "your@email.com"; // Email where inquiries will be sent
     ```

4. **Test the website**
   - Visit your website URL
   - Test all contact methods (WhatsApp, phone, contact form)
   - Verify responsive design on mobile devices

### Local Development

To run locally using PHP built-in server:

```bash
cd jinkaplotterwebsite
php -S localhost:8000
```

Then visit `http://localhost:8000` in your browser.

### AI Optimization Setup (Optional)

The AI features work in **Demo Mode** by default (no setup required). For production with real AI:

#### Option 1: Demo Mode (Default)
- No setup required
- Works immediately
- Shows sample AI responses
- Perfect for testing and training

#### Option 2: Production Mode
1. **Get API Keys** (5 minutes):
   - DeepSeek: https://platform.deepseek.com/ (free tier available)
   - Kimi AI: https://platform.moonshot.cn/ (free tier available)

2. **Configure** (1 minute):
   ```bash
   # Edit configuration file
   admin/includes/ai_config.php
   
   # Add your API keys
   define('DEEPSEEK_API_KEY', 'sk-your-deepseek-key');
   define('KIMI_API_KEY', 'sk-your-kimi-key');
   ```

3. **Test** (2 minutes):
   - Open `admin/test_ai.html` in browser
   - Run all tests
   - Verify AI responses

**Cost**: ~$0.01-0.02 per product optimization (very affordable!)

For detailed instructions, see:
- Quick Start: `admin/docs/AI_QUICK_START.md`
- Full Guide: `admin/docs/AI_OPTIMIZATION_GUIDE.md`
- Checklist: `admin/docs/IMPLEMENTATION_CHECKLIST.md`

## File Structure

```
jinkaplotterwebsite/
├── index.php                          # Main homepage
├── contact.php                        # Contact form handler
├── README.md                          # This file
├── .gitignore                         # Git ignore (protects API keys)
├── css/
│   └── style.css                      # Main stylesheet
├── js/
│   └── script.js                      # JavaScript functionality
├── images/
│   ├── plotter-hero.webp             # Hero section image
│   ├── plotter-main.jpg              # Main product image
│   └── plotter-action.jpg            # Action/usage image
├── includes/                          # PHP includes
├── admin/                             # Admin panel
│   ├── products.php                  # Product management (AI-enhanced)
│   ├── ai_optimize.php               # AI optimization endpoint
│   ├── fetch_alibaba.php             # Alibaba import API
│   ├── test_ai.html                  # AI testing interface
│   ├── includes/
│   │   ├── ai_helper.php            # AI service class
│   │   └── ai_config.php            # API configuration (not in git)
│   ├── css/
│   │   ├── ai-optimization.css      # AI section styling
│   │   └── alibaba-import.css       # Import section styling
│   └── docs/
│       ├── AI_OPTIMIZATION_GUIDE.md      # Complete AI guide
│       ├── AI_QUICK_START.md             # 5-minute quick start
│       ├── AI_FEATURE_SUMMARY.txt        # Visual summary
│       ├── IMPLEMENTATION_CHECKLIST.md   # Implementation guide
│       ├── ALIBABA_IMPORT_GUIDE.md       # Alibaba import guide
│       ├── TESTING_ALIBABA_IMPORT.md     # Testing guide
│       ├── ALIBABA_FEATURE_SUMMARY.md    # Feature summary
│       └── QUICK_REFERENCE.txt           # Quick reference
```

## Customization

### Changing Colors

Edit `css/style.css` and modify the CSS variables:

```css
:root {
    --primary-color: #2563eb;      /* Main brand color */
    --primary-dark: #1e40af;       /* Darker shade */
    --secondary-color: #10b981;    /* Accent color */
    --text-dark: #1f2937;          /* Dark text */
    --text-light: #6b7280;         /* Light text */
}
```

### Changing Pricing

Edit `index.php` and update:

```php
$product_price_kes = "120,000";      // Kenya Shillings
$product_price_tzs = "2,400,000";    // Tanzania Shillings
```

### Adding More Products

To add more products, you can:
1. Duplicate `index.php` and create product-specific pages
2. Modify the content for each product
3. Update navigation links

### Changing Images

Replace images in the `images/` folder with your own:
- `plotter-hero.webp` - Hero section (recommended: 642x588px)
- `plotter-main.jpg` - Main product image (recommended: 750x750px)
- `plotter-action.jpg` - Action shot (recommended: 640x424px)

## SEO Optimization

The website includes:
- Proper meta tags in `<head>` section
- Semantic HTML5 structure
- Alt text for all images
- Mobile-responsive design
- Fast loading times

To improve SEO further:
1. Add Google Analytics tracking code
2. Submit sitemap to Google Search Console
3. Add structured data (Schema.org) markup
4. Optimize images (compress, use WebP format)

## Contact Form

The contact form:
- Validates all input fields
- Sanitizes data to prevent XSS attacks
- Sends email notifications
- Saves inquiries to `inquiries.txt` as backup
- Returns JSON response for AJAX handling

### Email Configuration

If emails are not sending:
1. Check your server's mail configuration
2. Verify SPF/DKIM records for your domain
3. Consider using SMTP (PHPMailer library)
4. Check spam folder for test emails

## Browser Support

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)
- Mobile browsers (iOS Safari, Chrome Mobile)

## Performance

- Minimal CSS (< 10KB)
- Minimal JavaScript (< 5KB)
- Optimized images
- No external dependencies (except Google Fonts)
- Fast page load times

## Security

- Input sanitization on contact form
- CSRF protection recommended for production
- SQL injection not applicable (no database)
- XSS prevention through PHP filters

## Support

For technical support or customization requests, contact the developer.

## License

This website is proprietary software. All rights reserved.

## Version

Version 1.0 - November 2025

## Recent Updates

### AI Optimization System (December 2024)
- **DeepSeek AI Integration**: Product title and description optimization
- **Kimi AI Integration**: SEO keywords and meta descriptions
- **Full Automation**: Complete product enhancement in one click
- **Demo Mode**: Test without API keys
- **Documentation**: Comprehensive guides and testing tools

### Alibaba Product Import (December 2024)
- **Auto-Import**: Fetch product data from Alibaba URLs
- **Smart Parsing**: Extracts names, descriptions, specs, images
- **One-Click Fill**: Auto-populates product forms
- **Documentation**: Complete user guides

### Admin Panel Features
- Product management system
- Image upload functionality
- Category management
- SEO optimization tools
- AI-powered content generation

## Changelog

### Version 2.0 (December 2024)
- Added AI optimization system (DeepSeek + Kimi AI)
- Alibaba product import feature
- Enhanced admin panel with AI tools
- Comprehensive documentation suite
- Testing tools and interfaces
- API configuration system
- Security improvements (.gitignore)

### Version 1.0 (November 2025)
- Initial release
- Responsive design
- Contact form integration
- WhatsApp integration
- Professional B2B layout

````
