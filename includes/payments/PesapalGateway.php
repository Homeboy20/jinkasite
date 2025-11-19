<?php

class PesapalGateway extends AbstractPaymentGateway implements PaymentGatewayInterface
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = $this->isSandboxMode()
            ? 'https://cybqa.pesapal.com/pesapalv3'
            : 'https://pay.pesapal.com/v3';
    }

    private function authenticate(): string
    {
        $consumerKey = $this->resolveConfig('pesapal_consumer_key', 'PESAPAL_CONSUMER_KEY');
        $consumerSecret = $this->resolveConfig('pesapal_consumer_secret', 'PESAPAL_CONSUMER_SECRET');

        $this->ensureConfigured(
            ['consumer_key', 'consumer_secret'],
            [
                'consumer_key' => $consumerKey,
                'consumer_secret' => $consumerSecret,
            ]
        );

        $payload = [
            'consumer_key' => $consumerKey,
            'consumer_secret' => $consumerSecret,
        ];

        $response = $this->request('POST', $this->baseUrl . '/api/Auth/RequestToken', $payload);

        if (empty($response['token'])) {
            throw new PaymentGatewayException('Unable to authenticate with Pesapal.');
        }

        return $response['token'];
    }

    public function initiatePayment(array $order, array $customer, array $metadata = []): array
    {
        $token = $this->authenticate();
        $callbackUrl = $this->resolveConfig('pesapal_callback_url', 'PESAPAL_CALLBACK_URL', SITE_URL . '/payment-callback/pesapal');
        $ipnId = $this->resolveConfig('pesapal_ipn_id', 'PESAPAL_IPN_ID');
        $names = explode(' ', trim($customer['name'] ?? ''), 2);
        $firstName = $names[0] ?? 'Customer';
        $lastName = $names[1] ?? $firstName;

        $payload = [
            'id' => $order['reference'],
            'currency' => $order['currency'],
            'amount' => number_format((float) $order['amount'], 2, '.', ''),
            'description' => $order['description'] ?? 'Order payment',
            'callback_url' => $callbackUrl,
            'billing_address' => [
                'email_address' => $customer['email'] ?? '',
                'phone_number' => $customer['phone'] ?? '',
                'country_code' => strtoupper($metadata['country_code'] ?? ''),
                'first_name' => $firstName,
                'last_name' => $lastName,
            ],
        ];

        if (!empty($ipnId)) {
            $payload['notification_id'] = $ipnId;
        }

        $headers = [
            'Authorization: Bearer ' . $token,
        ];

        $response = $this->request(
            'POST',
            $this->baseUrl . '/api/Transactions/SubmitOrderRequest',
            $payload,
            $headers
        );

        if (empty($response['redirect_url'])) {
            throw new PaymentGatewayException('Pesapal did not return a redirect URL.');
        }

        return [
            'success' => true,
            'redirect_url' => $response['redirect_url'],
            'reference' => $order['reference'],
            'gateway_reference' => $response['order_tracking_id'] ?? null,
        ];
    }
}
