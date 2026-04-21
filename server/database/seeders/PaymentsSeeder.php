<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Payment;
use App\Models\SalesOrder;

class PaymentsSeeder extends Seeder
{
    public function run(): void
    {
        $orders = SalesOrder::all();

        foreach ($orders as $order) {
            if ($order->payment_status === 'unpaid') {
                continue;
            }

            if ($order->payments()->exists()) {
                continue;
            }

            Payment::create([
                'sales_order_id' => $order->id,
                'payment_method' => collect(['cash', 'card', 'bank_transfer', 'mobile_money'])->random(),
                'amount' => $order->grand_total,
                'status' => $order->payment_status === 'refunded' ? 'refunded' : 'paid',
                'transaction_reference' => 'TXN-' . strtoupper(substr(md5($order->so_number . random_int(1000, 9999)), 0, 12)),
                'paid_at' => $order->completed_at ?? $order->ordered_at ?? now(),
                'notes' => $order->payment_status === 'refunded'
                    ? 'Refund payment record'
                    : 'Seeded payment record',
            ]);
        }
    }
}
