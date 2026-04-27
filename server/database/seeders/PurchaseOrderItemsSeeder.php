<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\SupplierProduct;
use Carbon\Carbon;
use Database\Seeders\Concerns\SeedsTimelineWindow;
use Illuminate\Database\Seeder;

class PurchaseOrderItemsSeeder extends Seeder
{
    use SeedsTimelineWindow;

    public function run(): void
    {
        $purchaseOrders = PurchaseOrder::all();

        foreach ($purchaseOrders as $purchaseOrder) {
            if ($purchaseOrder->items()->exists()) {
                continue;
            }

            $growthFactor = $this->growthFactorForDate(Carbon::parse($purchaseOrder->ordered_at), 0.95, 1.30);

            $supplierProducts = SupplierProduct::with('product')
                ->where('supplier_id', $purchaseOrder->supplier_id)
                ->where('is_active', true)
                ->inRandomOrder()
                ->take(random_int(4, 14))
                ->get();

            if ($supplierProducts->isEmpty()) {
                $fallbackProducts = Product::where('is_active', true)
                    ->inRandomOrder()
                    ->take(random_int(4, 10))
                    ->get();

                foreach ($fallbackProducts as $product) {
                    $baseQty = $this->qtyForCategory($product->category?->name);
                    $qty = max(1, (int) round($baseQty * $growthFactor));
                    $unitCost = round($this->retailPrice($product) * $this->costMultiplier($product->category?->name), 2);
                    $lineTotal = round($qty * $unitCost, 2);

                    PurchaseOrderItem::create([
                        'purchase_order_id' => $purchaseOrder->id,
                        'product_id' => $product->id,
                        'ordered_qty' => $qty,
                        'unit_cost' => $unitCost,
                        'line_total' => $lineTotal,
                    ]);
                }
            } else {
                foreach ($supplierProducts as $supplierProduct) {
                    $baseQty = $this->qtyForCategory($supplierProduct->product?->category?->name);
                    $qty = max(
                        $supplierProduct->min_order_qty ?? 1,
                        (int) round($baseQty * $growthFactor)
                    );
                    $unitCost = round((float) ($supplierProduct->last_cost ?? ($this->retailPrice($supplierProduct->product) * 0.65)), 2);
                    $lineTotal = round($qty * $unitCost, 2);

                    PurchaseOrderItem::create([
                        'purchase_order_id' => $purchaseOrder->id,
                        'product_id' => $supplierProduct->product_id,
                        'ordered_qty' => $qty,
                        'unit_cost' => $unitCost,
                        'line_total' => $lineTotal,
                    ]);
                }
            }

            $subtotal = round((float) $purchaseOrder->items()->sum('line_total'), 2);
            $taxAmount = round($subtotal * 0.08, 2);
            $shipping = $purchaseOrder->status === 'cancelled' ? 0 : round(random_int(20, 280), 2);
            $other = $purchaseOrder->status === 'cancelled' ? 0 : round(random_int(0, 65), 2);

            $purchaseOrder->update([
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'shipping_cost' => $shipping,
                'other_cost' => $other,
                'total_cost' => round($subtotal + $taxAmount + $shipping + $other, 2),
            ]);
        }
    }

    private function qtyForCategory(?string $category): int
    {
        return match ($category) {
            'Cables & Adapters', 'Memory Cards', 'Thermal Solutions' => random_int(24, 110),
            'Keyboards', 'Mice', 'Audio', 'Case Fans', 'Tools & Equipment' => random_int(10, 40),
            'Operating Systems', 'Productivity Software', 'Security Software' => random_int(6, 24),
            'Laptops', 'Desktop PCs', 'Tablets', 'Handhelds', 'Monitors' => random_int(2, 10),
            default => random_int(4, 26),
        };
    }

    private function retailPrice(?Product $product): float
    {
        if (!$product || !$product->description) {
            return random_int(25, 1200);
        }

        if (preg_match('/Retail price approx:\s*\$([0-9,]+(?:\.[0-9]{1,2})?)/i', $product->description, $matches)) {
            return (float) str_replace(',', '', $matches[1]);
        }

        return random_int(25, 1200);
    }

    private function costMultiplier(?string $category): float
    {
        return match ($category) {
            'Laptops', 'Desktop PCs', 'Tablets', 'Handhelds' => 0.68,
            'Processors (CPU)', 'Graphics Cards (GPU)', 'Motherboards', 'Internal SSDs', 'Monitors' => 0.66,
            'Operating Systems', 'Productivity Software', 'Security Software' => 0.58,
            'Cables & Adapters', 'Memory Cards', 'Thermal Solutions' => 0.52,
            default => 0.62,
        };
    }
}
