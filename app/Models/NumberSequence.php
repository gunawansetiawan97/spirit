<?php

namespace App\Models;

use App\Traits\HasAuditFields;
use App\Traits\HasUuid;
use App\Traits\HasValidation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NumberSequence extends Model
{
    use HasFactory, HasAuditFields, HasUuid, HasValidation;

    public static string $label = 'Penomoran';

    protected $fillable = [
        'code',
        'name',
        'prefix',
        'separator',
        'format',
        'reset_type',
        'scope_type',
        'sequence_length',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sequence_length' => 'integer',
        'approved_at' => 'datetime',
        'printed_at' => 'datetime',
    ];

    public static function validationRules(): array
    {
        return [
            'code' => 'required|string|max:50|unique:number_sequences,code',
            'name' => 'required|string|max:100',
            'prefix' => 'required|string|max:20',
            'separator' => 'nullable|string|max:5',
            'format' => 'required|string|max:100',
            'reset_type' => 'required|in:none,daily,monthly,yearly',
            'scope_type' => 'required|in:global,branch',
            'sequence_length' => 'required|integer|min:1|max:10',
            'is_active' => 'boolean',
        ];
    }

    public function counters(): HasMany
    {
        return $this->hasMany(NumberSequenceCounter::class);
    }
}
