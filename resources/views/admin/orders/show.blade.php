@extends('layouts.admin', ['title' => $order->order_number])

@section('content')
    <div class="flex flex-wrap items-end justify-between gap-5">
        <div>
            <a href="{{ route('admin.orders.index') }}" class="text-sm text-black/55">← Orders</a>
            <h1 class="mt-2 font-['Bodoni_Moda'] text-4xl">{{ $order->order_number }}</h1>
            <p class="mt-2 text-sm text-black/50">Created {{ $order->created_at->format('d M Y, H:i T') }}</p>
        </div>
        <div class="flex gap-2 text-sm">
            <span class="rounded-full bg-black/5 px-3 py-1.5">{{ str($order->status)->replace('_', ' ')->title() }}</span>
            <span class="rounded-full bg-emerald-50 px-3 py-1.5 text-emerald-800">{{ str($order->payment_status)->replace('_', ' ')->title() }}</span>
        </div>
    </div>

    <div class="mt-8 grid gap-8 lg:grid-cols-[minmax(0,1fr)_360px]">
        <div class="space-y-6">
            <section class="overflow-hidden rounded-xl bg-white shadow-sm">
                <div class="border-b border-black/10 px-6 py-5"><h2 class="font-semibold">Items</h2></div>
                <div class="divide-y divide-black/10">
                    @foreach ($order->items as $item)
                        <div class="flex items-start justify-between gap-5 px-6 py-5 text-sm">
                            <div>
                                <p class="font-medium">{{ $item->product_title }}</p>
                                @if (data_get($item->metadata, 'variant_title'))
                                    <p class="mt-1 text-black/60">{{ data_get($item->metadata, 'variant_title') }}</p>
                                @endif
                                @if ($item->product_subtitle)
                                    <p class="mt-1 text-black/50">{{ $item->product_subtitle }}</p>
                                @endif
                                <p class="mt-2 text-xs text-black/45">₹{{ number_format($item->unit_price_paise / 100, 2) }} × {{ $item->quantity }}</p>
                            </div>
                            <p class="font-medium">₹{{ number_format($item->line_total_paise / 100, 2) }}</p>
                        </div>
                    @endforeach
                </div>
                <dl class="space-y-3 border-t border-black/10 px-6 py-5 text-sm">
                    <div class="flex justify-between gap-5"><dt class="text-black/55">Subtotal</dt><dd>₹{{ number_format($order->subtotal_paise / 100, 2) }}</dd></div>
                    @if ($order->discount_paise)<div class="flex justify-between gap-5"><dt class="text-black/55">Discount</dt><dd>−₹{{ number_format($order->discount_paise / 100, 2) }}</dd></div>@endif
                    @if ($order->shipping_paise)<div class="flex justify-between gap-5"><dt class="text-black/55">Shipping</dt><dd>₹{{ number_format($order->shipping_paise / 100, 2) }}</dd></div>@endif
                    @if ($order->cod_fee_paise)<div class="flex justify-between gap-5"><dt class="text-black/55">Cash on delivery charge</dt><dd>₹{{ number_format($order->cod_fee_paise / 100, 2) }}</dd></div>@endif
                    <div class="flex justify-between gap-5 border-t border-black/10 pt-3 text-base font-semibold"><dt>Total</dt><dd>₹{{ number_format($order->total_paise / 100, 2) }}</dd></div>
                    @if ($order->cod_due_paise)<div class="flex justify-between gap-5 text-amber-800"><dt>Cash due on delivery</dt><dd>₹{{ number_format($order->cod_due_paise / 100, 2) }}</dd></div>@endif
                </dl>
            </section>

            <section class="overflow-hidden rounded-xl bg-white shadow-sm">
                <div class="border-b border-black/10 px-6 py-5"><h2 class="font-semibold">Payments</h2></div>
                <div class="divide-y divide-black/10">
                    @forelse ($order->payments as $payment)
                        <dl class="grid gap-4 px-6 py-5 text-sm sm:grid-cols-2">
                            <div><dt class="text-xs uppercase tracking-wide text-black/45">Status</dt><dd class="mt-1">{{ str($payment->status)->replace('_', ' ')->title() }}</dd></div>
                            <div><dt class="text-xs uppercase tracking-wide text-black/45">Amount</dt><dd class="mt-1">₹{{ number_format($payment->amount_paise / 100, 2) }}</dd></div>
                            <div><dt class="text-xs uppercase tracking-wide text-black/45">Purpose</dt><dd class="mt-1">{{ str($payment->purpose)->replace('_', ' ')->title() }}</dd></div>
                            <div><dt class="text-xs uppercase tracking-wide text-black/45">Razorpay payment</dt><dd class="mt-1 break-all font-mono text-xs">{{ $payment->provider_payment_id ?: 'Not received' }}</dd></div>
                        </dl>
                    @empty
                        <p class="px-6 py-8 text-sm text-black/50">No payment attempt has been recorded.</p>
                    @endforelse
                </div>
            </section>
        </div>

        <aside class="space-y-6">
            <section class="rounded-xl bg-white p-6 shadow-sm">
                <h2 class="font-semibold">Customer</h2>
                <dl class="mt-5 space-y-4 text-sm">
                    <div><dt class="text-xs uppercase tracking-wide text-black/45">Name</dt><dd class="mt-1">{{ $order->customer_name }}</dd></div>
                    <div><dt class="text-xs uppercase tracking-wide text-black/45">Email</dt><dd class="mt-1 break-all"><a href="mailto:{{ $order->customer_email }}">{{ $order->customer_email }}</a></dd></div>
                    <div><dt class="text-xs uppercase tracking-wide text-black/45">Phone</dt><dd class="mt-1"><a href="tel:{{ $order->customer_phone }}">{{ $order->customer_phone }}</a></dd></div>
                </dl>
            </section>

            <section class="rounded-xl bg-white p-6 shadow-sm">
                <h2 class="font-semibold">Delivery</h2>
                <address class="mt-5 text-sm not-italic leading-6 text-black/70">
                    {{ $order->address_line_1 }}<br>
                    @if ($order->address_line_2){{ $order->address_line_2 }}<br>@endif
                    @if ($order->city){{ $order->city }}, @endif{{ $order->state }} {{ $order->postal_code }}<br>
                    {{ $order->country_code }}
                </address>
                <p class="mt-4 text-xs uppercase tracking-wide text-black/45">Fulfilment</p>
                <p class="mt-1 text-sm">{{ str($order->fulfilment_status)->replace('_', ' ')->title() }}</p>

                <form method="post" action="{{ route('admin.orders.fulfilment.update', $order) }}" class="mt-5 border-t border-black/10 pt-5">
                    @csrf
                    @method('patch')
                    <label for="fulfilment_status" class="mb-2 block text-sm">Update fulfilment</label>
                    <select id="fulfilment_status" name="fulfilment_status" class="w-full rounded-lg border border-black/20 bg-white px-4 py-2.5" @disabled(! in_array($order->payment_status, ['captured', 'cod_fee_paid'], true))>
                        @foreach (['unfulfilled' => 'Unfulfilled', 'processing' => 'Processing', 'shipped' => 'Shipped', 'delivered' => 'Delivered'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('fulfilment_status', $order->fulfilment_status) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('fulfilment_status')<p class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror
                    <button type="submit" class="mt-3 w-full rounded-lg bg-[#080808] px-4 py-2.5 text-sm text-white" @disabled(! in_array($order->payment_status, ['captured', 'cod_fee_paid'], true))>Save fulfilment status</button>
                    @if (! in_array($order->payment_status, ['captured', 'cod_fee_paid'], true))<p class="mt-2 text-xs text-black/45">Available after the required online payment is confirmed.</p>@endif
                </form>
            </section>

            @if ($order->customer_note)
                <section class="rounded-xl bg-white p-6 shadow-sm"><h2 class="font-semibold">Customer note</h2><p class="mt-4 whitespace-pre-line text-sm leading-6 text-black/70">{{ $order->customer_note }}</p></section>
            @endif
        </aside>
    </div>
@endsection
