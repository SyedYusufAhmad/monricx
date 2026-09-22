@extends('layouts.storefront', ['title' => 'FAQ'])

@section('content')
    <div class="monricx-dark-page monricx-faq-page">
        <section class="monricx-faq-hero">
            <p class="monricx-kicker">MONRICX HELP CENTER</p>
            <h1><strong>Frequently Asked Questions</strong></h1>
            <p>Find answers to the most frequently asked questions about orders, shipping, returns, payments, and customer support.</p>
            <a href="{{ route('home') }}">Shop Collection</a>
        </section>

        <section class="monricx-faq-content">
            <p class="monricx-kicker"><strong>COMMON QUESTIONS</strong></p>
            <h2><strong>Need Help?</strong></h2>
            <p>We're here to make your shopping experience easy and hassle-free. If you can't find your answer below, feel free to contact our support team.</p>
            <div class="monricx-faq-grid">
                <article><h3>How long does delivery take?</h3><p>Orders are usually delivered within 3–7 business days across India.</p></article>
                <article><h3>Do you offer Cash on Delivery (COD)?</h3><p>Yes, Cash on Delivery (COD) is available. A ₹89.00 COD charge is paid online at checkout, and the remaining order amount is paid in cash when your order is delivered.</p></article>
                <article><h3>What is the return policy?</h3><p>Returns are accepted only for damaged, wrong, or missing products. An unboxing video is mandatory. Eligible claims will receive a replacement product only.</p></article>
                <article><h3>How can I contact customer support?</h3><p>You can contact us via WhatsApp at +91 8791627686 or email us at <a href="mailto:monricxapp@gmail.com">monricxapp@gmail.com</a> during our support hours.</p></article>
            </div>
            <div class="monricx-faq-help">
                <h3><strong>Still Need Help?</strong></h3>
                <p>Our customer support team is always ready to help you with orders, shipping, returns, and product-related questions.</p>
            </div>
        </section>
    </div>
@endsection
