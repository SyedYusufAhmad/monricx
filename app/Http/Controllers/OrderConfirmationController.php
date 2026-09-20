<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\CartService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class OrderConfirmationController extends Controller
{
    public function __invoke(
        Request $request,
        string $orderPublicId,
        CartService $cartService,
    ): View {
        $order = Order::query()
            ->with('items')
            ->where('public_id', $orderPublicId)
            ->firstOrFail();
        $allowedOrderIds = array_filter([
            $request->session()->get('monricx_completed_order_id'),
            $request->session()->get('monricx_checkout_order_id'),
        ]);

        abort_unless(in_array($order->id, $allowedOrderIds, true), 404);

        if (in_array($order->payment_status, ['captured', 'cod_fee_paid'], true)) {
            $cartService->clear($request);
            $request->session()->forget('monricx_checkout_order_id');
        }

        return view('storefront.order-confirmation', compact('order'));
    }
}
