# Test Alibaba Import Feature

## Test URLs

You can test the Alibaba import feature with these example URLs:

### Cutting Plotter Example
```
https://www.alibaba.com/product-detail/Professional-Vinyl-Cutting-Plotter-Machine_62147889416.html
```

### Industrial Printer Example
```
https://www.alibaba.com/product-detail/Large-Format-Printer-Eco-Solvent_1600234567890.html
```

## How to Test

1. Open the Admin Panel
2. Go to Products → Add New Product
3. Click "Show Import" in the Alibaba import section
4. Paste one of the test URLs above
5. Click "Fetch Product"
6. Wait 5-15 seconds for data to load
7. Review the auto-filled information
8. Set prices and click Create Product

## Expected Results

✅ Product name extracted from page title  
✅ Description auto-filled  
✅ SKU auto-generated  
✅ Specifications imported into table  
✅ Features listed  
✅ Form automatically switches to Pricing tab  

## Notes

- The actual product pages on Alibaba.com may change their structure
- If a specific URL doesn't work, try another product from Alibaba
- Some pages may have restricted access or require login
- The import works best with standard product listing pages

## Development Mode

For testing without internet/Alibaba access, you can modify `fetch_alibaba.php` to return mock data:

```php
// Add this at the top of fetch_alibaba.php for testing
$mock_mode = true;

if ($mock_mode) {
    echo json_encode([
        'success' => true,
        'product' => [
            'name' => 'JINKA Professional Cutting Plotter 1350mm',
            'sku' => 'JINKA-1350-PRO',
            'short_description' => 'Professional-grade vinyl cutting plotter for signage and graphics',
            'description' => 'High-precision cutting plotter designed for commercial use. Features advanced servo motor technology, large cutting width, and user-friendly software. Perfect for sign making, vehicle graphics, and commercial printing.',
            'specifications' => [
                ['name' => 'Cutting Width', 'value' => '1350mm (53 inches)'],
                ['name' => 'Cutting Speed', 'value' => '800mm/s'],
                ['name' => 'Cutting Force', 'value' => '10-500g'],
                ['name' => 'Interface', 'value' => 'USB 2.0, RS-232C'],
                ['name' => 'Power', 'value' => 'AC 110-240V, 50/60Hz']
            ],
            'features' => [
                'High precision stepper motor',
                'Professional-grade construction',
                'CE certified',
                'User-friendly software included',
                'Low noise operation'
            ],
            'price' => 'US$ 800 - $1,200',
            'images' => []
        ]
    ]);
    exit;
}
```

## Troubleshooting

If import fails:
1. Check browser console for errors (F12)
2. Verify `fetch_alibaba.php` has no syntax errors
3. Ensure cURL extension is enabled in PHP
4. Check server has outbound internet access
5. Try a different Alibaba product URL

---
Created: November 7, 2025
