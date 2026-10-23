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
                    <label for="sku" class="mb-2 block text-sm">SKU number <span class="text-black/50">(optional)</span></label>
                    <input id="sku" name="sku" value="{{ old('sku', $product->sku ?? '') }}" maxlength="255" class="w-full rounded-lg border border-black/20 px-4 py-3">
                    <p class="mt-1 text-xs text-black/50">Optional. Leave blank if the product has no SKU number.</p>
                    @error('sku')<p class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror
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

            <input class="mt-5 block w-full rounded-lg border border-black/20 px-4 py-3" type="file" name="images[]" multiple accept="image/jpeg,image/png,image/webp">
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
                    <div class="mb-2 flex items-center justify-between gap-3">
                        <label for="category_id" class="block text-sm">Category</label>
                        <button type="button" id="open-category-modal" class="shrink-0 text-sm underline decoration-black/20 underline-offset-4">+ Add new category</button>
                    </div>
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

<div id="category-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
    <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-lg" role="dialog" aria-modal="true" aria-labelledby="category-modal-title">
        <div class="flex items-start justify-between gap-4">
            <h3 id="category-modal-title" class="text-lg font-semibold">Add new category</h3>
            <button type="button" id="close-category-modal" class="text-2xl leading-none text-black/40" aria-label="Close">&times;</button>
        </div>
        <div class="mt-4 space-y-4">
            <div>
                <label for="modal-category-name" class="mb-2 block text-sm">Name</label>
                <input id="modal-category-name" maxlength="255" class="w-full rounded-lg border border-black/20 px-4 py-3" placeholder="e.g. Anklets">
                <p class="modal-error mt-1 hidden text-sm text-red-700" data-error-for="name"></p>
            </div>
            <div>
                <label for="modal-category-description" class="mb-2 block text-sm">Description <span class="text-black/40">(optional)</span></label>
                <textarea id="modal-category-description" rows="3" class="w-full rounded-lg border border-black/20 px-4 py-3"></textarea>
                <p class="modal-error mt-1 hidden text-sm text-red-700" data-error-for="description"></p>
            </div>
            <div>
                <label for="modal-category-image" class="mb-2 block text-sm">Image <span class="text-black/40">(optional)</span></label>
                <input id="modal-category-image" type="file" accept="image/*" class="w-full text-sm">
                <p class="modal-error mt-1 hidden text-sm text-red-700" data-error-for="image"></p>
            </div>
            <label class="flex cursor-pointer items-center gap-3 text-sm">
                <input id="modal-category-active" type="checkbox" value="1" checked class="h-5 w-5 accent-black">
                Active
            </label>
            <p id="category-modal-general-error" class="hidden text-sm text-red-700"></p>
            <div class="flex justify-end gap-3 pt-2">
                <button type="button" id="cancel-category-modal" class="rounded-lg border border-black/20 px-5 py-2.5 text-sm">Cancel</button>
                <button type="button" id="category-modal-submit" class="rounded-lg bg-[#080808] px-5 py-2.5 text-sm text-white">Save category</button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var modal = document.getElementById('category-modal');
    var select = document.getElementById('category_id');
    var nameInput = document.getElementById('modal-category-name');
    var descriptionInput = document.getElementById('modal-category-description');
    var imageInput = document.getElementById('modal-category-image');
    var activeInput = document.getElementById('modal-category-active');
    var submitBtn = document.getElementById('category-modal-submit');
    var generalError = document.getElementById('category-modal-general-error');
    var csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var storeUrl = "{{ route('admin.categories.store') }}";

    function clearErrors() {
        modal.querySelectorAll('.modal-error').forEach(function (el) {
            el.textContent = '';
            el.classList.add('hidden');
        });
        generalError.textContent = '';
        generalError.classList.add('hidden');
    }

    function resetModal() {
        nameInput.value = '';
        descriptionInput.value = '';
        imageInput.value = '';
        activeInput.checked = true;
        clearErrors();
    }

    function openModal() {
        resetModal();
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        nameInput.focus();
    }

    function closeModal() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        resetModal();
    }

    function showErrors(errors) {
        clearErrors();
        Object.keys(errors).forEach(function (field) {
            var el = modal.querySelector('[data-error-for="' + field + '"]');
            if (el && errors[field] && errors[field][0]) {
                el.textContent = errors[field][0];
                el.classList.remove('hidden');
            }
        });
    }

    function showGeneralError(message) {
        generalError.textContent = message || 'Could not save the category. Please try again.';
        generalError.classList.remove('hidden');
    }

    document.getElementById('open-category-modal').addEventListener('click', openModal);
    document.getElementById('close-category-modal').addEventListener('click', closeModal);
    document.getElementById('cancel-category-modal').addEventListener('click', closeModal);
    modal.addEventListener('click', function (event) {
        if (event.target === modal) closeModal();
    });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !modal.classList.contains('hidden')) closeModal();
    });
    nameInput.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            submitBtn.click();
        }
    });

    submitBtn.addEventListener('click', function () {
        clearErrors();
        submitBtn.disabled = true;
        submitBtn.textContent = 'Saving…';

        var formData = new FormData();
        formData.append('name', nameInput.value);
        formData.append('description', descriptionInput.value);
        if (imageInput.files[0]) formData.append('image', imageInput.files[0]);
        if (activeInput.checked) formData.append('is_active', '1');

        fetch(storeUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: formData,
        })
            .then(function (response) {
                return response.json().then(function (data) {
                    return { status: response.status, data: data };
                });
            })
            .then(function (result) {
                if (result.status === 201 && result.data.id) {
                    var option = document.createElement('option');
                    option.value = result.data.id;
                    option.textContent = result.data.name;
                    select.appendChild(option);
                    select.value = result.data.id;
                    closeModal();
                } else if (result.status === 422 && result.data.errors) {
                    showErrors(result.data.errors);
                } else {
                    showGeneralError(result.data && result.data.message);
                }
            })
            .catch(function () {
                showGeneralError();
            })
            .then(function () {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Save category';
            });
    });
})();
</script>
