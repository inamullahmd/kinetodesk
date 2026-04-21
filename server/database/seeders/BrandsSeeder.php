<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;

class BrandsSeeder extends Seeder
{
    public function run(): void
    {
        $brands = [
            [
                'name' => 'Dell',
                'slug' => 'dell',
                'website' => 'https://www.dell.com',
                'description' => 'Desktops, laptops, monitors, and accessories',
                'active' => true,
            ],
            [
                'name' => 'HP',
                'slug' => 'hp',
                'website' => 'https://www.hp.com',
                'description' => 'Laptops, desktops, printers, and accessories',
                'active' => true,
            ],
            [
                'name' => 'Lenovo',
                'slug' => 'lenovo',
                'website' => 'https://www.lenovo.com',
                'description' => 'Laptops, desktops, monitors, and business systems',
                'active' => true,
            ],
            [
                'name' => 'ASUS',
                'slug' => 'asus',
                'website' => 'https://www.asus.com',
                'description' => 'Laptops, motherboards, graphics cards, and peripherals',
                'active' => true,
            ],
            [
                'name' => 'Acer',
                'slug' => 'acer',
                'website' => 'https://www.acer.com',
                'description' => 'Laptops, desktops, and monitors',
                'active' => true,
            ],
            [
                'name' => 'MSI',
                'slug' => 'msi',
                'website' => 'https://www.msi.com',
                'description' => 'Gaming laptops, motherboards, graphics cards, and monitors',
                'active' => true,
            ],
            [
                'name' => 'Apple',
                'slug' => 'apple',
                'website' => 'https://www.apple.com',
                'description' => 'Laptops, desktops, tablets, and accessories',
                'active' => true,
            ],
            [
                'name' => 'Samsung',
                'slug' => 'samsung',
                'website' => 'https://www.samsung.com',
                'description' => 'SSDs, monitors, memory cards, and storage products',
                'active' => true,
            ],
            [
                'name' => 'Kingston',
                'slug' => 'kingston',
                'website' => 'https://www.kingston.com',
                'description' => 'Memory, SSDs, USB drives, and storage products',
                'active' => true,
            ],
            [
                'name' => 'Corsair',
                'slug' => 'corsair',
                'website' => 'https://www.corsair.com',
                'description' => 'Memory, PSUs, cooling, cases, and gaming peripherals',
                'active' => true,
            ],
            [
                'name' => 'Crucial',
                'slug' => 'crucial',
                'website' => 'https://www.crucial.com',
                'description' => 'Memory and SSD upgrades',
                'active' => true,
            ],
            [
                'name' => 'Western Digital',
                'slug' => 'western-digital',
                'website' => 'https://www.westerndigital.com',
                'description' => 'Internal and external storage products',
                'active' => true,
            ],
            [
                'name' => 'Seagate',
                'slug' => 'seagate',
                'website' => 'https://www.seagate.com',
                'description' => 'Hard drives and storage products',
                'active' => true,
            ],
            [
                'name' => 'SanDisk',
                'slug' => 'sandisk',
                'website' => 'https://www.sandisk.com',
                'description' => 'Memory cards, flash drives, and SSD storage',
                'active' => true,
            ],
            [
                'name' => 'Intel',
                'slug' => 'intel',
                'website' => 'https://www.intel.com',
                'description' => 'Processors, networking products, and computing platforms',
                'active' => true,
            ],
            [
                'name' => 'AMD',
                'slug' => 'amd',
                'website' => 'https://www.amd.com',
                'description' => 'Processors and graphics cards',
                'active' => true,
            ],
            [
                'name' => 'NVIDIA',
                'slug' => 'nvidia',
                'website' => 'https://www.nvidia.com',
                'description' => 'Graphics processing units and AI computing hardware',
                'active' => true,
            ],
            [
                'name' => 'Gigabyte',
                'slug' => 'gigabyte',
                'website' => 'https://www.gigabyte.com',
                'description' => 'Motherboards, graphics cards, laptops, and monitors',
                'active' => true,
            ],
            [
                'name' => 'ASRock',
                'slug' => 'asrock',
                'website' => 'https://www.asrock.com',
                'description' => 'Motherboards, mini PCs, and graphics products',
                'active' => true,
            ],
            [
                'name' => 'EVGA',
                'slug' => 'evga',
                'website' => 'https://www.evga.com',
                'description' => 'Power supplies, graphics cards, and PC components',
                'active' => true,
            ],
            [
                'name' => 'Cooler Master',
                'slug' => 'cooler-master',
                'website' => 'https://www.coolermaster.com',
                'description' => 'Cases, coolers, power supplies, and peripherals',
                'active' => true,
            ],
            [
                'name' => 'Noctua',
                'slug' => 'noctua',
                'website' => 'https://noctua.at',
                'description' => 'CPU coolers, thermal compounds, and cooling fans',
                'active' => true,
            ],
            [
                'name' => 'NZXT',
                'slug' => 'nzxt',
                'website' => 'https://nzxt.com',
                'description' => 'Cases, coolers, motherboards, and gaming hardware',
                'active' => true,
            ],
            [
                'name' => 'Logitech',
                'slug' => 'logitech',
                'website' => 'https://www.logitech.com',
                'description' => 'Mice, keyboards, webcams, audio, and streaming gear',
                'active' => true,
            ],
            [
                'name' => 'Razer',
                'slug' => 'razer',
                'website' => 'https://www.razer.com',
                'description' => 'Gaming laptops, mice, keyboards, audio, and accessories',
                'active' => true,
            ],
            [
                'name' => 'SteelSeries',
                'slug' => 'steelseries',
                'website' => 'https://steelseries.com',
                'description' => 'Gaming peripherals and audio products',
                'active' => true,
            ],
            [
                'name' => 'TP-Link',
                'slug' => 'tp-link',
                'website' => 'https://www.tp-link.com',
                'description' => 'Routers, switches, adapters, and networking products',
                'active' => true,
            ],
            [
                'name' => 'Netgear',
                'slug' => 'netgear',
                'website' => 'https://www.netgear.com',
                'description' => 'Networking hardware and connectivity products',
                'active' => true,
            ],
            [
                'name' => 'Canon',
                'slug' => 'canon',
                'website' => 'https://www.canon.com',
                'description' => 'Printers, scanners, and imaging hardware',
                'active' => true,
            ],
            [
                'name' => 'Epson',
                'slug' => 'epson',
                'website' => 'https://epson.com',
                'description' => 'Printers, scanners, and office imaging devices',
                'active' => true,
            ],
        ];

        foreach ($brands as $brand) {
            Brand::updateOrCreate(
                ['slug' => $brand['slug']],
                $brand
            );
        }
    }
}