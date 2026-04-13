<?php

namespace Database\Seeders;

use App\Models\Inventory;
use App\Models\Product;
use Illuminate\Database\Seeder;

class InventorySeeder extends Seeder
{
    public function run(): void
    {
        $products = Product::with('category')->get();

        foreach ($products as $product) {
            [$stockRange, $reorderRange] = $this->inventoryRuleForCategory($product->category->name);

            $openingStock = fake()->numberBetween($stockRange[0], $stockRange[1]);
            $reorderLevel = fake()->numberBetween($reorderRange[0], $reorderRange[1]);

            Inventory::updateOrCreate(
                ['product_id' => $product->id],
                [
                    'opening_stock' => $openingStock,
                    'stock_on_hand' => $openingStock,
                    'reorder_level' => $reorderLevel,
                ]
            );
        }
    }

    private function inventoryRuleForCategory(string $category): array
    {
        return match ($category) {
            'Laptops' => [[6, 24], [3, 6]],
            'Desktops' => [[4, 18], [2, 5]],
            'Monitors' => [[10, 40], [4, 10]],
            'Components' => [[20, 90], [8, 20]],
            'Storage' => [[18, 85], [8, 18]],
            'Networking' => [[12, 50], [5, 12]],
            'Peripherals' => [[30, 140], [12, 30]],
            'Accessories' => [[35, 160], [15, 35]],
            default => [[10, 30], [5, 10]],
        };
    }
}