<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $employees = [
            ['employee_code' => 'EMP-0001', 'first_name' => 'Inam', 'last_name' => 'Admin', 'email' => 'admin@kinetodesk.local', 'role' => 'admin'],
            ['employee_code' => 'EMP-0002', 'first_name' => 'Sarah', 'last_name' => 'Lopez', 'email' => 'slopez@kinetodesk.local', 'role' => 'manager'],
            ['employee_code' => 'EMP-0003', 'first_name' => 'James', 'last_name' => 'Carter', 'email' => 'jcarter@kinetodesk.local', 'role' => 'sales'],
            ['employee_code' => 'EMP-0004', 'first_name' => 'Alicia', 'last_name' => 'Nguyen', 'email' => 'anguyen@kinetodesk.local', 'role' => 'sales'],
            ['employee_code' => 'EMP-0005', 'first_name' => 'Marcus', 'last_name' => 'Hill', 'email' => 'mhill@kinetodesk.local', 'role' => 'sales'],
            ['employee_code' => 'EMP-0006', 'first_name' => 'David', 'last_name' => 'Patel', 'email' => 'dpatel@kinetodesk.local', 'role' => 'tech'],
            ['employee_code' => 'EMP-0007', 'first_name' => 'Elena', 'last_name' => 'Brooks', 'email' => 'ebrooks@kinetodesk.local', 'role' => 'tech'],
            ['employee_code' => 'EMP-0008', 'first_name' => 'Ryan', 'last_name' => 'Cole', 'email' => 'rcole@kinetodesk.local', 'role' => 'sales'],
            ['employee_code' => 'EMP-0009', 'first_name' => 'Priya', 'last_name' => 'Mehta', 'email' => 'pmehta@kinetodesk.local', 'role' => 'tech'],
        ];

        foreach ($employees as $index => $row) {
            $role = Role::where('code', $row['role'])->firstOrFail();

            Employee::updateOrCreate(
                ['employee_code' => $row['employee_code']],
                [
                    'first_name' => $row['first_name'],
                    'last_name' => $row['last_name'],
                    'email' => $row['email'],
                    'phone' => '918-555-' . str_pad((string)(1000 + $index), 4, '0', STR_PAD_LEFT),
                    'role_id' => $role->id,
                    'commission_type' => $row['role'] === 'sales' ? 'percent' : 'none',
                    'commission_rate' => $row['role'] === 'sales' ? 3.50 : 0.00,
                    'is_active' => true,
                    'hired_at' => now()->subYears(rand(1, 12))->toDateString(),
                    'password_hash' => Hash::make($row['role'] === 'admin' ? 'Admin@12345' : Str::random(24)),
                ]
            );
        }
    }
}