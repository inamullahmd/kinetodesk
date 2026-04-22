<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\Product;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;



class DashboardController extends Controller
{
    # Declare constants


    # This method will return key metrics for the dashboard such as total sales, total orders, and top selling products for the current day
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'last12MonthsRevenue' => $this->getRevenueforLast12Months(),
            'revenueComparison' => $this->getRevenueComparedToPreviousMonth(),
            'quarterComparison' => $this->comparetoPreviousQuarter(),
            'salesByChannel' => $this->salesByChannelForCurrentMonth(),
            'topSellingProducts' => $this->topSellingProductsForCurrentMonth(),
            'topEmployees' => $this->getTopEmployeesBySalesForCurrentMonth(),
            'ordersByStatus' => $this->getNumberOfOrdersByStatusForCurrentMonth(),
            'lowStockProducts' => $this->getLowStockProducts(10),

        ]);
    }

    protected function getRevenueforLast12Months(): array
    {
        $revenues = [];
        for ($i = 0; $i < 12; $i++) {
            $startDate = CarbonImmutable::now()->subMonths($i)->startOfMonth();
            $endDate = CarbonImmutable::now()->subMonths($i)->endOfMonth();
            $revenues[] = [
                'month' => $startDate->format('F Y'),
                'revenue' => $this->getRevenueByDateRange($startDate, $endDate),
            ];
        }
        return array_reverse($revenues);
    }


    protected function getRevenueByDateRange(CarbonImmutable $startDate, CarbonImmutable $endDate): float
    {
        return SalesOrder::whereBetween('ordered_at', [$startDate, $endDate])
            ->where('status', 'delivered')
            ->sum('grand_total');
    }

    protected function getRevenueComparedToPreviousMonth(): array
    {
        $today = CarbonImmutable::now();
        $currentStart = $today->subDays(29)->startOfDay();
        $currentEnd = $today->endOfDay();
        $previousStart = $currentStart->subDays(30)->startOfDay();
        $previousEnd = $currentStart->subDay()->endOfDay();

        $currentRevenue = $this->getRevenueByDateRange($currentStart, $currentEnd);
        $previousRevenue = $this->getRevenueByDateRange($previousStart, $previousEnd);

        return [
            'current_month_revenue' => $currentRevenue,
            'previous_month_revenue' => $previousRevenue,
            'percentage_change' => $previousRevenue > 0 ? (($currentRevenue - $previousRevenue) / $previousRevenue) * 100 : null,
        ];
    }

    protected function compareToPreviousQuarter(): array
    {
        $today = CarbonImmutable::now();

        $currentQuarterStart = $today->startOfQuarter()->startOfDay();
        $currentQuarterEnd = $today->endOfDay();

        $previousQuarterStart = $currentQuarterStart->subQuarter()->startOfDay();
        $previousQuarterEndLimit = $previousQuarterStart->endOfQuarter()->endOfDay();

        // inclusive day count from current quarter start to today
        $elapsedDays = $currentQuarterStart->diffInDays($currentQuarterEnd) + 1;

        $previousQuarterEnd = $previousQuarterStart
            ->addDays($elapsedDays - 1)
            ->endOfDay();

        // safety in case quarter lengths differ
        if ($previousQuarterEnd->gt($previousQuarterEndLimit)) {
            $previousQuarterEnd = $previousQuarterEndLimit;
        }

        $currentRevenue = $this->getRevenueByDateRange($currentQuarterStart, $currentQuarterEnd);
        $previousRevenue = $this->getRevenueByDateRange($previousQuarterStart, $previousQuarterEnd);

        return [
            'current_quarter_start' => $currentQuarterStart->toDateString(),
            'current_quarter_end' => $currentQuarterEnd->toDateString(),
            'previous_quarter_start' => $previousQuarterStart->toDateString(),
            'previous_quarter_end' => $previousQuarterEnd->toDateString(),
            'current_quarter_revenue' => $currentRevenue,
            'previous_quarter_revenue' => $previousRevenue,
            'percentage_change' => $previousRevenue > 0
                ? round((($currentRevenue - $previousRevenue) / $previousRevenue) * 100, 2)
                : null,
        ];
    }

    protected function salesByChannelForCurrentMonth(): array
    {
        $today = CarbonImmutable::now();
        $startOfMonth = $today->startOfMonth()->startOfDay();
        $endOfMonth = $today->endOfDay();

        return SalesOrder::select('channel', DB::raw('SUM(grand_total) as total_revenue, COUNT(*) as total_orders'))
            ->whereBetween('ordered_at', [$startOfMonth, $endOfMonth])
            ->groupBy('channel')
            ->get()
            ->toArray();
    }

    protected function topSellingProductsForCurrentMonth(): array
    {
        $today = CarbonImmutable::now();
        $startOfMonth = $today->startOfMonth()->startOfDay();
        $endOfMonth = $today->endOfDay();

        return SalesOrderItem::select('product_id', DB::raw('SUM(qty) as total_quantity'))
            ->whereHas('salesOrder', function ($query) use ($startOfMonth, $endOfMonth) {
                $query->whereBetween('ordered_at', [$startOfMonth, $endOfMonth])
                    ->where('status', 'delivered');
            })
            ->groupBy('product_id')
            ->orderByDesc('total_quantity')
            ->with('product:id,title')
            ->take(5)
            ->get()
            ->map(function ($item) {
                return [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->title,
                    'total_quantity' => $item->total_quantity,
                ];
            })
            ->toArray();
    }

    protected function getTopEmployeesBySalesForCurrentMonth(): array
    {
        $today = CarbonImmutable::now();
        $startOfMonth = $today->startOfMonth()->startOfDay();
        $endOfMonth = $today->endOfDay();

        return SalesOrder::select('employee_id', DB::raw('SUM(grand_total) as total_sales, COUNT(*) as total_orders'))
            ->whereBetween('ordered_at', [$startOfMonth, $endOfMonth])
            ->where('status', 'delivered')
            ->whereNotNull('employee_id')
            ->groupBy('employee_id')
            ->orderByDesc('total_sales')
            ->with('employee:id,first_name,last_name')
            ->take(5)
            ->get()
            ->map(function ($order) {
                return [
                    'employee_id' => $order->employee_id,
                    'employee_name' => $order->employee ? $order->employee->first_name . ' ' . $order->employee->last_name : 'Unknown',
                    'total_sales' => $order->total_sales,
                    'total_orders' => $order->total_orders,
                ];
            })
            ->toArray();
    }

    protected function getNumberOfOrdersByStatusForCurrentMonth(): array
    {
        $today = CarbonImmutable::now();
        $startOfMonth = $today->startOfMonth()->startOfDay();
        $endOfMonth = $today->endOfDay();

        return SalesOrder::select('status', DB::raw('COUNT(*) as total_orders'))
            ->whereBetween('ordered_at', [$startOfMonth, $endOfMonth])
            ->groupBy('status')
            ->get()
            ->toArray();
    }

    protected function getLowStockProducts(int $threshold = 10): array
    {
        return Product::query()
            ->leftJoin('stock_batches', 'products.id', '=', 'stock_batches.product_id')
            ->where('products.is_active', true)
            ->groupBy('products.id', 'products.title', 'products.internal_sku')
            ->select(
                'products.id',
                'products.title',
                'products.internal_sku',
                DB::raw('COALESCE(SUM(stock_batches.qty_remaining), 0) as stock_qty')
            )
            ->havingRaw('COALESCE(SUM(stock_batches.qty_remaining), 0) <= ?', [$threshold])
            ->orderBy('stock_qty')
            ->take(5)
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'title' => $product->title,
                    'internal_sku' => $product->internal_sku,
                    'stock_qty' => (int) $product->stock_qty,
                ];
            })
            ->toArray();
    }
}
