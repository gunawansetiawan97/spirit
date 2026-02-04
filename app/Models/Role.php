<?php

namespace App\Models;

use App\Traits\HasAuditFields;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    use HasFactory, HasAuditFields, HasUuid;

    protected $fillable = [
        'code',
        'name',
        'description',
        'is_active',
        'branch_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'approved_at' => 'datetime',
        'printed_at' => 'datetime',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions')
            ->withPivot(['can_view', 'can_create', 'can_edit', 'can_delete', 'can_approve', 'can_print', 'can_export'])
            ->withTimestamps();
    }

    public function rolePermissions(): HasMany
    {
        return $this->hasMany(RolePermission::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function hasPermission(string $permissionCode, string $action = 'can_view'): bool
    {
        $permission = $this->permissions()
            ->where('permissions.code', $permissionCode)
            ->first();

        if (!$permission) {
            return false;
        }

        return (bool) $permission->pivot->{$action};
    }
}
