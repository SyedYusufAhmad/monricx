<?php

namespace App\Services;

use App\Contracts\PaymentGateway;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockReservation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class OrderPaymentService
{
    public function __construct(
        private readonly PaymentGateway $gateway,
        private readonly StockReservationService $reservations,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function initiate(Order $order): array
    {
        if (! $this->gateway->isConfigured()) {
            throw ValidationException::withMessages([
                'payment' => 'Secure payment is temporarily unavailable.',
            ]);
        }

        $this->reservations->reserve($order);
        $order->refresh();

        $purpose = $order->payment_method === 'cash_on_delivery'
            ? $this->codOnlinePaymentPurpose($order)
            : 'order_total';
        $amountPaise = $order->online_payable_paise;
        $payment = Payment::query()
            ->where('order_id', $order->id)
            ->where('purpose', $purpose)
            ->where('amount_paise', $amountPaise)
            ->where('currency', $order->currency)
            ->whereNotNull('provider_order_id')
            ->whereIn('status', ['created', 'authorized', 'failed'])
            ->latest('id')
            ->first();

        if (! $payment) {
            Payment::query()
                ->where('order_id', $order->id)
                ->whereIn('status', ['initializing', 'created', 'authorized', 'failed'])
                ->update(['status' => 'superseded']);

            $payment = Payment::query()->create([
                'order_id' => $order->id,
                'provider' => 'razorpay',
                'purpose' => $purpose,
                'status' => 'initializing',
                'amount_paise' => $amountPaise,
                'currency' => $order->currency,
            ]);

            try {
                $providerOrder = $this->gateway->createOrder([
                    'amount' => $amountPaise,
                    'currency' => $order->currency,
                    'receipt' => $order->order_number.'-'.$payment->id,
                    'notes' => [
                        'order_number' => $order->order_number,
                        'payment_purpose' => $purpose,
                        'environment' => config('services.razorpay.mode'),
                    ],
                ]);

                $this->validateProviderOrder($providerOrder, $payment);

                $payment->forceFill([
                    'provider_order_id' => $providerOrder['id'],
                    'status' => 'created',
                    'provider_payload' => $providerOrder,
                ])->save();
            } catch (Throwable $exception) {
                $payment->forceFill([
                    'status' => 'failed',
                    'failed_at' => now(),
                    'provider_payload' => ['error' => $exception->getMessage()],
                ])->save();
                $this->reservations->release($order);
                $order->forceFill(['status' => 'draft', 'payment_status' => 'pending'])->save();

                Log::error('Razorpay order creation failed.', [
                    'order_id' => $order->id,
                    'payment_id' => $payment->id,
                    'exception' => $exception,
                ]);

                throw ValidationException::withMessages([
                    'payment' => 'Secure payment is temporarily unavailable.',
                ]);
            }
        }

        return $this->checkoutPayload($order, $payment);
    }

    /**
     * @param  array{razorpay_payment_id: string, razorpay_order_id: string, razorpay_signature: string}  $attributes
     */
    public function verifyBrowserPayment(Order $order, array $attributes): Order
    {
        $payment = Payment::query()
            ->where('order_id', $order->id)
            ->where('provider_order_id', $attributes['razorpay_order_id'])
            ->first();

        if (! $payment || $payment->provider_order_id !== $attributes['razorpay_order_id']) {
            throw ValidationException::withMessages([
                'payment' => 'Payment verification failed.',
            ]);
        }

        try {
            $this->gateway->verifyPaymentSignature([
                'razorpay_order_id' => $payment->provider_order_id,
                'razorpay_payment_id' => $attributes['razorpay_payment_id'],
                'razorpay_signature' => $attributes['razorpay_signature'],
            ]);

            $providerPayment = $this->gateway->fetchPayment($attributes['razorpay_payment_id']);
            $this->processProviderPayment($payment, $providerPayment, $attributes['razorpay_signature']);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            Log::warning('Razorpay browser payment verification failed.', [
                'order_id' => $order->id,
                'payment_id' => $payment->id,
                'exception' => $exception,
            ]);

            throw ValidationException::withMessages([
                'payment' => 'Payment verification failed.',
            ]);
        }

        return $order->refresh();
    }

    /**
     * @param  array<string, mixed>  $providerPayment
     */
    public function processWebhookPayment(array $providerPayment): void
    {
        $providerOrderId = $providerPayment['order_id'] ?? null;

        if (! is_string($providerOrderId) || $providerOrderId === '') {
            throw new \DomainException('Webhook payment does not include an order id.');
        }

        $payment = Payment::query()->where('provider_order_id', $providerOrderId)->first();

        if (! $payment) {
            throw new \DomainException('Webhook payment does not match a local order.');
        }

        $this->processProviderPayment($payment, $providerPayment);
    }

    /**
     * @return array<string, mixed>
     */
    private function checkoutPayload(Order $order, Payment $payment): array
    {
        return [
            'key' => config('services.razorpay.key_id'),
            'amount' => $payment->amount_paise,
            'currency' => $payment->currency,
            'name' => 'MONRICX',
            'description' => in_array($payment->purpose, ['cod_fee', 'shipping_fee'], true)
                ? ($payment->purpose === 'shipping_fee' ? 'Standard shipping' : 'Cash on delivery charge')
                : 'Order '.$order->order_number,
            'order_id' => $payment->provider_order_id,
            'prefill' => [
                'name' => $order->customer_name,
                'email' => $order->customer_email,
                'contact' => $order->customer_phone,
            ],
            'notes' => [
                'order_number' => $order->order_number,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $providerOrder
     */
    private function validateProviderOrder(array $providerOrder, Payment $payment): void
    {
        if (
            ! isset($providerOrder['id'], $providerOrder['amount'], $providerOrder['currency'])
            || ! is_string($providerOrder['id'])
            || (int) $providerOrder['amount'] !== $payment->amount_paise
            || $providerOrder['currency'] !== $payment->currency
        ) {
            throw new \UnexpectedValueException('Razorpay returned an invalid order.');
        }
    }

    /**
     * @param  array<string, mixed>  $providerPayment
     */
    private function processProviderPayment(
        Payment $payment,
        array $providerPayment,
        ?string $signature = null
    ): void {
        $this->validateProviderPayment($providerPayment, $payment);

        match ($providerPayment['status']) {
            'captured' => $this->capture($payment, $providerPayment, $signature),
            'authorized' => $this->captureAuthorizedPayment($payment, $providerPayment, $signature),
            'failed' => $this->fail($payment, $providerPayment),
            default => throw new \UnexpectedValueException('Razorpay payment is not in a verifiable state.'),
        };
    }

    /**
     * @param  array<string, mixed>  $providerPayment
     */
    private function captureAuthorizedPayment(
        Payment $payment,
        array $providerPayment,
        ?string $signature
    ): void {
        $this->authorize($payment, $providerPayment, $signature);
        $payment->refresh();

        // Never collect a late or superseded authorization automatically.
        if ($payment->status !== 'authorized') {
            return;
        }

        try {
            $capturedPayment = $this->gateway->capturePayment(
                $providerPayment['id'],
                $payment->amount_paise,
                $payment->currency,
            );
        } catch (Throwable $captureException) {
            // A concurrent webhook/browser callback or a network timeout can mean
            // the capture succeeded even though this request received an error.
            $capturedPayment = $this->gateway->fetchPayment($providerPayment['id']);

            if (($capturedPayment['status'] ?? null) !== 'captured') {
                throw $captureException;
            }
        }

        $this->validateProviderPayment($capturedPayment, $payment);

        if (($capturedPayment['status'] ?? null) !== 'captured') {
            throw new \UnexpectedValueException('Razorpay did not capture the authorized payment.');
        }

        $this->capture($payment, $capturedPayment, $signature);
    }

    /**
     * @param  array<string, mixed>  $providerPayment
     */
    private function validateProviderPayment(array $providerPayment, Payment $payment): void
    {
        if (
            ! isset(
                $providerPayment['id'],
                $providerPayment['order_id'],
                $providerPayment['amount'],
                $providerPayment['currency'],
                $providerPayment['status']
            )
            || $providerPayment['order_id'] !== $payment->provider_order_id
            || (int) $providerPayment['amount'] !== $payment->amount_paise
            || $providerPayment['currency'] !== $payment->currency
        ) {
            throw new \UnexpectedValueException('Razorpay returned invalid payment details.');
        }
    }

    /**
     * @param  array<string, mixed>  $providerPayment
     */
    private function authorize(Payment $payment, array $providerPayment, ?string $signature): void
    {
        DB::transaction(function () use ($payment, $providerPayment, $signature): void {
            $lockedPayment = Payment::query()->lockForUpdate()->findOrFail($payment->id);
            $order = Order::query()->lockForUpdate()->findOrFail($lockedPayment->order_id);

            if (in_array($order->payment_status, ['captured', 'cod_fee_paid'], true)) {
                return;
            }

            if (! $this->paymentMatchesCurrentOrder($lockedPayment, $order)) {
                $lockedPayment->forceFill([
                    'provider_payment_id' => $providerPayment['id'],
                    'provider_signature' => $signature ?? $lockedPayment->provider_signature,
                    'status' => 'authorized_review',
                    'method' => $providerPayment['method'] ?? null,
                    'provider_payload' => $providerPayment,
                    'authorized_at' => now(),
                ])->save();

                return;
            }

            $lockedPayment->forceFill([
                'provider_payment_id' => $providerPayment['id'],
                'provider_signature' => $signature ?? $lockedPayment->provider_signature,
                'status' => 'authorized',
                'method' => $providerPayment['method'] ?? null,
                'provider_payload' => $providerPayment,
                'authorized_at' => now(),
            ])->save();
            $order->forceFill(['status' => 'payment_pending', 'payment_status' => 'authorized'])->save();
        });
    }

    /**
     * @param  array<string, mixed>  $providerPayment
     */
    private function capture(Payment $payment, array $providerPayment, ?string $signature): void
    {
        DB::transaction(function () use ($payment, $providerPayment, $signature): void {
            $lockedPayment = Payment::query()->lockForUpdate()->findOrFail($payment->id);
            $order = Order::query()->lockForUpdate()->findOrFail($lockedPayment->order_id);

            if ($lockedPayment->status === 'captured' && in_array($order->payment_status, ['captured', 'cod_fee_paid'], true)) {
                return;
            }

            if ($order->payment_status === 'captured_review') {
                $this->markForReview($order, $lockedPayment, $providerPayment, $signature);

                return;
            }

            if (in_array($order->payment_status, ['captured', 'cod_fee_paid'], true)) {
                $lockedPayment->forceFill([
                    'provider_payment_id' => $providerPayment['id'],
                    'status' => 'captured_review',
                    'provider_payload' => $providerPayment,
                    'captured_at' => now(),
                ])->save();

                return;
            }

            if (! $this->paymentMatchesCurrentOrder($lockedPayment, $order)) {
                $this->markForReview($order, $lockedPayment, $providerPayment, $signature);

                return;
            }

            $items = OrderItem::query()
                ->where('order_id', $order->id)
                ->orderBy('product_id')
                ->orderBy('product_variant_id')
                ->get();
            $allocations = [];

            if ($items->isEmpty()) {
                $this->markForReview($order, $lockedPayment, $providerPayment, $signature);

                return;
            }

            foreach ($items as $item) {
                $product = Product::query()->lockForUpdate()->find($item->product_id);
                $variant = $item->product_variant_id
                    ? ProductVariant::query()
                        ->where('product_id', $item->product_id)
                        ->lockForUpdate()
                        ->find($item->product_variant_id)
                    : null;
                $reservationQuery = StockReservation::query()
                    ->where('order_id', $order->id)
                    ->where('product_id', $item->product_id)
                    ->lockForUpdate();
                $variant
                    ? $reservationQuery->where('product_variant_id', $variant->id)
                    : $reservationQuery->whereNull('product_variant_id');
                $reservation = $reservationQuery->first();

                if (! $product || ($item->product_variant_id && ! $variant)) {
                    $this->markForReview($order, $lockedPayment, $providerPayment, $signature);

                    return;
                }

                $hasReservation = $reservation
                    && $reservation->committed_at === null
                    && $reservation->released_at === null
                    && $reservation->expires_at->isFuture();
                $stock = $variant?->stock ?? $product->stock;

                if ($hasReservation) {
                    $available = $stock;
                } else {
                    $otherReservations = StockReservation::query()
                        ->where('product_id', $product->id)
                        ->where('order_id', '!=', $order->id)
                        ->whereNull('committed_at')
                        ->whereNull('released_at')
                        ->where('expires_at', '>', now());
                    $variant
                        ? $otherReservations->where('product_variant_id', $variant->id)
                        : $otherReservations->whereNull('product_variant_id');
                    $reservedByOtherOrders = (int) $otherReservations->sum('quantity');
                    $available = $stock - $reservedByOtherOrders;
                }

                if ($item->quantity > $available) {
                    $this->markForReview($order, $lockedPayment, $providerPayment, $signature);

                    return;
                }

                $allocations[] = [$product, $variant, $reservation, $item->quantity];
            }

            foreach ($allocations as [$product, $variant, $reservation, $quantity]) {
                $variant?->decrement('stock', $quantity);
                $product->decrement('stock', $quantity);

                if ($reservation) {
                    $reservation->forceFill([
                        'committed_at' => now(),
                        'released_at' => null,
                    ])->save();
                } else {
                    StockReservation::query()->create([
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'product_variant_id' => $variant?->id,
                        'quantity' => $quantity,
                        'expires_at' => now(),
                        'committed_at' => now(),
                    ]);
                }
            }

            $isCod = $order->payment_method === 'cash_on_delivery';
            $lockedPayment->forceFill([
                'provider_payment_id' => $providerPayment['id'],
                'provider_signature' => $signature ?? $lockedPayment->provider_signature,
                'status' => 'captured',
                'method' => $providerPayment['method'] ?? null,
                'provider_payload' => $providerPayment,
                'authorized_at' => $lockedPayment->authorized_at ?? now(),
                'captured_at' => now(),
                'failed_at' => null,
            ])->save();
            $order->forceFill([
                'status' => $isCod ? 'confirmed' : 'paid',
                'payment_status' => $isCod ? 'cod_fee_paid' : 'captured',
                'placed_at' => $order->placed_at ?? now(),
            ])->save();
        });
    }

    /**
     * @param  array<string, mixed>  $providerPayment
     */
    private function fail(Payment $payment, array $providerPayment): void
    {
        $payment->forceFill([
            'provider_payment_id' => $providerPayment['id'],
            'status' => 'failed',
            'method' => $providerPayment['method'] ?? null,
            'provider_payload' => $providerPayment,
            'failed_at' => now(),
        ])->save();
    }

    /**
     * @param  array<string, mixed>  $providerPayment
     */
    private function markForReview(
        Order $order,
        Payment $payment,
        array $providerPayment,
        ?string $signature
    ): void {
        $payment->forceFill([
            'provider_payment_id' => $providerPayment['id'],
            'provider_signature' => $signature ?? $payment->provider_signature,
            'status' => 'captured_review',
            'method' => $providerPayment['method'] ?? null,
            'provider_payload' => $providerPayment,
            'captured_at' => now(),
        ])->save();
        $order->forceFill([
            'status' => 'payment_review',
            'payment_status' => 'captured_review',
            'placed_at' => $order->placed_at ?? now(),
        ])->save();
        StockReservation::query()
            ->where('order_id', $order->id)
            ->whereNull('committed_at')
            ->whereNull('released_at')
            ->update(['released_at' => now()]);
    }

    private function paymentMatchesCurrentOrder(Payment $payment, Order $order): bool
    {
        $expectedPurpose = $order->payment_method === 'cash_on_delivery'
            ? $this->codOnlinePaymentPurpose($order)
            : 'order_total';

        return $payment->purpose === $expectedPurpose
            && $payment->amount_paise === $order->online_payable_paise
            && $payment->currency === $order->currency;
    }

    private function codOnlinePaymentPurpose(Order $order): string
    {
        return $order->shipping_paise > 0 && $order->cod_fee_paise === 0
            ? 'shipping_fee'
            : 'cod_fee';
    }
}
