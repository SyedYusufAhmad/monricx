@extends('layouts.storefront')

@section('content')
    <section class="monricx-hero">
        <img src="{{ asset('images/monricx/hero.jpg') }}" alt="">
        <div class="monricx-hero__shade"></div>
        <div class="monricx-hero__content">
            <p class="monricx-hero__brand"><strong>MONRICX</strong></p>
            <h1><strong>Luxury Style<br>Exclusive Designs</strong></h1>
            <p class="monricx-hero__intro">Discover Exclusive Jewellery for Every Occasion.</p>
            <p class="monricx-hero__limited"><strong>Limited Edition - Once Sold Out, It Won't Be Restocked.</strong></p>
            <div class="monricx-hero__actions">
                <a href="{{ route('shop') }}">Shop Now</a>
                <a href="{{ route('shop') }}#shop-header">Explore Collection</a>
            </div>
        </div>
    </section>

    <section class="monricx-philosophy">
        <p>THE MONRICX<br>PHILOSOPHY⭐</p>
        <p><strong><em>The question isn't 'Can you afford it?' It's 'Can you carry it?</em></strong></p>
    </section>

    <section class="monricx-product-section monricx-product-section--home">
        @if ($products->isNotEmpty())
            <div class="monricx-product-toolbar monricx-product-toolbar--home">
                <p>{{ $products->total() }} products</p>
                <form method="get" class="monricx-sort-form">
                    <label for="home-sort">Sort by:</label>
                    <select id="home-sort" name="sort" onchange="this.form.submit()">
                        <option value="" @selected($sort === '')>Default</option>
                        <option value="price-asc" @selected($sort === 'price-asc')>Price (low to high)</option>
                        <option value="price-desc" @selected($sort === 'price-desc')>Price (high to low)</option>
                    </select>
                </form>
            </div>
        @endif
        <div class="monricx-product-grid">
            @foreach ($products as $product)
                @include('storefront.partials.product-card', ['product' => $product])
            @endforeach
        </div>
        @if ($products->hasPages())
            <nav class="monricx-home-pagination" aria-label="Product pages">
                @if ($products->onFirstPage())
                    <span aria-hidden="true"><svg width="8" height="14" viewBox="0 0 8 14" fill="none"><path d="M7 1L1 7L7 13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                @else
                    <a href="{{ $products->previousPageUrl() }}" aria-label="Previous page"><svg width="8" height="14" viewBox="0 0 8 14" fill="none"><path d="M7 1L1 7L7 13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></a>
                @endif
                @foreach ($products->getUrlRange(1, $products->lastPage()) as $page => $url)
                    <a href="{{ $url }}" @class(['is-active' => $page === $products->currentPage()]) aria-label="Page {{ $page }}">{{ $page }}</a>
                @endforeach
                @if ($products->hasMorePages())
                    <a href="{{ $products->nextPageUrl() }}" aria-label="Next page"><svg width="8" height="14" viewBox="0 0 8 14" fill="none"><path d="M1 13L7 7L1 1" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></a>
                @else
                    <span aria-hidden="true"><svg width="8" height="14" viewBox="0 0 8 14" fill="none"><path d="M1 13L7 7L1 1" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                @endif
            </nav>
        @endif
    </section>

    <section class="monricx-gallery-strip" aria-label="MONRICX gallery">
        <div>
            <img src="{{ asset('images/monricx/gallery-ring.png') }}" alt="">
            <img src="{{ asset('images/monricx/gallery-key.png') }}" alt="">
            <img src="{{ asset('images/monricx/gallery-earrings.png') }}" alt="">
            <img src="{{ asset('images/monricx/gallery-band.png') }}" alt="">
        </div>
    </section>

    @include('storefront.partials.category-grid', ['variant' => 'home'])
@endsection
