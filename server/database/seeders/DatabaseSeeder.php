<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CategorySeeder::class,
            SupplierSeeder::class,
            CustomerSeeder::class,
            ProductSeeder::class,
            PurchaseOrderSeeder::class,
            InventorySeeder::class,
            SalesOrderSeeder::class,
            RecalculateInventorySeeder::class,
        ]);
    }
}