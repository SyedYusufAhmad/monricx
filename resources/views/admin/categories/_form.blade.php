@php
    $editing = $category->exists;
@endphp

<div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_340px]">
    <div class="space-y-6">
        <section class="rounded-xl bg-white p-6 shadow-sm">
            <h2 class="font-semibold">Category details</h2>
            <div class="mt-6 space-y-5">
                <div>
                    <label for="name" class="mb-2 block text-sm">Name</label>
                    <input id="name" name="name" value="{{ old('name', $category->name ?? '') }}" required maxlength="255" class="w-full rounded-lg border border-black/20 px-4 py-3" placeholder="e.g. Anklets">
                    @error('name')<p class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="subtitle" class="mb-2 block text-sm">Subtitle <span class="text-black/40">(optional)</span></label>
                    <input id="subtitle" name="subtitle" value="{{ old('subtitle', $category->subtitle ?? '') }}" maxlength="255" class="w-full rounded-lg border border-black/20 px-4 py-3" placeholder="e.g. Everyday silver anklets">
                    @error('subtitle')<p class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="description" class="mb-2 block text-sm">Description <span class="text-black/40">(optional)</span></label>
                    <textarea id="description" name="description" rows="4" class="w-full rounded-lg border border-black/20 px-4 py-3">{{ old('description', $category->description ?? '') }}</textarea>
                    @error('description')<p class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror
                </div>
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="sort_order" class="mb-2 block text-sm">Sort order</label>
                        <input id="sort_order" name="sort_order" type="number" min="0" value="{{ old('sort_order', $category->sort_order ?? 0) }}" class="w-full rounded-lg border border-black/20 px-4 py-3">
                        <p class="mt-1 text-xs text-black/45">Lower numbers appear first.</p>
                        @error('sort_order')<p class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror
                    </div>
                    <div class="flex items-end pb-1">
                        <label class="flex cursor-pointer items-center gap-3 text-sm">
                            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $category->is_active ?? true)) class="h-5 w-5 accent-black">
                            Active
                        </label>
                    </div>
                </div>
                @error('is_active')<p class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>
        </section>
    </div>

    <div class="space-y-6">
        <section class="rounded-xl bg-white p-6 shadow-sm">
            <h2 class="font-semibold">Image <span class="font-normal text-black/40">(optional)</span></h2>
            @if ($editing && $category->image_path)
                <img src="{{ asset('storage/'.$category->image_path) }}" alt="" class="mt-4 aspect-square w-full rounded-lg object-cover">
            @endif
            <div class="mt-4">
                <input id="image" name="image" type="file" accept="image/*" class="w-full text-sm">
                <p class="mt-1 text-xs text-black/45">JPG or PNG, up to 2 MB. Replaces the current image.</p>
                @error('image')<p class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>
        </section>

        @if ($editing)
            <section class="rounded-xl bg-white p-6 shadow-sm">
                <h2 class="font-semibold">Usage</h2>
                <p class="mt-2 text-sm text-black/60">{{ number_format($category->products_count ?? 0) }} product(s) assigned.</p>
                <p class="mt-1 text-xs text-black/45">Categories with products cannot be deleted.</p>
            </section>
        @endif

        <button type="submit" class="w-full rounded-lg bg-[#080808] px-5 py-3 text-sm text-white">{{ $editing ? 'Save changes' : 'Create category' }}</button>
    </div>
</div>
