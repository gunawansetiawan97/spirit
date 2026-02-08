<?php

namespace App\Models;

use App\Traits\HasAuditFields;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountGroup extends Model
{
    use HasFactory, HasAuditFields, HasUuid;

    protected $fillable = [
        'account_type_id',
        'group_name',
        'normal_balance',
        'is_active',
    ];

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
