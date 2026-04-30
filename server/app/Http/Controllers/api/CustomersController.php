<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CustomersController extends Controller
{
    private const CUSTOMER_LIFESPAN_YEARS = 3;

    private const US_STATES = [
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

    public function index(Request $request): JsonResponse
    {
        $type = $this->resolveCustomerType($request);
        $pagination = $this->resolvePagination($request);

        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'state' => strtoupper(trim((string) $request->query('state', 'all'))),
            'status' => trim((string) $request->query('status', 'all')),
        ];

        $customersPayload = $this->getCustomerRows($type, $filters, $pagination);
        $stateDistribution = $this->getStateDistribution($type);

        return response()->json([
            'type' => $type,
            'summary' => $this->getSummary($type),
            'stateDistribution' => $stateDistribution,
            'customers' => $customersPayload['rows'],
            'pagination' => $customersPayload['pagination'],
            'filterOptions' => [
                'states' => $this->stateOptions(),
                'statuses' => ['active', 'inactive'],
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $customer = DB::table('customers')
            ->where('customers.id', $id)
            ->select($this->customerSelectColumns())
            ->first();

        if (!$customer) {
            return response()->json([
                'message' => 'Customer not found.',
            ], 404);
        }

        $type = $this->rowCustomerType($customer);

        return response()->json([
            'customer' => $this->mapCustomerBase($customer, $type),
            'metrics' => $this->getCustomerMetrics($id),
            'recentOrders' => $this->getCustomerRecentOrders($id),
            'topProduct' => $this->getCustomerTopProduct($id),
            'favoriteChannel' => $this->getCustomerFavoriteChannel($id),
        ]);
    }

    protected function resolveCustomerType(Request $request): string
    {
        $type = (string) $request->query('customerType', 'individual');

        return in_array($type, ['individual', 'business'], true) ? $type : 'individual';
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

    protected function customerActiveColumn(): ?string
    {
        if (Schema::hasColumn('customers', 'is_active')) {
            return 'is_active';
        }

        if (Schema::hasColumn('customers', 'active')) {
            return 'active';
        }

        return null;
    }

    protected function statusSelectExpression()
    {
        $activeColumn = $this->customerActiveColumn();

        if (!$activeColumn) {
            return DB::raw("'active' as status");
        }

        return DB::raw("CASE WHEN customers.{$activeColumn} = 1 THEN 'active' ELSE 'inactive' END as status");
    }

    protected function applyCustomerType($query, string $type): void
    {
        if ($type === 'business') {
            $query->whereNotNull('customers.business_name')
                ->where('customers.business_name', '<>', '');

            return;
        }

        $query->where(function ($query) {
            $query->whereNull('customers.business_name')
                ->orWhere('customers.business_name', '');
        });
    }

    protected function applyStatusFilter($query, string $status): void
    {
        if (!in_array($status, ['active', 'inactive'], true)) {
            return;
        }

        $activeColumn = $this->customerActiveColumn();

        if (!$activeColumn) {
            return;
        }

        $query->where("customers.{$activeColumn}", $status === 'active');
    }

    protected function applyStateFilter($query, string $state): void
    {
        if ($state === '' || $state === 'ALL') {
            return;
        }

        $stateName = self::US_STATES[$state] ?? null;

        $query->where(function ($query) use ($state, $stateName) {
            $query->whereRaw('UPPER(customers.state) = ?', [$state]);

            if ($stateName) {
                $query->orWhereRaw('LOWER(customers.state) = ?', [strtolower($stateName)]);
            }
        });
    }

    protected function applySearch($query, string $search): void
    {
        if ($search === '') {
            return;
        }

        $like = '%' . $search . '%';

        $query->where(function ($query) use ($like) {
            $query->where('customers.first_name', 'like', $like)
                ->orWhere('customers.last_name', 'like', $like)
                ->orWhere('customers.business_name', 'like', $like)
                ->orWhere('customers.email', 'like', $like)
                ->orWhere('customers.phone', 'like', $like)
                ->orWhere('customers.city', 'like', $like)
                ->orWhere('customers.state', 'like', $like);
        });
    }

    protected function lifetimeStatsSubquery()
    {
        return DB::table('sales_orders')
            ->where('sales_orders.status', 'delivered')
            ->select([
                'sales_orders.customer_id',
                DB::raw('COUNT(*) as order_count'),
                DB::raw('SUM(sales_orders.grand_total) as total_revenue'),
                DB::raw('AVG(sales_orders.grand_total) as average_order_value'),
                DB::raw('MIN(sales_orders.ordered_at) as first_order_at'),
                DB::raw('MAX(sales_orders.ordered_at) as last_order_at'),
            ])
            ->groupBy('sales_orders.customer_id');
    }

    protected function customerSelectColumns(): array
    {
        return [
            'customers.id',
            'customers.first_name',
            'customers.last_name',
            'customers.business_name',
            'customers.email',
            'customers.phone',
            'customers.address_line_1',
            'customers.address_line_2',
            'customers.city',
            'customers.state',
            'customers.postal_code',
            'customers.country',
            'customers.created_at',
            $this->statusSelectExpression(),
        ];
    }

    protected function getCustomerRows(
        string $type,
        array $filters,
        array $pagination
    ): array {
        $lifetimeStats = $this->lifetimeStatsSubquery();

        $query = DB::table('customers')
            ->leftJoinSub($lifetimeStats, 'lifetime_stats', function ($join) {
                $join->on('customers.id', '=', 'lifetime_stats.customer_id');
            });

        $this->applyCustomerType($query, $type);
        $this->applySearch($query, $filters['search'] ?? '');
        $this->applyStateFilter($query, $filters['state'] ?? 'all');
        $this->applyStatusFilter($query, $filters['status'] ?? 'all');

        $total = (clone $query)->count('customers.id');

        $rows = $query
            ->select([
                ...$this->customerSelectColumns(),
                DB::raw('COALESCE(lifetime_stats.order_count, 0) as order_count'),
                DB::raw('COALESCE(lifetime_stats.total_revenue, 0) as total_revenue'),
                DB::raw('COALESCE(lifetime_stats.average_order_value, 0) as average_order_value'),
                'lifetime_stats.first_order_at',
                'lifetime_stats.last_order_at',
            ])
            ->orderByDesc('lifetime_stats.total_revenue')
            ->orderBy('customers.business_name')
            ->orderBy('customers.last_name')
            ->orderBy('customers.first_name')
            ->offset($pagination['offset'])
            ->limit($pagination['perPage'])
            ->get()
            ->map(function ($row) use ($type) {
                return [
                    ...$this->mapCustomerBase($row, $type),
                    'orderCount' => (int) $row->order_count,
                    'totalRevenue' => round((float) $row->total_revenue, 2),
                    'averageOrderValue' => round((float) $row->average_order_value, 2),
                    'lastOrderAt' => $row->last_order_at ? CarbonImmutable::parse($row->last_order_at)->toDateString() : null,
                    'firstOrderAt' => $row->first_order_at ? CarbonImmutable::parse($row->first_order_at)->toDateString() : null,
                ];
            })
            ->toArray();

        return [
            'rows' => $rows,
            'pagination' => $this->paginationPayload($pagination['page'], $pagination['perPage'], $total),
        ];
    }

    protected function mapCustomerBase($row, string $type): array
    {
        $firstLast = trim((string) ($row->first_name ?? '') . ' ' . (string) ($row->last_name ?? ''));

        $displayName = $type === 'business'
            ? ($row->business_name ?: $firstLast ?: 'Unknown business')
            : ($firstLast ?: $row->business_name ?: 'Unknown customer');

        $contactName = $type === 'business'
            ? ($firstLast ?: null)
            : null;

        $stateCode = $this->normalizeStateCode($row->state ?? null);

        return [
            'id' => (int) $row->id,
            'type' => $type,
            'displayName' => $displayName,
            'contactName' => $contactName,
            'email' => $row->email,
            'phone' => $row->phone,
            'addressLine1' => $row->address_line_1 ?? null,
            'addressLine2' => $row->address_line_2 ?? null,
            'city' => $row->city,
            'state' => $row->state,
            'stateCode' => $stateCode,
            'postalCode' => $row->postal_code ?? null,
            'country' => $row->country ?? null,
            'status' => $row->status ?? 'active',
            'createdAt' => $row->created_at ? CarbonImmutable::parse($row->created_at)->toDateString() : null,
        ];
    }

    protected function rowCustomerType($row): string
    {
        return trim((string) ($row->business_name ?? '')) !== '' ? 'business' : 'individual';
    }

    protected function getSummary(string $type): array
    {
        $baseCustomers = DB::table('customers');
        $this->applyCustomerType($baseCustomers, $type);

        $totalCustomers = (clone $baseCustomers)->count();

        $salesQuery = DB::table('sales_orders')
            ->join('customers', 'sales_orders.customer_id', '=', 'customers.id')
            ->where('sales_orders.status', 'delivered');

        $this->applyCustomerType($salesQuery, $type);

        $salesRow = (clone $salesQuery)
            ->select([
                DB::raw('COUNT(*) as order_count'),
                DB::raw('COUNT(DISTINCT customers.id) as buying_customer_count'),
                DB::raw('SUM(sales_orders.grand_total) as revenue'),
            ])
            ->first();

        $repeatBuyersQuery = DB::table('customers')
            ->join('sales_orders', 'customers.id', '=', 'sales_orders.customer_id')
            ->where('sales_orders.status', 'delivered');

        $this->applyCustomerType($repeatBuyersQuery, $type);

        $repeatBuyers = DB::query()
            ->fromSub(
                $repeatBuyersQuery
                    ->select([
                        'customers.id',
                        DB::raw('COUNT(sales_orders.id) as order_count'),
                    ])
                    ->groupBy('customers.id'),
                'repeat_source'
            )
            ->where('order_count', '>=', 2)
            ->count();

        $orderCount = (int) ($salesRow->order_count ?? 0);
        $buyingCustomerCount = (int) ($salesRow->buying_customer_count ?? 0);
        $revenue = (float) ($salesRow->revenue ?? 0);

        $averagePurchaseValue = $orderCount > 0
            ? $revenue / $orderCount
            : 0;

        $averagePurchaseFrequency = $buyingCustomerCount > 0
            ? $orderCount / $buyingCustomerCount
            : 0;

        $averageClv = $averagePurchaseValue
            * $averagePurchaseFrequency
            * self::CUSTOMER_LIFESPAN_YEARS;

        return [
            'totalCustomers' => (int) $totalCustomers,
            'repeatCustomers' => (int) $repeatBuyers,
            'customerRevenue' => round($revenue, 2),
            'averageOrderValue' => round($averagePurchaseValue, 2),
            'averageClv' => round($averageClv, 2),
            'clvAssumptionYears' => self::CUSTOMER_LIFESPAN_YEARS,
        ];
    }

    protected function getStateDistribution(string $type): array
    {
        $source = DB::table('sales_orders')
            ->join('customers', 'sales_orders.customer_id', '=', 'customers.id')
            ->where('sales_orders.status', 'delivered')
            ->select([
                'sales_orders.id as order_id',
                'sales_orders.customer_id',
                'sales_orders.grand_total',
                DB::raw("COALESCE(NULLIF(customers.state, ''), NULLIF(sales_orders.delivery_state, ''), 'Unknown') as raw_state"),
            ]);

        $this->applyCustomerType($source, $type);

        $rows = DB::query()
            ->fromSub($source, 'state_source')
            ->select([
                'raw_state',
                DB::raw('COUNT(DISTINCT customer_id) as customer_count'),
                DB::raw('COUNT(order_id) as order_count'),
                DB::raw('SUM(grand_total) as revenue'),
            ])
            ->groupBy('raw_state')
            ->get();

        $states = [];

        foreach ($rows as $row) {
            $stateCode = $this->normalizeStateCode($row->raw_state);

            if (!$stateCode) {
                continue;
            }

            if (!isset($states[$stateCode])) {
                $states[$stateCode] = [
                    'state' => self::US_STATES[$stateCode],
                    'stateCode' => $stateCode,
                    'customerCount' => 0,
                    'orderCount' => 0,
                    'revenue' => 0,
                ];
            }

            $states[$stateCode]['customerCount'] += (int) $row->customer_count;
            $states[$stateCode]['orderCount'] += (int) $row->order_count;
            $states[$stateCode]['revenue'] += (float) $row->revenue;
        }

        $values = array_values(array_map(function ($row) {
            return [
                ...$row,
                'revenue' => round((float) $row['revenue'], 2),
            ];
        }, $states));

        usort($values, function ($a, $b) {
            return $b['customerCount'] <=> $a['customerCount'];
        });

        return $values;
    }

    protected function getCustomerMetrics(int $customerId): array
    {
        $row = DB::table('sales_orders')
            ->where('customer_id', $customerId)
            ->where('status', 'delivered')
            ->select([
                DB::raw('COUNT(*) as order_count'),
                DB::raw('SUM(grand_total) as total_revenue'),
                DB::raw('AVG(grand_total) as average_order_value'),
                DB::raw('MIN(ordered_at) as first_order_at'),
                DB::raw('MAX(ordered_at) as last_order_at'),
            ])
            ->first();

        return [
            'orderCount' => (int) ($row->order_count ?? 0),
            'totalRevenue' => round((float) ($row->total_revenue ?? 0), 2),
            'averageOrderValue' => round((float) ($row->average_order_value ?? 0), 2),
            'firstOrderAt' => $row?->first_order_at ? CarbonImmutable::parse($row->first_order_at)->toDateString() : null,
            'lastOrderAt' => $row?->last_order_at ? CarbonImmutable::parse($row->last_order_at)->toDateString() : null,
        ];
    }

    protected function getCustomerRecentOrders(int $customerId): array
    {
        return DB::table('sales_orders')
            ->where('customer_id', $customerId)
            ->select([
                'id',
                'so_number',
                'channel',
                'status',
                'payment_status',
                'ordered_at',
                'grand_total',
            ])
            ->orderByDesc('ordered_at')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'orderNumber' => $row->so_number,
                'channel' => $row->channel,
                'status' => $row->status,
                'paymentStatus' => $row->payment_status,
                'orderedAt' => $row->ordered_at ? CarbonImmutable::parse($row->ordered_at)->toDateString() : null,
                'totalValue' => round((float) $row->grand_total, 2),
            ])
            ->toArray();
    }

    protected function getCustomerTopProduct(int $customerId): ?array
    {
        $row = DB::table('sales_order_items')
            ->join('sales_orders', 'sales_order_items.sales_order_id', '=', 'sales_orders.id')
            ->join('products', 'sales_order_items.product_id', '=', 'products.id')
            ->where('sales_orders.customer_id', $customerId)
            ->where('sales_orders.status', 'delivered')
            ->select([
                'products.id',
                'products.title',
                DB::raw('SUM(sales_order_items.qty) as total_qty'),
                DB::raw('SUM(sales_order_items.line_total) as total_value'),
            ])
            ->groupBy('products.id', 'products.title')
            ->orderByDesc('total_qty')
            ->first();

        if (!$row) {
            return null;
        }

        return [
            'id' => (int) $row->id,
            'title' => $row->title,
            'quantity' => (int) $row->total_qty,
            'totalValue' => round((float) $row->total_value, 2),
        ];
    }

    protected function getCustomerFavoriteChannel(int $customerId): ?array
    {
        $row = DB::table('sales_orders')
            ->where('customer_id', $customerId)
            ->where('status', 'delivered')
            ->select([
                'channel',
                DB::raw('COUNT(*) as order_count'),
                DB::raw('SUM(grand_total) as revenue'),
            ])
            ->groupBy('channel')
            ->orderByDesc('order_count')
            ->first();

        if (!$row) {
            return null;
        }

        return [
            'channel' => $row->channel,
            'orderCount' => (int) $row->order_count,
            'revenue' => round((float) $row->revenue, 2),
        ];
    }

    protected function normalizeStateCode(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        $normalized = strtoupper(trim($value));

        if (isset(self::US_STATES[$normalized])) {
            return $normalized;
        }

        $lower = strtolower(trim($value));

        foreach (self::US_STATES as $code => $name) {
            if (strtolower($name) === $lower) {
                return $code;
            }
        }

        return null;
    }

    protected function stateOptions(): array
    {
        return array_map(
            fn ($code, $name) => [
                'code' => $code,
                'name' => $name,
            ],
            array_keys(self::US_STATES),
            array_values(self::US_STATES)
        );
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
}