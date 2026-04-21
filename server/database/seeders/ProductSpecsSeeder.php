<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\ProductSpec;
use Illuminate\Support\Str;

class ProductSpecsSeeder extends Seeder
{
    public function run(): void
    {
        Product::chunk(100, function ($products) {
            foreach ($products as $product) {
                $this->seedSpecsForProduct($product);
            }
        });
    }

    private function seedSpecsForProduct(Product $product): void
    {
        $category = $product->category?->name;
        $brand = $product->brand?->name;
        $title = $product->title;
        $model = $product->model_number;

        if (!$category) {
            return;
        }

        $specs = match ($category) {
            'Laptops' => $this->laptopSpecs($brand, $title, $model),
            'Desktop PCs' => $this->desktopSpecs($brand, $title, $model),
            'Tablets' => $this->tabletSpecs($brand, $title, $model),
            'Handhelds' => $this->handheldSpecs($brand, $title, $model),
            'Processors (CPU)' => $this->cpuSpecs($brand, $title, $model),
            'Graphics Cards (GPU)' => $this->gpuSpecs($brand, $title, $model),
            'Motherboards' => $this->motherboardSpecs($brand, $title, $model),
            'Memory (RAM)' => $this->memorySpecs($brand, $title, $model),
            'Power Supplies (PSU)' => $this->psuSpecs($brand, $title, $model),
            'Cases & Chassis' => $this->caseSpecs($brand, $title, $model),
            'Internal SSDs' => $this->internalSsdSpecs($brand, $title, $model),
            'Hard Drives (HDD)' => $this->hddSpecs($brand, $title, $model),
            'External Storage' => $this->externalStorageSpecs($brand, $title, $model),
            'Memory Cards' => $this->memoryCardSpecs($brand, $title, $model),
            'CPU Cooling' => $this->cpuCoolingSpecs($brand, $title, $model),
            'Case Fans' => $this->caseFanSpecs($brand, $title, $model),
            'Thermal Solutions' => $this->thermalSpecs($brand, $title, $model),
            'Tools & Equipment' => $this->toolSpecs($brand, $title, $model),
            'Monitors' => $this->monitorSpecs($brand, $title, $model),
            'Keyboards' => $this->keyboardSpecs($brand, $title, $model),
            'Mice' => $this->mouseSpecs($brand, $title, $model),
            'Audio' => $this->audioSpecs($brand, $title, $model),
            'Streaming Gear' => $this->streamingSpecs($brand, $title, $model),
            'Wireless Networking' => $this->wirelessSpecs($brand, $title, $model),
            'Wired Networking' => $this->wiredSpecs($brand, $title, $model),
            'Cables & Adapters' => $this->cableSpecs($brand, $title, $model),
            'Operating Systems' => $this->osSpecs($brand, $title, $model),
            'Productivity Software' => $this->productivitySpecs($brand, $title, $model),
            'Security Software' => $this->securitySpecs($brand, $title, $model),
            default => [],
        };

        foreach ($specs as $specName => $specValue) {
            ProductSpec::updateOrCreate(
                [
                    'product_id' => $product->id,
                    'spec_name' => $specName,
                ],
                [
                    'spec_value' => $specValue,
                ]
            );
        }
    }

    private function laptopSpecs(?string $brand, string $title, string $model): array
    {
        $screenSize = $this->matchFirst($title, [
            '13"' => '13-inch',
            '14"' => '14-inch',
            '15"' => '15-inch',
            '16"' => '16-inch',
            '18' => '18-inch',
        ]) ?? '14-inch';

        $ram = $this->extractNumberBefore($title, 'GB') ?? match (true) {
            Str::contains($title, ['MacBook Pro', 'XPS 16', 'Titan', 'Zephyrus']) => 32,
            default => 16,
        };

        $storage = $this->extractStorage($title) ?? 512;

        $cpuFamily = match (true) {
            Str::contains($title, ['MacBook Air', 'MacBook Pro']) => 'Apple Silicon',
            Str::contains($title, ['ROG', 'TUF', 'OMEN', 'Legion', 'Titan', 'Katana']) => 'High-performance Mobile CPU',
            Str::contains($title, ['ThinkPad', 'Latitude', 'EliteBook', 'Surface']) => 'Business-class Mobile CPU',
            default => 'Mobile CPU',
        };

        $gpu = match (true) {
            Str::contains($title, ['ROG', 'TUF', 'OMEN', 'Legion', 'Alienware', 'Titan', 'Katana']) => 'Dedicated GPU',
            Str::contains($title, ['MacBook Pro']) => 'Integrated / Apple GPU',
            default => 'Integrated Graphics',
        };

        return [
            'Form Factor' => 'Laptop',
            'Screen Size' => $screenSize,
            'Resolution' => Str::contains($title, ['OLED', 'Spectre', 'Zenbook']) ? '2880 x 1800' : '1920 x 1200',
            'Processor Family' => $cpuFamily,
            'Memory' => $ram . 'GB',
            'Storage' => $storage >= 1024 ? ($storage / 1024) . 'TB SSD' : $storage . 'GB SSD',
            'Graphics' => $gpu,
            'Operating System' => $brand === 'Apple' ? 'macOS' : 'Windows 11',
            'Wireless' => 'Wi-Fi 6E / Bluetooth',
            'Keyboard Layout' => 'US English',
            'Color' => $this->detectColor($title) ?? 'Black',
            'Model Reference' => $model,
        ];
    }

    private function desktopSpecs(?string $brand, string $title, string $model): array
    {
        return [
            'Form Factor' => Str::contains($title, ['Mini', 'Tiny']) ? 'Mini PC' : 'Tower',
            'Processor Family' => Str::contains($title, ['OMEN', 'Legion', 'Alienware', 'Predator']) ? 'Gaming Desktop CPU' : 'Business Desktop CPU',
            'Graphics' => Str::contains($title, ['OMEN', 'Legion', 'Alienware', 'Predator', 'ROG', 'Aegis']) ? 'Dedicated GPU' : 'Integrated / Optional GPU',
            'Memory' => Str::contains($title, ['Mini', 'Tiny']) ? '16GB' : '32GB',
            'Storage' => '1TB SSD',
            'Operating System' => 'Windows 11',
            'Connectivity' => 'USB-C, USB-A, HDMI, Ethernet',
            'Networking' => 'Wi-Fi / Bluetooth / Gigabit Ethernet',
            'Included Accessories' => 'Power cable only',
            'Color' => 'Black',
            'Model Reference' => $model,
        ];
    }

    private function tabletSpecs(?string $brand, string $title, string $model): array
    {
        return [
            'Form Factor' => 'Tablet',
            'Display Size' => $this->matchFirst($title, [
                '11"' => '11-inch',
                '13"' => '13-inch',
            ]) ?? '11-inch',
            'Display Type' => Str::contains($title, ['iPad Pro', 'Galaxy Tab S']) ? 'Premium display' : 'LCD',
            'Storage' => ($this->extractStorage($title) ?? 128) . 'GB',
            'Memory' => Str::contains($title, ['Pro', 'Surface']) ? '8GB / 16GB' : '8GB',
            'Operating System' => match ($brand) {
                'Apple' => 'iPadOS',
                'Microsoft' => 'Windows 11',
                default => 'Android',
            },
            'Wireless' => 'Wi-Fi / Bluetooth',
            'Rear Camera' => 'Yes',
            'Front Camera' => 'Yes',
            'Color' => $this->detectColor($title) ?? 'Gray',
            'Model Reference' => $model,
        ];
    }

    private function handheldSpecs(?string $brand, string $title, string $model): array
    {
        return [
            'Form Factor' => 'Handheld',
            'Display Size' => '7-inch to 8.8-inch',
            'Storage' => ($this->extractStorage($title) ?? 512) . 'GB',
            'Memory' => '16GB',
            'Operating System' => match ($brand) {
                'Valve' => 'SteamOS',
                'ASUS', 'Lenovo' => 'Windows 11',
                'Nintendo' => 'Nintendo OS',
                default => 'Custom OS',
            },
            'Wireless' => 'Wi-Fi / Bluetooth',
            'Controls' => 'Integrated gaming controls',
            'Battery' => 'Integrated rechargeable battery',
            'Color' => $this->detectColor($title) ?? 'Black',
            'Model Reference' => $model,
        ];
    }

    private function cpuSpecs(?string $brand, string $title, string $model): array
    {
        $cores = match (true) {
            Str::contains($title, ['i3']) => '4 Cores',
            Str::contains($title, ['i5', 'Ryzen 5']) => '6 Cores',
            Str::contains($title, ['i7', 'Ryzen 7']) => '8 Cores',
            Str::contains($title, ['i9', 'Ryzen 9']) => '12 to 16 Cores',
            default => 'Multi-Core',
        };

        return [
            'Socket' => Str::contains($brand, 'Intel') ? 'LGA 1700 / newer' : 'AM5',
            'Core Configuration' => $cores,
            'Unlocked' => Str::contains($title, ['K', 'X']) ? 'Yes' : 'No',
            'Integrated Graphics' => Str::contains($title, ['F']) ? 'No' : 'Varies by SKU',
            'Cooling Included' => Str::contains($title, ['K', 'X3D', 'X']) ? 'No' : 'Varies by SKU',
            'Processor Family' => $title,
            'Platform' => $brand === 'Intel' ? 'Intel Desktop' : 'AMD Desktop',
            'Model Reference' => $model,
        ];
    }

    private function gpuSpecs(?string $brand, string $title, string $model): array
    {
        return [
            'Graphics Chipset' => $title,
            'Memory Size' => $this->extractNumberBefore($title, 'GB') ? $this->extractNumberBefore($title, 'GB') . 'GB' : '12GB',
            'Interface' => 'PCIe x16',
            'Display Outputs' => 'HDMI / DisplayPort',
            'Cooling' => 'Active cooling',
            'Ray Tracing' => Str::contains($title, ['RTX', '7900', '7800', '7700']) ? 'Supported' : 'Varies',
            'Power Connector' => 'Varies by model',
            'Model Reference' => $model,
        ];
    }

    private function motherboardSpecs(?string $brand, string $title, string $model): array
    {
        return [
            'Socket' => Str::contains($title, ['B650', 'X670']) ? 'AM5' : 'LGA 1700',
            'Form Factor' => $this->matchFirst($title, [
                'Micro' => 'Micro-ATX',
                'Mini' => 'Mini-ITX',
            ]) ?? 'ATX',
            'Memory Support' => Str::contains($title, ['B650', 'X670']) ? 'DDR5' : 'DDR4 / DDR5',
            'PCIe Generation' => 'PCIe 4.0 / PCIe 5.0',
            'Wi-Fi' => Str::contains($title, ['WiFi', 'Wi-Fi']) ? 'Included' : 'Optional / No',
            'M.2 Slots' => 'Multiple M.2 slots',
            'SATA Ports' => '4 to 6 SATA ports',
            'Rear I/O' => 'USB, HDMI/DP, Ethernet, Audio',
            'Model Reference' => $model,
        ];
    }

    private function memorySpecs(?string $brand, string $title, string $model): array
    {
        return [
            'Memory Type' => Str::contains($title, ['DDR5']) ? 'DDR5' : 'DDR4',
            'Capacity' => $this->extractNumberBefore($title, 'GB') ? $this->extractNumberBefore($title, 'GB') . 'GB' : '32GB',
            'Speed' => $this->extractSpeedMHz($title) ?? '3200MHz',
            'Kit Configuration' => Str::contains($title, ['32GB']) ? '2 x 16GB' : 'Varies',
            'RGB Lighting' => Str::contains($title, ['RGB']) ? 'Yes' : 'No',
            'Compatibility' => 'Desktop systems',
            'Model Reference' => $model,
        ];
    }

    private function psuSpecs(?string $brand, string $title, string $model): array
    {
        return [
            'Wattage' => $this->extractNumberBefore($title, 'W') ? $this->extractNumberBefore($title, 'W') . 'W' : '750W',
            'Efficiency Rating' => Str::contains($title, ['Gold']) ? '80 Plus Gold' : '80 Plus',
            'Modular' => Str::contains($title, ['RM', 'Shift', 'Thor', 'Toughpower']) ? 'Fully / Semi Modular' : 'Varies',
            'Cooling' => '120mm / 135mm fan',
            'ATX Standard' => 'ATX',
            'Use Case' => 'Desktop PC power supply',
            'Model Reference' => $model,
        ];
    }

    private function caseSpecs(?string $brand, string $title, string $model): array
    {
        return [
            'Case Type' => 'Mid Tower / Full Tower',
            'Motherboard Support' => 'ATX / Micro-ATX / Mini-ITX',
            'Front Panel' => Str::contains($title, ['Flow', 'Airflow', 'Mesh']) ? 'Mesh' : 'Solid / Tempered Glass',
            'Side Panel' => 'Tempered glass / steel',
            'Drive Bays' => '2.5-inch / 3.5-inch support',
            'Cooling Support' => 'Multiple fan and radiator mounts',
            'Color' => $this->detectColor($title) ?? 'Black',
            'Model Reference' => $model,
        ];
    }

    private function internalSsdSpecs(?string $brand, string $title, string $model): array
    {
        return [
            'Capacity' => $this->extractStorage($title) ? ($this->extractStorage($title) >= 1024 ? ($this->extractStorage($title) / 1024) . 'TB' : $this->extractStorage($title) . 'GB') : '1TB',
            'Interface' => 'PCIe NVMe',
            'Form Factor' => 'M.2 2280',
            'Use Case' => 'Internal system storage',
            'Read Speed' => 'High-performance sequential read',
            'Write Speed' => 'High-performance sequential write',
            'Heatsink' => Str::contains($title, ['Heatsink']) ? 'Included' : 'Optional / No',
            'Model Reference' => $model,
        ];
    }

    private function hddSpecs(?string $brand, string $title, string $model): array
    {
        return [
            'Capacity' => $this->extractStorage($title) ? ($this->extractStorage($title) / 1024) . 'TB' : '4TB',
            'Drive Type' => '3.5-inch HDD',
            'Interface' => 'SATA',
            'Cache' => 'Varies by model',
            'Rotation Speed' => Str::contains($title, ['NAS', 'Performance']) ? '7200 RPM' : '5400 / 7200 RPM',
            'Use Case' => Str::contains($title, ['NAS']) ? 'NAS / RAID storage' : 'Desktop storage',
            'Model Reference' => $model,
        ];
    }

    private function externalStorageSpecs(?string $brand, string $title, string $model): array
    {
        return [
            'Capacity' => $this->extractStorage($title) ? ($this->extractStorage($title) >= 1024 ? ($this->extractStorage($title) / 1024) . 'TB' : $this->extractStorage($title) . 'GB') : '1TB',
            'Connection' => 'USB-C / USB 3.x',
            'Portable' => 'Yes',
            'Drive Type' => Str::contains($title, ['SSD']) ? 'Portable SSD' : 'Portable HDD',
            'Compatibility' => 'Windows / macOS',
            'Color' => $this->detectColor($title) ?? 'Black',
            'Model Reference' => $model,
        ];
    }

    private function memoryCardSpecs(?string $brand, string $title, string $model): array
    {
        return [
            'Capacity' => $this->extractNumberBefore($title, 'GB') ? $this->extractNumberBefore($title, 'GB') . 'GB' : '128GB',
            'Card Type' => Str::contains($title, ['microSD']) ? 'microSDXC' : 'SDXC',
            'Speed Class' => Str::contains($title, ['Extreme', 'Professional']) ? 'U3 / V30 or higher' : 'U1 / U3',
            'Use Case' => 'Cameras / handhelds / mobile devices',
            'Model Reference' => $model,
        ];
    }

    private function cpuCoolingSpecs(?string $brand, string $title, string $model): array
    {
        return [
            'Cooler Type' => Str::contains($title, ['Kraken', 'H100', 'Liquid']) ? 'Liquid Cooler' : 'Air Cooler',
            'Socket Support' => 'Intel and AMD desktop sockets',
            'Radiator Size' => Str::contains($title, ['360']) ? '360mm' : (Str::contains($title, ['240']) ? '240mm' : 'N/A'),
            'Fan Included' => 'Yes',
            'RGB' => Str::contains($title, ['RGB', 'Capellix']) ? 'Yes' : 'No',
            'Model Reference' => $model,
        ];
    }

    private function caseFanSpecs(?string $brand, string $title, string $model): array
    {
        return [
            'Fan Size' => Str::contains($title, ['140']) ? '140mm' : '120mm',
            'Pack Size' => Str::contains($title, ['Triple', '3']) ? '3-Pack' : 'Single / Multi-Pack',
            'RGB' => Str::contains($title, ['RGB', 'Light']) ? 'Yes' : 'No',
            'Bearing Type' => 'Varies by manufacturer',
            'PWM Control' => Str::contains($title, ['PWM']) ? 'Yes' : 'Varies',
            'Model Reference' => $model,
        ];
    }

    private function thermalSpecs(?string $brand, string $title, string $model): array
    {
        return [
            'Product Type' => 'Thermal compound',
            'Application' => 'CPU / GPU cooling',
            'Package Size' => $this->extractNumberBefore($title, 'g') ? $this->extractNumberBefore($title, 'g') . 'g' : 'Standard tube',
            'Electrical Conductivity' => 'Non-conductive / varies',
            'Model Reference' => $model,
        ];
    }

    private function toolSpecs(?string $brand, string $title, string $model): array
    {
        return [
            'Product Type' => 'PC tool / equipment',
            'Use Case' => 'Repair / assembly / maintenance',
            'Portability' => 'Portable',
            'Included Pieces' => 'Varies by kit',
            'Model Reference' => $model,
        ];
    }

    private function monitorSpecs(?string $brand, string $title, string $model): array
    {
        return [
            'Screen Size' => $this->extractNumberBefore($title, 'inch') ? $this->extractNumberBefore($title, 'inch') . '-inch' : '27-inch',
            'Resolution' => Str::contains($title, ['5K']) ? '5120 x 2880' : (Str::contains($title, ['4K']) ? '3840 x 2160' : '2560 x 1440'),
            'Refresh Rate' => $this->extractRefreshRate($title) ?? '60Hz',
            'Panel Type' => Str::contains($title, ['UltraSharp', 'ViewFinity', 'ProArt']) ? 'IPS' : 'IPS / VA',
            'Adaptive Sync' => Str::contains($title, ['Gaming', 'UltraGear', 'Odyssey', 'TUF']) ? 'Supported' : 'Varies',
            'Ports' => 'HDMI / DisplayPort / USB-C varies',
            'Color' => 'Black',
            'Model Reference' => $model,
        ];
    }

    private function keyboardSpecs(?string $brand, string $title, string $model): array
    {
        return [
            'Keyboard Type' => Str::contains($title, ['Mechanical', 'BlackWidow', 'Huntsman', 'Apex', 'K70']) ? 'Mechanical' : 'Wireless / Membrane / Scissor',
            'Connection' => Str::contains($title, ['Wireless', 'Lightspeed']) ? 'Wireless' : 'Wired / Wireless',
            'Backlighting' => Str::contains($title, ['RGB']) ? 'RGB' : 'White / None',
            'Layout' => 'US English',
            'Form Factor' => Str::contains($title, ['TKL']) ? 'Tenkeyless' : 'Full Size / Compact',
            'Model Reference' => $model,
        ];
    }

    private function mouseSpecs(?string $brand, string $title, string $model): array
    {
        return [
            'Connection' => Str::contains($title, ['Wireless', 'Master 3S', 'Superlight']) ? 'Wireless' : 'Wired / Wireless',
            'Sensor Type' => 'Optical',
            'Use Case' => Str::contains($title, ['G Pro', 'DeathAdder', 'Viper', 'Aerox']) ? 'Gaming' : 'Productivity',
            'Buttons' => 'Programmable / standard buttons',
            'Hand Orientation' => 'Right-handed / ambidextrous varies',
            'Model Reference' => $model,
        ];
    }

    private function audioSpecs(?string $brand, string $title, string $model): array
    {
        return [
            'Product Type' => Str::contains($title, ['Speakers', 'Pebble']) ? 'Speakers' : 'Headset / Headphones',
            'Connection' => Str::contains($title, ['Wireless', 'Bluetooth']) ? 'Wireless / Bluetooth' : 'Wired',
            'Noise Cancellation' => Str::contains($title, ['QuietComfort', 'WH-1000', 'Nova Pro']) ? 'Yes / Advanced' : 'Varies',
            'Microphone' => Str::contains($title, ['Headset', 'Pro X', 'BlackShark', 'Cloud']) ? 'Included' : 'No / Varies',
            'Color' => $this->detectColor($title) ?? 'Black',
            'Model Reference' => $model,
        ];
    }

    private function streamingSpecs(?string $brand, string $title, string $model): array
    {
        return [
            'Product Type' => match (true) {
                Str::contains($title, ['Webcam', 'Facecam', 'Kiyo']) => 'Webcam',
                Str::contains($title, ['Deck']) => 'Control Surface',
                Str::contains($title, ['Capture']) => 'Capture Card',
                Str::contains($title, ['Mic', 'Wave', 'QuadCast']) => 'Microphone',
                default => 'Streaming Accessory',
            },
            'Connection' => 'USB / HDMI varies',
            'Streaming Use' => 'Content creation / live streaming',
            'Color' => $this->detectColor($title) ?? 'Black',
            'Model Reference' => $model,
        ];
    }

    private function wirelessSpecs(?string $brand, string $title, string $model): array
    {
        return [
            'Device Type' => Str::contains($title, ['Deco', 'Mesh', 'Pack']) ? 'Mesh Wi-Fi System' : 'Wireless Router / Access Point',
            'Wi-Fi Standard' => Str::contains($title, ['6E', 'Wi-Fi 7']) ? 'Wi-Fi 6E / Wi-Fi 7' : 'Wi-Fi 6',
            'Bands' => 'Dual-band / Tri-band varies',
            'Ports' => 'Ethernet ports vary by model',
            'Management' => 'Web / App management',
            'Model Reference' => $model,
        ];
    }

    private function wiredSpecs(?string $brand, string $title, string $model): array
    {
        return [
            'Device Type' => 'Ethernet Switch',
            'Port Count' => $this->extractNumberBefore($title, 'Port') ? $this->extractNumberBefore($title, 'Port') . ' Ports' : '8 Ports',
            'Speed' => 'Gigabit Ethernet',
            'Managed' => Str::contains($title, ['Smart', 'UniFi', 'CBS']) ? 'Managed / Smart' : 'Unmanaged',
            'Mounting' => 'Desktop / rack varies',
            'Model Reference' => $model,
        ];
    }

    private function cableSpecs(?string $brand, string $title, string $model): array
    {
        return [
            'Product Type' => match (true) {
                Str::contains($title, ['HDMI']) => 'HDMI Cable / Adapter',
                Str::contains($title, ['DisplayPort']) => 'DisplayPort Cable / Adapter',
                Str::contains($title, ['Ethernet', 'CAT']) => 'Ethernet Cable',
                Str::contains($title, ['USB-C']) => 'USB-C Accessory',
                default => 'Cable / Adapter',
            },
            'Length' => $this->extractCableLength($title) ?? 'Standard length',
            'Connector Type' => 'Varies by product',
            'Use Case' => 'Power / display / data connectivity',
            'Model Reference' => $model,
        ];
    }

    private function osSpecs(?string $brand, string $title, string $model): array
    {
        return [
            'License Type' => Str::contains($title, ['OEM']) ? 'OEM' : 'Retail',
            'Platform' => 'PC',
            'Version' => $title,
            'Delivery' => 'Digital / Physical media varies',
            'Model Reference' => $model,
        ];
    }

    private function productivitySpecs(?string $brand, string $title, string $model): array
    {
        return [
            'Software Type' => 'Productivity Software',
            'License Term' => Str::contains($title, ['Annual', '1 Year']) ? '1 Year' : 'Perpetual / Varies',
            'Delivery' => 'Digital license',
            'Platform' => 'Windows / macOS varies',
            'Model Reference' => $model,
        ];
    }

    private function securitySpecs(?string $brand, string $title, string $model): array
    {
        return [
            'Software Type' => 'Security Software',
            'License Term' => Str::contains($title, ['1 Year']) ? '1 Year' : 'Varies',
            'Protected Devices' => $this->extractNumberBefore($title, 'Devices') ? $this->extractNumberBefore($title, 'Devices') . ' Devices' : 'Multiple devices',
            'Delivery' => 'Digital license',
            'Model Reference' => $model,
        ];
    }

    private function extractNumberBefore(string $text, string $keyword): ?int
    {
        if (preg_match('/(\d+)\s*' . preg_quote($keyword, '/') . '/i', $text, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    private function extractStorage(string $text): ?int
    {
        if (preg_match('/(\d+)\s*TB/i', $text, $matches)) {
            return (int) $matches[1] * 1024;
        }

        if (preg_match('/(\d+)\s*GB/i', $text, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    private function extractSpeedMHz(string $text): ?string
    {
        if (preg_match('/(\d{4,5})\s*MHz/i', $text, $matches)) {
            return $matches[1] . 'MHz';
        }

        if (preg_match('/DDR[45][-_ ]?(\d{4,5})/i', $text, $matches)) {
            return $matches[1] . 'MHz';
        }

        return null;
    }

    private function extractRefreshRate(string $text): ?string
    {
        if (preg_match('/(\d{2,3})Hz/i', $text, $matches)) {
            return $matches[1] . 'Hz';
        }

        return null;
    }

    private function extractCableLength(string $text): ?string
    {
        if (preg_match('/(\d+(\.\d+)?)\s*ft/i', $text, $matches)) {
            return $matches[1] . ' ft';
        }

        if (preg_match('/(\d+(\.\d+)?)\s*m/i', $text, $matches)) {
            return $matches[1] . ' m';
        }

        return null;
    }

    private function detectColor(string $text): ?string
    {
        $colors = [
            'Black',
            'White',
            'Silver',
            'Gray',
            'Graphite',
            'Midnight',
            'Blue',
            'Red',
            'Pink',
            'Green',
            'Beige',
            'Space Gray',
        ];

        foreach ($colors as $color) {
            if (Str::contains($text, $color)) {
                return $color;
            }
        }

        return null;
    }

    private function matchFirst(string $text, array $map): ?string
    {
        foreach ($map as $needle => $value) {
            if (Str::contains($text, $needle)) {
                return $value;
            }
        }

        return null;
    }
}