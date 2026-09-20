<?php

namespace App\Contracts;

interface PaymentGateway
{
    public function isConfigured(): bool;

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public function createOrder(array $attributes): array;

    /**
     * @param  array<string, string>  $attributes
     */
    public function verifyPaymentSignature(array $attributes): void;

    public function verifyWebhookSignature(string $payload, string $signature, string $secret): void;

    /**
     * @return array<string, mixed>
     */
    public function fetchPayment(string $paymentId): array;

    /**
     * @return array<string, mixed>
     */
    public function capturePayment(string $paymentId, int $amount, string $currency): array;
}
