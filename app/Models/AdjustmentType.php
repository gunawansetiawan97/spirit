<?php

namespace App\Models;

use App\Traits\HasAuditFields;
use App\Traits\HasUuid;
use App\Traits\HasValidation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdjustmentType extends Model
{
    use HasFactory, HasAuditFields, HasUuid, HasValidation;

    public string $label = 'Tipe Penyesuaian';

    protected $fillable = [
        'code', 'name', 'coa_id', 'description', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected function validationRules(): array
    {
        return [
            'code' => "required|string|max:20|unique:adjustment_types,code",
            'name' => 'required|string|max:100',
            'coa_id' => 'nullable|exists:chart_of_accounts,id',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ];
    }

    public function coa(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'coa_id');
    }
}
