<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class OrdersController extends Controller
{
    private const MAX_REPORT_DATE = '2026-03-31';

    private const SALES_STATUSES = [
        'pending',
        'confirmed',
        'shipped',
        'delivered',
        'cancelled',
        'returned',
        'refunded',
    ];

    private const PURCHASE_STATUSES = [
        'draft',
        'issued',
        'transit',
        'fulfilled',
        'cancelled',
    ];

    private const SALES_CHANNELS = [
        'online',
        'in_store',
        'phone',
    ];

    public function __invoke(Request $request): JsonResponse
    {
        $type = $this->resolveType($request);
        [$startDate, $endDate] = $this->resolveDateRange($request);

        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'statuses' => $this->arrayQuery($request, 'statuses'),
            'channels' => $this->arrayQuery($request, 'channels'),
            'suppliers' => $this->arrayQuery($request, 'suppliers'),
        ];

        $pagination = $this->resolvePagination($request);

        $ordersPayload = $type === 'sales'
            ? $this->getRecentSalesOrders($startDate, $endDate, $filters, $pagination)
            : $this->getRecentPurchaseOrders($startDate, $endDate, $filters, $pagination);

        return response()->json([
            'type' => $type,
            'reportingPeriod' => [
                'startDate' => $startDate->toDateString(),
                'endDate' => $endDate->toDateString(),
                'maxDate' => self::MAX_REPORT_DATE,
                'label' => $this->formatReportingPeriodLabel($startDate, $endDate),
            ],
            'summary' => $type === 'sales'
                ? $this->getSalesSummary($startDate, $endDate)
                : $this->getPurchaseSummary($startDate, $endDate),
            'recentOrders' => $ordersPayload['orders'],
            'pagination' => $ordersPayload['pagination'],
            'filterOptions' => [
                'statuses' => $type === 'sales' ? self::SALES_STATUSES : self::PURCHASE_STATUSES,
                'channels' => self::SALES_CHANNELS,
                'suppliers' => $this->getSupplierOptions(),
            ],
        ]);
    }

    public function show(Request $request, string $type, int $id): JsonResponse
    {
        $type = in_array($type, ['sales', 'purchase'], true) ? $type : 'sales';

        $order = $type === 'sales'
            ? $this->getSalesOrderDetail($id)
            : $this->getPurchaseOrderDetail($id);

        if (!$order) {
            return response()->json([
                'message' => 'Order not found.',
            ], 404);
        }

        return response()->json($order);
    }

    protected function resolveType(Request $request): string
    {
        $type = (string) $request->query('type', 'sales');

        return in_array($type, ['sales', 'purchase'], true) ? $type : 'sales';
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

    protected function resolveDateRange(Request $request): array
    {
        $maxDate = CarbonImmutable::parse(self::MAX_REPORT_DATE);
        $defaultStart = $maxDate->startOfMonth();

        $startDate = $this->parseDateValue($request->query('startDate'), $defaultStart);
        $endDate = $this->parseDateValue($request->query('endDate'), $maxDate);

        if ($startDate->greaterThan($maxDate)) {
            $startDate = $defaultStart;
        }

        if ($endDate->greaterThan($maxDate)) {
            $endDate = $maxDate;
        }

        if ($startDate->greaterThan($endDate)) {
            $startDate = $endDate;
        }

        return [
            $startDate->startOfDay(),
            $endDate->endOfDay(),
        ];
    }

    protected function parseDateValue(mixed $value, CarbonImmutable $fallback): CarbonImmutable
    {
        if (!is_string($value) || trim($value) === '') {
            return $fallback;
        }

        try {
            return CarbonImmutable::parse($value);
        } catch (Throwable) {
            return $fallback;
        }
    }

    protected function arrayQuery(Request $request, string $key): array
    {
        $value = $request->query($key, []);

        if (is_string($value)) {
            return array_values(array_filter(array_map('trim', explode(',', $value))));
        }

        if (!is_array($value)) {
            return [];
        }

        return array_values(array_filter(array_map('strval', $value)));
    }

    protected function formatReportingPeriodLabel(CarbonImmutable $startDate, CarbonImmutable $endDate): string
    {
        if ($startDate->year === $endDate->year) {
            return $startDate->format('F j') . ' - ' . $endDate->format('F j, Y');
        }

        return $startDate->format('F j, Y') . ' - ' . $endDate->format('F j, Y');
    }

    protected function getSalesSummary(CarbonImmutable $startDate, CarbonImmutable $endDate): array
    {
        $baseQuery = DB::table('sales_orders')
            ->whereBetween('ordered_at', [$startDate, $endDate]);

        return [
            'totalOrders' => (int) (clone $baseQuery)->count(),
            'openOrders' => (int) (clone $baseQuery)
                ->whereIn('status', ['pending', 'confirmed', 'shipped'])
                ->count(),
            'completedOrders' => (int) (clone $baseQuery)
                ->where('status', 'delivered')
                ->count(),
            'totalValue' => round((float) (clone $baseQuery)->sum('grand_total'), 2),
            'attentionOrders' => (int) (clone $baseQuery)
                ->whereIn('status', ['cancelled', 'returned', 'refunded'])
                ->count(),
        ];
    }

    protected function getPurchaseSummary(CarbonImmutable $startDate, CarbonImmutable $endDate): array
    {
        $baseQuery = DB::table('purchase_orders')
            ->whereBetween('ordered_at', [$startDate->toDateString(), $endDate->toDateString()]);

        return [
            'totalOrders' => (int) (clone $baseQuery)->count(),
            'openOrders' => (int) (clone $baseQuery)
                ->whereIn('status', ['draft', 'issued', 'transit'])
                ->count(),
            'completedOrders' => (int) (clone $baseQuery)
                ->where('status', 'fulfilled')
                ->count(),
            'totalValue' => round((float) (clone $baseQuery)->sum('total_cost'), 2),
            'attentionOrders' => (int) (clone $baseQuery)
                ->whereIn('status', ['issued', 'transit'])
                ->whereNotNull('expected_at')
                ->whereDate('expected_at', '<', $endDate->toDateString())
                ->count(),
        ];
    }

    protected function getRecentSalesOrders(
        CarbonImmutable $startDate,
        CarbonImmutable $endDate,
        array $filters,
        array $pagination
    ): array {
        $itemSummary = DB::table('sales_order_items')
            ->select([
                'sales_order_id',
                DB::raw('COUNT(*) as item_count'),
                DB::raw('SUM(qty) as total_quantity'),
            ])
            ->groupBy('sales_order_id');

        $query = DB::table('sales_orders')
            ->leftJoin('customers', 'sales_orders.customer_id', '=', 'customers.id')
            ->leftJoin('employees', 'sales_orders.employee_id', '=', 'employees.id')
            ->leftJoinSub($itemSummary, 'item_summary', function ($join) {
                $join->on('sales_orders.id', '=', 'item_summary.sales_order_id');
            })
            ->whereBetween('sales_orders.ordered_at', [$startDate, $endDate]);

        $statuses = array_values(array_intersect($filters['statuses'] ?? [], self::SALES_STATUSES));
        if ($statuses) {
            $query->whereIn('sales_orders.status', $statuses);
        }

        $channels = array_values(array_intersect($filters['channels'] ?? [], self::SALES_CHANNELS));
        if ($channels) {
            $query->whereIn('sales_orders.channel', $channels);
        }

        $search = $filters['search'] ?? '';
        if ($search !== '') {
            $like = '%' . $search . '%';

            $query->where(function ($query) use ($like) {
                $query->where('sales_orders.so_number', 'like', $like)
                    ->orWhere('sales_orders.contact_name', 'like', $like)
                    ->orWhere('sales_orders.contact_phone', 'like', $like)
                    ->orWhere('customers.first_name', 'like', $like)
                    ->orWhere('customers.last_name', 'like', $like)
                    ->orWhere('customers.business_name', 'like', $like)
                    ->orWhere('customers.email', 'like', $like);
            });
        }

        $total = (clone $query)->count();

        $orders = $query
            ->select([
                'sales_orders.id',
                'sales_orders.so_number as order_number',
                'sales_orders.channel',
                'sales_orders.status',
                'sales_orders.payment_status',
                'sales_orders.ordered_at',
                'sales_orders.completed_at',
                'sales_orders.grand_total as total_value',
                DB::raw("COALESCE(NULLIF(sales_orders.contact_name, ''), NULLIF(customers.business_name, ''), NULLIF(TRIM(CONCAT(COALESCE(customers.first_name, ''), ' ', COALESCE(customers.last_name, ''))), ''), 'Unknown customer') as counterparty_name"),
                DB::raw("NULLIF(TRIM(CONCAT(COALESCE(employees.first_name, ''), ' ', COALESCE(employees.last_name, ''))), '') as owner_name"),
                DB::raw('COALESCE(item_summary.item_count, 0) as item_count'),
                DB::raw('COALESCE(item_summary.total_quantity, 0) as total_quantity'),
            ])
            ->orderByDesc('sales_orders.ordered_at')
            ->orderByDesc('sales_orders.id')
            ->offset($pagination['offset'])
            ->limit($pagination['perPage'])
            ->get()
            ->map(function ($row) {
                return [
                    'id' => (int) $row->id,
                    'type' => 'sales',
                    'orderNumber' => $row->order_number,
                    'counterpartyName' => $row->counterparty_name,
                    'counterpartyMeta' => $row->owner_name ? 'Sales: ' . $row->owner_name : null,
                    'date' => $row->ordered_at ? CarbonImmutable::parse($row->ordered_at)->toDateString() : null,
                    'status' => $row->status,
                    'paymentStatus' => $row->payment_status,
                    'channel' => $row->channel,
                    'expectedAt' => null,
                    'receivedAt' => $row->completed_at ? CarbonImmutable::parse($row->completed_at)->toDateString() : null,
                    'itemCount' => (int) $row->item_count,
                    'totalQuantity' => (int) $row->total_quantity,
                    'totalValue' => round((float) $row->total_value, 2),
                ];
            })
            ->toArray();

        return [
            'orders' => $orders,
            'pagination' => $this->paginationPayload($pagination['page'], $pagination['perPage'], $total),
        ];
    }

    protected function getRecentPurchaseOrders(
        CarbonImmutable $startDate,
        CarbonImmutable $endDate,
        array $filters,
        array $pagination
    ): array {
        $itemSummary = DB::table('purchase_order_items')
            ->select([
                'purchase_order_id',
                DB::raw('COUNT(*) as item_count'),
                DB::raw('SUM(ordered_qty) as total_quantity'),
            ])
            ->groupBy('purchase_order_id');

        $query = DB::table('purchase_orders')
            ->leftJoin('suppliers', 'purchase_orders.supplier_id', '=', 'suppliers.id')
            ->leftJoinSub($itemSummary, 'item_summary', function ($join) {
                $join->on('purchase_orders.id', '=', 'item_summary.purchase_order_id');
            })
            ->whereBetween('purchase_orders.ordered_at', [$startDate->toDateString(), $endDate->toDateString()]);

        $statuses = array_values(array_intersect($filters['statuses'] ?? [], self::PURCHASE_STATUSES));
        if ($statuses) {
            $query->whereIn('purchase_orders.status', $statuses);
        }

        $supplierIds = array_values(array_filter(array_map('intval', $filters['suppliers'] ?? [])));
        if ($supplierIds) {
            $query->whereIn('purchase_orders.supplier_id', $supplierIds);
        }

        $search = $filters['search'] ?? '';
        if ($search !== '') {
            $like = '%' . $search . '%';

            $query->where(function ($query) use ($like) {
                $query->where('purchase_orders.po_number', 'like', $like)
                    ->orWhere('suppliers.name', 'like', $like)
                    ->orWhere('suppliers.contact_person', 'like', $like)
                    ->orWhere('suppliers.email', 'like', $like);
            });
        }

        $total = (clone $query)->count();

        $orders = $query
            ->select([
                'purchase_orders.id',
                'purchase_orders.po_number as order_number',
                'purchase_orders.status',
                'purchase_orders.ordered_at',
                'purchase_orders.expected_at',
                'purchase_orders.received_at',
                'purchase_orders.total_cost as total_value',
                'suppliers.name as supplier_name',
                'suppliers.contact_person',
                DB::raw('COALESCE(item_summary.item_count, 0) as item_count'),
                DB::raw('COALESCE(item_summary.total_quantity, 0) as total_quantity'),
            ])
            ->orderByDesc('purchase_orders.ordered_at')
            ->orderByDesc('purchase_orders.id')
            ->offset($pagination['offset'])
            ->limit($pagination['perPage'])
            ->get()
            ->map(function ($row) {
                return [
                    'id' => (int) $row->id,
                    'type' => 'purchase',
                    'orderNumber' => $row->order_number,
                    'counterpartyName' => $row->supplier_name ?? 'Unknown supplier',
                    'counterpartyMeta' => $row->contact_person ? 'Contact: ' . $row->contact_person : null,
                    'date' => $row->ordered_at ? CarbonImmutable::parse($row->ordered_at)->toDateString() : null,
                    'status' => $row->status,
                    'paymentStatus' => null,
                    'channel' => null,
                    'expectedAt' => $row->expected_at ? CarbonImmutable::parse($row->expected_at)->toDateString() : null,
                    'receivedAt' => $row->received_at ? CarbonImmutable::parse($row->received_at)->toDateString() : null,
                    'itemCount' => (int) $row->item_count,
                    'totalQuantity' => (int) $row->total_quantity,
                    'totalValue' => round((float) $row->total_value, 2),
                ];
            })
            ->toArray();

        return [
            'orders' => $orders,
            'pagination' => $this->paginationPayload($pagination['page'], $pagination['perPage'], $total),
        ];
    }

    protected function paginationPayload(int $page, int $perPage, int $total): array
    {
        $lastPage = max((int) ceil($total / $perPage), 1);
        $from = $total === 0 ? 0 : (($page - 1) * $perPage) + 1;
        $to = min($page * $perPage, $total);

        return [
            'currentPage' => $page,
            'perPage' => $perPage,
            'total' => $total,
            'lastPage' => $lastPage,
            'from' => $from,
            'to' => $to,
        ];
    }

    protected function getSalesOrderDetail(int $id): ?array
    {
        $order = DB::table('sales_orders')
            ->leftJoin('customers', 'sales_orders.customer_id', '=', 'customers.id')
            ->leftJoin('employees', 'sales_orders.employee_id', '=', 'employees.id')
            ->where('sales_orders.id', $id)
            ->select([
                'sales_orders.*',
                'customers.email as customer_email',
                'customers.phone as customer_phone',
                'customers.business_name',
                DB::raw("COALESCE(NULLIF(sales_orders.contact_name, ''), NULLIF(customers.business_name, ''), NULLIF(TRIM(CONCAT(COALESCE(customers.first_name, ''), ' ', COALESCE(customers.last_name, ''))), ''), 'Unknown customer') as customer_name"),
                DB::raw("NULLIF(TRIM(CONCAT(COALESCE(employees.first_name, ''), ' ', COALESCE(employees.last_name, ''))), '') as employee_name"),
            ])
            ->first();

        if (!$order) {
            return null;
        }

        $items = DB::table('sales_order_items')
            ->join('products', 'sales_order_items.product_id', '=', 'products.id')
            ->leftJoin('brands', 'products.brand_id', '=', 'brands.id')
            ->where('sales_order_items.sales_order_id', $id)
            ->select([
                'sales_order_items.id',
                'sales_order_items.product_id',
                'products.title',
                'products.internal_sku',
                'products.model_number',
                'brands.name as brand_name',
                'sales_order_items.qty',
                'sales_order_items.unit_price',
                'sales_order_items.discount_amount',
                'sales_order_items.final_unit_price',
                'sales_order_items.cost_basis',
                'sales_order_items.min_allowed_price',
                'sales_order_items.commission_per_unit',
                'sales_order_items.commission_total',
                'sales_order_items.line_subtotal',
                'sales_order_items.line_total',
                'sales_order_items.line_profit',
            ])
            ->orderBy('sales_order_items.id')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => (int) $item->id,
                    'productId' => (int) $item->product_id,
                    'productTitle' => $item->title,
                    'sku' => $item->internal_sku,
                    'modelNumber' => $item->model_number,
                    'brandName' => $item->brand_name,
                    'quantity' => (int) $item->qty,
                    'unitPrice' => round((float) $item->unit_price, 2),
                    'discountAmount' => round((float) $item->discount_amount, 2),
                    'finalUnitPrice' => round((float) $item->final_unit_price, 2),
                    'costBasis' => round((float) $item->cost_basis, 2),
                    'minAllowedPrice' => round((float) $item->min_allowed_price, 2),
                    'commissionPerUnit' => round((float) $item->commission_per_unit, 2),
                    'commissionTotal' => round((float) $item->commission_total, 2),
                    'lineSubtotal' => round((float) $item->line_subtotal, 2),
                    'lineTotal' => round((float) $item->line_total, 2),
                    'lineProfit' => round((float) $item->line_profit, 2),
                ];
            })
            ->toArray();

        return [
            'type' => 'sales',
            'id' => (int) $order->id,
            'orderNumber' => $order->so_number,
            'status' => $order->status,
            'paymentStatus' => $order->payment_status,
            'channel' => $order->channel,
            'orderedAt' => $order->ordered_at ? CarbonImmutable::parse($order->ordered_at)->toDateString() : null,
            'completedAt' => $order->completed_at ? CarbonImmutable::parse($order->completed_at)->toDateString() : null,
            'counterpartyName' => $order->customer_name,
            'counterpartyEmail' => $order->customer_email,
            'counterpartyPhone' => $order->contact_phone ?: $order->customer_phone,
            'ownerName' => $order->employee_name,
            'address' => [
                'line1' => $order->delivery_address_line_1,
                'line2' => $order->delivery_address_line_2,
                'city' => $order->delivery_city,
                'state' => $order->delivery_state,
                'postalCode' => $order->delivery_postal_code,
                'country' => $order->delivery_country,
            ],
            'totals' => [
                'subtotal' => round((float) $order->subtotal, 2),
                'discountAmount' => round((float) $order->discount_amount, 2),
                'taxAmount' => round((float) $order->tax_amount, 2),
                'shippingAmount' => round((float) $order->shipping_fee, 2),
                'otherAmount' => 0,
                'grandTotal' => round((float) $order->grand_total, 2),
            ],
            'notes' => $order->notes,
            'items' => $items,
        ];
    }

    protected function getPurchaseOrderDetail(int $id): ?array
    {
        $order = DB::table('purchase_orders')
            ->leftJoin('suppliers', 'purchase_orders.supplier_id', '=', 'suppliers.id')
            ->where('purchase_orders.id', $id)
            ->select([
                'purchase_orders.*',
                'suppliers.name as supplier_name',
                'suppliers.contact_person',
                'suppliers.email as supplier_email',
                'suppliers.phone as supplier_phone',
                'suppliers.address_line_1',
                'suppliers.address_line_2',
                'suppliers.city',
                'suppliers.state',
                'suppliers.postal_code',
                'suppliers.country',
            ])
            ->first();

        if (!$order) {
            return null;
        }

        $items = DB::table('purchase_order_items')
            ->join('products', 'purchase_order_items.product_id', '=', 'products.id')
            ->leftJoin('brands', 'products.brand_id', '=', 'brands.id')
            ->where('purchase_order_items.purchase_order_id', $id)
            ->select([
                'purchase_order_items.id',
                'purchase_order_items.product_id',
                'products.title',
                'products.internal_sku',
                'products.model_number',
                'brands.name as brand_name',
                'purchase_order_items.ordered_qty',
                'purchase_order_items.unit_cost',
                'purchase_order_items.line_total',
            ])
            ->orderBy('purchase_order_items.id')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => (int) $item->id,
                    'productId' => (int) $item->product_id,
                    'productTitle' => $item->title,
                    'sku' => $item->internal_sku,
                    'modelNumber' => $item->model_number,
                    'brandName' => $item->brand_name,
                    'quantity' => (int) $item->ordered_qty,
                    'unitCost' => round((float) $item->unit_cost, 2),
                    'lineTotal' => round((float) $item->line_total, 2),
                ];
            })
            ->toArray();

        return [
            'type' => 'purchase',
            'id' => (int) $order->id,
            'orderNumber' => $order->po_number,
            'status' => $order->status,
            'paymentStatus' => null,
            'channel' => null,
            'orderedAt' => $order->ordered_at ? CarbonImmutable::parse($order->ordered_at)->toDateString() : null,
            'expectedAt' => $order->expected_at ? CarbonImmutable::parse($order->expected_at)->toDateString() : null,
            'receivedAt' => $order->received_at ? CarbonImmutable::parse($order->received_at)->toDateString() : null,
            'counterpartyName' => $order->supplier_name ?? 'Unknown supplier',
            'counterpartyEmail' => $order->supplier_email,
            'counterpartyPhone' => $order->supplier_phone,
            'ownerName' => $order->contact_person,
            'address' => [
                'line1' => $order->address_line_1,
                'line2' => $order->address_line_2,
                'city' => $order->city,
                'state' => $order->state,
                'postalCode' => $order->postal_code,
                'country' => $order->country,
            ],
            'totals' => [
                'subtotal' => round((float) $order->subtotal, 2),
                'discountAmount' => 0,
                'taxAmount' => round((float) $order->tax_amount, 2),
                'shippingAmount' => round((float) $order->shipping_cost, 2),
                'otherAmount' => round((float) $order->other_cost, 2),
                'grandTotal' => round((float) $order->total_cost, 2),
            ],
            'notes' => $order->notes,
            'items' => $items,
        ];
    }

    protected function getSupplierOptions(): array
    {
        return DB::table('suppliers')
            ->where('is_active', true)
            ->select([
                'id',
                'name',
            ])
            ->orderBy('name')
            ->get()
            ->map(function ($supplier) {
                return [
                    'id' => (int) $supplier->id,
                    'name' => $supplier->name,
                ];
            })
            ->toArray();
    }
}