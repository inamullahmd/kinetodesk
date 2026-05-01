<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FinanceController extends Controller
{
    private const DEFAULT_START_DATE = '2026-03-01';
    private const DEFAULT_END_DATE = '2026-03-31';

    public function overview(Request $request): JsonResponse
    {
        $range = $this->resolveDateRange($request);

        return response()->json([
            'dateRange' => $range,
            'summary' => $this->summary($range['startDate'], $range['endDate']),
            'dailyTrend' => $this->dailyTrend($range['startDate'], $range['endDate']),
            'paymentStatusBreakdown' => $this->paymentStatusBreakdown($range['startDate'], $range['endDate']),
            'paymentMethodBreakdown' => $this->paymentMethodBreakdown($range['startDate'], $range['endDate']),
            'recentPayments' => $this->recentPayments($range['startDate'], $range['endDate']),
            'recentRefunds' => $this->recentRefunds($range['startDate'], $range['endDate']),
            'commissionPayouts' => $this->commissionPayouts($range['startDate'], $range['endDate']),
            'openReceivables' => $this->openReceivables($range['startDate'], $range['endDate']),
        ]);
    }

    protected function summary(string $startDate, string $endDate): array
    {
        $orders = DB::table('sales_orders')
            ->whereDate('ordered_at', '>=', $startDate)
            ->whereDate('ordered_at', '<=', $endDate)
            ->where('status', '!=', 'cancelled')
            ->select([
                DB::raw('COUNT(*) as order_count'),
                DB::raw('SUM(grand_total) as revenue'),
                DB::raw('SUM(discount_amount) as discounts'),
                DB::raw('SUM(tax_amount) as tax_collected'),
                DB::raw('SUM(shipping_fee) as shipping_charged'),
                DB::raw('SUM(CASE WHEN payment_status = "unpaid" THEN grand_total ELSE 0 END) as pending_receivables'),
                DB::raw('AVG(grand_total) as average_order_value'),
            ])
            ->first();

        $items = DB::table('sales_order_items')
            ->join('sales_orders', 'sales_order_items.sales_order_id', '=', 'sales_orders.id')
            ->whereDate('sales_orders.ordered_at', '>=', $startDate)
            ->whereDate('sales_orders.ordered_at', '<=', $endDate)
            ->where('sales_orders.status', '!=', 'cancelled')
            ->select([
                DB::raw('SUM(sales_order_items.qty) as units_sold'),
                DB::raw('SUM(sales_order_items.line_profit) as gross_profit'),
                DB::raw('SUM(sales_order_items.commission_total) as earned_commission'),
            ])
            ->first();

        $payments = DB::table('payments')
            ->whereDate('paid_at', '>=', $startDate)
            ->whereDate('paid_at', '<=', $endDate)
            ->select([
                DB::raw('SUM(CASE WHEN status = "paid" THEN amount ELSE 0 END) as payments_collected'),
                DB::raw('SUM(CASE WHEN status = "refunded" THEN amount ELSE 0 END) as payment_refunds'),
                DB::raw('SUM(CASE WHEN status = "refunded" THEN 1 ELSE 0 END) as payment_refund_count'),
            ])
            ->first();

        /*
         * Match Dashboard refund logic exactly:
         * Refund amount = SUM(returns.refund_amount)
         * Refund count  = COUNT(returns.id)
         * Date filter   = sales_orders.ordered_at
         */
        $returns = DB::table('returns')
            ->join('sales_orders', 'returns.sales_order_id', '=', 'sales_orders.id')
            ->whereDate('sales_orders.ordered_at', '>=', $startDate)
            ->whereDate('sales_orders.ordered_at', '<=', $endDate)
            ->select([
                DB::raw('COUNT(returns.id) as return_count'),
                DB::raw('COALESCE(SUM(returns.refund_amount), 0) as refund_amount'),
            ])
            ->first();

        $commissions = DB::table('commission_payouts')
            ->whereDate('period_start', '<=', $endDate)
            ->whereDate('period_end', '>=', $startDate)
            ->select([
                DB::raw('SUM(CASE WHEN status = "pending" THEN total_commission ELSE 0 END) as pending_commission'),
                DB::raw('SUM(CASE WHEN status = "paid" THEN total_commission ELSE 0 END) as paid_commission'),
            ])
            ->first();

        $revenue = (float) ($orders->revenue ?? 0);
        $grossProfit = (float) ($items->gross_profit ?? 0);
        $returnRefundAmount = (float) ($returns->refund_amount ?? 0);
        $returnCount = (int) ($returns->return_count ?? 0);
        $paymentRefundAmount = (float) ($payments->payment_refunds ?? 0);

        return [
            'revenue' => round($revenue, 2),
            'grossProfit' => round($grossProfit, 2),
            'grossMargin' => $revenue > 0 ? round(($grossProfit / $revenue) * 100, 1) : 0,
            'orderCount' => (int) ($orders->order_count ?? 0),
            'unitsSold' => (float) ($items->units_sold ?? 0),
            'averageOrderValue' => round((float) ($orders->average_order_value ?? 0), 2),
            'taxCollected' => round((float) ($orders->tax_collected ?? 0), 2),
            'discountsGiven' => round((float) ($orders->discounts ?? 0), 2),
            'shippingCharged' => round((float) ($orders->shipping_charged ?? 0), 2),
            'paymentsCollected' => round((float) ($payments->payments_collected ?? 0), 2),

            'paymentRefunds' => round($paymentRefundAmount, 2),
            'returnRefunds' => round($returnRefundAmount, 2),
            'returnCount' => $returnCount,
            'totalRefunds' => round($returnRefundAmount, 2),
            'refundRecordCount' => $returnCount,

            'pendingReceivables' => round((float) ($orders->pending_receivables ?? 0), 2),
            'earnedCommission' => round((float) ($items->earned_commission ?? 0), 2),
            'pendingCommission' => round((float) ($commissions->pending_commission ?? 0), 2),
            'paidCommission' => round((float) ($commissions->paid_commission ?? 0), 2),
        ];
    }

    protected function dailyTrend(string $startDate, string $endDate): array
    {
        $orderRows = DB::table('sales_orders')
            ->whereDate('ordered_at', '>=', $startDate)
            ->whereDate('ordered_at', '<=', $endDate)
            ->where('status', '!=', 'cancelled')
            ->selectRaw('DATE(ordered_at) as date')
            ->selectRaw('SUM(grand_total) as revenue')
            ->selectRaw('COUNT(*) as orders')
            ->groupByRaw('DATE(ordered_at)')
            ->get()
            ->keyBy('date');

        $profitRows = DB::table('sales_order_items')
            ->join('sales_orders', 'sales_order_items.sales_order_id', '=', 'sales_orders.id')
            ->whereDate('sales_orders.ordered_at', '>=', $startDate)
            ->whereDate('sales_orders.ordered_at', '<=', $endDate)
            ->where('sales_orders.status', '!=', 'cancelled')
            ->selectRaw('DATE(sales_orders.ordered_at) as date')
            ->selectRaw('SUM(sales_order_items.line_profit) as gross_profit')
            ->groupByRaw('DATE(sales_orders.ordered_at)')
            ->get()
            ->keyBy('date');

        $paymentRows = DB::table('payments')
            ->whereDate('paid_at', '>=', $startDate)
            ->whereDate('paid_at', '<=', $endDate)
            ->where('status', 'paid')
            ->selectRaw('DATE(paid_at) as date')
            ->selectRaw('SUM(amount) as payments')
            ->groupByRaw('DATE(paid_at)')
            ->get()
            ->keyBy('date');

        $rows = [];
        $cursor = CarbonImmutable::parse($startDate);
        $end = CarbonImmutable::parse($endDate);

        while ($cursor->lte($end)) {
            $date = $cursor->toDateString();

            $rows[] = [
                'date' => $date,
                'label' => $cursor->format('M j'),
                'revenue' => round((float) optional($orderRows->get($date))->revenue, 2),
                'grossProfit' => round((float) optional($profitRows->get($date))->gross_profit, 2),
                'payments' => round((float) optional($paymentRows->get($date))->payments, 2),
                'orders' => (int) optional($orderRows->get($date))->orders,
            ];

            $cursor = $cursor->addDay();
        }

        return $rows;
    }

    protected function paymentStatusBreakdown(string $startDate, string $endDate): array
    {
        return DB::table('sales_orders')
            ->whereDate('ordered_at', '>=', $startDate)
            ->whereDate('ordered_at', '<=', $endDate)
            ->where('status', '!=', 'cancelled')
            ->select([
                'payment_status',
                DB::raw('COUNT(*) as order_count'),
                DB::raw('SUM(grand_total) as total_value'),
            ])
            ->groupBy('payment_status')
            ->orderByDesc('total_value')
            ->get()
            ->map(fn ($row) => [
                'status' => $row->payment_status,
                'orderCount' => (int) $row->order_count,
                'totalValue' => round((float) $row->total_value, 2),
            ])
            ->toArray();
    }

    protected function paymentMethodBreakdown(string $startDate, string $endDate): array
    {
        return DB::table('payments')
            ->whereDate('paid_at', '>=', $startDate)
            ->whereDate('paid_at', '<=', $endDate)
            ->where('status', 'paid')
            ->select([
                'payment_method',
                DB::raw('COUNT(*) as payment_count'),
                DB::raw('SUM(amount) as total_amount'),
            ])
            ->groupBy('payment_method')
            ->orderByDesc('total_amount')
            ->get()
            ->map(fn ($row) => [
                'method' => $row->payment_method,
                'paymentCount' => (int) $row->payment_count,
                'totalAmount' => round((float) $row->total_amount, 2),
            ])
            ->toArray();
    }

    protected function recentPayments(string $startDate, string $endDate): array
    {
        return DB::table('payments')
            ->join('sales_orders', 'payments.sales_order_id', '=', 'sales_orders.id')
            ->leftJoin('customers', 'sales_orders.customer_id', '=', 'customers.id')
            ->whereDate('payments.paid_at', '>=', $startDate)
            ->whereDate('payments.paid_at', '<=', $endDate)
            ->where('payments.status', 'paid')
            ->orderByDesc('payments.paid_at')
            ->limit(10)
            ->select([
                'payments.id',
                'payments.payment_method',
                'payments.amount',
                'payments.status',
                'payments.transaction_reference',
                'payments.paid_at',
                'sales_orders.so_number',
                DB::raw('COALESCE(customers.business_name, TRIM(CONCAT(COALESCE(customers.first_name, ""), " ", COALESCE(customers.last_name, "")))) as customer_name'),
            ])
            ->get()
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'paidAt' => $this->dateString($row->paid_at),
                'salesOrderNumber' => $row->so_number,
                'customerName' => trim((string) $row->customer_name) ?: '-',
                'method' => $row->payment_method,
                'status' => $row->status,
                'amount' => round((float) $row->amount, 2),
                'transactionReference' => $row->transaction_reference,
            ])
            ->toArray();
    }

    protected function recentRefunds(string $startDate, string $endDate): array
    {
        return DB::table('returns')
            ->join('sales_orders', 'returns.sales_order_id', '=', 'sales_orders.id')
            ->leftJoin('customers', 'sales_orders.customer_id', '=', 'customers.id')
            ->whereDate('sales_orders.ordered_at', '>=', $startDate)
            ->whereDate('sales_orders.ordered_at', '<=', $endDate)
            ->orderByDesc('returns.created_at')
            ->limit(10)
            ->select([
                'returns.id',
                'returns.return_number',
                'returns.status',
                'returns.reason',
                'returns.refund_amount',
                'returns.created_at',
                'sales_orders.so_number',
                DB::raw('COALESCE(customers.business_name, TRIM(CONCAT(COALESCE(customers.first_name, ""), " ", COALESCE(customers.last_name, "")))) as customer_name'),
            ])
            ->get()
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'createdAt' => $this->dateString($row->created_at),
                'returnNumber' => $row->return_number,
                'salesOrderNumber' => $row->so_number,
                'customerName' => trim((string) $row->customer_name) ?: '-',
                'status' => $row->status,
                'reason' => $row->reason,
                'refundAmount' => round((float) $row->refund_amount, 2),
                'source' => 'return',
            ])
            ->toArray();
    }

    protected function commissionPayouts(string $startDate, string $endDate): array
    {
        return DB::table('commission_payouts')
            ->join('employees', 'commission_payouts.employee_id', '=', 'employees.id')
            ->whereDate('commission_payouts.period_start', '<=', $endDate)
            ->whereDate('commission_payouts.period_end', '>=', $startDate)
            ->orderByDesc('commission_payouts.period_end')
            ->limit(10)
            ->select([
                'commission_payouts.id',
                'commission_payouts.period_start',
                'commission_payouts.period_end',
                'commission_payouts.total_commission',
                'commission_payouts.status',
                'commission_payouts.paid_at',
                'employees.employee_number',
                DB::raw('CONCAT(employees.first_name, " ", employees.last_name) as employee_name'),
            ])
            ->get()
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'periodStart' => $this->dateString($row->period_start),
                'periodEnd' => $this->dateString($row->period_end),
                'employeeNumber' => $row->employee_number,
                'employeeName' => $row->employee_name,
                'status' => $row->status,
                'paidAt' => $this->dateString($row->paid_at),
                'totalCommission' => round((float) $row->total_commission, 2),
            ])
            ->toArray();
    }

    protected function openReceivables(string $startDate, string $endDate): array
    {
        return DB::table('sales_orders')
            ->leftJoin('customers', 'sales_orders.customer_id', '=', 'customers.id')
            ->whereDate('sales_orders.ordered_at', '>=', $startDate)
            ->whereDate('sales_orders.ordered_at', '<=', $endDate)
            ->where('sales_orders.status', '!=', 'cancelled')
            ->where('sales_orders.payment_status', 'unpaid')
            ->orderByDesc('sales_orders.grand_total')
            ->limit(10)
            ->select([
                'sales_orders.id',
                'sales_orders.so_number',
                'sales_orders.ordered_at',
                'sales_orders.status',
                'sales_orders.channel',
                'sales_orders.grand_total',
                DB::raw('COALESCE(customers.business_name, TRIM(CONCAT(COALESCE(customers.first_name, ""), " ", COALESCE(customers.last_name, "")))) as customer_name'),
            ])
            ->get()
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'orderedAt' => $this->dateString($row->ordered_at),
                'salesOrderNumber' => $row->so_number,
                'customerName' => trim((string) $row->customer_name) ?: '-',
                'status' => $row->status,
                'channel' => $row->channel,
                'amountDue' => round((float) $row->grand_total, 2),
            ])
            ->toArray();
    }

    protected function resolveDateRange(Request $request): array
    {
        $startDate = (string) $request->query('startDate', self::DEFAULT_START_DATE);
        $endDate = (string) $request->query('endDate', self::DEFAULT_END_DATE);

        if ($startDate > $endDate) {
            $startDate = $endDate;
        }

        return [
            'startDate' => $startDate,
            'endDate' => $endDate,
        ];
    }

    protected function dateString($value): ?string
    {
        return $value ? CarbonImmutable::parse($value)->toDateString() : null;
    }
}