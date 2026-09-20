<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class StorefrontController extends Controller
{
    public function __invoke(Request $request): View
    {
        $sort = $request->string('sort')->toString();

        $products = Product::query()
            ->published()
            ->with(['images', 'category', 'variants'])
            ->when($sort === 'price-asc', fn ($query) => $query->orderByRaw('COALESCE(discounted_price_paise, price_paise) ASC'))
            ->when($sort === 'price-desc', fn ($query) => $query->orderByRaw('COALESCE(discounted_price_paise, price_paise) DESC'))
            ->when(! in_array($sort, ['price-asc', 'price-desc'], true), fn ($query) => $query->orderBy('sort_order'))
            ->paginate(18)
            ->withQueryString();

        return view('storefront.home', compact('products', 'sort'));
    }
}
