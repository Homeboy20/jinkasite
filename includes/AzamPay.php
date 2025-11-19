<?php
/**
 * AzamPay Payment Gateway Integration
 * 
 * Official API Documentation: https://developers.azampay.co.tz/
 * Supports: Tigo Pesa, M-Pesa TZ, Airtel Money, Halopesa, and local cards
 * 
 * @author JINKA Plotter
 * @version 1.0
 * @created 2025-11-09
 */

if (!defined('JINKA_ACCESS')) {
    die('Direct access not permitted');
}

class AzamPay {
    private $appName;
    private $clientId;
    private $clientSecret;
    private $apiKey;
    private $accountNumber;
    private $isSandbox;
    private $baseUrl;
    private $accessToken;
    private $tokenExpiry;

    public function __construct() {
        $this->appName = AZAMPAY_APP_NAME;
        $this->clientId = AZAMPAY_CLIENT_ID;
        $this->clientSecret = AZAMPAY_CLIENT_SECRET;
        $this->apiKey = AZAMPAY_API_KEY;
        $this->accountNumber = AZAMPAY_ACCOUNT_NUMBER;
        $this->isSandbox = PAYMENT_USE_SANDBOX;
        
        // Set API base URL
        $this->baseUrl = $this->isSandbox 
            ? 'https://sandbox.azampay.co.tz' 
            : 'https://checkout.azampay.co.tz';
    }

    /**
     * Get OAuth2 Access Token
     * 
     * @return string|false Access token or false on failure
     */
    private function getAccessToken() {
        // Check if token is still valid
        if ($this->accessToken && $this->tokenExpiry && time() < $this->tokenExpiry) {
            return $this->accessToken;
        }

        // Authentication uses a different base URL
        $authUrl = $this->isSandbox
            ? 'https://authenticator-sandbox.azampay.co.tz'
            : 'https://authenticator.azampay.co.tz';
        
        $url = $authUrl . '/AppRegistration/GenerateToken';
        
        $data = [
            'appName' => $this->appName,
            'clientId' => $this->clientId,
            'clientSecret' => $this->clientSecret
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json'
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, !$this->isSandbox);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        // Check for cURL errors
        if ($curlError) {
            Logger::error('AzamPay cURL error during authentication', [
                'error' => $curlError,
                'url' => $url
            ]);
            return false;
        }

        // Check if response is HTML (error page) instead of JSON
        if (stripos($response, '<!DOCTYPE') !== false || stripos($response, '<html') !== false) {
            Logger::error('AzamPay returned HTML instead of JSON', [
                'http_code' => $httpCode,
                'response_preview' => substr($response, 0, 500),
                'url' => $url
            ]);
            return false;
        }

        if ($httpCode !== 200) {
            Logger::error('AzamPay token generation failed', [
                'http_code' => $httpCode,
                'response' => $response,
                'url' => $url
            ]);
            return false;
        }

        $result = json_decode($response, true);

        // Check for JSON decode errors
        if (json_last_error() !== JSON_ERROR_NONE) {
            Logger::error('AzamPay response is not valid JSON', [
                'http_code' => $httpCode,
                'json_error' => json_last_error_msg(),
                'response_preview' => substr($response, 0, 500)
            ]);
            return false;
        }

        if (isset($result['data']['accessToken'])) {
            $this->accessToken = $result['data']['accessToken'];
            // Token valid for 1 hour, set expiry to 55 minutes to be safe
            $this->tokenExpiry = time() + 3300;
            return $this->accessToken;
        }

        return false;
    }

    /**
     * Initiate Mobile Money Payment (MNO Checkout)
     * 
     * @param array $params Payment parameters
     * @return array Response with checkout URL or error
     */
    public function initiateMobilePayment($params) {
        $token = $this->getAccessToken();
        if (!$token) {
            return [
                'success' => false,
                'error' => 'Failed to authenticate with AzamPay'
            ];
        }

        $url = $this->baseUrl . '/azampay/mno/checkout';

        // Prepare payment data
        $paymentData = [
            'accountNumber' => $this->accountNumber,
            'amount' => (string)number_format($params['amount'], 2, '.', ''),
            'currency' => 'TZS',
            'externalId' => $params['reference'],
            'provider' => $params['provider'] ?? 'Tigo', // Tigo, Airtel, Halopesa, Mpesa
            'additionalProperties' => [
                'description' => $params['description'] ?? 'Order Payment',
                'orderId' => $params['order_id'] ?? '',
                'customerName' => $params['customer_name'] ?? '',
                'customerEmail' => $params['customer_email'] ?? '',
                'customerPhone' => $params['customer_phone'] ?? ''
            ]
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($paymentData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $token,
            'X-API-Key: ' . $this->apiKey
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, !$this->isSandbox);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        // Check for cURL errors
        if ($curlError) {
            Logger::error('AzamPay cURL error during payment', [
                'error' => $curlError,
                'reference' => $params['reference']
            ]);
            return [
                'success' => false,
                'error' => 'Connection error: ' . $curlError
            ];
        }

        // Check if response is HTML instead of JSON
        if (stripos($response, '<!DOCTYPE') !== false || stripos($response, '<html') !== false) {
            Logger::error('AzamPay returned HTML instead of JSON', [
                'http_code' => $httpCode,
                'response_preview' => substr($response, 0, 500),
                'reference' => $params['reference']
            ]);
            return [
                'success' => false,
                'error' => 'API returned HTML error page (HTTP ' . $httpCode . ')',
                'http_code' => $httpCode
            ];
        }

        Logger::info('AzamPay MNO checkout initiated', [
            'reference' => $params['reference'],
            'http_code' => $httpCode,
            'response' => $response
        ]);

        if ($httpCode !== 200 && $httpCode !== 201) {
            return [
                'success' => false,
                'error' => 'Failed to initiate payment with AzamPay',
                'http_code' => $httpCode,
                'response' => $response
            ];
        }

        $result = json_decode($response, true);

        // Check for JSON decode errors
        if (json_last_error() !== JSON_ERROR_NONE) {
            Logger::error('AzamPay payment response is not valid JSON', [
                'http_code' => $httpCode,
                'json_error' => json_last_error_msg(),
                'response_preview' => substr($response, 0, 500)
            ]);
            return [
                'success' => false,
                'error' => 'Invalid JSON response: ' . json_last_error_msg()
            ];
        }

        if (isset($result['success']) && $result['success']) {
            return [
                'success' => true,
                'transaction_id' => $result['transactionId'] ?? null,
                'message' => $result['message'] ?? 'Payment initiated successfully'
            ];
        }

        return [
            'success' => false,
            'error' => $result['message'] ?? 'Payment initiation failed',
            'response' => $result
        ];
    }

    /**
     * Initiate Bank Checkout
     * 
     * @param array $params Payment parameters
     * @return array Response with checkout details
     */
    public function initiateBankCheckout($params) {
        $token = $this->getAccessToken();
        if (!$token) {
            return [
                'success' => false,
                'error' => 'Failed to authenticate with AzamPay'
            ];
        }

        $url = $this->baseUrl . '/azampay/bank/checkout';

        $paymentData = [
            'accountNumber' => $this->accountNumber,
            'amount' => (string)number_format($params['amount'], 2, '.', ''),
            'currency' => 'TZS',
            'merchantAccountNumber' => $params['merchant_account'] ?? '',
            'merchantMobileNumber' => $params['merchant_mobile'] ?? '',
            'merchantName' => $params['merchant_name'] ?? BUSINESS_NAME,
            'referenceId' => $params['reference'],
            'remarks' => $params['description'] ?? 'Order Payment'
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($paymentData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $token
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, !$this->isSandbox);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        Logger::info('AzamPay Bank checkout initiated', [
            'reference' => $params['reference'],
            'http_code' => $httpCode
        ]);

        if ($httpCode !== 200 && $httpCode !== 201) {
            return [
                'success' => false,
                'error' => 'Failed to initiate bank checkout',
                'http_code' => $httpCode
            ];
        }

        $result = json_decode($response, true);

        if (isset($result['success']) && $result['success']) {
            return [
                'success' => true,
                'transaction_id' => $result['transactionId'] ?? null,
                'message' => $result['message'] ?? 'Bank checkout initiated'
            ];
        }

        return [
            'success' => false,
            'error' => $result['message'] ?? 'Bank checkout failed',
            'response' => $result
        ];
    }

    /**
     * Check Payment Status / Name Lookup
     * 
     * @param string $reference Transaction reference
     * @param string $provider Payment provider (Tigo, Airtel, Halopesa, Mpesa)
     * @return array Payment status
     */
    public function checkPaymentStatus($reference, $provider = 'Tigo') {
        $token = $this->getAccessToken();
        if (!$token) {
            return [
                'success' => false,
                'error' => 'Failed to authenticate with AzamPay'
            ];
        }

        $url = $this->baseUrl . '/azampay/mno/namelookup';

        $data = [
            'accountNumber' => $this->accountNumber,
            'bankName' => $provider,
            'transactionReference' => $reference
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $token
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, !$this->isSandbox);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            return [
                'success' => false,
                'error' => 'Failed to check payment status',
                'http_code' => $httpCode
            ];
        }

        $result = json_decode($response, true);

        return [
            'success' => isset($result['success']) && $result['success'],
            'data' => $result
        ];
    }

    /**
     * Process Callback/IPN from AzamPay
     * 
     * @param array $callbackData Callback data from AzamPay
     * @return array Processed callback data
     */
    public function processCallback($callbackData) {
        Logger::info('AzamPay callback received', $callbackData);

        // Expected callback structure:
        // {
        //   "transactionId": "string",
        //   "transactionReference": "string",
        //   "status": "success" | "failed" | "pending",
        //   "amount": "number",
        //   "currency": "TZS",
        //   "provider": "Tigo" | "Airtel" | etc,
        //   "paymentDate": "timestamp"
        // }

        return [
            'transaction_id' => $callbackData['transactionId'] ?? null,
            'reference' => $callbackData['transactionReference'] ?? null,
            'status' => $callbackData['status'] ?? 'unknown',
            'amount' => $callbackData['amount'] ?? 0,
            'currency' => $callbackData['currency'] ?? 'TZS',
            'provider' => $callbackData['provider'] ?? 'Unknown',
            'payment_date' => $callbackData['paymentDate'] ?? null,
            'raw_data' => $callbackData
        ];
    }

    /**
     * Initiate Disbursement (Payout to customer)
     * 
     * @param array $params Disbursement parameters
     * @return array Response
     */
    public function initiateDisbursement($params) {
        $token = $this->getAccessToken();
        if (!$token) {
            return [
                'success' => false,
                'error' => 'Failed to authenticate with AzamPay'
            ];
        }

        $url = $this->baseUrl . '/azampay/mno/disburse';

        $data = [
            'source' => [
                'countryCode' => 'TZ',
                'fullName' => BUSINESS_NAME,
                'bankName' => 'Azampay',
                'accountNumber' => $this->accountNumber,
                'currency' => 'TZS'
            ],
            'destination' => [
                'countryCode' => 'TZ',
                'fullName' => $params['recipient_name'],
                'bankName' => $params['provider'] ?? 'Tigo', // Tigo, Airtel, Halopesa, Mpesa
                'accountNumber' => $params['recipient_phone'],
                'currency' => 'TZS'
            ],
            'transferDetails' => [
                'type' => 'Normal',
                'amount' => (string)number_format($params['amount'], 2, '.', ''),
                'date' => date('Y-m-d')
            ],
            'externalReferenceId' => $params['reference'],
            'remarks' => $params['remarks'] ?? 'Payout'
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $token
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, !$this->isSandbox);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        Logger::info('AzamPay disbursement initiated', [
            'reference' => $params['reference'],
            'http_code' => $httpCode
        ]);

        if ($httpCode !== 200 && $httpCode !== 201) {
            return [
                'success' => false,
                'error' => 'Failed to initiate disbursement',
                'http_code' => $httpCode
            ];
        }

        $result = json_decode($response, true);

        return [
            'success' => isset($result['success']) && $result['success'],
            'data' => $result
        ];
    }

    /**
     * Get Transaction Status by Reference
     * 
     * @param string $reference Transaction reference
     * @return array Transaction details
     */
    public function getTransactionStatus($reference) {
        $token = $this->getAccessToken();
        if (!$token) {
            return [
                'success' => false,
                'error' => 'Failed to authenticate with AzamPay'
            ];
        }

        // AzamPay doesn't have a direct status check endpoint
        // You need to rely on callbacks or use the name lookup as a workaround
        return $this->checkPaymentStatus($reference);
    }

    /**
     * Format phone number for AzamPay (Tanzanian format)
     * 
     * @param string $phone Phone number
     * @return string Formatted phone number
     */
    public static function formatPhoneNumber($phone) {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // If starts with 255, keep as is
        if (substr($phone, 0, 3) === '255') {
            return $phone;
        }

        // If starts with 0, replace with 255
        if (substr($phone, 0, 1) === '0') {
            return '255' . substr($phone, 1);
        }

        // If starts with country code without 255, add it
        if (strlen($phone) === 9) {
            return '255' . $phone;
        }

        return $phone;
    }

    /**
     * Validate AzamPay configuration
     * 
     * @return bool True if configured correctly
     */
    public function isConfigured() {
        return !empty($this->appName) 
            && !empty($this->clientId) 
            && !empty($this->clientSecret)
            && !empty($this->accountNumber);
    }

    /**
     * Get supported mobile money providers
     * 
     * @return array List of providers
     */
    public static function getSupportedProviders() {
        return [
            'Tigo' => 'Tigo Pesa',
            'Airtel' => 'Airtel Money',
            'Halopesa' => 'HaloPesa',
            'Mpesa' => 'M-Pesa Tanzania'
        ];
    }
}
