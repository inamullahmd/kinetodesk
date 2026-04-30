<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Support\AppliesMultiColumnSorting;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class InventoryController extends Controller
{
    use AppliesMultiColumnSorting;

    private const MAX_REPORT_DATE = '2026-03-31';

    public function overview(Request $request): JsonResponse
    {
        [$startDate, $endDate] = $this->resolveDateRange($request);

        return response()->json([
            'reportingPeriod' => $this->reportingPeriodPayload($startDate, $endDate),
            'summary' => $this->getOverviewSummary($endDate),
            'categoryBreakdown' => $this->getCategoryBreakdown($endDate),
            'recentMovements' => $this->getRecentMovements($startDate, $endDate, 8),
            'alertsPreview' => $this->getAlertsRows($endDate, [], ['page' => 1, 'perPage' => 5, 'offset' => 0])['rows'],
        ]);
    }

    public function products(Request $request): JsonResponse
    {
        [$startDate, $endDate] = $this->resolveDateRange($request);

        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'statuses' => $this->arrayQuery($request, 'statuses'),
            'category' => trim((string) $request->query('category', 'all')),
            'brand' => trim((string) $request->query('brand', 'all')),
            'serialized' => trim((string) $request->query('serialized', 'all')),
        ];

        $pagination = $this->resolvePagination($request);
        $payload = $this->getProductRows($endDate, $filters, $pagination);

        return response()->json([
            'reportingPeriod' => $this->reportingPeriodPayload($startDate, $endDate),
            'rows' => $payload['rows'],
            'pagination' => $payload['pagination'],
            'filterOptions' => $this->getFilterOptions(),
        ]);
    }

    public function productDetail(int $id): JsonResponse
    {
        $product = $this->getProductDetail($id);

        if (!$product) {
            return response()->json(['message' => 'Product not found.'], 404);
        }

        return response()->json($product);
    }

    public function alerts(Request $request): JsonResponse
    {
        [$startDate, $endDate] = $this->resolveDateRange($request);

        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'alertTypes' => $this->arrayQuery($request, 'alertTypes'),
            'category' => trim((string) $request->query('category', 'all')),
            'brand' => trim((string) $request->query('brand', 'all')),
        ];

        $pagination = $this->resolvePagination($request);
        $payload = $this->getAlertsRows($endDate, $filters, $pagination);

        return response()->json([
            'reportingPeriod' => $this->reportingPeriodPayload($startDate, $endDate),
            'summary' => $this->getAlertsSummary($endDate),
            'rows' => $payload['rows'],
            'pagination' => $payload['pagination'],
            'filterOptions' => [
                ...$this->getFilterOptions(),
                'alertTypes' => ['out_of_stock', 'low_stock', 'stale_stock'],
            ],
        ]);
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

        return [$startDate->startOfDay(), $endDate->endOfDay()];
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

    protected function resolvePagination(Request $request): array
    {
        $page = max((int) $request->query('page', 1), 1);
        $perPage = max(min((int) $request->query('perPage', 10), 50), 5);

        return [
            'page' => $page,
            'perPage' => $perPage,
            'offset' => ($page - 1) * $perPage,
        ];
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

    protected function reportingPeriodPayload(CarbonImmutable $startDate, CarbonImmutable $endDate): array
    {
        return [
            'startDate' => $startDate->toDateString(),
            'endDate' => $endDate->toDateString(),
            'maxDate' => self::MAX_REPORT_DATE,
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

    protected function stockSubquery(CarbonImmutable $endDate)
    {
        return DB::table('stock_batches')
            ->select([
                'product_id',
                DB::raw('SUM(qty_remaining) as stock_qty'),
                DB::raw('SUM(qty_remaining * unit_cost) as inventory_value'),
                DB::raw('AVG(unit_cost) as avg_unit_cost'),
                DB::raw('MAX(received_at) as last_received_at'),
            ])
            ->whereDate('received_at', '<=', $endDate->toDateString())
            ->groupBy('product_id');
    }

    protected function lastSaleSubquery(CarbonImmutable $endDate)
    {
        return DB::table('sales_order_items')
            ->join('sales_orders', 'sales_order_items.sales_order_id', '=', 'sales_orders.id')
            ->select([
                'sales_order_items.product_id',
                DB::raw('MAX(sales_orders.ordered_at) as last_sold_at'),
            ])
            ->where('sales_orders.status', 'delivered')
            ->whereDate('sales_orders.ordered_at', '<=', $endDate->toDateString())
            ->groupBy('sales_order_items.product_id');
    }

    protected function baseProductQuery(CarbonImmutable $endDate)
    {
        return DB::table('products')
            ->join('brands', 'products.brand_id', '=', 'brands.id')
            ->leftJoin('categories as product_category', 'products.category_id', '=', 'product_category.id')
            ->leftJoin('categories as parent_category', 'product_category.parent_id', '=', 'parent_category.id')
            ->leftJoinSub($this->stockSubquery($endDate), 'stock_summary', function ($join) {
                $join->on('products.id', '=', 'stock_summary.product_id');
            })
            ->leftJoinSub($this->lastSaleSubquery($endDate), 'last_sales', function ($join) {
                $join->on('products.id', '=', 'last_sales.product_id');
            });
    }

    protected function getOverviewSummary(CarbonImmutable $endDate): array
    {
        $stock = $this->baseProductQuery($endDate);

        return [
            'totalProducts' => (int) DB::table('products')->count(),
            'activeSkus' => (int) DB::table('products')->where('is_active', true)->count(),
            'inventoryValue' => round((float) DB::table('stock_batches')->sum(DB::raw('qty_remaining * unit_cost')), 2),
            'lowStockItems' => (int) (clone $stock)
                ->whereRaw('COALESCE(stock_summary.stock_qty, 0) > 0')
                ->whereRaw('COALESCE(stock_summary.stock_qty, 0) <= 5')
                ->count(),
            'outOfStockItems' => (int) (clone $stock)
                ->whereRaw('COALESCE(stock_summary.stock_qty, 0) = 0')
                ->count(),
            'serializedUnits' => (int) DB::table('serial_numbers')
                ->where('status', 'available')
                ->count(),
        ];
    }

    protected function getCategoryBreakdown(CarbonImmutable $endDate): array
    {
        return $this->baseProductQuery($endDate)
            ->select([
                DB::raw('COALESCE(parent_category.name, product_category.name, "Uncategorized") as category_name'),
                DB::raw('COUNT(products.id) as product_count'),
                DB::raw('SUM(COALESCE(stock_summary.stock_qty, 0)) as stock_qty'),
                DB::raw('SUM(COALESCE(stock_summary.inventory_value, 0)) as inventory_value'),
                DB::raw('SUM(CASE WHEN COALESCE(stock_summary.stock_qty, 0) = 0 THEN 1 ELSE 0 END) as out_of_stock_count'),
                DB::raw('SUM(CASE WHEN COALESCE(stock_summary.stock_qty, 0) > 0 AND COALESCE(stock_summary.stock_qty, 0) <= 5 THEN 1 ELSE 0 END) as low_stock_count'),
            ])
            ->groupBy(DB::raw('COALESCE(parent_category.name, product_category.name, "Uncategorized")'))
            ->orderByDesc('inventory_value')
            ->limit(8)
            ->get()
            ->map(fn ($row) => [
                'categoryName' => $row->category_name,
                'productCount' => (int) $row->product_count,
                'stockQty' => (int) $row->stock_qty,
                'inventoryValue' => round((float) $row->inventory_value, 2),
                'outOfStockCount' => (int) $row->out_of_stock_count,
                'lowStockCount' => (int) $row->low_stock_count,
            ])
            ->toArray();
    }

    protected function getRecentMovements(CarbonImmutable $startDate, CarbonImmutable $endDate, int $limit): array
    {
        return DB::table('stock_movements')
            ->join('products', 'stock_movements.product_id', '=', 'products.id')
            ->leftJoin('stock_batches', 'stock_movements.stock_batch_id', '=', 'stock_batches.id')
            ->whereBetween('stock_movements.moved_at', [$startDate, $endDate])
            ->select([
                'stock_movements.id',
                'stock_movements.movement_type',
                'stock_movements.qty_change',
                'stock_movements.unit_cost',
                'stock_movements.moved_at',
                'stock_movements.reference_type',
                'stock_movements.reference_id',
                'products.title as product_title',
                'products.internal_sku',
                'stock_batches.batch_code',
            ])
            ->orderByDesc('stock_movements.moved_at')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'movementType' => $row->movement_type,
                'qtyChange' => (int) $row->qty_change,
                'unitCost' => $row->unit_cost !== null ? round((float) $row->unit_cost, 2) : null,
                'movedAt' => CarbonImmutable::parse($row->moved_at)->toDateString(),
                'referenceType' => $row->reference_type,
                'referenceId' => $row->reference_id,
                'productTitle' => $row->product_title,
                'sku' => $row->internal_sku,
                'batchCode' => $row->batch_code,
            ])
            ->toArray();
    }

    protected function getProductRows(CarbonImmutable $endDate, array $filters, array $pagination): array
    {
        $query = $this->baseProductQuery($endDate);

        $this->applyProductFilters($query, $filters);

        $statuses = $filters['statuses'] ?? [];
        if ($statuses) {
            $query->where(function ($query) use ($statuses) {
                foreach ($statuses as $status) {
                    if ($status === 'out_of_stock') {
                        $query->orWhereRaw('COALESCE(stock_summary.stock_qty, 0) = 0');
                    }

                    if ($status === 'low_stock') {
                        $query->orWhereRaw('COALESCE(stock_summary.stock_qty, 0) > 0 AND COALESCE(stock_summary.stock_qty, 0) <= 5');
                    }

                    if ($status === 'in_stock') {
                        $query->orWhereRaw('COALESCE(stock_summary.stock_qty, 0) > 5');
                    }
                }
            });
        }

        $total = (clone $query)->count();

        $rowsQuery = $query->select($this->productSelectColumns());

        $productAllowedSorts = [
            'product' => 'products.title',
            'sku' => 'products.internal_sku',
            'brand' => 'brands.name',
            'category' => fn ($query, string $direction) => $query->orderByRaw("COALESCE(parent_category.name, product_category.name, 'Uncategorized') {$direction}"),
            'currentStock' => fn ($query, string $direction) => $query->orderByRaw("COALESCE(stock_summary.stock_qty, 0) {$direction}"),
            'stockValue' => fn ($query, string $direction) => $query->orderByRaw("COALESCE(stock_summary.inventory_value, 0) {$direction}"),
            'avgUnitCost' => fn ($query, string $direction) => $query->orderByRaw("COALESCE(stock_summary.avg_unit_cost, 0) {$direction}"),
            'lastSoldAt' => function ($query, string $direction) {
                $query
                    ->orderByRaw("CASE WHEN last_sales.last_sold_at IS NULL THEN 1 ELSE 0 END ASC")
                    ->orderBy('last_sales.last_sold_at', $direction);
            },
            'status' => fn ($query, string $direction) => $this->orderByProductStatus($query, $direction, 'stock_summary.stock_qty'),
        ];

        $this->applySorts($rowsQuery, request(), $productAllowedSorts, [
            ['field' => 'product', 'direction' => 'asc'],
        ]);

        $rows = $rowsQuery
            ->orderBy('products.id')
            ->offset($pagination['offset'])
            ->limit($pagination['perPage'])
            ->get()
            ->map(fn ($row) => $this->mapProductRow($row))
            ->toArray();

        return [
            'rows' => $rows,
            'pagination' => $this->paginationPayload($pagination['page'], $pagination['perPage'], $total),
        ];
    }

    protected function getAlertsRows(CarbonImmutable $endDate, array $filters, array $pagination): array
    {
        $query = $this->baseProductQuery($endDate);

        $this->applyProductFilters($query, $filters);

        $alertTypes = $filters['alertTypes'] ?? [];

        $query->where(function ($query) use ($alertTypes) {
            if (!$alertTypes || in_array('out_of_stock', $alertTypes, true)) {
                $query->orWhereRaw('COALESCE(stock_summary.stock_qty, 0) = 0');
            }

            if (!$alertTypes || in_array('low_stock', $alertTypes, true)) {
                $query->orWhereRaw('COALESCE(stock_summary.stock_qty, 0) > 0 AND COALESCE(stock_summary.stock_qty, 0) <= 5');
            }

            if (!$alertTypes || in_array('stale_stock', $alertTypes, true)) {
                $query->orWhere(function ($query) {
                    $query->whereRaw('COALESCE(stock_summary.stock_qty, 0) > 0')
                        ->where(function ($query) {
                            $query->whereNull('last_sales.last_sold_at')
                                ->orWhereRaw('DATEDIFF("' . self::MAX_REPORT_DATE . '", last_sales.last_sold_at) >= 90');
                        });
                });
            }
        });

        $total = (clone $query)->count();

        $rowsQuery = $query->select($this->productSelectColumns());

        $alertAllowedSorts = [
            'product' => 'products.title',
            'sku' => 'products.internal_sku',
            'brand' => 'brands.name',
            'category' => fn ($query, string $direction) => $query->orderByRaw("COALESCE(parent_category.name, product_category.name, 'Uncategorized') {$direction}"),
            'currentStock' => fn ($query, string $direction) => $query->orderByRaw("COALESCE(stock_summary.stock_qty, 0) {$direction}"),
            'stockValue' => fn ($query, string $direction) => $query->orderByRaw("COALESCE(stock_summary.inventory_value, 0) {$direction}"),
            'avgUnitCost' => fn ($query, string $direction) => $query->orderByRaw("COALESCE(stock_summary.avg_unit_cost, 0) {$direction}"),
            'lastSoldAt' => function ($query, string $direction) {
                $query
                    ->orderByRaw("CASE WHEN last_sales.last_sold_at IS NULL THEN 1 ELSE 0 END ASC")
                    ->orderBy('last_sales.last_sold_at', $direction);
            },
            'status' => fn ($query, string $direction) => $this->orderByProductStatus($query, $direction, 'stock_summary.stock_qty'),
        ];

        $this->applySorts($rowsQuery, request(), $alertAllowedSorts, [
            ['field' => 'currentStock', 'direction' => 'asc'],
            ['field' => 'product', 'direction' => 'asc'],
        ]);

        $rows = $rowsQuery
            ->orderBy('products.id')
            ->offset($pagination['offset'])
            ->limit($pagination['perPage'])
            ->get()
            ->map(fn ($row) => $this->mapProductRow($row))
            ->toArray();

        return [
            'rows' => $rows,
            'pagination' => $this->paginationPayload($pagination['page'], $pagination['perPage'], $total),
        ];
    }

    protected function applyProductFilters($query, array $filters): void
    {
        if (($filters['search'] ?? '') !== '') {
            $like = '%' . $filters['search'] . '%';

            $query->where(function ($query) use ($like) {
                $query->where('products.title', 'like', $like)
                    ->orWhere('products.internal_sku', 'like', $like)
                    ->orWhere('products.model_number', 'like', $like)
                    ->orWhere('brands.name', 'like', $like);
            });
        }

        if (($filters['category'] ?? 'all') !== 'all') {
            $query->where(function ($query) use ($filters) {
                $query->where('product_category.id', (int) $filters['category'])
                    ->orWhere('parent_category.id', (int) $filters['category']);
            });
        }

        if (($filters['brand'] ?? 'all') !== 'all') {
            $query->where('brands.id', (int) $filters['brand']);
        }

        if (($filters['serialized'] ?? 'all') === 'serialized') {
            $query->where('products.is_serialized', true);
        }

        if (($filters['serialized'] ?? 'all') === 'non_serialized') {
            $query->where('products.is_serialized', false);
        }
    }

    protected function productSelectColumns(): array
    {
        return [
            'products.id',
            'products.title',
            'products.internal_sku',
            'products.model_number',
            'products.is_serialized',
            'products.is_active',
            'brands.name as brand_name',
            DB::raw('COALESCE(parent_category.name, product_category.name) as category_name'),
            DB::raw("
                CASE
                    WHEN product_category.parent_id IS NULL THEN NULL
                    ELSE product_category.name
                END as sub_category_name
            "),
            DB::raw('COALESCE(stock_summary.stock_qty, 0) as stock_qty'),
            DB::raw('COALESCE(stock_summary.inventory_value, 0) as inventory_value'),
            DB::raw('COALESCE(stock_summary.avg_unit_cost, 0) as avg_unit_cost'),
            'stock_summary.last_received_at',
            'last_sales.last_sold_at',
        ];
    }

    protected function mapProductRow($row): array
    {
        $stockQty = (int) $row->stock_qty;

        if ($stockQty === 0) {
            $status = 'out_of_stock';
        } elseif ($stockQty <= 5) {
            $status = 'low_stock';
        } else {
            $status = 'in_stock';
        }

        $daysSinceLastSale = $row->last_sold_at
            ? CarbonImmutable::parse($row->last_sold_at)->diffInDays(CarbonImmutable::parse(self::MAX_REPORT_DATE), false)
            : null;

        return [
            'id' => (int) $row->id,
            'title' => $row->title,
            'sku' => $row->internal_sku,
            'modelNumber' => $row->model_number,
            'brandName' => $row->brand_name,
            'categoryName' => $row->category_name,
            'subCategoryName' => $row->sub_category_name,
            'isSerialized' => (bool) $row->is_serialized,
            'isActive' => (bool) $row->is_active,
            'stockQty' => $stockQty,
            'inventoryValue' => round((float) $row->inventory_value, 2),
            'avgUnitCost' => round((float) $row->avg_unit_cost, 2),
            'lastReceivedAt' => $row->last_received_at,
            'lastSoldAt' => $row->last_sold_at ? CarbonImmutable::parse($row->last_sold_at)->toDateString() : null,
            'daysSinceLastSale' => $daysSinceLastSale,
            'status' => $status,
        ];
    }

    protected function getProductDetail(int $id): ?array
    {
        $endDate = CarbonImmutable::parse(self::MAX_REPORT_DATE)->endOfDay();

        $row = $this->baseProductQuery($endDate)
            ->where('products.id', $id)
            ->select($this->productSelectColumns())
            ->first();

        if (!$row) {
            return null;
        }

        $product = $this->mapProductRow($row);

        $batches = DB::table('stock_batches')
            ->leftJoin('purchase_order_items', 'stock_batches.purchase_order_item_id', '=', 'purchase_order_items.id')
            ->leftJoin('purchase_orders', 'purchase_order_items.purchase_order_id', '=', 'purchase_orders.id')
            ->where('stock_batches.product_id', $id)
            ->select([
                'stock_batches.id',
                'stock_batches.batch_code',
                'stock_batches.qty_received',
                'stock_batches.qty_remaining',
                'stock_batches.unit_cost',
                'stock_batches.received_at',
                'purchase_orders.po_number',
            ])
            ->orderByDesc('stock_batches.received_at')
            ->limit(10)
            ->get()
            ->map(fn ($batch) => [
                'id' => (int) $batch->id,
                'batchCode' => $batch->batch_code,
                'qtyReceived' => (int) $batch->qty_received,
                'qtyRemaining' => (int) $batch->qty_remaining,
                'unitCost' => round((float) $batch->unit_cost, 2),
                'remainingValue' => round((float) $batch->qty_remaining * (float) $batch->unit_cost, 2),
                'receivedAt' => $batch->received_at,
                'purchaseOrderNumber' => $batch->po_number,
            ])
            ->toArray();

        $movements = DB::table('stock_movements')
            ->leftJoin('stock_batches', 'stock_movements.stock_batch_id', '=', 'stock_batches.id')
            ->where('stock_movements.product_id', $id)
            ->select([
                'stock_movements.id',
                'stock_movements.movement_type',
                'stock_movements.qty_change',
                'stock_movements.unit_cost',
                'stock_movements.moved_at',
                'stock_movements.reference_type',
                'stock_movements.reference_id',
                'stock_batches.batch_code',
            ])
            ->orderByDesc('stock_movements.moved_at')
            ->limit(10)
            ->get()
            ->map(fn ($movement) => [
                'id' => (int) $movement->id,
                'movementType' => $movement->movement_type,
                'qtyChange' => (int) $movement->qty_change,
                'unitCost' => $movement->unit_cost !== null ? round((float) $movement->unit_cost, 2) : null,
                'movedAt' => CarbonImmutable::parse($movement->moved_at)->toDateString(),
                'referenceType' => $movement->reference_type,
                'referenceId' => $movement->reference_id,
                'batchCode' => $movement->batch_code,
            ])
            ->toArray();

        $serials = DB::table('serial_numbers')
            ->leftJoin('stock_batches', 'serial_numbers.stock_batch_id', '=', 'stock_batches.id')
            ->where('serial_numbers.product_id', $id)
            ->select([
                'serial_numbers.id',
                'serial_numbers.serial_number',
                'serial_numbers.status',
                'stock_batches.batch_code',
                'stock_batches.received_at',
            ])
            ->orderBy('serial_numbers.serial_number')
            ->limit(20)
            ->get()
            ->map(fn ($serial) => [
                'id' => (int) $serial->id,
                'serialNumber' => $serial->serial_number,
                'status' => $serial->status,
                'batchCode' => $serial->batch_code,
                'receivedAt' => $serial->received_at,
            ])
            ->toArray();

        return [
            ...$product,
            'batches' => $batches,
            'movements' => $movements,
            'serials' => $serials,
        ];
    }

    protected function getAlertsSummary(CarbonImmutable $endDate): array
    {
        $base = $this->baseProductQuery($endDate);

        return [
            'outOfStock' => (int) (clone $base)->whereRaw('COALESCE(stock_summary.stock_qty, 0) = 0')->count(),
            'lowStock' => (int) (clone $base)
                ->whereRaw('COALESCE(stock_summary.stock_qty, 0) > 0')
                ->whereRaw('COALESCE(stock_summary.stock_qty, 0) <= 5')
                ->count(),
            'staleStock' => (int) (clone $base)
                ->whereRaw('COALESCE(stock_summary.stock_qty, 0) > 0')
                ->where(function ($query) {
                    $query->whereNull('last_sales.last_sold_at')
                        ->orWhereRaw('DATEDIFF("' . self::MAX_REPORT_DATE . '", last_sales.last_sold_at) >= 90');
                })
                ->count(),
        ];
    }

    protected function getFilterOptions(): array
    {
        return [
            'categories' => DB::table('categories')
                ->select(['id', 'name'])
                ->where('active', true)
                ->orderBy('name')
                ->get()
                ->map(fn ($row) => ['id' => (int) $row->id, 'name' => $row->name])
                ->toArray(),
            'brands' => DB::table('brands')
                ->select(['id', 'name'])
                ->where('active', true)
                ->orderBy('name')
                ->get()
                ->map(fn ($row) => ['id' => (int) $row->id, 'name' => $row->name])
                ->toArray(),
            'statuses' => ['in_stock', 'low_stock', 'out_of_stock'],
            'serializedOptions' => ['serialized', 'non_serialized'],
        ];
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