<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'parent_id',
        'code',
        'name',
        'icon',
        'route',
        'type',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Permission::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Permission::class, 'parent_id')->orderBy('sort_order');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permissions')
            ->withPivot(['can_view', 'can_create', 'can_edit', 'can_delete', 'can_approve', 'can_print', 'can_export'])
            ->withTimestamps();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeMenus($query)
    {
        return $query->where('type', 'menu')->whereNull('parent_id');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    public static function getMenuTree()
    {
        return self::with(['children' => function ($query) {
            $query->active()->ordered()->with(['children' => function ($q) {
                $q->active()->ordered();
            }]);
        }])
        ->whereNull('parent_id')
        ->where('type', 'menu')
        ->active()
        ->ordered()
        ->get();
    }
}
