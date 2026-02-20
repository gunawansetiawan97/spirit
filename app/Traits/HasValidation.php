<?php

namespace App\Traits;

use Illuminate\Http\Request;

trait HasValidation
{
    /**
     * Define base validation rules. Override in each model.
     * Rules should use the STORE form (no ID exclusion in unique rules).
     */
    abstract public static function validationRules(): array;

    /**
     * Rules that differ between store and update.
     * Override in model for cases like password: required vs nullable.
     *
     * @return array<string, array{store: string|array, update: string|array}>
     */
    public static function contextualRules(): array
    {
        return [];
    }

    /**
     * Get rules for store (create) operation.
     */
    public static function storeRules(?Request $request = null): array
    {
        $rules = static::validationRules();

        foreach (static::contextualRules() as $field => $variants) {
            $rules[$field] = $variants['store'];
        }

        return $rules;
    }

    /**
     * Get rules for update operation.
     * Automatically adjusts unique constraints to exclude the given ID.
     */
    public static function updateRules($id, ?Request $request = null): array
    {
        $rules = static::validationRules();

        foreach (static::contextualRules() as $field => $variants) {
            $rules[$field] = $variants['update'];
        }

        foreach ($rules as $field => &$fieldRules) {
            $fieldRules = static::adjustUniqueRules($fieldRules, $id);
        }

        return $rules;
    }

    /**
     * Adjust unique rules to exclude a given ID.
     * Handles both string and array rule formats.
     */
    protected static function adjustUniqueRules($rules, $id)
    {
        if (is_array($rules)) {
            return array_map(fn($rule) => static::adjustSingleRule($rule, $id), $rules);
        }

        $parts = explode('|', $rules);
        $parts = array_map(fn($rule) => static::adjustSingleRule($rule, $id), $parts);

        return implode('|', $parts);
    }

    /**
     * Adjust a single rule string if it's a simple unique rule.
     */
    protected static function adjustSingleRule($rule, $id)
    {
        if (!is_string($rule) || !str_starts_with($rule, 'unique:')) {
            return $rule;
        }

        $segments = explode(',', substr($rule, 7));
        $count = count($segments);

        if ($count <= 2) {
            // unique:table,column → unique:table,column,$id
            $segments[] = $id;
            return 'unique:' . implode(',', $segments);
        }

        if ($count >= 3 && strtoupper(trim($segments[2])) === 'NULL') {
            // unique:table,column,NULL,... → unique:table,column,$id,...
            $segments[2] = $id;
            return 'unique:' . implode(',', $segments);
        }

        return $rule;
    }
}
