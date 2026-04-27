<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\Product;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Models\Customer;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Builder;

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

            'last12MonthsRevenue' => $this->getRevenueforLast12Months(),
            'last12MonthsProfit' => $this->getProfitForLast12Months(),

            'last8QuartersRevenue' => $this->getRevenueForLast8Quarters(),
            'last8QuartersProfit' => $this->getProfitForLast8Quarters(),
            
            'salesByChannel' => $this->salesByChannelForCurrentMonth(),
            'lowStockProducts' => $this->getLowStockProducts(10),

            'topSellingProducts' => $this->topSellingProductsForCurrentMonth(),
            'topEmployees' => $this->getTopEmployeesBySalesForCurrentMonth(),
            
            'ordersByStatus' => $this->getNumberOfOrdersByStatusForCurrentMonth(),
            'customerBusinessMap' => $this->getCustomerBusinessMap(),
        ]);
    }

    # Function to fetch the "as of" date for the dashboard, allowing for easy adjustments in the future
    protected function asOfDate(): CarbonImmutable
    {
        return CarbonImmutable::parse(self::DASHBOARD_AS_OF);
    }

    # Function to calculate revenue for the current month and compare it to the same number of days in the previous month, returning the revenue amounts and percentage change
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
            'current_month_revenue' => $currentRevenue,
            'previous_month_revenue' => $previousRevenue,
            'percentage_change' => $previousRevenue > 0
                ? round((($currentRevenue - $previousRevenue) / $previousRevenue) * 100, 2)
                : null,
        ];
    }

    # Function to calculate revenue for the current quarter and compare it to the same number of days in the previous quarter, returning the revenue amounts and percentage change
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
            'current_quarter_revenue' => $currentRevenue,
            'previous_quarter_revenue' => $previousRevenue,
            'percentage_change' => $previousRevenue > 0
                ? round((($currentRevenue - $previousRevenue) / $previousRevenue) * 100, 2)
                : null,
        ];
    }

    # Function to calculate profit for the current month and compare it to the same number of days in the previous month, returning the profit amounts and percentage change
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
            'current_month_profit' => $currentProfit,
            'previous_month_profit' => $previousProfit,
            'percentage_change' => $previousProfit > 0
                ? round((($currentProfit - $previousProfit) / $previousProfit) * 100, 2)
                : null,
        ];
    }

    # Function to calculate profit for the current quarter and compare it to the same number of days in the previous quarter, returning the profit amounts and percentage change
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
            'current_quarter_profit' => $currentProfit,
            'previous_quarter_profit' => $previousProfit,
            'percentage_change' => $previousProfit > 0
                ? round((($currentProfit - $previousProfit) / $previousProfit) * 100, 2)
                : null,
        ];
    }

    # Function to calculate the current inventory value by summing the remaining quantity multiplied by unit cost for all active products, returning the total inventory value
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

    # Function to calculate revenue for the last 8 quarters, returning an array of quarter labels and revenue amounts
    protected function getRevenueforLast12Months(): array
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
                'revenue' => $this->getRevenueByDateRange($startDate, $endDate),
            ];
        }

        return $revenues;
    }

    # Function to calculate profit for the last 12 months, returning an array of month labels and profit amounts
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
                'profit' => $this->getNetProfitByDateRange($startDate, $endDate),
            ];
        }

        return $profits;
    }

    # Function to calculate revenue for the last 8 quarters, returning an array of quarter labels and revenue amounts
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

            $quarterNumber = (int) ceil($startDate->month / 3);

            $revenues[] = [
                'quarter' => 'Q' . $quarterNumber . ' ' . $startDate->year,
                'revenue' => $this->getRevenueByDateRange($startDate, $endDate),
            ];
        }

        return array_reverse($revenues);
    }

    # Function to calculate profit for the last 8 quarters, returning an array of quarter labels and profit amounts
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

            $quarterNumber = (int) ceil($startDate->month / 3);

            $profits[] = [
                'quarter' => 'Q' . $quarterNumber . ' ' . $startDate->year,
                'profit' => $this->getNetProfitByDateRange($startDate, $endDate),
            ];
        }

        return array_reverse($profits);
    }

    # Helper function to calculate total revenue for a given date range, filtering for delivered orders, returning the total revenue amount
    protected function getRevenueByDateRange(CarbonImmutable $startDate, CarbonImmutable $endDate): float
    {
        return SalesOrder::whereBetween('ordered_at', [$startDate, $endDate])
            ->where('status', 'delivered')
            ->sum('grand_total');
    }    

    # Helper function to calculate gross profit for a given date range by summing the line profit from sales order items for delivered orders, returning the total gross profit amount
    protected function getGrossProfitByDateRange(CarbonImmutable $startDate, CarbonImmutable $endDate): float
    {
        return round(
            (float) SalesOrderItem::query()
                ->join('sales_orders', 'sales_order_items.sales_order_id', '=', 'sales_orders.id')
                ->whereBetween('sales_orders.ordered_at', [$startDate, $endDate])
                ->where('sales_orders.status', 'delivered')
                ->sum('sales_order_items.line_profit'),
            2
        );
    }

    # Helper function to calculate total loss from scrapped return items for a given date range, returning the total loss amount
    protected function getScrappedReturnLossByDateRange(CarbonImmutable $startDate, CarbonImmutable $endDate): float
    {
        return round(
            (float) DB::table('return_items')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->where('action', 'scrap')
                ->sum('refund_amount'),
            2
        );
    }

    # Helper function to calculate net profit for a given date range by subtracting scrapped return losses from gross profit, returning the total net profit amount
    protected function getNetProfitByDateRange(CarbonImmutable $startDate, CarbonImmutable $endDate): float
    {
        $grossProfit = $this->getGrossProfitByDateRange($startDate, $endDate);
        $scrapLoss = $this->getScrappedReturnLossByDateRange($startDate, $endDate);

        return round($grossProfit - $scrapLoss, 2);
    } 

    # Function to calculate sales revenue by channel for the current month, returning an array of channels with total revenue and order count for each channel
    protected function salesByChannelForCurrentMonth(): array
    {
        $today = $this->asOfDate();
        $startOfMonth = $today->startOfMonth()->startOfDay();
        $endOfMonth = $today->endOfDay();

        return SalesOrder::select('channel', DB::raw('SUM(grand_total) as total_revenue, COUNT(*) as total_orders'))
            ->whereBetween('ordered_at', [$startOfMonth, $endOfMonth])
            ->where('status', 'delivered')
            ->groupBy('channel')
            ->get()
            ->toArray();
    }

    protected function topSellingProductsForCurrentMonth(): array
    {
        $today = $this->asOfDate();
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
        $today = $this->asOfDate();
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
                    'employee_name' => $order->employee
                        ? $order->employee->first_name . ' ' . $order->employee->last_name
                        : 'Unknown',
                    'total_sales' => $order->total_sales,
                    'total_orders' => $order->total_orders,
                ];
            })
            ->toArray();
    }

    protected function getNumberOfOrdersByStatusForCurrentMonth(): array
    {
        $today = $this->asOfDate();
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
                    'stock_qty' => (int) $product->stock_qty,
                    'stock_status' => $this->getStockStatus((int) $product->stock_qty),
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

    protected function getCustomerBusinessMap(): array
    {
        return [
            'customers' => $this->getCountsByState('individual'),
            'businesses' => $this->getCountsByState('business'),
        ];
    }

protected function getCountsByState(string $customerType): array
{
    return Customer::select(DB::raw("TRIM(state) as state, COUNT(*) as total"))
        ->where('customer_type', $customerType)
        ->whereNotNull('state')
        ->where('state', '!=', '')
        ->groupBy('state')
        ->orderBy('state')
        ->get()
        ->map(function ($row) {
            return [
                'state' => $this->normalizeStateName($row->state),
                'count' => (int) $row->total,
            ];
        })
        ->filter(fn ($row) => !empty($row['state']))
        ->values()
        ->all();
}

protected function normalizeStateName(?string $state): ?string
{
    if (!$state) {
        return null;
    }

    $state = trim($state);

    $map = [
        'AL' => 'Alabama',
        'AK' => 'Alaska',
        'AZ' => 'Arizona',
        'AR' => 'Arkansas',
        'CA' => 'California',
        'CO' => 'Colorado',
        'CT' => 'Connecticut',
        'DE' => 'Delaware',
        'FL' => 'Florida',
        'GA' => 'Georgia',
        'HI' => 'Hawaii',
        'ID' => 'Idaho',
        'IL' => 'Illinois',
        'IN' => 'Indiana',
        'IA' => 'Iowa',
        'KS' => 'Kansas',
        'KY' => 'Kentucky',
        'LA' => 'Louisiana',
        'ME' => 'Maine',
        'MD' => 'Maryland',
        'MA' => 'Massachusetts',
        'MI' => 'Michigan',
        'MN' => 'Minnesota',
        'MS' => 'Mississippi',
        'MO' => 'Missouri',
        'MT' => 'Montana',
        'NE' => 'Nebraska',
        'NV' => 'Nevada',
        'NH' => 'New Hampshire',
        'NJ' => 'New Jersey',
        'NM' => 'New Mexico',
        'NY' => 'New York',
        'NC' => 'North Carolina',
        'ND' => 'North Dakota',
        'OH' => 'Ohio',
        'OK' => 'Oklahoma',
        'OR' => 'Oregon',
        'PA' => 'Pennsylvania',
        'RI' => 'Rhode Island',
        'SC' => 'South Carolina',
        'SD' => 'South Dakota',
        'TN' => 'Tennessee',
        'TX' => 'Texas',
        'UT' => 'Utah',
        'VT' => 'Vermont',
        'VA' => 'Virginia',
        'WA' => 'Washington',
        'WV' => 'West Virginia',
        'WI' => 'Wisconsin',
        'WY' => 'Wyoming',
    ];

    $upper = strtoupper($state);

    return $map[$upper] ?? $state;
}
}
