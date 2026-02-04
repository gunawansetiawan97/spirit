<?php

namespace App\Traits;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;

trait HasUuid
{
    /**
     * Boot the trait
     */
    public static function bootHasUuid(): void
    {
        static::creating(function (Model $model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }

    /**
     * Get the route key for the model.
     * This makes Laravel use 'uuid' for route model binding instead of 'id'
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * Find a model by its UUID
     */
    public static function findByUuid(string $uuid): ?static
    {
        return static::where('uuid', $uuid)->first();
    }

    /**
     * Find a model by its UUID or fail
     */
    public static function findByUuidOrFail(string $uuid): static
    {
        return static::where('uuid', $uuid)->firstOrFail();
    }

    /**
     * Scope to filter by UUID
     */
    public function scopeByUuid($query, string $uuid)
    {
        return $query->where('uuid', $uuid);
    }
}
