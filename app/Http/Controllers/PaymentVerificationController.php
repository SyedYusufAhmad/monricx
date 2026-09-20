<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\CartService;
use App\Services\OrderPaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PaymentVerificationController extends Controller
{
    public function __invoke(
        Request $request,
        OrderPaymentService $paymentService,
        CartService $cartService,
    ): RedirectResponse {
        $validated = $request->validate([
            'razorpay_payment_id' => ['required', 'string', 'max:255'],
            'razorpay_order_id' => ['required', 'string', 'max:255'],
            'razorpay_signature' => ['required', 'string', 'max:512'],
        ]);

        $orderId = $request->session()->get('monricx_checkout_order_id');
        $order = $orderId ? Order::query()->find($orderId) : null;

        abort_unless($order, 404);

        $order = $paymentService->verifyBrowserPayment($order, $validated);
        $request->session()->put('monricx_completed_order_id', $order->id);

        if (in_array($order->payment_status, ['captured', 'cod_fee_paid'], true)) {
            $cartService->clear($request);
            $request->session()->forget('monricx_checkout_order_id');
        }

        return redirect()->route('order.confirmation', $order->public_id);
    }
}
