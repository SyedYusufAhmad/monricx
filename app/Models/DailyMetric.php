<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyMetric extends Model
{
    use HasFactory;

    protected $primaryKey = 'metric_date';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'metric_date' => 'date',
            'page_views' => 'integer',
            'sessions' => 'integer',
            'orders_count' => 'integer',
            'paid_orders_count' => 'integer',
            'gross_revenue_paise' => 'integer',
        ];
    }
}
