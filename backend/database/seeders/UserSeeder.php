<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@kinetodesk.local'],
            [
                'name' => 'Inam Admin',
                'email' => 'admin@kinetodesk.local',
                'password' => Hash::make('Admin@12345'),
                'role' => 'admin',
            ]
        );
    }
}