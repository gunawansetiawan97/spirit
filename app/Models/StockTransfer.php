<?php

namespace App\Models;

use App\Traits\HasAuditFields;
use App\Traits\HasUuid;
use App\Traits\HasValidation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockTransfer extends Model
{
    use HasFactory, HasAuditFields, HasUuid, HasValidation;

    public string $label = 'Transfer Stok';

    protected $fillable = [
        'code', 'date', 'from_warehouse_id', 'to_warehouse_id',
        'description', 'status',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public static function validationRules(): array
    {
        return [
            'code'               => "required|string|max:30|unique:stock_transfers,code",
            'date'               => 'required|date',
            'from_warehouse_id'  => 'required|exists:warehouses,id',
            'to_warehouse_id'    => 'required|exists:warehouses,id|different:from_warehouse_id',
            'description'        => 'nullable|string',
        ];
    }

    public function fromWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function toWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(StockTransferDetail::class);
    }
}
