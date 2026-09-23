<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'MONRICX' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bodoni+Moda:opsz,wght@6..96,400;6..96,500;6..96,600;6..96,700;6..96,900&family=Libre+Baskerville:ital,wght@1,700&family=Manrope:wght@400;500;600;700&family=Roboto+Slab:wght@400&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#f8f5f6] text-[#1d1e20] antialiased monricx-site-body"
      x-data="{ cartOpen: false }"
      @keydown.escape.window="cartOpen = false"
      @if (session('cart_open')) x-init="$nextTick(() => cartOpen = true)" @endif>
    <div class="monricx-shipping-bar"><strong>🚚 Free Shipping On Orders Above ₹399</strong></div>

    <header class="monricx-header" x-data="{ menuOpen: false, shopOpen: false }">
        <div class="monricx-header__inner">
            <button class="monricx-menu-button" type="button" aria-label="Menu" :aria-expanded="menuOpen" @click="menuOpen = !menuOpen">
                <span></span><span></span><span></span>
            </button>
            <a href="{{ route('home') }}" class="monricx-logo" aria-label="MONRICX home">
                <img src="{{ asset('images/monricx/logo.png') }}" alt="">
            </a>
            <nav class="monricx-navigation" aria-label="Primary navigation">
                <a href="{{ route('home') }}" @class(['is-active' => request()->routeIs('home')])>Home</a>
                <div class="relative" @click.outside="shopOpen = false">
                    <button type="button" class="monricx-shop-trigger" @click="shopOpen = !shopOpen" :aria-expanded="shopOpen">
                        Shop
                        <svg viewBox="0 0 12 8" aria-hidden="true"><path d="m1 1 5 5 5-5" fill="none" stroke="currentColor" stroke-width="1.5"/></svg>
                    </button>
                    <div class="monricx-shop-menu" x-cloak x-show="shopOpen" x-transition>
                        <a href="{{ route('shop') }}">Shop</a>
                        <a href="/rings">Rings</a>
                        <a href="/earings">Earing</a>
                        <a href="/necklace-and-pendants">Necklaces &amp; Pendants</a>
                        <a href="/bracelets">Bracelets</a>
                    </div>
                </div>
                <a href="{{ route('privacy-policy') }}" @class(['is-active' => request()->routeIs('privacy-policy')])>Privacy policy</a>
                <a href="{{ route('refund-policy') }}" @class(['is-active' => request()->routeIs('refund-policy')])>Refund policy</a>
                <a href="{{ route('faq') }}" @class(['is-active' => request()->routeIs('faq')])>FAQ</a>
                <a href="{{ route('contact') }}" @class(['is-active' => request()->routeIs('contact')])>Contact</a>
                <a href="{{ route('about') }}" @class(['is-active' => request()->routeIs('about')])>About</a>
            </nav>
            <div class="monricx-header-actions">
                <a href="https://www.facebook.com/share/1CahhmfctE/?mibextid=wwXIfr" target="_blank" rel="noopener" aria-label="Go to Facebook page">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M24 12.07C24 5.4 18.63 0 12 0S0 5.4 0 12.07C0 18.1 4.39 23.1 10.13 24v-8.44H7.08v-3.49h3.05V9.41c0-3.03 1.79-4.7 4.53-4.7 1.31 0 2.68.24 2.68.24v2.96h-1.51c-1.49 0-1.96.93-1.96 1.89v2.27h3.33l-.53 3.49h-2.8V24C19.61 23.1 24 18.1 24 12.07Z"/></svg>
                </a>
                <a href="https://www.instagram.com/monricx?igsh=MTcwaGljajNha2hyZw==&utm_source=qr" target="_blank" rel="noopener" aria-label="Go to Instagram page">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="2" d="M7 2h10a5 5 0 0 1 5 5v10a5 5 0 0 1-5 5H7a5 5 0 0 1-5-5V7a5 5 0 0 1 5-5Z"/><circle cx="12" cy="12" r="4" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="18" cy="6" r="1.2" fill="currentColor"/></svg>
                </a>
                <button type="button" class="monricx-cart-button" aria-label="Go to cart" @click="cartOpen = true">
                    <svg viewBox="0 0 32 38" aria-hidden="true"><path d="M2 10h28v26H2zM10 12V7a6 6 0 0 1 12 0v5" fill="none" stroke="currentColor" stroke-width="1.6"/></svg>
                    @if ($cartSummary['item_count'] > 0)
                        <span class="monricx-cart-count" aria-hidden="true">{{ $cartSummary['item_count'] }}</span>
                    @endif
                    <span class="sr-only">{{ $cartSummary['item_count'] }} items</span>
                </button>
            </div>
        </div>
        <nav class="monricx-mobile-navigation" x-cloak x-show="menuOpen" x-transition aria-label="Mobile navigation">
            <a href="{{ route('home') }}">Home</a>
            <a href="{{ route('shop') }}">Shop</a>
            <a href="{{ route('privacy-policy') }}">Privacy policy</a>
            <a href="{{ route('refund-policy') }}">Refund policy</a>
            <a href="{{ route('faq') }}">FAQ</a>
            <a href="{{ route('contact') }}">Contact</a>
            <a href="{{ route('about') }}">About</a>
        </nav>
    </header>

    <div class="monricx-cart-overlay" x-cloak x-show="cartOpen" x-transition.opacity @click="cartOpen = false"></div>
    <aside class="monricx-cart-drawer"
           x-cloak
           x-show="cartOpen"
           x-transition:enter="transition ease-out duration-300"
           x-transition:enter-start="translate-x-full"
           x-transition:enter-end="translate-x-0"
           x-transition:leave="transition ease-in duration-200"
           x-transition:leave-start="translate-x-0"
           x-transition:leave-end="translate-x-full"
           aria-label="Shopping bag">
        <div class="monricx-cart-drawer__heading">
            <p>Shopping bag</p>
            <button type="button" aria-label="Close shopping bag" @click="cartOpen = false">
                <svg viewBox="0 0 20 20" aria-hidden="true"><path d="m4 4 12 12M16 4 4 16" fill="none" stroke="currentColor" stroke-width="1.5"/></svg>
            </button>
        </div>

        @if ($errors->has('quantity') || $errors->has('cart'))
            <p class="monricx-cart-error">{{ $errors->first('quantity') ?: $errors->first('cart') }}</p>
        @endif

        <div class="monricx-cart-drawer__items">
            @forelse ($cartSummary['items'] as $item)
                @php
                        $cartProduct = $item->product;
                        $cartImage = $cartProduct->images->firstWhere('is_primary', true) ?? $cartProduct->images->first();
                        $cartVariant = $item->variant;
                        $cartStock = $cartVariant?->stock ?? $cartProduct->stock;
                @endphp
                <article class="monricx-cart-item">
                    <a href="{{ route('product.show', $cartProduct->slug) }}" class="monricx-cart-item__image">
                        @if ($cartImage)
                            <img src="{{ asset('storage/'.$cartImage->path) }}" alt="{{ $cartImage->alt_text ?: $cartProduct->title }}">
                        @endif
                    </a>
                    <div class="monricx-cart-item__details">
                        <a href="{{ route('product.show', $cartProduct->slug) }}">{{ $cartProduct->title }}</a>
                        @if ($cartVariant && $cartVariant->title !== $cartProduct->title)
                            <small>{{ $cartVariant->title }}</small>
                        @endif
                        <div class="monricx-cart-item__quantity">
                            <form action="{{ route('cart.items.update', $item->id) }}" method="post">
                                @csrf
                                @method('PATCH')
                                <button type="submit" name="quantity" value="{{ max(1, $item->quantity - 1) }}" aria-label="Decrease quantity" @disabled($item->quantity <= 1)>−</button>
                            </form>
                            <input type="text" value="{{ $item->quantity }}" aria-label="Quantity" disabled>
                            <form action="{{ route('cart.items.update', $item->id) }}" method="post">
                                @csrf
                                @method('PATCH')
                                <button type="submit" name="quantity" value="{{ $item->quantity + 1 }}" aria-label="Increase quantity" @disabled($item->quantity >= $cartStock)>+</button>
                            </form>
                        </div>
                    </div>
                    <div class="monricx-cart-item__price">
                        <form action="{{ route('cart.items.destroy', $item->id) }}" method="post">
                            @csrf
                            @method('DELETE')
                            <button type="submit" aria-label="Remove {{ $cartProduct->title }}">
                                <svg viewBox="0 0 20 20" aria-hidden="true"><path d="M4 5h12M8 5V3h4v2m-7 0 1 12h8l1-12M8 8v6m4-6v6" fill="none" stroke="currentColor" stroke-width="1.35"/></svg>
                            </button>
                        </form>
                        @if (($cartVariant?->discounted_price_paise ?? $cartProduct->discounted_price_paise) !== null)
                            <span>₹{{ number_format(($cartVariant?->price_paise ?? $cartProduct->price_paise) / 100, 2) }}</span>
                        @endif
                        <strong>₹{{ number_format($item->unit_price_paise / 100, 2) }}</strong>
                    </div>
                </article>
            @empty
                <div class="monricx-cart-empty">
                    <p>Shopping bag is empty</p>
                </div>
            @endforelse
        </div>

        @if ($cartSummary['item_count'] > 0)
            <div class="monricx-cart-drawer__summary">
                <p><span>Subtotal:</span><strong>₹{{ number_format($cartSummary['subtotal_paise'] / 100, 2) }}</strong></p>
                <p>Shipping calculated at checkout</p>
                <a href="{{ route('checkout') }}">Checkout</a>
                <p class="monricx-cart-secure">
                    <svg viewBox="0 0 20 20" aria-hidden="true"><path d="M5 9V6a5 5 0 0 1 10 0v3m-11 0h12v9H4z" fill="none" stroke="currentColor" stroke-width="1.4"/></svg>
                    Secure checkout
                </p>
            </div>
        @endif
    </aside>

    <main>@yield('content')</main>

    <footer class="monricx-footer">
        <div class="monricx-footer__grid">
            <div>
                <h1>MONRICX</h1>
                <p class="monricx-footer__tagline"><strong>Luxury Style</strong><br><strong>Exclusive Designs</strong></p>
            </div>
            <div class="monricx-footer__contact">
                <p>CONTACT US</p>
                <p>(Mon to Sat 12:00 PM to 6 PM)</p>
                <p>Email: MONRICXAPP@gmail.com</p>
                <p>Whatsapp : +91 8791627686 <span>(Avg reply time: 3h)</span></p>
                <p>Address: Salek Vihar, Shamli, UP, India</p>
            </div>
            <div class="monricx-footer__links">
                <p>Quick Links</p>
                <p><a href="{{ route('home') }}">Home</a></p>
                <p><a href="{{ route('shop') }}">Shop</a></p>
                <p><a href="{{ route('about') }}">About Us</a></p>
                <p><a href="{{ route('privacy-policy') }}">Privacy Policy</a></p>
                <p><a href="{{ route('refund-policy') }}">Return Policy</a></p>
            </div>
        </div>
        <div class="monricx-footer__bottom">
            <p>© 2026 MONRICX-Premium Fashion Jewellery for Every Occasion.</p>
            <p>Proudly Made In India</p>
        </div>
        <div class="monricx-footer__socials">
            <a href="https://www.facebook.com/share/1CahhmfctE/?mibextid=wwXIfr" target="_blank" rel="noopener" aria-label="Go to Facebook page">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M24 12.0726C24 5.44354 18.629 0.0725708 12 0.0725708C5.37097 0.0725708 0 5.44354 0 12.0726C0 18.0619 4.38823 23.0264 10.125 23.9274V15.5414H7.07661V12.0726H10.125V9.4287C10.125 6.42144 11.9153 4.76031 14.6574 4.76031C15.9706 4.76031 17.3439 4.99451 17.3439 4.99451V7.94612H15.8303C14.34 7.94612 13.875 8.87128 13.875 9.82015V12.0726H17.2031L16.6708 15.5414H13.875V23.9274C19.6118 23.0264 24 18.0619 24 12.0726Z" fill="currentColor"/></svg>
            </a>
            <a href="https://www.instagram.com/monricx?igsh=MTcwaGljajNha2hyZw==&utm_source=qr" target="_blank" rel="noopener" aria-label="Go to Instagram page">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12.0027 5.84808C8.59743 5.84808 5.85075 8.59477 5.85075 12C5.85075 15.4053 8.59743 18.1519 12.0027 18.1519C15.4079 18.1519 18.1546 15.4053 18.1546 12C18.1546 8.59477 15.4079 5.84808 12.0027 5.84808ZM12.0027 15.9996C9.80212 15.9996 8.00312 14.2059 8.00312 12C8.00312 9.7941 9.79677 8.00046 12.0027 8.00046C14.2086 8.00046 16.0022 9.7941 16.0022 12C16.0022 14.2059 14.2032 15.9996 12.0027 15.9996ZM19.8412 5.59644C19.8412 6.39421 19.1987 7.03135 18.4062 7.03135C17.6085 7.03135 16.9713 6.38885 16.9713 5.59644C16.9713 4.80402 17.6138 4.16153 18.4062 4.16153C19.1987 4.16153 19.8412 4.80402 19.8412 5.59644ZM23.9157 7.05277C23.8247 5.13063 23.3856 3.42801 21.9775 2.02522C20.5747 0.622429 18.8721 0.183388 16.9499 0.0870135C14.9689 -0.0254238 9.03112 -0.0254238 7.05008 0.0870135C5.1333 0.178034 3.43068 0.617075 2.02253 2.01986C0.614389 3.42265 0.180703 5.12527 0.0843279 7.04742C-0.0281093 9.02845 -0.0281093 14.9662 0.0843279 16.9472C0.175349 18.8694 0.614389 20.572 2.02253 21.9748C3.43068 23.3776 5.12794 23.8166 7.05008 23.913C9.03112 24.0254 14.9689 24.0254 16.9499 23.913C18.8721 23.822 20.5747 23.3829 21.9775 21.9748C23.3803 20.572 23.8193 18.8694 23.9157 16.9472C24.0281 14.9662 24.0281 9.03381 23.9157 7.05277ZM21.3564 19.0728C20.9388 20.1223 20.1303 20.9307 19.0755 21.3537C17.496 21.9802 13.7481 21.8356 12.0027 21.8356C10.2572 21.8356 6.50396 21.9748 4.92984 21.3537C3.88042 20.9361 3.07195 20.1276 2.64897 19.0728C2.02253 17.4934 2.16709 13.7455 2.16709 12C2.16709 10.2546 2.02789 6.50129 2.64897 4.92717C3.06659 3.87776 3.87507 3.06928 4.92984 2.6463C6.50931 2.01986 10.2572 2.16443 12.0027 2.16443C13.7481 2.16443 17.5014 2.02522 19.0755 2.6463C20.1249 3.06392 20.9334 3.8724 21.3564 4.92717C21.9828 6.50665 21.8383 10.2546 21.8383 12C21.8383 13.7455 21.9828 17.4987 21.3564 19.0728Z" fill="currentColor"/></svg>
            </a>
        </div>
    </footer>

    <button class="monricx-back-to-top" type="button" aria-label="Back to top" @click="window.scrollTo({ top: 0, behavior: 'smooth' })">
        <svg viewBox="0 0 20 20" aria-hidden="true"><path d="m4 12 6-6 6 6" fill="none" stroke="currentColor" stroke-width="1.5"/></svg>
    </button>
</body>
</html>
