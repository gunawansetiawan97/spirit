<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    public function index(Request $request)
    {
        $query = Permission::with('children');

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('code', 'like', "%{$request->search}%")
                    ->orWhere('name', 'like', "%{$request->search}%");
            });
        }

        if ($request->type) {
            $query->where('type', $request->type);
        }

        if ($request->parent_id === 'null') {
            $query->whereNull('parent_id');
        } elseif ($request->parent_id) {
            $query->where('parent_id', $request->parent_id);
        }

        $sortBy = $request->sort_by ?? 'sort_order';
        $sortDirection = $request->sort_direction ?? 'asc';
        $query->orderBy($sortBy, $sortDirection);

        $perPage = $request->per_page ?? 10;
        $permissions = $query->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $permissions->items(),
            'meta' => [
                'current_page' => $permissions->currentPage(),
                'per_page' => $permissions->perPage(),
                'total' => $permissions->total(),
                'last_page' => $permissions->lastPage(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'parent_id' => 'nullable|exists:permissions,id',
            'code' => 'required|string|max:50|unique:permissions,code',
            'name' => 'required|string|max:100',
            'icon' => 'nullable|string|max:50',
            'route' => 'nullable|string',
            'type' => 'required|in:menu,submenu,action',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ]);

        $permission = Permission::create($request->only([
            'parent_id', 'code', 'name', 'icon', 'route', 'type', 'sort_order', 'is_active'
        ]));

        return response()->json([
            'status' => 'success',
            'message' => 'Permission berhasil dibuat',
            'data' => $permission,
        ], 201);
    }

    public function show(string $id)
    {
        $permission = Permission::with('children')->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $permission,
        ]);
    }

    public function update(Request $request, string $id)
    {
        $permission = Permission::findOrFail($id);

        $request->validate([
            'parent_id' => 'nullable|exists:permissions,id',
            'code' => 'required|string|max:50|unique:permissions,code,' . $id,
            'name' => 'required|string|max:100',
            'icon' => 'nullable|string|max:50',
            'route' => 'nullable|string',
            'type' => 'required|in:menu,submenu,action',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ]);

        $permission->update($request->only([
            'parent_id', 'code', 'name', 'icon', 'route', 'type', 'sort_order', 'is_active'
        ]));

        return response()->json([
            'status' => 'success',
            'message' => 'Permission berhasil diupdate',
            'data' => $permission,
        ]);
    }

    public function destroy(string $id)
    {
        $permission = Permission::findOrFail($id);

        if ($permission->children()->count() > 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Permission tidak dapat dihapus karena memiliki sub-menu',
            ], 422);
        }

        $permission->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Permission berhasil dihapus',
        ]);
    }

    public function tree()
    {
        $permissions = Permission::with(['children' => function ($query) {
            $query->ordered()->with(['children' => function ($q) {
                $q->ordered();
            }]);
        }])
        ->whereNull('parent_id')
        ->ordered()
        ->get();

        return response()->json([
            'status' => 'success',
            'data' => $permissions,
        ]);
    }

    public function parents()
    {
        $permissions = Permission::whereNull('parent_id')
            ->orWhere('type', 'menu')
            ->ordered()
            ->get(['id', 'code', 'name', 'type']);

        return response()->json([
            'status' => 'success',
            'data' => $permissions,
        ]);
    }
}
