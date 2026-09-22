@extends('layouts.admin', ['title' => 'Orders'])

@section('content')
    <div>
        <p class="text-sm text-black/55">Sales</p>
        <h1 class="mt-1 font-['Bodoni_Moda'] text-4xl">Orders</h1>
    </div>

    <form method="get" class="mt-8 flex flex-wrap gap-3 rounded-xl bg-white p-4 shadow-sm">
        <label class="min-w-[260px] flex-1">
            <span class="sr-only">Search orders</span>
            <input type="search" name="q" value="{{ $query }}" placeholder="Search order, customer, email, or phone" class="w-full rounded-lg border border-black/20 px-4 py-2.5">
        </label>
        <select name="status" class="rounded-lg border border-black/20 bg-white px-4 py-2.5" aria-label="Order status">
            <option value="">All statuses</option>
            @foreach ($statusOptions as $option)
                <option value="{{ $option }}" @selected($status === $option)>{{ str($option)->replace('_', ' ')->title() }}</option>
            @endforeach
        </select>
        <button class="rounded-lg border border-black/20 px-5 py-2.5 text-sm" type="submit">Filter</button>
    </form>

    <section class="mt-6 overflow-hidden rounded-xl bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[980px] text-left text-sm">
                <thead class="bg-black/[0.03] text-black/55"><tr><th class="px-5 py-3">Order</th><th class="px-5 py-3">Customer</th><th class="px-5 py-3">Method</th><th class="px-5 py-3">Payment</th><th class="px-5 py-3">Items</th><th class="px-5 py-3">Total</th><th class="px-5 py-3">Created</th></tr></thead>
                <tbody class="divide-y divide-black/10">
                    @forelse ($orders as $order)
                        <tr>
                            <td class="px-5 py-4"><a href="{{ route('admin.orders.show', $order) }}" class="font-medium underline decoration-black/20 underline-offset-4">{{ $order->order_number }}</a><p class="mt-1 inline-block rounded-full bg-black/5 px-2.5 py-1 text-xs text-black/60">{{ str($order->status)->replace('_', ' ')->title() }}</p></td>
                            <td class="px-5 py-4"><p>{{ $order->customer_name }}</p><p class="mt-1 text-xs text-black/45">{{ $order->customer_email }}</p></td>
                            <td class="px-5 py-4">{{ $order->payment_method === 'cash_on_delivery' ? 'Cash on delivery' : 'Online' }}</td>
                            <td class="px-5 py-4">{{ str($order->payment_status)->replace('_', ' ')->title() }}</td>
                            <td class="px-5 py-4">{{ $order->items_count }}</td>
                            <td class="px-5 py-4">₹{{ number_format($order->total_paise / 100, 2) }}</td>
                            <td class="px-5 py-4">{{ $order->created_at->format('d M Y, H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-6 py-12 text-center text-black/50">No orders have been received.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($orders->hasPages())<div class="border-t border-black/10 px-6 py-4">{{ $orders->links() }}</div>@endif
    </section>
@endsection
