<?php

namespace App\Models;

use App\Traits\HasAuditFields;
use App\Traits\HasUuid;
use App\Traits\HasValidation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountGroup extends Model
{
    use HasFactory, HasAuditFields, HasUuid, HasValidation;

    public static string $label = 'Group Akun';

    protected $fillable = [
        'account_type_id',
        'group_name',
        'normal_balance',
        'is_active',
    ];

    public static function validationRules(): array
    {
        return [
            'account_type_id' => 'required|exists:account_types,id',
            'group_name' => 'required|string|max:100',
            'normal_balance' => 'required|in:Debit,Credit',
            'is_active' => 'boolean',
        ];
    }

    protected $casts = [
        'is_active' => 'boolean',
        'approved_at' => 'datetime',
        'printed_at' => 'datetime',
    ];

    public function accountType(): BelongsTo
    {
        return $this->belongsTo(AccountType::class);
    }

    public function chartOfAccounts(): HasMany
    {
        return $this->hasMany(ChartOfAccount::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
