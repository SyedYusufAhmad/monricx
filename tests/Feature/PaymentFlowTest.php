<?php

namespace Tests\Feature;

use App\Contracts\PaymentGateway;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\StockReservation;
use App\Services\OrderPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\Fakes\FakePaymentGateway;
use Tests\TestCase;

class PaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    private FakePaymentGateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->gateway = new FakePaymentGateway;
        $this->app->instance(PaymentGateway::class, $this->gateway);
        config(['services.razorpay.key_id' => 'rzp_test_monricx']);
    }

    public function test_online_checkout_creates_full_value_razorpay_order_and_stock_reservation(): void
    {
        $product = $this->product();

        $response = $this->startCheckout($product, 2, 'razorpay');

        $response
            ->assertRedirect(route('checkout'))
            ->assertSessionHas('razorpay_checkout', fn (array $checkout) => $checkout['amount'] === 26100
                && $checkout['order_id'] === 'order_test_1'
            );

        $order = Order::query()->sole();

        $this->assertSame('payment_pending', $order->status);
        $this->assertSame('razorpay', $order->payment_method);
        $this->assertSame(8900, $order->shipping_paise);
        $this->assertSame(26100, $order->online_payable_paise);
        $this->assertSame(0, $order->cod_due_paise);
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'purpose' => 'order_total',
            'provider_order_id' => 'order_test_1',
            'amount_paise' => 26100,
            'status' => 'created',
        ]);
        $this->assertDatabaseHas('stock_reservations', [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'committed_at' => null,
            'released_at' => null,
        ]);
        $this->assertSame(26100, $this->gateway->createdOrders[0]['amount']);
        $this->assertArrayNotHasKey('capture', $this->gateway->createdOrders[0]);
        $this->assertSame('test', $this->gateway->createdOrders[0]['notes']['environment']);
        $this->assertSame(8, $product->fresh()->stock);
    }

    public function test_online_checkout_has_free_shipping_only_above_399_rupees(): void
    {
        $product = $this->product([
            'price_paise' => 50000,
            'discounted_price_paise' => 40000,
        ]);

        $this->startCheckout($product, 1, 'razorpay')
            ->assertSessionHas('razorpay_checkout', fn (array $checkout) => $checkout['amount'] === 40000);

        $order = Order::query()->sole();

        $this->assertSame(40000, $order->subtotal_paise);
        $this->assertSame(0, $order->shipping_paise);
        $this->assertSame(40000, $order->total_paise);
        $this->assertSame(40000, $order->online_payable_paise);
    }

    public function test_online_checkout_at_exactly_399_rupees_still_adds_shipping(): void
    {
        $product = $this->product([
            'price_paise' => 50000,
            'discounted_price_paise' => 39900,
        ]);

        $this->startCheckout($product, 1, 'razorpay');

        $order = Order::query()->sole();

        $this->assertSame(39900, $order->subtotal_paise);
        $this->assertSame(8900, $order->shipping_paise);
        $this->assertSame(48800, $order->total_paise);
        $this->assertSame(48800, $order->online_payable_paise);
    }

    public function test_cash_on_delivery_collects_only_89_rupees_online_and_records_remaining_balance(): void
    {
        $product = $this->product();

        $this->startCheckout($product, 1, 'cash_on_delivery')
            ->assertSessionHas('razorpay_checkout', fn (array $checkout) => $checkout['amount'] === 8900
                && $checkout['description'] === 'Cash on delivery charge'
            );

        $order = Order::query()->sole();

        $this->assertSame('cash_on_delivery', $order->payment_method);
        $this->assertSame(8600, $order->subtotal_paise);
        $this->assertSame(0, $order->shipping_paise);
        $this->assertSame(8900, $order->cod_fee_paise);
        $this->assertSame(17500, $order->total_paise);
        $this->assertSame(8900, $order->online_payable_paise);
        $this->assertSame(8600, $order->cod_due_paise);
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'purpose' => 'cod_fee',
            'amount_paise' => 8900,
        ]);
        $this->assertSame(8900, $this->gateway->createdOrders[0]['amount']);
    }

    public function test_cash_on_delivery_always_collects_89_rupees_online_even_above_399_rupees(): void
    {
        $product = $this->product([
            'price_paise' => 60000,
            'discounted_price_paise' => 50000,
        ]);

        $this->startCheckout($product, 1, 'cash_on_delivery')
            ->assertSessionHas('razorpay_checkout', fn (array $checkout) => $checkout['amount'] === 8900);

        $order = Order::query()->sole();

        $this->assertSame(50000, $order->subtotal_paise);
        $this->assertSame(0, $order->shipping_paise);
        $this->assertSame(8900, $order->cod_fee_paise);
        $this->assertSame(58900, $order->total_paise);
        $this->assertSame(8900, $order->online_payable_paise);
        $this->assertSame(50000, $order->cod_due_paise);
    }

    public function test_checkout_displays_both_online_and_cash_on_delivery_options(): void
    {
        $product = $this->product();
        $this->post(route('cart.items.store'), [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->get(route('checkout'))
            ->assertOk()
            ->assertSee('Razorpay')
            ->assertSee('Cash on delivery')
            ->assertSee('Address')
            ->assertSee('City')
            ->assertSee('Postal code')
            ->assertSee('Standard shipping')
            ->assertSee('₹89.00 COD charge paid online. Remaining order amount paid on delivery.');
    }

    public function test_checkout_fails_closed_when_razorpay_is_not_configured(): void
    {
        $this->gateway->configured = false;
        $product = $this->product();

        $this->startCheckout($product, 1, 'cash_on_delivery')
            ->assertSessionHasErrors('payment');

        $this->assertSame('draft', Order::query()->sole()->status);
        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('stock_reservations', 0);
        $this->assertSame(8, $product->fresh()->stock);
    }

    public function test_verified_captured_online_payment_commits_stock_and_clears_cart(): void
    {
        $product = $this->product(['stock' => 5]);
        $this->startCheckout($product, 2, 'razorpay');
        $payment = Payment::query()->sole();
        $this->gateway->fetchedPayment = $this->capturedPayment($payment);

        $this->from(route('checkout'))
            ->post(route('checkout.payment.verify'), [
                'razorpay_payment_id' => 'pay_captured_1',
                'razorpay_order_id' => $payment->provider_order_id,
                'razorpay_signature' => 'valid-signature',
            ])
            ->assertRedirect(route('order.confirmation', $payment->order->public_id));

        $order = $payment->order->fresh();

        $this->assertSame('paid', $order->status);
        $this->assertSame('captured', $order->payment_status);
        $this->assertSame(3, $product->fresh()->stock);
        $this->assertDatabaseCount(CartItem::class, 0);
        $this->assertNotNull(StockReservation::query()->sole()->committed_at);
        $this->assertSame('captured', $payment->fresh()->status);
        $this->assertSame(1, $this->gateway->paymentSignatureChecks);
    }

    public function test_selected_variant_controls_price_reservation_and_stock_capture(): void
    {
        $product = $this->product(['stock' => 8]);
        $classic = $product->variants()->create([
            'title' => 'Classic – 2.3 cm',
            'price_paise' => 20900,
            'discounted_price_paise' => 2100,
            'stock' => 2,
            'is_default' => true,
            'is_active' => true,
            'sort_order' => 10,
        ]);
        $statement = $product->variants()->create([
            'title' => 'Statement – 3.4 cm',
            'price_paise' => 23900,
            'discounted_price_paise' => 2400,
            'stock' => 6,
            'is_default' => false,
            'is_active' => true,
            'sort_order' => 20,
        ]);

        $this->post(route('cart.items.store'), [
            'product_id' => $product->id,
            'product_variant_id' => $statement->id,
            'quantity' => 2,
        ]);
        $this->post(route('checkout.store'), $this->checkoutDetails('razorpay'));

        $order = Order::query()->sole();
        $payment = Payment::query()->sole();
        $this->assertSame(4800, $order->subtotal_paise);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_variant_id' => $statement->id,
            'unit_price_paise' => 2400,
            'quantity' => 2,
        ]);
        $this->assertDatabaseHas('stock_reservations', [
            'order_id' => $order->id,
            'product_variant_id' => $statement->id,
            'quantity' => 2,
        ]);

        $this->gateway->fetchedPayment = $this->capturedPayment($payment);
        $this->post(route('checkout.payment.verify'), [
            'razorpay_payment_id' => 'pay_variant_1',
            'razorpay_order_id' => $payment->provider_order_id,
            'razorpay_signature' => 'valid-signature',
        ])->assertRedirect(route('order.confirmation', $order->public_id));

        $this->assertSame(2, $classic->fresh()->stock);
        $this->assertSame(4, $statement->fresh()->stock);
        $this->assertSame(6, $product->fresh()->stock);
    }

    public function test_authorized_payment_is_captured_via_api_before_inventory_is_committed(): void
    {
        $product = $this->product(['stock' => 5]);
        $this->startCheckout($product, 2, 'razorpay');
        $payment = Payment::query()->sole();
        $this->gateway->fetchedPayment = [
            'order_id' => $payment->provider_order_id,
            'amount' => $payment->amount_paise,
            'currency' => $payment->currency,
            'status' => 'authorized',
            'method' => 'upi',
        ];
        $this->gateway->capturedPaymentResponse = [
            'order_id' => $payment->provider_order_id,
            'method' => 'upi',
        ];

        $this->post(route('checkout.payment.verify'), [
            'razorpay_payment_id' => 'pay_authorized_1',
            'razorpay_order_id' => $payment->provider_order_id,
            'razorpay_signature' => 'valid-signature',
        ])->assertRedirect(route('order.confirmation', $payment->order->public_id));

        $this->assertSame([[
            'id' => 'pay_authorized_1',
            'amount' => $payment->amount_paise,
            'currency' => 'INR',
        ]], $this->gateway->capturedPayments);
        $this->assertSame('paid', $payment->order->fresh()->status);
        $this->assertSame('captured', $payment->fresh()->status);
        $this->assertSame(3, $product->fresh()->stock);
    }

    public function test_verified_cod_fee_confirms_order_and_preserves_cod_balance(): void
    {
        $product = $this->product(['stock' => 5]);
        $this->startCheckout($product, 1, 'cash_on_delivery');
        $payment = Payment::query()->sole();
        $this->gateway->fetchedPayment = $this->capturedPayment($payment);

        $this->post(route('checkout.payment.verify'), [
            'razorpay_payment_id' => 'pay_cod_fee_1',
            'razorpay_order_id' => $payment->provider_order_id,
            'razorpay_signature' => 'valid-signature',
        ])->assertRedirect(route('order.confirmation', $payment->order->public_id));

        $order = $payment->order->fresh();

        $this->assertSame('confirmed', $order->status);
        $this->assertSame('cod_fee_paid', $order->payment_status);
        $this->assertSame(8600, $order->cod_due_paise);
        $this->assertSame(4, $product->fresh()->stock);
    }

    public function test_invalid_browser_signature_never_commits_inventory(): void
    {
        $product = $this->product(['stock' => 5]);
        $this->startCheckout($product, 1, 'razorpay');
        $payment = Payment::query()->sole();
        $this->gateway->paymentSignatureIsValid = false;

        $this->from(route('checkout'))
            ->post(route('checkout.payment.verify'), [
                'razorpay_payment_id' => 'pay_tampered',
                'razorpay_order_id' => $payment->provider_order_id,
                'razorpay_signature' => 'tampered-signature',
            ])
            ->assertRedirect(route('checkout'))
            ->assertSessionHasErrors('payment');

        $this->assertSame(5, $product->fresh()->stock);
        $this->assertNull(StockReservation::query()->sole()->committed_at);
        $this->assertSame('created', $payment->fresh()->status);
    }

    public function test_capture_for_superseded_payment_is_held_for_review_without_stock_change(): void
    {
        $product = $this->product(['stock' => 5]);
        $this->startCheckout($product, 1, 'razorpay');
        $oldPayment = Payment::query()->sole();

        $this->post(route('checkout.store'), $this->checkoutDetails('cash_on_delivery'));

        $this->assertDatabaseCount('payments', 2);
        $this->assertSame('superseded', $oldPayment->fresh()->status);

        app(OrderPaymentService::class)->processWebhookPayment([
            'id' => 'pay_late_old_order',
            'order_id' => $oldPayment->provider_order_id,
            'amount' => $oldPayment->amount_paise,
            'currency' => $oldPayment->currency,
            'status' => 'captured',
            'method' => 'upi',
        ]);

        $this->assertSame('payment_review', $oldPayment->order->fresh()->status);
        $this->assertSame('captured_review', $oldPayment->fresh()->status);
        $this->assertSame(5, $product->fresh()->stock);
    }

    public function test_captured_webhook_is_signature_checked_and_idempotent(): void
    {
        config(['services.razorpay.webhook_secret' => 'webhook-secret']);
        $product = $this->product(['stock' => 5]);
        $this->startCheckout($product, 1, 'razorpay');
        $payment = Payment::query()->sole();
        $providerPayment = $this->capturedPayment($payment);
        $providerPayment['id'] = 'pay_webhook_1';
        $payload = json_encode([
            'event' => 'payment.captured',
            'payload' => ['payment' => ['entity' => $providerPayment]],
        ], JSON_THROW_ON_ERROR);
        $server = [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_RAZORPAY_SIGNATURE' => 'valid-webhook-signature',
            'HTTP_X_RAZORPAY_EVENT_ID' => 'event_unique_1',
        ];

        $this->call('POST', route('razorpay.webhook'), [], [], [], $server, $payload)
            ->assertOk()
            ->assertJson(['accepted' => true]);
        $this->call('POST', route('razorpay.webhook'), [], [], [], $server, $payload)
            ->assertOk()
            ->assertJson(['accepted' => true]);

        $this->assertSame(4, $product->fresh()->stock);
        $this->assertSame('captured', $payment->order->fresh()->payment_status);
        $this->assertDatabaseCount('payment_webhook_events', 1);
        $this->assertSame(2, $this->gateway->webhookSignatureChecks);
    }

    public function test_invalid_webhook_signature_is_rejected_without_storing_event(): void
    {
        config(['services.razorpay.webhook_secret' => 'webhook-secret']);
        $this->gateway->webhookSignatureIsValid = false;

        $this->call('POST', route('razorpay.webhook'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_RAZORPAY_SIGNATURE' => 'invalid',
            'HTTP_X_RAZORPAY_EVENT_ID' => 'event_invalid_1',
        ], '{"event":"payment.captured"}')
            ->assertStatus(400)
            ->assertJson(['accepted' => false]);

        $this->assertDatabaseCount('payment_webhook_events', 0);
    }

    private function startCheckout(
        Product $product,
        int $quantity,
        string $paymentOption
    ): TestResponse {
        $this->post(route('cart.items.store'), [
            'product_id' => $product->id,
            'quantity' => $quantity,
        ]);

        return $this->post(route('checkout.store'), [
            ...$this->checkoutDetails($paymentOption),
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function checkoutDetails(string $paymentOption): array
    {
        return [
            'customer_email' => 'buyer@example.com',
            'customer_name' => 'MONRICX Buyer',
            'country_code' => 'IN',
            'state' => 'Uttar Pradesh',
            'address_line_1' => '42 Jewellery Lane',
            'city' => 'Lucknow',
            'postal_code' => '226001',
            'customer_phone' => '9876543210',
            'payment_option' => $paymentOption,
            'terms' => '1',
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function product(array $attributes = []): Product
    {
        return Product::query()->create(array_merge([
            'title' => 'Golden Heart Pendant Necklace ❤️✨',
            'slug' => '-golden-heart-pendant-necklace-',
            'subtitle' => 'Where timeless romance meets effortless luxury. ❤️✨',
            'price_paise' => 23600,
            'discounted_price_paise' => 8600,
            'stock' => 8,
            'description' => 'Description — Top Choice ✨',
            'status' => 'published',
            'published_at' => now(),
        ], $attributes));
    }

    /**
     * @return array<string, mixed>
     */
    private function capturedPayment(Payment $payment): array
    {
        return [
            'order_id' => $payment->provider_order_id,
            'amount' => $payment->amount_paise,
            'currency' => $payment->currency,
            'status' => 'captured',
            'method' => 'upi',
        ];
    }
}
