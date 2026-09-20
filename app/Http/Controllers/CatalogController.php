<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function index(Request $request): View
    {
        return $this->catalog($request);
    }

    public function category(Request $request, string $category): View
    {
        return $this->catalog($request, $category);
    }

    private function catalog(Request $request, ?string $category = null): View
    {
        $sort = $request->string('sort')->toString();
        $query = $request->string('q')->trim()->toString();

        $products = Product::query()
            ->published()
            ->with(['images', 'category', 'variants'])
            ->when($category !== null, fn ($builder) => $builder->whereHas(
                'category',
                fn ($categoryQuery) => $categoryQuery->where('slug', $category)
            ))
            ->when($query !== '', fn ($builder) => $builder->where(function ($productQuery) use ($query) {
                $productQuery
                    ->where('title', 'like', '%'.$query.'%')
                    ->orWhere('subtitle', 'like', '%'.$query.'%');
            }))
            ->when($sort === 'price-asc', fn ($query) => $query->orderByRaw('COALESCE(discounted_price_paise, price_paise) ASC'))
            ->when($sort === 'price-desc', fn ($query) => $query->orderByRaw('COALESCE(discounted_price_paise, price_paise) DESC'))
            ->when($sort === 'recent', fn ($query) => $query->latest('published_at'))
            ->when(! in_array($sort, ['price-asc', 'price-desc', 'recent'], true), fn ($query) => $query->orderBy('sort_order'))
            ->get();

        return view('storefront.shop', compact('products', 'sort', 'query'));
    }
}
