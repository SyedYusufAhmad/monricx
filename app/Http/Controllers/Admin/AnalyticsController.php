<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\PageView;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function __invoke(Request $request): View
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $to = isset($validated['to']) ? CarbonImmutable::parse($validated['to'])->endOfDay() : today()->endOfDay()->toImmutable();
        $from = isset($validated['from']) ? CarbonImmutable::parse($validated['from'])->startOfDay() : $to->subDays(29)->startOfDay();

        if ($from->isAfter($to)) {
            [$from, $to] = [$to->startOfDay(), $from->endOfDay()];
        }

        if ($from->diffInDays($to) > 365) {
            $from = $to->subDays(365)->startOfDay();
        }

        $viewsByDate = PageView::query()
            ->whereBetween('visited_at', [$from, $to])
            ->selectRaw('DATE(visited_at) as metric_date, COUNT(*) as page_views, COUNT(DISTINCT session_hash) as sessions')
            ->groupBy(DB::raw('DATE(visited_at)'))
            ->get()
            ->keyBy('metric_date');
        $ordersByDate = Order::query()
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw("DATE(created_at) as metric_date, COUNT(*) as orders_count,
                SUM(CASE WHEN payment_status IN ('captured', 'cod_fee_paid') THEN 1 ELSE 0 END) as confirmed_orders,
                SUM(CASE WHEN payment_status IN ('captured', 'cod_fee_paid') THEN total_paise ELSE 0 END) as order_value_paise,
                SUM(CASE WHEN payment_status IN ('captured', 'cod_fee_paid') THEN online_payable_paise ELSE 0 END) as online_collected_paise")
            ->groupBy(DB::raw('DATE(created_at)'))
            ->get()
            ->keyBy('metric_date');
        $daily = collect();

        for ($date = $from->startOfDay(); $date->lte($to); $date = $date->addDay()) {
            $dateKey = $date->toDateString();
            $dayViews = $viewsByDate->get($dateKey);
            $dayOrders = $ordersByDate->get($dateKey);

            $daily->push([
                'date' => $dateKey,
                'label' => $date->format('d M'),
                'page_views' => (int) ($dayViews?->page_views ?? 0),
                'sessions' => (int) ($dayViews?->sessions ?? 0),
                'orders' => (int) ($dayOrders?->orders_count ?? 0),
                'confirmed_orders' => (int) ($dayOrders?->confirmed_orders ?? 0),
                'order_value_paise' => (int) ($dayOrders?->order_value_paise ?? 0),
                'online_collected_paise' => (int) ($dayOrders?->online_collected_paise ?? 0),
            ]);
        }

        $devices = PageView::query()
            ->whereBetween('visited_at', [$from, $to])
            ->selectRaw("COALESCE(device_type, 'unknown') as device, COUNT(*) as views")
            ->groupBy('device')
            ->orderByDesc('views')
            ->pluck('views', 'device')
            ->map(fn ($views) => (int) $views);
        $topPages = PageView::query()
            ->whereBetween('visited_at', [$from, $to])
            ->selectRaw('path, COUNT(*) as views')
            ->groupBy('path')
            ->orderByDesc('views')
            ->limit(10)
            ->pluck('views', 'path')
            ->map(fn ($views) => (int) $views);

        return view('admin.analytics.index', [
            'from' => $from,
            'to' => $to,
            'daily' => $daily,
            'totals' => [
                'page_views' => (int) $daily->sum('page_views'),
                'sessions' => PageView::query()->whereBetween('visited_at', [$from, $to])->distinct()->count('session_hash'),
                'orders' => (int) $daily->sum('orders'),
                'confirmed_orders' => (int) $daily->sum('confirmed_orders'),
                'order_value_paise' => (int) $daily->sum('order_value_paise'),
                'online_collected_paise' => (int) $daily->sum('online_collected_paise'),
            ],
            'devices' => $devices,
            'topPages' => $topPages,
        ]);
    }
}
