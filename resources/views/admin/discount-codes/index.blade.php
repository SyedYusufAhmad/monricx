@extends('layouts.admin', ['title' => 'Discount codes'])

@section('content')
    <div class="flex flex-wrap items-end justify-between gap-5">
        <div>
            <p class="text-sm text-black/55">Marketing</p>
            <h1 class="mt-1 font-['Bodoni_Moda'] text-4xl">Discount codes</h1>
        </div>
        <a href="{{ route('admin.discount-codes.create') }}" class="rounded-lg bg-[#080808] px-5 py-3 text-sm text-white">Add discount code</a>
    </div>

    @if (session('status'))
        <p class="mt-6 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</p>
    @endif

    <section class="mt-6 overflow-hidden rounded-xl bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[820px] text-left text-sm">
                <thead class="bg-black/[0.03] text-black/55"><tr><th class="px-5 py-3">Code</th><th class="px-5 py-3">Discount</th><th class="px-5 py-3">Min. order</th><th class="px-5 py-3">Uses</th><th class="px-5 py-3">Valid period</th><th class="px-5 py-3">Status</th><th class="px-5 py-3"><span class="sr-only">Actions</span></th></tr></thead>
                <tbody class="divide-y divide-black/10">
                    @forelse ($codes as $discountCode)
                        <tr>
                            <td class="px-5 py-4"><span class="font-mono font-semibold">{{ $discountCode->code }}</span></td>
                            <td class="px-5 py-4">
                                @if ($discountCode->type === 'percentage')
                                    {{ $discountCode->value }}%
                                    @if ($discountCode->maximum_discount_paise) <span class="text-xs text-black/45">(max ₹{{ number_format($discountCode->maximum_discount_paise / 100, 2) }})</span>@endif
                                @else
                                    ₹{{ number_format($discountCode->value / 100, 2) }}
                                @endif
                            </td>
                            <td class="px-5 py-4">₹{{ number_format($discountCode->minimum_order_paise / 100, 2) }}</td>
                            <td class="px-5 py-4">{{ number_format($discountCode->used_count) }}{{ $discountCode->usage_limit ? ' / '.number_format($discountCode->usage_limit) : '' }}</td>
                            <td class="px-5 py-4 text-black/60">
                                {{ $discountCode->starts_at?->format('d M Y') ?? '—' }} → {{ $discountCode->ends_at?->format('d M Y') ?? '—' }}
                            </td>
                            <td class="px-5 py-4">
                                <span @class(['rounded-full px-2.5 py-1 text-xs', 'bg-emerald-50 text-emerald-800' => $discountCode->isCurrentlyValid(), 'bg-black/5 text-black/60' => ! $discountCode->isCurrentlyValid()])>{{ $discountCode->isCurrentlyValid() ? 'Active' : 'Inactive' }}</span>
                            </td>
                            <td class="px-5 py-4 text-right"><a href="{{ route('admin.discount-codes.edit', $discountCode) }}" class="underline decoration-black/20 underline-offset-4">Edit</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-6 py-12 text-center text-black/50">No discount codes yet. Create one to offer promotions at checkout.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($codes->hasPages())<div class="border-t border-black/10 px-6 py-4">{{ $codes->links() }}</div>@endif
    </section>
@endsection
