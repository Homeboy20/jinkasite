<?php

require_once __DIR__ . '/PaymentGatewayInterface.php';
require_once __DIR__ . '/PaymentGatewayException.php';
require_once __DIR__ . '/AbstractPaymentGateway.php';
require_once __DIR__ . '/PesapalGateway.php';
require_once __DIR__ . '/FlutterwaveGateway.php';
require_once __DIR__ . '/AzamPayGateway.php';
require_once __DIR__ . '/LipaNaMpesaGateway.php';
require_once __DIR__ . '/PayPalGateway.php';
require_once __DIR__ . '/StripeGateway.php';

class PaymentGatewayManager
{
    public static function initiate(string $gateway, array $order, array $customer, array $metadata = []): array
    {
        $gateway = strtolower($gateway);

        switch ($gateway) {
            case 'pesapal':
                $instance = new PesapalGateway();
                break;
            case 'flutterwave':
                $instance = new FlutterwaveGateway();
                break;
            case 'flutterwave-inline':
                $instance = new FlutterwaveGateway();
                $metadata['inline_payment'] = true;
                break;
            case 'azampay':
                $instance = new AzamPayGateway();
                break;
            case 'mpesa':
            case 'lipa_na_mpesa':
                $instance = new LipaNaMpesaGateway();
                break;
            case 'paypal':
                $instance = new PayPalGateway();
                break;
            case 'stripe':
                $instance = new StripeGateway();
                break;
            default:
                throw new PaymentGatewayException('Unsupported payment gateway: ' . $gateway);
        }

        return $instance->initiatePayment($order, $customer, $metadata);
    }
}
