<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $categories = Category::pluck('id', 'name');
        $suppliers = Supplier::pluck('id', 'name');

        $products = [
            // Laptops
            ['sku' => 'KDT-LAP-001', 'name' => 'ThinkPro 14 Laptop', 'category' => 'Laptops', 'supplier' => 'TechSource Distributors', 'brand' => 'ThinkPro', 'cost_price' => 640.00, 'selling_price' => 799.00],
            ['sku' => 'KDT-LAP-002', 'name' => 'ThinkPro 16 Laptop', 'category' => 'Laptops', 'supplier' => 'TechSource Distributors', 'brand' => 'ThinkPro', 'cost_price' => 760.00, 'selling_price' => 949.00],
            ['sku' => 'KDT-LAP-003', 'name' => 'AeroBook 15 Laptop', 'category' => 'Laptops', 'supplier' => 'Nova Hardware Supply', 'brand' => 'AeroBook', 'cost_price' => 720.00, 'selling_price' => 899.00],
            ['sku' => 'KDT-LAP-004', 'name' => 'AeroBook 13 Laptop', 'category' => 'Laptops', 'supplier' => 'Nova Hardware Supply', 'brand' => 'AeroBook', 'cost_price' => 600.00, 'selling_price' => 759.00],
            ['sku' => 'KDT-LAP-005', 'name' => 'ZenithBook Ryzen 7 Laptop', 'category' => 'Laptops', 'supplier' => 'Vertex Computing Imports', 'brand' => 'ZenithBook', 'cost_price' => 810.00, 'selling_price' => 1019.00],
            ['sku' => 'KDT-LAP-006', 'name' => 'ZenithBook Business 14', 'category' => 'Laptops', 'supplier' => 'Vertex Computing Imports', 'brand' => 'ZenithBook', 'cost_price' => 690.00, 'selling_price' => 869.00],
            ['sku' => 'KDT-LAP-007', 'name' => 'CoreLite Student 15 Laptop', 'category' => 'Laptops', 'supplier' => 'CoreLink Systems', 'brand' => 'CoreLite', 'cost_price' => 470.00, 'selling_price' => 599.00],
            ['sku' => 'KDT-LAP-008', 'name' => 'CoreLite Student 14 Laptop', 'category' => 'Laptops', 'supplier' => 'CoreLink Systems', 'brand' => 'CoreLite', 'cost_price' => 440.00, 'selling_price' => 569.00],
            ['sku' => 'KDT-LAP-009', 'name' => 'VoltEdge Creator 16 Laptop', 'category' => 'Laptops', 'supplier' => 'Titan IT Distribution', 'brand' => 'VoltEdge', 'cost_price' => 980.00, 'selling_price' => 1219.00],
            ['sku' => 'KDT-LAP-010', 'name' => 'VoltEdge Creator 14 Laptop', 'category' => 'Laptops', 'supplier' => 'Titan IT Distribution', 'brand' => 'VoltEdge', 'cost_price' => 910.00, 'selling_price' => 1129.00],
            ['sku' => 'KDT-LAP-011', 'name' => 'PulseNote 15 Laptop', 'category' => 'Laptops', 'supplier' => 'Pinnacle Systems Trade', 'brand' => 'PulseNote', 'cost_price' => 650.00, 'selling_price' => 819.00],
            ['sku' => 'KDT-LAP-012', 'name' => 'PulseNote 13 Laptop', 'category' => 'Laptops', 'supplier' => 'Pinnacle Systems Trade', 'brand' => 'PulseNote', 'cost_price' => 560.00, 'selling_price' => 709.00],

            // Desktops
            ['sku' => 'KDT-DES-001', 'name' => 'CoreStation Business Desktop', 'category' => 'Desktops', 'supplier' => 'Prime Circuit Wholesale', 'brand' => 'CoreStation', 'cost_price' => 580.00, 'selling_price' => 749.00],
            ['sku' => 'KDT-DES-002', 'name' => 'CoreStation Pro Desktop', 'category' => 'Desktops', 'supplier' => 'Prime Circuit Wholesale', 'brand' => 'CoreStation', 'cost_price' => 730.00, 'selling_price' => 929.00],
            ['sku' => 'KDT-DES-003', 'name' => 'VoltEdge Tower PC', 'category' => 'Desktops', 'supplier' => 'Titan IT Distribution', 'brand' => 'VoltEdge', 'cost_price' => 690.00, 'selling_price' => 879.00],
            ['sku' => 'KDT-DES-004', 'name' => 'VoltEdge Compact Desktop', 'category' => 'Desktops', 'supplier' => 'Titan IT Distribution', 'brand' => 'VoltEdge', 'cost_price' => 520.00, 'selling_price' => 679.00],
            ['sku' => 'KDT-DES-005', 'name' => 'NexaBox Office Desktop', 'category' => 'Desktops', 'supplier' => 'Nexa Device Wholesale', 'brand' => 'NexaBox', 'cost_price' => 610.00, 'selling_price' => 789.00],
            ['sku' => 'KDT-DES-006', 'name' => 'NexaBox Elite Desktop', 'category' => 'Desktops', 'supplier' => 'Nexa Device Wholesale', 'brand' => 'NexaBox', 'cost_price' => 780.00, 'selling_price' => 979.00],
            ['sku' => 'KDT-DES-007', 'name' => 'ZenGrid Mini PC', 'category' => 'Desktops', 'supplier' => 'BlueGrid Electronics', 'brand' => 'ZenGrid', 'cost_price' => 340.00, 'selling_price' => 469.00],
            ['sku' => 'KDT-DES-008', 'name' => 'ZenGrid Pro Mini PC', 'category' => 'Desktops', 'supplier' => 'BlueGrid Electronics', 'brand' => 'ZenGrid', 'cost_price' => 460.00, 'selling_price' => 619.00],
            ['sku' => 'KDT-DES-009', 'name' => 'MetroCore Gaming Tower', 'category' => 'Desktops', 'supplier' => 'Metro Circuit Supply', 'brand' => 'MetroCore', 'cost_price' => 940.00, 'selling_price' => 1189.00],
            ['sku' => 'KDT-DES-010', 'name' => 'MetroCore Creator Tower', 'category' => 'Desktops', 'supplier' => 'Metro Circuit Supply', 'brand' => 'MetroCore', 'cost_price' => 980.00, 'selling_price' => 1249.00],
            ['sku' => 'KDT-DES-011', 'name' => 'HarborDesk Essential PC', 'category' => 'Desktops', 'supplier' => 'Harbor Tech Supply', 'brand' => 'HarborDesk', 'cost_price' => 490.00, 'selling_price' => 639.00],
            ['sku' => 'KDT-DES-012', 'name' => 'HarborDesk Performance PC', 'category' => 'Desktops', 'supplier' => 'Harbor Tech Supply', 'brand' => 'HarborDesk', 'cost_price' => 670.00, 'selling_price' => 859.00],

            // Monitors
            ['sku' => 'KDT-MON-001', 'name' => 'VisionEdge 24 Monitor', 'category' => 'Monitors', 'supplier' => 'BlueGrid Electronics', 'brand' => 'VisionEdge', 'cost_price' => 110.00, 'selling_price' => 169.00],
            ['sku' => 'KDT-MON-002', 'name' => 'VisionEdge 27 Monitor', 'category' => 'Monitors', 'supplier' => 'BlueGrid Electronics', 'brand' => 'VisionEdge', 'cost_price' => 145.00, 'selling_price' => 219.00],
            ['sku' => 'KDT-MON-003', 'name' => 'VisionEdge 27 QHD Monitor', 'category' => 'Monitors', 'supplier' => 'BlueGrid Electronics', 'brand' => 'VisionEdge', 'cost_price' => 190.00, 'selling_price' => 279.00],
            ['sku' => 'KDT-MON-004', 'name' => 'ClearPanel 22 Monitor', 'category' => 'Monitors', 'supplier' => 'Harbor Tech Supply', 'brand' => 'ClearPanel', 'cost_price' => 88.00, 'selling_price' => 139.00],
            ['sku' => 'KDT-MON-005', 'name' => 'ClearPanel 24 IPS Monitor', 'category' => 'Monitors', 'supplier' => 'Harbor Tech Supply', 'brand' => 'ClearPanel', 'cost_price' => 122.00, 'selling_price' => 189.00],
            ['sku' => 'KDT-MON-006', 'name' => 'ClearPanel 32 Curved Monitor', 'category' => 'Monitors', 'supplier' => 'Harbor Tech Supply', 'brand' => 'ClearPanel', 'cost_price' => 240.00, 'selling_price' => 349.00],
            ['sku' => 'KDT-MON-007', 'name' => 'PixelView 24 Business Monitor', 'category' => 'Monitors', 'supplier' => 'Summit Digital Supply', 'brand' => 'PixelView', 'cost_price' => 115.00, 'selling_price' => 175.00],
            ['sku' => 'KDT-MON-008', 'name' => 'PixelView 27 Business Monitor', 'category' => 'Monitors', 'supplier' => 'Summit Digital Supply', 'brand' => 'PixelView', 'cost_price' => 155.00, 'selling_price' => 229.00],
            ['sku' => 'KDT-MON-009', 'name' => 'Lumina 34 Ultrawide Monitor', 'category' => 'Monitors', 'supplier' => 'Nexa Device Wholesale', 'brand' => 'Lumina', 'cost_price' => 310.00, 'selling_price' => 439.00],
            ['sku' => 'KDT-MON-010', 'name' => 'Lumina 29 Ultrawide Monitor', 'category' => 'Monitors', 'supplier' => 'Nexa Device Wholesale', 'brand' => 'Lumina', 'cost_price' => 245.00, 'selling_price' => 369.00],
            ['sku' => 'KDT-MON-011', 'name' => 'GridSight 24 Monitor', 'category' => 'Monitors', 'supplier' => 'Pinnacle Systems Trade', 'brand' => 'GridSight', 'cost_price' => 108.00, 'selling_price' => 165.00],
            ['sku' => 'KDT-MON-012', 'name' => 'GridSight 27 QHD Monitor', 'category' => 'Monitors', 'supplier' => 'Pinnacle Systems Trade', 'brand' => 'GridSight', 'cost_price' => 182.00, 'selling_price' => 269.00],

            // Components
            ['sku' => 'KDT-CMP-001', 'name' => 'Ryphor X6 Processor', 'category' => 'Components', 'supplier' => 'Vertex Computing Imports', 'brand' => 'Ryphor', 'cost_price' => 210.00, 'selling_price' => 299.00],
            ['sku' => 'KDT-CMP-002', 'name' => 'Ryphor X8 Processor', 'category' => 'Components', 'supplier' => 'Vertex Computing Imports', 'brand' => 'Ryphor', 'cost_price' => 295.00, 'selling_price' => 419.00],
            ['sku' => 'KDT-CMP-003', 'name' => 'Voltis B760 Motherboard', 'category' => 'Components', 'supplier' => 'Prime Circuit Wholesale', 'brand' => 'Voltis', 'cost_price' => 125.00, 'selling_price' => 189.00],
            ['sku' => 'KDT-CMP-004', 'name' => 'Voltis X670 Motherboard', 'category' => 'Components', 'supplier' => 'Prime Circuit Wholesale', 'brand' => 'Voltis', 'cost_price' => 215.00, 'selling_price' => 319.00],
            ['sku' => 'KDT-CMP-005', 'name' => 'Zephyr 16GB DDR5 RAM', 'category' => 'Components', 'supplier' => 'TechSource Distributors', 'brand' => 'Zephyr', 'cost_price' => 42.00, 'selling_price' => 69.00],
            ['sku' => 'KDT-CMP-006', 'name' => 'Zephyr 32GB DDR5 RAM', 'category' => 'Components', 'supplier' => 'TechSource Distributors', 'brand' => 'Zephyr', 'cost_price' => 78.00, 'selling_price' => 129.00],
            ['sku' => 'KDT-CMP-007', 'name' => 'TitanCool CPU Air Cooler', 'category' => 'Components', 'supplier' => 'Titan IT Distribution', 'brand' => 'TitanCool', 'cost_price' => 19.00, 'selling_price' => 39.00],
            ['sku' => 'KDT-CMP-008', 'name' => 'TitanCool 240 Liquid Cooler', 'category' => 'Components', 'supplier' => 'Titan IT Distribution', 'brand' => 'TitanCool', 'cost_price' => 54.00, 'selling_price' => 99.00],
            ['sku' => 'KDT-CMP-009', 'name' => 'GridPower 650W PSU', 'category' => 'Components', 'supplier' => 'SignalByte Components', 'brand' => 'GridPower', 'cost_price' => 46.00, 'selling_price' => 79.00],
            ['sku' => 'KDT-CMP-010', 'name' => 'GridPower 750W PSU', 'category' => 'Components', 'supplier' => 'SignalByte Components', 'brand' => 'GridPower', 'cost_price' => 62.00, 'selling_price' => 109.00],
            ['sku' => 'KDT-CMP-011', 'name' => 'SignalFrame ATX Case', 'category' => 'Components', 'supplier' => 'SignalByte Components', 'brand' => 'SignalFrame', 'cost_price' => 36.00, 'selling_price' => 69.00],
            ['sku' => 'KDT-CMP-012', 'name' => 'SignalFrame Compact Case', 'category' => 'Components', 'supplier' => 'SignalByte Components', 'brand' => 'SignalFrame', 'cost_price' => 31.00, 'selling_price' => 59.00],

            // Storage
            ['sku' => 'KDT-STO-001', 'name' => 'FlashCore 1TB SSD', 'category' => 'Storage', 'supplier' => 'CoreLink Systems', 'brand' => 'FlashCore', 'cost_price' => 55.00, 'selling_price' => 89.00],
            ['sku' => 'KDT-STO-002', 'name' => 'FlashCore 2TB SSD', 'category' => 'Storage', 'supplier' => 'CoreLink Systems', 'brand' => 'FlashCore', 'cost_price' => 95.00, 'selling_price' => 149.00],
            ['sku' => 'KDT-STO-003', 'name' => 'DataVault 2TB HDD', 'category' => 'Storage', 'supplier' => 'Summit Digital Supply', 'brand' => 'DataVault', 'cost_price' => 48.00, 'selling_price' => 79.00],
            ['sku' => 'KDT-STO-004', 'name' => 'DataVault 4TB HDD', 'category' => 'Storage', 'supplier' => 'Summit Digital Supply', 'brand' => 'DataVault', 'cost_price' => 84.00, 'selling_price' => 129.00],
            ['sku' => 'KDT-STO-005', 'name' => 'RapidStore 500GB NVMe SSD', 'category' => 'Storage', 'supplier' => 'TechSource Distributors', 'brand' => 'RapidStore', 'cost_price' => 29.00, 'selling_price' => 49.00],
            ['sku' => 'KDT-STO-006', 'name' => 'RapidStore 1TB NVMe SSD', 'category' => 'Storage', 'supplier' => 'TechSource Distributors', 'brand' => 'RapidStore', 'cost_price' => 49.00, 'selling_price' => 79.00],
            ['sku' => 'KDT-STO-007', 'name' => 'VaultX 1TB SATA SSD', 'category' => 'Storage', 'supplier' => 'Metro Circuit Supply', 'brand' => 'VaultX', 'cost_price' => 44.00, 'selling_price' => 72.00],
            ['sku' => 'KDT-STO-008', 'name' => 'VaultX 2TB SATA SSD', 'category' => 'Storage', 'supplier' => 'Metro Circuit Supply', 'brand' => 'VaultX', 'cost_price' => 81.00, 'selling_price' => 129.00],
            ['sku' => 'KDT-STO-009', 'name' => 'PulseDrive External 1TB SSD', 'category' => 'Storage', 'supplier' => 'Pinnacle Systems Trade', 'brand' => 'PulseDrive', 'cost_price' => 63.00, 'selling_price' => 99.00],
            ['sku' => 'KDT-STO-010', 'name' => 'PulseDrive External 2TB SSD', 'category' => 'Storage', 'supplier' => 'Pinnacle Systems Trade', 'brand' => 'PulseDrive', 'cost_price' => 109.00, 'selling_price' => 169.00],
            ['sku' => 'KDT-STO-011', 'name' => 'HarborVault 8TB HDD', 'category' => 'Storage', 'supplier' => 'Harbor Tech Supply', 'brand' => 'HarborVault', 'cost_price' => 132.00, 'selling_price' => 199.00],
            ['sku' => 'KDT-STO-012', 'name' => 'HarborVault 6TB HDD', 'category' => 'Storage', 'supplier' => 'Harbor Tech Supply', 'brand' => 'HarborVault', 'cost_price' => 111.00, 'selling_price' => 169.00],

            // Networking
            ['sku' => 'KDT-NET-001', 'name' => 'LinkWave WiFi 6 Router', 'category' => 'Networking', 'supplier' => 'Nova Hardware Supply', 'brand' => 'LinkWave', 'cost_price' => 38.00, 'selling_price' => 69.00],
            ['sku' => 'KDT-NET-002', 'name' => 'LinkWave WiFi 6E Router', 'category' => 'Networking', 'supplier' => 'Nova Hardware Supply', 'brand' => 'LinkWave', 'cost_price' => 72.00, 'selling_price' => 129.00],
            ['sku' => 'KDT-NET-003', 'name' => 'NetBridge 8-Port Switch', 'category' => 'Networking', 'supplier' => 'Harbor Tech Supply', 'brand' => 'NetBridge', 'cost_price' => 21.00, 'selling_price' => 39.00],
            ['sku' => 'KDT-NET-004', 'name' => 'NetBridge 16-Port Switch', 'category' => 'Networking', 'supplier' => 'Harbor Tech Supply', 'brand' => 'NetBridge', 'cost_price' => 44.00, 'selling_price' => 79.00],
            ['sku' => 'KDT-NET-005', 'name' => 'SignalMesh Dual-Band Access Point', 'category' => 'Networking', 'supplier' => 'SignalByte Components', 'brand' => 'SignalMesh', 'cost_price' => 58.00, 'selling_price' => 109.00],
            ['sku' => 'KDT-NET-006', 'name' => 'SignalMesh Enterprise Access Point', 'category' => 'Networking', 'supplier' => 'SignalByte Components', 'brand' => 'SignalMesh', 'cost_price' => 112.00, 'selling_price' => 199.00],
            ['sku' => 'KDT-NET-007', 'name' => 'CoreLink Mesh Router', 'category' => 'Networking', 'supplier' => 'CoreLink Systems', 'brand' => 'CoreLink', 'cost_price' => 67.00, 'selling_price' => 119.00],
            ['sku' => 'KDT-NET-008', 'name' => 'CoreLink Mesh Node', 'category' => 'Networking', 'supplier' => 'CoreLink Systems', 'brand' => 'CoreLink', 'cost_price' => 41.00, 'selling_price' => 79.00],
            ['sku' => 'KDT-NET-009', 'name' => 'MetroWAN Business Router', 'category' => 'Networking', 'supplier' => 'Metro Circuit Supply', 'brand' => 'MetroWAN', 'cost_price' => 95.00, 'selling_price' => 169.00],
            ['sku' => 'KDT-NET-010', 'name' => 'MetroWAN VPN Router', 'category' => 'Networking', 'supplier' => 'Metro Circuit Supply', 'brand' => 'MetroWAN', 'cost_price' => 128.00, 'selling_price' => 229.00],
            ['sku' => 'KDT-NET-011', 'name' => 'PulseNet USB WiFi Adapter', 'category' => 'Networking', 'supplier' => 'Quantum Peripheral House', 'brand' => 'PulseNet', 'cost_price' => 9.00, 'selling_price' => 19.00],
            ['sku' => 'KDT-NET-012', 'name' => 'PulseNet PCIe WiFi Card', 'category' => 'Networking', 'supplier' => 'Quantum Peripheral House', 'brand' => 'PulseNet', 'cost_price' => 17.00, 'selling_price' => 34.00],

            // Peripherals
            ['sku' => 'KDT-PRP-001', 'name' => 'ClickLite Wireless Mouse', 'category' => 'Peripherals', 'supplier' => 'Axis Peripheral Partners', 'brand' => 'ClickLite', 'cost_price' => 9.00, 'selling_price' => 19.00],
            ['sku' => 'KDT-PRP-002', 'name' => 'ClickLite Silent Mouse', 'category' => 'Peripherals', 'supplier' => 'Axis Peripheral Partners', 'brand' => 'ClickLite', 'cost_price' => 11.00, 'selling_price' => 24.00],
            ['sku' => 'KDT-PRP-003', 'name' => 'TypeFlow Mechanical Keyboard', 'category' => 'Peripherals', 'supplier' => 'Axis Peripheral Partners', 'brand' => 'TypeFlow', 'cost_price' => 28.00, 'selling_price' => 59.00],
            ['sku' => 'KDT-PRP-004', 'name' => 'TypeFlow Compact Keyboard', 'category' => 'Peripherals', 'supplier' => 'Axis Peripheral Partners', 'brand' => 'TypeFlow', 'cost_price' => 22.00, 'selling_price' => 49.00],
            ['sku' => 'KDT-PRP-005', 'name' => 'ClearView 1080p Webcam', 'category' => 'Peripherals', 'supplier' => 'CoreLink Systems', 'brand' => 'ClearView', 'cost_price' => 24.00, 'selling_price' => 49.00],
            ['sku' => 'KDT-PRP-006', 'name' => 'ClearView 2K Webcam', 'category' => 'Peripherals', 'supplier' => 'CoreLink Systems', 'brand' => 'ClearView', 'cost_price' => 39.00, 'selling_price' => 79.00],
            ['sku' => 'KDT-PRP-007', 'name' => 'SoundArc USB Headset', 'category' => 'Peripherals', 'supplier' => 'Quantum Peripheral House', 'brand' => 'SoundArc', 'cost_price' => 18.00, 'selling_price' => 39.00],
            ['sku' => 'KDT-PRP-008', 'name' => 'SoundArc Wireless Headset', 'category' => 'Peripherals', 'supplier' => 'Quantum Peripheral House', 'brand' => 'SoundArc', 'cost_price' => 34.00, 'selling_price' => 69.00],
            ['sku' => 'KDT-PRP-009', 'name' => 'TrackPoint Wireless Combo', 'category' => 'Peripherals', 'supplier' => 'Pinnacle Systems Trade', 'brand' => 'TrackPoint', 'cost_price' => 26.00, 'selling_price' => 54.00],
            ['sku' => 'KDT-PRP-010', 'name' => 'TrackPoint Office Keyboard', 'category' => 'Peripherals', 'supplier' => 'Pinnacle Systems Trade', 'brand' => 'TrackPoint', 'cost_price' => 17.00, 'selling_price' => 34.00],
            ['sku' => 'KDT-PRP-011', 'name' => 'VoiceBeam Conference Mic', 'category' => 'Peripherals', 'supplier' => 'Summit Digital Supply', 'brand' => 'VoiceBeam', 'cost_price' => 29.00, 'selling_price' => 59.00],
            ['sku' => 'KDT-PRP-012', 'name' => 'VoiceBeam Streaming Mic', 'category' => 'Peripherals', 'supplier' => 'Summit Digital Supply', 'brand' => 'VoiceBeam', 'cost_price' => 41.00, 'selling_price' => 89.00],

            // Accessories
            ['sku' => 'KDT-ACC-001', 'name' => 'FlexArm Monitor Stand', 'category' => 'Accessories', 'supplier' => 'Summit Digital Supply', 'brand' => 'FlexArm', 'cost_price' => 18.00, 'selling_price' => 39.00],
            ['sku' => 'KDT-ACC-002', 'name' => 'FlexArm Dual Monitor Stand', 'category' => 'Accessories', 'supplier' => 'Summit Digital Supply', 'brand' => 'FlexArm', 'cost_price' => 34.00, 'selling_price' => 69.00],
            ['sku' => 'KDT-ACC-003', 'name' => 'PowerDock USB-C Hub', 'category' => 'Accessories', 'supplier' => 'TechSource Distributors', 'brand' => 'PowerDock', 'cost_price' => 14.00, 'selling_price' => 34.00],
            ['sku' => 'KDT-ACC-004', 'name' => 'PowerDock 9-in-1 Hub', 'category' => 'Accessories', 'supplier' => 'TechSource Distributors', 'brand' => 'PowerDock', 'cost_price' => 22.00, 'selling_price' => 49.00],
            ['sku' => 'KDT-ACC-005', 'name' => 'SurgeSafe Power Strip', 'category' => 'Accessories', 'supplier' => 'Harbor Tech Supply', 'brand' => 'SurgeSafe', 'cost_price' => 11.00, 'selling_price' => 24.00],
            ['sku' => 'KDT-ACC-006', 'name' => 'SurgeSafe UPS 650VA', 'category' => 'Accessories', 'supplier' => 'Harbor Tech Supply', 'brand' => 'SurgeSafe', 'cost_price' => 42.00, 'selling_price' => 79.00],
            ['sku' => 'KDT-ACC-007', 'name' => 'CablePro HDMI 2m', 'category' => 'Accessories', 'supplier' => 'Quantum Peripheral House', 'brand' => 'CablePro', 'cost_price' => 3.50, 'selling_price' => 9.00],
            ['sku' => 'KDT-ACC-008', 'name' => 'CablePro USB-C 1m', 'category' => 'Accessories', 'supplier' => 'Quantum Peripheral House', 'brand' => 'CablePro', 'cost_price' => 2.50, 'selling_price' => 8.00],
            ['sku' => 'KDT-ACC-009', 'name' => 'DeskRise Laptop Stand', 'category' => 'Accessories', 'supplier' => 'Axis Peripheral Partners', 'brand' => 'DeskRise', 'cost_price' => 12.00, 'selling_price' => 29.00],
            ['sku' => 'KDT-ACC-010', 'name' => 'DeskRise Aluminum Stand', 'category' => 'Accessories', 'supplier' => 'Axis Peripheral Partners', 'brand' => 'DeskRise', 'cost_price' => 16.00, 'selling_price' => 36.00],
            ['sku' => 'KDT-ACC-011', 'name' => 'AirCarry Laptop Sleeve 14"', 'category' => 'Accessories', 'supplier' => 'Nexa Device Wholesale', 'brand' => 'AirCarry', 'cost_price' => 8.00, 'selling_price' => 19.00],
            ['sku' => 'KDT-ACC-012', 'name' => 'AirCarry Laptop Sleeve 16"', 'category' => 'Accessories', 'supplier' => 'Nexa Device Wholesale', 'brand' => 'AirCarry', 'cost_price' => 9.50, 'selling_price' => 22.00],
        ];

        foreach ($products as $product) {
            Product::updateOrCreate(
                ['sku' => $product['sku']],
                [
                    'name' => $product['name'],
                    'category_id' => $categories[$product['category']],
                    'supplier_id' => $suppliers[$product['supplier']],
                    'brand' => $product['brand'],
                    'cost_price' => $product['cost_price'],
                    'selling_price' => $product['selling_price'],
                ]
            );
        }
    }
}