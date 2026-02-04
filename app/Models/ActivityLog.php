<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'loggable_type',
        'loggable_id',
        'action',
        'changes',
        'ip_address',
        'created_at',
    ];

    protected $casts = [
        'changes' => 'array',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function loggable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeForModel($query, Model $model)
    {
        return $query->where('loggable_type', $model->getMorphClass())
                     ->where('loggable_id', $model->getKey());
    }

    public function getActionLabelAttribute(): string
    {
        $labels = [
            'created' => 'membuat',
            'updated' => 'mengupdate',
            'deleted' => 'menghapus',
            'approved' => 'approve',
            'unapproved' => 'unapprove',
            'printed' => 'mencetak',
        ];

        return $labels[$this->action] ?? $this->action;
    }
}
