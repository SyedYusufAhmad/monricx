<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Order {{ $order->order_number }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bodoni+Moda:wght@400;500&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="monricx-checkout-body">
    <header class="monricx-checkout-header">
        <a href="{{ route('home') }}" aria-label="Return to store">
            <img src="{{ asset('images/monricx/logo.png') }}" alt="">
        </a>
    </header>

    <main class="monricx-order-confirmation">
        @if ($order->payment_status === 'cod_fee_paid')
            <p class="monricx-order-confirmation__mark">✓</p>
            <h1>Order confirmed</h1>
            <p>₹{{ number_format($order->online_payable_paise / 100, 2) }} paid online</p>
            <p>₹{{ number_format($order->cod_due_paise / 100, 2) }} to be paid on delivery</p>
        @elseif ($order->payment_status === 'captured')
            <p class="monricx-order-confirmation__mark">✓</p>
            <h1>Payment successful</h1>
            <p>Thank you for your order.</p>
        @elseif ($order->payment_status === 'captured_review')
            <h1>Payment received</h1>
            <p>Your order is being reviewed.</p>
        @else
            <h1>Payment is being confirmed</h1>
            <p>Please keep this page for your records.</p>
        @endif

        <div class="monricx-order-confirmation__number">
            <span>Order</span>
            <strong>{{ $order->order_number }}</strong>
        </div>
        <a href="{{ route('shop') }}">Return to store</a>
    </main>
</body>
</html>
