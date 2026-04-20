<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class ProfitService
{
    public function getProfitMargin(array $filters = []): array
    {
        $startDate = !empty($filters['start_date'])
            ? Carbon::parse($filters['start_date'])->startOfDay()
            : now()->subYears(15)->startOfDay();

        $endDate = !empty($filters['end_date'])
            ? Carbon::parse($filters['end_date'])->endOfDay()
            : now()->endOfDay();

        $base = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->leftJoin('product_supplier_prices as psp', function ($join) {
                $join->on('psp.product_id', '=', 'products.id')
                    ->where('psp.is_primary', '=', 1);
            })
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->whereIn('orders.status', ['completed', 'paid', 'partially_paid', 'confirmed']);

        $this->applyFilters($base, $filters);

        $summary = (clone $base)
            ->selectRaw('
                COALESCE(SUM(order_items.quantity * order_items.unit_price_at_sale), 0) as gross_sales,
                COALESCE(SUM(order_items.discount_amount), 0) as discounts,
                COALESCE(SUM(order_items.tax_amount), 0) as taxes,
                COALESCE(SUM(order_items.quantity * order_items.unit_cost_at_sale), 0) as total_cost,
                COUNT(DISTINCT orders.id) as orders_count,
                COUNT(order_items.id) as line_items_count
            ')
            ->first();

        $grossSales = (float) ($summary->gross_sales ?? 0);
        $discounts = (float) ($summary->discounts ?? 0);
        $taxes = (float) ($summary->taxes ?? 0);
        $totalCost = (float) ($summary->total_cost ?? 0);

        $netRevenue = $grossSales - $discounts + $taxes;
        $grossProfit = $netRevenue - $totalCost;
        $marginPercent = $netRevenue > 0 ? ($grossProfit / $netRevenue) * 100 : 0;

        $byCategory = (clone $base)
            ->join('categories', 'categories.id', '=', 'products.category_id')
            ->selectRaw('
                categories.id as category_id,
                categories.name as category_name,
                COALESCE(SUM(order_items.quantity * order_items.unit_price_at_sale), 0) as revenue,
                COALESCE(SUM(order_items.quantity * order_items.unit_cost_at_sale), 0) as cost
            ')
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('revenue')
            ->get()
            ->map(function ($row) {
                $revenue = (float) $row->revenue;
                $cost = (float) $row->cost;
                $profit = $revenue - $cost;

                return [
                    'category_id' => (int) $row->category_id,
                    'category_name' => $row->category_name,
                    'revenue' => round($revenue, 2),
                    'cost' => round($cost, 2),
                    'profit' => round($profit, 2),
                    'margin_percent' => $revenue > 0 ? round(($profit / $revenue) * 100, 2) : 0.00,
                ];
            });

        $bySupplier = (clone $base)
            ->leftJoin('suppliers', 'suppliers.id', '=', 'products.preferred_supplier_id')
            ->selectRaw('
                suppliers.id as supplier_id,
                suppliers.name as supplier_name,
                COALESCE(SUM(order_items.quantity * order_items.unit_price_at_sale), 0) as revenue,
                COALESCE(SUM(order_items.quantity * order_items.unit_cost_at_sale), 0) as cost
            ')
            ->groupBy('suppliers.id', 'suppliers.name')
            ->orderByDesc('revenue')
            ->get()
            ->map(function ($row) {
                $revenue = (float) $row->revenue;
                $cost = (float) $row->cost;
                $profit = $revenue - $cost;

                return [
                    'supplier_id' => $row->supplier_id ? (int) $row->supplier_id : null,
                    'supplier_name' => $row->supplier_name ?? 'Unassigned Supplier',
                    'revenue' => round($revenue, 2),
                    'cost' => round($cost, 2),
                    'profit' => round($profit, 2),
                    'margin_percent' => $revenue > 0 ? round(($profit / $revenue) * 100, 2) : 0.00,
                ];
            });

        return [
            'filters' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'supplier_id' => $filters['supplier_id'] ?? null,
                'category_id' => $filters['category_id'] ?? null,
            ],
            'summary' => [
                'orders_count' => (int) ($summary->orders_count ?? 0),
                'line_items_count' => (int) ($summary->line_items_count ?? 0),
                'gross_sales' => round($grossSales, 2),
                'discounts' => round($discounts, 2),
                'taxes' => round($taxes, 2),
                'net_revenue' => round($netRevenue, 2),
                'total_cost' => round($totalCost, 2),
                'gross_profit' => round($grossProfit, 2),
                'margin_percent' => round($marginPercent, 2),
            ],
            'breakdowns' => [
                'by_category' => $byCategory,
                'by_supplier' => $bySupplier,
            ],
        ];
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        if (!empty($filters['category_id'])) {
            $query->where('products.category_id', (int) $filters['category_id']);
        }

        if (!empty($filters['supplier_id'])) {
            $query->where('products.preferred_supplier_id', (int) $filters['supplier_id']);
        }
    }
}