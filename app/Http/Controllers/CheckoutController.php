<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCheckoutRequest;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartService;
use App\Services\OrderPaymentService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    private const ORDER_SESSION_KEY = 'monricx_checkout_order_id';

    public function create(Request $request, CartService $cartService): View|RedirectResponse
    {
        $summary = $cartService->summary($request);

        if ($summary['item_count'] === 0) {
            return redirect()->route('shop')->with('cart_open', true);
        }

        $draftOrder = $this->draftOrder($request);

        return view('storefront.checkout', [
            'summary' => $summary,
            'draftOrder' => $draftOrder,
            'states' => config('monricx.indian_states'),
            'shippingFeePaise' => config('monricx.shipping_fee_paise'),
            'freeShippingAbovePaise' => config('monricx.free_shipping_above_paise'),
            'codFeePaise' => config('monricx.cod_fee_paise'),
            'razorpayCheckout' => $request->session()->get('razorpay_checkout'),
        ]);
    }

    public function store(
        StoreCheckoutRequest $request,
        CartService $cartService,
        OrderPaymentService $paymentService,
    ): RedirectResponse {
        $validated = $request->validated();
        $cart = $cartService->current($request);

        if (! $cart) {
            return redirect()->route('shop')->with('cart_open', true);
        }

        $order = DB::transaction(function () use ($request, $validated, $cart): Order {
            $cartItems = CartItem::query()
                ->where('cart_id', $cart->id)
                ->lockForUpdate()
                ->get();

            if ($cartItems->isEmpty()) {
                throw ValidationException::withMessages([
                    'cart' => 'Your shopping bag is empty.',
                ]);
            }

            $products = Product::query()
                ->published()
                ->whereIn('id', $cartItems->pluck('product_id'))
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            $variants = ProductVariant::query()
                ->whereIn('id', $cartItems->pluck('product_variant_id')->filter())
                ->where('is_active', true)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $subtotalPaise = 0;

            foreach ($cartItems as $cartItem) {
                $product = $products->get($cartItem->product_id);
                $variant = $cartItem->product_variant_id
                    ? $variants->get($cartItem->product_variant_id)
                    : null;
                $availableStock = $variant?->stock ?? $product?->stock;

                if (! $product
                    || ($cartItem->product_variant_id && (! $variant || $variant->product_id !== $product->id))
                    || $cartItem->quantity > $availableStock) {
                    throw ValidationException::withMessages([
                        'cart' => 'One or more products are no longer available in the requested quantity.',
                    ]);
                }

                $unitPricePaise = $variant?->effectivePricePaise() ?? $product->effectivePricePaise();
                $cartItem->forceFill(['unit_price_paise' => $unitPricePaise])->save();
                $subtotalPaise += $cartItem->quantity * $unitPricePaise;
            }

            $order = $this->lockedDraftOrder($request) ?? new Order;
            $isCod = $validated['payment_option'] === 'cash_on_delivery';
            $shippingFeePaise = ! $isCod && $subtotalPaise <= config('monricx.free_shipping_above_paise')
                ? config('monricx.shipping_fee_paise')
                : 0;
            $codFeePaise = $isCod ? config('monricx.cod_fee_paise') : 0;
            $totalPaise = $subtotalPaise + $shippingFeePaise + $codFeePaise;
            $order->fill([
                'user_id' => $request->user()?->id,
                'status' => 'draft',
                'payment_status' => 'pending',
                'payment_method' => $validated['payment_option'],
                'fulfilment_status' => 'unfulfilled',
                'customer_name' => $validated['customer_name'],
                'customer_email' => $validated['customer_email'],
                'customer_phone' => $validated['customer_phone'],
                'address_line_1' => $validated['address_line_1'],
                'address_line_2' => null,
                'city' => $validated['city'],
                'state' => $validated['state'],
                'postal_code' => $validated['postal_code'],
                'country_code' => $validated['country_code'],
                'currency' => 'INR',
                'subtotal_paise' => $subtotalPaise,
                'discount_paise' => 0,
                'shipping_paise' => $shippingFeePaise,
                'cod_fee_paise' => $codFeePaise,
                'online_payable_paise' => $isCod ? $codFeePaise : $totalPaise,
                'cod_due_paise' => $isCod ? $subtotalPaise : 0,
                'total_paise' => $totalPaise,
                'discount_code' => null,
                'consent_at' => now(),
            ]);
            $order->save();

            $order->items()->delete();

            foreach ($cartItems as $cartItem) {
                $product = $products->get($cartItem->product_id);
                $variant = $cartItem->product_variant_id
                    ? $variants->get($cartItem->product_variant_id)
                    : null;
                $unitPricePaise = $variant?->effectivePricePaise() ?? $product->effectivePricePaise();

                $order->items()->create([
                    'product_id' => $product->id,
                    'product_variant_id' => $variant?->id,
                    'product_title' => $product->title,
                    'product_subtitle' => $product->subtitle,
                    'product_slug' => $product->slug,
                    'quantity' => $cartItem->quantity,
                    'list_price_paise' => $variant?->price_paise ?? $product->price_paise,
                    'unit_price_paise' => $unitPricePaise,
                    'line_total_paise' => $cartItem->quantity * $unitPricePaise,
                    'metadata' => $variant ? [
                        'variant_title' => $variant->title,
                        'variant_sku' => $variant->sku,
                        'variant_source_id' => $variant->source_id,
                    ] : null,
                ]);
            }

            return $order;
        });

        $request->session()->put(self::ORDER_SESSION_KEY, $order->id);
        $razorpayCheckout = $paymentService->initiate($order);

        return redirect()->route('checkout')->with('razorpay_checkout', $razorpayCheckout);
    }

    private function draftOrder(Request $request): ?Order
    {
        $orderId = $request->session()->get(self::ORDER_SESSION_KEY);

        return $orderId
            ? Order::query()->whereKey($orderId)->whereIn('status', ['draft', 'payment_pending'])->first()
            : null;
    }

    private function lockedDraftOrder(Request $request): ?Order
    {
        $orderId = $request->session()->get(self::ORDER_SESSION_KEY);

        return $orderId
            ? Order::query()
                ->whereKey($orderId)
                ->whereIn('status', ['draft', 'payment_pending'])
                ->lockForUpdate()
                ->first()
            : null;
    }
}
