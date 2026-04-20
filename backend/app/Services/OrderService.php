<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductSupplierPrice;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class OrderService
{
    private InventoryService $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * Create an order with items and reserve inventory.
     */
    public function createOrder(
        int $customerId,
        string $orderType,
        string $channel,
        array $items,
        ?float $discountAmount = null,
        ?string $notes = null
    ): Order {
        return DB::transaction(function () use ($customerId, $orderType, $channel, $items, $discountAmount, $notes) {
            $pricingDate = now();

            foreach ($items as $item) {
                /** @var Product $product */
                $product = Product::query()->findOrFail($item['product_id']);
                $requestedQuantity = (int) $item['quantity'];
                $availableStock = $this->inventoryService->getAvailableStock($product);

                if ($availableStock < $requestedQuantity) {
                    throw new \Exception(
                        "Product {$product->sku} has insufficient stock. Available: {$availableStock}, Requested: {$requestedQuantity}"
                    );
                }

                if ($product->is_serialized) {
                    if ($requestedQuantity !== 1) {
                        throw new \Exception("Serialized product {$product->sku} must be ordered with quantity 1 per line item.");
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
            foreach ($items as $item) {
                $subtotal += ((float) $item['unit_price']) * ((int) $item['quantity']);
            }

            $discountAmount = $discountAmount ?? 0.0;
            $taxAmount = 0.0;
            $totalAmount = $subtotal - $discountAmount + $taxAmount;

            $order = Order::create([
                'order_number' => $orderNumber,
                'customer_id' => $customerId,
                'employee_id' => auth()->user()?->employee?->id,
                'order_type' => $orderType,
                'channel' => $channel,
                'status' => 'draft',
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'amount_paid' => 0,
                'notes' => $notes,
            ]);

            foreach ($items as $item) {
                /** @var Product $product */
                $product = Product::query()->findOrFail($item['product_id']);
                $quantity = (int) $item['quantity'];
                $unitPrice = (float) $item['unit_price'];
                $unitCostAtSale = $this->resolveHistoricalUnitCost($product, $pricingDate);

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'serial_id' => $item['serial_id'] ?? null,
                    'quantity' => $quantity,
                    'unit_cost_at_sale' => $unitCostAtSale,
                    'unit_price_at_sale' => $unitPrice,
                    'discount_amount' => 0,
                    'tax_amount' => 0,
                    'line_total' => $quantity * $unitPrice,
                ]);

                $this->inventoryService->reserveInventory($product, $quantity);
            }

            return $order->load([
                'items.product',
                'items.serial',
            ]);
        });
    }

    /**
     * Fulfill order through staged transitions.
     */
    public function fulfillOrder(Order $order, string $newStatus, ?string $trackingInfo = null): void
    {
        if ($newStatus === 'confirmed') {
            if ($order->status !== 'draft') {
                throw new \Exception('Order must be in draft status to confirm.');
            }

            $order->update(['status' => 'confirmed']);
            return;
        }

        if ($newStatus === 'paid') {
            if ($order->status !== 'confirmed') {
                throw new \Exception('Order must be confirmed before marking as paid.');
            }

            $order->update(['status' => 'paid']);
            return;
        }

        if ($newStatus === 'completed') {
            if ($order->status !== 'paid') {
                throw new \Exception('Order must be paid before completing.');
            }

            DB::transaction(function () use ($order) {
                $order->loadMissing('items.product', 'items.serial');

                foreach ($order->items as $item) {
                    $this->inventoryService->consumeInventory(
                        product: $item->product,
                        quantity: (int) $item->quantity,
                        movementType: 'sale',
                        referenceType: 'Order',
                        referenceId: $order->id,
                        serial: $item->serial,
                        notes: "Completed via Order #{$order->order_number}",
                        unitCost: (float) $item->unit_cost_at_sale
                    );

                    $this->inventoryService->releaseReservedInventory($item->product, (int) $item->quantity);
                }

                $order->update(['status' => 'completed']);
            });

            return;
        }

        throw new \Exception("Unsupported order status transition [{$newStatus}].");
    }

    /**
     * Cancel order and release reserved inventory.
     */
    public function cancelOrder(Order $order): void
    {
        if (! in_array($order->status, ['draft', 'confirmed'], true)) {
            throw new \Exception('Only draft or confirmed orders can be cancelled.');
        }

        DB::transaction(function () use ($order) {
            $order->loadMissing('items.product');

            foreach ($order->items as $item) {
                $this->inventoryService->releaseReservedInventory($item->product, (int) $item->quantity);
            }

            $order->update(['status' => 'cancelled']);
        });
    }

    /**
     * Resolve historical unit cost using supplier price history effective at sale time.
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