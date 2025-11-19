<?php

class PayPalGateway extends AbstractPaymentGateway implements PaymentGatewayInterface
{
    private string $apiBase;

    public function __construct()
    {
        $this->apiBase = $this->isSandboxMode()
            ? 'https://api-m.sandbox.paypal.com'
            : 'https://api-m.paypal.com';
    }

    private function getAccessToken(): string
    {
        $clientId = $this->resolveConfig('paypal_client_id', 'PAYPAL_CLIENT_ID');
        $clientSecret = $this->resolveConfig('paypal_client_secret', 'PAYPAL_CLIENT_SECRET');

        $this->ensureConfigured(
            ['client_id', 'client_secret'],
            [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
            ]
        );

        $headers = [
            'Authorization: Basic ' . base64_encode($clientId . ':' . $clientSecret),
            'Content-Type: application/x-www-form-urlencoded',
        ];

        $response = $this->request(
            'POST',
            $this->apiBase . '/v1/oauth2/token',
            'grant_type=client_credentials',
            $headers
        );

        if (empty($response['access_token'])) {
            throw new PaymentGatewayException('Unable to authenticate with PayPal.');
        }

        return $response['access_token'];
    }

    public function initiatePayment(array $order, array $customer, array $metadata = []): array
    {
        $accessToken = $this->getAccessToken();
        $returnUrl = $this->resolveConfig('paypal_return_url', 'PAYPAL_RETURN_URL', SITE_URL . '/payment-callback/paypal/success');
        $cancelUrl = $this->resolveConfig('paypal_cancel_url', 'PAYPAL_CANCEL_URL', SITE_URL . '/payment-callback/paypal/cancel');

        $payload = [
            'intent' => 'CAPTURE',
            'purchase_units' => [
                [
                    'reference_id' => $order['reference'],
                    'description' => $order['description'] ?? 'Order payment',
                    'amount' => [
                        'currency_code' => strtoupper($order['currency']),
                        'value' => number_format((float) $order['amount'], 2, '.', ''),
                    ],
                ],
            ],
            'application_context' => [
                'brand_name' => substr(BUSINESS_NAME, 0, 127),
                'return_url' => $returnUrl,
                'cancel_url' => $cancelUrl,
                'landing_page' => 'NO_PREFERENCE',
                'user_action' => 'PAY_NOW',
            ],
        ];

        $headers = [
            'Authorization: Bearer ' . $accessToken,
        ];

        $response = $this->request(
            'POST',
            $this->apiBase . '/v2/checkout/orders',
            $payload,
            $headers
        );

        if (empty($response['links'])) {
            throw new PaymentGatewayException('PayPal did not return any approval links.');
        }

        $approvalUrl = null;
        foreach ($response['links'] as $link) {
            if (($link['rel'] ?? '') === 'approve') {
                $approvalUrl = $link['href'];
                break;
            }
        }

        if (!$approvalUrl) {
            throw new PaymentGatewayException('PayPal approval link missing from response.');
        }

        return [
            'success' => true,
            'redirect_url' => $approvalUrl,
            'reference' => $response['id'] ?? $order['reference'],
            'gateway_reference' => $response['id'] ?? null,
        ];
    }
}
