<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OverviewController extends Controller
{
    private function resolveDateRange(Request $request, int $defaultMonths = 6): array
    {
        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->query('start_date'))->startOfDay()
            : now()->subMonths($defaultMonths)->startOfDay();

        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->query('end_date'))->endOfDay()
            : now()->endOfDay();

        return [$startDate, $endDate];
    }

    public function kpis(Request $request): JsonResponse
    {
        [$startDate, $endDate] = $this->resolveDateRange($request, 6);

        $salesQuery = SalesOrder::where('status', 'completed')
            ->whereBetween('sold_at', [$startDate, $endDate]);

        $purchaseQuery = PurchaseOrder::where('status', 'received')
            ->whereBetween('purchased_at', [$startDate, $endDate]);

        $totalRevenue = (clone $salesQuery)->sum('total_amount');
        $totalOrders = (clone $salesQuery)->count();
        $unitsSold = (clone $salesQuery)->sum('quantity');

        $averageOrderValue = $totalOrders > 0
            ? round($totalRevenue / $totalOrders, 2)
            : 0;

        $purchaseSpend = (clone $purchaseQuery)->sum('total_cost');

        $inventoryValue = Inventory::join('products', 'inventory.product_id', '=', 'products.id')
            ->select(DB::raw('SUM(inventory.stock_on_hand * products.cost_price) as total'))
            ->value('total');

        $lowStockCount = Inventory::whereColumn('stock_on_hand', '<=', 'reorder_level')->count();

        return response()->json([
            'total_revenue' => round($totalRevenue, 2),
            'total_orders' => (int) $totalOrders,
            'average_order_value' => $averageOrderValue,
            'units_sold' => (int) $unitsSold,
            'inventory_value' => round($inventoryValue ?? 0, 2),
            'low_stock_count' => (int) $lowStockCount,
            'purchase_spend' => round($purchaseSpend, 2),
        ]);
    }

    public function revenueTrend(Request $request): JsonResponse
    {
        [$startDate, $endDate] = $this->resolveDateRange($request, 6);

        $trend = SalesOrder::selectRaw('DATE(sold_at) as date, SUM(total_amount) as revenue')
            ->where('status', 'completed')
            ->whereBetween('sold_at', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($row) {
                return [
                    'date' => $row->date,
                    'revenue' => round((float) $row->revenue, 2),
                ];
            });

        return response()->json($trend);
    }

    public function lowStock(): JsonResponse
    {
        $lowStockItems = Inventory::with('product')
            ->whereColumn('stock_on_hand', '<=', 'reorder_level')
            ->orderBy('stock_on_hand')
            ->take(10)
            ->get()
            ->map(function ($item) {
                return [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product?->name,
                    'sku' => $item->product?->sku,
                    'stock_on_hand' => $item->stock_on_hand,
                    'reorder_level' => $item->reorder_level,
                ];
            });

        return response()->json($lowStockItems);
    }

    public function recentSales(Request $request): JsonResponse
    {
        [$startDate, $endDate] = $this->resolveDateRange($request, 6);

        $recentSales = SalesOrder::with(['customer', 'product'])
            ->whereBetween('sold_at', [$startDate, $endDate])
            ->latest('sold_at')
            ->take(10)
            ->get()
            ->map(function ($sale) {
                return [
                    'order_number' => $sale->order_number,
                    'customer_name' => $sale->customer?->name,
                    'product_name' => $sale->product?->name,
                    'quantity' => (int) $sale->quantity,
                    'unit_price' => (float) $sale->unit_price,
                    'total_amount' => (float) $sale->total_amount,
                    'status' => $sale->status,
                    'sold_at' => $sale->sold_at,
                ];
            });

        return response()->json($recentSales);
    }

    public function orderStatus(Request $request): JsonResponse
    {
        [$startDate, $endDate] = $this->resolveDateRange($request, 3);

        $statuses = SalesOrder::selectRaw('LOWER(status) as name, COUNT(*) as value')
            ->whereBetween('sold_at', [$startDate, $endDate])
            ->groupBy('name')
            ->orderBy('value', 'desc')
            ->get()
            ->map(function ($row) {
                return [
                    'name' => $row->name,
                    'value' => (int) $row->value,
                ];
            });

        return response()->json($statuses);
    }

    public function topProducts(Request $request): JsonResponse
    {
        [$startDate, $endDate] = $this->resolveDateRange($request, 6);

        $products = SalesOrder::join('products', 'sales_orders.product_id', '=', 'products.id')
            ->selectRaw('products.name as name, SUM(sales_orders.total_amount) as revenue')
            ->where('sales_orders.status', 'completed')
            ->whereBetween('sales_orders.sold_at', [$startDate, $endDate])
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get()
            ->map(function ($row) {
                return [
                    'name' => $row->name,
                    'revenue' => round((float) $row->revenue, 2),
                ];
            });

        return response()->json($products);
    }

    public function salesByCategory(Request $request): JsonResponse
    {
        [$startDate, $endDate] = $this->resolveDateRange($request, 6);

        $categories = SalesOrder::join('products', 'sales_orders.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->selectRaw('categories.name as name, SUM(sales_orders.total_amount) as value')
            ->where('sales_orders.status', 'completed')
            ->whereBetween('sales_orders.sold_at', [$startDate, $endDate])
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('value')
            ->limit(6)
            ->get()
            ->map(function ($row) {
                return [
                    'name' => $row->name,
                    'value' => round((float) $row->value, 2),
                ];
            });

        return response()->json($categories);
    }
}