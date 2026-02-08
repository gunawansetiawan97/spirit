<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasAuditFields;
use App\Traits\HasUuid;

class Unit extends Model
{
    use HasFactory, HasAuditFields, HasUuid;
    protected $fillable = [
        'code',
        'name',
        'description',
        'is_active', 
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'approved_at' => 'datetime',
        'printed_at' => 'datetime',
    ];  
}
