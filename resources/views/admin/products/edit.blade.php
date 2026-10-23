@extends('layouts.admin', ['title' => 'Edit product'])

@section('content')
    <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
        <div>
            <a href="{{ route('admin.products.index') }}" class="text-sm text-black/55">← Products</a>
            <h1 class="mt-2 font-['Bodoni_Moda'] text-4xl">Edit product</h1>
        </div>
        @if ($product->status === 'published')
            <a href="{{ route('product.show', $product->slug) }}" target="_blank" rel="noopener" class="text-sm underline decoration-black/20 underline-offset-4">View product</a>
        @endif
    </div>

    <form method="post" action="{{ route('admin.products.update', $product) }}" enctype="multipart/form-data">
        @csrf
        @method('put')
        @include('admin.products._form')
    </form>

    <section class="mt-8 rounded-xl border border-red-900/15 bg-white p-6 shadow-sm">
        <h2 class="font-semibold text-red-800">Archive product</h2>
        <p class="mt-2 text-sm text-black/55">The product disappears from the storefront. Existing order records remain available.</p>
        <form id="archive-product-form" method="post" action="{{ route('admin.products.destroy', $product) }}" class="mt-4">
            @csrf
            @method('delete')
            <button id="archive-product-button" type="submit" class="rounded-lg border border-red-800/30 px-4 py-2 text-sm text-red-800">Archive product</button>
        </form>
        <script>
            (function () {
                var form = document.getElementById('archive-product-form');
                var button = document.getElementById('archive-product-button');
                if (!form || !button) return;
                var armed = false;
                var originalText = button.textContent;
                var resetTimer = null;
                function disarm() {
                    armed = false;
                    button.textContent = originalText;
                    button.classList.remove('bg-red-800', 'text-white');
                    if (resetTimer) { clearTimeout(resetTimer); resetTimer = null; }
                }
                form.addEventListener('submit', function (event) {
                    if (!armed) {
                        event.preventDefault();
                        armed = true;
                        button.textContent = 'Tap again to confirm archive';
                        button.classList.add('bg-red-800', 'text-white');
                        resetTimer = setTimeout(disarm, 10000);
                    }
                });
            })();
        </script>
    </section>
@endsection
