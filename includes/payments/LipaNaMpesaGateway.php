<?php

class LipaNaMpesaGateway extends AbstractPaymentGateway implements PaymentGatewayInterface
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = $this->isSandboxMode()
            ? 'https://sandbox.safaricom.co.ke'
            : 'https://api.safaricom.co.ke';
    }

    private function getAccessToken(): string
    {
        $consumerKey = $this->resolveConfig('mpesa_consumer_key', 'MPESA_CONSUMER_KEY');
        $consumerSecret = $this->resolveConfig('mpesa_consumer_secret', 'MPESA_CONSUMER_SECRET');

        $this->ensureConfigured(
            ['consumer_key', 'consumer_secret'],
            [
                'consumer_key' => $consumerKey,
                'consumer_secret' => $consumerSecret,
            ]
        );

        $credentials = base64_encode($consumerKey . ':' . $consumerSecret);
        $headers = [
            'Authorization: Basic ' . $credentials,
        ];

        $response = $this->request(
            'GET',
            $this->baseUrl . '/oauth/v1/generate?grant_type=client_credentials',
            null,
            $headers
        );

        if (empty($response['access_token'])) {
            throw new PaymentGatewayException('Unable to obtain M-Pesa access token.');
        }

        return $response['access_token'];
    }

    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);
        if (strpos($digits, '0') === 0) {
            $digits = '254' . substr($digits, 1);
        }
        if (strpos($digits, '254') !== 0) {
            $digits = '254' . ltrim($digits, '0');
        }
        return $digits;
    }

    public function initiatePayment(array $order, array $customer, array $metadata = []): array
    {
        $shortcode = $this->resolveConfig('mpesa_shortcode', 'MPESA_SHORTCODE');
        $passkey = $this->resolveConfig('mpesa_passkey', 'MPESA_PASSKEY');
        $callbackUrl = $this->resolveConfig('mpesa_callback_url', 'MPESA_CALLBACK_URL', SITE_URL . '/payment-callback/mpesa');

        $this->ensureConfigured(
            ['shortcode', 'passkey'],
            [
                'shortcode' => $shortcode,
                'passkey' => $passkey,
            ]
        );

        $phone = $this->normalizePhone($customer['phone'] ?? '');
        if (strlen($phone) < 10) {
            throw new PaymentGatewayException('Valid Kenyan phone number required for M-Pesa payment.');
        }

        $token = $this->getAccessToken();
        $timestamp = date('YmdHis');
        $password = base64_encode($shortcode . $passkey . $timestamp);

        $payload = [
            'BusinessShortCode' => $shortcode,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'TransactionType' => 'CustomerPayBillOnline',
            'Amount' => (int) round((float) $order['amount']),
            'PartyA' => $phone,
            'PartyB' => $shortcode,
            'PhoneNumber' => $phone,
            'CallBackURL' => $callbackUrl,
            'AccountReference' => substr($order['reference'], 0, 12),
            'TransactionDesc' => $order['description'] ?? 'Order payment',
        ];

        $headers = [
            'Authorization: Bearer ' . $token,
        ];

        $response = $this->request(
            'POST',
            $this->baseUrl . '/mpesa/stkpush/v1/processrequest',
            $payload,
            $headers
        );

        return [
            'success' => true,
            'status' => 'pending',
            'customer_message' => $response['CustomerMessage'] ?? 'Check your phone to complete the payment.',
            'checkout_request_id' => $response['CheckoutRequestID'] ?? null,
            'merchant_request_id' => $response['MerchantRequestID'] ?? null,
        ];
    }
}
