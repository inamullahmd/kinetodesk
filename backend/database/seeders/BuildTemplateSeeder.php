<?php

namespace Database\Seeders;

use App\Models\BuildTemplate;
use App\Models\BuildTemplateItem;
use App\Models\Product;
use Illuminate\Database\Seeder;

class BuildTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            'Entry Gaming PC',
            'Midrange Gaming PC',
            'Office Productivity PC',
            'Content Creator PC',
            'Engineering Workstation',
        ];

        $cpuProducts = Product::whereHas('category', fn($q) => $q->where('name', 'CPUs'))->pluck('id')->all();
        $gpuProducts = Product::whereHas('category', fn($q) => $q->where('name', 'GPUs'))->pluck('id')->all();
        $ramProducts = Product::whereHas('category', fn($q) => $q->where('name', 'RAM'))->pluck('id')->all();
        $storageProducts = Product::whereHas('category', fn($q) => $q->where('name', 'Storage'))->pluck('id')->all();
        $mbProducts = Product::whereHas('category', fn($q) => $q->where('name', 'Motherboards'))->pluck('id')->all();
        $psuProducts = Product::whereHas('category', fn($q) => $q->where('name', 'Power Supplies'))->pluck('id')->all();
        $caseProducts = Product::whereHas('category', fn($q) => $q->where('name', 'Cases'))->pluck('id')->all();
        $coolingProducts = Product::whereHas('category', fn($q) => $q->where('name', 'Cooling'))->pluck('id')->all();

        foreach ($templates as $name) {
            $template = BuildTemplate::updateOrCreate(
                ['name' => $name],
                [
                    'description' => "{$name} template used for custom-built customer systems.",
                    'is_active' => true,
                ]
            );

            BuildTemplateItem::where('build_template_id', $template->id)->delete();

            $items = [
                ['component_type' => 'CPU', 'product_id' => $cpuProducts[array_rand($cpuProducts)] ?? null],
                ['component_type' => 'GPU', 'product_id' => $gpuProducts[array_rand($gpuProducts)] ?? null],
                ['component_type' => 'Motherboard', 'product_id' => $mbProducts[array_rand($mbProducts)] ?? null],
                ['component_type' => 'RAM', 'product_id' => $ramProducts[array_rand($ramProducts)] ?? null],
                ['component_type' => 'Storage', 'product_id' => $storageProducts[array_rand($storageProducts)] ?? null],
                ['component_type' => 'PSU', 'product_id' => $psuProducts[array_rand($psuProducts)] ?? null],
                ['component_type' => 'Case', 'product_id' => $caseProducts[array_rand($caseProducts)] ?? null],
                ['component_type' => 'Cooling', 'product_id' => $coolingProducts[array_rand($coolingProducts)] ?? null],
            ];

            foreach ($items as $item) {
                if (!$item['product_id']) {
                    continue;
                }

                BuildTemplateItem::create([
                    'build_template_id' => $template->id,
                    'component_type' => $item['component_type'],
                    'product_id' => $item['product_id'],
                    'quantity' => 1,
                    'is_required' => true,
                ]);
            }
        }
    }
}