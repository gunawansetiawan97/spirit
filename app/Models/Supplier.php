<?php

namespace App\Models;

use App\Traits\HasAuditFields;
use App\Traits\HasUuid;
use App\Traits\HasValidation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Supplier extends Model
{
    use HasFactory, HasAuditFields, HasUuid, HasValidation;

    public string $label = 'Supplier';

    protected $fillable = [
        'code', 'name', 'phone', 'address',
        'coa_id', 'coa_dp_id', 'city',
        'npwp_no', 'npwp_name', 'npwp_address', 'nik',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public static function validationRules(): array
    {
        return [
            'code'         => 'required|string|max:20|unique:suppliers,code',
            'name'         => 'required|string|max:100',
            'phone'        => 'nullable|string|max:30',
            'address'      => 'nullable|string',
            'coa_id'       => 'nullable|exists:chart_of_accounts,id',
            'coa_dp_id'    => 'nullable|exists:chart_of_accounts,id',
            'city'         => 'nullable|string|max:100',
            'npwp_no'      => 'nullable|string|max:20',
            'npwp_name'    => 'nullable|string|max:100',
            'npwp_address' => 'nullable|string',
            'nik'          => 'nullable|string|max:20',
            'is_active'    => 'boolean',
        ];
    }

    public function coa(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'coa_id');
    }

    public function coaDp(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'coa_dp_id');
    }
}
