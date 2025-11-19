<?php

interface PaymentGatewayInterface
{
    /**
     * Initiates a payment and returns gateway response data.
     *
     * @param array $order    Order data including amount, currency, and reference.
     * @param array $customer Customer data including name, email, phone.
     * @param array $metadata Additional metadata such as geolocation details.
     *
     * @return array
     */
    public function initiatePayment(array $order, array $customer, array $metadata = []): array;
}
