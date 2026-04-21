<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\SupplierProduct;
use Illuminate\Support\Str;

class SupplierProductsSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = Supplier::all()->keyBy('name');

        Product::with(['brand', 'category'])->chunk(100, function ($products) use ($suppliers) {
            foreach ($products as $product) {
                $this->attachSuppliersToProduct($product, $suppliers);
            }
        });
    }

    private function attachSuppliersToProduct(Product $product, $suppliers): void
    {
        $supplierNames = $this->supplierCandidatesForProduct(
            $product->brand?->name,
            $product->category?->name,
            $product->title
        );

        $supplierNames = array_values(array_unique(array_filter($supplierNames)));

        if (count($supplierNames) === 0) {
            return;
        }

        $preferredIndex = 0;

        foreach ($supplierNames as $index => $supplierName) {
            $supplier = $suppliers->get($supplierName);

            if (!$supplier) {
                continue;
            }

            $cost = $this->estimateCostFromProduct($product, $index === $preferredIndex);
            $currency = $supplier->country === 'USA' ? 'USD' : 'USD';

            SupplierProduct::updateOrCreate(
                [
                    'supplier_id' => $supplier->id,
                    'product_id' => $product->id,
                ],
                [
                    'supplier_sku' => $this->supplierSku($supplier->name, $product->model_number, $product->internal_sku),
                    'preferred_supplier' => $index === $preferredIndex,
                    'min_order_qty' => $this->minOrderQtyForCategory($product->category?->name),
                    'lead_time_days' => $this->leadTimeForSupplier($supplier->country, $index === $preferredIndex),
                    'last_cost' => $cost,
                    'currency' => $currency,
                    'is_active' => true,
                ]
            );
        }
    }

    private function supplierCandidatesForProduct(?string $brand, ?string $category, string $title): array
    {
        $distributors = [
            'Ingram Micro',
            'TD SYNNEX',
            'D&H Distributing',
            'ScanSource',
            'ASI Corp',
            'Ma Labs',
            'Petra Industries',
            'Arrow Electronics',
            'Westcon-Comstor',
            'Exertis',
            'Climb Channel Solutions',
            'Insight Enterprises',
            'CDW',
            'Connection',
            'Zones',
            'SHI International',
        ];

        $networking = [
            'Ingram Micro',
            'TD SYNNEX',
            'Westcon-Comstor',
            'ASI Corp',
            'Climb Channel Solutions',
            'CDW',
            'Insight Enterprises',
        ];

        $enterprise = [
            'Ingram Micro',
            'TD SYNNEX',
            'D&H Distributing',
            'ASI Corp',
            'Ma Labs',
            'Insight Enterprises',
            'CDW',
            'SHI International',
        ];

        $componentHeavy = [
            'ASI Corp',
            'Ma Labs',
            'D&H Distributing',
            'Ingram Micro',
            'TD SYNNEX',
            'Arrow Electronics',
        ];

        $softwareHeavy = [
            'Ingram Micro',
            'TD SYNNEX',
            'Climb Channel Solutions',
            'Insight Enterprises',
            'CDW',
            'SHI International',
            'Zones',
        ];

        $brandPreferred = match ($brand) {
            'Apple' => ['Ingram Micro', 'TD SYNNEX', 'CDW'],
            'Dell' => ['Ingram Micro', 'TD SYNNEX', 'Insight Enterprises'],
            'HP' => ['Ingram Micro', 'TD SYNNEX', 'D&H Distributing'],
            'Lenovo' => ['Ingram Micro', 'TD SYNNEX', 'D&H Distributing'],
            'ASUS' => ['ASI Corp', 'Ma Labs', 'Ingram Micro'],
            'Acer' => ['Ingram Micro', 'TD SYNNEX', 'D&H Distributing'],
            'MSI' => ['ASI Corp', 'Ma Labs', 'Ingram Micro'],
            'Samsung' => ['Ingram Micro', 'TD SYNNEX', 'D&H Distributing'],
            'Microsoft' => ['Ingram Micro', 'TD SYNNEX', 'Climb Channel Solutions'],
            'Intel' => ['Arrow Electronics', 'Ingram Micro', 'TD SYNNEX'],
            'AMD' => ['Arrow Electronics', 'Ingram Micro', 'TD SYNNEX'],
            'NVIDIA' => ['Arrow Electronics', 'Ingram Micro', 'TD SYNNEX'],
            'Corsair' => ['ASI Corp', 'Ma Labs', 'Ingram Micro'],
            'G.Skill' => ['ASI Corp', 'Ma Labs', 'D&H Distributing'],
            'Kingston' => ['Ingram Micro', 'TD SYNNEX', 'D&H Distributing'],
            'Crucial' => ['Ingram Micro', 'TD SYNNEX', 'D&H Distributing'],
            'Western Digital' => ['Ingram Micro', 'TD SYNNEX', 'D&H Distributing'],
            'Seagate' => ['Ingram Micro', 'TD SYNNEX', 'D&H Distributing'],
            'SanDisk' => ['Ingram Micro', 'TD SYNNEX', 'D&H Distributing'],
            'Noctua' => ['ASI Corp', 'Ma Labs', 'D&H Distributing'],
            'NZXT' => ['ASI Corp', 'Ma Labs', 'D&H Distributing'],
            'Cooler Master' => ['ASI Corp', 'Ma Labs', 'Ingram Micro'],
            'be quiet!' => ['ASI Corp', 'Ma Labs', 'D&H Distributing'],
            'EVGA' => ['ASI Corp', 'Ma Labs', 'D&H Distributing'],
            'Thermaltake' => ['ASI Corp', 'Ma Labs', 'D&H Distributing'],
            'Fractal Design' => ['ASI Corp', 'Ma Labs', 'D&H Distributing'],
            'Lian Li' => ['ASI Corp', 'Ma Labs', 'D&H Distributing'],
            'Logitech' => ['Ingram Micro', 'TD SYNNEX', 'Petra Industries'],
            'Razer' => ['Ingram Micro', 'TD SYNNEX', 'Petra Industries'],
            'SteelSeries' => ['Ingram Micro', 'TD SYNNEX', 'Petra Industries'],
            'HyperX' => ['Ingram Micro', 'TD SYNNEX', 'Petra Industries'],
            'TP-Link' => ['Ingram Micro', 'TD SYNNEX', 'Westcon-Comstor'],
            'Netgear' => ['Ingram Micro', 'TD SYNNEX', 'Westcon-Comstor'],
            'Ubiquiti' => ['Westcon-Comstor', 'Ingram Micro', 'TD SYNNEX'],
            'Cisco' => ['Westcon-Comstor', 'Ingram Micro', 'TD SYNNEX'],
            'Linksys' => ['Ingram Micro', 'TD SYNNEX', 'Westcon-Comstor'],
            'Eero' => ['Ingram Micro', 'TD SYNNEX', 'Westcon-Comstor'],
            'Google' => ['Ingram Micro', 'TD SYNNEX', 'Westcon-Comstor'],
            'Anker' => ['Petra Industries', 'Ingram Micro', 'TD SYNNEX'],
            'Belkin' => ['Petra Industries', 'Ingram Micro', 'TD SYNNEX'],
            'Cable Matters' => ['Petra Industries', 'Ingram Micro', 'TD SYNNEX'],
            'StarTech' => ['Ingram Micro', 'TD SYNNEX', 'D&H Distributing'],
            'UGREEN' => ['Petra Industries', 'Ingram Micro', 'TD SYNNEX'],
            'Adobe' => ['Climb Channel Solutions', 'Ingram Micro', 'TD SYNNEX'],
            'Intuit' => ['Climb Channel Solutions', 'Ingram Micro', 'TD SYNNEX'],
            'Norton' => ['Ingram Micro', 'TD SYNNEX', 'Climb Channel Solutions'],
            'McAfee' => ['Ingram Micro', 'TD SYNNEX', 'Climb Channel Solutions'],
            'Bitdefender' => ['Ingram Micro', 'TD SYNNEX', 'Climb Channel Solutions'],
            'ESET' => ['Ingram Micro', 'TD SYNNEX', 'Climb Channel Solutions'],
            default => [],
        };

        $categoryBased = match ($category) {
            'Laptops', 'Desktop PCs', 'Tablets', 'Handhelds', 'Monitors' => $enterprise,
            'Processors (CPU)', 'Graphics Cards (GPU)', 'Motherboards', 'Memory (RAM)', 'Power Supplies (PSU)', 'Cases & Chassis', 'Internal SSDs', 'Hard Drives (HDD)', 'CPU Cooling', 'Case Fans', 'Thermal Solutions', 'Tools & Equipment' => $componentHeavy,
            'Wireless Networking', 'Wired Networking' => $networking,
            'Operating Systems', 'Productivity Software', 'Security Software' => $softwareHeavy,
            default => $distributors,
        };

        $extra = [];

        if (Str::contains($title, ['Gaming', 'ROG', 'Legion', 'OMEN', 'Predator', 'Alienware'])) {
            $extra = ['ASI Corp', 'Ma Labs', 'D&H Distributing'];
        }

        if (Str::contains($title, ['Business', 'ThinkPad', 'Latitude', 'EliteBook', 'Surface', 'OptiPlex', 'ThinkCentre'])) {
            $extra = ['Insight Enterprises', 'CDW', 'SHI International'];
        }

        return array_merge($brandPreferred, $categoryBased, $extra);
    }

    private function estimateCostFromProduct(Product $product, bool $preferred): float
    {
        $price = $this->extractApproxRetailPrice($product->description);

        if (!$price) {
            $price = 100;
        }

        $baseMultiplier = match (true) {
            $price >= 2000 => 0.70,
            $price >= 1000 => 0.68,
            $price >= 500 => 0.66,
            $price >= 200 => 0.64,
            default => 0.60,
        };

        $cost = $price * $baseMultiplier;

        if (!$preferred) {
            $cost *= 1.03;
        }

        return round($cost, 2);
    }

    private function extractApproxRetailPrice(?string $description): ?float
    {
        if (!$description) {
            return null;
        }

        if (preg_match('/Retail price approx:\\s*\\$([0-9,]+(?:\\.[0-9]{1,2})?)/i', $description, $matches)) {
            return (float) str_replace(',', '', $matches[1]);
        }

        return null;
    }

    private function supplierSku(string $supplierName, string $modelNumber, string $internalSku): string
    {
        $supplierCode = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $supplierName), 0, 4));
        $modelCode = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $modelNumber), 0, 8));
        $skuCode = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $internalSku), -6));

        return $supplierCode . '-' . $modelCode . '-' . $skuCode;
    }

    private function minOrderQtyForCategory(?string $category): int
    {
        return match ($category) {
            'Cables & Adapters', 'Memory Cards', 'Thermal Solutions' => 10,
            'Keyboards', 'Mice', 'Audio', 'Case Fans' => 5,
            'Operating Systems', 'Productivity Software', 'Security Software' => 1,
            default => 1,
        };
    }

    private function leadTimeForSupplier(?string $country, bool $preferred): int
    {
        $base = match ($country) {
            'USA' => 5,
            'Canada' => 7,
            'United Kingdom' => 10,
            'Germany', 'France', 'Italy', 'Switzerland', 'Poland', 'Sweden', 'Norway', 'Ireland', 'Cyprus', 'Latvia' => 12,
            'India', 'Singapore', 'Hong Kong' => 14,
            'Australia' => 16,
            'South Africa' => 18,
            default => 12,
        };

        if (!$preferred) {
            $base += 3;
        }

        return $base;
    }
}