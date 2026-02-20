<?php

namespace App\Models;

use App\Traits\HasAuditFields;
use App\Traits\HasUuid;
use App\Traits\HasValidation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockAdjustment extends Model
{
    use HasFactory, HasAuditFields, HasUuid, HasValidation;

    public string $label = 'Penyesuaian Stok';

    protected $fillable = [
        'code', 'date', 'warehouse_id', 'adjustment_type_id',
        'description', 'status',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public static function validationRules(): array
    {
        return [
            'code'               => "required|string|max:30|unique:stock_adjustments,code",
            'date'               => 'required|date',
            'warehouse_id'       => 'required|exists:warehouses,id',
            'adjustment_type_id' => 'required|exists:adjustment_types,id',
            'description'        => 'nullable|string',
            'details'            => 'required|array|min:1',
            'details.*.product_id'  => 'required|exists:products,id',
            'details.*.direction'   => 'required|in:in,out',
            'details.*.batch_number'=> 'nullable|string|max:50',
            'details.*.unit_id'     => 'required|exists:units,id',
            'details.*.qty'         => 'required|numeric|min:0.0001',
            'details.*.unit_cost'   => 'nullable|numeric|min:0',
            'details.*.description' => 'nullable|string',
        ];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function adjustmentType(): BelongsTo
    {
        return $this->belongsTo(AdjustmentType::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(StockAdjustmentDetail::class);
    }
}
