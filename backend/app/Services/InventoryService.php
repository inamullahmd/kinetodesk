<?php

namespace App\Services;

use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductSerial;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * Valid inventory movement types defined by the schema.
     */
    private const MOVEMENT_TYPES = [
        'purchase_receive',
        'sale',
        'sale_return',
        'service_use',
        'service_return',
        'build_reserve',
        'build_consume',
        'build_release',
        'manual_adjustment',
        'transfer_in',
        'transfer_out',
        'defective_mark',
    ];

    public function receivePurchaseOrder(
        PurchaseOrder $purchaseOrder,
        array $receivedQuantities,
        ?array $serials = null
    ): void {
        DB::transaction(function () use ($purchaseOrder, $receivedQuantities, $serials) {
            $purchaseOrder->items()->each(function (PurchaseOrderItem $item) use ($purchaseOrder, $receivedQuantities, $serials) {
                $itemId = $item->id;
                $receivedQty = (int) ($receivedQuantities[$itemId] ?? 0);

                if ($receivedQty <= 0) {
                    return;
                }

                $remainingReceivable = (int) $item->quantity_ordered - (int) $item->quantity_received;

                if ($receivedQty > $remainingReceivable) {
                    throw new \Exception(
                        "Cannot receive {$receivedQty} units for PO item {$item->id}; only {$remainingReceivable} remaining."
                    );
                }

                $product = $item->product;

                if ($product->is_serialized) {
                    $providedSerials = $serials[$itemId] ?? [];

                    if (count($providedSerials) !== $receivedQty) {
                        throw new \Exception(
                            "Serialized product {$product->sku} requires exactly {$receivedQty} serial record(s) for receipt."
                        );
                    }
                }

                $product->increment('current_stock', $receivedQty);

                InventoryMovement::create([
                    'product_id' => $product->id,
                    'serial_id' => null,
                    'movement_type' => 'purchase_receive',
                    'quantity' => $receivedQty,
                    'unit_cost' => $item->unit_cost,
                    'reference_type' => 'PurchaseOrder',
                    'reference_id' => $purchaseOrder->id,
                    'notes' => "Received from PO #{$purchaseOrder->po_number}",
                    'moved_at' => now(),
                ]);

                $item->increment('quantity_received', $receivedQty);

                if ($product->is_serialized) {
                    foreach (($serials[$itemId] ?? []) as $serialData) {
                        ProductSerial::create([
                            'product_id' => $product->id,
                            'purchase_order_item_id' => $item->id,
                            'serial_number' => $serialData['serial_number'],
                            'status' => 'in_stock',
                            'warranty_expiry_date' => $serialData['warranty_expiry_date'] ?? null,
                        ]);
                    }
                }
            });

            $purchaseOrder->refresh();

            $allFullyReceived = $purchaseOrder->items()
                ->get()
                ->every(fn (PurchaseOrderItem $item) => (int) $item->quantity_received >= (int) $item->quantity_ordered);

            if ($allFullyReceived) {
                $purchaseOrder->update([
                    'status' => 'received',
                    'received_at' => now(),
                ]);
            } else {
                $purchaseOrder->update(['status' => 'partially_received']);
            }
        });
    }

    public function consumeInventory(
        Product $product,
        int $quantity,
        string $movementType,
        string $referenceType,
        int $referenceId,
        ?ProductSerial $serial = null,
        ?string $notes = null,
        ?float $unitCost = null
    ): void {
        if (! in_array($movementType, self::MOVEMENT_TYPES, true)) {
            throw new \InvalidArgumentException("Invalid inventory movement type [{$movementType}].");
        }

        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Inventory consumption quantity must be greater than zero.');
        }

        DB::transaction(function () use ($product, $quantity, $movementType, $referenceType, $referenceId, $serial, $notes, $unitCost) {
            $product->refresh();

            if ((int) $product->current_stock < $quantity) {
                throw new \Exception(
                    "Insufficient current stock for product {$product->sku}. Available: {$product->current_stock}, Requested: {$quantity}"
                );
            }

            if ($product->is_serialized) {
                if (! $serial) {
                    throw new \Exception("Serialized product {$product->sku} requires a serial to be consumed.");
                }

                if ((int) $serial->product_id !== (int) $product->id) {
                    throw new \Exception("Serial {$serial->id} does not belong to product {$product->sku}.");
                }

                if ($serial->status !== 'in_stock') {
                    throw new \Exception("Serial {$serial->serial_number} is not available for consumption.");
                }

                if ($quantity !== 1) {
                    throw new \Exception("Serialized product {$product->sku} must be consumed one unit at a time.");
                }
            }

            $product->decrement('current_stock', $quantity);

            InventoryMovement::create([
                'product_id' => $product->id,
                'serial_id' => $serial?->id,
                'movement_type' => $movementType,
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'notes' => $notes ?? "Consumed from {$referenceType}",
                'moved_at' => now(),
            ]);

            if ($serial) {
                $newSerialStatus = match ($movementType) {
                    'sale', 'service_use', 'build_consume' => 'sold',
                    default => $serial->status,
                };

                $serial->update(['status' => $newSerialStatus]);
            }
        });
    }

    public function adjustInventory(
        Product $product,
        int $quantityDelta,
        string $reason,
        ?string $notes = null,
        ?int $referenceId = null,
        ?string $referenceType = null,
        ?ProductSerial $serial = null,
        ?float $unitCost = null
    ): void {
        DB::transaction(function () use ($product, $quantityDelta, $reason, $notes, $referenceId, $referenceType, $serial, $unitCost) {
            if ($quantityDelta === 0) {
                throw new \InvalidArgumentException('Inventory adjustment cannot be zero.');
            }

            if (! in_array($reason, self::MOVEMENT_TYPES, true)) {
                throw new \InvalidArgumentException("Invalid inventory movement type [{$reason}].");
            }

            $product->refresh();

            if ($quantityDelta > 0) {
                $product->increment('current_stock', $quantityDelta);
            } else {
                $decreaseBy = abs($quantityDelta);

                if ((int) $product->current_stock < $decreaseBy) {
                    throw new \Exception(
                        "Cannot decrease stock for product {$product->sku} by {$decreaseBy}; only {$product->current_stock} available."
                    );
                }

                $product->decrement('current_stock', $decreaseBy);
            }

            InventoryMovement::create([
                'product_id' => $product->id,
                'serial_id' => $serial?->id,
                'movement_type' => $reason,
                'quantity' => abs($quantityDelta),
                'unit_cost' => $unitCost,
                'reference_type' => $referenceType ?? 'Adjustment',
                'reference_id' => $referenceId ?? 0,
                'notes' => $notes,
                'moved_at' => now(),
            ]);
        });
    }

    /**
     * Record an inventory event without changing sellable stock.
     * Useful for defective returns when you want the audit trail
     * but do not want the item counted as available inventory.
     */
    public function recordInventoryMovement(
        Product $product,
        int $quantity,
        string $movementType,
        string $referenceType,
        int $referenceId,
        ?ProductSerial $serial = null,
        ?string $notes = null,
        ?float $unitCost = null
    ): void {
        if (! in_array($movementType, self::MOVEMENT_TYPES, true)) {
            throw new \InvalidArgumentException("Invalid inventory movement type [{$movementType}].");
        }

        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Inventory movement quantity must be greater than zero.');
        }

        InventoryMovement::create([
            'product_id' => $product->id,
            'serial_id' => $serial?->id,
            'movement_type' => $movementType,
            'quantity' => $quantity,
            'unit_cost' => $unitCost,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'notes' => $notes,
            'moved_at' => now(),
        ]);
    }

    public function reserveInventory(Product $product, int $quantity): void
    {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Reserved quantity must be greater than zero.');
        }

        $product->refresh();

        if ($this->getAvailableStock($product) < $quantity) {
            throw new \Exception(
                "Insufficient available stock for product {$product->sku}. Available: {$this->getAvailableStock($product)}, Requested: {$quantity}"
            );
        }

        $product->increment('reserved_stock', $quantity);
    }

    public function releaseReservedInventory(Product $product, int $quantity): void
    {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Released quantity must be greater than zero.');
        }

        $product->refresh();

        $newReservedStock = max(0, (int) $product->reserved_stock - $quantity);
        $product->update(['reserved_stock' => $newReservedStock]);
    }

    public function getAvailableStock(Product $product): int
    {
        return max(0, (int) $product->current_stock - (int) $product->reserved_stock);
    }
}