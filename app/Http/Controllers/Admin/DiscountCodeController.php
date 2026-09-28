<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DiscountCode;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DiscountCodeController extends Controller
{
    public function index(): View
    {
        return view('admin.discount-codes.index', [
            'codes' => DiscountCode::query()->latest()->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('admin.discount-codes.create', [
            'code' => new DiscountCode(['is_active' => true, 'type' => 'percentage']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateCode($request);

        $discount = DiscountCode::query()->create($this->attributes($validated));

        return redirect()
            ->route('admin.discount-codes.edit', $discount)
            ->with('status', "Discount code {$discount->code} created.");
    }

    public function edit(DiscountCode $discountCode): View
    {
        return view('admin.discount-codes.edit', ['code' => $discountCode]);
    }

    public function update(Request $request, DiscountCode $discountCode): RedirectResponse
    {
        $validated = $this->validateCode($request, $discountCode);

        $discountCode->update($this->attributes($validated));

        return redirect()
            ->route('admin.discount-codes.edit', $discountCode)
            ->with('status', "Discount code {$discountCode->code} updated.");
    }

    public function destroy(DiscountCode $discountCode): RedirectResponse
    {
        $code = $discountCode->code;
        $discountCode->delete();

        return redirect()
            ->route('admin.discount-codes.index')
            ->with('status', "Discount code {$code} deleted.");
    }

    private function validateCode(Request $request, ?DiscountCode $discount = null): array
    {
        return $request->validate([
            'code' => ['required', 'string', 'max: 32', Rule::unique('discount_codes', 'code')->ignore($discount?->id)],
            'type' => ['required', Rule::in(['percentage', 'fixed'])],
            'value' => [
                'required',
                'numeric',
                'min: 1',
                $request->input('type') === 'percentage' ? 'max:100' : 'max:1000000',
            ],
            'minimum_order_rupees' => ['nullable', 'numeric', 'min: 0', 'max: 1000000'],
            'maximum_discount_rupees' => ['nullable', 'numeric', 'min: 1', 'max: 1000000'],
            'usage_limit' => ['nullable', 'integer', 'min: 1', 'max: 1000000'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
    }

    private function attributes(array $validated): array
    {
        $value = (float) $validated['value'];

        return [
            'code' => strtoupper(trim($validated['code'])),
            'type' => $validated['type'],
            'value' => $validated['type'] === 'percentage'
                ? (int) min(100, $value)
                : (int) round($value * 100),
            'minimum_order_paise' => isset($validated['minimum_order_rupees'])
                ? (int) round((float) $validated['minimum_order_rupees'] * 100)
                : 0,
            'maximum_discount_paise' => $validated['type'] === 'percentage' && isset($validated['maximum_discount_rupees'])
                ? (int) round((float) $validated['maximum_discount_rupees'] * 100)
                : null,
            'usage_limit' => $validated['usage_limit'] ?? null,
            'starts_at' => $validated['starts_at'] ?? null,
            'ends_at' => $validated['ends_at'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ];
    }
}
