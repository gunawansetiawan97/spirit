<?php

namespace App\Models;

use App\Traits\HasAuditFields;
use App\Traits\HasUuid;
use App\Traits\HasValidation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory, HasAuditFields, HasUuid, HasValidation;

    public static string $label = 'Produk';

    protected $fillable = [
        'product_category_id',
        'product_brand_id',
        'code',
        'name',
        'type',
        'min_stock',
        'max_stock',
        'description',
        'coa_inventory_id',
        'coa_cogs_id',
        'coa_sales_id',
        'is_active',
    ];

    protected $casts = [
        'min_stock' => 'decimal:2',
        'max_stock' => 'decimal:2',
        'is_active' => 'boolean',
        'approved_at' => 'datetime',
        'printed_at' => 'datetime',
    ];

    public static function validationRules(): array
    {
        return [
            'code' => 'nullable|string|max:20|unique:products,code',
            'name' => 'required|string|max:100',
            'product_category_id' => 'nullable|exists:product_categories,id',
            'product_brand_id' => 'nullable|exists:product_brands,id',
            'type' => 'required|in:stock,non-stock',
            'min_stock' => 'nullable|numeric|min:0',
            'max_stock' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'coa_inventory_id' => 'nullable|exists:chart_of_accounts,id',
            'coa_cogs_id' => 'nullable|exists:chart_of_accounts,id',
            'coa_sales_id' => 'nullable|exists:chart_of_accounts,id',
            'is_active' => 'boolean',
            'units' => 'nullable|array',
            'units.*.unit_id' => 'required|exists:units,id',
            'units.*.conversion' => 'required|numeric|min:0.0001',
            'units.*.is_base_unit' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(ProductBrand::class, 'product_brand_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function units(): HasMany
    {
        return $this->hasMany(ProductUnit::class);
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
