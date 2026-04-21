<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CommissionPayout;
use App\Models\Employee;
use App\Models\SalesOrderItem;
use Carbon\Carbon;

class CommissionPayoutsSeeder extends Seeder
{
    public function run(): void
    {
        $employees = Employee::whereIn('role', ['owner', 'manager', 'sales_representative', 'cashier'])->get();

        foreach ($employees as $employee) {
            for ($monthOffset = 35; $monthOffset >= 0; $monthOffset--) {
                $start = Carbon::now()->subMonths($monthOffset)->startOfMonth();
                $end = Carbon::now()->subMonths($monthOffset)->endOfMonth();

                $total = (float) SalesOrderItem::where('employee_id', $employee->id)
                    ->whereHas('salesOrder', function ($q) use ($start, $end) {
                        $q->whereBetween('ordered_at', [$start, $end])
                          ->whereIn('status', ['delivered']);
                    })
                    ->sum('commission_total');

                if ($total <= 0) {
                    continue;
                }

                $isPaid = $monthOffset > 0;

                CommissionPayout::updateOrCreate(
                    [
                        'employee_id' => $employee->id,
                        'period_start' => $start->toDateString(),
                        'period_end' => $end->toDateString(),
                    ],
                    [
                        'total_commission' => round($total, 2),
                        'status' => $isPaid ? 'paid' : 'pending',
                        'paid_at' => $isPaid ? $end->copy()->subDays(rand(0, 5)) : null,
                        'notes' => 'Monthly commission payout period',
                    ]
                );
            }
        }
    }
}
