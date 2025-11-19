<?php

class StripeGateway extends AbstractPaymentGateway implements PaymentGatewayInterface
{
    private string $apiBase = 'https://api.stripe.com/v1';

    public function initiatePayment(array $order, array $customer, array $metadata = []): array
    {
        $secretKey = $this->resolveConfig('stripe_secret_key', 'STRIPE_SECRET_KEY');
        $successUrl = $this->resolveConfig('stripe_success_url', 'STRIPE_SUCCESS_URL', SITE_URL . '/payment-callback/stripe/success');
        $cancelUrl = $this->resolveConfig('stripe_cancel_url', 'STRIPE_CANCEL_URL', SITE_URL . '/payment-callback/stripe/cancel');
        $description = $order['description'] ?? (BUSINESS_NAME . ' Order');

        $this->ensureConfigured(
            ['secret_key', 'success_url', 'cancel_url'],
            [
                'secret_key' => $secretKey,
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
            ]
        );

        $amount = (int) round(((float) $order['amount']) * 100);
        if ($amount <= 0) {
            throw new PaymentGatewayException('Stripe requires a positive amount to initiate payment.');
        }

        $body = [
            'mode' => 'payment',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'customer_email' => $customer['email'] ?? '',
            'line_items[0][quantity]' => 1,
            'line_items[0][price_data][currency]' => strtolower($order['currency']),
            'line_items[0][price_data][unit_amount]' => $amount,
            'line_items[0][price_data][product_data][name]' => substr($description, 0, 127),
            'metadata[order_reference]' => $order['reference'],
            'metadata[customer_name]' => $customer['name'] ?? '',
            'metadata[customer_phone]' => $customer['phone'] ?? '',
            'automatic_tax[enabled]' => 'false',
            'phone_number_collection[enabled]' => 'true',
        ];

        if (!empty($metadata['meta']['notes'])) {
            $body['metadata[notes]'] = substr((string) $metadata['meta']['notes'], 0, 250);
        }

        $body['payment_method_types[]'] = 'card';

        $payload = http_build_query($body);

        $headers = [
            'Authorization: Bearer ' . $secretKey,
            'Content-Type: application/x-www-form-urlencoded',
        ];

        $response = $this->request(
            'POST',
            $this->apiBase . '/checkout/sessions',
            $payload,
            $headers
        );

        if (empty($response['url'])) {
            throw new PaymentGatewayException('Stripe did not return a checkout URL.');
        }

        return [
            'success' => true,
            'redirect_url' => $response['url'],
            'reference' => $order['reference'],
            'gateway_reference' => $response['id'] ?? null,
        ];
    }
}
