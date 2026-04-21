<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\SupplierProduct;

class PurchaseOrderItemsSeeder extends Seeder
{
    public function run(): void
    {
        $purchaseOrders = PurchaseOrder::all();

        foreach ($purchaseOrders as $purchaseOrder) {
            if ($purchaseOrder->items()->exists()) {
                continue;
            }

            $supplierProducts = SupplierProduct::with('product')
                ->where('supplier_id', $purchaseOrder->supplier_id)
                ->where('is_active', true)
                ->inRandomOrder()
                ->take(rand(4, 14))
                ->get();

            if ($supplierProducts->isEmpty()) {
                $fallbackProducts = Product::where('is_active', true)
                    ->inRandomOrder()
                    ->take(rand(4, 10))
                    ->get();

                foreach ($fallbackProducts as $product) {
                    $qty = $this->qtyForCategory($product->category?->name);
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
                    $qty = max($supplierProduct->min_order_qty ?? 1, $this->qtyForCategory($supplierProduct->product?->category?->name));
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
            $shipping = $purchaseOrder->status === 'cancelled' ? 0 : round(rand(20, 280), 2);
            $other = $purchaseOrder->status === 'cancelled' ? 0 : round(rand(0, 65), 2);

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
            'Cables & Adapters', 'Memory Cards', 'Thermal Solutions' => rand(20, 100),
            'Keyboards', 'Mice', 'Audio', 'Case Fans', 'Tools & Equipment' => rand(8, 35),
            'Operating Systems', 'Productivity Software', 'Security Software' => rand(5, 25),
            'Laptops', 'Desktop PCs', 'Tablets', 'Handhelds', 'Monitors' => rand(1, 10),
            default => rand(3, 24),
        };
    }

    private function retailPrice(?Product $product): float
    {
        if (!$product || !$product->description) {
            return rand(25, 1200);
        }

        if (preg_match('/Retail price approx:\s*\$([0-9,]+(?:\.[0-9]{1,2})?)/i', $product->description, $matches)) {
            return (float) str_replace(',', '', $matches[1]);
        }

        return rand(25, 1200);
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
