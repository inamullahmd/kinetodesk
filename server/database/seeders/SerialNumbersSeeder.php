<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\StockBatch;
use App\Models\SerialNumber;

class SerialNumbersSeeder extends Seeder
{
    public function run(): void
    {
        $serializedBatches = StockBatch::with('product')
            ->whereHas('product', fn ($q) => $q->where('is_serialized', true))
            ->get();

        foreach ($serializedBatches as $batch) {
            $prefix = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $batch->product->model_number), 0, 6));

            for ($i = 1; $i <= $batch->qty_received; $i++) {
                $serial = $prefix
                    . str_pad((string) $batch->id, 5, '0', STR_PAD_LEFT)
                    . str_pad((string) $i, 4, '0', STR_PAD_LEFT);

                SerialNumber::updateOrCreate(
                    ['serial_number' => $serial],
                    [
                        'product_id' => $batch->product_id,
                        'stock_batch_id' => $batch->id,
                        'status' => 'available',
                    ]
                );
            }
        }
    }
}
