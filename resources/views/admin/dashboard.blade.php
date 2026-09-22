@extends('layouts.admin', ['title' => 'Dashboard'])

@section('content')
    <div>
        <p class="text-sm text-black/55">Overview</p>
        <h1 class="mt-1 font-['Bodoni_Moda'] text-4xl">Dashboard</h1>
    </div>

    <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
        @if ($canViewOrders)
            <div class="rounded-xl bg-white p-6 shadow-sm"><p class="text-sm text-black/55">Online collected</p><p class="mt-3 text-2xl font-semibold">₹{{ number_format($metrics['paid_sales_paise'] / 100, 2) }}</p></div>
            <div class="rounded-xl bg-white p-6 shadow-sm"><p class="text-sm text-black/55">Orders</p><p class="mt-3 text-2xl font-semibold">{{ number_format($metrics['orders']) }}</p></div>
        @endif
        @if ($canViewAnalytics)
            <div class="rounded-xl bg-white p-6 shadow-sm"><p class="text-sm text-black/55">Views today</p><p class="mt-3 text-2xl font-semibold">{{ number_format($metrics['views_today']) }}</p></div>
        @endif
        @if ($canViewProducts)
            <div class="rounded-xl bg-white p-6 shadow-sm"><p class="text-sm text-black/55">Low stock</p><p class="mt-3 text-2xl font-semibold">{{ number_format($metrics['low_stock_products']) }}</p></div>
        @endif
    </div>

    @if ($canViewOrders)
    <section class="mt-8 overflow-hidden rounded-xl bg-white shadow-sm">
        <div class="border-b border-black/10 px-6 py-5"><h2 class="font-semibold">Recent orders</h2></div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[720px] text-left text-sm">
                <thead class="bg-black/[0.03] text-black/55"><tr><th class="px-5 py-3">Order</th><th class="px-5 py-3">Customer</th><th class="px-5 py-3">Payment</th><th class="px-5 py-3">Total</th><th class="px-5 py-3">Created</th></tr></thead>
                <tbody class="divide-y divide-black/10">
                    @forelse ($recentOrders as $order)
                        <tr><td class="px-5 py-4"><a class="underline decoration-black/20 underline-offset-4" href="{{ route('admin.orders.show', $order) }}">{{ $order->order_number }}</a></td><td class="px-5 py-4">{{ $order->customer_name }}</td><td class="px-5 py-4">{{ $order->payment_status }}</td><td class="px-5 py-4">₹{{ number_format($order->total_paise / 100, 2) }}</td><td class="px-5 py-4">{{ $order->created_at->format('d M Y, H:i') }}</td></tr>
                    @empty
                        <tr><td colspan="5" class="px-6 py-12 text-center text-black/50">No orders</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
    @endif
@endsection
