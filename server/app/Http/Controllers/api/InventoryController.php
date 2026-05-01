<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Support\AppliesMultiColumnSorting;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    use AppliesMultiColumnSorting;

    private const DEFAULT_START_DATE = '2026-03-01';
    private const DEFAULT_END_DATE = '2026-03-31';
    private const LOW_STOCK_THRESHOLD = 5;
    private const STALE_STOCK_DAYS = 90;

    public function overview(Request $request): JsonResponse
    {
        $range = $this->resolveDateRange($request);
        $stockSummary = $this->stockSummarySubquery($range['endDate']);

        $baseQuery = DB::table('products')
            ->leftJoinSub($stockSummary, 'stock_summary', function ($join) {
                $join->on('products.id', '=', 'stock_summary.product_id');
            });

        $summary = (clone $baseQuery)
            ->select([
                DB::raw('COUNT(*) as product_count'),
                DB::raw('SUM(CASE WHEN products.is_active = 1 THEN 1 ELSE 0 END) as active_products'),
                DB::raw('SUM(CASE WHEN COALESCE(stock_summary.stock_qty, 0) <= 0 THEN 1 ELSE 0 END) as out_of_stock'),
                DB::raw('SUM(CASE WHEN COALESCE(stock_summary.stock_qty, 0) > 0 AND COALESCE(stock_summary.stock_qty, 0) <= ' . self::LOW_STOCK_THRESHOLD . ' THEN 1 ELSE 0 END) as low_stock'),
                DB::raw('SUM(COALESCE(stock_summary.stock_qty, 0)) as stock_units'),
                DB::raw('SUM(CASE WHEN products.is_serialized = 1 THEN COALESCE(stock_summary.stock_qty, 0) ELSE 0 END) as serialized_units'),
                DB::raw('SUM(COALESCE(stock_summary.inventory_value, 0)) as inventory_value'),
            ])
            ->first();

        $categoryBreakdown = DB::table('products')
            ->leftJoin('categories as product_category', 'products.category_id', '=', 'product_category.id')
            ->leftJoin('categories as parent_category', 'product_category.parent_id', '=', 'parent_category.id')
            ->leftJoinSub($stockSummary, 'stock_summary', function ($join) {
                $join->on('products.id', '=', 'stock_summary.product_id');
            })
            ->select([
                DB::raw('COALESCE(parent_category.name, product_category.name, "Uncategorized") as category'),
                DB::raw('COUNT(products.id) as product_count'),
                DB::raw('SUM(COALESCE(stock_summary.stock_qty, 0)) as stock_units'),
                DB::raw('SUM(COALESCE(stock_summary.inventory_value, 0)) as inventory_value'),
            ])
            ->groupBy(DB::raw('COALESCE(parent_category.name, product_category.name, "Uncategorized")'))
            ->orderByDesc('inventory_value')
            ->limit(8)
            ->get()
            ->map(fn($row) => [
                'categoryName' => $row->category,
                'productCount' => (int) $row->product_count,
                'stockQty' => (float) $row->stock_units,
                'inventoryValue' => round((float) $row->inventory_value, 2),

                // aliases for older/newer UI compatibility
                'category' => $row->category,
                'stockUnits' => (float) $row->stock_units,
            ])
            ->toArray();

        $lowStockProducts = $this->baseProductRowsQuery($range['endDate'])
            ->whereRaw('COALESCE(stock_summary.stock_qty, 0) <= ?', [self::LOW_STOCK_THRESHOLD])
            ->select($this->productRowSelectColumns())
            ->orderBy('stock_qty')
            ->orderBy('products.title')
            ->limit(4)
            ->get()
            ->map(fn($row) => $this->mapProductRow($row))
            ->toArray();

        $recentMovements = DB::table('stock_movements')
            ->join('products', 'stock_movements.product_id', '=', 'products.id')
            ->join('brands', 'products.brand_id', '=', 'brands.id')
            ->leftJoin('stock_batches', 'stock_movements.stock_batch_id', '=', 'stock_batches.id')
            ->select([
                'stock_movements.id',
                'stock_movements.moved_at',
                'stock_movements.movement_type',
                'stock_movements.qty_change',
                'stock_movements.unit_cost',
                'stock_movements.reference_type',
                'stock_movements.reference_id',
                'stock_batches.batch_code',
                'products.title as product_title',
                'products.internal_sku',
                'products.model_number',
                'brands.name as brand_name',
            ])
            ->orderByDesc('stock_movements.moved_at')
            ->limit(8)
            ->get()
            ->map(fn($row) => [
                'id' => (int) $row->id,
                'movedAt' => $this->dateString($row->moved_at),
                'movementType' => $row->movement_type,
                'qtyChange' => (float) $row->qty_change,
                'unitCost' => $row->unit_cost !== null ? round((float) $row->unit_cost, 2) : null,
                'referenceType' => $row->reference_type,
                'referenceId' => $row->reference_id,
                'batchCode' => $row->batch_code,
                'productTitle' => $row->product_title,
                'sku' => $row->internal_sku,
                'modelNumber' => $row->model_number,
                'brandName' => $row->brand_name,
            ])
            ->toArray();

        return response()->json([
            'summary' => [
                'totalProducts' => (int) ($summary->product_count ?? 0),
                'activeSkus' => (int) ($summary->active_products ?? 0),
                'inventoryValue' => round((float) ($summary->inventory_value ?? 0), 2),
                'lowStockItems' => (int) ($summary->low_stock ?? 0),
                'outOfStock' => (int) ($summary->out_of_stock ?? 0),
                'serializedUnits' => (float) ($summary->serialized_units ?? 0),

                // aliases for older/newer UI compatibility
                'productCount' => (int) ($summary->product_count ?? 0),
                'activeProducts' => (int) ($summary->active_products ?? 0),
                'lowStock' => (int) ($summary->low_stock ?? 0),
                'stockUnits' => (float) ($summary->stock_units ?? 0),
            ],
            'categoryBreakdown' => $categoryBreakdown,
            'lowStockProducts' => $lowStockProducts,
            'recentMovements' => $recentMovements,
        ]);
    }

    public function products(Request $request): JsonResponse
    {
        $range = $this->resolveDateRange($request);
        $pagination = $this->resolvePagination($request);

        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'statuses' => $this->arrayQuery($request, 'statuses'),
            'category' => trim((string) $request->query('category', 'all')),
            'brand' => trim((string) $request->query('brand', 'all')),
            'serialized' => trim((string) $request->query('serialized', 'all')),
        ];

        $query = $this->baseProductRowsQuery($range['endDate']);

        $this->applyProductFilters($query, $filters);
        $this->applyProductStatusFilters($query, $filters['statuses']);

        $total = (clone $query)->count('products.id');

        $rowsQuery = $query->select($this->productRowSelectColumns());

        $this->applySorts($rowsQuery, $request, $this->productAllowedSorts(), [
            ['field' => 'product', 'direction' => 'asc'],
        ]);

        $rows = $rowsQuery
            ->orderBy('products.id')
            ->offset($pagination['offset'])
            ->limit($pagination['perPage'])
            ->get()
            ->map(fn($row) => $this->mapProductRow($row))
            ->toArray();

        return response()->json([
            'rows' => $rows,
            'pagination' => $this->paginationPayload($pagination['page'], $pagination['perPage'], $total),
            'filterOptions' => $this->filterOptions(),
        ]);
    }

    public function alerts(Request $request): JsonResponse
    {
        $range = $this->resolveDateRange($request);
        $pagination = $this->resolvePagination($request);

        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'alertTypes' => $this->arrayQuery($request, 'alertTypes'),
            'category' => trim((string) $request->query('category', 'all')),
            'brand' => trim((string) $request->query('brand', 'all')),
        ];

        $query = $this->baseProductRowsQuery($range['endDate']);

        $this->applyProductFilters($query, [
            'search' => $filters['search'],
            'category' => $filters['category'],
            'brand' => $filters['brand'],
            'serialized' => 'all',
        ]);

        $this->applyAlertTypeFilters($query, $filters['alertTypes'], $range['endDate']);

        $total = (clone $query)->count('products.id');

        $rowsQuery = $query->select($this->productRowSelectColumns());

        $this->applySorts($rowsQuery, $request, $this->productAllowedSorts(), [
            ['field' => 'currentStock', 'direction' => 'asc'],
            ['field' => 'product', 'direction' => 'asc'],
        ]);

        $rows = $rowsQuery
            ->orderBy('products.id')
            ->offset($pagination['offset'])
            ->limit($pagination['perPage'])
            ->get()
            ->map(fn($row) => $this->mapAlertRow($row, $filters['alertTypes'], $range['endDate']))
            ->toArray();

        return response()->json([
            'summary' => $this->alertsSummary($range['endDate']),
            'rows' => $rows,
            'pagination' => $this->paginationPayload($pagination['page'], $pagination['perPage'], $total),
            'filterOptions' => [
                ...$this->filterOptions(),
                'alertTypes' => ['out_of_stock', 'low_stock', 'stale_stock'],
            ],
        ]);
    }

    public function movements(Request $request): JsonResponse
    {
        $range = $this->resolveDateRange($request);
        $pagination = $this->resolvePagination($request);

        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'movementTypes' => $this->arrayQuery($request, 'movementTypes'),
            'referenceTypes' => $this->arrayQuery($request, 'referenceTypes'),
            'direction' => trim((string) $request->query('direction', 'all')),
        ];

        $query = $this->baseStockMovementRowsQuery($range['startDate'], $range['endDate']);

        $this->applyStockMovementFilters($query, $filters);

        $total = (clone $query)->count('stock_movements.id');
        $summary = $this->stockMovementSummary(clone $query);

        $rowsQuery = $query->select($this->stockMovementSelectColumns());

        $this->applySorts($rowsQuery, $request, $this->movementAllowedSorts(), [
            ['field' => 'movedAt', 'direction' => 'desc'],
        ]);

        $rows = $rowsQuery
            ->orderByDesc('stock_movements.id')
            ->offset($pagination['offset'])
            ->limit($pagination['perPage'])
            ->get()
            ->map(fn($row) => $this->mapStockMovementRow($row))
            ->toArray();

        return response()->json([
            'summary' => $summary,
            'rows' => $rows,
            'pagination' => $this->paginationPayload($pagination['page'], $pagination['perPage'], $total),
            'filterOptions' => $this->movementFilterOptions(),
        ]);
    }

    public function productDetail(int $id, Request $request): JsonResponse
    {
        $range = $this->resolveDateRange($request);

        $product = $this->baseProductRowsQuery($range['endDate'])
            ->where('products.id', $id)
            ->select($this->productRowSelectColumns())
            ->first();

        if (! $product) {
            return response()->json([
                'message' => 'Product not found.',
            ], 404);
        }

        $mappedProduct = $this->mapProductRow($product);

        return response()->json([
            ...$mappedProduct,
            'stockQty' => $mappedProduct['currentStock'],
            'batches' => $this->productBatches($id),
            'movements' => $this->productMovements($id),
            'serials' => $this->productSerials($id),
        ]);
    }

    protected function stockSummarySubquery(string $endDate)
    {
        return DB::table('stock_batches')
            ->select([
                'product_id',
                DB::raw('SUM(qty_remaining) as stock_qty'),
                DB::raw('SUM(qty_remaining * unit_cost) as inventory_value'),
                DB::raw('AVG(unit_cost) as avg_unit_cost'),
                DB::raw('MAX(received_at) as last_received_at'),
            ])
            ->whereDate('received_at', '<=', $endDate)
            ->groupBy('product_id');
    }

    protected function lastSalesSubquery(string $endDate)
    {
        return DB::table('sales_order_items')
            ->join('sales_orders', 'sales_order_items.sales_order_id', '=', 'sales_orders.id')
            ->select([
                'sales_order_items.product_id',
                DB::raw('MAX(sales_orders.ordered_at) as last_sold_at'),
            ])
            ->where('sales_orders.status', 'delivered')
            ->whereDate('sales_orders.ordered_at', '<=', $endDate)
            ->groupBy('sales_order_items.product_id');
    }

    protected function baseProductRowsQuery(string $endDate)
    {
        return DB::table('products')
            ->join('brands', 'products.brand_id', '=', 'brands.id')
            ->leftJoin('categories as product_category', 'products.category_id', '=', 'product_category.id')
            ->leftJoin('categories as parent_category', 'product_category.parent_id', '=', 'parent_category.id')
            ->leftJoinSub($this->stockSummarySubquery($endDate), 'stock_summary', function ($join) {
                $join->on('products.id', '=', 'stock_summary.product_id');
            })
            ->leftJoinSub($this->lastSalesSubquery($endDate), 'last_sales', function ($join) {
                $join->on('products.id', '=', 'last_sales.product_id');
            });
    }

    protected function productRowSelectColumns(): array
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

    protected function applyProductFilters($query, array $filters): void
    {
        $search = trim((string) ($filters['search'] ?? ''));

        if ($search !== '') {
            $like = '%' . $search . '%';

            $query->where(function ($query) use ($like) {
                $query
                    ->where('products.title', 'like', $like)
                    ->orWhere('products.internal_sku', 'like', $like)
                    ->orWhere('products.model_number', 'like', $like)
                    ->orWhere('brands.name', 'like', $like)
                    ->orWhere('product_category.name', 'like', $like)
                    ->orWhere('parent_category.name', 'like', $like);
            });
        }

        $category = trim((string) ($filters['category'] ?? 'all'));

        if ($category !== '' && $category !== 'all') {
            $query->where(function ($query) use ($category) {
                $query
                    ->where('product_category.id', $category)
                    ->orWhere('parent_category.id', $category);
            });
        }

        $brand = trim((string) ($filters['brand'] ?? 'all'));

        if ($brand !== '' && $brand !== 'all') {
            $query->where('brands.id', $brand);
        }

        $serialized = trim((string) ($filters['serialized'] ?? 'all'));

        if ($serialized === 'serialized') {
            $query->where('products.is_serialized', true);
        }

        if ($serialized === 'non_serialized') {
            $query->where('products.is_serialized', false);
        }
    }

    protected function applyProductStatusFilters($query, array $statuses): void
    {
        $statuses = array_values(array_filter($statuses));

        if (! count($statuses)) {
            return;
        }

        $query->where(function ($query) use ($statuses) {
            foreach ($statuses as $status) {
                match ($status) {
                    'inactive' => $query->orWhere('products.is_active', false),
                    'active' => $query->orWhere('products.is_active', true),
                    'out_of_stock' => $query->orWhereRaw('COALESCE(stock_summary.stock_qty, 0) <= 0'),
                    'low_stock' => $query->orWhereRaw(
                        'COALESCE(stock_summary.stock_qty, 0) > 0 AND COALESCE(stock_summary.stock_qty, 0) <= ?',
                        [self::LOW_STOCK_THRESHOLD]
                    ),
                    'in_stock' => $query->orWhereRaw('COALESCE(stock_summary.stock_qty, 0) > ?', [self::LOW_STOCK_THRESHOLD]),
                    default => null,
                };
            }
        });
    }

    protected function applyAlertTypeFilters($query, array $alertTypes, string $endDate): void
    {
        $alertTypes = array_values(array_filter($alertTypes));
        $selected = count($alertTypes)
            ? $alertTypes
            : ['out_of_stock', 'low_stock', 'stale_stock'];

        $query->where(function ($query) use ($selected, $endDate) {
            foreach ($selected as $alertType) {
                match ($alertType) {
                    'out_of_stock' => $query->orWhereRaw('COALESCE(stock_summary.stock_qty, 0) <= 0'),
                    'low_stock' => $query->orWhereRaw(
                        'COALESCE(stock_summary.stock_qty, 0) > 0 AND COALESCE(stock_summary.stock_qty, 0) <= ?',
                        [self::LOW_STOCK_THRESHOLD]
                    ),
                    'stale_stock' => $query->orWhere(function ($query) use ($endDate) {
                        $query
                            ->whereRaw('COALESCE(stock_summary.stock_qty, 0) > 0')
                            ->where(function ($query) use ($endDate) {
                                $query
                                    ->whereNull('last_sales.last_sold_at')
                                    ->orWhereRaw('DATEDIFF(?, last_sales.last_sold_at) >= ?', [
                                        $endDate,
                                        self::STALE_STOCK_DAYS,
                                    ]);
                            });
                    }),
                    default => null,
                };
            }
        });
    }

    protected function productAllowedSorts(): array
    {
        return [
            'product' => 'products.title',
            'sku' => 'products.internal_sku',
            'brand' => 'brands.name',
            'category' => DB::raw('COALESCE(parent_category.name, product_category.name)'),
            'currentStock' => DB::raw('COALESCE(stock_summary.stock_qty, 0)'),
            'inventoryValue' => DB::raw('COALESCE(stock_summary.inventory_value, 0)'),
            'avgUnitCost' => DB::raw('COALESCE(stock_summary.avg_unit_cost, 0)'),
            'lastReceivedAt' => function ($query, string $direction) {
                $query->orderByRaw('stock_summary.last_received_at IS NULL asc');
                $query->orderBy('stock_summary.last_received_at', $direction);
            },
            'lastSoldAt' => function ($query, string $direction) {
                $query->orderByRaw('last_sales.last_sold_at IS NULL asc');
                $query->orderBy('last_sales.last_sold_at', $direction);
            },
            'status' => function ($query, string $direction) {
                $query->orderByRaw("
                    CASE
                        WHEN products.is_active = 0 THEN 4
                        WHEN COALESCE(stock_summary.stock_qty, 0) <= 0 THEN 0
                        WHEN COALESCE(stock_summary.stock_qty, 0) <= " . self::LOW_STOCK_THRESHOLD . " THEN 1
                        ELSE 2
                    END {$direction}
                ");
            },
        ];
    }

    protected function mapProductRow($row): array
    {
        $stockQty = (float) $row->stock_qty;

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
            'currentStock' => $stockQty,
            'inventoryValue' => round((float) $row->inventory_value, 2),
            'avgUnitCost' => round((float) $row->avg_unit_cost, 2),
            'lastReceivedAt' => $this->dateString($row->last_received_at),
            'lastSoldAt' => $this->dateString($row->last_sold_at),
            'status' => $this->resolveProductStatus((bool) $row->is_active, $stockQty),
            'alertType' => null,
        ];
    }

    protected function mapAlertRow($row, array $selectedAlertTypes, string $endDate): array
    {
        $base = $this->mapProductRow($row);
        $alertType = $this->resolveAlertType($row, $selectedAlertTypes, $endDate);

        return [
            ...$base,
            'status' => $alertType,
            'alertType' => $alertType,
        ];
    }

    protected function resolveProductStatus(bool $isActive, float $stockQty): string
    {
        if (! $isActive) {
            return 'inactive';
        }

        if ($stockQty <= 0) {
            return 'out_of_stock';
        }

        if ($stockQty <= self::LOW_STOCK_THRESHOLD) {
            return 'low_stock';
        }

        return 'in_stock';
    }

    protected function resolveAlertType($row, array $selectedAlertTypes, string $endDate): string
    {
        $stockQty = (float) $row->stock_qty;
        $isStale = $this->isStaleStock($row->last_sold_at, $stockQty, $endDate);

        if (
            in_array('stale_stock', $selectedAlertTypes, true)
            && $isStale
        ) {
            return 'stale_stock';
        }

        if ($stockQty <= 0) {
            return 'out_of_stock';
        }

        if ($stockQty <= self::LOW_STOCK_THRESHOLD) {
            return 'low_stock';
        }

        if ($isStale) {
            return 'stale_stock';
        }

        return 'healthy';
    }

    protected function isStaleStock($lastSoldAt, float $stockQty, string $endDate): bool
    {
        if ($stockQty <= 0) {
            return false;
        }

        if (! $lastSoldAt) {
            return true;
        }

        return CarbonImmutable::parse($lastSoldAt)
            ->diffInDays(CarbonImmutable::parse($endDate)) >= self::STALE_STOCK_DAYS;
    }

    protected function alertsSummary(string $endDate): array
    {
        $row = $this->baseProductRowsQuery($endDate)
            ->selectRaw('SUM(CASE WHEN COALESCE(stock_summary.stock_qty, 0) <= 0 THEN 1 ELSE 0 END) as out_of_stock')
            ->selectRaw(
                'SUM(CASE WHEN COALESCE(stock_summary.stock_qty, 0) > 0 AND COALESCE(stock_summary.stock_qty, 0) <= ? THEN 1 ELSE 0 END) as low_stock',
                [self::LOW_STOCK_THRESHOLD]
            )
            ->selectRaw(
                'SUM(CASE WHEN COALESCE(stock_summary.stock_qty, 0) > 0 AND (last_sales.last_sold_at IS NULL OR DATEDIFF(?, last_sales.last_sold_at) >= ?) THEN 1 ELSE 0 END) as stale_stock',
                [$endDate, self::STALE_STOCK_DAYS]
            )
            ->first();

        return [
            'outOfStock' => (int) ($row->out_of_stock ?? 0),
            'lowStock' => (int) ($row->low_stock ?? 0),
            'staleStock' => (int) ($row->stale_stock ?? 0),
        ];
    }

    protected function productBatches(int $productId): array
{
    return DB::table('stock_batches')
        ->leftJoin('purchase_order_items', 'stock_batches.purchase_order_item_id', '=', 'purchase_order_items.id')
        ->leftJoin('purchase_orders', 'purchase_order_items.purchase_order_id', '=', 'purchase_orders.id')
        ->where('stock_batches.product_id', $productId)
        ->where('stock_batches.qty_remaining', '>', 0)
        ->select([
            'stock_batches.id',
            'stock_batches.batch_code',
            'stock_batches.received_at',
            'stock_batches.qty_received',
            'stock_batches.qty_remaining',
            'stock_batches.unit_cost',
            DB::raw('(stock_batches.qty_remaining * stock_batches.unit_cost) as remaining_value'),
            'purchase_orders.po_number as purchase_order_number',
        ])
        ->orderByDesc('stock_batches.received_at')
        ->limit(20)
        ->get()
        ->map(fn ($row) => [
            'id' => (int) $row->id,
            'batchCode' => $row->batch_code,
            'receivedAt' => $this->dateString($row->received_at),
            'qtyReceived' => (float) $row->qty_received,
            'qtyRemaining' => (float) $row->qty_remaining,
            'unitCost' => round((float) $row->unit_cost, 2),
            'remainingValue' => round((float) $row->remaining_value, 2),
            'purchaseOrderNumber' => $row->purchase_order_number,
        ])
        ->toArray();
}

    protected function productMovements(int $productId): array
    {
        return DB::table('stock_movements')
            ->leftJoin('stock_batches', 'stock_movements.stock_batch_id', '=', 'stock_batches.id')
            ->where('stock_movements.product_id', $productId)
            ->select([
                'stock_movements.id',
                'stock_movements.moved_at',
                'stock_movements.movement_type',
                'stock_movements.qty_change',
                'stock_movements.unit_cost',
                'stock_movements.reference_type',
                'stock_movements.reference_id',
                'stock_batches.batch_code',
            ])
            ->orderByDesc('stock_movements.moved_at')
            ->limit(30)
            ->get()
            ->map(fn($row) => [
                'id' => (int) $row->id,
                'movedAt' => $this->dateString($row->moved_at),
                'movementType' => $row->movement_type,
                'qtyChange' => (float) $row->qty_change,
                'unitCost' => $row->unit_cost !== null ? round((float) $row->unit_cost, 2) : null,
                'referenceType' => $row->reference_type,
                'referenceId' => $row->reference_id,
                'batchCode' => $row->batch_code,
            ])
            ->toArray();
    }

    protected function productSerials(int $productId): array
{
    return DB::table('serial_numbers')
        ->leftJoin('stock_batches', 'serial_numbers.stock_batch_id', '=', 'stock_batches.id')
        ->where('serial_numbers.product_id', $productId)
        ->select([
            'serial_numbers.id',
            'serial_numbers.serial_number',
            'serial_numbers.status',
            'stock_batches.batch_code',
            'stock_batches.received_at as batch_received_at',
        ])
        ->orderBy('serial_numbers.serial_number')
        ->limit(50)
        ->get()
        ->map(fn ($row) => [
            'id' => (int) $row->id,
            'serialNumber' => $row->serial_number,
            'status' => $row->status,
            'receivedAt' => $this->dateString($row->batch_received_at),
            'batchCode' => $row->batch_code,
        ])
        ->toArray();
}

    protected function filterOptions(): array
    {
        $categories = DB::table('categories')
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get()
            ->map(fn($row) => [
                'id' => (int) $row->id,
                'name' => $row->name,
            ])
            ->toArray();

        $brands = DB::table('brands')
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get()
            ->map(fn($row) => [
                'id' => (int) $row->id,
                'name' => $row->name,
            ])
            ->toArray();

        return [
            'categories' => $categories,
            'brands' => $brands,
            'statuses' => ['in_stock', 'low_stock', 'out_of_stock', 'inactive'],
            'serializedOptions' => ['serialized', 'non_serialized'],
        ];
    }

    protected function resolveDateRange(Request $request): array
    {
        $startDate = (string) $request->query('startDate', self::DEFAULT_START_DATE);
        $endDate = (string) $request->query('endDate', self::DEFAULT_END_DATE);

        if ($endDate > self::DEFAULT_END_DATE) {
            $endDate = self::DEFAULT_END_DATE;
        }

        if ($startDate > $endDate) {
            $startDate = $endDate;
        }

        return [
            'startDate' => $startDate,
            'endDate' => $endDate,
        ];
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

    protected function arrayQuery(Request $request, string $key): array
    {
        $value = $request->query($key, []);

        if (is_array($value)) {
            return array_values(array_filter($value, fn($item) => $item !== null && $item !== ''));
        }

        if (is_string($value) && trim($value) !== '') {
            return array_values(array_filter(explode(',', $value)));
        }

        return [];
    }

    protected function dateString($value): ?string
    {
        return $value ? CarbonImmutable::parse($value)->toDateString() : null;
    }

    protected function baseStockMovementRowsQuery(string $startDate, string $endDate)
    {
        return DB::table('stock_movements')
            ->join('products', 'stock_movements.product_id', '=', 'products.id')
            ->join('brands', 'products.brand_id', '=', 'brands.id')
            ->leftJoin('stock_batches', 'stock_movements.stock_batch_id', '=', 'stock_batches.id')
            ->whereDate('stock_movements.moved_at', '>=', $startDate)
            ->whereDate('stock_movements.moved_at', '<=', $endDate);
    }

    protected function stockMovementSelectColumns(): array
    {
        return [
            'stock_movements.id',
            'stock_movements.product_id',
            'stock_movements.moved_at',
            'stock_movements.movement_type',
            'stock_movements.qty_change',
            'stock_movements.unit_cost',
            'stock_movements.reference_type',
            'stock_movements.reference_id',
            'stock_batches.batch_code',
            'products.title as product_title',
            'products.internal_sku',
            'products.model_number',
            'brands.name as brand_name',
            DB::raw('ABS(stock_movements.qty_change) * COALESCE(stock_movements.unit_cost, 0) as movement_value'),
        ];
    }

    protected function applyStockMovementFilters($query, array $filters): void
    {
        $search = trim((string) ($filters['search'] ?? ''));

        if ($search !== '') {
            $like = '%' . $search . '%';

            $query->where(function ($query) use ($like) {
                $query
                    ->where('products.title', 'like', $like)
                    ->orWhere('products.internal_sku', 'like', $like)
                    ->orWhere('products.model_number', 'like', $like)
                    ->orWhere('brands.name', 'like', $like)
                    ->orWhere('stock_batches.batch_code', 'like', $like)
                    ->orWhere('stock_movements.reference_type', 'like', $like)
                    ->orWhere('stock_movements.reference_id', 'like', $like);
            });
        }

        $movementTypes = array_values(array_filter($filters['movementTypes'] ?? []));

        if (count($movementTypes)) {
            $query->whereIn('stock_movements.movement_type', $movementTypes);
        }

        $referenceTypes = array_values(array_filter($filters['referenceTypes'] ?? []));

        if (count($referenceTypes)) {
            $query->whereIn('stock_movements.reference_type', $referenceTypes);
        }

        $direction = trim((string) ($filters['direction'] ?? 'all'));

        if ($direction === 'inbound') {
            $query->where('stock_movements.qty_change', '>', 0);
        }

        if ($direction === 'outbound') {
            $query->where('stock_movements.qty_change', '<', 0);
        }
    }

    protected function stockMovementSummary($query): array
    {
        $row = $query
            ->selectRaw('COUNT(stock_movements.id) as total_movements')
            ->selectRaw('SUM(CASE WHEN stock_movements.qty_change > 0 THEN stock_movements.qty_change ELSE 0 END) as inbound_units')
            ->selectRaw('SUM(CASE WHEN stock_movements.qty_change < 0 THEN ABS(stock_movements.qty_change) ELSE 0 END) as outbound_units')
            ->selectRaw('SUM(stock_movements.qty_change) as net_qty_change')
            ->selectRaw('SUM(ABS(stock_movements.qty_change) * COALESCE(stock_movements.unit_cost, 0)) as inventory_value_moved')
            ->first();

        return [
            'totalMovements' => (int) ($row->total_movements ?? 0),
            'inboundUnits' => (float) ($row->inbound_units ?? 0),
            'outboundUnits' => (float) ($row->outbound_units ?? 0),
            'netQtyChange' => (float) ($row->net_qty_change ?? 0),
            'inventoryValueMoved' => round((float) ($row->inventory_value_moved ?? 0), 2),
        ];
    }

    protected function movementAllowedSorts(): array
    {
        return [
            'movedAt' => 'stock_movements.moved_at',
            'product' => 'products.title',
            'sku' => 'products.internal_sku',
            'brand' => 'brands.name',
            'movementType' => 'stock_movements.movement_type',
            'qtyChange' => 'stock_movements.qty_change',
            'unitCost' => 'stock_movements.unit_cost',
            'movementValue' => DB::raw('ABS(stock_movements.qty_change) * COALESCE(stock_movements.unit_cost, 0)'),
            'batch' => function ($query, string $direction) {
                $query->orderByRaw('stock_batches.batch_code IS NULL asc');
                $query->orderBy('stock_batches.batch_code', $direction);
            },
            'reference' => function ($query, string $direction) {
                $query->orderBy('stock_movements.reference_type', $direction);
                $query->orderBy('stock_movements.reference_id', $direction);
            },
        ];
    }

    protected function movementFilterOptions(): array
    {
        $movementTypes = DB::table('stock_movements')
            ->whereNotNull('movement_type')
            ->distinct()
            ->orderBy('movement_type')
            ->pluck('movement_type')
            ->values()
            ->toArray();

        $referenceTypes = DB::table('stock_movements')
            ->whereNotNull('reference_type')
            ->distinct()
            ->orderBy('reference_type')
            ->pluck('reference_type')
            ->values()
            ->toArray();

        return [
            'movementTypes' => $movementTypes,
            'referenceTypes' => $referenceTypes,
            'directions' => ['inbound', 'outbound'],
        ];
    }

    protected function mapStockMovementRow($row): array
    {
        return [
            'id' => (int) $row->id,
            'productId' => (int) $row->product_id,
            'movedAt' => $this->dateString($row->moved_at),
            'movementType' => $row->movement_type,
            'qtyChange' => (float) $row->qty_change,
            'unitCost' => $row->unit_cost !== null ? round((float) $row->unit_cost, 2) : null,
            'movementValue' => round((float) $row->movement_value, 2),
            'referenceType' => $row->reference_type,
            'referenceId' => $row->reference_id,
            'batchCode' => $row->batch_code,
            'productTitle' => $row->product_title,
            'sku' => $row->internal_sku,
            'modelNumber' => $row->model_number,
            'brandName' => $row->brand_name,
        ];
    }
}
