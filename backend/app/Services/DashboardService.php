<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\ReorderAlert;
use App\Models\ServiceTicket;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function getOverview(): array
    {
        $todayStart = now()->startOfDay();
        $todayEnd = now()->endOfDay();

        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        $yearStart = now()->startOfYear();
        $yearEnd = now()->endOfYear();

        $today = $this->salesSummary($todayStart, $todayEnd);
        $month = $this->salesSummary($monthStart, $monthEnd);
        $year = $this->salesSummary($yearStart, $yearEnd);

        return [
            'summary' => [
                'today' => $today,
                'month' => $month,
                'year' => $year,
            ],
            'inventory' => [
                'low_stock_count' => Product::query()
                    ->where('product_type', '!=', 'service')
                    ->whereColumn('current_stock', '<=', 'reorder_threshold')
                    ->count(),

                'reorder_alert_count' => ReorderAlert::query()
                    ->where('status', 'open')
                    ->count(),

                'top_low_stock_products' => Product::query()
                    ->with(['category:id,name', 'brand:id,name', 'preferredSupplier:id,name'])
                    ->where('product_type', '!=', 'service')
                    ->whereColumn('current_stock', '<=', 'reorder_threshold')
                    ->orderBy('current_stock')
                    ->orderByDesc('reorder_threshold')
                    ->limit(10)
                    ->get([
                        'id',
                        'sku',
                        'name',
                        'category_id',
                        'brand_id',
                        'preferred_supplier_id',
                        'current_stock',
                        'reserved_stock',
                        'reorder_threshold',
                    ]),
            ],
            'service' => [
                'open_tickets_count' => ServiceTicket::query()
                    ->whereNotIn('status', ['delivered', 'cancelled'])
                    ->count(),

                'recent_service_tickets' => ServiceTicket::query()
                    ->with([
                        'customer:id,customer_type,business_name,first_name,last_name',
                        'assignedEmployee:id,first_name,last_name',
                    ])
                    ->latest('received_at')
                    ->limit(10)
                    ->get(),
            ],
            'sales_breakdowns' => [
                'sales_by_channel' => $this->salesByChannel($monthStart, $monthEnd),
                'employee_vs_unattended' => $this->employeeVsUnattended($monthStart, $monthEnd),
            ],
            'recent_orders' => Order::query()
                ->with([
                    'customer:id,customer_type,business_name,first_name,last_name',
                    'employee:id,first_name,last_name',
                ])
                ->latest('created_at')
                ->limit(10)
                ->get(),
        ];
    }

    private function salesSummary(Carbon $start, Carbon $end): array
    {
        $baseQuery = Order::query()
            ->whereBetween('created_at', [$start, $end])
            ->whereIn('status', ['completed', 'paid', 'partially_paid', 'confirmed']);

        $revenue = (float) (clone $baseQuery)->sum('total_amount');

        $cost = (float) DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereBetween('orders.created_at', [$start, $end])
            ->whereIn('orders.status', ['completed', 'paid', 'partially_paid', 'confirmed'])
            ->selectRaw('COALESCE(SUM(order_items.quantity * order_items.unit_cost_at_sale), 0) as total_cost')
            ->value('total_cost');

        $profit = round($revenue - $cost, 2);
        $margin = $revenue > 0 ? round(($profit / $revenue) * 100, 2) : 0.00;

        return [
            'orders_count' => (clone $baseQuery)->count(),
            'revenue' => round($revenue, 2),
            'cost' => round($cost, 2),
            'profit' => $profit,
            'margin_percent' => $margin,
        ];
    }

    private function salesByChannel(Carbon $start, Carbon $end): Collection
    {
        return Order::query()
            ->selectRaw('channel, COUNT(*) as orders_count, COALESCE(SUM(total_amount), 0) as revenue')
            ->whereBetween('created_at', [$start, $end])
            ->whereIn('status', ['completed', 'paid', 'partially_paid', 'confirmed'])
            ->groupBy('channel')
            ->orderByDesc('revenue')
            ->get()
            ->map(function ($row) {
                return [
                    'channel' => $row->channel,
                    'orders_count' => (int) $row->orders_count,
                    'revenue' => round((float) $row->revenue, 2),
                ];
            });
    }

    private function employeeVsUnattended(Carbon $start, Carbon $end): Collection
    {
        $employeeRevenue = (float) Order::query()
            ->whereBetween('created_at', [$start, $end])
            ->whereIn('status', ['completed', 'paid', 'partially_paid', 'confirmed'])
            ->whereNotNull('employee_id')
            ->sum('total_amount');

        $unattendedRevenue = (float) Order::query()
            ->whereBetween('created_at', [$start, $end])
            ->whereIn('status', ['completed', 'paid', 'partially_paid', 'confirmed'])
            ->whereNull('employee_id')
            ->sum('total_amount');

        $employeeOrders = Order::query()
            ->whereBetween('created_at', [$start, $end])
            ->whereIn('status', ['completed', 'paid', 'partially_paid', 'confirmed'])
            ->whereNotNull('employee_id')
            ->count();

        $unattendedOrders = Order::query()
            ->whereBetween('created_at', [$start, $end])
            ->whereIn('status', ['completed', 'paid', 'partially_paid', 'confirmed'])
            ->whereNull('employee_id')
            ->count();

        return collect([
            [
                'segment' => 'Employee Assisted',
                'orders_count' => $employeeOrders,
                'revenue' => round($employeeRevenue, 2),
            ],
            [
                'segment' => 'Online/Unattended',
                'orders_count' => $unattendedOrders,
                'revenue' => round($unattendedRevenue, 2),
            ],
        ]);
    }
}