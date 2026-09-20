<?php

namespace App\Http\Controllers;

use App\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function store(Request $request, CartService $cartService): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer'],
            'product_variant_id' => ['nullable', 'integer'],
            'quantity' => ['required', 'integer', 'min:1', 'max:99'],
        ]);

        $cartService->add(
            $request,
            $validated['product_id'],
            $validated['quantity'],
            $validated['product_variant_id'] ?? null,
        );

        return back()->with('cart_open', true);
    }

    public function update(Request $request, int $item, CartService $cartService): RedirectResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:99'],
        ]);

        $cartService->update($request, $item, $validated['quantity']);

        return back()->with('cart_open', true);
    }

    public function destroy(Request $request, int $item, CartService $cartService): RedirectResponse
    {
        $cartService->remove($request, $item);

        return back()->with('cart_open', true);
    }
}
