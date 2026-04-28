<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    private const DASHBOARD_AS_OF = '2026-03-31 23:59:59';

    public function __invoke(): JsonResponse
    {
        return response()->json([
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

            'salesByChannel' => $this->salesByChannelForCurrentMonth(),
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

    protected function getRevenueComparedToPreviousMonth(): array
    {
        $today = $this->asOfDate();

        $currentStart = $today->startOfMonth()->startOfDay();
        $currentEnd = $today->endOfDay();

        $previousMonthStart = $currentStart->subMonth()->startOfMonth();
        $previousMonthEnd = $previousMonthStart->endOfMonth()->endOfDay();

        $currentRevenue = $this->getRevenueByDateRange($currentStart, $currentEnd);
        $previousRevenue = $this->getRevenueByDateRange($previousMonthStart, $previousMonthEnd);

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

        $previousQuarterStart = $currentQuarterStart->subQuarter()->startOfDay();
        $previousQuarterEnd = $previousQuarterStart->endOfQuarter()->endOfDay();

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

        $previousMonthStart = $currentStart->subMonth()->startOfMonth();
        $previousMonthEnd = $previousMonthStart->endOfMonth()->endOfDay();

        $currentProfit = $this->getNetProfitByDateRange($currentStart, $currentEnd);
        $previousProfit = $this->getNetProfitByDateRange($previousMonthStart, $previousMonthEnd);

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

        $previousQuarterStart = $currentQuarterStart->subQuarter()->startOfDay();
        $previousQuarterEnd = $previousQuarterStart->endOfQuarter()->endOfDay();

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
            $monthBase = $asOf->copy()->subMonthsNoOverflow($i);

            $startDate = $monthBase->copy()->startOfMonth()->startOfDay();
            $endDate = $i === 0
                ? $asOf->copy()->endOfDay()
                : $monthBase->copy()->endOfMonth()->endOfDay();

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
            $monthBase = $asOf->copy()->subMonthsNoOverflow($i);

            $startDate = $monthBase->copy()->startOfMonth()->startOfDay();
            $endDate = $i === 0
                ? $asOf->copy()->endOfDay()
                : $monthBase->copy()->endOfMonth()->endOfDay();

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
            $quarterDate = $asOf->copy()->subQuarters($i);
            $startDate = $quarterDate->copy()->startOfQuarter()->startOfDay();
            $endDate = $i === 0
                ? $asOf->copy()->endOfDay()
                : $quarterDate->copy()->endOfQuarter()->endOfDay();

            $quarterNumber = (int) ceil($startDate->month / 3);

            $revenues[] = [
                'quarter' => 'Q' . $quarterNumber . ' ' . $startDate->year,
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
            $quarterDate = $asOf->copy()->subQuarters($i);
            $startDate = $quarterDate->copy()->startOfQuarter()->startOfDay();
            $endDate = $i === 0
                ? $asOf->copy()->endOfDay()
                : $quarterDate->copy()->endOfQuarter()->endOfDay();

            $quarterNumber = (int) ceil($startDate->month / 3);

            $profits[] = [
                'quarter' => 'Q' . $quarterNumber . ' ' . $startDate->year,
                'profit' => round($this->getNetProfitByDateRange($startDate, $endDate), 2),
            ];
        }

        return array_reverse($profits);
    }

    protected function getRevenueByDateRange(CarbonImmutable $startDate, CarbonImmutable $endDate): float
    {
        return (float) SalesOrder::query()
            ->whereBetween('ordered_at', [$startDate, $endDate])
            ->where('status', 'delivered')
            ->sum('grand_total');
    }

    protected function getGrossProfitByDateRange(CarbonImmutable $startDate, CarbonImmutable $endDate): float
    {
        return (float) SalesOrderItem::query()
            ->join('sales_orders', 'sales_order_items.sales_order_id', '=', 'sales_orders.id')
            ->whereBetween('sales_orders.ordered_at', [$startDate, $endDate])
            ->where('sales_orders.status', 'delivered')
            ->sum('sales_order_items.line_profit');
    }

    protected function getScrappedReturnLossByDateRange(CarbonImmutable $startDate, CarbonImmutable $endDate): float
    {
        return (float) DB::table('return_items')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('action', 'scrap')
            ->sum('refund_amount');
    }

    protected function getNetProfitByDateRange(CarbonImmutable $startDate, CarbonImmutable $endDate): float
    {
        $grossProfit = $this->getGrossProfitByDateRange($startDate, $endDate);
        $scrapLoss = $this->getScrappedReturnLossByDateRange($startDate, $endDate);

        return round($grossProfit - $scrapLoss, 2);
    }

    protected function salesByChannelForCurrentMonth(): array
    {
        $today = $this->asOfDate();
        $startOfMonth = $today->startOfMonth()->startOfDay();
        $endOfMonth = $today->endOfDay();

        return SalesOrder::query()
            ->select('channel', DB::raw('SUM(grand_total) as total_revenue, COUNT(*) as total_orders'))
            ->whereBetween('ordered_at', [$startOfMonth, $endOfMonth])
            ->where('status', 'delivered')
            ->groupBy('channel')
            ->get()
            ->toArray();
    }

    protected function getTopSellingProductForCurrentMonth(): ?array
    {
        $today = $this->asOfDate();
        $startOfMonth = $today->startOfMonth()->startOfDay();
        $endOfMonth = $today->endOfDay();

        $item = SalesOrderItem::query()
            ->select('product_id', DB::raw('SUM(qty) as total_quantity'))
            ->whereHas('salesOrder', function ($query) use ($startOfMonth, $endOfMonth) {
                $query->whereBetween('ordered_at', [$startOfMonth, $endOfMonth])
                    ->where('status', 'delivered');
            })
            ->groupBy('product_id')
            ->orderByDesc('total_quantity')
            ->with('product:id,title')
            ->first();

        if (!$item) {
            return null;
        }

        return [
            'product_id' => $item->product_id,
            'product_name' => $item->product?->title ?? 'Unknown',
            'total_quantity' => (int) $item->total_quantity,
        ];
    }

    protected function getTopEmployeeBySalesForCurrentMonth(): ?array
    {
        $today = $this->asOfDate();
        $startOfMonth = $today->startOfMonth()->startOfDay();
        $endOfMonth = $today->endOfDay();

        $order = SalesOrder::query()
            ->select('employee_id', DB::raw('SUM(grand_total) as total_sales, COUNT(*) as total_orders'))
            ->whereBetween('ordered_at', [$startOfMonth, $endOfMonth])
            ->where('status', 'delivered')
            ->whereNotNull('employee_id')
            ->groupBy('employee_id')
            ->orderByDesc('total_sales')
            ->with('employee:id,first_name,last_name')
            ->first();

        if (!$order) {
            return null;
        }

        return [
            'employee_id' => $order->employee_id,
            'employee_name' => $order->employee
                ? $order->employee->first_name . ' ' . $order->employee->last_name
                : 'Unknown',
            'total_sales' => round((float) $order->total_sales, 2),
            'total_orders' => (int) $order->total_orders,
        ];
    }

    protected function getPendingOrdersCountForCurrentMonth(): int
    {
        $today = $this->asOfDate();
        $startOfMonth = $today->startOfMonth()->startOfDay();
        $endOfMonth = $today->endOfDay();

        return SalesOrder::query()
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
        return Product::query()
            ->leftJoin('stock_batches', 'products.id', '=', 'stock_batches.product_id')
            ->where('products.is_active', true)
            ->groupBy('products.id', 'products.title')
            ->select(
                'products.id',
                'products.title',
                DB::raw('COALESCE(SUM(stock_batches.qty_remaining), 0) as stock_qty')
            )
            ->havingRaw('COALESCE(SUM(stock_batches.qty_remaining), 0) <= ?', [$threshold])
            ->orderBy('stock_qty')
            ->take(5)
            ->get()
            ->map(function ($product) {
                $stock = (int) $product->stock_qty;

                return [
                    'id' => $product->id,
                    'title' => $product->title,
                    'stock_qty' => $stock,
                    'stock_status' => $this->getStockStatus($stock),
                ];
            })
            ->toArray();
    }

    protected function getStockStatus(int $stock): array
    {
        if ($stock === 0) {
            return [
                'label' => 'Out of Stock',
                'action' => 'Order ASAP',
                'tone' => 'danger',
            ];
        }

        if ($stock <= 3) {
            return [
                'label' => 'Critical',
                'action' => 'Reorder Immediately',
                'tone' => 'warning',
            ];
        }

        return [
            'label' => 'Low Stock',
            'action' => 'Reorder Soon',
            'tone' => 'caution',
        ];
    }
}