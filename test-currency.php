<?php
/**
 * Currency Detection Test Page
 * Tests the CurrencyDetector functionality
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/CurrencyDetector.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize currency detector
$currencyDetector = CurrencyDetector::getInstance();

// Get current currency info
$currentCurrency = $currencyDetector->getCurrentCurrency();
$currentCountry = $currencyDetector->getCountryCode();
$availableCurrencies = $currencyDetector->getAvailableCurrencies();
$currencyDetails = $currencyDetector->getCurrencyDetails();
$baseCurrency = $currencyDetector->getBaseCurrency();

// Test price conversions - price is in base currency
$testPrice = 150000; // Sample price in base currency
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Currency Detection Test</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            padding: 2rem;
            background: #f9fafb;
            color: #1f2937;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
        }
        h1 {
            color: #ff5900;
            margin-bottom: 2rem;
            font-size: 1.75rem;
        }
        h2 {
            color: #1f2937;
            margin: 1.5rem 0 1rem;
            font-size: 1.25rem;
            border-bottom: 2px solid #ff5900;
            padding-bottom: 0.5rem;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 200px 1fr;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }
        .label {
            font-weight: 600;
            color: #4b5563;
        }
        .value {
            color: #1f2937;
            font-family: 'Courier New', monospace;
            background: #f3f4f6;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
        }
        .currency-card {
            background: #fff7ed;
            border: 2px solid #ff5900;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        .price-test {
            background: #f0fdf4;
            border: 1px solid #10b981;
            padding: 1rem;
            border-radius: 8px;
            margin: 0.75rem 0;
        }
        .price-large {
            font-size: 1.5rem;
            font-weight: 700;
            color: #ff5900;
            margin: 0.5rem 0;
        }
        .btn {
            background: #ff5900;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.875rem;
            margin-top: 1rem;
            transition: background 0.2s;
        }
        .btn:hover {
            background: #cc4700;
        }
        .success {
            background: #d1fae5;
            border-left: 4px solid #10b981;
            padding: 0.75rem 1rem;
            margin: 1rem 0;
            border-radius: 4px;
        }
        .warning {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 0.75rem 1rem;
            margin: 1rem 0;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🌍 Currency Detection Test</h1>
        
        <div class="success">
            ✅ Currency system is active and operational
        </div>

        <h2>📍 Detection Information</h2>
        <div class="info-grid">
            <div class="label">Your IP Address:</div>
            <div class="value"><?php echo htmlspecialchars($currencyDetector->getUserIP()); ?></div>
            
            <div class="label">Detected Country:</div>
            <div class="value"><?php echo htmlspecialchars($currentCountry); ?></div>
            
            <div class="label">Base Currency:</div>
            <div class="value" style="background: #10b981; color: white; font-weight: 600;">
                <?php echo htmlspecialchars($baseCurrency); ?>
            </div>
            
            <div class="label">Current Currency:</div>
            <div class="value"><?php echo htmlspecialchars($currentCurrency); ?></div>
            
            <div class="label">Currency Name:</div>
            <div class="value"><?php echo htmlspecialchars($currencyDetails['name']); ?></div>
            
            <div class="label">Currency Symbol:</div>
            <div class="value"><?php echo htmlspecialchars($currencyDetails['symbol']); ?></div>
            
            <div class="label">Exchange Rate:</div>
            <div class="value"><?php echo number_format($currencyDetails['rate'], 4); ?></div>
        </div>

        <h2>💱 Available Currencies</h2>
        <?php foreach ($availableCurrencies as $code => $details): ?>
        <div class="currency-card" style="<?php echo ($code === $currentCurrency) ? '' : 'background: #f9fafb; border-color: #e5e7eb;'; ?>">
            <div class="info-grid">
                <div class="label">Code:</div>
                <div class="value"><?php echo htmlspecialchars($code); ?></div>
                
                <div class="label">Name:</div>
                <div class="value"><?php echo htmlspecialchars($details['name']); ?></div>
                
                <div class="label">Symbol:</div>
                <div class="value"><?php echo htmlspecialchars($details['symbol']); ?></div>
                
                <div class="label">Exchange Rate:</div>
                <div class="value"><?php echo number_format($details['rate'], 2); ?></div>
            </div>
            <?php if ($code === $currentCurrency): ?>
            <div style="margin-top: 0.5rem; color: #ff5900; font-weight: 600;">
                ✓ Currently Active
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>

        <h2>💰 Price Conversion Test</h2>
        <div class="warning">
            Sample product price: <strong><?php echo $baseCurrency; ?> <?php echo number_format($testPrice, 0); ?></strong> (stored in database as base currency)
        </div>
        
        <?php foreach ($availableCurrencies as $code => $details): ?>
        <div class="price-test">
            <div class="label">Converted to <?php echo htmlspecialchars($details['name']); ?>:</div>
            <div class="price-large">
                <?php 
                // Convert price using getPrice method
                $convertedPrice = $currencyDetector->getPrice($testPrice, $code);
                echo $currencyDetector->formatPrice($convertedPrice, $code);
                ?>
            </div>
            <small style="color: #6b7280;">
                <?php if ($code === $baseCurrency): ?>
                    Base currency - no conversion needed
                <?php else: ?>
                    Conversion: <?php echo $baseCurrency; ?> <?php echo number_format($testPrice, 0); ?> → <?php echo $code; ?> <?php echo number_format($convertedPrice, 2); ?> (rate: <?php echo number_format($details['rate'], 4); ?>)
                <?php endif; ?>
            </small>
        </div>
        <?php endforeach; ?>

        <h2>🔧 Session Information</h2>
        <div class="info-grid">
            <div class="label">Session Active:</div>
            <div class="value"><?php echo (session_status() === PHP_SESSION_ACTIVE) ? 'Yes' : 'No'; ?></div>
            
            <div class="label">Session Currency:</div>
            <div class="value"><?php echo htmlspecialchars($_SESSION['user_currency'] ?? 'Not Set'); ?></div>
            
            <div class="label">Session Country:</div>
            <div class="value"><?php echo htmlspecialchars($_SESSION['user_country'] ?? 'Not Set'); ?></div>
        </div>

        <h2>🧪 Test Currency Switch</h2>
        <form action="api/currency.php" method="POST" style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <?php foreach ($availableCurrencies as $code => $details): ?>
            <button type="button" class="btn" 
                    onclick="switchCurrency('<?php echo $code; ?>')"
                    style="<?php echo ($code === $currentCurrency) ? 'background: #10b981;' : ''; ?>">
                Switch to <?php echo htmlspecialchars($code); ?>
            </button>
            <?php endforeach; ?>
        </form>

        <div style="margin-top: 2rem; padding: 1rem; background: #f9fafb; border-radius: 8px; font-size: 0.875rem; color: #6b7280;">
            <strong>Note:</strong> This test page verifies that the currency detection system is working correctly.<br>
            • Currency is detected based on your IP address on first visit, then stored in your session.<br>
            • Base currency (<?php echo $baseCurrency; ?>) is set in Admin → Settings and used for all database prices.<br>
            • Exchange rates are configured in settings and applied automatically during conversion.<br>
            • You can manually switch currencies using the buttons above or the header dropdown.
        </div>
    </div>

    <script>
        async function switchCurrency(currency) {
            try {
                const response = await fetch('api/currency.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ currency: currency })
                });

                const data = await response.json();
                
                if (data.success) {
                    alert('Currency switched to ' + currency);
                    window.location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Failed to switch currency'));
                }
            } catch (error) {
                console.error('Currency switch error:', error);
                alert('Error switching currency. Please try again.');
            }
        }
    </script>
</body>
</html>
