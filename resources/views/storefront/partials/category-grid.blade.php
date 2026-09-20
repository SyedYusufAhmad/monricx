@php($imagePrefix = ($variant ?? 'home') === 'shop' ? 'shop' : 'home')
<section class="monricx-categories">
    <p class="monricx-categories__premium">MONRICX Premium Collection</p>
    <h2>Explore our products</h2>
    <div class="monricx-category-grid">
        <article class="monricx-category-card">
            @if ($imagePrefix === 'home')
                <picture>
                    <source media="(max-width: 768px)" srcset="{{ asset('images/monricx/collection-necklaces-mobile.jpg') }}">
                    <img src="{{ asset('images/monricx/collection-rings-desktop.jpg') }}" alt="a woman wearing a pair of earrings with pearls">
                </picture>
            @else
                <img src="{{ asset('images/monricx/shop-rings.jpg') }}" alt="">
            @endif
            <div class="monricx-category-card__shade"></div>
            <div class="monricx-category-card__content">
                <h3><strong>Rings</strong></h3>
                <p>Timeless Rings, Exclusively Selected</p>
                <a href="/rings">See more</a>
            </div>
        </article>
        <article class="monricx-category-card">
            @if ($imagePrefix === 'home')
                <picture>
                    <source media="(max-width: 768px)" srcset="{{ asset('images/monricx/collection-rings-mobile.jpg') }}">
                    <img src="{{ asset('images/monricx/collection-earings-desktop.jpg') }}" alt="A gold necklace with a rectangular diamond pendant">
                </picture>
            @else
                <img src="{{ asset('images/monricx/shop-earings.jpg') }}" alt="">
            @endif
            <div class="monricx-category-card__shade"></div>
            <div class="monricx-category-card__content">
                <h3><strong>Earing</strong></h3>
                <p>Discover Our Exclusive Earring Collection</p>
                <a href="/earings">See more</a>
            </div>
        </article>
        <article class="monricx-category-card">
            @if ($imagePrefix === 'home')
                <picture>
                    <source media="(max-width: 768px)" srcset="{{ asset('images/monricx/collection-earings-mobile.jpg') }}">
                    <img src="{{ asset('images/monricx/collection-necklaces-desktop.jpg') }}" alt="silver-colored rings">
                </picture>
            @else
                <img src="{{ asset('images/monricx/shop-necklaces.jpg') }}" alt="">
            @endif
            <div class="monricx-category-card__shade"></div>
            <div class="monricx-category-card__content">
                <h3><strong>Necklaces &amp; Pendants</strong></h3>
                <p>Elegant Necklaces &amp; Pendants for Every Occasion</p>
                <a href="/necklace-and-pendants">See more</a>
            </div>
        </article>
        <article class="monricx-category-card">
            @if ($imagePrefix === 'home')
                <picture>
                    <source media="(max-width: 768px)" srcset="{{ asset('images/monricx/collection-bracelets-mobile.webp') }}">
                    <img src="{{ asset('images/monricx/collection-bracelets-desktop.webp') }}" alt="">
                </picture>
            @else
                <img src="{{ asset('images/monricx/shop-bracelets.jpg') }}" alt="">
            @endif
            <div class="monricx-category-card__shade"></div>
            <div class="monricx-category-card__content">
                <h3><strong>Bracelets</strong></h3>
                <p>Luxury Bracelet Collection</p>
                <a href="/bracelets">See more</a>
            </div>
        </article>
    </div>
</section>
