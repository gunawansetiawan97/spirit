<?php

namespace App\Models;

use App\Traits\HasAuditFields;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChartOfAccount extends Model
{
    use HasFactory, HasAuditFields, HasUuid;

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
