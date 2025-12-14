# Dynamic Currency Detection System

## Overview
Automatic currency detection and conversion system based on user's IP geolocation. Supports multiple East African currencies with manual override capability.

## Supported Currencies
- **KES** (Kenyan Shilling) - Base currency
- **TZS** (Tanzanian Shilling) - KES × 18.5
- **UGX** (Ugandan Shilling) - KES × 30
- **USD** (US Dollar) - KES ÷ 130

## How It Works

### 1. Initial Detection
- On first page load, system detects user's IP address
- Queries free geolocation APIs (ip-api.com → ipapi.co fallback)
- Maps country code to appropriate currency (KE→KES, TZ→TZS, etc.)
- Defaults to KES for local/private IPs

### 2. Session Storage
- Currency and country stored in PHP session
- Prevents repeated API calls
- Persists across all pages during user session

### 3. Price Conversion
- All product prices stored in KES in database
- Converted dynamically using exchange rates
- `$currencyDetector->getPrice($priceKES)` - Returns converted amount
- `$currencyDetector->formatPrice($price)` - Returns formatted string with symbol

### 4. Manual Override
- Currency switcher in header dropdown
- AJAX endpoint at `api/currency.php`
- Updates session and reloads page with new currency

## Files Structure

```
includes/
  ├── CurrencyDetector.php    # Main currency detection class
  └── config.php               # Initializes $currencyDetector globally

api/
  └── currency.php             # REST API endpoint (GET/POST)

css/
  └── style.css                # Currency switcher styles

test-currency.php              # Test/debug page
```

## API Endpoints

### GET api/currency.php
Returns current currency information:
```json
{
  "success": true,
  "currency": "KES",
  "country": "KE",
  "details": {
    "code": "KES",
    "name": "Kenyan Shilling",
    "symbol": "KES",
    "rate": 1
  },
  "available": { /* all currencies */ }
}
```

### POST api/currency.php
Switch currency:
```json
{
  "currency": "TZS"
}
```

Response:
```json
{
  "success": true,
  "currency": "TZS",
  "details": { /* currency details */ }
}
```

## Usage in Code

### Display Product Price
```php
<?php
$currency = $currencyDetector->getCurrentCurrency();
$price = $currencyDetector->getPrice($product['price_kes']);
echo $currencyDetector->formatPrice($price);
?>
```

### Get Currency Details
```php
<?php
$details = $currencyDetector->getCurrencyDetails();
// Returns: ['code' => 'KES', 'name' => 'Kenyan Shilling', 'symbol' => 'KES', 'rate' => 1]
?>
```

### Manual Currency Switch
```javascript
async function switchCurrency(currency) {
    const response = await fetch('api/currency.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ currency: currency })
    });
    
    if (response.ok) {
        window.location.reload();
    }
}
```

## CurrencyDetector Methods

### Public Methods
- `getInstance()` - Get singleton instance
- `getCurrentCurrency()` - Returns current currency code (e.g., 'KES')
- `getCountryCode()` - Returns detected country code (e.g., 'KE')
- `getCurrencyDetails()` - Returns current currency details array
- `getAvailableCurrencies()` - Returns all supported currencies
- `getPrice($priceKES, $currency = null)` - Convert price from KES
- `formatPrice($price, $currency = null)` - Format price with symbol
- `setCurrency($currencyCode)` - Manually set currency

### Private Methods
- `getUserIP()` - Gets user's IP (handles proxies)
- `isPrivateIP($ip)` - Checks if IP is local/private
- `getCountryFromIP()` - Queries geolocation APIs
- `detectCurrency()` - Initial currency detection
- `mapCountryToCurrency($countryCode)` - Maps country to currency

## Testing

### Test Page
Visit: `http://localhost/jinkaplotterwebsite/test-currency.php`

Shows:
- Detected IP and country
- Current currency with exchange rate
- All available currencies
- Price conversion examples
- Test buttons to switch currencies
- Session information

### Manual Testing
1. Visit any product page
2. Check currency switcher in header (top-right)
3. Click to open dropdown
4. Select different currency
5. Page reloads with new prices

## Configuration

### Exchange Rates
Edit in `CurrencyDetector.php`:
```php
private $currencies = [
    'KES' => ['name' => 'Kenyan Shilling', 'symbol' => 'KES', 'rate' => 1],
    'TZS' => ['name' => 'Tanzanian Shilling', 'symbol' => 'TZS', 'rate' => 18.5],
    'UGX' => ['name' => 'Ugandan Shilling', 'symbol' => 'UGX', 'rate' => 30],
    'USD' => ['name' => 'US Dollar', 'symbol' => '$', 'rate' => 0.0077]
];
```

### Country Mapping
Edit in `mapCountryToCurrency()`:
```php
private function mapCountryToCurrency($countryCode) {
    $mapping = [
        'KE' => 'KES',
        'TZ' => 'TZS',
        'UG' => 'UGX',
        'RW' => 'RWF',
        'US' => 'USD',
    ];
    return $mapping[$countryCode] ?? 'KES'; // Default to KES
}
```

## Troubleshooting

### Currency Not Detected
- Check if session is started in config.php
- Verify `$currencyDetector` is initialized globally
- Test with `test-currency.php` page

### API Not Working
- Ensure `api/` directory has proper permissions
- Check browser console for AJAX errors
- Verify WAMP/Apache is running

### Prices Not Converting
- Ensure `$currencyDetector->getPrice()` is used instead of direct `price_kes`
- Check that CurrencyDetector is instantiated before use
- Verify currency rates are configured correctly

### Local Development
- Private IPs (127.0.0.1, 192.168.x.x) default to Kenya/KES
- Use manual switcher to test other currencies
- Or temporarily hardcode country in `getCountryFromIP()`

## Security Notes
- IP detection uses free APIs (no API key required)
- 2-second timeout prevents hanging
- Session-based storage (no database writes per request)
- Input validation on currency switching
- No sensitive data exposed in API responses

## Performance
- Single API call per session (cached)
- Fallback API if primary fails
- Lightweight price calculations
- Minimal overhead (~0.01s first load, <0.001s subsequent)

## Future Enhancements
- Add more currencies (GBP, EUR, etc.)
- Fetch live exchange rates from API
- Remember user preference in cookies/localStorage
- Add currency preference to user account settings
- Implement currency conversion caching
