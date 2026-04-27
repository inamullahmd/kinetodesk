<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ReturnRecord;
use App\Models\SalesOrder;

class ReturnsSeeder extends Seeder
{
    public function run(): void
    {
        $orders = SalesOrder::whereIn('status', ['returned', 'refunded'])->get();

        $counter = 1;

        foreach ($orders as $order) {
            if (ReturnRecord::where('sales_order_id', $order->id)->exists()) {
                continue;
            }

            ReturnRecord::create([
                'sales_order_id' => $order->id,
                'customer_id' => $order->customer_id,
                'return_number' => 'RET-' . str_pad((string) $counter, 7, '0', STR_PAD_LEFT),
                'status' => $order->status === 'refunded' ? 'refunded' : 'received',
                'reason' => collect([
                    'Customer changed mind',
                    'Damaged on arrival',
                    'Incorrect item delivered',
                    'Defective product',
                ])->random(),
                'refund_amount' => $order->grand_total,
                'restock' => false, // will be recalculated in ReturnItemsSeeder
            ]);

            $counter++;
        }
    }
}