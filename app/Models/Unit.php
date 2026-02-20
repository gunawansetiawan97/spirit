<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasAuditFields;
use App\Traits\HasUuid;
use App\Traits\HasValidation;

class Unit extends Model
{
    use HasFactory, HasAuditFields, HasUuid, HasValidation;

    public static string $label = 'Unit';

    protected $fillable = [
        'code',
        'name',
        'description',
        'is_active',
    ];

    public static function validationRules(): array
    {
        return [
            'code' => 'required|string|max:20|unique:units,code',
            'name' => 'required|string|max:100',
            'is_active' => 'boolean',
        ];
    }

    protected $casts = [
        'is_active' => 'boolean',
        'approved_at' => 'datetime',
        'printed_at' => 'datetime',
    ];  
}
