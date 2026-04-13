<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class OverviewController extends Controller
{
    public function kpis(): JsonResponse
    {
        $totalRevenue = SalesOrder::where('status', 'completed')->sum('total_amount');

        $totalOrders = SalesOrder::where('status', 'completed')->count();

        $averageOrderValue = $totalOrders > 0
            ? round($totalRevenue / $totalOrders, 2)
            : 0;

        $inventoryValue = Inventory::join('products', 'inventory.product_id', '=', 'products.id')
            ->select(DB::raw('SUM(inventory.stock_on_hand * products.cost_price) as total'))
            ->value('total');

        $lowStockCount = Inventory::whereColumn('stock_on_hand', '<=', 'reorder_level')->count();

        $purchaseSpend = PurchaseOrder::where('status', 'received')->sum('total_cost');

        return response()->json([
            'total_revenue' => round($totalRevenue, 2),
            'total_orders' => $totalOrders,
            'average_order_value' => $averageOrderValue,
            'inventory_value' => round($inventoryValue ?? 0, 2),
            'low_stock_count' => $lowStockCount,
            'purchase_spend' => round($purchaseSpend, 2),
        ]);
    }

    public function revenueTrend(): JsonResponse
    {
        $trend = SalesOrder::selectRaw('DATE(sold_at) as date, SUM(total_amount) as revenue')
            ->where('status', 'completed')
            ->where('sold_at', '>=', now()->subMonths(6))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

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

    public function recentSales(): JsonResponse
    {
        $recentSales = SalesOrder::with(['customer', 'product'])
            ->latest('sold_at')
            ->take(10)
            ->get()
            ->map(function ($sale) {
                return [
                    'order_number' => $sale->order_number,
                    'customer_name' => $sale->customer?->name,
                    'product_name' => $sale->product?->name,
                    'quantity' => $sale->quantity,
                    'unit_price' => $sale->unit_price,
                    'total_amount' => $sale->total_amount,
                    'status' => $sale->status,
                    'sold_at' => $sale->sold_at,
                ];
            });

        return response()->json($recentSales);
    }
}
