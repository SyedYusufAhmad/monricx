@extends('layouts.admin', ['title' => 'Analytics'])

@section('content')
    <div class="flex flex-wrap items-end justify-between gap-5">
        <div>
            <p class="text-sm text-black/55">Performance</p>
            <h1 class="mt-1 font-['Bodoni_Moda'] text-4xl">Analytics</h1>
            <p class="mt-2 text-sm text-black/50">{{ $from->format('d M Y') }} – {{ $to->format('d M Y') }}</p>
        </div>
        <form method="get" class="flex flex-wrap items-end gap-3 rounded-xl bg-white p-4 shadow-sm">
            <label class="text-xs text-black/55">From<input type="date" name="from" value="{{ $from->toDateString() }}" class="mt-1 block rounded-lg border border-black/20 px-3 py-2 text-sm text-black"></label>
            <label class="text-xs text-black/55">To<input type="date" name="to" value="{{ $to->toDateString() }}" class="mt-1 block rounded-lg border border-black/20 px-3 py-2 text-sm text-black"></label>
            <button type="submit" class="rounded-lg bg-[#080808] px-4 py-2.5 text-sm text-white">Apply</button>
        </form>
    </div>

    <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
        <div class="rounded-xl bg-white p-6 shadow-sm"><p class="text-sm text-black/55">Page views</p><p class="mt-3 text-2xl font-semibold">{{ number_format($totals['page_views']) }}</p></div>
        <div class="rounded-xl bg-white p-6 shadow-sm"><p class="text-sm text-black/55">Unique sessions</p><p class="mt-3 text-2xl font-semibold">{{ number_format($totals['sessions']) }}</p></div>
        <div class="rounded-xl bg-white p-6 shadow-sm"><p class="text-sm text-black/55">Orders</p><p class="mt-3 text-2xl font-semibold">{{ number_format($totals['orders']) }}</p></div>
        <div class="rounded-xl bg-white p-6 shadow-sm"><p class="text-sm text-black/55">Confirmed orders</p><p class="mt-3 text-2xl font-semibold">{{ number_format($totals['confirmed_orders']) }}</p></div>
        <div class="rounded-xl bg-white p-6 shadow-sm"><p class="text-sm text-black/55">Confirmed order value</p><p class="mt-3 text-2xl font-semibold">₹{{ number_format($totals['order_value_paise'] / 100, 2) }}</p></div>
        <div class="rounded-xl bg-white p-6 shadow-sm"><p class="text-sm text-black/55">Collected online</p><p class="mt-3 text-2xl font-semibold">₹{{ number_format($totals['online_collected_paise'] / 100, 2) }}</p></div>
    </div>

    <div class="mt-8 grid gap-8 xl:grid-cols-2">
        <section class="rounded-xl bg-white p-6 shadow-sm">
            <h2 class="font-semibold">Traffic by day</h2>
            <div class="mt-6 h-[320px]"><canvas id="traffic-chart" aria-label="Daily page views and sessions"></canvas></div>
        </section>
        <section class="rounded-xl bg-white p-6 shadow-sm">
            <h2 class="font-semibold">Orders and confirmed value</h2>
            <div class="mt-6 h-[320px]"><canvas id="sales-chart" aria-label="Daily orders and confirmed order value"></canvas></div>
        </section>
    </div>

    <div class="mt-8 grid gap-8 lg:grid-cols-2">
        <section class="overflow-hidden rounded-xl bg-white shadow-sm">
            <div class="border-b border-black/10 px-6 py-5"><h2 class="font-semibold">Top pages</h2></div>
            <div class="divide-y divide-black/10 text-sm">
                @forelse ($topPages as $path => $views)
                    <div class="flex items-center justify-between gap-5 px-6 py-4"><span class="truncate font-mono text-xs">{{ $path }}</span><strong>{{ number_format($views) }}</strong></div>
                @empty
                    <p class="px-6 py-10 text-center text-black/50">No page views in this period.</p>
                @endforelse
            </div>
        </section>

        <section class="rounded-xl bg-white p-6 shadow-sm">
            <h2 class="font-semibold">Devices</h2>
            <div class="mx-auto mt-6 h-[260px] max-w-md"><canvas id="device-chart" aria-label="Page views by device type"></canvas></div>
        </section>
    </div>

    <section class="mt-8 overflow-hidden rounded-xl bg-white shadow-sm">
        <div class="border-b border-black/10 px-6 py-5"><h2 class="font-semibold">Calendar breakdown</h2></div>
        <div class="max-h-[520px] overflow-auto">
            <table class="w-full min-w-[780px] text-left text-sm">
                <thead class="sticky top-0 bg-[#f8f8f7] text-black/55"><tr><th class="px-5 py-3">Date</th><th class="px-5 py-3">Views</th><th class="px-5 py-3">Sessions</th><th class="px-5 py-3">Orders</th><th class="px-5 py-3">Confirmed</th><th class="px-5 py-3">Order value</th></tr></thead>
                <tbody class="divide-y divide-black/10">
                    @foreach ($daily->reverse() as $day)
                        <tr><td class="px-5 py-3">{{ \Carbon\CarbonImmutable::parse($day['date'])->format('d M Y') }}</td><td class="px-5 py-3">{{ number_format($day['page_views']) }}</td><td class="px-5 py-3">{{ number_format($day['sessions']) }}</td><td class="px-5 py-3">{{ number_format($day['orders']) }}</td><td class="px-5 py-3">{{ number_format($day['confirmed_orders']) }}</td><td class="px-5 py-3">₹{{ number_format($day['order_value_paise'] / 100, 2) }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <script>
        window.addEventListener('load', () => {
            const daily = @js($daily->values());
            const devices = @js($devices);
            const gridColor = 'rgba(0, 0, 0, 0.08)';

            new window.Chart(document.getElementById('traffic-chart'), {
                type: 'line',
                data: {
                    labels: daily.map(day => day.label),
                    datasets: [
                        { label: 'Page views', data: daily.map(day => day.page_views), borderColor: '#080808', backgroundColor: 'rgba(8,8,8,.08)', fill: true, tension: .3 },
                        { label: 'Sessions', data: daily.map(day => day.sessions), borderColor: '#c79b2e', backgroundColor: 'transparent', tension: .3 }
                    ]
                },
                options: { responsive: true, maintainAspectRatio: false, scales: { x: { grid: { display: false } }, y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: gridColor } } } }
            });

            new window.Chart(document.getElementById('sales-chart'), {
                data: {
                    labels: daily.map(day => day.label),
                    datasets: [
                        { type: 'bar', label: 'Orders', data: daily.map(day => day.orders), backgroundColor: 'rgba(8,8,8,.78)', yAxisID: 'orders' },
                        { type: 'line', label: 'Order value (₹)', data: daily.map(day => day.order_value_paise / 100), borderColor: '#c79b2e', backgroundColor: '#c79b2e', tension: .3, yAxisID: 'value' }
                    ]
                },
                options: { responsive: true, maintainAspectRatio: false, scales: { x: { grid: { display: false } }, orders: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: gridColor } }, value: { beginAtZero: true, position: 'right', grid: { display: false } } } }
            });

            new window.Chart(document.getElementById('device-chart'), {
                type: 'doughnut',
                data: { labels: Object.keys(devices).map(label => label.charAt(0).toUpperCase() + label.slice(1)), datasets: [{ data: Object.values(devices), backgroundColor: ['#080808', '#c79b2e', '#eadcba', '#8b8b8b'] }] },
                options: { responsive: true, maintainAspectRatio: false }
            });
        });
    </script>
@endsection
