<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Contracts\View\View;

class ProductController extends Controller
{
    public function __invoke(string $productSlug): View
    {
        $product = Product::query()
            ->published()
            ->with(['images', 'category', 'variants'])
            ->where('slug', $productSlug)
            ->firstOrFail();

        $relatedProducts = Product::query()
            ->published()
            ->with('images')
            ->whereKeyNot($product->id)
            ->when(
                $product->category_id,
                fn ($query) => $query->where('category_id', $product->category_id)
            )
            ->orderByDesc('sort_order')
            ->paginate(4, ['*'], 'related_page')
            ->withQueryString();

        return view('storefront.product', compact('product', 'relatedProducts'));
    }
}
