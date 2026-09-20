<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockReservation;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockReservationService
{
    public function reserve(Order $order): void
    {
        DB::transaction(function () use ($order): void {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);
            $items = OrderItem::query()
                ->where('order_id', $lockedOrder->id)
                ->orderBy('product_id')
                ->orderBy('product_variant_id')
                ->get();
            $productIds = $items->pluck('product_id')->filter()->values();
            $expiresAt = now()->addMinutes(config('monricx.stock_reservation_minutes'));
            $touchedReservationIds = [];

            if ($items->isEmpty() || $productIds->count() !== $items->count()) {
                throw ValidationException::withMessages([
                    'cart' => 'One or more products are no longer available.',
                ]);
            }

            foreach ($items as $item) {
                $product = Product::query()
                    ->published()
                    ->lockForUpdate()
                    ->find($item->product_id);

                if (! $product) {
                    throw ValidationException::withMessages([
                        'cart' => 'One or more products are no longer available.',
                    ]);
                }

                $variant = $item->product_variant_id
                    ? ProductVariant::query()
                        ->where('product_id', $product->id)
                        ->where('is_active', true)
                        ->lockForUpdate()
                        ->find($item->product_variant_id)
                    : null;

                if ($item->product_variant_id && ! $variant) {
                    throw ValidationException::withMessages([
                        'cart' => 'One or more product options are no longer available.',
                    ]);
                }

                $expiredReservations = StockReservation::query()
                    ->where('product_id', $product->id)
                    ->whereNull('committed_at')
                    ->whereNull('released_at')
                    ->where('expires_at', '<=', now());
                $variant
                    ? $expiredReservations->where('product_variant_id', $variant->id)
                    : $expiredReservations->whereNull('product_variant_id');
                $expiredReservations->update(['released_at' => now()]);

                $otherReservations = StockReservation::query()
                    ->where('product_id', $product->id)
                    ->where('order_id', '!=', $lockedOrder->id)
                    ->whereNull('committed_at')
                    ->whereNull('released_at')
                    ->where('expires_at', '>', now());
                $variant
                    ? $otherReservations->where('product_variant_id', $variant->id)
                    : $otherReservations->whereNull('product_variant_id');
                $reservedByOtherOrders = (int) $otherReservations->sum('quantity');
                $availableStock = $variant?->stock ?? $product->stock;

                if ($item->quantity > ($availableStock - $reservedByOtherOrders)) {
                    throw ValidationException::withMessages([
                        'cart' => 'One or more products are no longer available in the requested quantity.',
                    ]);
                }

                $reservation = StockReservation::query()->updateOrCreate(
                    [
                        'order_id' => $lockedOrder->id,
                        'product_id' => $product->id,
                        'product_variant_id' => $variant?->id,
                    ],
                    [
                        'quantity' => $item->quantity,
                        'expires_at' => $expiresAt,
                        'committed_at' => null,
                        'released_at' => null,
                    ]
                );
                $touchedReservationIds[] = $reservation->id;
            }

            StockReservation::query()
                ->where('order_id', $lockedOrder->id)
                ->whereNotIn('id', $touchedReservationIds)
                ->whereNull('committed_at')
                ->whereNull('released_at')
                ->update(['released_at' => now()]);

            $lockedOrder->forceFill(['status' => 'payment_pending'])->save();
        });
    }

    public function release(Order $order): void
    {
        StockReservation::query()
            ->where('order_id', $order->id)
            ->whereNull('committed_at')
            ->whereNull('released_at')
            ->update(['released_at' => now()]);
    }
}
