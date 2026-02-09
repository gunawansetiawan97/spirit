<?php

namespace App\Models;

use App\Traits\HasAuditFields;
use App\Traits\HasUuid;
use App\Traits\HasValidation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductCategory extends Model
{
    use HasFactory, HasAuditFields, HasUuid, HasValidation;

    public static string $label = 'Kategori Produk';

    protected $fillable = [
        'code',
        'name',
        'description',
        'coa_inventory_id',
        'coa_cogs_id',
        'coa_sales_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'approved_at' => 'datetime',
        'printed_at' => 'datetime',
    ];

    public static function validationRules(): array
    {
        return [
            'code' => 'nullable|string|max:20|unique:product_categories,code',
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'coa_inventory_id' => 'nullable|exists:chart_of_accounts,id',
            'coa_cogs_id' => 'nullable|exists:chart_of_accounts,id',
            'coa_sales_id' => 'nullable|exists:chart_of_accounts,id',
            'is_active' => 'boolean',
        ];
    }

    public function coaInventory(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'coa_inventory_id');
    }

    public function coaCogs(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'coa_cogs_id');
    }

    public function coaSales(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'coa_sales_id');
    }
}
