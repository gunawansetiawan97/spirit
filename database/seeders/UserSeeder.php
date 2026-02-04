<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use App\Models\Branch;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::where('code', 'ADMIN')->first();
        $managerRole = Role::where('code', 'MANAGER')->first();
        $staffRole = Role::where('code', 'STAFF')->first();
        $headOffice = Branch::where('code', 'HO')->first();

        // Create admin user
        User::create([
            'name' => 'Administrator',
            'email' => 'admin@spirit.com',
            'password' => Hash::make('password'),
            'role_id' => $adminRole->id,
            'branch_id' => $headOffice->id,
            'is_active' => true,
        ]);

        // Create manager user
        User::create([
            'name' => 'Manager',
            'email' => 'manager@spirit.com',
            'password' => Hash::make('password'),
            'role_id' => $managerRole->id,
            'branch_id' => $headOffice->id,
            'is_active' => true,
        ]);

        // Create staff user
        User::create([
            'name' => 'Staff',
            'email' => 'staff@spirit.com',
            'password' => Hash::make('password'),
            'role_id' => $staffRole->id,
            'branch_id' => $headOffice->id,
            'is_active' => true,
        ]);
    }
}
