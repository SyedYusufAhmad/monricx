<?php

namespace App\Services;

use App\Contracts\PaymentGateway;
use Razorpay\Api\Api;
use RuntimeException;

class RazorpayGateway implements PaymentGateway
{
    private ?Api $api = null;

    public function isConfigured(): bool
    {
        $mode = config('services.razorpay.mode');
        $keyId = config('services.razorpay.key_id');
        $expectedPrefix = match ($mode) {
            'test' => 'rzp_test_',
            'live' => 'rzp_live_',
            default => null,
        };

        return $expectedPrefix !== null
            && is_string($keyId)
            && str_starts_with($keyId, $expectedPrefix)
            && filled(config('services.razorpay.key_secret'));
    }

    public function createOrder(array $attributes): array
    {
        return $this->api()->order->create($attributes)->toArray();
    }

    public function verifyPaymentSignature(array $attributes): void
    {
        $this->api()->utility->verifyPaymentSignature($attributes);
    }

    public function verifyWebhookSignature(string $payload, string $signature, string $secret): void
    {
        $this->api()->utility->verifyWebhookSignature($payload, $signature, $secret);
    }

    public function fetchPayment(string $paymentId): array
    {
        return $this->api()->payment->fetch($paymentId)->toArray();
    }

    public function capturePayment(string $paymentId, int $amount, string $currency): array
    {
        return $this->api()->payment->fetch($paymentId)->capture([
            'amount' => $amount,
            'currency' => $currency,
        ])->toArray();
    }

    private function api(): Api
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Razorpay is not configured.');
        }

        return $this->api ??= new Api(
            config('services.razorpay.key_id'),
            config('services.razorpay.key_secret')
        );
    }
}
