<?php

class FlutterwaveGateway extends AbstractPaymentGateway implements PaymentGatewayInterface
{
    private string $baseUrl = 'https://api.flutterwave.com/v3';

    public function initiatePayment(array $order, array $customer, array $metadata = []): array
    {
        $secretKey = $this->resolveConfig('flutterwave_secret_key', 'FLUTTERWAVE_SECRET_KEY');
        $redirectUrl = $this->resolveConfig('flutterwave_redirect_url', 'FLUTTERWAVE_REDIRECT_URL', SITE_URL . '/payment-callback/flutterwave');

        $this->ensureConfigured(
            ['secret_key'],
            ['secret_key' => $secretKey]
        );

        // Check if this is an inline MNO payment
        if (!empty($metadata['inline_payment']) && !empty($metadata['payment_method'])) {
            return $this->initiateInlineMnoPayment($order, $customer, $metadata, $secretKey);
        }

        // Standard hosted payment page flow
        $payload = [
            'tx_ref' => $order['reference'],
            'amount' => number_format((float) $order['amount'], 2, '.', ''),
            'currency' => $order['currency'],
            'redirect_url' => $redirectUrl,
            'customer' => [
                'email' => $customer['email'] ?? '',
                'phone_number' => $customer['phone'] ?? '',
                'name' => $customer['name'] ?? 'Customer',
            ],
            'meta' => array_merge(
                [
                    'order_reference' => $order['reference'],
                    'customer_country' => strtoupper($metadata['country_code'] ?? ''),
                ],
                $metadata['meta'] ?? []
            ),
            'customizations' => [
                'title' => BUSINESS_NAME,
                'description' => $order['description'] ?? 'Order payment',
            ],
        ];

        if (!empty($metadata['payment_options'])) {
            $payload['payment_options'] = $metadata['payment_options'];
        }

        $headers = [
            'Authorization: Bearer ' . $secretKey,
        ];

        $response = $this->request('POST', $this->baseUrl . '/payments', $payload, $headers);

        if (empty($response['data']['link'])) {
            throw new PaymentGatewayException('Flutterwave did not return a payment link.');
        }

        return [
            'success' => true,
            'redirect_url' => $response['data']['link'],
            'reference' => $order['reference'],
        ];
    }

    /**
     * Initiate inline mobile money payment (M-Pesa Kenya, Tanzania mobile wallets)
     */
    private function initiateInlineMnoPayment(array $order, array $customer, array $metadata, string $secretKey): array
    {
        $paymentMethod = $metadata['payment_method'] ?? '';
        $phoneNumber = $metadata['phone_number'] ?? '';
        $network = $metadata['network'] ?? '';

        if (empty($phoneNumber)) {
            throw new PaymentGatewayException('Phone number is required for mobile money payment.');
        }

        // Format phone number
        $phoneNumber = $this->formatPhoneNumber($phoneNumber, $order['currency']);

        $payload = [
            'tx_ref' => $order['reference'],
            'amount' => number_format((float) $order['amount'], 2, '.', ''),
            'currency' => $order['currency'],
            'email' => $customer['email'] ?? '',
            'phone_number' => $phoneNumber,
            'fullname' => $customer['name'] ?? 'Customer',
        ];

        // Set the appropriate payment type based on payment method
        if ($paymentMethod === 'mpesa') {
            // Kenya M-Pesa
            $payload['type'] = 'mpesa';
            $endpoint = '/charges?type=mpesa';
        } elseif ($paymentMethod === 'mobilemoneytz') {
            // Tanzania mobile money
            $payload['type'] = 'mobile_money_tanzania';
            $payload['network'] = $network; // tigo, airtel, mpesa, halopesa
            $endpoint = '/charges?type=mobile_money_tanzania';
        } else {
            throw new PaymentGatewayException('Unsupported mobile money payment method.');
        }

        $headers = [
            'Authorization: Bearer ' . $secretKey,
        ];

        $response = $this->request('POST', $this->baseUrl . $endpoint, $payload, $headers);

        // Check response status
        if (empty($response['status']) || $response['status'] !== 'success') {
            $message = $response['message'] ?? 'Failed to initiate mobile money payment.';
            throw new PaymentGatewayException($message);
        }

        return [
            'success' => true,
            'customer_message' => 'Payment request sent! Please check your phone and enter your PIN to complete the payment.',
            'reference' => $order['reference'],
            'transaction_id' => $response['data']['id'] ?? null,
        ];
    }

    /**
     * Format phone number for mobile money payments
     */
    private function formatPhoneNumber(string $phone, string $currency): string
    {
        // Remove any non-digit characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Format based on currency/country
        if ($currency === 'KES') {
            // Kenya - should be 254XXXXXXXXX
            if (strlen($phone) === 10 && substr($phone, 0, 1) === '0') {
                return '254' . substr($phone, 1);
            } elseif (strlen($phone) === 9) {
                return '254' . $phone;
            } elseif (substr($phone, 0, 3) !== '254') {
                return '254' . $phone;
            }
        } elseif ($currency === 'TZS') {
            // Tanzania - should be 255XXXXXXXXX
            if (strlen($phone) === 10 && substr($phone, 0, 1) === '0') {
                return '255' . substr($phone, 1);
            } elseif (strlen($phone) === 9) {
                return '255' . $phone;
            } elseif (substr($phone, 0, 3) !== '255') {
                return '255' . $phone;
            }
        }

        return $phone;
    }
}
