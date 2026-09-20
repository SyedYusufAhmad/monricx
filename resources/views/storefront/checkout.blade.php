<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Checkout</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
@php
    $selectedPaymentOption = old('payment_option', $draftOrder?->payment_method ?? 'razorpay');
    $selectedState = old('state', $draftOrder?->state ?? '');
    $initialShippingPaise = $selectedState
        && $selectedPaymentOption === 'razorpay'
        && $summary['subtotal_paise'] <= $freeShippingAbovePaise
            ? $shippingFeePaise
            : 0;
    $initialCodFeePaise = $selectedPaymentOption === 'cash_on_delivery' ? $codFeePaise : 0;
@endphp
<body class="monricx-checkout-body"
      x-data="{
          paymentOption: @js($selectedPaymentOption),
          deliveryState: @js($selectedState),
          subtotalPaise: {{ $summary['subtotal_paise'] }},
          standardShippingPaise: {{ $shippingFeePaise }},
          freeShippingAbovePaise: {{ $freeShippingAbovePaise }},
          codFeePaise: {{ $codFeePaise }},
          get shippingPaise() {
              return this.deliveryState
                  && this.paymentOption === 'razorpay'
                  && this.subtotalPaise <= this.freeShippingAbovePaise
                      ? this.standardShippingPaise
                      : 0;
          },
          get codChargePaise() {
              return this.paymentOption === 'cash_on_delivery' ? this.codFeePaise : 0;
          },
          money(value) {
              return '₹' + (value / 100).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
          }
      }">
    <header class="monricx-checkout-header">
        <a href="{{ route('home') }}" aria-label="Return to store">
            <img src="{{ asset('images/monricx/logo.png') }}" alt="">
        </a>
    </header>

    <main class="monricx-checkout">
        <section class="monricx-checkout-form-wrap">
            <div class="monricx-checkout-form-inner">
                <a href="{{ url()->previous() === url()->current() ? route('shop') : url()->previous() }}" class="monricx-checkout-back">
                    <span aria-hidden="true">←</span> Return to store
                </a>

                <form action="{{ route('checkout.store') }}" method="post" class="monricx-checkout-form">
                    @csrf
                    <h1>Contact</h1>
                    <label class="sr-only" for="customer_email">Email</label>
                    <input id="customer_email" name="customer_email" type="email" value="{{ old('customer_email', $draftOrder?->customer_email) }}" placeholder="Email" autocomplete="email">
                    @error('customer_email')<p class="monricx-checkout-error">{{ $message }}</p>@enderror

                    <h2>Delivery</h2>
                    <label class="sr-only" for="customer_name">Full name</label>
                    <input id="customer_name" name="customer_name" type="text" value="{{ old('customer_name', $draftOrder?->customer_name) }}" placeholder="Full name" autocomplete="name">
                    @error('customer_name')<p class="monricx-checkout-error">{{ $message }}</p>@enderror

                    <label class="sr-only" for="country_code">Country</label>
                    <select id="country_code" name="country_code" aria-label="Country">
                        <option value="IN">India</option>
                    </select>

                    <label class="sr-only" for="state">State</label>
                    <select id="state" name="state" aria-label="State" x-model="deliveryState">
                        <option value="">State</option>
                        @foreach ($states as $state)
                            <option value="{{ $state }}" @selected(old('state', $draftOrder?->state) === $state)>{{ $state }}</option>
                        @endforeach
                    </select>
                    @error('state')<p class="monricx-checkout-error">{{ $message }}</p>@enderror

                    <div class="monricx-delivery-address" x-cloak x-show="deliveryState">
                        <div>
                            <label class="sr-only" for="address_line_1">Address</label>
                            <input id="address_line_1" name="address_line_1" type="text" value="{{ old('address_line_1', $draftOrder?->address_line_1) }}" placeholder="Address" autocomplete="street-address">
                            @error('address_line_1')<p class="monricx-checkout-error">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="sr-only" for="city">City</label>
                            <input id="city" name="city" type="text" value="{{ old('city', $draftOrder?->city) }}" placeholder="City" autocomplete="address-level2">
                            @error('city')<p class="monricx-checkout-error">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="sr-only" for="postal_code">Postal code</label>
                            <input id="postal_code" name="postal_code" type="text" inputmode="numeric" value="{{ old('postal_code', $draftOrder?->postal_code) }}" placeholder="Postal code" autocomplete="postal-code" maxlength="6">
                            @error('postal_code')<p class="monricx-checkout-error">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="monricx-phone-field">
                        <span aria-hidden="true">🇮🇳⌄</span>
                        <label class="sr-only" for="customer_phone">Phone number</label>
                        <input id="customer_phone" name="customer_phone" type="tel" value="{{ old('customer_phone', $draftOrder?->customer_phone) }}" placeholder="Phone number" autocomplete="tel">
                    </div>
                    @error('customer_phone')<p class="monricx-checkout-error">{{ $message }}</p>@enderror

                    <h2>Shipping method</h2>
                    <div class="monricx-shipping-method" x-show="!deliveryState">Enter delivery details to view shipping options</div>
                    <div class="monricx-shipping-method monricx-shipping-method--available" x-cloak x-show="deliveryState">
                        <span>Standard shipping</span>
                        <strong x-text="shippingPaise > 0 ? money(shippingPaise) : 'Free'">{{ $initialShippingPaise > 0 ? '₹'.number_format($initialShippingPaise / 100, 2) : 'Free' }}</strong>
                    </div>

                    <h2>Payment</h2>
                    <div class="monricx-payment-options">
                        <label class="monricx-payment-method" :class="{ 'is-selected': paymentOption === 'razorpay' }">
                            <input type="radio" name="payment_option" value="razorpay" x-model="paymentOption">
                            <strong>Razorpay</strong>
                            <span>VISA&nbsp; ● &nbsp;AMEX&nbsp; RuPay&nbsp; UPI</span>
                        </label>
                        <label class="monricx-payment-method monricx-payment-method--cod" :class="{ 'is-selected': paymentOption === 'cash_on_delivery' }">
                            <input type="radio" name="payment_option" value="cash_on_delivery" x-model="paymentOption">
                            <span>
                                <strong>Cash on delivery</strong>
                                <small>₹89.00 COD charge paid online. Remaining order amount paid on delivery.</small>
                            </span>
                        </label>
                    </div>
                    @error('payment_option')<p class="monricx-checkout-error">{{ $message }}</p>@enderror
                    @error('payment')<p class="monricx-checkout-error">{{ $message }}</p>@enderror

                    <label class="monricx-terms">
                        <input type="checkbox" name="terms" value="1" @checked(old('terms', $draftOrder?->consent_at !== null))>
                        <span>I agree to <a href="{{ route('privacy-policy') }}">Privacy policy</a> and <a href="{{ route('refund-policy') }}">Refund policy</a></span>
                    </label>
                    @error('terms')<p class="monricx-checkout-error">{{ $message }}</p>@enderror
                    @error('cart')<p class="monricx-checkout-error">{{ $message }}</p>@enderror

                    <button type="submit" class="monricx-checkout-continue">Continue</button>
                    <p class="monricx-razorpay-notice">By using the payment initiation service provided by Razorpay, you confirm that you have read and agree with the <a href="https://razorpay.com/terms" target="_blank" rel="noopener">Terms &amp; Conditions</a> and <a href="https://razorpay.com/privacy" target="_blank" rel="noopener">Privacy policy</a>.</p>
                </form>
            </div>
        </section>

        <aside class="monricx-checkout-summary">
            <div class="monricx-checkout-summary__inner">
                <div class="monricx-checkout-products">
                    @foreach ($summary['items'] as $item)
                        @php
                            $checkoutProduct = $item->product;
                            $checkoutImage = $checkoutProduct->images->firstWhere('is_primary', true) ?? $checkoutProduct->images->first();
                            $checkoutVariant = $item->variant;
                        @endphp
                        <article class="monricx-checkout-product">
                            <div class="monricx-checkout-product__image">
                                @if ($checkoutImage)
                                    <img src="{{ asset('storage/'.$checkoutImage->path) }}" alt="{{ $checkoutImage->alt_text ?: $checkoutProduct->title }}">
                                @endif
                                <span>{{ $item->quantity }}</span>
                            </div>
                            <h2>
                                {{ $checkoutProduct->title }}
                                @if ($checkoutVariant && $checkoutVariant->title !== $checkoutProduct->title)
                                    <small>{{ $checkoutVariant->title }}</small>
                                @endif
                            </h2>
                            <strong>₹{{ number_format(($item->quantity * $item->unit_price_paise) / 100, 2) }}</strong>
                        </article>
                    @endforeach
                </div>

                <div class="monricx-discount-field">
                    <input type="text" placeholder="Enter discount code" aria-label="Enter discount code">
                    <button type="button" disabled>Apply</button>
                </div>

                <div class="monricx-checkout-totals">
                    <p><span>Subtotal</span><strong>₹{{ number_format($summary['subtotal_paise'] / 100, 2) }}</strong></p>
                    <p>
                        <span>Shipping</span>
                        <span x-show="!deliveryState">Choose shipping destination</span>
                        <strong x-cloak x-show="deliveryState" x-text="shippingPaise > 0 ? money(shippingPaise) : 'Free'">{{ $initialShippingPaise > 0 ? '₹'.number_format($initialShippingPaise / 100, 2) : 'Free' }}</strong>
                    </p>
                    <p x-cloak x-show="paymentOption === 'cash_on_delivery'"><span>Cash on delivery charge</span><strong>₹{{ number_format($codFeePaise / 100, 2) }}</strong></p>
                    <div class="monricx-checkout-payment-split" x-cloak x-show="paymentOption === 'cash_on_delivery'">
                        <p><span>Pay online now</span><strong>₹{{ number_format($codFeePaise / 100, 2) }}</strong></p>
                        <p><span>Pay on delivery</span><strong>₹{{ number_format($summary['subtotal_paise'] / 100, 2) }}</strong></p>
                    </div>
                    <p class="monricx-checkout-total"><strong>Total</strong><strong x-text="money(subtotalPaise + shippingPaise + codChargePaise)">₹{{ number_format(($summary['subtotal_paise'] + $initialShippingPaise + $initialCodFeePaise) / 100, 2) }}</strong></p>
                </div>
            </div>
        </aside>
    </main>

    @if ($razorpayCheckout)
        <form id="razorpay-verification-form" action="{{ route('checkout.payment.verify') }}" method="post" hidden>
            @csrf
            <input type="hidden" name="razorpay_payment_id">
            <input type="hidden" name="razorpay_order_id">
            <input type="hidden" name="razorpay_signature">
        </form>
        <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const verificationForm = document.getElementById('razorpay-verification-form');
                const options = {
                    ...@js($razorpayCheckout),
                    image: @js(asset('images/monricx/logo.png')),
                    handler(response) {
                        verificationForm.elements.razorpay_payment_id.value = response.razorpay_payment_id;
                        verificationForm.elements.razorpay_order_id.value = response.razorpay_order_id;
                        verificationForm.elements.razorpay_signature.value = response.razorpay_signature;
                        verificationForm.submit();
                    },
                    theme: { color: '#080808' }
                };

                const checkout = new Razorpay(options);
                checkout.open();
            });
        </script>
    @endif
</body>
</html>
