<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $query = Order::query()->withCount('items');

        if ($request->filled('q')) {
            $search = trim((string) $request->input('q'));
            $query->where(fn ($query) => $query
                ->where('order_number', 'like', "%{$search}%")
                ->orWhere('customer_name', 'like', "%{$search}%")
                ->orWhere('customer_email', 'like', "%{$search}%")
                ->orWhere('customer_phone', 'like', "%{$search}%"));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        return view('admin.orders.index', [
            'orders' => $query->latest()->paginate(25)->withQueryString(),
            'query' => (string) $request->input('q'),
            'status' => (string) $request->input('status'),
            'statusOptions' => Order::query()->distinct()->orderBy('status')->pluck('status'),
        ]);
    }

    public function show(Order $order): View
    {
        $order->load(['items', 'payments']);

        return view('admin.orders.show', compact('order'));
    }

    public function updateFulfilment(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'fulfilment_status' => ['required', Rule::in(['unfulfilled', 'processing', 'shipped', 'delivered'])],
        ]);

        if (! in_array($order->payment_status, ['captured', 'cod_fee_paid'], true)) {
            throw ValidationException::withMessages([
                'fulfilment_status' => 'Fulfilment can only begin after the required online payment is confirmed.',
            ]);
        }

        $previousStatus = $order->fulfilment_status;
        $order->forceFill(['fulfilment_status' => $validated['fulfilment_status']])->save();

        AuditLog::query()->create([
            'actor_user_id' => $request->user()->id,
            'action' => 'order.fulfilment_updated',
            'auditable_type' => Order::class,
            'auditable_id' => $order->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => [
                'from' => $previousStatus,
                'to' => $order->fulfilment_status,
            ],
        ]);

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('status', 'Fulfilment status updated.');
    }
}
