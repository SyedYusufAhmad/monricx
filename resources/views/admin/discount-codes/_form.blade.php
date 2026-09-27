@php
    $editing = $code->exists;
    $type = old('type', $code->type ?? 'percentage');
    $valueDisplay = old('value', $editing
        ? ($code->type === 'percentage' ? $code->value : $code->value / 100)
        : '');
@endphp

<div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_340px]">
    <div class="space-y-6">
        <section class="rounded-xl bg-white p-6 shadow-sm">
            <h2 class="font-semibold">Code details</h2>
            <div class="mt-6 space-y-5">
                <div>
                    <label for="code" class="mb-2 block text-sm">Code</label>
                    <input id="code" name="code" value="{{ old('code', $code->code ?? '') }}" required maxlength="32" class="w-full rounded-lg border border-black/20 px-4 py-3 font-mono uppercase" placeholder="e.g. DIWALI10">
                    <p class="mt-1 text-xs text-black/45">Customers type this at checkout. Stored in uppercase.</p>
                    @error('code')<p class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror
                </div>
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="type" class="mb-2 block text-sm">Discount type</label>
                        <select id="type" name="type" class="w-full rounded-lg border border-black/20 bg-white px-4 py-3">
                            <option value="percentage" @selected($type === 'percentage')>Percentage off</option>
                            <option value="fixed" @selected($type === 'fixed')>Fixed amount off</option>
                        </select>
                        @error('type')<p class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="value" class="mb-2 block text-sm"><span id="value-label">{{ $type === 'fixed' ? 'Amount (₹)' : 'Percentage (%)' }}</span></label>
                        <input id="value" name="value" type="number" step="any" min="1" value="{{ $valueDisplay }}" required class="w-full rounded-lg border border-black/20 px-4 py-3" placeholder="{{ $type === 'fixed' ? 'e.g. 200' : 'e.g. 10' }}">
                        @error('value')<p class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="minimum_order_rupees" class="mb-2 block text-sm">Minimum order (₹)</label>
                        <input id="minimum_order_rupees" name="minimum_order_rupees" type="number" step="any" min="0" value="{{ old('minimum_order_rupees', $editing ? $code->minimum_order_paise / 100 : '') }}" class="w-full rounded-lg border border-black/20 px-4 py-3" placeholder="No minimum">
                        @error('minimum_order_rupees')<p class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="maximum_discount_rupees" class="mb-2 block text-sm">Max. discount (₹)</label>
                        <input id="maximum_discount_rupees" name="maximum_discount_rupees" type="number" step="any" min="1" value="{{ old('maximum_discount_rupees', $editing && $code->maximum_discount_paise ? $code->maximum_discount_paise / 100 : '') }}" class="w-full rounded-lg border border-black/20 px-4 py-3" placeholder="No cap">
                        <p class="mt-1 text-xs text-black/45">Caps percentage discounts. Ignored for fixed amounts.</p>
                        @error('maximum_discount_rupees')<p class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>
        </section>

        <section class="rounded-xl bg-white p-6 shadow-sm">
            <h2 class="font-semibold">Usage limits</h2>
            <div class="mt-6 grid gap-5 sm:grid-cols-3">
                <div>
                    <label for="usage_limit" class="mb-2 block text-sm">Usage limit</label>
                    <input id="usage_limit" name="usage_limit" type="number" min="1" value="{{ old('usage_limit', $code->usage_limit ?? '') }}" class="w-full rounded-lg border border-black/20 px-4 py-3" placeholder="Unlimited">
                    @error('usage_limit')<p class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="starts_at" class="mb-2 block text-sm">Starts at</label>
                    <input id="starts_at" name="starts_at" type="datetime-local" value="{{ old('starts_at', $editing && $code->starts_at ? $code->starts_at->format('Y-m-d\TH:i') : '') }}" class="w-full rounded-lg border border-black/20 px-4 py-3">
                    @error('starts_at')<p class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="ends_at" class="mb-2 block text-sm">Ends at</label>
                    <input id="ends_at" name="ends_at" type="datetime-local" value="{{ old('ends_at', $editing && $code->ends_at ? $code->ends_at->format('Y-m-d\TH:i') : '') }}" class="w-full rounded-lg border border-black/20 px-4 py-3">
                    @error('ends_at')<p class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror
                </div>
            </div>
            @if ($editing)
                <p class="mt-4 text-sm text-black/55">Used {{ number_format($code->used_count) }} time(s) so far.</p>
            @endif
        </section>
    </div>

    <aside class="space-y-6">
        <section class="rounded-xl bg-white p-6 shadow-sm">
            <h2 class="font-semibold">Visibility</h2>
            <label class="mt-4 flex items-center gap-3 text-sm">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $code->is_active ?? true)) class="h-5 w-5">
                Active — customers can use this code
            </label>
            <button type="submit" class="mt-6 w-full rounded-lg bg-[#080808] px-5 py-3 text-sm text-white">{{ $editing ? 'Save changes' : 'Create code' }}</button>
        </section>
    </aside>
</div>

<script>
    document.getElementById('type').addEventListener('change', function () {
        var fixed = this.value === 'fixed';
        document.getElementById('value-label').textContent = fixed ? 'Amount (₹)' : 'Percentage (%)';
        document.getElementById('value').placeholder = fixed ? 'e.g. 200' : 'e.g. 10';
    });
</script>
