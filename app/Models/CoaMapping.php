<?php

namespace App\Models;

use App\Traits\HasAuditFields;
use App\Traits\HasUuid;
use App\Traits\HasValidation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Request;

class CoaMapping extends Model
{
    use HasFactory, HasAuditFields, HasUuid, HasValidation;

    public static string $label = 'COA Mapping';

    protected $fillable = [
        'module',
        'mapping_key',
        'coa_id',
        'description',
        'is_active',
    ];

    public static function validationRules(): array
    {
        return [
            'module' => 'required|string|max:50',
            'coa_id' => 'required|exists:chart_of_accounts,id',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ];
    }

    public static function storeRules(?Request $request = null): array
    {
        $rules = parent::storeRules($request);
        $module = $request?->module ?? '';
        $rules['mapping_key'] = 'required|string|max:50|unique:coa_mappings,mapping_key,NULL,id,module,' . $module;
        return $rules;
    }

    public static function updateRules($id, ?Request $request = null): array
    {
        $rules = parent::updateRules($id, $request);
        $module = $request?->module ?? '';
        $rules['mapping_key'] = 'required|string|max:50|unique:coa_mappings,mapping_key,' . $id . ',id,module,' . $module;
        return $rules;
    }

    protected $casts = [
        'is_active' => 'boolean',
        'approved_at' => 'datetime',
        'printed_at' => 'datetime',
    ];

    public function coa(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'coa_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForModule($query, string $module)
    {
        return $query->where('module', $module);
    }
}
