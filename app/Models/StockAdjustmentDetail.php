<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockAdjustmentDetail extends Model
{
    protected $fillable = [
        'stock_adjustment_id', 'product_id', 'direction',
        'batch_number', 'unit_id', 'qty', 'unit_cost', 'description',
    ];

    protected $casts = [
        'qty'       => 'decimal:4',
        'unit_cost' => 'decimal:4',
    ];

    public function stockAdjustment(): BelongsTo
    {
        return $this->belongsTo(StockAdjustment::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
