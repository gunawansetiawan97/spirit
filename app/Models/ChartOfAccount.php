<?php

namespace App\Models;

use App\Traits\HasAuditFields;
use App\Traits\HasUuid;
use App\Traits\HasValidation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChartOfAccount extends Model
{
    use HasFactory, HasAuditFields, HasUuid, HasValidation;

    public static string $label = 'Chart of Account';

    protected $fillable = [
        'code',
        'name',
        'account_group_id',
        'posting_type',
        'parent_id',
        'is_active',
        'allow_manual_journal',
        'currency',
        'cost_center',
    ];

    public static function validationRules(): array
    {
        return [
            'code' => 'required|string|max:20|unique:chart_of_accounts,code',
            'name' => 'required|string|max:100',
            'account_group_id' => 'required|exists:account_groups,id',
            'posting_type' => 'required|in:Posting,Header',
            'parent_id' => 'nullable|exists:chart_of_accounts,id',
            'is_active' => 'boolean',
            'allow_manual_journal' => 'boolean',
            'currency' => 'required|string|max:5',
            'cost_center' => 'boolean',
        ];
    }

    protected $casts = [
        'is_active' => 'boolean',
        'allow_manual_journal' => 'boolean',
        'cost_center' => 'boolean',
        'approved_at' => 'datetime',
        'printed_at' => 'datetime',
    ];

    public function accountGroup(): BelongsTo
    {
        return $this->belongsTo(AccountGroup::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(ChartOfAccount::class, 'parent_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePosting($query)
    {
        return $query->where('posting_type', 'Posting');
    }
}
