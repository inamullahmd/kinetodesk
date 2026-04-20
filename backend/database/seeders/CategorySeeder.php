<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Laptops',
            'Desktops',
            'CPUs',
            'GPUs',
            'Motherboards',
            'RAM',
            'Storage',
            'Power Supplies',
            'Cases',
            'Cooling',
            'Monitors',
            'Keyboards',
            'Mice',
            'Networking',
            'Printers',
            'Accessories',
            'Services',
            'Custom Build Components',
        ];

        foreach ($categories as $name) {
            Category::updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'parent_id' => null]
            );
        }
    }
}