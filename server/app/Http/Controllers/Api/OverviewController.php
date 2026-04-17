<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OverviewController extends Controller
{
    private function resolveDateRange(Request $request, int $defaultMonths = 6): array
    {
        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->query('start_date'))->startOfDay()
            : now()->subMonths($defaultMonths)->startOfDay();

        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->query('end_date'))->endOfDay()
            : now()->endOfDay();

        return [$startDate, $endDate];
    }

    private function buildKpis(Carbon $startDate, Carbon $endDate): array
    {
        $salesQuery = SalesOrder::where('status', 'completed')
            ->whereBetween('sold_at', [$startDate, $endDate]);

        $purchaseQuery = PurchaseOrder::where('status', 'received')
            ->whereBetween('purchased_at', [$startDate, $endDate]);

        $totalRevenue = (clone $salesQuery)->sum('total_amount');
        $totalOrders = (clone $salesQuery)->count();
        $unitsSold = (clone $salesQuery)->sum('quantity');

        $averageOrderValue = $totalOrders > 0
            ? round($totalRevenue / $totalOrders, 2)
            : 0;

        $purchaseSpend = (clone $purchaseQuery)->sum('total_cost');

        $inventoryValue = Inventory::join('products', 'inventory.product_id', '=', 'products.id')
            ->select(DB::raw('SUM(inventory.stock_on_hand * products.cost_price) as total'))
            ->value('total');

        $lowStockCount = Inventory::whereColumn('stock_on_hand', '<=', 'reorder_level')->count();

        return [
            'total_revenue' => round($totalRevenue, 2),
            'total_orders' => (int) $totalOrders,
            'average_order_value' => $averageOrderValue,
            'units_sold' => (int) $unitsSold,
            'inventory_value' => round($inventoryValue ?? 0, 2),
            'low_stock_count' => (int) $lowStockCount,
            'purchase_spend' => round($purchaseSpend, 2),
        ];
    }

    private function buildRevenueTrend(Carbon $startDate, Carbon $endDate)
    {
        return SalesOrder::selectRaw('DATE(sold_at) as date, SUM(total_amount) as revenue')
            ->where('status', 'completed')
            ->whereBetween('sold_at', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($row) {
                return [
                    'date' => $row->date,
                    'revenue' => round((float) $row->revenue, 2),
                ];
            })
            ->values();
    }

    private function buildOrderStatus(Carbon $startDate, Carbon $endDate)
    {
        return SalesOrder::selectRaw('LOWER(status) as name, COUNT(*) as value')
            ->whereBetween('sold_at', [$startDate, $endDate])
            ->groupBy('name')
            ->orderBy('value', 'desc')
            ->get()
            ->map(function ($row) {
                return [
                    'name' => $row->name,
                    'value' => (int) $row->value,
                ];
            })
            ->values();
    }

    private function buildTopProducts(Carbon $startDate, Carbon $endDate)
    {
        return SalesOrder::join('products', 'sales_orders.product_id', '=', 'products.id')
            ->selectRaw('products.name as name, SUM(sales_orders.total_amount) as revenue')
            ->where('sales_orders.status', 'completed')
            ->whereBetween('sales_orders.sold_at', [$startDate, $endDate])
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get()
            ->map(function ($row) {
                return [
                    'name' => $row->name,
                    'revenue' => round((float) $row->revenue, 2),
                ];
            })
            ->values();
    }

    private function buildSalesByCategory(Carbon $startDate, Carbon $endDate)
    {
        return SalesOrder::join('products', 'sales_orders.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->selectRaw('categories.name as name, SUM(sales_orders.total_amount) as value')
            ->where('sales_orders.status', 'completed')
            ->whereBetween('sales_orders.sold_at', [$startDate, $endDate])
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('value')
            ->limit(6)
            ->get()
            ->map(function ($row) {
                return [
                    'name' => $row->name,
                    'value' => round((float) $row->value, 2),
                ];
            })
            ->values();
    }

    private function buildSalesByRegion(Carbon $startDate, Carbon $endDate)
    {
        $rows = SalesOrder::join('customers', 'sales_orders.customer_id', '=', 'customers.id')
            ->selectRaw('customers.city as city, customers.state as state, SUM(sales_orders.total_amount) as value')
            ->where('sales_orders.status', 'completed')
            ->whereBetween('sales_orders.sold_at', [$startDate, $endDate])
            ->groupBy('customers.city', 'customers.state')
            ->get();

        $buckets = [
            'Norman' => 0,
            'Rest of Oklahoma' => 0,
            'Out of State' => 0,
        ];

        foreach ($rows as $row) {
            $city = strtolower(trim((string) ($row->city ?? '')));
            $state = strtolower(trim((string) ($row->state ?? '')));
            $value = round((float) $row->value, 2);

            if ($city === 'norman' && $state === 'oklahoma') {
                $buckets['Norman'] += $value;
            } elseif ($state === 'oklahoma') {
                $buckets['Rest of Oklahoma'] += $value;
            } else {
                $buckets['Out of State'] += $value;
            }
        }

        return collect($buckets)
            ->map(function ($value, $name) {
                return [
                    'name' => $name,
                    'value' => round((float) $value, 2),
                ];
            })
            ->sortByDesc('value')
            ->values();
    }

    private function buildSalesByCustomerType(Carbon $startDate, Carbon $endDate)
    {
        return SalesOrder::join('customers', 'sales_orders.customer_id', '=', 'customers.id')
            ->selectRaw('LOWER(customers.customer_type) as name, SUM(sales_orders.total_amount) as value')
            ->where('sales_orders.status', 'completed')
            ->whereBetween('sales_orders.sold_at', [$startDate, $endDate])
            ->groupBy('customers.customer_type')
            ->orderByDesc('value')
            ->get()
            ->map(function ($row) {
                return [
                    'name' => $row->name,
                    'value' => round((float) $row->value, 2),
                ];
            })
            ->values();
    }

    public function overview(Request $request): JsonResponse
    {
        [$startDate, $endDate] = $this->resolveDateRange($request, 6);

        return response()->json([
            'kpis' => $this->buildKpis($startDate, $endDate),
            'revenue_trend' => $this->buildRevenueTrend($startDate, $endDate),
            'order_status' => $this->buildOrderStatus($startDate, $endDate),
            'top_products' => $this->buildTopProducts($startDate, $endDate),
            'sales_by_category' => $this->buildSalesByCategory($startDate, $endDate),
            'sales_by_region' => $this->buildSalesByRegion($startDate, $endDate),
            'sales_by_customer_type' => $this->buildSalesByCustomerType($startDate, $endDate),
            'meta' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
        ]);
    }
}