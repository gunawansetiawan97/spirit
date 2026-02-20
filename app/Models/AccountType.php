<?php

namespace App\Models;

use App\Traits\HasAuditFields;
use App\Traits\HasUuid;
use App\Traits\HasValidation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AccountType extends Model
{
    use HasFactory, HasAuditFields, HasUuid, HasValidation;

    public static string $label = 'Tipe Akun';

    protected $fillable = [
        'code',
        'name',
        'description',
        'is_active',
    ];

    public static function validationRules(): array
    {
        return [
            'code' => 'required|string|max:20|unique:account_types,code',
            'is_active' => 'boolean',
        ];
    }

    protected $casts = [
        'is_active' => 'boolean',
        'approved_at' => 'datetime',
        'printed_at' => 'datetime',
    ];  
}
