<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PurchaseOrder;
use App\Models\StockBatch;
use Carbon\Carbon;

class StockBatchesSeeder extends Seeder
{
    public function run(): void
    {
        $fulfilledOrders = PurchaseOrder::with('items')
            ->where('status', 'fulfilled')
            ->get();

        foreach ($fulfilledOrders as $purchaseOrder) {
            foreach ($purchaseOrder->items as $item) {
                StockBatch::updateOrCreate(
                    ['purchase_order_item_id' => $item->id],
                    [
                        'product_id' => $item->product_id,
                        'purchase_order_item_id' => $item->id,
                        'batch_code' => 'BATCH-' . str_pad((string) $item->id, 9, '0', STR_PAD_LEFT),
                        'qty_received' => $item->ordered_qty,
                        'qty_remaining' => $item->ordered_qty,
                        'unit_cost' => $item->unit_cost,
                        'received_at' => $purchaseOrder->received_at
                            ? Carbon::parse($purchaseOrder->received_at)
                            : Carbon::parse($purchaseOrder->expected_at)->addDays(rand(0, 3)),
                    ]
                );
            }
        }
    }
}
