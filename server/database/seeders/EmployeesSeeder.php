<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Employee;

class EmployeesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        # Hardcoded employee data for seeding
        $employeesList = [            
            [
                'employee_number' => '9502746183',
                'first_name' => 'Hrithik',
                'last_name' => 'Roshan',
                'email' => 'hrithik.roshan@kinetodesk.local',
                'phone_number' => '+12025550111',
                'role' => 'owner',
                'active' => true,
            ],
            [
                'employee_number' => '4071936825',
                'first_name' => 'Priya',
                'last_name' => 'Sharma',
                'email' => 'priya.sharma@kinetodesk.local',
                'phone_number' => '+12025550112',
                'role' => 'manager',
                'active' => true,
            ],
            [
                'employee_number' => '5830147296',
                'first_name' => 'John',
                'last_name' => 'Carter',
                'email' => 'john.carter@kinetodesk.local',
                'phone_number' => '+12025550101',
                'role' => 'sales_representative',
                'active' => true,
            ],
            [
                'employee_number' => '2147698350',
                'first_name' => 'Sarah',
                'last_name' => 'Mitchell',
                'email' => 'sarah.mitchell@kinetodesk.local',
                'phone_number' => '+12025550102',
                'role' => 'sales_representative',
                'active' => true,
            ],
            [
                'employee_number' => '4618209573',
                'first_name' => 'Daniel',
                'last_name' => 'Reed',
                'email' => 'daniel.reed@kinetodesk.local',
                'phone_number' => '+12025550103',
                'role' => 'cashier',
                'active' => true,
            ],
            [
                'employee_number' => '9073154628',
                'first_name' => 'Michael',
                'last_name' => 'Brooks',
                'email' => 'michael.brooks@kinetodesk.local',
                'phone_number' => '+12025550104',
                'role' => 'sales_representative',
                'active' => true,
            ],
            [
                'employee_number' => '7385921046',
                'first_name' => 'Emily',
                'last_name' => 'Turner',
                'email' => 'emily.turner@kinetodesk.local',
                'phone_number' => '+12025550105',
                'role' => 'sales_representative',
                'active' => true,
            ],
            [
                'employee_number' => '6241859037',
                'first_name' => 'Jacob',
                'last_name' => 'Foster',
                'email' => 'jacob.foster@kinetodesk.local',
                'phone_number' => '+12025550106',
                'role' => 'sales_representative',
                'active' => true,
            ],
            [
                'employee_number' => '8459203617',
                'first_name' => 'Olivia',
                'last_name' => 'Parker',
                'email' => 'olivia.parker@kinetodesk.local',
                'phone_number' => '+12025550107',
                'role' => 'sales_representative',
                'active' => true,
            ],
            [
                'employee_number' => '3916728450',
                'first_name' => 'Ethan',
                'last_name' => 'Collins',
                'email' => 'ethan.collins@kinetodesk.local',
                'phone_number' => '+12025550108',
                'role' => 'sales_representative',
                'active' => true,
            ],
            [
                'employee_number' => '5728491036',
                'first_name' => 'Carlos',
                'last_name' => 'Ramirez',
                'email' => 'carlos.ramirez@kinetodesk.local',
                'phone_number' => '+12025550109',
                'role' => 'sales_representative',
                'active' => true,
            ],
            [
                'employee_number' => '1683047592',
                'first_name' => 'Sofia',
                'last_name' => 'Hernandez',
                'email' => 'sofia.hernandez@kinetodesk.local',
                'phone_number' => '+12025550110',
                'role' => 'sales_representative',
                'active' => true,
            ],
        ];
        
        foreach ($employeesList as $employee) {
            Employee::updateOrCreate(
                ['employee_number' => $employee['employee_number']],
                $employee
            );
        }


    }
}
