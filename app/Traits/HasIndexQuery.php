<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

trait HasIndexQuery
{
    /**
     * Build a paginated, filtered, sorted JSON response from a query.
     *
     * @param  Builder  $query    Base query (already has with/withCount applied)
     * @param  Request  $request  HTTP request
     * @param  array    $config   Configuration:
     *   - searchFields: ['code', 'name', 'accountType.code'] — dot notation triggers whereHas
     *   - filterFields: ['account_type_id'] — extra filters besides is_active
     *   - defaultSort: 'name'
     *   - defaultDirection: 'asc'
     */
    protected function paginatedResponse(Builder $query, Request $request, array $config = []): JsonResponse
    {
        $searchFields = $config['searchFields'] ?? [];
        $filterFields = $config['filterFields'] ?? [];
        $defaultSort = $config['defaultSort'] ?? 'id';
        $defaultDirection = $config['defaultDirection'] ?? 'asc';

        // Search
        if ($request->search && !empty($searchFields)) {
            $search = $request->search;
            $query->where(function (Builder $q) use ($search, $searchFields) {
                foreach ($searchFields as $i => $field) {
                    if (str_contains($field, '.')) {
                        [$relation, $column] = explode('.', $field, 2);
                        $method = $i === 0 ? 'whereHas' : 'orWhereHas';
                        $q->$method($relation, function (Builder $q2) use ($column, $search) {
                            $q2->where($column, 'like', "%{$search}%");
                        });
                    } else {
                        $method = $i === 0 ? 'where' : 'orWhere';
                        $q->$method($field, 'like', "%{$search}%");
                    }
                }
            });
        }

        // is_active filter (universal)
        if ($request->is_active !== null) {
            $query->where('is_active', $request->is_active);
        }

        // Extra filters
        foreach ($filterFields as $field) {
            if ($request->has($field) && $request->$field !== null) {
                $query->where($field, $request->$field);
            }
        }

        // Sort
        $sortBy = $request->sort_by ?? $defaultSort;
        $sortDirection = $request->sort_direction ?? $defaultDirection;
        $query->orderBy($sortBy, $sortDirection);

        // Paginate
        $perPage = $request->per_page ?? 10;

        if ($perPage == -1) {
            $all = $query->get();
            return response()->json([
                'status' => 'success',
                'data' => $all,
                'meta' => [
                    'current_page' => 1,
                    'per_page' => $all->count(),
                    'total' => $all->count(),
                    'last_page' => 1,
                ],
            ]);
        }

        $paginated = $query->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $paginated->items(),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'last_page' => $paginated->lastPage(),
            ],
        ]);
    }

    protected function storeResponse(Model $model): JsonResponse
    {
        $label = $model::$label ?? class_basename($model);

        return response()->json([
            'status' => 'success',
            'message' => "$label berhasil dibuat",
            'data' => $model,
        ], 201);
    }

    protected function updateResponse(Model $model): JsonResponse
    {
        $label = $model::$label ?? class_basename($model);

        return response()->json([
            'status' => 'success',
            'message' => "$label berhasil diupdate",
            'data' => $model,
        ]);
    }

    protected function destroyResponse(Model $model): JsonResponse
    {
        $label = $model::$label ?? class_basename($model);

        return response()->json([
            'status' => 'success',
            'message' => "$label berhasil dihapus",
        ]);
    }

    protected function showResponse(Model $model, array $additionalRelations = [], ?array $auditRelations = null): JsonResponse
    {
        $auditRelations = $auditRelations ?? [
            'createdBy:id,name',
            'updatedBy:id,name',
            'approvedBy:id,name',
            'printedBy:id,name',
        ];

        $model->load(array_merge($additionalRelations, $auditRelations));

        $data = $model->toArray();

        $auditMap = [
            'createdBy' => 'created_by_user',
            'updatedBy' => 'updated_by_user',
            'approvedBy' => 'approved_by_user',
            'printedBy' => 'printed_by_user',
        ];

        foreach ($auditRelations as $rel) {
            $relationName = explode(':', $rel)[0];
            if (isset($auditMap[$relationName])) {
                $data[$auditMap[$relationName]] = $model->$relationName;
            }
        }

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }
}
