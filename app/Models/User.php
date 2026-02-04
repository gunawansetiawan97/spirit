<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, HasUuid;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'branch_id',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
        'approved_at' => 'datetime',
        'printed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (Auth::check()) {
                $model->created_by = Auth::id();
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

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class)->withTimestamps();
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

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function hasPermission(string $permissionCode, string $action = 'can_view'): bool
    {
        if (!$this->role) {
            return false;
        }

        return $this->role->hasPermission($permissionCode, $action);
    }

    public function getPermissions(): array
    {
        if (!$this->role) {
            return [];
        }

        $permissions = [];
        foreach ($this->role->permissions as $permission) {
            $permissions[$permission->code] = [
                'can_view' => $permission->pivot->can_view,
                'can_create' => $permission->pivot->can_create,
                'can_edit' => $permission->pivot->can_edit,
                'can_delete' => $permission->pivot->can_delete,
                'can_approve' => $permission->pivot->can_approve,
                'can_print' => $permission->pivot->can_print,
                'can_export' => $permission->pivot->can_export,
            ];
        }

        return $permissions;
    }

    public function getMenus(): array
    {
        if (!$this->role) {
            return [];
        }

        $permissionIds = $this->role->permissions()
            ->wherePivot('can_view', true)
            ->pluck('permissions.id')
            ->toArray();

        return Permission::with(['children' => function ($query) use ($permissionIds) {
            $query->whereIn('id', $permissionIds)
                ->active()
                ->ordered()
                ->with(['children' => function ($q) use ($permissionIds) {
                    $q->whereIn('id', $permissionIds)->active()->ordered();
                }]);
        }])
        ->whereIn('id', $permissionIds)
        ->whereNull('parent_id')
        ->where('type', 'menu')
        ->active()
        ->ordered()
        ->get()
        ->toArray();
    }
}
