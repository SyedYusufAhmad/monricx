@extends('layouts.storefront', ['title' => 'About'])

@section('content')
    <div class="monricx-dark-page monricx-about-page">
        <section class="monricx-about-split">
            <div class="monricx-about-copy">
                <p class="monricx-kicker">MONRICX ARCHITECTURE</p>
                <h1>Timeless Luxury<br>Modern Elegance..</h1>
                <p>MONRICX creates premium jewellery designed for modern lifestyles. Every piece is carefully selected to combine timeless elegance, exceptional quality, and everyday comfort.</p>
            </div>
            <img src="{{ asset('images/monricx/about-ring.jpg') }}" alt="Extreme macro close-up of a heavy-gauge gold ring on a finger, focusing on the brushed champagne texture and sharp architectural edges under dramatic low-key lighting.">
        </section>

        <section class="monricx-about-split monricx-about-split--reverse">
            <img src="{{ asset('images/monricx/about-pendant.jpg') }}" alt="A single heavy gold pendant resting on dark polished obsidian stone, sharp directional key light highlighting the structural curves and deep shadows.">
            <div class="monricx-about-copy">
                <p class="monricx-kicker">THE PHILOSOPHY</p>
                <h2>Sculpted modern art</h2>
                <p>At MONRICX, we believe jewellery is more than an accessory. It is a reflection of confidence, personality, and individual style. Our collections are designed to elevate everyday fashion with elegant details and lasting quality.</p>
                <p>From elegant earrings and rings to bracelets, pendants, anklets, necklaces, and gift collections, every piece is carefully selected to combine modern design, lasting quality, and everyday comfort. Our mission is to make luxury-inspired jewellery accessible while delivering a secure, reliable, and enjoyable shopping experience.</p>
            </div>
        </section>

        <section class="monricx-about-gallery" aria-label="MONRICX jewellery collection">
            <img src="{{ asset('images/monricx/about-quality.jpg') }}" alt="">
            <img src="{{ asset('images/monricx/about-elegance.jpg') }}" alt="">
            <img src="{{ asset('images/monricx/about-collection.jpg') }}" alt="">
        </section>

        <section class="monricx-about-features">
            <article>
                <p class="monricx-kicker">PREMIUM QUALITY</p>
                <p>At MONRICX, we carefully select every jewellery piece for its premium quality, elegant design, and lasting finish. Our commitment is to provide stylish, affordable, and comfortable fashion jewellery that adds timeless elegance to every occasion..</p>
            </article>
            <article>
                <h2>Timeless Elegance</h2>
                <p>Discover our curated collection of premium earrings, rings, bracelets, pendants, anklets, and necklaces—designed to complement every style and every occasion with elegance and sophistication..</p>
            </article>
        </section>
    </div>
@endsection
