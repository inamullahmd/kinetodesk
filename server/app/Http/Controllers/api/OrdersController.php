<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\SalesOrder;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrdersController extends Controller
{
    private const AS_OF_DATE = '2026-03-31 23:59:59';

    public function stats(Request $request): JsonResponse
    {
        [$startDate, $endDate] = $this->resolveStatsDateRange($request);

        $summaryQuery = SalesOrder::query()
            ->where('sales_orders.ordered_at', '<=', $this->asOfDate())
            ->whereBetween('sales_orders.ordered_at', [$startDate, $endDate]);

        $summaryQuery = $this->applyCommonFilters($summaryQuery, $request, 'sales_orders');

        $totalOrders = (clone $summaryQuery)->count();
        $totalValue = round((float) (clone $summaryQuery)->sum('sales_orders.grand_total'), 2);

        $pendingOrders = (clone $summaryQuery)->where('sales_orders.status', 'pending')->count();
        $deliveredOrders = (clone $summaryQuery)->where('sales_orders.status', 'delivered')->count();
        $cancelledOrders = (clone $summaryQuery)->where('sales_orders.status', 'cancelled')->count();

        $statusCounts = (clone $summaryQuery)
            ->selectRaw('sales_orders.status as status, COUNT(*) as total_orders')
            ->groupBy('sales_orders.status')
            ->orderByDesc('total_orders')
            ->get()
            ->map(fn($row) => [
                'status' => $row->status,
                'total_orders' => (int) $row->total_orders,
            ])
            ->values()
            ->all();

        $channelCounts = (clone $summaryQuery)
            ->selectRaw('sales_orders.channel as channel, COUNT(*) as total_orders')
            ->groupBy('sales_orders.channel')
            ->orderByDesc('total_orders')
            ->get()
            ->map(fn($row) => [
                'channel' => $row->channel,
                'total_orders' => (int) $row->total_orders,
            ])
            ->values()
            ->all();

        $refundRow = DB::table('returns')
            ->join('sales_orders', 'returns.sales_order_id', '=', 'sales_orders.id')
            ->where('sales_orders.ordered_at', '<=', $this->asOfDate())
            ->whereBetween('sales_orders.ordered_at', [$startDate, $endDate]);

        $refundRow = $this->applyCommonFilters($refundRow, $request, 'sales_orders');

        $refundSummary = $refundRow
            ->selectRaw('COUNT(returns.id) as refund_count, COALESCE(SUM(returns.refund_amount), 0) as refund_value')
            ->first();

        return response()->json([
            'summary' => [
                'total_orders' => $totalOrders,
                'pending_orders' => $pendingOrders,
                'delivered_orders' => $deliveredOrders,
                'cancelled_orders' => $cancelledOrders,
                'total_value' => $totalValue,
                'refund_count' => (int) ($refundSummary->refund_count ?? 0),
                'refund_value' => round((float) ($refundSummary->refund_value ?? 0), 2),
            ],
            'summary_period' => [
                'label' => $startDate->format('F Y'),
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'status_counts' => $statusCounts,
            'channel_counts' => $channelCounts,
            'trend' => $this->getOrdersTrend($request),
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->integer('per_page', 25), 1), 100);

        $query = $this->buildOrdersListQuery($request);
        $sorts = $this->parseSorts($request);

        if (!empty($sorts)) {
            foreach ($sorts as $sort) {
                $this->applySort($query, $sort['key'], $sort['direction']);
            }
        } else {
            $query->orderByDesc('sales_orders.id');
        }

        $paginator = $query->paginate($perPage);

        $rows = collect($paginator->items())
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number ?: 'SO-' . str_pad((string) $order->id, 6, '0', STR_PAD_LEFT),
                    'ordered_at' => optional($order->ordered_at)->toDateTimeString(),
                    'customer_name' => $order->customer_sort_name ?: 'Unknown',
                    'channel' => $order->channel,
                    'status' => $order->status,
                    'grand_total' => round((float) $order->grand_total, 2),
                    'employee_name' => $order->employee_sort_name ?: 'Unassigned',
                ];
            })
            ->values();

        return response()->json([
            'data' => $rows,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ]);
    }

    protected function buildOrdersListQuery(Request $request): Builder
    {
        $customerSortSql = $this->customerSortSql();
        $employeeSortSql = $this->employeeSortSql();

        $query = SalesOrder::query()
            ->leftJoin('customers', 'sales_orders.customer_id', '=', 'customers.id')
            ->leftJoin('employees', 'sales_orders.employee_id', '=', 'employees.id')
            ->where('sales_orders.ordered_at', '<=', $this->asOfDate())
            ->select('sales_orders.*')
            ->selectRaw("{$customerSortSql} as customer_sort_name")
            ->selectRaw("{$employeeSortSql} as employee_sort_name");

        $query = $this->applyCommonFilters($query, $request, 'sales_orders');

        $search = trim($request->string('search')->toString());

        if ($search !== '') {
            $query->where(function ($inner) use ($search, $customerSortSql, $employeeSortSql) {
                $inner->where('sales_orders.order_number', 'like', "%{$search}%")
                    ->orWhere('sales_orders.status', 'like', "%{$search}%")
                    ->orWhere('sales_orders.channel', 'like', "%{$search}%")
                    ->orWhereRaw("{$customerSortSql} LIKE ?", ["%{$search}%"])
                    ->orWhereRaw("{$employeeSortSql} LIKE ?", ["%{$search}%"]);

                if (is_numeric($search)) {
                    $inner->orWhere('sales_orders.id', (int) $search);
                }
            });
        }

        return $query;
    }

    protected function applyCommonFilters($query, Request $request, string $table)
    {
        $status = trim($request->string('status')->toString());
        $channel = trim($request->string('channel')->toString());
        $dateFrom = trim($request->string('date_from')->toString());
        $dateTo = trim($request->string('date_to')->toString());

        if ($status !== '') {
            $query->where("{$table}.status", $status);
        }

        if ($channel !== '') {
            $query->where("{$table}.channel", $channel);
        }

        if ($dateFrom !== '') {
            $query->where("{$table}.ordered_at", '>=', CarbonImmutable::parse($dateFrom)->startOfDay());
        }

        if ($dateTo !== '') {
            $query->where("{$table}.ordered_at", '<=', CarbonImmutable::parse($dateTo)->endOfDay());
        }

        return $query;
    }

    protected function getOrdersTrend(Request $request): array
    {
        $trendEnd = $request->filled('date_to')
            ? CarbonImmutable::parse($request->string('date_to')->toString())->endOfDay()
            : $this->asOfDate()->endOfDay();

        if ($trendEnd->gt($this->asOfDate())) {
            $trendEnd = $this->asOfDate()->endOfDay();
        }

        $trendStart = $request->filled('date_from')
            ? CarbonImmutable::parse($request->string('date_from')->toString())->startOfDay()
            : $trendEnd->subDays(29)->startOfDay();

        $trendQuery = SalesOrder::query()
            ->where('sales_orders.ordered_at', '<=', $this->asOfDate())
            ->whereBetween('sales_orders.ordered_at', [$trendStart, $trendEnd]);

        $trendQuery = $this->applyCommonFilters($trendQuery, $request, 'sales_orders');

        $rows = $trendQuery
            ->selectRaw('DATE(sales_orders.ordered_at) as order_date, COUNT(*) as total_orders')
            ->groupBy(DB::raw('DATE(sales_orders.ordered_at)'))
            ->orderBy('order_date')
            ->get()
            ->keyBy('order_date');

        $trend = [];

        for ($date = $trendStart; $date->lte($trendEnd); $date = $date->addDay()) {
            $key = $date->toDateString();

            $trend[] = [
                'date' => $key,
                'label' => $date->format('M d'),
                'total_orders' => (int) ($rows[$key]->total_orders ?? 0),
            ];
        }

        return $trend;
    }

    protected function resolveStatsDateRange(Request $request): array
    {
        if ($request->filled('date_from') || $request->filled('date_to')) {
            $startDate = $request->filled('date_from')
                ? CarbonImmutable::parse($request->string('date_from')->toString())->startOfDay()
                : $this->asOfDate()->startOfMonth()->startOfDay();

            $endDate = $request->filled('date_to')
                ? CarbonImmutable::parse($request->string('date_to')->toString())->endOfDay()
                : $this->asOfDate()->endOfDay();

            if ($endDate->gt($this->asOfDate())) {
                $endDate = $this->asOfDate()->endOfDay();
            }

            return [$startDate, $endDate];
        }

        return [
            $this->asOfDate()->startOfMonth()->startOfDay(),
            $this->asOfDate()->endOfDay(),
        ];
    }

    protected function parseSorts(Request $request): array
    {
        $allowed = [
            'order_number',
            'ordered_at',
            'customer_name',
            'channel',
            'status',
            'grand_total',
            'employee_name',
        ];

        $sortParam = trim($request->string('sort')->toString());

        if ($sortParam === '') {
            return [];
        }

        $tokens = array_filter(array_map('trim', explode(',', $sortParam)));
        $sorts = [];
        $seen = [];

        foreach ($tokens as $token) {
            $direction = str_starts_with($token, '-') ? 'desc' : 'asc';
            $key = ltrim($token, '+-');

            if (!in_array($key, $allowed, true)) {
                continue;
            }

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;

            $sorts[] = [
                'key' => $key,
                'direction' => $direction,
            ];

            if (count($sorts) === 2) {
                break;
            }
        }

        return $sorts;
    }

    protected function applySort(Builder $query, string $key, string $direction): void
    {
        switch ($key) {
            case 'order_number':
                $query->orderBy('sales_orders.order_number', $direction);
                break;

            case 'ordered_at':
                $query->orderBy('sales_orders.ordered_at', $direction);
                break;

            case 'customer_name':
                $query->orderBy('customer_sort_name', $direction);
                break;

            case 'channel':
                $query->orderBy('sales_orders.channel', $direction);
                break;

            case 'status':
                $query->orderBy('sales_orders.status', $direction);
                break;

            case 'grand_total':
                $query->orderBy('sales_orders.grand_total', $direction);
                break;

            case 'employee_name':
                $query->orderBy('employee_sort_name', $direction);
                break;

            default:
                $query->orderBy('sales_orders.ordered_at', 'desc');
                break;
        }
    }

    protected function customerSortSql(): string
    {
        return "COALESCE(
            NULLIF(TRIM(customers.business_name), ''),
            NULLIF(TRIM(CONCAT(COALESCE(customers.first_name, ''), ' ', COALESCE(customers.last_name, ''))), ''),
            NULLIF(TRIM(customers.email), '')
        )";
    }

    protected function employeeSortSql(): string
    {
        return "NULLIF(TRIM(CONCAT(COALESCE(employees.first_name, ''), ' ', COALESCE(employees.last_name, ''))), '')";
    }

    protected function asOfDate(): CarbonImmutable
    {
        return CarbonImmutable::parse(self::AS_OF_DATE);
    }
}
