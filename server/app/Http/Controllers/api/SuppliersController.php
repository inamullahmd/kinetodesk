<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Support\AppliesMultiColumnSorting;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SuppliersController extends Controller
{
    use AppliesMultiColumnSorting;

    public function index(Request $request): JsonResponse
    {
        $pagination = $this->resolvePagination($request);

        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'status' => trim((string) $request->query('status', 'all')),
            'state' => strtoupper(trim((string) $request->query('state', 'all'))),
        ];

        $payload = $this->getSupplierRows($request, $filters, $pagination);

        return response()->json([
            'summary' => $this->getSummary(),
            'suppliers' => $payload['rows'],
            'pagination' => $payload['pagination'],
            'filterOptions' => [
                'states' => $this->stateOptions(),
                'statuses' => ['active', 'inactive'],
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $supplier = DB::table('suppliers')
            ->where('suppliers.id', $id)
            ->select($this->supplierSelectColumns())
            ->first();

        if (! $supplier) {
            return response()->json([
                'message' => 'Supplier not found.',
            ], 404);
        }

        return response()->json([
            'supplier' => $this->mapSupplierBase($supplier),
            'metrics' => $this->getSupplierMetrics($id),
            'recentPurchaseOrders' => $this->getSupplierRecentPurchaseOrders($id),
            'products' => $this->getSupplierProducts($id),
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

    protected function supplierSelectColumns(): array
    {
        return [
            'suppliers.id',
            'suppliers.name',
            'suppliers.contact_person',
            'suppliers.email',
            'suppliers.phone',
            'suppliers.website',
            'suppliers.tax_number',
            'suppliers.address_line_1',
            'suppliers.address_line_2',
            'suppliers.city',
            'suppliers.state',
            'suppliers.postal_code',
            'suppliers.country',
            'suppliers.created_at',
            DB::raw("CASE WHEN suppliers.is_active = 1 THEN 'active' ELSE 'inactive' END as status"),
        ];
    }

    protected function purchaseStatsSubquery()
    {
        return DB::table('purchase_orders')
            ->select([
                'purchase_orders.supplier_id',
                DB::raw('COUNT(*) as purchase_order_count'),
                DB::raw("SUM(CASE WHEN purchase_orders.status <> 'cancelled' THEN purchase_orders.total_cost ELSE 0 END) as total_spend"),
                DB::raw("AVG(CASE WHEN purchase_orders.status <> 'cancelled' THEN purchase_orders.total_cost ELSE NULL END) as average_order_value"),
                DB::raw("SUM(CASE WHEN purchase_orders.status IN ('issued', 'transit') THEN 1 ELSE 0 END) as open_purchase_orders"),
                DB::raw("SUM(CASE WHEN purchase_orders.status = 'fulfilled' THEN 1 ELSE 0 END) as fulfilled_purchase_orders"),
                DB::raw('MAX(purchase_orders.ordered_at) as last_order_at'),
                DB::raw('MAX(purchase_orders.received_at) as last_received_at'),
            ])
            ->groupBy('purchase_orders.supplier_id');
    }

    protected function productStatsSubquery()
    {
        return DB::table('supplier_products')
            ->select([
                'supplier_products.supplier_id',
                DB::raw('COUNT(DISTINCT supplier_products.product_id) as product_count'),
                DB::raw("SUM(CASE WHEN supplier_products.preferred_supplier = 1 THEN 1 ELSE 0 END) as preferred_product_count"),
                DB::raw('AVG(supplier_products.lead_time_days) as average_lead_time_days'),
            ])
            ->groupBy('supplier_products.supplier_id');
    }

    protected function baseSupplierRowsQuery()
    {
        return DB::table('suppliers')
            ->leftJoinSub($this->purchaseStatsSubquery(), 'purchase_stats', function ($join) {
                $join->on('suppliers.id', '=', 'purchase_stats.supplier_id');
            })
            ->leftJoinSub($this->productStatsSubquery(), 'product_stats', function ($join) {
                $join->on('suppliers.id', '=', 'product_stats.supplier_id');
            });
    }

    protected function getSupplierRows(Request $request, array $filters, array $pagination): array
    {
        $query = $this->baseSupplierRowsQuery();

        $this->applySearch($query, $filters['search'] ?? '');
        $this->applyStateFilter($query, $filters['state'] ?? 'all');
        $this->applyStatusFilter($query, $filters['status'] ?? 'all');

        $total = (clone $query)->count('suppliers.id');

        $rowsQuery = $query->select([
            ...$this->supplierSelectColumns(),
            DB::raw('COALESCE(purchase_stats.purchase_order_count, 0) as purchase_order_count'),
            DB::raw('COALESCE(purchase_stats.total_spend, 0) as total_spend'),
            DB::raw('COALESCE(purchase_stats.average_order_value, 0) as average_order_value'),
            DB::raw('COALESCE(purchase_stats.open_purchase_orders, 0) as open_purchase_orders'),
            DB::raw('COALESCE(purchase_stats.fulfilled_purchase_orders, 0) as fulfilled_purchase_orders'),
            'purchase_stats.last_order_at',
            'purchase_stats.last_received_at',
            DB::raw('COALESCE(product_stats.product_count, 0) as product_count'),
            DB::raw('COALESCE(product_stats.preferred_product_count, 0) as preferred_product_count'),
            DB::raw('COALESCE(product_stats.average_lead_time_days, 0) as average_lead_time_days'),
        ]);

        $this->applySorts($rowsQuery, $request, $this->supplierAllowedSorts(), [
            ['field' => 'totalSpend', 'direction' => 'desc'],
        ]);

        $rows = $rowsQuery
            ->orderBy('suppliers.id')
            ->offset($pagination['offset'])
            ->limit($pagination['perPage'])
            ->get()
            ->map(function ($row) {
                return [
                    ...$this->mapSupplierBase($row),
                    'purchaseOrderCount' => (int) $row->purchase_order_count,
                    'openPurchaseOrders' => (int) $row->open_purchase_orders,
                    'fulfilledPurchaseOrders' => (int) $row->fulfilled_purchase_orders,
                    'totalSpend' => round((float) $row->total_spend, 2),
                    'averageOrderValue' => round((float) $row->average_order_value, 2),
                    'productCount' => (int) $row->product_count,
                    'preferredProductCount' => (int) $row->preferred_product_count,
                    'averageLeadTimeDays' => round((float) $row->average_lead_time_days, 1),
                    'lastOrderAt' => $this->dateString($row->last_order_at),
                    'lastReceivedAt' => $this->dateString($row->last_received_at),
                ];
            })
            ->toArray();

        return [
            'rows' => $rows,
            'pagination' => $this->paginationPayload($pagination['page'], $pagination['perPage'], $total),
        ];
    }

    protected function supplierAllowedSorts(): array
    {
        return [
            'name' => 'suppliers.name',
            'contactPerson' => 'suppliers.contact_person',
            'location' => function ($query, string $direction) {
                $query
                    ->orderBy('suppliers.country', $direction)
                    ->orderBy('suppliers.state', $direction)
                    ->orderBy('suppliers.city', $direction);
            },
            'purchaseOrderCount' => 'purchase_order_count',
            'openPurchaseOrders' => 'open_purchase_orders',
            'fulfilledPurchaseOrders' => 'fulfilled_purchase_orders',
            'totalSpend' => 'total_spend',
            'averageOrderValue' => 'average_order_value',
            'productCount' => 'product_count',
            'averageLeadTimeDays' => 'average_lead_time_days',
            'lastOrderAt' => function ($query, string $direction) {
                $this->orderByNullableDate($query, 'purchase_stats.last_order_at', $direction);
            },
            'lastReceivedAt' => function ($query, string $direction) {
                $this->orderByNullableDate($query, 'purchase_stats.last_received_at', $direction);
            },
            'status' => 'suppliers.is_active',
        ];
    }

    protected function applySearch($query, string $search): void
    {
        if ($search === '') {
            return;
        }

        $like = '%' . $search . '%';

        $query->where(function ($query) use ($like) {
            $query->where('suppliers.name', 'like', $like)
                ->orWhere('suppliers.contact_person', 'like', $like)
                ->orWhere('suppliers.email', 'like', $like)
                ->orWhere('suppliers.phone', 'like', $like)
                ->orWhere('suppliers.website', 'like', $like)
                ->orWhere('suppliers.tax_number', 'like', $like)
                ->orWhere('suppliers.city', 'like', $like)
                ->orWhere('suppliers.state', 'like', $like)
                ->orWhere('suppliers.country', 'like', $like);
        });
    }

    protected function applyStateFilter($query, string $state): void
    {
        if ($state === '' || $state === 'ALL') {
            return;
        }

        $query->whereRaw('UPPER(suppliers.state) = ?', [$state]);
    }

    protected function applyStatusFilter($query, string $status): void
    {
        if (! in_array($status, ['active', 'inactive'], true)) {
            return;
        }

        $query->where('suppliers.is_active', $status === 'active');
    }

    protected function mapSupplierBase($row): array
    {
        return [
            'id' => (int) $row->id,
            'name' => $row->name,
            'contactPerson' => $row->contact_person,
            'email' => $row->email,
            'phone' => $row->phone,
            'website' => $row->website,
            'taxNumber' => $row->tax_number,
            'addressLine1' => $row->address_line_1 ?? null,
            'addressLine2' => $row->address_line_2 ?? null,
            'city' => $row->city,
            'state' => $row->state,
            'postalCode' => $row->postal_code ?? null,
            'country' => $row->country ?? null,
            'status' => $row->status ?? 'active',
            'createdAt' => $this->dateString($row->created_at),
        ];
    }

    protected function getSummary(): array
    {
        $supplierRow = DB::table('suppliers')
            ->select([
                DB::raw('COUNT(*) as total_suppliers'),
                DB::raw('SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_suppliers'),
            ])
            ->first();

        $purchaseRow = DB::table('purchase_orders')
            ->select([
                DB::raw("COUNT(*) as purchase_order_count"),
                DB::raw("SUM(CASE WHEN status IN ('issued', 'transit') THEN 1 ELSE 0 END) as open_purchase_orders"),
                DB::raw("SUM(CASE WHEN status = 'fulfilled' THEN 1 ELSE 0 END) as fulfilled_purchase_orders"),
                DB::raw("SUM(CASE WHEN status <> 'cancelled' THEN total_cost ELSE 0 END) as total_spend"),
            ])
            ->first();

        $productCount = DB::table('supplier_products')->distinct('product_id')->count('product_id');

        return [
            'totalSuppliers' => (int) ($supplierRow->total_suppliers ?? 0),
            'activeSuppliers' => (int) ($supplierRow->active_suppliers ?? 0),
            'purchaseOrderCount' => (int) ($purchaseRow->purchase_order_count ?? 0),
            'openPurchaseOrders' => (int) ($purchaseRow->open_purchase_orders ?? 0),
            'fulfilledPurchaseOrders' => (int) ($purchaseRow->fulfilled_purchase_orders ?? 0),
            'totalSpend' => round((float) ($purchaseRow->total_spend ?? 0), 2),
            'productCount' => (int) $productCount,
        ];
    }

    protected function getSupplierMetrics(int $supplierId): array
    {
        $purchaseRow = DB::table('purchase_orders')
            ->where('supplier_id', $supplierId)
            ->select([
                DB::raw('COUNT(*) as purchase_order_count'),
                DB::raw("SUM(CASE WHEN status IN ('issued', 'transit') THEN 1 ELSE 0 END) as open_purchase_orders"),
                DB::raw("SUM(CASE WHEN status = 'fulfilled' THEN 1 ELSE 0 END) as fulfilled_purchase_orders"),
                DB::raw("SUM(CASE WHEN status <> 'cancelled' THEN total_cost ELSE 0 END) as total_spend"),
                DB::raw("AVG(CASE WHEN status <> 'cancelled' THEN total_cost ELSE NULL END) as average_order_value"),
                DB::raw('MAX(ordered_at) as last_order_at'),
                DB::raw('MAX(received_at) as last_received_at'),
            ])
            ->first();

        $productRow = DB::table('supplier_products')
            ->where('supplier_id', $supplierId)
            ->select([
                DB::raw('COUNT(DISTINCT product_id) as product_count'),
                DB::raw("SUM(CASE WHEN preferred_supplier = 1 THEN 1 ELSE 0 END) as preferred_product_count"),
                DB::raw('AVG(lead_time_days) as average_lead_time_days'),
            ])
            ->first();

        return [
            'purchaseOrderCount' => (int) ($purchaseRow->purchase_order_count ?? 0),
            'openPurchaseOrders' => (int) ($purchaseRow->open_purchase_orders ?? 0),
            'fulfilledPurchaseOrders' => (int) ($purchaseRow->fulfilled_purchase_orders ?? 0),
            'totalSpend' => round((float) ($purchaseRow->total_spend ?? 0), 2),
            'averageOrderValue' => round((float) ($purchaseRow->average_order_value ?? 0), 2),
            'productCount' => (int) ($productRow->product_count ?? 0),
            'preferredProductCount' => (int) ($productRow->preferred_product_count ?? 0),
            'averageLeadTimeDays' => round((float) ($productRow->average_lead_time_days ?? 0), 1),
            'lastOrderAt' => $this->dateString($purchaseRow->last_order_at ?? null),
            'lastReceivedAt' => $this->dateString($purchaseRow->last_received_at ?? null),
        ];
    }

    protected function getSupplierRecentPurchaseOrders(int $supplierId): array
    {
        $itemStats = DB::table('purchase_order_items')
            ->select([
                'purchase_order_id',
                DB::raw('COUNT(*) as item_count'),
                DB::raw('SUM(ordered_qty) as total_quantity'),
            ])
            ->groupBy('purchase_order_id');

        return DB::table('purchase_orders')
            ->leftJoinSub($itemStats, 'item_stats', function ($join) {
                $join->on('purchase_orders.id', '=', 'item_stats.purchase_order_id');
            })
            ->where('purchase_orders.supplier_id', $supplierId)
            ->select([
                'purchase_orders.id',
                'purchase_orders.po_number',
                'purchase_orders.status',
                'purchase_orders.ordered_at',
                'purchase_orders.expected_at',
                'purchase_orders.received_at',
                'purchase_orders.total_cost',
                DB::raw('COALESCE(item_stats.item_count, 0) as item_count'),
                DB::raw('COALESCE(item_stats.total_quantity, 0) as total_quantity'),
            ])
            ->orderByDesc('purchase_orders.ordered_at')
            ->limit(12)
            ->get()
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'orderNumber' => $row->po_number,
                'status' => $row->status,
                'orderedAt' => $this->dateString($row->ordered_at),
                'expectedAt' => $this->dateString($row->expected_at),
                'receivedAt' => $this->dateString($row->received_at),
                'totalCost' => round((float) $row->total_cost, 2),
                'itemCount' => (int) $row->item_count,
                'totalQuantity' => (int) $row->total_quantity,
            ])
            ->toArray();
    }

    protected function getSupplierProducts(int $supplierId): array
    {
        return DB::table('supplier_products')
            ->join('products', 'supplier_products.product_id', '=', 'products.id')
            ->leftJoin('brands', 'products.brand_id', '=', 'brands.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->where('supplier_products.supplier_id', $supplierId)
            ->select([
                'products.id',
                'products.title',
                'products.internal_sku',
                'products.model_number',
                'brands.name as brand_name',
                'categories.name as category_name',
                'supplier_products.supplier_sku',
                'supplier_products.preferred_supplier',
                'supplier_products.min_order_qty',
                'supplier_products.lead_time_days',
                'supplier_products.last_cost',
                'supplier_products.currency',
                DB::raw("CASE WHEN supplier_products.is_active = 1 THEN 'active' ELSE 'inactive' END as status"),
            ])
            ->orderByDesc('supplier_products.preferred_supplier')
            ->orderBy('products.title')
            ->limit(16)
            ->get()
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'title' => $row->title,
                'sku' => $row->internal_sku,
                'modelNumber' => $row->model_number,
                'brandName' => $row->brand_name,
                'categoryName' => $row->category_name,
                'supplierSku' => $row->supplier_sku,
                'preferredSupplier' => (bool) $row->preferred_supplier,
                'minOrderQty' => (int) ($row->min_order_qty ?? 0),
                'leadTimeDays' => $row->lead_time_days !== null ? (int) $row->lead_time_days : null,
                'lastCost' => round((float) ($row->last_cost ?? 0), 2),
                'currency' => $row->currency,
                'status' => $row->status,
            ])
            ->toArray();
    }

    protected function stateOptions(): array
    {
        return DB::table('suppliers')
            ->whereNotNull('state')
            ->where('state', '<>', '')
            ->selectRaw('UPPER(state) as code')
            ->distinct()
            ->orderBy('code')
            ->get()
            ->map(fn ($row) => [
                'code' => $row->code,
                'name' => $row->code,
            ])
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