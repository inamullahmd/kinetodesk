<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    private const DASHBOARD_AS_OF = '2026-03-31 23:59:59';

    public function __invoke(): JsonResponse
    {
        return response()->json([
            'reportingPeriod' => $this->getCurrentReportingPeriod(),
            'asOfDate' => $this->asOfDate()->toDateString(),

            'revenueComparison' => $this->getRevenueComparedToPreviousMonth(),
            'quarterComparison' => $this->getRevenueComparedToPreviousQuarter(),
            'profitComparison' => $this->getProfitComparedToPreviousMonth(),
            'quarterProfitComparison' => $this->getProfitComparedToPreviousQuarter(),
            'currentInventoryValue' => $this->getCurrentInventoryValue(),

            'last12MonthsRevenue' => $this->getRevenueForLast12Months(),
            'last12MonthsProfit' => $this->getProfitForLast12Months(),
            'last8QuartersRevenue' => $this->getRevenueForLast8Quarters(),
            'last8QuartersProfit' => $this->getProfitForLast8Quarters(),

            'salesByChannel' => $this->getSalesByChannelForCurrentMonth(),
            'lowStockProducts' => $this->getLowStockProducts(10),

            'topSellingProduct' => $this->getTopSellingProductForCurrentMonth(),
            'topEmployee' => $this->getTopEmployeeBySalesForCurrentMonth(),
            'pendingOrdersCount' => $this->getPendingOrdersCountForCurrentMonth(),
            'refundSummary' => $this->getRefundSummaryForCurrentMonth(),
        ]);
    }

    protected function asOfDate(): CarbonImmutable
    {
        return CarbonImmutable::parse(self::DASHBOARD_AS_OF);
    }

    protected function getCurrentReportingPeriod(): array
    {
        $asOf = $this->asOfDate();
        $startDate = $asOf->startOfMonth()->startOfDay();
        $endDate = $asOf->endOfDay();

        return [
            'startDate' => $startDate->toDateString(),
            'endDate' => $endDate->toDateString(),
            'label' => $this->formatReportingPeriodLabel($startDate, $endDate),
        ];
    }

    protected function formatReportingPeriodLabel(CarbonImmutable $startDate, CarbonImmutable $endDate): string
    {
        if ($startDate->year === $endDate->year) {
            return $startDate->format('F j') . ' - ' . $endDate->format('F j, Y');
        }

        return $startDate->format('F j, Y') . ' - ' . $endDate->format('F j, Y');
    }

    protected function getRevenueComparedToPreviousMonth(): array
    {
        $today = $this->asOfDate();

        $currentStart = $today->startOfMonth()->startOfDay();
        $currentEnd = $today->endOfDay();

        $previousMonthDate = $today->subMonthNoOverflow();
        $previousStart = $previousMonthDate->startOfMonth()->startOfDay();
        $previousEnd = $previousMonthDate->endOfMonth()->endOfDay();

        $currentRevenue = $this->getRevenueByDateRange($currentStart, $currentEnd);
        $previousRevenue = $this->getRevenueByDateRange($previousStart, $previousEnd);

        return [
            'current_month_revenue' => round($currentRevenue, 2),
            'previous_month_revenue' => round($previousRevenue, 2),
            'percentage_change' => $previousRevenue > 0
                ? round((($currentRevenue - $previousRevenue) / $previousRevenue) * 100, 2)
                : null,
        ];
    }

    protected function getRevenueComparedToPreviousQuarter(): array
    {
        $today = $this->asOfDate();

        $currentQuarterStart = $today->startOfQuarter()->startOfDay();
        $currentQuarterEnd = $today->endOfDay();

        $previousQuarterDate = $today->subQuarters(1);
        $previousQuarterStart = $previousQuarterDate->startOfQuarter()->startOfDay();
        $previousQuarterEnd = $previousQuarterDate->endOfQuarter()->endOfDay();

        $currentRevenue = $this->getRevenueByDateRange($currentQuarterStart, $currentQuarterEnd);
        $previousRevenue = $this->getRevenueByDateRange($previousQuarterStart, $previousQuarterEnd);

        return [
            'current_quarter_revenue' => round($currentRevenue, 2),
            'previous_quarter_revenue' => round($previousRevenue, 2),
            'percentage_change' => $previousRevenue > 0
                ? round((($currentRevenue - $previousRevenue) / $previousRevenue) * 100, 2)
                : null,
        ];
    }

    protected function getProfitComparedToPreviousMonth(): array
    {
        $today = $this->asOfDate();

        $currentStart = $today->startOfMonth()->startOfDay();
        $currentEnd = $today->endOfDay();

        $previousMonthDate = $today->subMonthNoOverflow();
        $previousStart = $previousMonthDate->startOfMonth()->startOfDay();
        $previousEnd = $previousMonthDate->endOfMonth()->endOfDay();

        $currentProfit = $this->getNetProfitByDateRange($currentStart, $currentEnd);
        $previousProfit = $this->getNetProfitByDateRange($previousStart, $previousEnd);

        return [
            'current_month_profit' => round($currentProfit, 2),
            'previous_month_profit' => round($previousProfit, 2),
            'percentage_change' => $previousProfit > 0
                ? round((($currentProfit - $previousProfit) / $previousProfit) * 100, 2)
                : null,
        ];
    }

    protected function getProfitComparedToPreviousQuarter(): array
    {
        $today = $this->asOfDate();

        $currentQuarterStart = $today->startOfQuarter()->startOfDay();
        $currentQuarterEnd = $today->endOfDay();

        $previousQuarterDate = $today->subQuarters(1);
        $previousQuarterStart = $previousQuarterDate->startOfQuarter()->startOfDay();
        $previousQuarterEnd = $previousQuarterDate->endOfQuarter()->endOfDay();

        $currentProfit = $this->getNetProfitByDateRange($currentQuarterStart, $currentQuarterEnd);
        $previousProfit = $this->getNetProfitByDateRange($previousQuarterStart, $previousQuarterEnd);

        return [
            'current_quarter_profit' => round($currentProfit, 2),
            'previous_quarter_profit' => round($previousProfit, 2),
            'percentage_change' => $previousProfit > 0
                ? round((($currentProfit - $previousProfit) / $previousProfit) * 100, 2)
                : null,
        ];
    }

    protected function getCurrentInventoryValue(): float
    {
        return round(
            (float) DB::table('stock_batches')
                ->join('products', 'stock_batches.product_id', '=', 'products.id')
                ->where('products.is_active', true)
                ->sum(DB::raw('stock_batches.qty_remaining * stock_batches.unit_cost')),
            2
        );
    }

    protected function getRevenueForLast12Months(): array
    {
        $asOf = $this->asOfDate();
        $revenues = [];

        for ($i = 11; $i >= 0; $i--) {
            $monthBase = $asOf->subMonthsNoOverflow($i);
            $startDate = $monthBase->startOfMonth()->startOfDay();
            $endDate = $i === 0
                ? $asOf->endOfDay()
                : $monthBase->endOfMonth()->endOfDay();

            $revenues[] = [
                'month' => $startDate->format('F Y'),
                'revenue' => round($this->getRevenueByDateRange($startDate, $endDate), 2),
            ];
        }

        return $revenues;
    }

    protected function getProfitForLast12Months(): array
    {
        $asOf = $this->asOfDate();
        $profits = [];

        for ($i = 11; $i >= 0; $i--) {
            $monthBase = $asOf->subMonthsNoOverflow($i);
            $startDate = $monthBase->startOfMonth()->startOfDay();
            $endDate = $i === 0
                ? $asOf->endOfDay()
                : $monthBase->endOfMonth()->endOfDay();

            $profits[] = [
                'month' => $startDate->format('F Y'),
                'profit' => round($this->getNetProfitByDateRange($startDate, $endDate), 2),
            ];
        }

        return $profits;
    }

    protected function getRevenueForLast8Quarters(): array
    {
        $asOf = $this->asOfDate();
        $revenues = [];

        for ($i = 0; $i < 8; $i++) {
            $quarterDate = $asOf->subQuarters($i);
            $startDate = $quarterDate->startOfQuarter()->startOfDay();
            $endDate = $i === 0
                ? $asOf->endOfDay()
                : $quarterDate->endOfQuarter()->endOfDay();

            $revenues[] = [
                'quarter' => 'Q' . $startDate->quarter . ' ' . $startDate->year,
                'revenue' => round($this->getRevenueByDateRange($startDate, $endDate), 2),
            ];
        }

        return array_reverse($revenues);
    }

    protected function getProfitForLast8Quarters(): array
    {
        $asOf = $this->asOfDate();
        $profits = [];

        for ($i = 0; $i < 8; $i++) {
            $quarterDate = $asOf->subQuarters($i);
            $startDate = $quarterDate->startOfQuarter()->startOfDay();
            $endDate = $i === 0
                ? $asOf->endOfDay()
                : $quarterDate->endOfQuarter()->endOfDay();

            $profits[] = [
                'quarter' => 'Q' . $startDate->quarter . ' ' . $startDate->year,
                'profit' => round($this->getNetProfitByDateRange($startDate, $endDate), 2),
            ];
        }

        return array_reverse($profits);
    }

    protected function getRevenueByDateRange(CarbonImmutable $startDate, CarbonImmutable $endDate): float
    {
        return (float) DB::table('sales_orders')
            ->whereBetween('ordered_at', [$startDate, $endDate])
            ->where('status', 'delivered')
            ->sum('grand_total');
    }

    protected function getGrossProfitByDateRange(CarbonImmutable $startDate, CarbonImmutable $endDate): float
    {
        return (float) DB::table('sales_order_items')
            ->join('sales_orders', 'sales_order_items.sales_order_id', '=', 'sales_orders.id')
            ->whereBetween('sales_orders.ordered_at', [$startDate, $endDate])
            ->where('sales_orders.status', 'delivered')
            ->sum('sales_order_items.line_profit');
    }

    protected function getScrappedReturnLossByDateRange(CarbonImmutable $startDate, CarbonImmutable $endDate): float
    {
        return (float) DB::table('return_items')
            ->join('returns', 'return_items.return_id', '=', 'returns.id')
            ->join('sales_orders', 'returns.sales_order_id', '=', 'sales_orders.id')
            ->whereBetween('sales_orders.ordered_at', [$startDate, $endDate])
            ->where('return_items.action', 'scrap')
            ->sum('return_items.refund_amount');
    }

    protected function getNetProfitByDateRange(CarbonImmutable $startDate, CarbonImmutable $endDate): float
    {
        $grossProfit = $this->getGrossProfitByDateRange($startDate, $endDate);
        $scrapLoss = $this->getScrappedReturnLossByDateRange($startDate, $endDate);

        return round($grossProfit - $scrapLoss, 2);
    }

    protected function getSalesByChannelForCurrentMonth(): array
    {
        $today = $this->asOfDate();
        $startOfMonth = $today->startOfMonth()->startOfDay();
        $endOfMonth = $today->endOfDay();

        return DB::table('sales_orders')
            ->select(['channel', DB::raw('SUM(grand_total) as total_revenue, COUNT(*) as total_orders')])
            ->whereBetween('ordered_at', [$startOfMonth, $endOfMonth])
            ->where('status', '!=', 'refunded')
            ->groupBy('channel')
            ->get()
            ->map(function ($row) {
                return [
                    'channel' => $row->channel,
                    'total_revenue' => round((float) $row->total_revenue, 2),
                    'total_orders' => (int) $row->total_orders,
                ];
            })
            ->toArray();
    }

    protected function getTopSellingProductForCurrentMonth(): ?array
    {
        $today = $this->asOfDate();
        $startOfMonth = $today->startOfMonth()->startOfDay();
        $endOfMonth = $today->endOfDay();

        $row = DB::table('sales_order_items')
            ->join('sales_orders', 'sales_order_items.sales_order_id', '=', 'sales_orders.id')
            ->join('products', 'sales_order_items.product_id', '=', 'products.id')
            ->leftJoin('categories as product_category', 'products.category_id', '=', 'product_category.id')
            ->leftJoin('categories as parent_category', 'product_category.parent_id', '=', 'parent_category.id')
            ->select([
                'sales_order_items.product_id',
                'products.title',
                DB::raw('COALESCE(parent_category.name, product_category.name) as category_name'),
                DB::raw("
                CASE
                    WHEN product_category.parent_id IS NULL THEN NULL
                    ELSE product_category.name
                END as sub_category_name
            "),
                DB::raw('SUM(sales_order_items.qty) as total_quantity'),
            ])
            ->whereBetween('sales_orders.ordered_at', [$startOfMonth, $endOfMonth])
            ->where('sales_orders.status', 'delivered')
            ->groupBy(
                'sales_order_items.product_id',
                'products.title',
                'product_category.name',
                'product_category.parent_id',
                'parent_category.name'
            )
            ->orderByDesc('total_quantity')
            ->first();

        if (!$row) {
            return null;
        }

        return [
            'product_id' => (int) $row->product_id,
            'product_name' => $row->title,
            'category_name' => $row->category_name,
            'sub_category_name' => $row->sub_category_name,
            'total_quantity' => (int) $row->total_quantity,
        ];
    }

    protected function getTopEmployeeBySalesForCurrentMonth(): ?array
    {
        $today = $this->asOfDate();
        $startOfMonth = $today->startOfMonth()->startOfDay();
        $endOfMonth = $today->endOfDay();

        $row = DB::table('sales_orders')
            ->join('employees', 'sales_orders.employee_id', '=', 'employees.id')
            ->select([
                'sales_orders.employee_id',
                DB::raw("TRIM(CONCAT(employees.first_name, ' ', employees.last_name)) as employee_name"),
                DB::raw('SUM(sales_orders.grand_total) as total_sales'),
                DB::raw('COUNT(*) as total_orders')
            ])
            ->whereBetween('sales_orders.ordered_at', [$startOfMonth, $endOfMonth])
            ->where('sales_orders.status', 'delivered')
            ->whereNotNull('sales_orders.employee_id')
            ->groupBy('sales_orders.employee_id', 'employees.first_name', 'employees.last_name')
            ->orderByDesc('total_sales')
            ->first();

        if (!$row) {
            return null;
        }

        return [
            'employee_id' => (int) $row->employee_id,
            'employee_name' => $row->employee_name ?: 'Unknown',
            'total_sales' => round((float) $row->total_sales, 2),
            'total_orders' => (int) $row->total_orders,
        ];
    }

    protected function getPendingOrdersCountForCurrentMonth(): int
    {
        $today = $this->asOfDate();
        $startOfMonth = $today->startOfMonth()->startOfDay();
        $endOfMonth = $today->endOfDay();

        return DB::table('sales_orders')
            ->whereBetween('ordered_at', [$startOfMonth, $endOfMonth])
            ->where('status', 'pending')
            ->count();
    }

    protected function getRefundSummaryForCurrentMonth(): array
    {
        $today = $this->asOfDate();
        $startOfMonth = $today->startOfMonth()->startOfDay();
        $endOfMonth = $today->endOfDay();

        $row = DB::table('returns')
            ->join('sales_orders', 'returns.sales_order_id', '=', 'sales_orders.id')
            ->whereBetween('sales_orders.ordered_at', [$startOfMonth, $endOfMonth])
            ->selectRaw('COUNT(returns.id) as refund_count, COALESCE(SUM(returns.refund_amount), 0) as refund_value')
            ->first();

        return [
            'refund_count' => (int) ($row->refund_count ?? 0),
            'refund_value' => round((float) ($row->refund_value ?? 0), 2),
        ];
    }

    protected function getLowStockProducts(int $threshold = 10): array
    {
        $today = $this->asOfDate();

        $lastSalesSubquery = DB::table('sales_order_items')
            ->join('sales_orders', 'sales_order_items.sales_order_id', '=', 'sales_orders.id')
            ->select([
                'sales_order_items.product_id',
                DB::raw('MAX(sales_orders.ordered_at) as last_sold_at')
            ])
            ->where('sales_orders.status', 'delivered')
            ->groupBy('sales_order_items.product_id');

        return DB::table('products')
            ->leftJoin('stock_batches', 'products.id', '=', 'stock_batches.product_id')
            ->leftJoinSub($lastSalesSubquery, 'last_sales', function ($join) {
                $join->on('products.id', '=', 'last_sales.product_id');
            })
            ->where('products.is_active', true)
            ->groupBy(
                'products.id',
                'products.title',
                'products.internal_sku',
                'last_sales.last_sold_at'
            )
            ->select([
                'products.id',
                'products.title',
                'products.internal_sku',
                'last_sales.last_sold_at',
                DB::raw('COALESCE(SUM(stock_batches.qty_remaining), 0) as stock_qty')
            ])
            ->havingRaw('COALESCE(SUM(stock_batches.qty_remaining), 0) <= ?', [$threshold])
            ->orderBy('stock_qty')
            ->orderBy('last_sales.last_sold_at')
            ->limit(5)
            ->get()
            ->map(function ($product) use ($today) {
                $stock = (int) $product->stock_qty;
                $lastSoldAt = $product->last_sold_at
                    ? CarbonImmutable::parse($product->last_sold_at)
                    : null;
                $daysOut = $lastSoldAt ? $lastSoldAt->diffInDays($today) : null;

                return [
                    'id' => (int) $product->id,
                    'title' => $product->title,
                    'internal_sku' => $product->internal_sku,
                    'stock_qty' => $stock,
                    'last_sold_at' => $lastSoldAt?->toDateString(),
                    'days_out_of_stock' => $daysOut,
                    'stock_status' => [
                        'label' => $stock === 0 ? 'Out of Stock' : ($stock <= 3 ? 'Critical' : 'Low Stock'),
                        'action' => $stock === 0 ? 'Order ASAP' : ($stock <= 3 ? 'Reorder Immediately' : 'Reorder Soon'),
                        'tone' => $stock === 0 ? 'danger' : ($stock <= 3 ? 'warning' : 'caution'),
                        'criticality' => $daysOut !== null && $daysOut >= 21 ? 'Critical' : ($stock === 0 ? 'Urgent' : 'Monitor'),
                    ],
                ];
            })
            ->toArray();
    }
}
