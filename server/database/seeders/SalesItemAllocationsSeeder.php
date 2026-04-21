<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SalesOrderItem;
use App\Models\SalesItemAllocation;
use App\Models\StockBatch;
use App\Models\StockMovement;
use App\Models\SerialNumber;

class SalesItemAllocationsSeeder extends Seeder
{
    public function run(): void
    {
        SalesOrderItem::with([
                'product:id,is_serialized',
                'salesOrder:id,ordered_at',
            ])
            ->select([
                'id',
                'sales_order_id',
                'product_id',
                'qty',
            ])
            ->chunkById(200, function ($items) {
                foreach ($items as $item) {
                    if (SalesItemAllocation::where('sales_order_item_id', $item->id)->exists()) {
                        continue;
                    }

                    $qtyNeeded = (int) $item->qty;

                    $batches = StockBatch::where('product_id', $item->product_id)
                        ->where('qty_remaining', '>', 0)
                        ->orderBy('received_at')
                        ->orderBy('id')
                        ->get([
                            'id',
                            'product_id',
                            'qty_remaining',
                            'unit_cost',
                            'received_at',
                        ]);

                    foreach ($batches as $batch) {
                        if ($qtyNeeded <= 0) {
                            break;
                        }

                        $allocate = min($qtyNeeded, (int) $batch->qty_remaining);

                        if ($allocate <= 0) {
                            continue;
                        }

                        SalesItemAllocation::create([
                            'sales_order_item_id' => $item->id,
                            'stock_batch_id' => $batch->id,
                            'qty_allocated' => $allocate,
                            'unit_cost' => $batch->unit_cost,
                        ]);

                        $batch->decrement('qty_remaining', $allocate);

                        StockMovement::create([
                            'product_id' => $item->product_id,
                            'stock_batch_id' => $batch->id,
                            'movement_type' => 'sale',
                            'reference_type' => 'sales_order_item',
                            'reference_id' => $item->id,
                            'qty_change' => -1 * $allocate,
                            'unit_cost' => $batch->unit_cost,
                            'notes' => 'Seeded sale allocation',
                            'moved_at' => $item->salesOrder?->ordered_at ?? now(),
                        ]);

                        if ($item->product?->is_serialized) {
                            $serials = SerialNumber::where('stock_batch_id', $batch->id)
                                ->where('product_id', $item->product_id)
                                ->where('status', 'available')
                                ->limit($allocate)
                                ->get(['id']);

                            foreach ($serials as $serial) {
                                $serial->update([
                                    'status' => 'sold',
                                ]);
                            }
                        }

                        $qtyNeeded -= $allocate;
                    }
                }
            });
    }
}