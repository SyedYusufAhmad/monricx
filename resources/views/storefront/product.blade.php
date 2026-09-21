@extends('layouts.storefront', ['title' => $product->title])

@section('content')
    @php
        $primaryImage = $product->images->firstWhere('is_primary', true) ?? $product->images->first();
        $galleryImages = $product->images->values();
        $activeVariants = $product->variants->where('is_active', true)->values();
        $initialVariant = $activeVariants->firstWhere('is_default', true) ?? $activeVariants->first();
        $variantOptions = $activeVariants->map(fn ($variant) => [
            'id' => $variant->id,
            'title' => $variant->title,
            'list_price' => $variant->price_paise,
            'price' => $variant->effectivePricePaise(),
            'stock' => $variant->stock,
        ])->values();
    @endphp

    <section class="monricx-product-page"
             x-data="{
                 quantity: 1,
                 activeImage: 0,
                 variants: @js($variantOptions),
                 selectedVariantId: @js($initialVariant?->id),
                productStock: {{ $product->stock }},
                imageCount: {{ $galleryImages->count() }},
                 get selectedVariant() { return this.variants.find(variant => variant.id === Number(this.selectedVariantId)) ?? this.variants[0] ?? null },
                 get selectedStock() { return this.selectedVariant?.stock ?? this.productStock },
                 money(value) { return '₹' + (value / 100).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }
             }">
        <div class="monricx-product-detail">
            <div class="monricx-product-gallery">
                <div class="monricx-product-gallery__main">
                    @forelse ($galleryImages as $index => $image)
                        <img x-cloak
                             x-show="activeImage === {{ $index }}"
                             src="{{ asset('storage/'.$image->path) }}"
                             alt="{{ $image->alt_text ?: $product->title }}">
                    @empty
                        <div></div>
                    @endforelse
                    @if ($galleryImages->count() > 1)
                        <button type="button" class="monricx-product-gallery__arrow monricx-product-gallery__arrow--previous" aria-label="Previous image" @click="activeImage = (activeImage + imageCount - 1) % imageCount">‹</button>
                        <button type="button" class="monricx-product-gallery__arrow monricx-product-gallery__arrow--next" aria-label="Next image" @click="activeImage = (activeImage + 1) % imageCount">›</button>
                        <div class="monricx-product-gallery__dots" aria-label="Product image position">
                            @foreach ($galleryImages as $index => $image)
                                <button type="button" aria-label="Show image {{ $index + 1 }}" @click="activeImage = {{ $index }}" :class="{ 'is-active': activeImage === {{ $index }} }"><span></span></button>
                            @endforeach
                        </div>
                    @endif
                </div>
                @if ($galleryImages->count() > 1)
                    <div class="monricx-product-gallery__thumbs" aria-label="Product images">
                        @foreach ($galleryImages as $index => $image)
                            <button type="button" @click="activeImage = {{ $index }}" :class="{ 'is-active': activeImage === {{ $index }} }" aria-label="{{ $product->title }}">
                                <img src="{{ asset('storage/'.$image->path) }}" alt="{{ $image->alt_text ?: $product->title }}">
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="monricx-product-info">
                <h1>{{ $product->title }}</h1>
                @if ($product->subtitle)
                    <h2>{{ $product->subtitle }}</h2>
                @endif
                <p class="monricx-product-info__price">
                    @if ($product->discounted_price_paise !== null)
                        <span x-text="money(selectedVariant?.list_price ?? {{ $product->price_paise }})">₹{{ number_format($product->price_paise / 100, 2) }}</span>
                    @endif
                    <strong x-text="money(selectedVariant?.price ?? {{ $product->effectivePricePaise() }})">₹{{ number_format($product->effectivePricePaise() / 100, 2) }}</strong>
                </p>

                <form action="{{ route('cart.items.store') }}" method="post" class="monricx-product-buy">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                    @if ($activeVariants->count() > 1)
                        <div class="monricx-product-option">
                            <label for="product-variant">SIZE</label>
                            <select id="product-variant" name="product_variant_id" x-model.number="selectedVariantId" @change="quantity = 1">
                                @foreach ($activeVariants as $variant)
                                    <option value="{{ $variant->id }}">{{ $variant->title }}</option>
                                @endforeach
                            </select>
                        </div>
                    @elseif ($initialVariant)
                        <input type="hidden" name="product_variant_id" value="{{ $initialVariant->id }}">
                    @endif
                    <div class="monricx-product-buy__controls">
                        <div class="monricx-product-quantity">
                            <button type="button" aria-label="Decrease quantity" @click="quantity = Math.max(1, quantity - 1)">-</button>
                            <input type="text" name="quantity" x-model.number="quantity" readonly aria-label="Quantity">
                            <button type="button" aria-label="Increase quantity" @click="quantity = Math.min(selectedStock, quantity + 1)" :disabled="quantity >= selectedStock">+</button>
                        </div>
                        <p x-text="selectedStock > 0 ? (selectedStock >= 5 ? '5+ in stock' : selectedStock + ' in stock') : 'Out of stock'">{{ ($initialVariant?->stock ?? $product->stock) > 0 ? (($initialVariant?->stock ?? $product->stock) >= 5 ? '5+ in stock' : ($initialVariant?->stock ?? $product->stock).' in stock') : 'Out of stock' }}</p>
                    </div>
                    <button type="submit" :disabled="selectedStock < 1" @disabled(($initialVariant?->stock ?? $product->stock) < 1)>Add to bag</button>
                </form>

                @if ($errors->has('quantity'))
                    <p class="monricx-product-error">{{ $errors->first('quantity') }}</p>
                @endif

                @if ($product->description)
                    <div class="monricx-product-description">{!! $product->description_is_html ? $product->description : nl2br(e($product->description)) !!}</div>
                @endif
            </div>
        </div>
    </section>

    <section class="monricx-related-products">
        <h2>{{ $product->slug === 'emerald-aura-pendant' ? 'Elegant emerald-green centerpiece' : 'The Details That Shine ✨' }}</h2>
        <div class="monricx-product-grid">
            @foreach ($relatedProducts as $relatedProduct)
                @include('storefront.partials.product-card', ['product' => $relatedProduct])
            @endforeach
        </div>
        @if ($relatedProducts->hasPages())
            <nav class="monricx-related-pagination" aria-label="Related product pages">
                @if ($relatedProducts->onFirstPage())
                    <span aria-hidden="true">‹</span>
                @else
                    <a href="{{ $relatedProducts->previousPageUrl() }}" aria-label="Previous related products">‹</a>
                @endif
                @foreach ($relatedProducts->getUrlRange(1, $relatedProducts->lastPage()) as $page => $url)
                    <a href="{{ $url }}" @class(['is-active' => $page === $relatedProducts->currentPage()]) aria-label="Related product page {{ $page }}">{{ $page }}</a>
                @endforeach
                @if ($relatedProducts->hasMorePages())
                    <a href="{{ $relatedProducts->nextPageUrl() }}" aria-label="Next related products">›</a>
                @else
                    <span aria-hidden="true">›</span>
                @endif
            </nav>
        @endif
    </section>

    <section class="monricx-reviews-empty">
        <h2>Be the first to review</h2>
        <button type="button">Leave review</button>
    </section>
@endsection
