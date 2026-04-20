<?php

namespace App\Services;

use App\Models\CustomBuildOrder;
use App\Models\CustomBuildOrderItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductSupplierPrice;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CustomBuildService
{
    private InventoryService $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * Create a custom build order with components and parent order.
     */
    public function createCustomBuild(
        int $customerId,
        int $buildTemplateId,
        string $channel,
        array $items,
        ?float $laborCharge = null,
        ?string $assemblyNotes = null
    ): CustomBuildOrder {
        return DB::transaction(function () use ($customerId, $buildTemplateId, $channel, $items, $laborCharge, $assemblyNotes) {
            $pricingDate = now();

            foreach ($items as $item) {
                /** @var Product $product */
                $product = Product::query()->findOrFail($item['product_id']);
                $quantity = (int) $item['quantity'];
                $availableStock = $this->inventoryService->getAvailableStock($product);

                if ($availableStock < $quantity) {
                    throw new \Exception("Product {$product->sku} has insufficient stock.");
                }

                if ($product->is_serialized) {
                    if ($quantity !== 1) {
                        throw new \Exception("Serialized product {$product->sku} must use quantity 1 per build line.");
                    }

                    if (empty($item['serial_id'])) {
                        throw new \Exception("Serialized product {$product->sku} requires a serial_id.");
                    }

                    $serial = $product->serials()
                        ->whereKey((int) $item['serial_id'])
                        ->where('status', 'in_stock')
                        ->first();

                    if (! $serial) {
                        throw new \Exception("Serial for product {$product->sku} is invalid or not available.");
                    }
                }
            }

            $orderNumber = 'ORD-' . now()->format('Ymd') . '-' . str_pad((string) (Order::count() + 1), 5, '0', STR_PAD_LEFT);

            $subtotal = 0.0;
            $componentCostTotal = 0.0;

            foreach ($items as $item) {
                /** @var Product $product */
                $product = Product::query()->findOrFail($item['product_id']);
                $quantity = (int) $item['quantity'];
                $unitPrice = (float) $item['unit_price'];
                $unitCost = $this->resolveHistoricalUnitCost($product, $pricingDate);

                $subtotal += $unitPrice * $quantity;
                $componentCostTotal += $unitCost * $quantity;
            }

            $laborCharge = $laborCharge ?? 0.0;
            $totalAmount = $subtotal + $laborCharge;

            $order = Order::create([
                'order_number' => $orderNumber,
                'customer_id' => $customerId,
                'employee_id' => auth()->user()?->employee?->id,
                'order_type' => 'custom_build',
                'channel' => $channel,
                'status' => 'confirmed',
                'subtotal' => $subtotal,
                'discount_amount' => 0,
                'tax_amount' => 0,
                'total_amount' => $totalAmount,
                'amount_paid' => 0,
            ]);

            $customBuild = CustomBuildOrder::create([
                'order_id' => $order->id,
                'build_template_id' => $buildTemplateId,
                'build_status' => 'draft',
                'assembly_notes' => $assemblyNotes,
                'labor_charge' => $laborCharge,
            ]);

            foreach ($items as $item) {
                /** @var Product $product */
                $product = Product::query()->findOrFail($item['product_id']);
                $quantity = (int) $item['quantity'];
                $unitCostAtBuild = $this->resolveHistoricalUnitCost($product, $pricingDate);

                CustomBuildOrderItem::create([
                    'custom_build_order_id' => $customBuild->id,
                    'component_type' => $item['component_type'],
                    'product_id' => $product->id,
                    'serial_id' => $item['serial_id'] ?? null,
                    'quantity' => $quantity,
                    'reserved_at' => now(),
                    'unit_cost_at_build' => $unitCostAtBuild,
                    'unit_price_at_build' => (float) $item['unit_price'],
                ]);

                $this->inventoryService->reserveInventory($product, $quantity);
            }

            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => null,
                'description' => "Custom Build - {$customBuild->buildTemplate->name}",
                'quantity' => 1,
                'unit_cost_at_sale' => $componentCostTotal,
                'unit_price_at_sale' => $totalAmount,
                'discount_amount' => 0,
                'tax_amount' => 0,
                'line_total' => $totalAmount,
            ]);

            return $customBuild->load([
                'items.product',
                'items.serial',
                'order',
                'buildTemplate',
            ]);
        });
    }

    /**
     * Complete custom build and consume inventory.
     */
    public function completeBuild(CustomBuildOrder $customBuild): void
    {
        if (! in_array($customBuild->build_status, ['draft', 'stock_check_pending', 'ready', 'assembling'], true)) {
            throw new \Exception('Build must be in draft, stock_check_pending, ready, or assembling status to complete.');
        }

        DB::transaction(function () use ($customBuild) {
            $customBuild->loadMissing('items.product', 'items.serial', 'order');

            foreach ($customBuild->items as $item) {
                $this->inventoryService->consumeInventory(
                    product: $item->product,
                    quantity: (int) $item->quantity,
                    movementType: 'build_consume',
                    referenceType: 'CustomBuildOrder',
                    referenceId: $customBuild->id,
                    serial: $item->serial,
                    notes: "Used in build {$customBuild->order->order_number}",
                    unitCost: (float) $item->unit_cost_at_build
                );

                $this->inventoryService->releaseReservedInventory($item->product, (int) $item->quantity);

                $item->update([
                    'consumed_at' => now(),
                ]);
            }

            $customBuild->update([
                'build_status' => 'completed',
                'completed_at' => now(),
            ]);

            $customBuild->order->update(['status' => 'confirmed']);
        });
    }

    /**
     * Cancel build and release reserved inventory.
     */
    public function cancelBuild(CustomBuildOrder $customBuild): void
    {
        if (in_array($customBuild->build_status, ['completed', 'delivered'], true)) {
            throw new \Exception('Cannot cancel a completed or delivered build.');
        }

        DB::transaction(function () use ($customBuild) {
            $customBuild->loadMissing('items.product', 'order');

            foreach ($customBuild->items as $item) {
                $this->inventoryService->releaseReservedInventory($item->product, (int) $item->quantity);
            }

            $customBuild->update(['build_status' => 'cancelled']);
            $customBuild->order->update(['status' => 'cancelled']);
        });
    }

    /**
     * Resolve historical unit cost using supplier price history effective at the build time.
     */
    private function resolveHistoricalUnitCost(Product $product, Carbon $pricingDate): float
    {
        $historicalPrice = ProductSupplierPrice::query()
            ->where('product_id', $product->id)
            ->where(function ($query) use ($pricingDate) {
                $query->whereNull('effective_from')
                    ->orWhere('effective_from', '<=', $pricingDate);
            })
            ->where(function ($query) use ($pricingDate) {
                $query->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', $pricingDate);
            })
            ->orderByDesc('is_primary')
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->first();

        if ($historicalPrice) {
            return (float) $historicalPrice->cost_price;
        }

        return 0.0;
    }
}