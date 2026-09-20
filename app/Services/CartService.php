<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CartService
{
    private const SESSION_KEY = 'monricx_cart_id';

    public function current(Request $request, bool $create = false): ?Cart
    {
        $cartId = $request->session()->get(self::SESSION_KEY);
        $sessionFingerprint = $this->sessionFingerprint($request);

        $cart = $cartId
            ? Cart::query()
                ->whereKey($cartId)
                ->where(fn ($query) => $query
                    ->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now()))
                ->first()
            : null;

        if ($cart || ! $create) {
            return $cart;
        }

        $cart = Cart::query()->create([
            'session_id' => $sessionFingerprint,
            'currency' => 'INR',
            'expires_at' => now()->addDays(30),
        ]);

        $request->session()->put(self::SESSION_KEY, $cart->id);

        return $cart;
    }

    /**
     * @return array{cart: ?Cart, items: Collection<int, CartItem>, item_count: int, subtotal_paise: int}
     */
    public function summary(Request $request): array
    {
        $cart = $this->current($request);

        if (! $cart) {
            return [
                'cart' => null,
                'items' => collect(),
                'item_count' => 0,
                'subtotal_paise' => 0,
            ];
        }

        $items = $cart->items()
            ->with(['product.images', 'variant'])
            ->orderBy('id')
            ->get()
            ->filter(fn (CartItem $item) => $item->product !== null)
            ->values();

        return [
            'cart' => $cart,
            'items' => $items,
            'item_count' => (int) $items->sum('quantity'),
            'subtotal_paise' => (int) $items->sum(
                fn (CartItem $item) => $item->quantity * $item->unit_price_paise
            ),
        ];
    }

    public function add(Request $request, int $productId, int $quantity, ?int $variantId = null): void
    {
        DB::transaction(function () use ($request, $productId, $quantity, $variantId): void {
            $product = Product::query()
                ->published()
                ->lockForUpdate()
                ->findOrFail($productId);
            $variant = $this->selectedVariant($product, $variantId);

            $cart = $this->current($request, true);
            $itemQuery = $cart->items()->where('product_id', $product->id);
            $variant
                ? $itemQuery->where('product_variant_id', $variant->id)
                : $itemQuery->whereNull('product_variant_id');
            $item = $itemQuery->lockForUpdate()->first();
            $newQuantity = ($item?->quantity ?? 0) + $quantity;

            $this->ensureStock($product, $newQuantity, $variant);

            $cart->items()->updateOrCreate(
                [
                    'product_id' => $product->id,
                    'product_variant_id' => $variant?->id,
                ],
                [
                    'quantity' => $newQuantity,
                    'unit_price_paise' => $variant?->effectivePricePaise() ?? $product->effectivePricePaise(),
                ]
            );

            $cart->forceFill(['expires_at' => now()->addDays(30)])->save();
        });
    }

    public function update(Request $request, int $itemId, int $quantity): void
    {
        DB::transaction(function () use ($request, $itemId, $quantity): void {
            $cart = $this->current($request);

            abort_unless($cart, 404);

            $item = $cart->items()->whereKey($itemId)->lockForUpdate()->firstOrFail();
            $product = Product::query()->published()->lockForUpdate()->findOrFail($item->product_id);
            $variant = $item->product_variant_id
                ? ProductVariant::query()
                    ->where('product_id', $product->id)
                    ->where('is_active', true)
                    ->lockForUpdate()
                    ->findOrFail($item->product_variant_id)
                : null;

            $this->ensureStock($product, $quantity, $variant);

            $item->update([
                'quantity' => $quantity,
                'unit_price_paise' => $variant?->effectivePricePaise() ?? $product->effectivePricePaise(),
            ]);
            $cart->forceFill(['expires_at' => now()->addDays(30)])->save();
        });
    }

    public function remove(Request $request, int $itemId): void
    {
        $cart = $this->current($request);

        abort_unless($cart, 404);

        $cart->items()->whereKey($itemId)->delete();
        $cart->forceFill(['expires_at' => now()->addDays(30)])->save();
    }

    public function clear(Request $request): void
    {
        $cart = $this->current($request);

        if (! $cart) {
            return;
        }

        $cart->items()->delete();
        $cart->forceFill(['expires_at' => now()->addDays(30)])->save();
    }

    private function selectedVariant(Product $product, ?int $variantId): ?ProductVariant
    {
        $variants = ProductVariant::query()
            ->where('product_id', $product->id)
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('sort_order')
            ->lockForUpdate()
            ->get();

        if ($variants->isEmpty()) {
            if ($variantId !== null) {
                throw ValidationException::withMessages([
                    'product_variant_id' => 'The selected product option is not available.',
                ]);
            }

            return null;
        }

        if ($variantId === null && $variants->count() > 1) {
            throw ValidationException::withMessages([
                'product_variant_id' => 'Please select a product option.',
            ]);
        }

        $variant = $variantId !== null
            ? $variants->firstWhere('id', $variantId)
            : $variants->first();

        if (! $variant) {
            throw ValidationException::withMessages([
                'product_variant_id' => 'The selected product option is not available.',
            ]);
        }

        return $variant;
    }

    private function ensureStock(Product $product, int $quantity, ?ProductVariant $variant = null): void
    {
        $stock = $variant?->stock ?? $product->stock;

        if ($quantity < 1 || $quantity > $stock) {
            throw ValidationException::withMessages([
                'quantity' => 'The requested quantity is not available.',
            ]);
        }
    }

    private function sessionFingerprint(Request $request): string
    {
        return hash('sha256', $request->session()->getId());
    }
}
