@php
    $cardVariants = $product->variants->where('is_active', true)->values();
    $cardVariant = $cardVariants->firstWhere('is_default', true) ?? $cardVariants->first();
    $cardStock = $cardVariant?->stock ?? $product->stock;
@endphp
<article class="monricx-product-card">
    <a href="{{ route('product.show', $product->slug) }}" class="monricx-product-card__link">
        <div class="monricx-product-card__image">
            @if ($product->badge)
                <span>{{ $product->badge }}</span>
            @endif
            @if ($product->images->first())
                <img src="{{ asset('storage/'.$product->images->first()->path) }}" alt="{{ $product->images->first()->alt_text ?: $product->title }}">
            @endif
        </div>
        <h2>{{ $product->title }}</h2>
        <div class="monricx-product-card__price">
            @if ($product->discounted_price_paise !== null)
                <span>₹{{ number_format($product->price_paise / 100, 2) }}</span>
            @endif
            <strong>₹{{ number_format($product->effectivePricePaise() / 100, 2) }}</strong>
        </div>
    </a>
    <form action="{{ route('cart.items.store') }}" method="post">
        @csrf
        <input type="hidden" name="product_id" value="{{ $product->id }}">
        @if ($cardVariant)
            <input type="hidden" name="product_variant_id" value="{{ $cardVariant->id }}">
        @endif
        <input type="hidden" name="quantity" value="1">
        <button type="submit" @disabled($cardStock < 1)>Add to bag</button>
    </form>
</article>
