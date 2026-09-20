@php
    $editing = isset($product);
    $hasMultipleVariants = $editing && $product->variants->count() > 1;
@endphp

<div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_340px]">
    <div class="space-y-6">
        <section class="rounded-xl bg-white p-6 shadow-sm">
            <h2 class="font-semibold">Product information</h2>
            <div class="mt-6 space-y-5">
                <div>
                    <label for="title" class="mb-2 block text-sm">Title</label>
                    <input id="title" name="title" value="{{ old('title', $product->title ?? '') }}" required maxlength="255" class="w-full rounded-lg border border-black/20 px-4 py-3">
                    @error('title')<p class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="subtitle" class="mb-2 block text-sm">Subtitle</label>
                    <input id="subtitle" name="subtitle" value="{{ old('subtitle', $product->subtitle ?? '') }}" maxlength="255" class="w-full rounded-lg border border-black/20 px-4 py-3">
                    @error('subtitle')<p class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="description" class="mb-2 block text-sm">Description</label>
                    <textarea id="description" name="description" rows="12" required maxlength="50000" class="w-full rounded-lg border border-black/20 px-4 py-3">{{ old('description', $product->description ?? '') }}</textarea>
                    @error('description')<p class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        <section class="rounded-xl bg-white p-6 shadow-sm">
            <h2 class="font-semibold">Images</h2>
            <p class="mt-1 text-sm text-black/50">JPEG, PNG, or WebP. Up to 5 MB per image and 8 images per upload.</p>

            @if ($editing && $product->images->isNotEmpty())
                <div class="mt-5 grid grid-cols-2 gap-4 sm:grid-cols-3">
                    @foreach ($product->images as $image)
                        <label class="overflow-hidden rounded-lg border border-black/10">
                            <img class="aspect-square w-full object-cover" src="{{ asset('storage/'.$image->path) }}" alt="{{ $image->alt_text }}">
                            <span class="flex items-center gap-2 px-3 py-2 text-xs"><input type="checkbox" name="remove_images[]" value="{{ $image->id }}"> Remove</span>
                        </label>
                    @endforeach
                </div>
            @endif

            <input class="mt-5 block w-full rounded-lg border border-black/20 px-4 py-3 text-sm" type="file" name="images[]" multiple accept="image/jpeg,image/png,image/webp">
            @error('images')<p class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror
            @error('images.*')<p class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror
        </section>
    </div>

    <aside class="space-y-6">
        <section class="rounded-xl bg-white p-6 shadow-sm">
            <h2 class="font-semibold">Pricing and stock</h2>
            <div class="mt-5 space-y-5">
                <div>
                    <label for="price" class="mb-2 block text-sm">Price (₹)</label>
                    <input id="price" name="price" type="number" min="0.01" max="9999999.99" step="0.01" value="{{ old('price', $editing ? number_format($product->price_paise / 100, 2, '.', '') : '') }}" required class="w-full rounded-lg border border-black/20 px-4 py-3">
                    @error('price')<p class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="discounted_price" class="mb-2 block text-sm">Discounted price (₹)</label>
                    <input id="discounted_price" name="discounted_price" type="number" min="0.01" max="9999999.99" step="0.01" value="{{ old('discounted_price', $editing && $product->discounted_price_paise !== null ? number_format($product->discounted_price_paise / 100, 2, '.', '') : '') }}" class="w-full rounded-lg border border-black/20 px-4 py-3">
                    @error('discounted_price')<p class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="stock" class="mb-2 block text-sm">Stock</label>
                    <input id="stock" name="stock" type="number" min="0" step="1" value="{{ old('stock', $product->stock ?? 0) }}" required @readonly($hasMultipleVariants) class="w-full rounded-lg border border-black/20 px-4 py-3">
                    @if ($hasMultipleVariants)
                        <p class="mt-1 text-xs text-black/50">Calculated from the variant quantities below.</p>
                    @endif
                    @error('stock')<p class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        @if ($hasMultipleVariants)
            <section class="rounded-xl bg-white p-6 shadow-sm">
                <h2 class="font-semibold">Variants</h2>
                <p class="mt-1 text-sm text-black/50">Manage each original size, price, and stock quantity separately.</p>
                <div class="mt-5 space-y-6">
                    @foreach ($product->variants as $variant)
                        <fieldset class="space-y-4 rounded-lg border border-black/10 p-4">
                            <legend class="px-2 text-sm font-semibold">{{ $variant->title }}</legend>
                            <input type="hidden" name="variants[{{ $variant->id }}][id]" value="{{ $variant->id }}">
                            <div>
                                <label class="mb-2 block text-sm" for="variant-{{ $variant->id }}-price">Price (₹)</label>
                                <input id="variant-{{ $variant->id }}-price" name="variants[{{ $variant->id }}][price]" type="number" min="0.01" max="9999999.99" step="0.01" value="{{ old("variants.{$variant->id}.price", number_format($variant->price_paise / 100, 2, '.', '')) }}" required class="w-full rounded-lg border border-black/20 px-4 py-3">
                            </div>
                            <div>
                                <label class="mb-2 block text-sm" for="variant-{{ $variant->id }}-discounted-price">Discounted price (₹)</label>
                                <input id="variant-{{ $variant->id }}-discounted-price" name="variants[{{ $variant->id }}][discounted_price]" type="number" min="0.01" max="9999999.99" step="0.01" value="{{ old("variants.{$variant->id}.discounted_price", $variant->discounted_price_paise !== null ? number_format($variant->discounted_price_paise / 100, 2, '.', '') : '') }}" class="w-full rounded-lg border border-black/20 px-4 py-3">
                            </div>
                            <div>
                                <label class="mb-2 block text-sm" for="variant-{{ $variant->id }}-stock">Stock</label>
                                <input id="variant-{{ $variant->id }}-stock" name="variants[{{ $variant->id }}][stock]" type="number" min="0" step="1" value="{{ old("variants.{$variant->id}.stock", $variant->stock) }}" required class="w-full rounded-lg border border-black/20 px-4 py-3">
                            </div>
                        </fieldset>
                    @endforeach
                </div>
                @error('variants')<p class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror
            </section>
        @endif

        <section class="rounded-xl bg-white p-6 shadow-sm">
            <h2 class="font-semibold">Organisation</h2>
            <div class="mt-5 space-y-5">
                <div>
                    <label for="category_id" class="mb-2 block text-sm">Category</label>
                    <select id="category_id" name="category_id" required class="w-full rounded-lg border border-black/20 bg-white px-4 py-3">
                        <option value="">Select category</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected((int) old('category_id', $product->category_id ?? 0) === $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                    @error('category_id')<p class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="status" class="mb-2 block text-sm">Status</label>
                    <select id="status" name="status" required class="w-full rounded-lg border border-black/20 bg-white px-4 py-3">
                        <option value="draft" @selected(old('status', $product->status ?? 'draft') === 'draft')>Draft</option>
                        <option value="published" @selected(old('status', $product->status ?? 'draft') === 'published')>Published</option>
                    </select>
                    @error('status')<p class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        <button type="submit" class="w-full rounded-lg bg-[#080808] px-5 py-3 text-white">{{ $editing ? 'Save changes' : 'Create product' }}</button>
    </aside>
</div>
