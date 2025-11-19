<?php

class AzamPayGateway extends AbstractPaymentGateway implements PaymentGatewayInterface
{
    private string $baseUrl;
    private $accessToken = null;
    private $tokenExpiry = null;

    public function __construct()
    {
        $this->baseUrl = $this->isSandboxMode()
            ? 'https://sandbox.azampay.co.tz'
            : 'https://checkout.azampay.co.tz';
    }

    private function authenticate(): string
    {
        // Check if token is still valid
        if ($this->accessToken && $this->tokenExpiry && time() < $this->tokenExpiry) {
            return $this->accessToken;
        }

        $appName = $this->resolveConfig('azampay_app_name', 'AZAMPAY_APP_NAME');
        $clientId = $this->resolveConfig('azampay_client_id', 'AZAMPAY_CLIENT_ID');
        $clientSecret = $this->resolveConfig('azampay_client_secret', 'AZAMPAY_CLIENT_SECRET');

        $this->ensureConfigured(
            ['app_name', 'client_id', 'client_secret'],
            [
                'app_name' => $appName,
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
            ]
        );

        $payload = [
            'appName' => $appName,
            'clientId' => $clientId,
            'clientSecret' => $clientSecret,
        ];

        // Authentication uses different base URL
        $authUrl = $this->isSandboxMode()
            ? 'https://authenticator-sandbox.azampay.co.tz'
            : 'https://authenticator.azampay.co.tz';

        $response = $this->request('POST', $authUrl . '/AppRegistration/GenerateToken', $payload);

        if (empty($response['data']['accessToken'])) {
            throw new PaymentGatewayException('Unable to authenticate with AzamPay: ' . ($response['message'] ?? 'Unknown error'));
        }

        $this->accessToken = $response['data']['accessToken'];
        // Token valid for 1 hour, set expiry to 55 minutes to be safe
        $this->tokenExpiry = time() + 3300;

        return $this->accessToken;
    }

    public function initiatePayment(array $order, array $customer, array $metadata = []): array
    {
        $accountNumber = $this->resolveConfig('azampay_account_number', 'AZAMPAY_ACCOUNT_NUMBER');
        $callbackUrl = $this->resolveConfig('azampay_callback_url', 'AZAMPAY_CALLBACK_URL', SITE_URL . '/payment-callback/azampay');

        $this->ensureConfigured(
            ['account_number'],
            ['account_number' => $accountNumber]
        );

        $token = $this->authenticate();

        // Determine provider from metadata (default to Tigo)
        $provider = $metadata['provider'] ?? 'Tigo';
        
        // Format phone number for Tanzania
        $phone = $customer['phone'] ?? '';
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (substr($phone, 0, 3) === '255') {
            // Already has country code
        } elseif (substr($phone, 0, 1) === '0') {
            $phone = '255' . substr($phone, 1);
        } elseif (strlen($phone) === 9) {
            $phone = '255' . $phone;
        }

        // Prepare payload for MNO checkout
        $payload = [
            'accountNumber' => $accountNumber,
            'amount' => number_format((float) $order['amount'], 2, '.', ''),
            'currency' => 'TZS', // AzamPay primarily works with TZS
            'externalId' => $order['reference'],
            'provider' => $provider, // Tigo, Airtel, Halopesa, Mpesa
            'additionalProperties' => [
                'description' => $order['description'] ?? 'Order Payment',
                'customerName' => $customer['name'] ?? 'Customer',
                'customerEmail' => $customer['email'] ?? '',
                'customerPhone' => $phone
            ]
        ];

        $headers = [
            'Authorization: Bearer ' . $token,
            'X-API-Key: ' . $this->resolveConfig('azampay_api_key', 'AZAMPAY_API_KEY', ''),
        ];

        // Use MNO checkout endpoint for mobile money
        $response = $this->request(
            'POST',
            $this->baseUrl . '/azampay/mno/checkout',
            $payload,
            $headers
        );

        // Log the response for debugging
        error_log('AzamPay Response: ' . json_encode($response));

        // Check for success
        if (isset($response['success']) && $response['success']) {
            return [
                'success' => true,
                'transaction_id' => $response['transactionId'] ?? null,
                'reference' => $order['reference'],
                'message' => $response['message'] ?? 'Payment initiated successfully',
                'instructions' => [
                    'title' => 'Complete Payment on Your Phone',
                    'provider' => $provider,
                    'amount' => number_format((float) $order['amount'], 2) . ' TZS',
                    'steps' => [
                        "1. Check your phone for a payment prompt from {$provider}",
                        "2. Enter your PIN to authorize the payment",
                        "3. You will receive a confirmation message",
                        "4. Return here to see your order confirmation"
                    ]
                ],
                // For web implementation, redirect to order success with pending status
                'redirect_url' => SITE_URL . '/order-success.php?order_id=' . ($metadata['order_id'] ?? '') . '&tx_ref=' . urlencode($order['reference']) . '&status=pending'
            ];
        }

        throw new PaymentGatewayException('AzamPay payment failed: ' . ($response['message'] ?? 'Unknown error'));
    }

    public function verifyPayment(string $reference): array
    {
        $token = $this->authenticate();
        $accountNumber = $this->resolveConfig('azampay_account_number', 'AZAMPAY_ACCOUNT_NUMBER');

        $payload = [
            'accountNumber' => $accountNumber,
            'bankName' => 'Tigo', // Provider used for the transaction
            'transactionReference' => $reference
        ];

        $headers = [
            'Authorization: Bearer ' . $token,
            'X-API-Key: ' . $this->resolveConfig('azampay_api_key', 'AZAMPAY_API_KEY', ''),
        ];

        $response = $this->request(
            'POST',
            $this->baseUrl . '/azampay/mno/namelookup',
            $payload,
            $headers
        );

        return [
            'success' => isset($response['success']) && $response['success'],
            'data' => $response
        ];
    }
}
