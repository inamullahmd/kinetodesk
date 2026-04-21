<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\StockBatch;
use App\Models\StockMovement;

class StockMovementsSeeder extends Seeder
{
    public function run(): void
    {
        $batches = StockBatch::all();

        foreach ($batches as $batch) {
            StockMovement::updateOrCreate(
                [
                    'product_id' => $batch->product_id,
                    'stock_batch_id' => $batch->id,
                    'movement_type' => 'purchase_receive',
                    'reference_type' => 'purchase_order_item',
                    'reference_id' => $batch->purchase_order_item_id,
                ],
                [
                    'qty_change' => $batch->qty_received,
                    'unit_cost' => $batch->unit_cost,
                    'notes' => 'Initial stock receipt from purchase order',
                    'moved_at' => $batch->received_at,
                ]
            );
        }
    }
}
