<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockTransferDetail extends Model
{
    protected $fillable = [
        'stock_transfer_id', 'product_id', 'batch_number',
        'unit_id', 'qty', 'unit_cost', 'description',
    ];

    protected $casts = [
        'qty'       => 'decimal:4',
        'unit_cost' => 'decimal:4',
    ];

    public function stockTransfer(): BelongsTo
    {
        return $this->belongsTo(StockTransfer::class);
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
