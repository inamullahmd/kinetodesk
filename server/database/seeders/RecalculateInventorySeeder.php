<?php

namespace Database\Seeders;

use App\Models\Inventory;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use Illuminate\Database\Seeder;

class RecalculateInventorySeeder extends Seeder
{
    public function run(): void
    {
        $products = Product::all();

        foreach ($products as $product) {
            $inventory = Inventory::where('product_id', $product->id)->first();

            if (! $inventory) {
                continue;
            }

            $receivedPurchases = PurchaseOrder::where('product_id', $product->id)
                ->where('status', 'received')
                ->sum('quantity');

            $completedSales = SalesOrder::where('product_id', $product->id)
                ->where('status', 'completed')
                ->sum('quantity');

            $currentStock = $inventory->opening_stock + $receivedPurchases - $completedSales;

            $inventory->update([
                'stock_on_hand' => max(0, $currentStock),
            ]);
        }
    }
}