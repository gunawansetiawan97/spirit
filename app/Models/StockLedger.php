<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockLedger extends Model
{
    protected $fillable = [
        'transaction_date', 'product_id', 'warehouse_id', 'branch_id',
        'ref_type', 'ref_id', 'code', 'uom_id', 'batch_number',
        'qty_in', 'qty_out', 'base_qty_in', 'base_qty_out', 'base_balance',
        'unit_cost', 'total_value',
    ];

    protected $casts = [
        'transaction_date' => 'datetime',
        'qty_in'           => 'decimal:4',
        'qty_out'          => 'decimal:4',
        'base_qty_in'      => 'decimal:4',
        'base_qty_out'     => 'decimal:4',
        'base_balance'     => 'decimal:4',
        'unit_cost'        => 'decimal:4',
        'total_value'      => 'decimal:4',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'uom_id');
    }
}
