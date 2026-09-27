<?php

namespace App\Http\Controllers;

use App\Services\CartService;
use App\Services\DiscountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CheckoutDiscountController extends Controller
{
    public function store(Request $request, CartService $cartService, DiscountService $discounts): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max: 32'],
        ]);

        $summary = $cartService->summary($request);

        [$discount, $error] = $discounts->validate($validated['code'], (int) $summary['subtotal_paise']);

        if (! $discount) {
            return redirect()->route('checkout')->with('discount_error', $error);
        }

        $request->session()->put(DiscountService::SESSION_KEY, $discount->code);

        return redirect()->route('checkout')->with('discount_success', "Code {$discount->code} applied.");
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->session()->forget(DiscountService::SESSION_KEY);

        return redirect()->route('checkout');
    }
}
