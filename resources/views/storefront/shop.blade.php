@extends('layouts.storefront', ['title' => 'Shop'])

@section('content')
    <section id="shop-header" class="monricx-product-section monricx-product-section--shop">
        <div class="monricx-product-toolbar">
            <form method="get" class="monricx-search-form">
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="m16.5 16.5 5 5" fill="none" stroke="currentColor" stroke-width="1.8"/></svg>
                <label class="sr-only" for="product-search">Search products</label>
                <input id="product-search" type="search" name="q" value="{{ $query }}" placeholder="Search products">
            </form>
            <form method="get" class="monricx-sort-form">
                @if ($query !== '')<input type="hidden" name="q" value="{{ $query }}">@endif
                <label for="sort">Sort by:</label>
                <select id="sort" name="sort" onchange="this.form.submit()">
                    <option value="" @selected($sort === '')>Default</option>
                    <option value="recent" @selected($sort === 'recent')>Most recent</option>
                    <option value="price-desc" @selected($sort === 'price-desc')>Price (high to low)</option>
                    <option value="price-asc" @selected($sort === 'price-asc')>Price (low to high)</option>
                </select>
            </form>
        </div>
        <div class="monricx-product-grid">
            @foreach ($products as $product)
                @include('storefront.partials.product-card', ['product' => $product])
            @endforeach
        </div>
        @if ($products->hasPages())
            <div class="mt-12">{{ $products->links() }}</div>
        @endif
    </section>

    @include('storefront.partials.category-grid', ['variant' => 'shop'])

    <section class="monricx-premium-heading">
        <h1>Premium Fashion Jewellery</h1>
    </section>
@endsection
