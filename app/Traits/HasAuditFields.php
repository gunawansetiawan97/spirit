<?php

namespace App\Traits;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

trait HasAuditFields
{
    use SoftDeletes;

    public static function bootHasAuditFields(): void
    {
        static::creating(function ($model) {
            if (Auth::check()) {
                $model->created_by = Auth::id();
                // Only set branch_id if the table has this column
                if (Schema::hasColumn($model->getTable(), 'branch_id')) {
                    $model->branch_id = $model->branch_id ?? Auth::user()->branch_id;
                }
            }
        });

        static::updating(function ($model) {
            if (Auth::check()) {
                $model->updated_by = Auth::id();
            }
        });

        static::deleting(function ($model) {
            if (Auth::check() && !$model->isForceDeleting()) {
                $model->deleted_by = Auth::id();
                $model->saveQuietly();
            }
        });
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function printedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'printed_by');
    }

    public function approve(): bool
    {
        if (!Auth::check()) {
            return false;
        }

        $this->approved_by = Auth::id();
        $this->approved_at = now();
        return $this->save();
    }

    public function unapprove(): bool
    {
        $this->approved_by = null;
        $this->approved_at = null;
        return $this->save();
    }

    public function markAsPrinted(): bool
    {
        if (!Auth::check()) {
            return false;
        }

        $this->printed_by = Auth::id();
        $this->printed_at = now();
        return $this->save();
    }

    public function isApproved(): bool
    {
        return $this->approved_at !== null;
    }

    public function isPrinted(): bool
    {
        return $this->printed_at !== null;
    }

    public function scopeByBranch($query, $branchId = null)
    {
        $branchId = $branchId ?? Auth::user()?->branch_id;
        if ($branchId) {
            return $query->where('branch_id', $branchId);
        }
        return $query;
    }

    public function scopeApproved($query)
    {
        return $query->whereNotNull('approved_at');
    }

    public function scopeUnapproved($query)
    {
        return $query->whereNull('approved_at');
    }
}
