<?php

namespace Tests\Fakes;

use App\Contracts\PaymentGateway;
use RuntimeException;

class FakePaymentGateway implements PaymentGateway
{
    public bool $configured = true;

    public bool $paymentSignatureIsValid = true;

    public bool $webhookSignatureIsValid = true;

    /** @var array<int, array<string, mixed>> */
    public array $createdOrders = [];

    /** @var array<string, mixed> */
    public array $fetchedPayment = [];

    /** @var array<string, mixed> */
    public array $capturedPaymentResponse = [];

    /** @var array<int, array{id: string, amount: int, currency: string}> */
    public array $capturedPayments = [];

    public int $paymentSignatureChecks = 0;

    public int $webhookSignatureChecks = 0;

    public function isConfigured(): bool
    {
        return $this->configured;
    }

    public function createOrder(array $attributes): array
    {
        $this->createdOrders[] = $attributes;

        return [
            'id' => 'order_test_'.count($this->createdOrders),
            'amount' => $attributes['amount'],
            'currency' => $attributes['currency'],
            'status' => 'created',
            'receipt' => $attributes['receipt'],
            'notes' => $attributes['notes'] ?? [],
        ];
    }

    public function verifyPaymentSignature(array $attributes): void
    {
        $this->paymentSignatureChecks++;

        if (! $this->paymentSignatureIsValid) {
            throw new RuntimeException('Invalid payment signature.');
        }
    }

    public function verifyWebhookSignature(string $payload, string $signature, string $secret): void
    {
        $this->webhookSignatureChecks++;

        if (! $this->webhookSignatureIsValid) {
            throw new RuntimeException('Invalid webhook signature.');
        }
    }

    public function fetchPayment(string $paymentId): array
    {
        return array_merge(['id' => $paymentId], $this->fetchedPayment);
    }

    public function capturePayment(string $paymentId, int $amount, string $currency): array
    {
        $this->capturedPayments[] = [
            'id' => $paymentId,
            'amount' => $amount,
            'currency' => $currency,
        ];

        return array_merge([
            'id' => $paymentId,
            'amount' => $amount,
            'currency' => $currency,
            'status' => 'captured',
        ], $this->capturedPaymentResponse);
    }
}
