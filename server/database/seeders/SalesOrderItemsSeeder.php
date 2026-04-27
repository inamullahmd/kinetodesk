<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use Carbon\Carbon;
use Database\Seeders\Concerns\SeedsTimelineWindow;
use Illuminate\Database\Seeder;

class SalesOrderItemsSeeder extends Seeder
{
    use SeedsTimelineWindow;

    public function run(): void
    {
        $orders = SalesOrder::with(['customer'])->get();

        foreach ($orders as $order) {
            if ($order->items()->exists()) {
                continue;
            }

            $itemsCount = $order->customer?->customer_type === 'business'
                ? random_int(3, 10)
                : random_int(1, 4);

            $products = Product::where('is_active', true)
                ->inRandomOrder()
                ->take($itemsCount)
                ->get();

            $growthFactor = $this->growthFactorForDate(Carbon::parse($order->ordered_at), 0.92, 1.18);

            foreach ($products as $product) {
                $baseQty = $this->qtyForCustomerType(
                    $order->customer?->customer_type ?? 'individual',
                    $product->category?->name
                );
                $qty = max(1, (int) round($baseQty * $growthFactor));

                $unitPrice = $this->extractRetailPrice($product->description) ?? random_int(20, 1500);
                $costBasis = round($unitPrice / 1.55, 2);
                $minAllowedPrice = round($costBasis * 1.4, 2);

                $discountAmount = $this->discountForOrder($order->customer?->customer_type ?? 'individual', $order->channel, $qty, $unitPrice);
                $discountPerUnit = round($discountAmount / max($qty, 1), 2);
                $finalUnitPrice = max($minAllowedPrice, round($unitPrice - $discountPerUnit, 2));

                $lineSubtotal = round($qty * $unitPrice, 2);
                $lineTotal = round($qty * $finalUnitPrice, 2);

                $employeeId = $order->employee_id;
                $commissionPerUnit = $employeeId ? (float) $product->commission_value : 0;
                $commissionTotal = round($commissionPerUnit * $qty, 2);
                $lineProfit = round($lineTotal - ($qty * $costBasis) - $commissionTotal, 2);

                SalesOrderItem::create([
                    'sales_order_id' => $order->id,
                    'product_id' => $product->id,
                    'employee_id' => $employeeId,
                    'qty' => $qty,
                    'unit_price' => $unitPrice,
                    'discount_amount' => $discountAmount,
                    'final_unit_price' => $finalUnitPrice,
                    'cost_basis' => $costBasis,
                    'min_allowed_price' => $minAllowedPrice,
                    'commission_per_unit' => $commissionPerUnit,
                    'commission_total' => $commissionTotal,
                    'line_subtotal' => $lineSubtotal,
                    'line_total' => $lineTotal,
                    'line_profit' => $lineProfit,
                ]);
            }

            $subtotal = round((float) $order->items()->sum('line_subtotal'), 2);
            $discount = round((float) $order->items()->sum('discount_amount'), 2);
            $tax = round(max($subtotal - $discount, 0) * 0.08, 2);
            $shipping = (float) ($order->shipping_fee ?? 0);

            $order->update([
                'subtotal' => $subtotal,
                'discount_amount' => $discount,
                'tax_amount' => $tax,
                'grand_total' => round($subtotal - $discount + $tax + $shipping, 2),
            ]);
        }
    }

    private function qtyForCustomerType(string $type, ?string $category): int
    {
        if ($type === 'business') {
            return match ($category) {
                'Cables & Adapters', 'Memory Cards', 'Thermal Solutions' => random_int(8, 40),
                'Keyboards', 'Mice', 'Audio', 'Monitors' => random_int(3, 12),
                default => random_int(2, 8),
            };
        }

        return match ($category) {
            'Cables & Adapters', 'Memory Cards', 'Thermal Solutions' => random_int(1, 5),
            default => random_int(1, 3),
        };
    }

    private function discountForOrder(string $customerType, string $channel, int $qty, float $unitPrice): float
    {
        $base = 0;

        if ($customerType === 'business' && $qty >= 5) {
            $base += ($unitPrice * $qty) * 0.05;
        }

        if ($channel === 'online' && random_int(1, 100) <= 18) {
            $base += ($unitPrice * $qty) * 0.03;
        }

        return round($base, 2);
    }

    private function extractRetailPrice(?string $description): ?float
    {
        if (!$description) {
            return null;
        }

        if (preg_match('/Retail price approx:\s*\$([0-9,]+(?:\.[0-9]{1,2})?)/i', $description, $matches)) {
            return (float) str_replace(',', '', $matches[1]);
        }

        return null;
    }
}
