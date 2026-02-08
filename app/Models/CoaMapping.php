<?php

namespace App\Models;

use App\Traits\HasAuditFields;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoaMapping extends Model
{
    use HasFactory, HasAuditFields, HasUuid;

    protected $fillable = [
        'module',
        'mapping_key',
        'coa_id',
        'description',
        'is_active',
    ];

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
