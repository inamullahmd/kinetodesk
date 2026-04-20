<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $categoryBrandMap = [
            'Laptops' => ['Dell', 'HP', 'Lenovo', 'ASUS', 'Acer'],
            'Desktops' => ['Dell', 'HP', 'Lenovo', 'ASUS', 'MSI'],
            'CPUs' => ['Intel', 'AMD'],
            'GPUs' => ['NVIDIA', 'AMD', 'Gigabyte', 'MSI'],
            'Motherboards' => ['ASUS', 'Gigabyte', 'MSI', 'ASRock'],
            'RAM' => ['Corsair', 'Kingston', 'Crucial'],
            'Storage' => ['Samsung', 'Seagate', 'Western Digital', 'Crucial'],
            'Power Supplies' => ['Corsair', 'Cooler Master', 'EVGA', 'Antec'],
            'Cases' => ['NZXT', 'Cooler Master', 'Antec'],
            'Cooling' => ['Noctua', 'DeepCool', 'Cooler Master'],
            'Monitors' => ['LG', 'Samsung', 'ASUS', 'ViewSonic'],
            'Keyboards' => ['Logitech', 'Razer', 'Corsair'],
            'Mice' => ['Logitech', 'Razer', 'Corsair'],
            'Networking' => ['TP-Link', 'ASUS'],
            'Printers' => ['Canon', 'Brother', 'HP'],
            'Accessories' => ['Logitech', 'Razer', 'Corsair'],
            'Services' => ['Dell'],
            'Custom Build Components' => ['Corsair', 'Cooler Master', 'NZXT'],
        ];

        $categoryDescriptors = [
            'Laptops' => ['Business 14', 'Business 15', 'Student Pro', 'Ultrabook', 'Workstation 16'],
            'Desktops' => ['Office Tower', 'Mini Desktop', 'Gaming Tower', 'Workstation'],
            'CPUs' => ['Core i3', 'Core i5', 'Core i7', 'Ryzen 5', 'Ryzen 7'],
            'GPUs' => ['RTX 3060', 'RTX 4060', 'RTX 4070', 'RX 7600', 'RX 7700 XT'],
            'Motherboards' => ['B550 Board', 'B650 Board', 'Z690 Board', 'Z790 Board'],
            'RAM' => ['8GB DDR4 Kit', '16GB DDR4 Kit', '32GB DDR4 Kit', '16GB DDR5 Kit'],
            'Storage' => ['500GB SATA SSD', '1TB NVMe SSD', '2TB NVMe SSD', '4TB HDD'],
            'Power Supplies' => ['550W Bronze PSU', '650W Gold PSU', '750W Gold PSU'],
            'Cases' => ['ATX Mid Tower', 'mATX Compact Case', 'Tempered Glass Case'],
            'Cooling' => ['120mm Air Cooler', '240mm Liquid Cooler', 'Tower Air Cooler'],
            'Monitors' => ['24-inch FHD Monitor', '27-inch QHD Monitor', '32-inch 4K Monitor'],
            'Keyboards' => ['Office Keyboard', 'Mechanical Keyboard', 'Wireless Keyboard'],
            'Mice' => ['Wireless Mouse', 'Gaming Mouse', 'Productivity Mouse'],
            'Networking' => ['AX1800 Router', 'AX3000 Router', 'Gigabit Switch'],
            'Printers' => ['Mono Laser Printer', 'Color Laser Printer', 'All-in-One Printer'],
            'Accessories' => ['USB-C Dock', 'Laptop Sleeve', 'Webcam', 'Headset'],
            'Services' => ['Diagnostic Service', 'OS Installation', 'Data Backup', 'General Repair Labor', 'Virus Removal'],
            'Custom Build Components' => ['RGB Fan Kit', 'Cable Extension Kit', 'Thermal Paste Kit'],
        ];

        $serializedCategories = [
            'Laptops', 'Desktops', 'GPUs', 'Storage', 'Monitors', 'Networking', 'Printers'
        ];

        $serviceCategories = ['Services'];

        $suppliers = Supplier::query()->pluck('id')->all();

        foreach ($categoryBrandMap as $categoryName => $brands) {
            $category = Category::where('name', $categoryName)->firstOrFail();

            $itemsPerBrand = $categoryName === 'Services' ? 5 : 6;

            foreach ($brands as $brandName) {
                $brand = Brand::where('name', $brandName)->firstOrFail();
                $descriptors = $categoryDescriptors[$categoryName];

                for ($i = 1; $i <= $itemsPerBrand; $i++) {
                    $descriptor = $descriptors[array_rand($descriptors)];
                    $name = "{$brandName} {$descriptor} {$i}";
                    $sku = strtoupper(substr($categoryName, 0, 3)) . '-' . strtoupper(substr($brandName, 0, 3)) . '-' . str_pad((string)$i, 3, '0', STR_PAD_LEFT) . '-' . strtoupper(substr(md5($name), 0, 4));

                    $basePrice = match ($categoryName) {
                        'Laptops' => rand(650, 1650),
                        'Desktops' => rand(550, 1800),
                        'CPUs' => rand(120, 450),
                        'GPUs' => rand(240, 950),
                        'Motherboards' => rand(100, 320),
                        'RAM' => rand(35, 180),
                        'Storage' => rand(50, 260),
                        'Power Supplies' => rand(60, 180),
                        'Cases' => rand(55, 220),
                        'Cooling' => rand(25, 160),
                        'Monitors' => rand(130, 650),
                        'Keyboards' => rand(25, 160),
                        'Mice' => rand(20, 120),
                        'Networking' => rand(45, 300),
                        'Printers' => rand(140, 700),
                        'Accessories' => rand(15, 180),
                        'Services' => rand(39, 149),
                        'Custom Build Components' => rand(10, 90),
                        default => rand(25, 120),
                    };

                    Product::updateOrCreate(
                        ['sku' => $sku],
                        [
                            'name' => $name,
                            'category_id' => $category->id,
                            'brand_id' => $brand->id,
                            'product_type' => in_array($categoryName, $serviceCategories, true) ? 'service' : 'inventory',
                            'is_serialized' => in_array($categoryName, $serializedCategories, true),
                            'unit_of_measure' => 'pcs',
                            'reorder_threshold' => in_array($categoryName, $serviceCategories, true) ? 0 : rand(3, 20),
                            'preferred_supplier_id' => $suppliers[array_rand($suppliers)] ?? null,
                            'current_stock' => 0,
                            'reserved_stock' => 0,
                            'sell_price' => number_format($basePrice + (rand(0, 99) / 100), 2, '.', ''),
                            'is_active' => true,
                            'description' => "{$name} stocked by the store for retail, business, service, and custom build workflows.",
                        ]
                    );
                }
            }
        }
    }
}