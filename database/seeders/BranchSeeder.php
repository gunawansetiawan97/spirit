<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        $branches = [
            [
                'code' => 'HO',
                'name' => 'Head Office',
                'address' => 'Jl. Sudirman No. 1, Jakarta',
                'phone' => '021-1234567',
                'is_active' => true,
            ],
            [
                'code' => 'BDG',
                'name' => 'Cabang Bandung',
                'address' => 'Jl. Asia Afrika No. 10, Bandung',
                'phone' => '022-1234567',
                'is_active' => true,
            ],
            [
                'code' => 'SBY',
                'name' => 'Cabang Surabaya',
                'address' => 'Jl. Tunjungan No. 5, Surabaya',
                'phone' => '031-1234567',
                'is_active' => true,
            ],
        ];

        foreach ($branches as $branch) {
            Branch::create($branch);
        }
    }
}
