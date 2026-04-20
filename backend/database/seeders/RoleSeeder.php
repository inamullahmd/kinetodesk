<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['code' => 'admin', 'name' => 'Administrator'],
            ['code' => 'manager', 'name' => 'Manager'],
            ['code' => 'sales', 'name' => 'Sales Associate'],
            ['code' => 'tech', 'name' => 'Technician'],
        ] as $role) {
            Role::updateOrCreate(['code' => $role['code']], $role);
        }
    }
}