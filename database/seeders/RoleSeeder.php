<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Permission;
use App\Models\RolePermission;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Create Administrator role with full access
        $admin = Role::updateOrCreate([
            'code' => 'ADMIN',
            'name' => 'Administrator',
            'description' => 'Full access to all features',
            'is_active' => true,
        ]);

        // Give admin all permissions with full access
        $permissions = Permission::all();
        foreach ($permissions as $permission) {
            RolePermission::updateOrCreate([
                'role_id' => $admin->id,
                'permission_id' => $permission->id,
                'can_view' => true,
                'can_create' => true,
                'can_edit' => true,
                'can_delete' => true,
                'can_approve' => true,
                'can_print' => true,
                'can_export' => true,
            ]);
        }

        // Create Manager role
        $manager = Role::updateOrCreate([
            'code' => 'MANAGER',
            'name' => 'Manager',
            'description' => 'Can manage most features but limited delete/approve',
            'is_active' => true,
        ]);

        foreach ($permissions as $permission) {
            RolePermission::updateOrCreate([
                'role_id' => $manager->id,
                'permission_id' => $permission->id,
                'can_view' => true,
                'can_create' => true,
                'can_edit' => true,
                'can_delete' => false,
                'can_approve' => true,
                'can_print' => true,
                'can_export' => true,
            ]);
        }

        // Create Staff role
        $staff = Role::updateOrCreate([
            'code' => 'STAFF',
            'name' => 'Staff',
            'description' => 'Basic access for daily operations',
            'is_active' => true,
        ]);

        // Staff gets limited permissions
        $staffPermissions = Permission::whereNotIn('code', [
            'master.role', 'master.user', 'master.permission'
        ])->get();

        foreach ($staffPermissions as $permission) {
            RolePermission::updateOrCreate([
                'role_id' => $staff->id,
                'permission_id' => $permission->id,
                'can_view' => true,
                'can_create' => true,
                'can_edit' => true,
                'can_delete' => false,
                'can_approve' => false,
                'can_print' => true,
                'can_export' => false,
            ]);
        }
    }
}
