<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        $brands = [
            'Dell', 'HP', 'Lenovo', 'ASUS', 'Acer', 'MSI', 'Gigabyte',
            'Samsung', 'LG', 'Intel', 'AMD', 'NVIDIA', 'Corsair',
            'Kingston', 'Crucial', 'Seagate', 'Western Digital',
            'Logitech', 'Razer', 'TP-Link', 'Canon', 'Brother',
            'Cooler Master', 'NZXT', 'EVGA', 'ASRock', 'Antec',
            'DeepCool', 'Noctua', 'ViewSonic'
        ];

        foreach ($brands as $name) {
            Brand::updateOrCreate(['name' => $name], ['name' => $name]);
        }
    }
}