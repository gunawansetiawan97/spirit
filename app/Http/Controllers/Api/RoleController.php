<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Permission;
use App\Models\RolePermission;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        $query = Role::withCount('users');

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('code', 'like', "%{$request->search}%")
                    ->orWhere('name', 'like', "%{$request->search}%");
            });
        }

        if ($request->is_active !== null) {
            $query->where('is_active', $request->is_active);
        }

        $sortBy = $request->sort_by ?? 'name';
        $sortDirection = $request->sort_direction ?? 'asc';
        $query->orderBy($sortBy, $sortDirection);

        $perPage = $request->per_page ?? 10;
        $roles = $query->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $roles->items(),
            'meta' => [
                'current_page' => $roles->currentPage(),
                'per_page' => $roles->perPage(),
                'total' => $roles->total(),
                'last_page' => $roles->lastPage(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:20|unique:roles,code',
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $role = Role::create($request->only(['code', 'name', 'description', 'is_active']));

        return response()->json([
            'status' => 'success',
            'message' => 'Role berhasil dibuat',
            'data' => $role,
        ], 201);
    }

    public function show(Role $role)
    {
        $role->load('permissions');

        return response()->json([
            'status' => 'success',
            'data' => $role,
        ]);
    }

    public function update(Request $request, Role $role)
    {
        $request->validate([
            'code' => 'required|string|max:20|unique:roles,code,' . $role->id,
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $role->update($request->only(['code', 'name', 'description', 'is_active']));

        return response()->json([
            'status' => 'success',
            'message' => 'Role berhasil diupdate',
            'data' => $role,
        ]);
    }

    public function destroy(Role $role)
    {
        if ($role->users()->count() > 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Role tidak dapat dihapus karena masih digunakan oleh user',
            ], 422);
        }

        $role->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Role berhasil dihapus',
        ]);
    }

    public function all()
    {
        $roles = Role::active()->orderBy('name')->get(['id', 'uuid', 'code', 'name']);

        return response()->json([
            'status' => 'success',
            'data' => $roles,
        ]);
    }

    public function permissions(Role $role)
    {
        $role->load('permissions');
        $allPermissions = Permission::with('children')->whereNull('parent_id')->ordered()->get();

        $rolePermissions = [];
        foreach ($role->permissions as $perm) {
            $rolePermissions[$perm->id] = [
                'can_view' => $perm->pivot->can_view,
                'can_create' => $perm->pivot->can_create,
                'can_edit' => $perm->pivot->can_edit,
                'can_delete' => $perm->pivot->can_delete,
                'can_approve' => $perm->pivot->can_approve,
                'can_print' => $perm->pivot->can_print,
                'can_export' => $perm->pivot->can_export,
            ];
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'role' => $role,
                'all_permissions' => $allPermissions,
                'role_permissions' => $rolePermissions,
            ],
        ]);
    }

    public function updatePermissions(Request $request, Role $role)
    {
        $request->validate([
            'permissions' => 'required|array',
            'permissions.*.permission_id' => 'required|exists:permissions,id',
            'permissions.*.can_view' => 'boolean',
            'permissions.*.can_create' => 'boolean',
            'permissions.*.can_edit' => 'boolean',
            'permissions.*.can_delete' => 'boolean',
            'permissions.*.can_approve' => 'boolean',
            'permissions.*.can_print' => 'boolean',
            'permissions.*.can_export' => 'boolean',
        ]);

        // Delete existing permissions
        RolePermission::where('role_id', $role->id)->delete();

        // Insert new permissions
        foreach ($request->permissions as $perm) {
            if ($perm['can_view'] ?? false) {
                RolePermission::create([
                    'role_id' => $role->id,
                    'permission_id' => $perm['permission_id'],
                    'can_view' => $perm['can_view'] ?? false,
                    'can_create' => $perm['can_create'] ?? false,
                    'can_edit' => $perm['can_edit'] ?? false,
                    'can_delete' => $perm['can_delete'] ?? false,
                    'can_approve' => $perm['can_approve'] ?? false,
                    'can_print' => $perm['can_print'] ?? false,
                    'can_export' => $perm['can_export'] ?? false,
                ]);
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Hak akses role berhasil diupdate',
        ]);
    }
}
