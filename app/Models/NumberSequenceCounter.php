<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NumberSequenceCounter extends Model
{
    protected $fillable = [
        'number_sequence_id',
        'scope_key',
        'last_number',
    ];

    protected $casts = [
        'last_number' => 'integer',
    ];

    public function sequence(): BelongsTo
    {
        return $this->belongsTo(NumberSequence::class, 'number_sequence_id');
    }
}
