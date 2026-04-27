<?php

namespace Database\Seeders;

use App\Models\CommissionPayout;
use App\Models\Employee;
use App\Models\SalesOrderItem;
use Carbon\Carbon;
use Database\Seeders\Concerns\SeedsTimelineWindow;
use Illuminate\Database\Seeder;

class CommissionPayoutsSeeder extends Seeder
{
    use SeedsTimelineWindow;

    public function run(): void
    {
        $employees = Employee::whereIn('role', ['owner', 'manager', 'sales_representative', 'cashier'])->get();
        $asOfMonth = $this->seedWindowEnd()->copy()->startOfMonth();

        foreach ($employees as $employee) {
            for ($monthOffset = 38; $monthOffset >= 0; $monthOffset--) {
                $start = $asOfMonth->copy()->subMonths($monthOffset)->startOfMonth();
                $end = $asOfMonth->copy()->subMonths($monthOffset)->endOfMonth();

                $total = (float) SalesOrderItem::where('employee_id', $employee->id)
                    ->whereHas('salesOrder', function ($q) use ($start, $end) {
                        $q->whereBetween('ordered_at', [$start, $end])
                          ->where('status', 'delivered');
                    })
                    ->sum('commission_total');

                if ($total <= 0) {
                    continue;
                }

                $isPaid = $end->lt($this->seedWindowEnd()->copy()->startOfMonth());
                $paidAt = $isPaid
                    ? $this->clampToSeedWindow($end->copy()->subDays(random_int(0, 5)))
                    : null;

                CommissionPayout::updateOrCreate(
                    [
                        'employee_id' => $employee->id,
                        'period_start' => $start->toDateString(),
                        'period_end' => $end->toDateString(),
                    ],
                    [
                        'total_commission' => round($total, 2),
                        'status' => $isPaid ? 'paid' : 'pending',
                        'paid_at' => $paidAt,
                        'notes' => 'Monthly commission payout period',
                    ]
                );
            }
        }
    }
}
