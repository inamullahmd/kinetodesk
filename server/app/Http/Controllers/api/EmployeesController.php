<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Support\AppliesMultiColumnSorting;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmployeesController extends Controller
{
    use AppliesMultiColumnSorting;

    public function index(Request $request): JsonResponse
    {
        $pagination = $this->resolvePagination($request);

        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'role' => trim((string) $request->query('role', 'all')),
            'status' => trim((string) $request->query('status', 'all')),
        ];

        $payload = $this->getEmployeeRows($request, $filters, $pagination);

        return response()->json([
            'summary' => $this->getSummary(),
            'employees' => $payload['rows'],
            'pagination' => $payload['pagination'],
            'filterOptions' => [
                'roles' => $this->roleOptions(),
                'statuses' => ['active', 'inactive'],
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $employee = DB::table('employees')
            ->where('employees.id', $id)
            ->select($this->employeeSelectColumns())
            ->first();

        if (! $employee) {
            return response()->json([
                'message' => 'Employee not found.',
            ], 404);
        }

        return response()->json([
            'employee' => $this->mapEmployeeBase($employee),
            'metrics' => $this->getEmployeeMetrics($id),
            'recentSalesOrders' => $this->getEmployeeRecentSalesOrders($id),
            'topProducts' => $this->getEmployeeTopProducts($id),
            'commissionPayouts' => $this->getEmployeeCommissionPayouts($id),
        ]);
    }

    protected function resolvePagination(Request $request): array
    {
        $page = max((int) $request->query('page', 1), 1);
        $perPage = (int) $request->query('perPage', 10);
        $perPage = max(min($perPage, 50), 5);

        return [
            'page' => $page,
            'perPage' => $perPage,
            'offset' => ($page - 1) * $perPage,
        ];
    }

    protected function employeeSelectColumns(): array
    {
        return [
            'employees.id',
            'employees.employee_number',
            'employees.first_name',
            'employees.last_name',
            'employees.email',
            'employees.phone_number',
            'employees.role',
            'employees.created_at',
            DB::raw("CASE WHEN employees.is_active = 1 THEN 'active' ELSE 'inactive' END as status"),
        ];
    }

    protected function salesStatsSubquery()
    {
        return DB::table('sales_order_items')
            ->join('sales_orders', 'sales_order_items.sales_order_id', '=', 'sales_orders.id')
            ->whereNotNull('sales_order_items.employee_id')
            ->select([
                'sales_order_items.employee_id',
                DB::raw('COUNT(DISTINCT sales_order_items.sales_order_id) as sales_order_count'),
                DB::raw('SUM(sales_order_items.qty) as units_sold'),
                DB::raw('SUM(sales_order_items.line_total) as total_revenue'),
                DB::raw('SUM(sales_order_items.line_profit) as total_profit'),
                DB::raw('SUM(sales_order_items.commission_total) as total_commission'),
                DB::raw('AVG(sales_order_items.line_total) as average_line_value'),
                DB::raw('MAX(sales_orders.ordered_at) as last_sale_at'),
            ])
            ->groupBy('sales_order_items.employee_id');
    }

    protected function payoutStatsSubquery()
    {
        return DB::table('commission_payouts')
            ->select([
                'employee_id',
                DB::raw("SUM(CASE WHEN status = 'pending' THEN total_commission ELSE 0 END) as pending_commission"),
                DB::raw("SUM(CASE WHEN status = 'paid' THEN total_commission ELSE 0 END) as paid_commission"),
                DB::raw('MAX(paid_at) as last_paid_at'),
            ])
            ->groupBy('employee_id');
    }

    protected function baseEmployeeRowsQuery()
    {
        return DB::table('employees')
            ->leftJoinSub($this->salesStatsSubquery(), 'sales_stats', function ($join) {
                $join->on('employees.id', '=', 'sales_stats.employee_id');
            })
            ->leftJoinSub($this->payoutStatsSubquery(), 'payout_stats', function ($join) {
                $join->on('employees.id', '=', 'payout_stats.employee_id');
            });
    }

    protected function getEmployeeRows(Request $request, array $filters, array $pagination): array
    {
        $query = $this->baseEmployeeRowsQuery();

        $this->applySearch($query, $filters['search'] ?? '');
        $this->applyRoleFilter($query, $filters['role'] ?? 'all');
        $this->applyStatusFilter($query, $filters['status'] ?? 'all');

        $total = (clone $query)->count('employees.id');

        $rowsQuery = $query->select([
            ...$this->employeeSelectColumns(),
            DB::raw('COALESCE(sales_stats.sales_order_count, 0) as sales_order_count'),
            DB::raw('COALESCE(sales_stats.units_sold, 0) as units_sold'),
            DB::raw('COALESCE(sales_stats.total_revenue, 0) as total_revenue'),
            DB::raw('COALESCE(sales_stats.total_profit, 0) as total_profit'),
            DB::raw('COALESCE(sales_stats.total_commission, 0) as total_commission'),
            DB::raw('COALESCE(sales_stats.average_line_value, 0) as average_line_value'),
            'sales_stats.last_sale_at',
            DB::raw('COALESCE(payout_stats.pending_commission, 0) as pending_commission'),
            DB::raw('COALESCE(payout_stats.paid_commission, 0) as paid_commission'),
            'payout_stats.last_paid_at',
        ]);

        $this->applySorts($rowsQuery, $request, $this->employeeAllowedSorts(), [
            ['field' => 'totalRevenue', 'direction' => 'desc'],
        ]);

        $rows = $rowsQuery
            ->orderBy('employees.id')
            ->offset($pagination['offset'])
            ->limit($pagination['perPage'])
            ->get()
            ->map(function ($row) {
                return [
                    ...$this->mapEmployeeBase($row),
                    'salesOrderCount' => (int) $row->sales_order_count,
                    'unitsSold' => (int) $row->units_sold,
                    'totalRevenue' => round((float) $row->total_revenue, 2),
                    'totalProfit' => round((float) $row->total_profit, 2),
                    'totalCommission' => round((float) $row->total_commission, 2),
                    'averageLineValue' => round((float) $row->average_line_value, 2),
                    'pendingCommission' => round((float) $row->pending_commission, 2),
                    'paidCommission' => round((float) $row->paid_commission, 2),
                    'lastSaleAt' => $this->dateString($row->last_sale_at),
                    'lastPaidAt' => $this->dateString($row->last_paid_at),
                ];
            })
            ->toArray();

        return [
            'rows' => $rows,
            'pagination' => $this->paginationPayload($pagination['page'], $pagination['perPage'], $total),
        ];
    }

    protected function employeeAllowedSorts(): array
    {
        return [
            'employeeNumber' => 'employees.employee_number',
            'name' => function ($query, string $direction) {
                $query
                    ->orderBy('employees.last_name', $direction)
                    ->orderBy('employees.first_name', $direction);
            },
            'role' => 'employees.role',
            'salesOrderCount' => 'sales_order_count',
            'unitsSold' => 'units_sold',
            'totalRevenue' => 'total_revenue',
            'totalProfit' => 'total_profit',
            'totalCommission' => 'total_commission',
            'pendingCommission' => 'pending_commission',
            'lastSaleAt' => function ($query, string $direction) {
                $this->orderByNullableDate($query, 'sales_stats.last_sale_at', $direction);
            },
            'status' => 'employees.is_active',
        ];
    }

    protected function applySearch($query, string $search): void
    {
        if ($search === '') {
            return;
        }

        $like = '%' . $search . '%';

        $query->where(function ($query) use ($like) {
            $query->where('employees.employee_number', 'like', $like)
                ->orWhere('employees.first_name', 'like', $like)
                ->orWhere('employees.last_name', 'like', $like)
                ->orWhere('employees.email', 'like', $like)
                ->orWhere('employees.phone_number', 'like', $like)
                ->orWhere('employees.role', 'like', $like);
        });
    }

    protected function applyRoleFilter($query, string $role): void
    {
        if ($role === '' || $role === 'all') {
            return;
        }

        $query->where('employees.role', $role);
    }

    protected function applyStatusFilter($query, string $status): void
    {
        if (! in_array($status, ['active', 'inactive'], true)) {
            return;
        }

        $query->where('employees.is_active', $status === 'active');
    }

    protected function mapEmployeeBase($row): array
    {
        $fullName = trim((string) ($row->first_name ?? '') . ' ' . (string) ($row->last_name ?? ''));

        return [
            'id' => (int) $row->id,
            'employeeNumber' => $row->employee_number,
            'firstName' => $row->first_name,
            'lastName' => $row->last_name,
            'displayName' => $fullName ?: 'Unnamed employee',
            'email' => $row->email,
            'phone' => $row->phone_number,
            'role' => $row->role,
            'status' => $row->status ?? 'active',
            'createdAt' => $this->dateString($row->created_at),
        ];
    }

    protected function getSummary(): array
    {
        $employeeRow = DB::table('employees')
            ->select([
                DB::raw('COUNT(*) as total_employees'),
                DB::raw('SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_employees'),
                DB::raw("SUM(CASE WHEN role = 'sales_representative' THEN 1 ELSE 0 END) as sales_representatives"),
            ])
            ->first();

        $salesRow = DB::table('sales_order_items')
            ->whereNotNull('employee_id')
            ->select([
                DB::raw('COUNT(DISTINCT sales_order_id) as sales_order_count'),
                DB::raw('SUM(line_total) as total_revenue'),
                DB::raw('SUM(line_profit) as total_profit'),
                DB::raw('SUM(commission_total) as total_commission'),
                DB::raw('SUM(qty) as units_sold'),
            ])
            ->first();

        $pendingCommission = DB::table('commission_payouts')
            ->where('status', 'pending')
            ->sum('total_commission');

        return [
            'totalEmployees' => (int) ($employeeRow->total_employees ?? 0),
            'activeEmployees' => (int) ($employeeRow->active_employees ?? 0),
            'salesRepresentatives' => (int) ($employeeRow->sales_representatives ?? 0),
            'salesOrderCount' => (int) ($salesRow->sales_order_count ?? 0),
            'unitsSold' => (int) ($salesRow->units_sold ?? 0),
            'totalRevenue' => round((float) ($salesRow->total_revenue ?? 0), 2),
            'totalProfit' => round((float) ($salesRow->total_profit ?? 0), 2),
            'totalCommission' => round((float) ($salesRow->total_commission ?? 0), 2),
            'pendingCommission' => round((float) ($pendingCommission ?? 0), 2),
        ];
    }

    protected function getEmployeeMetrics(int $employeeId): array
    {
        $salesRow = DB::table('sales_order_items')
            ->join('sales_orders', 'sales_order_items.sales_order_id', '=', 'sales_orders.id')
            ->where('sales_order_items.employee_id', $employeeId)
            ->select([
                DB::raw('COUNT(DISTINCT sales_order_items.sales_order_id) as sales_order_count'),
                DB::raw('SUM(sales_order_items.qty) as units_sold'),
                DB::raw('SUM(sales_order_items.line_total) as total_revenue'),
                DB::raw('SUM(sales_order_items.line_profit) as total_profit'),
                DB::raw('SUM(sales_order_items.commission_total) as total_commission'),
                DB::raw('AVG(sales_order_items.line_total) as average_line_value'),
                DB::raw('MAX(sales_orders.ordered_at) as last_sale_at'),
            ])
            ->first();

        $payoutRow = DB::table('commission_payouts')
            ->where('employee_id', $employeeId)
            ->select([
                DB::raw("SUM(CASE WHEN status = 'pending' THEN total_commission ELSE 0 END) as pending_commission"),
                DB::raw("SUM(CASE WHEN status = 'paid' THEN total_commission ELSE 0 END) as paid_commission"),
                DB::raw('MAX(paid_at) as last_paid_at'),
            ])
            ->first();

        return [
            'salesOrderCount' => (int) ($salesRow->sales_order_count ?? 0),
            'unitsSold' => (int) ($salesRow->units_sold ?? 0),
            'totalRevenue' => round((float) ($salesRow->total_revenue ?? 0), 2),
            'totalProfit' => round((float) ($salesRow->total_profit ?? 0), 2),
            'totalCommission' => round((float) ($salesRow->total_commission ?? 0), 2),
            'averageLineValue' => round((float) ($salesRow->average_line_value ?? 0), 2),
            'pendingCommission' => round((float) ($payoutRow->pending_commission ?? 0), 2),
            'paidCommission' => round((float) ($payoutRow->paid_commission ?? 0), 2),
            'lastSaleAt' => $this->dateString($salesRow->last_sale_at ?? null),
            'lastPaidAt' => $this->dateString($payoutRow->last_paid_at ?? null),
        ];
    }

    protected function getEmployeeRecentSalesOrders(int $employeeId): array
    {
        return DB::table('sales_order_items')
            ->join('sales_orders', 'sales_order_items.sales_order_id', '=', 'sales_orders.id')
            ->leftJoin('customers', 'sales_orders.customer_id', '=', 'customers.id')
            ->where('sales_order_items.employee_id', $employeeId)
            ->select([
                'sales_orders.id',
                'sales_orders.so_number',
                'sales_orders.channel',
                'sales_orders.status',
                'sales_orders.payment_status',
                'sales_orders.ordered_at',
                DB::raw("COALESCE(NULLIF(customers.business_name, ''), NULLIF(TRIM(CONCAT(COALESCE(customers.first_name, ''), ' ', COALESCE(customers.last_name, ''))), ''), sales_orders.contact_name) as customer_name"),
                DB::raw('SUM(sales_order_items.qty) as quantity'),
                DB::raw('SUM(sales_order_items.line_total) as total_value'),
                DB::raw('SUM(sales_order_items.line_profit) as profit'),
                DB::raw('SUM(sales_order_items.commission_total) as commission'),
            ])
            ->groupBy(
                'sales_orders.id',
                'sales_orders.so_number',
                'sales_orders.channel',
                'sales_orders.status',
                'sales_orders.payment_status',
                'sales_orders.ordered_at',
                'customers.business_name',
                'customers.first_name',
                'customers.last_name',
                'sales_orders.contact_name'
            )
            ->orderByDesc('sales_orders.ordered_at')
            ->limit(12)
            ->get()
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'orderNumber' => $row->so_number,
                'customerName' => $row->customer_name,
                'channel' => $row->channel,
                'status' => $row->status,
                'paymentStatus' => $row->payment_status,
                'orderedAt' => $this->dateString($row->ordered_at),
                'quantity' => (int) $row->quantity,
                'totalValue' => round((float) $row->total_value, 2),
                'profit' => round((float) $row->profit, 2),
                'commission' => round((float) $row->commission, 2),
            ])
            ->toArray();
    }

    protected function getEmployeeTopProducts(int $employeeId): array
    {
        return DB::table('sales_order_items')
            ->join('products', 'sales_order_items.product_id', '=', 'products.id')
            ->leftJoin('brands', 'products.brand_id', '=', 'brands.id')
            ->where('sales_order_items.employee_id', $employeeId)
            ->select([
                'products.id',
                'products.title',
                'products.internal_sku',
                'products.model_number',
                'brands.name as brand_name',
                DB::raw('SUM(sales_order_items.qty) as quantity'),
                DB::raw('SUM(sales_order_items.line_total) as total_value'),
                DB::raw('SUM(sales_order_items.line_profit) as profit'),
                DB::raw('SUM(sales_order_items.commission_total) as commission'),
            ])
            ->groupBy('products.id', 'products.title', 'products.internal_sku', 'products.model_number', 'brands.name')
            ->orderByDesc('total_value')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'title' => $row->title,
                'sku' => $row->internal_sku,
                'modelNumber' => $row->model_number,
                'brandName' => $row->brand_name,
                'quantity' => (int) $row->quantity,
                'totalValue' => round((float) $row->total_value, 2),
                'profit' => round((float) $row->profit, 2),
                'commission' => round((float) $row->commission, 2),
            ])
            ->toArray();
    }

    protected function getEmployeeCommissionPayouts(int $employeeId): array
    {
        return DB::table('commission_payouts')
            ->where('employee_id', $employeeId)
            ->select([
                'id',
                'period_start',
                'period_end',
                'total_commission',
                'status',
                'paid_at',
            ])
            ->orderByDesc('period_end')
            ->limit(8)
            ->get()
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'periodStart' => $this->dateString($row->period_start),
                'periodEnd' => $this->dateString($row->period_end),
                'totalCommission' => round((float) $row->total_commission, 2),
                'status' => $row->status,
                'paidAt' => $this->dateString($row->paid_at),
            ])
            ->toArray();
    }

    protected function roleOptions(): array
    {
        return DB::table('employees')
            ->select('role')
            ->distinct()
            ->orderBy('role')
            ->pluck('role')
            ->values()
            ->toArray();
    }

    protected function paginationPayload(int $page, int $perPage, int $total): array
    {
        $lastPage = max((int) ceil($total / $perPage), 1);

        return [
            'currentPage' => $page,
            'perPage' => $perPage,
            'total' => $total,
            'lastPage' => $lastPage,
            'from' => $total === 0 ? 0 : (($page - 1) * $perPage) + 1,
            'to' => min($page * $perPage, $total),
        ];
    }

    protected function dateString($value): ?string
    {
        return $value ? CarbonImmutable::parse($value)->toDateString() : null;
    }
}