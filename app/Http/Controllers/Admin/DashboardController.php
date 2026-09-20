<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\PageView;
use App\Models\Product;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = request()->user();
        $canViewOrders = $user->canAccessAdminArea('orders');
        $canViewProducts = $user->canAccessAdminArea('products');
        $canViewAnalytics = $user->canAccessAdminArea('analytics');

        $metrics = [
            'paid_sales_paise' => $canViewOrders
                ? (int) Order::query()->whereIn('payment_status', ['captured', 'cod_fee_paid'])->sum('online_payable_paise')
                : 0,
            'orders' => $canViewOrders ? Order::query()->count() : 0,
            'views_today' => $canViewAnalytics ? PageView::query()->whereDate('visited_at', today())->count() : 0,
            'low_stock_products' => $canViewProducts
                ? Product::query()->where('stock', '<=', 5)->where('status', 'published')->count()
                : 0,
        ];

        $recentOrders = $canViewOrders ? Order::query()->latest()->limit(10)->get() : collect();

        return view('admin.dashboard', compact(
            'metrics',
            'recentOrders',
            'canViewOrders',
            'canViewProducts',
            'canViewAnalytics',
        ));
    }
}
