<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            CategorySeeder::class,
            BrandSeeder::class,
            SupplierSeeder::class,
            EmployeeSeeder::class,
            CustomerSeeder::class,
            ProductCatalogSeeder::class,
            BuildTemplateSeeder::class,
            HistoricalStoreSeeder::class,
        ]);
    }
}