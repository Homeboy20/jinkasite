<?php
/**
 * Currency Detector - Detects user's currency based on IP geolocation
 */
class CurrencyDetector {
    private static $instance = null;
    private $userCurrency = null;
    private $userCountry = null;

    // Currency configuration
    private $currencyConfig = [];
    private $configLoaded = false;
    private $baseCurrency = 'KES'; // Default base currency

    private function __construct() {
        $this->detectCurrency();
    }

    /**
     * Load currency configuration from database settings (lazy loading)
     */
    private function loadCurrencyConfig() {
        if ($this->configLoaded) {
            return;
        }
        
        // Get base currency and exchange rates from settings
        $base_currency = 'KES';
        $rate_kes = 1;
        $rate_tzs = 18.5;
        $rate_ugx = 30;
        $rate_usd = 0.0077;
        
        // Try to load from database directly
        try {
            $db = Database::getInstance()->getConnection();
            if ($db) {
                // Get base currency
                $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
                if ($stmt) {
                    $key = 'currency';
                    $stmt->bind_param('s', $key);
                    $stmt->execute();
                    $result = $stmt->get_result()->fetch_assoc();
                    if ($result) $base_currency = $result['setting_value'];
                    
                    // Get exchange rates
                    $key = 'exchange_rate_kes';
                    $stmt->bind_param('s', $key);
                    $stmt->execute();
                    $result = $stmt->get_result()->fetch_assoc();
                    if ($result) $rate_kes = (float)$result['setting_value'];
                    
                    $key = 'exchange_rate_tzs';
                    $stmt->bind_param('s', $key);
                    $stmt->execute();
                    $result = $stmt->get_result()->fetch_assoc();
                    if ($result) $rate_tzs = (float)$result['setting_value'];
                    
                    $key = 'exchange_rate_ugx';
                    $stmt->bind_param('s', $key);
                    $stmt->execute();
                    $result = $stmt->get_result()->fetch_assoc();
                    if ($result) $rate_ugx = (float)$result['setting_value'];
                    
                    $key = 'exchange_rate_usd';
                    $stmt->bind_param('s', $key);
                    $stmt->execute();
                    $result = $stmt->get_result()->fetch_assoc();
                    if ($result) $rate_usd = (float)$result['setting_value'];
                }
            }
        } catch (Exception $e) {
            // Silently use defaults if database query fails
        }
        
        $this->baseCurrency = $base_currency;
        
        // Configure currency rates
        $this->currencyConfig = [
            'KE' => ['code' => 'KES', 'symbol' => 'KSh', 'name' => 'Kenya Shillings', 'rate' => $rate_kes],
            'TZ' => ['code' => 'TZS', 'symbol' => 'TSh', 'name' => 'Tanzania Shillings', 'rate' => $rate_tzs],
            'UG' => ['code' => 'UGX', 'symbol' => 'USh', 'name' => 'Uganda Shillings', 'rate' => $rate_ugx],
            'RW' => ['code' => 'RWF', 'symbol' => 'FRw', 'name' => 'Rwanda Francs', 'rate' => 7.5],
            'US' => ['code' => 'USD', 'symbol' => '$', 'name' => 'US Dollars', 'rate' => $rate_usd],
            'default' => ['code' => 'KES', 'symbol' => 'KSh', 'name' => 'Kenya Shillings', 'rate' => 1]
        ];
        
        $this->configLoaded = true;
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Detect user's currency based on IP
     */
    private function detectCurrency() {
        // Ensure config is loaded
        $this->loadCurrencyConfig();
        
        // Check if currency is manually set in session
        if (isset($_SESSION['user_currency'])) {
            $this->userCurrency = $_SESSION['user_currency'];
            return;
        }

        // Get country from IP
        $country = $this->getCountryFromIP();
        $this->userCountry = $country;

        // Set currency based on country
        if (isset($this->currencyConfig[$country])) {
            $this->userCurrency = $this->currencyConfig[$country]['code'];
        } else {
            $this->userCurrency = $this->currencyConfig['default']['code'];
        }

        // Store in session
        $_SESSION['user_currency'] = $this->userCurrency;
        $_SESSION['user_country'] = $country;
    }

    /**
     * Get country code from IP address using multiple free services
     */
    private function getCountryFromIP() {
        $ip = $this->getUserIP();
        
        // Skip detection for local/private IPs
        if ($this->isPrivateIP($ip)) {
            return 'KE'; // Default to Kenya for local development
        }

        // Try multiple free geolocation services
        $country = $this->tryIPAPI($ip);
        if ($country) return $country;

        $country = $this->tryIPGeolocation($ip);
        if ($country) return $country;

        // Default to Kenya if all services fail
        return 'KE';
    }

    /**
     * Get user's IP address
     */
    private function getUserIP() {
        $headers = [
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR'
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                // Handle multiple IPs (proxy chains)
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    /**
     * Check if IP is private/local
     */
    private function isPrivateIP($ip) {
        return !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }

    /**
     * Try ip-api.com service (free, no API key needed)
     */
    private function tryIPAPI($ip) {
        try {
            $url = "http://ip-api.com/json/{$ip}?fields=countryCode";
            $context = stream_context_create([
                'http' => [
                    'timeout' => 2,
                    'ignore_errors' => true
                ]
            ]);
            
            $response = @file_get_contents($url, false, $context);
            if ($response) {
                $data = json_decode($response, true);
                if (isset($data['countryCode'])) {
                    return $data['countryCode'];
                }
            }
        } catch (Exception $e) {
            // Silent fail
        }
        return null;
    }

    /**
     * Try ipapi.co service (free tier available)
     */
    private function tryIPGeolocation($ip) {
        try {
            $url = "https://ipapi.co/{$ip}/country/";
            $context = stream_context_create([
                'http' => [
                    'timeout' => 2,
                    'ignore_errors' => true
                ]
            ]);
            
            $country = @file_get_contents($url, false, $context);
            if ($country && strlen(trim($country)) === 2) {
                return trim($country);
            }
        } catch (Exception $e) {
            // Silent fail
        }
        return null;
    }

    /**
     * Get current user currency code
     */
    public function getCurrency() {
        return $this->userCurrency;
    }

    /**
     * Get current user currency code (alias)
     */
    public function getCurrentCurrency() {
        return $this->userCurrency;
    }

    /**
     * Get currency details
     */
    public function getCurrencyDetails() {
        $this->loadCurrencyConfig();
        $currency = $this->getCurrency();
        $countryCode = $this->mapCurrencyToCountry($currency);
        return $this->currencyConfig[$countryCode] ?? $this->currencyConfig['default'];
    }

    /**
     * Get user's detected country
     */
    public function getCountry() {
        return $this->userCountry;
    }

    /**
     * Get user's detected country code (alias)
     */
    public function getCountryCode() {
        return $this->userCountry;
    }

    /**
     * Get base currency from settings
     */
    public function getBaseCurrency() {
        $this->loadCurrencyConfig();
        return $this->baseCurrency;
    }

    /**
     * Manually set currency (for currency switcher)
     */
    public function setCurrency($currencyCode) {
        $this->loadCurrencyConfig();
        $currencyCode = strtoupper($currencyCode);
        
        // Validate currency code
        $validCurrencies = array_column($this->currencyConfig, 'code');
        if (in_array($currencyCode, $validCurrencies)) {
            $this->userCurrency = $currencyCode;
            $_SESSION['user_currency'] = $currencyCode;
            return true;
        }
        return false;
    }

    /**
     * Get price in user's currency
     * @param float $priceInBaseCurrency Price in base currency (from database)
     * @param string $currency Optional currency code to convert to
     * @return float Converted price
     */
    public function getPrice($priceInBaseCurrency, $currency = null) {
        $this->loadCurrencyConfig();
        $currency = $currency ?? $this->getCurrency();
        
        // Get exchange rates from database
        $db = Database::getInstance()->getConnection();
        $exchange_rates = [
            'KES' => (float)$this->getSettingValue('exchange_rate_kes', '1'),
            'TZS' => (float)$this->getSettingValue('exchange_rate_tzs', '2860'),
            'UGX' => (float)$this->getSettingValue('exchange_rate_ugx', '3900'),
            'USD' => (float)$this->getSettingValue('exchange_rate_usd', '0.0077')
        ];
        
        // Base currency always has rate 1
        // All other rates are relative to base
        // Example: If base=KES and rate_tzs=2860, then 1 KES = 2860 TZS
        // To convert from base to target: price_in_base × exchange_rate[target]
        
        $targetRate = $exchange_rates[$currency] ?? 1;
        return $priceInBaseCurrency * $targetRate;
    }
    
    /**
     * Helper to get setting value from database
     */
    private function getSettingValue($key, $default = '') {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
            $stmt->bind_param('s', $key);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            return $result ? $result['setting_value'] : $default;
        } catch (Exception $e) {
            return $default;
        }
    }

    /**
     * Map currency code to country code
     */
    public function mapCurrencyToCountry($currencyCode) {
        $mapping = [
            'KES' => 'KE',
            'TZS' => 'TZ',
            'UGX' => 'UG',
            'USD' => 'US'
        ];
        return $mapping[$currencyCode] ?? 'KE';
    }

    /**
     * Get currency rate for a country code
     */
    public function getCurrencyRate($countryCode) {
        $this->loadCurrencyConfig();
        $details = $this->currencyConfig[$countryCode] ?? $this->currencyConfig['default'];
        return $details['rate'] ?? 1;
    }

    /**
     * Format price with currency symbol
     * @param float $amount Amount to format
     * @param string $currency Optional currency code
     * @return string Formatted price with symbol
     */
    public function formatPrice($amount, $currency = null) {
        $this->loadCurrencyConfig();
        $currency = $currency ?? $this->getCurrency();
        $countryCode = $this->mapCurrencyToCountry($currency);
        $details = $this->currencyConfig[$countryCode] ?? $this->currencyConfig['default'];
        
        $symbol = $details['symbol'] ?? 'KSh';
        // USD shows 2 decimals, others show no decimals
        $decimals = ($currency === 'USD') ? 2 : 0;
        $formatted = number_format($amount, $decimals);
        
        // USD symbol goes before, others after
        if ($currency === 'USD') {
            return $symbol . $formatted;
        }
        return $symbol . ' ' . $formatted;
    }

    /**
     * Get available currencies for switcher
     */
    public function getAvailableCurrencies() {
        $this->loadCurrencyConfig();
        return [
            'KES' => $this->currencyConfig['KE'],
            'TZS' => $this->currencyConfig['TZ'],
            'UGX' => $this->currencyConfig['UG'],
            'USD' => $this->currencyConfig['US']
        ];
    }
}
