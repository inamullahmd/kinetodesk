<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ReturnRecord;
use App\Models\ReturnItem;
use App\Models\StockBatch;
use App\Models\StockMovement;
use App\Models\SerialNumber;

class ReturnItemsSeeder extends Seeder
{
    public function run(): void
    {
        $returns = ReturnRecord::with(['salesOrder.items.product'])->get();

        foreach ($returns as $returnRecord) {
            if ($returnRecord->items()->exists()) {
                continue;
            }

            $items = $returnRecord->salesOrder?->items;

            if (!$items || $items->isEmpty()) {
                continue;
            }

            $selected = $items->random(min(rand(1, 2), $items->count()));
            $hasRestockedItem = false;

            foreach ($selected as $item) {
                $qty = rand(1, $item->qty);
                $refundAmount = round($qty * $item->final_unit_price, 2);

                // damaged should be less common, but only damaged gets scrapped
                $condition = collect([
                    'sealed',
                    'opened',
                    'used',
                    'damaged',
                ])->random();

                $action = $condition === 'damaged' ? 'scrap' : 'restock';

                $returnItem = ReturnItem::create([
                    'return_id' => $returnRecord->id,
                    'sales_order_item_id' => $item->id,
                    'qty' => $qty,
                    'item_condition' => $condition,
                    'action' => $action,
                    'refund_amount' => $refundAmount,
                    'notes' => 'Seeded return item',
                ]);

                if ($action === 'restock') {
                    $hasRestockedItem = true;

                    $batch = StockBatch::where('product_id', $item->product_id)
                        ->orderByDesc('received_at')
                        ->first();

                    if ($batch) {
                        $batch->increment('qty_remaining', $qty);

                        StockMovement::create([
                            'product_id' => $item->product_id,
                            'stock_batch_id' => $batch->id,
                            'movement_type' => 'return_from_customer',
                            'reference_type' => 'return_item',
                            'reference_id' => $returnItem->id,
                            'qty_change' => $qty,
                            'unit_cost' => $batch->unit_cost,
                            'notes' => 'Customer return restocked',
                            'moved_at' => now(),
                        ]);

                        if ($item->product?->is_serialized) {
                            $serials = SerialNumber::where('product_id', $item->product_id)
                                ->where('status', 'sold')
                                ->take($qty)
                                ->get();

                            foreach ($serials as $serial) {
                                $serial->update([
                                    'status' => 'returned',
                                ]);
                            }
                        }
                    }
                }
            }

            $returnRecord->update([
                'refund_amount' => round((float) $returnRecord->items()->sum('refund_amount'), 2),
                'restock' => $hasRestockedItem,
            ]);
        }
    }
}