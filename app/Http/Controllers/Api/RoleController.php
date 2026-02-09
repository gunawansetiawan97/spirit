<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Permission;
use App\Models\RolePermission;
use App\Traits\HasIndexQuery;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    use HasIndexQuery;

    public function index(Request $request)
    {
        return $this->paginatedResponse(Role::withCount('users'), $request, [
            'searchFields' => ['code', 'name'],
            'defaultSort' => 'name',
        ]);
    }

    public function store(Request $request)
    {
        $request->validate(Role::storeRules());

        $role = Role::create($request->only(['code', 'name', 'description', 'is_active']));

        return $this->storeResponse($role);
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
        $request->validate(Role::updateRules($role->id));

        $role->update($request->only(['code', 'name', 'description', 'is_active']));

        return $this->updateResponse($role);
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

        return $this->destroyResponse($role);
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

        RolePermission::where('role_id', $role->id)->delete();

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
