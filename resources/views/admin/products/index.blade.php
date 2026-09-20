@extends('layouts.admin', ['title' => 'Products'])

@section('content')
    <div class="flex flex-wrap items-end justify-between gap-5">
        <div>
            <p class="text-sm text-black/55">Catalog</p>
            <h1 class="mt-1 font-['Bodoni_Moda'] text-4xl">Products</h1>
        </div>
        <a href="{{ route('admin.products.create') }}" class="rounded-lg bg-[#080808] px-5 py-3 text-sm text-white">Add product</a>
    </div>

    <form method="get" class="mt-8 flex flex-wrap gap-3 rounded-xl bg-white p-4 shadow-sm">
        <label class="min-w-[240px] flex-1">
            <span class="sr-only">Search products</span>
            <input type="search" name="q" value="{{ $query }}" placeholder="Search title, subtitle, or URL" class="w-full rounded-lg border border-black/20 px-4 py-2.5">
        </label>
        <select name="status" class="rounded-lg border border-black/20 bg-white px-4 py-2.5" aria-label="Product status">
            <option value="">All statuses</option>
            <option value="published" @selected($status === 'published')>Published</option>
            <option value="draft" @selected($status === 'draft')>Draft</option>
        </select>
        <button class="rounded-lg border border-black/20 px-5 py-2.5" type="submit">Filter</button>
    </form>

    <section class="mt-6 overflow-hidden rounded-xl bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[820px] text-left text-sm">
                <thead class="bg-black/[0.03] text-black/55"><tr><th class="px-5 py-3">Product</th><th class="px-5 py-3">Category</th><th class="px-5 py-3">Price</th><th class="px-5 py-3">Stock</th><th class="px-5 py-3">Status</th><th class="px-5 py-3"><span class="sr-only">Actions</span></th></tr></thead>
                <tbody class="divide-y divide-black/10">
                    @forelse ($products as $product)
                        <tr>
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="h-14 w-14 overflow-hidden rounded-lg bg-black/5">
                                        @if ($product->images->first())
                                            <img class="h-full w-full object-cover" src="{{ asset('storage/'.$product->images->first()->path) }}" alt="">
                                        @endif
                                    </div>
                                    <div><p class="font-medium">{{ $product->title }}</p><p class="mt-1 text-xs text-black/45">/{{ $product->slug }}</p></div>
                                </div>
                            </td>
                            <td class="px-5 py-4">{{ $product->category?->name ?? 'Uncategorised' }}</td>
                            <td class="px-5 py-4">
                                @if ($product->discounted_price_paise !== null)<span class="mr-1 text-xs text-black/40 line-through">₹{{ number_format($product->price_paise / 100, 2) }}</span>@endif
                                ₹{{ number_format($product->effectivePricePaise() / 100, 2) }}
                            </td>
                            <td class="px-5 py-4"><span @class(['font-semibold text-red-700' => $product->stock <= 5])>{{ number_format($product->stock) }}</span></td>
                            <td class="px-5 py-4"><span @class(['rounded-full px-2.5 py-1 text-xs', 'bg-emerald-50 text-emerald-800' => $product->status === 'published', 'bg-black/5 text-black/60' => $product->status !== 'published'])>{{ ucfirst($product->status) }}</span></td>
                            <td class="px-5 py-4 text-right"><a href="{{ route('admin.products.edit', $product) }}" class="underline decoration-black/20 underline-offset-4">Edit</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-6 py-12 text-center text-black/50">No products have been added.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($products->hasPages())<div class="border-t border-black/10 px-6 py-4">{{ $products->links() }}</div>@endif
    </section>
@endsection
