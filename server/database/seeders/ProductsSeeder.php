<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Product;
use Illuminate\Support\Str;

class ProductsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Retrieve a mapping of category names to their IDs and brand names to IDs.
        $categories = Category::pluck('id', 'name')->toArray();
        $brands     = Brand::pluck('id', 'name')->toArray();

        // Seed each product category with realistic products and variations. These
        // helper methods construct multiple entries per base model to reach over
        // eight hundred total records. High‑value items are serialized and
        // commission values are calculated based on price tiers.
        $this->seedLaptops($categories, $brands);
        $this->seedDesktopPCs($categories, $brands);
        $this->seedTablets($categories, $brands);
        $this->seedHandhelds($categories, $brands);
        $this->seedProcessors($categories, $brands);
        $this->seedGraphicsCards($categories, $brands);
        $this->seedMotherboards($categories, $brands);
        $this->seedMemory($categories, $brands);
        $this->seedPowerSupplies($categories, $brands);
        $this->seedCases($categories, $brands);
        $this->seedInternalSSDs($categories, $brands);
        $this->seedHardDrives($categories, $brands);
        $this->seedExternalStorage($categories, $brands);
        $this->seedMemoryCards($categories, $brands);
        $this->seedCpuCooling($categories, $brands);
        $this->seedCaseFans($categories, $brands);
        $this->seedThermalSolutions($categories, $brands);
        $this->seedTools($categories, $brands);
        $this->seedMonitors($categories, $brands);
        $this->seedKeyboards($categories, $brands);
        $this->seedMice($categories, $brands);
        $this->seedAudio($categories, $brands);
        $this->seedStreamingGear($categories, $brands);
        $this->seedWirelessNetworking($categories, $brands);
        $this->seedWiredNetworking($categories, $brands);
        $this->seedCablesAdapters($categories, $brands);
        $this->seedOperatingSystems($categories, $brands);
        $this->seedProductivitySoftware($categories, $brands);
        $this->seedSecuritySoftware($categories, $brands);
    }

    /**
     * Generate laptop products. Variations include different memory, storage
     * capacities and colours. Model numbers are based off of real world
     * identifiers (e.g. MRX3LL/A for MacBook Air) with a suffix describing
     * configuration. Prices are calculated relative to a base model. Laptops
     * are high‑value items and marked as serialized.
     */
    private function seedLaptops(array $categories, array $brands): void
    {
        $baseModels = [
            [
                'brand'      => 'Apple',
                'name'       => 'MacBook Air 13"',
                'model'      => 'MRX3LL/A',
                'basePrice'  => 999.97, // price from Best Buy for base configuration【495675361202639†screenshot】
                'baseRam'    => 8,
                'baseStorage'=> 256,
                'colors'     => ['Midnight', 'Silver'],
                'rams'       => [8, 16, 32],
                'storages'   => [256, 512, 1024, 2048],
            ],
            [
                'brand'      => 'Apple',
                'name'       => 'MacBook Air 15"',
                'model'      => 'MREW3LL/A',
                'basePrice'  => 1299.00,
                'baseRam'    => 8,
                'baseStorage'=> 256,
                'colors'     => ['Midnight', 'Silver'],
                'rams'       => [8, 16, 32],
                'storages'   => [256, 512, 1024, 2048],
            ],
            [
                'brand'      => 'Apple',
                'name'       => 'MacBook Pro 14"',
                'model'      => 'MRXG3LL/A',
                'basePrice'  => 1999.00,
                'baseRam'    => 16,
                'baseStorage'=> 512,
                'colors'     => ['Space Gray', 'Silver'],
                'rams'       => [16, 32, 64],
                'storages'   => [512, 1024, 2048, 4096],
            ],
            [
                'brand'      => 'Dell',
                'name'       => 'XPS 13 9350',
                'model'      => 'JRRLYB4', // model number from Best Buy listing【312536302722983†screenshot】
                'basePrice'  => 1129.99, // price for 16GB/1TB configuration【312536302722983†screenshot】
                'baseRam'    => 16,
                'baseStorage'=> 1024,
                'colors'     => ['Platinum', 'Graphite'],
                'rams'       => [16, 32, 64],
                'storages'   => [512, 1024, 2048],
            ],
            [
                'brand'      => 'HP',
                'name'       => 'Spectre x360 14',
                'model'      => '14-ef2013dx',
                'basePrice'  => 1299.99,
                'baseRam'    => 16,
                'baseStorage'=> 512,
                'colors'     => ['Nightfall Black', 'Nocturne Blue'],
                'rams'       => [16, 32, 64],
                'storages'   => [512, 1024, 2048],
            ],
            [
                'brand'      => 'Lenovo',
                'name'       => 'ThinkPad X1 Carbon Gen 11',
                'model'      => '21HM0000US',
                'basePrice'  => 1599.00,
                'baseRam'    => 16,
                'baseStorage'=> 512,
                'colors'     => ['Black'],
                'rams'       => [16, 32, 64],
                'storages'   => [512, 1024, 2048, 4096],
            ],
            [
                'brand'      => 'ASUS',
                'name'       => 'ROG Zephyrus G14',
                'model'      => 'GA402NU',
                'basePrice'  => 1599.99,
                'baseRam'    => 16,
                'baseStorage'=> 512,
                'colors'     => ['Moonlight White', 'Eclipse Gray'],
                'rams'       => [16, 32, 64],
                'storages'   => [512, 1024, 2048],
            ],
            [
                'brand'      => 'Acer',
                'name'       => 'Swift X 14',
                'model'      => 'SFX14-71G',
                'basePrice'  => 1099.99,
                'baseRam'    => 16,
                'baseStorage'=> 512,
                'colors'     => ['Steel Gray', 'Green'],
                'rams'       => [16, 32, 64],
                'storages'   => [512, 1024, 2048],
            ],
            [
                'brand'      => 'MSI',
                'name'       => 'Prestige 13 Evo',
                'model'      => 'A13M',
                'basePrice'  => 1199.99,
                'baseRam'    => 16,
                'baseStorage'=> 512,
                'colors'     => ['Urban Silver', 'Pure White'],
                'rams'       => [16, 32, 64],
                'storages'   => [512, 1024, 2048],
            ],
        ];

        foreach ($baseModels as $base) {
            $brandName     = $base['brand'];
            $categoryName  = 'Laptops';
            foreach ($base['rams'] as $ram) {
                foreach ($base['storages'] as $storage) {
                    foreach ($base['colors'] as $color) {
                        // Build model number suffix indicating configuration
                        $suffix      = strtoupper($ram . 'G' . $storage . 'G');
                        $modelNumber = $base['model'] . '-' . $suffix;

                        // Title includes memory, storage and colour
                        $title = $base['name'] . ' ' . $ram . 'GB/' . $storage . 'GB - ' . $color;

                        // Description summarises configuration
                        $description = $base['brand'] . ' ' . $base['name'] . ' with ' . $ram . 'GB RAM and ' . $storage . 'GB SSD in ' . $color . ' finish.';

                        // Price: base plus increments for higher RAM and storage
                        $price = $base['basePrice'];
                        if ($ram > $base['baseRam']) {
                            $price += (($ram - $base['baseRam']) / 8) * 150;
                        }
                        if ($storage > $base['baseStorage']) {
                            $price += (($storage - $base['baseStorage']) / 256) * 60;
                        }

                        $this->makeProduct(
                            $categories,
                            $brands,
                            $categoryName,
                            $brandName,
                            $modelNumber,
                            $title,
                            $description,
                            $price,
                            true
                        );
                    }
                }
            }
        }
    }

    /**
     * Generate desktop PC products with combinations of CPUs, GPUs, and memory.
     * Desktops are also high‑value items and are serialized. Base models
     * represent popular gaming and productivity towers from major brands.
     */
    private function seedDesktopPCs(array $categories, array $brands): void
    {
        $baseModels = [
            [
                'brand' => 'Dell',
                'name'  => 'XPS Desktop',
                'model' => 'XPS8960',
                'basePrice' => 1199.99,
                'cpus'  => ['Intel Core i5-13400', 'Intel Core i7-13700', 'Intel Core i9-13900K'],
                'gpus'  => ['NVIDIA RTX 4060', 'NVIDIA RTX 4070', 'NVIDIA RTX 4080'],
                'rams'  => [16, 32, 64],
                'storage' => [512, 1024, 2048],
            ],
            [
                'brand' => 'HP',
                'name'  => 'Omen 45L',
                'model' => 'GT22-0450',
                'basePrice' => 1499.99,
                'cpus'  => ['Intel Core i7-14700KF', 'Intel Core i9-14900KF', 'AMD Ryzen 9 7950X3D'],
                'gpus'  => ['NVIDIA RTX 4070 Ti', 'NVIDIA RTX 4080', 'NVIDIA RTX 4090'],
                'rams'  => [32, 64, 128],
                'storage' => [1024, 2048, 4096],
            ],
            [
                'brand' => 'Lenovo',
                'name'  => 'Legion Tower 7i',
                'model' => '90U80000US',
                'basePrice' => 1399.99,
                'cpus'  => ['Intel Core i5-13600KF', 'Intel Core i7-13700KF', 'Intel Core i9-13900KF'],
                'gpus'  => ['NVIDIA RTX 4060 Ti', 'NVIDIA RTX 4070 Ti', 'NVIDIA RTX 4080'],
                'rams'  => [16, 32, 64],
                'storage' => [1024, 2048, 4096],
            ],
            [
                'brand' => 'ASUS',
                'name'  => 'ROG Strix G15',
                'model' => 'G15DK',
                'basePrice' => 1299.99,
                'cpus'  => ['AMD Ryzen 5 7600X', 'AMD Ryzen 7 7800X3D', 'AMD Ryzen 9 7950X'],
                'gpus'  => ['AMD Radeon RX 7600', 'AMD Radeon RX 7700 XT', 'AMD Radeon RX 7800 XT'],
                'rams'  => [16, 32, 64],
                'storage' => [1024, 2048, 4096],
            ],
            [
                'brand' => 'MSI',
                'name'  => 'Infinite RS',
                'model' => 'A13VE',
                'basePrice' => 1499.99,
                'cpus'  => ['Intel Core i7-13700KF', 'Intel Core i9-13900KF', 'AMD Ryzen 9 7900X'],
                'gpus'  => ['NVIDIA RTX 4070', 'NVIDIA RTX 4080', 'NVIDIA RTX 4090'],
                'rams'  => [32, 64, 128],
                'storage' => [1024, 2048, 4096],
            ],
            [
                'brand' => 'CyberPowerPC',
                'name'  => 'Gamer Supreme',
                'model' => 'SYX7200',
                'basePrice' => 1299.99,
                'cpus'  => ['AMD Ryzen 5 7600', 'AMD Ryzen 7 7700X', 'AMD Ryzen 9 7900X'],
                'gpus'  => ['NVIDIA RTX 4060', 'NVIDIA RTX 4070', 'AMD Radeon RX 7800 XT'],
                'rams'  => [16, 32, 64],
                'storage' => [1024, 2048, 4096],
            ],
            [
                'brand' => 'Acer',
                'name'  => 'Predator Orion',
                'model' => 'PO7-650',
                'basePrice' => 1499.99,
                'cpus'  => ['Intel Core i5-13400F', 'Intel Core i7-13700F', 'Intel Core i9-13900F'],
                'gpus'  => ['NVIDIA RTX 4060', 'NVIDIA RTX 4070', 'NVIDIA RTX 4080'],
                'rams'  => [16, 32, 64],
                'storage' => [1024, 2048, 4096],
            ],
        ];

        $categoryName = 'Desktop PCs';
        foreach ($baseModels as $base) {
            foreach ($base['cpus'] as $cpu) {
                foreach ($base['gpus'] as $gpu) {
                    foreach ($base['rams'] as $ram) {
                        foreach ($base['storage'] as $storage) {
                            $modelNumber = $base['model'] . '-' . preg_replace('/[^A-Za-z0-9]/', '', $cpu) . '-' . preg_replace('/[^A-Za-z0-9]/', '', $gpu) . '-' . $ram . 'G' . $storage . 'G';
                            $title       = $base['name'] . ' | ' . $cpu . ' | ' . $gpu . ' | ' . $ram . 'GB RAM / ' . $storage . 'GB SSD';
                            $description = $base['brand'] . ' ' . $base['name'] . ' desktop configured with ' . $cpu . ', ' . $gpu . ', ' . $ram . 'GB RAM and ' . $storage . 'GB SSD.';
                            $price       = $base['basePrice'];
                            // CPU price factor
                            if (str_contains($cpu, 'i5') || str_contains($cpu, 'Ryzen 5')) {
                                $price += 100;
                            } elseif (str_contains($cpu, 'i7') || str_contains($cpu, 'Ryzen 7')) {
                                $price += 200;
                            } else {
                                $price += 400;
                            }
                            // GPU price factor
                            if (str_contains($gpu, '4060') || str_contains($gpu, 'RX 7600')) {
                                $price += 200;
                            } elseif (str_contains($gpu, '4070') || str_contains($gpu, '7700')) {
                                $price += 400;
                            } elseif (str_contains($gpu, '4080') || str_contains($gpu, '7800')) {
                                $price += 700;
                            } else {
                                $price += 1000;
                            }
                            // Additional RAM and storage
                            $price += (($ram - 16) / 16) * 150;
                            $price += (($storage - 1024) / 512) * 100;

                            $this->makeProduct(
                                $categories,
                                $brands,
                                $categoryName,
                                $base['brand'],
                                $modelNumber,
                                $title,
                                $description,
                                $price,
                                true
                            );
                        }
                    }
                }
            }
        }
    }

    /**
     * Seed tablet products with variations in memory, storage and colours. Tablets
     * are considered high‑value and use serialized tracking. Base models are
     * drawn from major tablet lines.
     */
    private function seedTablets(array $categories, array $brands): void
    {
        $baseModels = [
            [
                'brand'      => 'Apple',
                'name'       => 'iPad Pro 11"',
                'model'      => 'MNXR3LL/A',
                'basePrice'  => 799.00,
                'baseRam'    => 8,
                'baseStorage'=> 128,
                'colors'     => ['Space Gray', 'Silver'],
                'rams'       => [8, 16],
                'storages'   => [128, 256, 512, 1024, 2048],
            ],
            [
                'brand'      => 'Apple',
                'name'       => 'iPad Pro 13"',
                'model'      => 'MNXU3LL/A',
                'basePrice'  => 999.00,
                'baseRam'    => 8,
                'baseStorage'=> 128,
                'colors'     => ['Space Gray', 'Silver'],
                'rams'       => [8, 16],
                'storages'   => [128, 256, 512, 1024, 2048],
            ],
            [
                'brand'      => 'Samsung',
                'name'       => 'Galaxy Tab S9',
                'model'      => 'SM-X710',
                'basePrice'  => 749.99,
                'baseRam'    => 8,
                'baseStorage'=> 128,
                'colors'     => ['Graphite', 'Beige'],
                'rams'       => [8, 12],
                'storages'   => [128, 256, 512, 1024],
            ],
            [
                'brand'      => 'Samsung',
                'name'       => 'Galaxy Tab S9 Ultra',
                'model'      => 'SM-X910',
                'basePrice'  => 999.99,
                'baseRam'    => 12,
                'baseStorage'=> 256,
                'colors'     => ['Graphite'],
                'rams'       => [12, 16],
                'storages'   => [256, 512, 1024, 2048],
            ],
            [
                'brand'      => 'Microsoft',
                'name'       => 'Surface Pro 9',
                'model'      => 'QEZ-00001',
                'basePrice'  => 999.99,
                'baseRam'    => 8,
                'baseStorage'=> 128,
                'colors'     => ['Platinum', 'Graphite'],
                'rams'       => [8, 16, 32],
                'storages'   => [128, 256, 512, 1024, 2048],
            ],
            [
                'brand'      => 'Lenovo',
                'name'       => 'Yoga Tab 13',
                'model'      => 'ZA8A0000US',
                'basePrice'  => 699.99,
                'baseRam'    => 8,
                'baseStorage'=> 128,
                'colors'     => ['Shadow Black'],
                'rams'       => [8, 12],
                'storages'   => [128, 256, 512, 1024],
            ],
            [
                'brand'      => 'ASUS',
                'name'       => 'ROG Flow Z13',
                'model'      => 'GZ301',
                'basePrice'  => 1799.99,
                'baseRam'    => 16,
                'baseStorage'=> 512,
                'colors'     => ['Black'],
                'rams'       => [16, 32],
                'storages'   => [512, 1024, 2048],
            ],
        ];

        $categoryName = 'Tablets';
        foreach ($baseModels as $base) {
            foreach ($base['rams'] as $ram) {
                foreach ($base['storages'] as $storage) {
                    foreach ($base['colors'] as $color) {
                        $suffix      = strtoupper($ram . 'G' . $storage . 'G');
                        $modelNumber = $base['model'] . '-' . $suffix;
                        $title       = $base['name'] . ' ' . $ram . 'GB/' . $storage . 'GB - ' . $color;
                        $description = $base['brand'] . ' ' . $base['name'] . ' tablet with ' . $ram . 'GB RAM and ' . $storage . 'GB storage in ' . $color . ' colour.';
                        $price       = $base['basePrice'];
                        if ($ram > $base['baseRam']) {
                            $price += (($ram - $base['baseRam']) / 4) * 100;
                        }
                        if ($storage > $base['baseStorage']) {
                            $price += (($storage - $base['baseStorage']) / 128) * 50;
                        }
                        $this->makeProduct(
                            $categories,
                            $brands,
                            $categoryName,
                            $base['brand'],
                            $modelNumber,
                            $title,
                            $description,
                            $price,
                            true
                        );
                    }
                }
            }
        }
    }

    /**
     * Seed handheld gaming devices with variations in storage and colours. Handhelds
     * are serialized high‑value items. Base models include popular portable
     * gaming systems.
     */
    private function seedHandhelds(array $categories, array $brands): void
    {
        $baseModels = [
            [
                'brand'    => 'Valve',
                'name'     => 'Steam Deck',
                'model'    => 'VALV-003',
                'basePrice'=> 399.99,
                'storages' => [64, 256, 512],
                'colors'   => ['Black'],
            ],
            [
                'brand'    => 'ASUS',
                'name'     => 'ROG Ally',
                'model'    => 'RC71L',
                'basePrice'=> 699.99,
                'storages' => [512, 1024, 2048],
                'colors'   => ['White'],
            ],
            [
                'brand'    => 'Lenovo',
                'name'     => 'Legion Go',
                'model'    => '83ES0001US',
                'basePrice'=> 699.99,
                'storages' => [512, 1024, 2048],
                'colors'   => ['Gray'],
            ],
            [
                'brand'    => 'Nintendo',
                'name'     => 'Switch OLED',
                'model'    => 'HEGSKAAAA',
                'basePrice'=> 349.99,
                'storages' => [64, 256, 512],
                'colors'   => ['White', 'Red/Blue'],
            ],
        ];

        $categoryName = 'Handhelds';
        foreach ($baseModels as $base) {
            foreach ($base['storages'] as $storage) {
                foreach ($base['colors'] as $color) {
                    $suffix      = strtoupper($storage . 'G');
                    $modelNumber = $base['model'] . '-' . $suffix;
                    $title       = $base['name'] . ' ' . $storage . 'GB - ' . $color;
                    $description = $base['brand'] . ' ' . $base['name'] . ' with ' . $storage . 'GB storage in ' . $color . ' edition.';
                    $price       = $base['basePrice'];
                    if ($storage > 128) {
                        $price += (($storage - 128) / 128) * 50;
                    }
                    $this->makeProduct(
                        $categories,
                        $brands,
                        $categoryName,
                        $base['brand'],
                        $modelNumber,
                        $title,
                        $description,
                        $price,
                        true
                    );
                }
            }
        }
    }

    /**
     * Seed processor (CPU) products. Processors are serialized due to high
     * value. Each entry includes model numbers taken from retail packaging to
     * enhance realism. Boxed and tray versions are created.
     */
    private function seedProcessors(array $categories, array $brands): void
    {
        $processors = [
            ['brand' => 'Intel', 'name' => 'Core i5‑13400',   'model' => 'BX8071513400', 'price' => 229.99],
            ['brand' => 'Intel', 'name' => 'Core i5‑14600K',  'model' => 'BX8071514600K', 'price' => 289.99],
            ['brand' => 'Intel', 'name' => 'Core i7‑13700K',  'model' => 'BX8071513700K', 'price' => 399.99],
            ['brand' => 'Intel', 'name' => 'Core i7‑14700K',  'model' => 'BX8071514700K', 'price' => 449.99],
            ['brand' => 'Intel', 'name' => 'Core i9‑13900K',  'model' => 'BX8071513900K', 'price' => 589.99],
            ['brand' => 'Intel', 'name' => 'Core i9‑14900K',  'model' => 'BX8071514900K', 'price' => 639.99],
            ['brand' => 'AMD',   'name' => 'Ryzen 5 7600X',   'model' => '100-100000593WOF', 'price' => 229.99],
            ['brand' => 'AMD',   'name' => 'Ryzen 7 7800X3D', 'model' => '100-100000910WOF', 'price' => 449.99],
            ['brand' => 'AMD',   'name' => 'Ryzen 9 7950X',   'model' => '100-100000514WOF', 'price' => 579.99],
            ['brand' => 'AMD',   'name' => 'Ryzen 9 7950X3D','model' => '100-100000908WOF', 'price' => 699.99],
        ];
        $categoryName = 'Processors (CPU)';
        foreach ($processors as $cpu) {
            foreach (['Boxed', 'Tray'] as $pack) {
                $modelNumber = $cpu['model'] . ($pack === 'Boxed' ? '' : '-TRAY');
                $title       = $cpu['name'] . ' ' . $pack;
                $description = $cpu['brand'] . ' ' . $cpu['name'] . ' ' . $pack . ' version.';
                $price       = $cpu['price'];
                if ($pack !== 'Boxed') {
                    $price -= 20; // Tray versions are slightly cheaper
                }
                $this->makeProduct(
                    $categories,
                    $brands,
                    $categoryName,
                    $cpu['brand'],
                    $modelNumber,
                    $title,
                    $description,
                    $price,
                    true
                );
            }
        }
    }

    /**
     * Seed graphics card products with multiple brands and memory configurations.
     * GPUs are high‑value items and therefore serialized. Each GPU chip is
     * offered by several manufacturers with 8GB or higher memory.
     */
    private function seedGraphicsCards(array $categories, array $brands): void
    {
        $chips = [
            ['name' => 'GeForce RTX 4060',    'basePrice' => 299.99],
            ['name' => 'GeForce RTX 4060 Ti', 'basePrice' => 399.00],
            ['name' => 'GeForce RTX 4070',    'basePrice' => 599.99],
            ['name' => 'GeForce RTX 4070 Ti', 'basePrice' => 799.99],
            ['name' => 'GeForce RTX 4080',    'basePrice' => 1199.99],
            ['name' => 'GeForce RTX 4090',    'basePrice' => 1599.99],
            ['name' => 'Radeon RX 7600',      'basePrice' => 269.99],
            ['name' => 'Radeon RX 7700 XT',    'basePrice' => 449.99],
            ['name' => 'Radeon RX 7800 XT',    'basePrice' => 529.99],
            ['name' => 'Radeon RX 7900 XT',    'basePrice' => 799.99],
            ['name' => 'Radeon RX 7900 XTX',   'basePrice' => 999.99],
            ['name' => 'Radeon RX 7800',       'basePrice' => 479.99],
        ];
        $manufacturers = ['ASUS', 'MSI', 'Gigabyte', 'PNY'];
        $memSizes      = [8, 10, 12, 16, 20, 24];
        $categoryName  = 'Graphics Cards (GPU)';
        foreach ($chips as $chip) {
            foreach ($manufacturers as $brandName) {
                foreach ($memSizes as $vram) {
                    // Skip unrealistic combos: low VRAM on high‑end chips
                    if (str_contains($chip['name'], '4090') && $vram < 24) continue;
                    if (str_contains($chip['name'], '4080') && $vram < 16) continue;
                    if (str_contains($chip['name'], '4070 Ti') && $vram < 12) continue;
                    if (str_contains($chip['name'], '7900') && $vram < 20) continue;
                    $modelNumber = strtoupper(substr($brandName, 0, 3)) . '-' . str_replace(' ', '', $chip['name']) . '-' . $vram . 'G';
                    $title       = $brandName . ' ' . $chip['name'] . ' ' . $vram . 'GB';
                    $description = $brandName . ' graphics card based on ' . $chip['name'] . ' with ' . $vram . 'GB GDDR memory.';
                    // Price increases with VRAM and chip tier
                    $price       = $chip['basePrice'];
                    $price      += ($vram / 8 - 1) * 50;
                    // premium for brand
                    if ($brandName === 'ASUS' || $brandName === 'MSI') {
                        $price += 40;
                    }
                    if ($brandName === 'Gigabyte') {
                        $price += 20;
                    }
                    $this->makeProduct(
                        $categories,
                        $brands,
                        $categoryName,
                        $brandName,
                        $modelNumber,
                        $title,
                        $description,
                        $price,
                        true
                    );
                }
            }
        }
    }

    /**
     * Seed motherboard products. Each board is defined by chipset and form factor.
     */
    private function seedMotherboards(array $categories, array $brands): void
    {
        $boards = [
            ['brand' => 'ASUS', 'series' => 'ROG Strix X670E‑E',   'formFactors' => ['ATX', 'E‑ATX'], 'price' => 499.99],
            ['brand' => 'ASUS', 'series' => 'TUF Gaming B650‑Plus','formFactors' => ['ATX', 'Micro‑ATX'], 'price' => 199.99],
            ['brand' => 'MSI',  'series' => 'MPG Z790 Carbon WiFi','formFactors' => ['ATX'], 'price' => 399.99],
            ['brand' => 'MSI',  'series' => 'MAG B760 Tomahawk',  'formFactors' => ['ATX', 'Micro‑ATX'], 'price' => 189.99],
            ['brand' => 'Gigabyte','series' => 'AORUS X670E Master','formFactors' => ['ATX'], 'price' => 449.99],
            ['brand' => 'Gigabyte','series' => 'B650 AERO G',     'formFactors' => ['ATX', 'Micro‑ATX'], 'price' => 239.99],
            ['brand' => 'ASRock','series' => 'X670E Taichi',      'formFactors' => ['ATX'], 'price' => 499.99],
            ['brand' => 'ASRock','series' => 'B760 Pro RS',       'formFactors' => ['ATX', 'Micro‑ATX'], 'price' => 149.99],
            ['brand' => 'Biostar','series'=> 'X670E Valkyrie',     'formFactors' => ['ATX'], 'price' => 429.99],
            ['brand' => 'Biostar','series'=> 'B760M Silver',       'formFactors' => ['Micro‑ATX'], 'price' => 159.99],
        ];
        $categoryName = 'Motherboards';
        foreach ($boards as $board) {
            foreach ($board['formFactors'] as $form) {
                $modelNumber = strtoupper(substr($board['brand'],0,3)) . '-' . str_replace([' ', '‑'], '', $board['series']) . '-' . strtoupper($form);
                $title       = $board['brand'] . ' ' . $board['series'] . ' ' . $form;
                $description = $board['brand'] . ' motherboard ' . $board['series'] . ' in ' . $form . ' form factor.';
                $price       = $board['price'];
                if ($form !== 'ATX') {
                    $price -= 20; // micro boards slightly cheaper
                }
                $this->makeProduct(
                    $categories,
                    $brands,
                    $categoryName,
                    $board['brand'],
                    $modelNumber,
                    $title,
                    $description,
                    $price,
                    true
                );
            }
        }
    }

    /**
     * Seed memory (RAM) products with various capacities and speeds. Low‑value
     * modules are not serialized.
     */
    private function seedMemory(array $categories, array $brands): void
    {
        $modules = [
            ['brand' => 'Corsair', 'series' => 'Vengeance LPX',      'type' => 'DDR4', 'baseSpeed' => 3200, 'capacities' => [16, 32, 64, 128], 'price' => 79.99],
            ['brand' => 'Corsair', 'series' => 'Vengeance RGB',      'type' => 'DDR5', 'baseSpeed' => 5200, 'capacities' => [16, 32, 64, 128], 'price' => 119.99],
            ['brand' => 'G.Skill', 'series' => 'Trident Z Neo',      'type' => 'DDR4', 'baseSpeed' => 3600, 'capacities' => [16, 32, 64, 128], 'price' => 109.99],
            ['brand' => 'G.Skill', 'series' => 'Trident Z5 RGB',     'type' => 'DDR5', 'baseSpeed' => 6000, 'capacities' => [16, 32, 64, 128], 'price' => 159.99],
            ['brand' => 'Kingston', 'series'=> 'Fury Beast',          'type' => 'DDR4', 'baseSpeed' => 3200, 'capacities' => [16, 32, 64, 128], 'price' => 79.99],
            ['brand' => 'Kingston', 'series'=> 'Fury Renegade',       'type' => 'DDR5', 'baseSpeed' => 6000, 'capacities' => [16, 32, 64, 128], 'price' => 159.99],
            ['brand' => 'Crucial', 'series' => 'Ballistix',          'type' => 'DDR4', 'baseSpeed' => 3200, 'capacities' => [16, 32, 64, 128], 'price' => 89.99],
            ['brand' => 'Crucial', 'series' => 'Pro DDR5',           'type' => 'DDR5', 'baseSpeed' => 5600, 'capacities' => [16, 32, 64, 128], 'price' => 129.99],
        ];
        $categoryName = 'Memory (RAM)';
        foreach ($modules as $mod) {
            foreach ($mod['capacities'] as $capacity) {
                // speeds step up with capacity
                $speed     = $mod['baseSpeed'] + (($capacity / 16 - 1) * 200);
                $modelNumber = strtoupper(substr($mod['brand'],0,3)) . '-' . str_replace([' ', '5', '4'], '', $mod['series']) . '-' . $capacity . 'G-' . $speed;
                $title       = $mod['brand'] . ' ' . $mod['series'] . ' ' . $capacity . 'GB ' . $mod['type'] . ' ' . $speed . 'MHz';
                $description = $mod['brand'] . ' ' . $mod['series'] . ' memory kit with ' . $capacity . 'GB capacity and ' . $speed . 'MHz speed.';
                $price       = $mod['price'];
                $price      += (($capacity / 16 - 1) * 30);
                $price      += (($speed - $mod['baseSpeed']) / 200) * 10;
                $this->makeProduct(
                    $categories,
                    $brands,
                    $categoryName,
                    $mod['brand'],
                    $modelNumber,
                    $title,
                    $description,
                    $price,
                    false
                );
            }
        }
    }

    /**
     * Seed power supplies with different wattages. Low‑value items are not
     * serialized.
     */
    private function seedPowerSupplies(array $categories, array $brands): void
    {
        $units = [
            ['brand' => 'Corsair', 'series' => 'RMx',      'wattages' => [650, 750, 850], 'price' => 129.99],
            ['brand' => 'Corsair', 'series' => 'HX',       'wattages' => [750, 850, 1000], 'price' => 179.99],
            ['brand' => 'EVGA',    'series' => 'SuperNOVA','wattages' => [650, 750, 850], 'price' => 119.99],
            ['brand' => 'Seasonic','series' => 'Focus',    'wattages' => [650, 750, 850], 'price' => 109.99],
            ['brand' => 'ASUS',    'series' => 'ROG Thor', 'wattages' => [850, 1000, 1200], 'price' => 249.99],
            ['brand' => 'Cooler Master','series'=>'MWE',  'wattages' => [650, 750, 850], 'price' => 89.99],
            ['brand' => 'NZXT',    'series' => 'C Series', 'wattages' => [650, 750, 850], 'price' => 119.99],
            ['brand' => 'Thermaltake','series'=>'Toughpower','wattages' => [750, 850, 1000], 'price' => 129.99],
        ];
        $categoryName = 'Power Supplies (PSU)';
        foreach ($units as $unit) {
            foreach ($unit['wattages'] as $w) {
                $modelNumber = strtoupper(substr($unit['brand'],0,3)) . '-' . str_replace(' ', '', $unit['series']) . '-' . $w . 'W';
                $title       = $unit['brand'] . ' ' . $unit['series'] . ' ' . $w . 'W PSU';
                $description = $unit['brand'] . ' ' . $w . 'W power supply from the ' . $unit['series'] . ' series.';
                $price       = $unit['price'] + (($w - min($unit['wattages'])) / 100) * 20;
                $this->makeProduct(
                    $categories,
                    $brands,
                    $categoryName,
                    $unit['brand'],
                    $modelNumber,
                    $title,
                    $description,
                    $price,
                    false
                );
            }
        }
    }

    /**
     * Seed PC cases with different sizes and colours.
     */
    private function seedCases(array $categories, array $brands): void
    {
        $cases = [
            ['brand' => 'NZXT',   'model' => 'H510',     'price' => 79.99,  'colors' => ['Black', 'White', 'Red']],
            ['brand' => 'NZXT',   'model' => 'H7 Flow',  'price' => 129.99, 'colors' => ['Black', 'White']],
            ['brand' => 'Lian Li','model' => 'Lancool II','price' => 109.99,'colors' => ['Black', 'White']],
            ['brand' => 'Fractal Design','model' => 'Meshify C','price' => 99.99,'colors' => ['Black', 'White']],
            ['brand' => 'Corsair','model' => 'iCUE 4000D','price' => 109.99,'colors' => ['Black', 'White']],
            ['brand' => 'Cooler Master','model' => 'HAF 500','price' => 139.99,'colors' => ['Black', 'White']],
            ['brand' => 'Phanteks','model'=> 'Eclipse G360A','price' => 99.99,'colors' => ['Black', 'White']],
            ['brand' => 'Thermaltake','model'=>'Core P3','price'=>149.99,'colors'=>['Black','White']],
            ['brand' => 'Be quiet!','model'=> 'Pure Base 500DX','price'=>99.99,'colors'=>['Black','White','Orange']],
            ['brand' => 'Antec','model' => 'DF700 Flux','price'=>89.99,'colors'=>['Black','White']],
        ];
        $categoryName = 'Cases & Chassis';
        foreach ($cases as $case) {
            foreach ($case['colors'] as $color) {
                $modelNumber = strtoupper(substr($case['brand'],0,3)) . '-' . str_replace([' ', '!', '-'], '', $case['model']) . '-' . strtoupper(substr($color,0,1));
                $title       = $case['brand'] . ' ' . $case['model'] . ' - ' . $color;
                $description = $case['brand'] . ' ' . $case['model'] . ' mid‑tower case in ' . $color . ' colour.';
                $price       = $case['price'];
                if ($color !== 'Black') {
                    $price += 5; // colored variants slight premium
                }
                $this->makeProduct(
                    $categories,
                    $brands,
                    $categoryName,
                    $case['brand'],
                    $modelNumber,
                    $title,
                    $description,
                    $price,
                    false
                );
            }
        }
    }

    /**
     * Seed internal SSD products with multiple capacities.
     */
    private function seedInternalSSDs(array $categories, array $brands): void
    {
        $ssds = [
            ['brand' => 'Samsung', 'series' => '980 PRO',   'basePrice' => 89.99,  'capacities' => [500, 1000, 2000]],
            ['brand' => 'Samsung', 'series' => '990 PRO',   'basePrice' => 129.99, 'capacities' => [1000, 2000]],
            ['brand' => 'Western Digital','series' => 'Black SN850', 'basePrice' => 99.99, 'capacities' => [500, 1000, 2000]],
            ['brand' => 'Corsair', 'series' => 'MP600 PRO XT','basePrice' => 109.99,'capacities' => [1000, 2000]],
            ['brand' => 'Sabrent', 'series' => 'Rocket 4 Plus','basePrice' => 119.99,'capacities' => [500, 1000, 2000]],
            ['brand' => 'Crucial', 'series' => 'P5 Plus',  'basePrice' => 89.99,  'capacities' => [500, 1000, 2000]],
            ['brand' => 'Kingston','series' => 'KC3000',    'basePrice' => 99.99,  'capacities' => [500, 1000, 2000]],
            ['brand' => 'SK hynix','series' => 'Platinum P41','basePrice' => 109.99,'capacities' => [500, 1000, 2000]],
        ];
        $categoryName = 'Internal SSDs';
        foreach ($ssds as $ssd) {
            foreach ($ssd['capacities'] as $cap) {
                $modelNumber = strtoupper(substr($ssd['brand'],0,3)) . '-' . str_replace([' ', ' '], '', $ssd['series']) . '-' . $cap . 'GB';
                $title       = $ssd['brand'] . ' ' . $ssd['series'] . ' ' . ($cap >= 1000 ? ($cap/1000 . 'TB') : $cap . 'GB') . ' NVMe SSD';
                $description = $ssd['brand'] . ' ' . $ssd['series'] . ' PCIe NVMe SSD with ' . ($cap >= 1000 ? ($cap/1000 . 'TB') : $cap . 'GB') . ' capacity.';
                $price       = $ssd['basePrice'] + (($cap / 500) - 1) * 40;
                $this->makeProduct(
                    $categories,
                    $brands,
                    $categoryName,
                    $ssd['brand'],
                    $modelNumber,
                    $title,
                    $description,
                    $price,
                    false
                );
            }
        }
    }

    /**
     * Seed hard drive products (HDDs) with multiple capacities.
     */
    private function seedHardDrives(array $categories, array $brands): void
    {
        $hdds = [
            ['brand' => 'Seagate', 'series' => 'BarraCuda',  'basePrice' => 49.99, 'capacities' => [2000, 4000, 6000]],
            ['brand' => 'Western Digital', 'series' => 'Blue',   'basePrice' => 54.99, 'capacities' => [2000, 4000, 6000]],
            ['brand' => 'Toshiba', 'series' => 'X300',       'basePrice' => 59.99, 'capacities' => [4000, 6000, 8000]],
            ['brand' => 'Seagate', 'series' => 'IronWolf',    'basePrice' => 84.99, 'capacities' => [4000, 6000, 8000]],
            ['brand' => 'Western Digital', 'series' => 'Red Plus','basePrice' => 79.99, 'capacities' => [4000, 6000, 8000]],
        ];
        $categoryName = 'Hard Drives (HDD)';
        foreach ($hdds as $drive) {
            foreach ($drive['capacities'] as $cap) {
                $modelNumber = strtoupper(substr($drive['brand'],0,3)) . '-' . str_replace(' ', '', $drive['series']) . '-' . $cap . 'GB';
                $title       = $drive['brand'] . ' ' . $drive['series'] . ' ' . ($cap/1000) . 'TB HDD';
                $description = $drive['brand'] . ' ' . $drive['series'] . ' hard drive with ' . ($cap/1000) . 'TB capacity.';
                $price       = $drive['basePrice'] + (($cap / 1000) - 2) * 20;
                $this->makeProduct(
                    $categories,
                    $brands,
                    $categoryName,
                    $drive['brand'],
                    $modelNumber,
                    $title,
                    $description,
                    $price,
                    false
                );
            }
        }
    }

    /**
     * Seed external storage products including portable SSDs and HDDs.
     */
    private function seedExternalStorage(array $categories, array $brands): void
    {
        $storage = [
            ['brand' => 'Western Digital', 'series' => 'My Passport',     'type' => 'HDD', 'basePrice' => 79.99, 'capacities' => [1000, 2000, 4000]],
            ['brand' => 'Seagate',         'series' => 'Backup Plus',    'type' => 'HDD', 'basePrice' => 69.99, 'capacities' => [1000, 2000, 4000]],
            ['brand' => 'Samsung',         'series' => 'T7',             'type' => 'SSD', 'basePrice' => 89.99, 'capacities' => [500, 1000, 2000]],
            ['brand' => 'SanDisk',         'series' => 'Extreme Portable','type' => 'SSD', 'basePrice' => 99.99, 'capacities' => [500, 1000, 2000]],
            ['brand' => 'LaCie',           'series' => 'Rugged',         'type' => 'HDD', 'basePrice' => 109.99, 'capacities' => [1000, 2000, 5000]],
            ['brand' => 'Toshiba',         'series' => 'Canvio Advance',  'type' => 'HDD', 'basePrice' => 69.99, 'capacities' => [1000, 2000, 4000]],
            ['brand' => 'G‑Technology',    'series' => 'G‑Drive',        'type' => 'HDD', 'basePrice' => 119.99, 'capacities' => [2000, 4000, 8000]],
            ['brand' => 'ADATA',           'series' => 'SD600',          'type' => 'SSD', 'basePrice' => 79.99, 'capacities' => [256, 512, 1000]],
        ];
        $categoryName = 'External Storage';
        foreach ($storage as $ext) {
            foreach ($ext['capacities'] as $cap) {
                $capLabel   = $cap >= 1000 ? ($cap/1000 . 'TB') : $cap . 'GB';
                $modelNumber= strtoupper(substr($ext['brand'],0,3)) . '-' . str_replace([' ', '‑'], '', $ext['series']) . '-' . $capLabel;
                $title      = $ext['brand'] . ' ' . $ext['series'] . ' ' . $capLabel . ' ' . $ext['type'];
                $description= $ext['brand'] . ' ' . $ext['series'] . ' portable ' . $ext['type'] . ' drive with ' . $capLabel . ' capacity.';
                $price      = $ext['basePrice'];
                $price     += (($cap / ($ext['type'] === 'SSD' ? 500 : 1000)) - 1) * 30;
                $this->makeProduct(
                    $categories,
                    $brands,
                    $categoryName,
                    $ext['brand'],
                    $modelNumber,
                    $title,
                    $description,
                    $price,
                    false
                );
            }
        }
    }

    /**
     * Seed memory card products (SD and microSD). Low‑value, not serialized.
     */
    private function seedMemoryCards(array $categories, array $brands): void
    {
        $cards = [
            ['brand' => 'SanDisk', 'series' => 'Ultra microSD', 'basePrice' => 9.99, 'capacities' => [64, 128, 256, 512]],
            ['brand' => 'SanDisk', 'series' => 'Extreme microSD', 'basePrice' => 14.99, 'capacities' => [64, 128, 256, 512]],
            ['brand' => 'Samsung', 'series' => 'EVO Plus', 'basePrice' => 12.99, 'capacities' => [64, 128, 256, 512]],
            ['brand' => 'Lexar',   'series' => 'Professional', 'basePrice' => 19.99, 'capacities' => [64, 128, 256, 512]],
            ['brand' => 'Kingston','series' => 'Canvas React', 'basePrice' => 14.99, 'capacities' => [64, 128, 256, 512]],
            ['brand' => 'PNY',     'series' => 'Elite-X', 'basePrice' => 11.99, 'capacities' => [64, 128, 256, 512]],
        ];
        $categoryName = 'Memory Cards';
        foreach ($cards as $card) {
            foreach ($card['capacities'] as $cap) {
                $modelNumber = strtoupper(substr($card['brand'],0,3)) . '-' . str_replace([' ', 'microSD'], '', $card['series']) . '-' . $cap . 'G';
                $title       = $card['brand'] . ' ' . $card['series'] . ' ' . $cap . 'GB';
                $description = $card['brand'] . ' ' . $card['series'] . ' memory card with ' . $cap . 'GB capacity.';
                $price       = $card['basePrice'] + (($cap / 64) - 1) * 5;
                $this->makeProduct(
                    $categories,
                    $brands,
                    $categoryName,
                    $card['brand'],
                    $modelNumber,
                    $title,
                    $description,
                    $price,
                    false
                );
            }
        }
    }

    /**
     * Seed CPU cooling solutions: air and liquid coolers. Low‑value items.
     */
    private function seedCpuCooling(array $categories, array $brands): void
    {
        $coolers = [
            ['brand' => 'Noctua',     'series' => 'NH‑D15',         'types' => ['Air'],    'price' => 99.99],
            ['brand' => 'Corsair',    'series' => 'iCUE H100i Elite','types' => ['240mm AIO','360mm AIO'], 'price' => 139.99],
            ['brand' => 'Cooler Master','series' => 'Hyper 212',    'types' => ['Air'],    'price' => 39.99],
            ['brand' => 'NZXT',       'series' => 'Kraken X63',     'types' => ['280mm AIO'], 'price' => 149.99],
            ['brand' => 'be quiet!',  'series' => 'Dark Rock Pro 4','types' => ['Air'],    'price' => 89.99],
            ['brand' => 'Arctic',     'series' => 'Liquid Freezer II','types' => ['240mm AIO','360mm AIO'], 'price' => 109.99],
        ];
        $categoryName = 'CPU Cooling';
        foreach ($coolers as $cooler) {
            foreach ($cooler['types'] as $type) {
                $modelNumber = strtoupper(substr($cooler['brand'],0,3)) . '-' . str_replace([' ', '‑'], '', $cooler['series']) . '-' . str_replace(' ', '', $type);
                $title       = $cooler['brand'] . ' ' . $cooler['series'] . ' ' . $type;
                $description = $cooler['brand'] . ' ' . $type . ' cooler ' . $cooler['series'] . '.';
                $price       = $cooler['price'];
                if (str_contains($type, '360')) {
                    $price += 20;
                }
                $this->makeProduct(
                    $categories,
                    $brands,
                    $categoryName,
                    $cooler['brand'],
                    $modelNumber,
                    $title,
                    $description,
                    $price,
                    false
                );
            }
        }
    }

    /**
     * Seed case fans with various pack sizes. Low value.
     */
    private function seedCaseFans(array $categories, array $brands): void
    {
        $fans = [
            ['brand' => 'Corsair', 'series' => 'LL120 RGB',     'packSizes' => [1, 2, 3], 'price' => 39.99],
            ['brand' => 'Noctua',  'series' => 'NF‑A12x25',     'packSizes' => [1, 2, 3], 'price' => 29.99],
            ['brand' => 'Cooler Master','series' => 'SickleFlow', 'packSizes' => [1, 2, 3], 'price' => 24.99],
            ['brand' => 'NZXT',    'series' => 'Aer RGB 2',     'packSizes' => [1, 2, 3], 'price' => 34.99],
            ['brand' => 'Arctic',  'series' => 'P12',           'packSizes' => [1, 2, 3], 'price' => 19.99],
            ['brand' => 'Thermaltake','series'=>'Riing Quad',  'packSizes' => [1, 2, 3], 'price' => 44.99],
        ];
        $categoryName = 'Case Fans';
        foreach ($fans as $fan) {
            foreach ($fan['packSizes'] as $pack) {
                $modelNumber = strtoupper(substr($fan['brand'],0,3)) . '-' . str_replace([' ', '‑'], '', $fan['series']) . '-' . $pack . 'PK';
                $title       = $fan['brand'] . ' ' . $fan['series'] . ' (' . $pack . '-Pack)';
                $description = $fan['brand'] . ' ' . $fan['series'] . ' fan pack containing ' . $pack . ' fan(s).';
                $price       = $fan['price'] * $pack;
                $this->makeProduct(
                    $categories,
                    $brands,
                    $categoryName,
                    $fan['brand'],
                    $modelNumber,
                    $title,
                    $description,
                    $price,
                    false
                );
            }
        }
    }

    /**
     * Seed thermal solutions like paste. Low value.
     */
    private function seedThermalSolutions(array $categories, array $brands): void
    {
        $solutions = [
            ['brand' => 'Arctic',       'name' => 'MX‑6',       'basePrice' => 7.99,  'sizes' => [2, 4, 8]],
            ['brand' => 'Thermal Grizzly','name' => 'Kryonaut','basePrice' => 9.99,  'sizes' => [1, 5, 10]],
            ['brand' => 'Noctua',       'name' => 'NT‑H1',     'basePrice' => 8.99,  'sizes' => [3.5, 10, 20]],
            ['brand' => 'Corsair',      'name' => 'XTM50',     'basePrice' => 6.99,  'sizes' => [5, 10, 15]],
        ];
        $categoryName = 'Thermal Solutions';
        foreach ($solutions as $sol) {
            foreach ($sol['sizes'] as $size) {
                $modelNumber = strtoupper(substr($sol['brand'],0,3)) . '-' . str_replace([' ', '‑'], '', $sol['name']) . '-' . $size . 'G';
                $title       = $sol['brand'] . ' ' . $sol['name'] . ' ' . $size . 'g';
                $description = $sol['brand'] . ' ' . $sol['name'] . ' thermal compound, ' . $size . 'g syringe.';
                $price       = $sol['basePrice'] + ($size / 2) * 1.5;
                $this->makeProduct(
                    $categories,
                    $brands,
                    $categoryName,
                    $sol['brand'],
                    $modelNumber,
                    $title,
                    $description,
                    $price,
                    false
                );
            }
        }
    }

    /**
     * Seed tools and equipment for PC building.
     */
    private function seedTools(array $categories, array $brands): void
    {
        $tools = [
            ['brand' => 'iFixit',    'name' => 'Pro Tech Toolkit',        'model' => 'IF145‑307‑4', 'price' => 69.99],
            ['brand' => 'Antec',     'name' => 'Toolkit',                 'model' => 'ANT‑TOOLKIT', 'price' => 29.99],
            ['brand' => 'Corsair',   'name' => 'Premium Screwdriver Set', 'model' => 'CT‑9010001', 'price' => 39.99],
            ['brand' => 'Klein Tools','name'=> '11-in-1 Screwdriver',     'model' => '32500', 'price' => 14.99],
        ];
        $categoryName = 'Tools & Equipment';
        foreach ($tools as $tool) {
            $modelNumber = $tool['model'];
            $title       = $tool['name'];
            $description = $tool['brand'] . ' ' . $tool['name'] . ' toolkit.';
            $price       = $tool['price'];
            $this->makeProduct(
                $categories,
                $brands,
                $categoryName,
                $tool['brand'],
                $modelNumber,
                $title,
                $description,
                $price,
                false
            );
        }
    }

    /**
     * Seed monitor products in various sizes and resolutions.
     */
    private function seedMonitors(array $categories, array $brands): void
    {
        $monitors = [
            ['brand' => 'Dell',     'series' => 'UltraSharp U2723QE', 'sizes' => [27], 'res' => '4K',   'basePrice' => 449.99],
            ['brand' => 'LG',       'series' => 'UltraGear 27GP850',  'sizes' => [27], 'res' => 'QHD',  'basePrice' => 399.99],
            ['brand' => 'ASUS',     'series' => 'ROG Swift PG279QM', 'sizes' => [27], 'res' => 'QHD',  'basePrice' => 699.99],
            ['brand' => 'Samsung',  'series' => 'Odyssey G9',        'sizes' => [49], 'res' => 'DQHD', 'basePrice' => 1399.99],
            ['brand' => 'Acer',     'series' => 'Predator X27',       'sizes' => [27], 'res' => '4K',  'basePrice' => 1799.99],
            ['brand' => 'BenQ',     'series' => 'PD3220U',            'sizes' => [32], 'res' => '4K',   'basePrice' => 999.99],
            ['brand' => 'Gigabyte','series'=> 'M32U',                'sizes' => [32], 'res' => '4K',   'basePrice' => 699.99],
            ['brand' => 'HP',       'series' => 'Omen 27q',           'sizes' => [27], 'res' => 'QHD',  'basePrice' => 349.99],
            ['brand' => 'ViewSonic','series'=> 'XG320U',              'sizes' => [32], 'res' => '4K',   'basePrice' => 799.99],
            ['brand' => 'MSI',      'series' => 'Optix MPG321UR',     'sizes' => [32], 'res' => '4K',   'basePrice' => 999.99],
        ];
        $categoryName = 'Monitors';
        foreach ($monitors as $mon) {
            foreach ($mon['sizes'] as $size) {
                // We assume each size/res pair forms a distinct model
                $modelNumber = strtoupper(substr($mon['brand'],0,3)) . '-' . str_replace([' ', ' '], '', $mon['series']) . '-' . $size;
                $title       = $mon['brand'] . ' ' . $mon['series'] . ' ' . $size . '" ' . $mon['res'];
                $description = $mon['brand'] . ' ' . $size . '‑inch ' . $mon['res'] . ' monitor (' . $mon['series'] . ').';
                $price       = $mon['basePrice'];
                if ($size > 30) {
                    $price += 200;
                }
                if ($mon['res'] === '4K') {
                    $price += 100;
                }
                $this->makeProduct(
                    $categories,
                    $brands,
                    $categoryName,
                    $mon['brand'],
                    $modelNumber,
                    $title,
                    $description,
                    $price,
                    false
                );
            }
        }
    }

    /**
     * Seed keyboard products with various switch types or layouts.
     */
    private function seedKeyboards(array $categories, array $brands): void
    {
        $keyboards = [
            ['brand' => 'Logitech','series' => 'MX Mechanical','variants' => ['Tactile', 'Linear', 'Clicky'], 'price' => 169.99],
            ['brand' => 'Razer',   'series' => 'Huntsman V2', 'variants' => ['Linear Optical','Clicky Optical','Analog'], 'price' => 189.99],
            ['brand' => 'Corsair', 'series' => 'K95 RGB',     'variants' => ['Cherry MX Brown','Cherry MX Speed','Cherry MX Blue'], 'price' => 199.99],
            ['brand' => 'SteelSeries','series'=> 'Apex Pro','variants' => ['Adjustable OmniPoint'], 'price' => 199.99],
            ['brand' => 'Keychron','series' => 'K2',          'variants' => ['Red Switch','Blue Switch','Brown Switch'], 'price' => 84.99],
            ['brand' => 'HyperX',  'series' => 'Alloy Origins','variants' => ['HyperX Aqua','HyperX Red','HyperX Blue'], 'price' => 89.99],
            ['brand' => 'Ducky',   'series' => 'One 3',       'variants' => ['Cherry MX Red','Cherry MX Silent','Cherry MX Brown'], 'price' => 109.99],
            ['brand' => 'Microsoft','series'=> 'Sculpt Ergonomic','variants' => ['Standard'], 'price' => 129.99],
        ];
        $categoryName = 'Keyboards';
        foreach ($keyboards as $kb) {
            foreach ($kb['variants'] as $variant) {
                $modelNumber = strtoupper(substr($kb['brand'],0,3)) . '-' . str_replace([' ', ' '], '', $kb['series']) . '-' . strtoupper(substr(str_replace(' ', '', $variant),0,3));
                $title       = $kb['brand'] . ' ' . $kb['series'] . ' (' . $variant . ')';
                $description = $kb['brand'] . ' ' . $kb['series'] . ' keyboard with ' . $variant . ' switches.';
                $price       = $kb['price'];
                if (str_contains($variant, 'Analog') || str_contains($variant, 'Adjustable')) {
                    $price += 50;
                }
                $this->makeProduct(
                    $categories,
                    $brands,
                    $categoryName,
                    $kb['brand'],
                    $modelNumber,
                    $title,
                    $description,
                    $price,
                    false
                );
            }
        }
    }

    /**
     * Seed mice with options for wired and wireless.
     */
    private function seedMice(array $categories, array $brands): void
    {
        $mice = [
            ['brand' => 'Logitech','series' => 'MX Master 3S',   'types' => ['Wireless','Performance'], 'price' => 99.99],
            ['brand' => 'Razer',   'series' => 'DeathAdder V2',  'types' => ['Wired','Wireless'], 'price' => 69.99],
            ['brand' => 'Corsair', 'series' => 'Sabre RGB Pro',  'types' => ['Wired','Wireless'], 'price' => 59.99],
            ['brand' => 'SteelSeries','series'=> 'Rival 600',    'types' => ['Wired','Wireless'], 'price' => 79.99],
            ['brand' => 'Microsoft','series'=> 'Arc',           'types' => ['Wireless'], 'price' => 79.99],
            ['brand' => 'Logitech','series' => 'G Pro X Superlight','types' => ['Wireless'], 'price' => 149.99],
            ['brand' => 'Razer',   'series' => 'Basilisk V3',    'types' => ['Wired','Wireless'], 'price' => 79.99],
            ['brand' => 'HyperX',  'series' => 'Pulsefire Haste','types' => ['Wired','Wireless'], 'price' => 49.99],
        ];
        $categoryName = 'Mice';
        foreach ($mice as $m) {
            foreach ($m['types'] as $type) {
                $modelNumber = strtoupper(substr($m['brand'],0,3)) . '-' . str_replace([' ', ' '], '', $m['series']) . '-' . strtoupper(substr($type,0,3));
                $title       = $m['brand'] . ' ' . $m['series'] . ' ' . $type;
                $description = $m['brand'] . ' ' . $m['series'] . ' mouse (' . $type . ').';
                $price       = $m['price'];
                if ($type === 'Wireless') {
                    $price += 20;
                }
                $this->makeProduct(
                    $categories,
                    $brands,
                    $categoryName,
                    $m['brand'],
                    $modelNumber,
                    $title,
                    $description,
                    $price,
                    false
                );
            }
        }
    }

    /**
     * Seed audio products such as headphones and headsets.
     */
    private function seedAudio(array $categories, array $brands): void
    {
        $audio = [
            ['brand' => 'Bose',      'model' => 'QuietComfort 35 II', 'types' => ['Black','Silver'], 'price' => 299.99],
            ['brand' => 'Sony',      'model' => 'WH‑1000XM5',        'types' => ['Black','Silver'], 'price' => 399.99],
            ['brand' => 'Sennheiser','model' => 'HD 560S',           'types' => ['Black'], 'price' => 199.99],
            ['brand' => 'HyperX',    'model' => 'Cloud II',          'types' => ['Black','Pink'], 'price' => 99.99],
            ['brand' => 'Logitech',  'model' => 'G Pro X',           'types' => ['Black','White'], 'price' => 129.99],
            ['brand' => 'JBL',       'model' => 'Flip 6',            'types' => ['Black','Blue','Red'], 'price' => 129.99],
            ['brand' => 'Apple',     'model' => 'AirPods Pro',       'types' => ['White'], 'price' => 249.99],
            ['brand' => 'Beats',     'model' => 'Studio3',           'types' => ['Black','Red','White'], 'price' => 349.99],
        ];
        $categoryName = 'Audio';
        foreach ($audio as $a) {
            foreach ($a['types'] as $type) {
                $modelNumber = strtoupper(substr($a['brand'],0,3)) . '-' . str_replace([' ', ' '], '', $a['model']) . '-' . strtoupper(substr($type,0,1));
                $title       = $a['brand'] . ' ' . $a['model'] . ' (' . $type . ')';
                $description = $a['brand'] . ' ' . $a['model'] . ' audio device in ' . $type . ' colour.';
                $price       = $a['price'];
                if (strlen($type) > 5) {
                    $price += 10;
                }
                $this->makeProduct(
                    $categories,
                    $brands,
                    $categoryName,
                    $a['brand'],
                    $modelNumber,
                    $title,
                    $description,
                    $price,
                    false
                );
            }
        }
    }

    /**
     * Seed streaming gear such as microphones, webcams and lights.
     */
    private function seedStreamingGear(array $categories, array $brands): void
    {
        $gear = [
            ['brand' => 'Elgato', 'model' => 'Stream Deck MK.2',   'variants' => ['Black','White'], 'price' => 149.99],
            ['brand' => 'Elgato', 'model' => 'Wave:3',             'variants' => ['Black','White'], 'price' => 149.99],
            ['brand' => 'Razer',  'model' => 'Kiyo Pro',           'variants' => ['Black'], 'price' => 199.99],
            ['brand' => 'Logitech','model'=> 'Litra Glow',         'variants' => ['White'], 'price' => 59.99],
            ['brand' => 'HyperX','model'=> 'QuadCast S',          'variants' => ['Red/Black'], 'price' => 159.99],
            ['brand' => 'AverMedia','model'=>'Live Streamer CAM 513','variants'=>['Black'], 'price' => 219.99],
        ];
        $categoryName = 'Streaming Gear';
        foreach ($gear as $g) {
            foreach ($g['variants'] as $variant) {
                $modelNumber = strtoupper(substr($g['brand'],0,3)) . '-' . str_replace([' ', ':'], '', $g['model']) . '-' . strtoupper(substr(str_replace('/', '', $variant),0,3));
                $title       = $g['brand'] . ' ' . $g['model'] . ' (' . $variant . ')';
                $description = $g['brand'] . ' ' . $g['model'] . ' streaming gear variant ' . $variant . '.';
                $price       = $g['price'];
                if (str_contains($variant, '/')) {
                    $price += 10;
                }
                $this->makeProduct(
                    $categories,
                    $brands,
                    $categoryName,
                    $g['brand'],
                    $modelNumber,
                    $title,
                    $description,
                    $price,
                    false
                );
            }
        }
    }

    /**
     * Seed wireless networking equipment such as routers and mesh systems.
     */
    private function seedWirelessNetworking(array $categories, array $brands): void
    {
        $routers = [
            ['brand' => 'TP-Link','model' => 'Archer AX6000','variants' => ['Single','2-Pack'], 'price' => 299.99],
            ['brand' => 'Netgear','model' => 'Nighthawk AX5400','variants' => ['Single','2-Pack'], 'price' => 249.99],
            ['brand' => 'ASUS',   'model' => 'ROG Rapture GT-AXE11000','variants' => ['Single'], 'price' => 479.99],
            ['brand' => 'Linksys','model' => 'Velop WiFi 6 Mesh','variants' => ['2-Pack','3-Pack'], 'price' => 299.99],
            ['brand' => 'Ubiquiti','model'=> 'AmpliFi Alien','variants' => ['Single','2-Pack'], 'price' => 379.99],
            ['brand' => 'Google', 'model' => 'Nest WiFi Pro','variants' => ['2-Pack','3-Pack'], 'price' => 199.99],
            ['brand' => 'Eero',   'model' => 'Pro 6E','variants' => ['2-Pack','3-Pack'], 'price' => 299.99],
            ['brand' => 'D-Link', 'model' => 'EXO AX5400','variants' => ['Single','2-Pack'], 'price' => 199.99],
        ];
        $categoryName = 'Wireless Networking';
        foreach ($routers as $r) {
            foreach ($r['variants'] as $variant) {
                $modelNumber = strtoupper(substr($r['brand'],0,3)) . '-' . str_replace([' ', '-', '/'], '', $r['model']) . '-' . strtoupper(substr($variant,0,1));
                $title       = $r['brand'] . ' ' . $r['model'] . ' ' . $variant;
                $description = $r['brand'] . ' ' . $r['model'] . ' ' . $variant . ' wireless system.';
                $price       = $r['price'];
                if (str_contains($variant, '2')) {
                    $price += 80;
                } elseif (str_contains($variant, '3')) {
                    $price += 150;
                }
                $this->makeProduct(
                    $categories,
                    $brands,
                    $categoryName,
                    $r['brand'],
                    $modelNumber,
                    $title,
                    $description,
                    $price,
                    false
                );
            }
        }
    }

    /**
     * Seed wired networking equipment like switches.
     */
    private function seedWiredNetworking(array $categories, array $brands): void
    {
        $switches = [
            ['brand' => 'Netgear','model' => 'GS108','ports' => [8, 16], 'price' => 39.99],
            ['brand' => 'TP-Link','model' => 'TL-SG105','ports' => [5, 8, 16], 'price' => 29.99],
            ['brand' => 'Cisco',  'model' => 'SG110', 'ports' => [8, 16, 24], 'price' => 59.99],
            ['brand' => 'Ubiquiti','model' => 'UniFi Switch Lite','ports' => [8, 16], 'price' => 99.99],
        ];
        $categoryName = 'Wired Networking';
        foreach ($switches as $sw) {
            foreach ($sw['ports'] as $p) {
                $modelNumber = strtoupper(substr($sw['brand'],0,3)) . '-' . str_replace([' ', '-', '/'], '', $sw['model']) . '-' . $p . 'PT';
                $title       = $sw['brand'] . ' ' . $sw['model'] . ' ' . $p . '-Port';
                $description = $sw['brand'] . ' ' . $p . '-port unmanaged switch (' . $sw['model'] . ').';
                $price       = $sw['price'] + (($p / min($sw['ports'])) - 1) * 20;
                $this->makeProduct(
                    $categories,
                    $brands,
                    $categoryName,
                    $sw['brand'],
                    $modelNumber,
                    $title,
                    $description,
                    $price,
                    false
                );
            }
        }
    }

    /**
     * Seed cables and adapters with various lengths and types. Low value.
     */
    private function seedCablesAdapters(array $categories, array $brands): void
    {
        $items = [
            ['brand' => 'Anker',    'name' => 'USB-C to HDMI Adapter',     'lengths' => ['0.5m','1m','2m'], 'price' => 19.99],
            ['brand' => 'Belkin',   'name' => 'Lightning to 3.5mm',       'lengths' => ['0.1m','1m','2m'], 'price' => 14.99],
            ['brand' => 'Cable Matters','name'=> 'Cat6 Ethernet Cable',    'lengths' => ['1m','3m','5m','10m'], 'price' => 9.99],
            ['brand' => 'Amazon Basics','name'=> 'HDMI Cable',            'lengths' => ['1m','2m','3m','5m'], 'price' => 7.99],
            ['brand' => 'UGREEN',   'name' => 'USB-C Hub 7-in-1',         'lengths' => ['Short'], 'price' => 34.99],
            ['brand' => 'Monoprice','name'=> 'DisplayPort Cable',         'lengths' => ['1m','2m','3m'], 'price' => 11.99],
            ['brand' => 'Anker',    'name' => 'Powerline III USB-C',      'lengths' => ['0.9m','1.8m','3m'], 'price' => 17.99],
            ['brand' => 'Belkin',   'name' => 'USB-C to Ethernet Adapter','lengths' => ['0.15m'], 'price' => 24.99],
            ['brand' => 'Cable Matters','name'=> 'DisplayPort to HDMI',    'lengths' => ['1m','2m'], 'price' => 12.99],
            ['brand' => 'StarTech', 'name' => 'Thunderbolt 4 Cable',     'lengths' => ['0.8m','1.0m','2m'], 'price' => 29.99],
        ];
        $categoryName = 'Cables & Adapters';
        foreach ($items as $item) {
            foreach ($item['lengths'] as $length) {
                $modelNumber = strtoupper(substr(preg_replace('/[^A-Za-z]/','', $item['brand']),0,3)) . '-' . str_replace([' ', '-', '.'], '', $item['name']) . '-' . str_replace(['.','m'], '', $length);
                $title       = $item['brand'] . ' ' . $item['name'] . ' ' . $length;
                $description = $item['brand'] . ' ' . $item['name'] . ' of length ' . $length . '.';
                $price       = $item['price'];
                // longer cables cost more
                if (floatval($length) > 1) {
                    $price += (floatval($length) - 1) * 3;
                }
                $this->makeProduct(
                    $categories,
                    $brands,
                    $categoryName,
                    $item['brand'],
                    $modelNumber,
                    $title,
                    $description,
                    $price,
                    false
                );
            }
        }
    }

    /**
     * Seed operating systems with different license types. Serialized because of licensing.
     */
    private function seedOperatingSystems(array $categories, array $brands): void
    {
        $oses = [
            ['brand' => 'Microsoft', 'name' => 'Windows 11 Home', 'licenses' => ['OEM','Retail'], 'price' => 139.99],
            ['brand' => 'Microsoft', 'name' => 'Windows 11 Pro',  'licenses' => ['OEM','Retail'], 'price' => 199.99],
            ['brand' => 'Microsoft', 'name' => 'Windows 10 Pro',  'licenses' => ['OEM','Retail'], 'price' => 159.99],
        ];
        $categoryName = 'Operating Systems';
        foreach ($oses as $os) {
            foreach ($os['licenses'] as $license) {
                $modelNumber = strtoupper(substr($os['brand'],0,3)) . '-' . str_replace(' ', '', $os['name']) . '-' . strtoupper(substr($license,0,3));
                $title       = $os['name'] . ' (' . $license . ' License)';
                $description = $os['brand'] . ' ' . $os['name'] . ' ' . $license . ' license.';
                $price       = $os['price'];
                if ($license === 'Retail') {
                    $price += 20;
                }
                $this->makeProduct(
                    $categories,
                    $brands,
                    $categoryName,
                    $os['brand'],
                    $modelNumber,
                    $title,
                    $description,
                    $price,
                    true
                );
            }
        }
    }

    /**
     * Seed productivity software with subscription durations.
     */
    private function seedProductivitySoftware(array $categories, array $brands): void
    {
        $software = [
            ['brand' => 'Microsoft','name' => 'Office 365 Personal','durations' => ['1 Month','1 Year','3 Years'], 'price' => 6.99],
            ['brand' => 'Microsoft','name' => 'Office 365 Family','durations' => ['1 Month','1 Year','3 Years'], 'price' => 9.99],
            ['brand' => 'Adobe',    'name' => 'Creative Cloud All Apps','durations' => ['1 Month','1 Year','3 Years'], 'price' => 52.99],
            ['brand' => 'Google',   'name' => 'Workspace Business',  'durations' => ['1 Month','1 Year','3 Years'], 'price' => 12.00],
        ];
        $categoryName = 'Productivity Software';
        foreach ($software as $sw) {
            foreach ($sw['durations'] as $duration) {
                $modelNumber = strtoupper(substr($sw['brand'],0,3)) . '-' . str_replace([' ', ':'], '', $sw['name']) . '-' . str_replace(' ', '', $duration);
                $title       = $sw['name'] . ' (' . $duration . ')';
                $description = $sw['brand'] . ' ' . $sw['name'] . ' subscription for ' . $duration . '.';
                $price       = $sw['price'];
                if (str_contains($duration, 'Year')) {
                    $years = (int) filter_var($duration, FILTER_SANITIZE_NUMBER_INT);
                    $price *= ($years * 12);
                    $price *= 0.9; // discount for yearly plans
                } elseif (str_contains($duration, '3')) {
                    $price *= (36);
                    $price *= 0.8;
                }
                $this->makeProduct(
                    $categories,
                    $brands,
                    $categoryName,
                    $sw['brand'],
                    $modelNumber,
                    $title,
                    $description,
                    $price,
                    true
                );
            }
        }
    }

    /**
     * Seed security software with durations and device counts.
     */
    private function seedSecuritySoftware(array $categories, array $brands): void
    {
        $security = [
            ['brand' => 'Norton',     'name' => '360 Deluxe',       'devices' => [1, 3, 5], 'price' => 39.99],
            ['brand' => 'McAfee',     'name' => 'Total Protection', 'devices' => [1, 3, 5], 'price' => 29.99],
            ['brand' => 'Bitdefender','name' => 'Internet Security','devices' => [1, 3, 5], 'price' => 34.99],
            ['brand' => 'Kaspersky',  'name' => 'Plus',             'devices' => [1, 3, 5], 'price' => 24.99],
        ];
        $categoryName = 'Security Software';
        foreach ($security as $sec) {
            foreach ($sec['devices'] as $count) {
                foreach (['1 Year','2 Years','3 Years'] as $duration) {
                    $modelNumber = strtoupper(substr($sec['brand'],0,3)) . '-' . str_replace(' ', '', $sec['name']) . '-' . $count . 'D-' . str_replace(' ', '', $duration);
                    $title       = $sec['brand'] . ' ' . $sec['name'] . ' - ' . $count . ' Device(s) / ' . $duration;
                    $description = $sec['brand'] . ' ' . $sec['name'] . ' for ' . $count . ' devices valid for ' . $duration . '.';
                    $price       = $sec['price'];
                    $price      += ($count - 1) * 10;
                    $years       = (int) filter_var($duration, FILTER_SANITIZE_NUMBER_INT);
                    $price      *= $years;
                    if ($years >= 2) {
                        $price *= 0.9;
                    }
                    $this->makeProduct(
                        $categories,
                        $brands,
                        $categoryName,
                        $sec['brand'],
                        $modelNumber,
                        $title,
                        $description,
                        $price,
                        true
                    );
                }
            }
        }
    }

    /**
     * Creates or updates a product record with calculated commission and SKU.
     */
    private function makeProduct(
        array $categories,
        array $brands,
        string $categoryName,
        string $brandName,
        string $modelNumber,
        string $title,
        string $description,
        float $price,
        bool $isSerialized
    ): void {
        if (!isset($categories[$categoryName]) || !isset($brands[$brandName])) {
            return;
        }
        $categoryId = $categories[$categoryName];
        $brandId    = $brands[$brandName];
        $sku        = $this->sku($brandName, $modelNumber);
        $commission = $this->commission($price);
        Product::updateOrCreate(
            ['model_number' => $modelNumber],
            [
                'category_id'      => $categoryId,
                'brand_id'         => $brandId,
                'internal_sku'     => $sku,
                'model_number'     => $modelNumber,
                'title'            => $title,
                'description'      => $description,
                'commission_value' => $commission,
                'is_serialized'    => $isSerialized,
                'is_active'        => true,
            ]
        );
    }

    /**
     * Calculate commission based on price tiers. Higher price yields lower
     * commission percentage.
     */
    private function commission(float $price): float
    {
        if ($price >= 2000) {
            return round($price * 0.05, 2);
        }
        if ($price >= 1000) {
            return round($price * 0.07, 2);
        }
        if ($price >= 500) {
            return round($price * 0.08, 2);
        }
        return round($price * 0.10, 2);
    }

    /**
     * Generate a consistent internal SKU using brand and model identifiers.
     */
    private function sku(string $brandName, string $modelNumber): string
    {
        $brandPart = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $brandName), 0, 3));
        $modelPart = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $modelNumber), 0, 5));
        $unique    = strtoupper(Str::random(4));
        return $brandPart . '-' . $modelPart . '-' . $unique;
    }
}