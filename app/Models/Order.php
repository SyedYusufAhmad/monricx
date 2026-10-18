<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::creating(function (Order $order): void {
            $order->public_id ??= (string) Str::uuid();
            $order->order_number ??= 'MON-'.now()->format('ymd').'-'.Str::upper(Str::random(6));
        });
    }

    protected function casts(): array
    {
        return [
            'subtotal_paise' => 'integer',
            'discount_paise' => 'integer',
            'shipping_paise' => 'integer',
            'cod_fee_paise' => 'integer',
            'online_payable_paise' => 'integer',
            'cod_due_paise' => 'integer',
            'total_paise' => 'integer',
            'consent_at' => 'datetime',
            'placed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function stockReservations(): HasMany
    {
        return $this->hasMany(StockReservation::class);
    }

    /** Display the latest persisted Razorpay refund status for this order. */
    public const REFUND_STATUS_LABELS = [
        'not_refunded' => 'Not refunded',
        'refund_requested' => 'Refund requested',
        'refund_processing' => 'Refund processing',
        'refunded' => 'Refunded',
        'refund_failed' => 'Refund failed',
    ];

    public function refundStatusLabel(): string
    {
        $status = $this->getAttribute('refund_status');

        if (! $status && $this->relationLoaded('payments')) {
            $status = $this->payments
                ->pluck('refund_status')
                ->filter()
                ->last();
        }

        $status = match ($status) {
            'processing' => 'refund_processing',
            'processed' => 'refunded',
            'failed' => 'refund_failed',
            default => $status ?: 'not_refunded',
        };

        return self::REFUND_STATUS_LABELS[$status] ?? self::REFUND_STATUS_LABELS['not_refunded'];
    }
}
