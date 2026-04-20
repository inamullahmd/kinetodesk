<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ReturnItem;
use App\Models\ReturnRequest;
use Illuminate\Support\Facades\DB;

class ReturnService
{
    private InventoryService $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    public function createReturn(
        int $orderId,
        string $reason,
        array $items
    ): ReturnRequest {
        return DB::transaction(function () use ($orderId, $reason, $items) {
            $order = Order::query()
                ->with('items.product', 'items.serial')
                ->findOrFail($orderId);

            if (empty($order->customer_id)) {
                throw new \Exception('Order has no customer.');
            }

            $this->validateReturnItems($order, $items);

            $returnNumber = 'RET-' . now()->format('Ymd') . '-' . str_pad((string) (ReturnRequest::count() + 1), 5, '0', STR_PAD_LEFT);

            $return = ReturnRequest::create([
                'return_number' => $returnNumber,
                'order_id' => $order->id,
                'customer_id' => $order->customer_id,
                'status' => 'initiated',
                'reason' => $reason,
                'return_date' => now(),
                'refund_amount' => $this->calculateRefundAmount($order, $items),
            ]);

            foreach ($items as $item) {
                /** @var OrderItem|null $orderItem */
                $orderItem = $order->items()
                    ->whereKey((int) $item['order_item_id'])
                    ->first();

                if (! $orderItem) {
                    throw new \Exception("Order item {$item['order_item_id']} does not belong to order {$order->order_number}.");
                }

                ReturnItem::create([
                    'return_id' => $return->id,
                    'order_item_id' => $orderItem->id,
                    'serial_id' => $item['serial_id'] ?? null,
                    'quantity' => (int) $item['quantity'],
                    'condition_status' => $item['condition_status'],
                    'resolution' => $item['resolution'],
                ]);
            }

            return $return->load([
                'order',
                'customer',
                'items.orderItem.product',
                'items.serial',
            ]);
        });
    }

    public function processReturn(ReturnRequest $return, string $newStatus): void
    {
        if (! in_array($newStatus, ['approved', 'rejected', 'refunded', 'exchanged'], true)) {
            throw new \Exception('Invalid return status.');
        }

        DB::transaction(function () use ($return, $newStatus) {
            $return->loadMissing('items.orderItem.product', 'items.orderItem.serial', 'items.serial', 'order');

            if ($newStatus === 'approved') {
                if ($return->status !== 'initiated') {
                    throw new \Exception('Only initiated returns can be approved.');
                }

                $return->update(['status' => 'approved']);
                return;
            }

            if ($newStatus === 'rejected') {
                if (! in_array($return->status, ['initiated', 'approved'], true)) {
                    throw new \Exception('Only initiated or approved returns can be rejected.');
                }

                $return->update(['status' => 'rejected']);
                return;
            }

            if ($newStatus === 'refunded') {
                if (! in_array($return->status, ['approved', 'received'], true)) {
                    throw new \Exception('Return must be approved or received before refunding.');
                }

                foreach ($return->items as $item) {
                    $this->processReturnedItem($return, $item);
                }

                $return->update([
                    'status' => 'refunded',
                    'refund_amount' => $this->calculateRefundAmountFromStoredItems($return),
                ]);

                return;
            }

            if ($newStatus === 'exchanged') {
                if (! in_array($return->status, ['approved', 'received'], true)) {
                    throw new \Exception('Return must be approved or received before exchanging.');
                }

                foreach ($return->items as $item) {
                    $this->processReturnedItem($return, $item);
                }

                $return->update(['status' => 'exchanged']);
            }
        });
    }

    private function validateReturnItems(Order $order, array $items): void
    {
        if (empty($items)) {
            throw new \Exception('At least one return item is required.');
        }

        $groupedRequestedQuantities = [];

        foreach ($items as $item) {
            $orderItemId = (int) $item['order_item_id'];
            $requestedQuantity = (int) $item['quantity'];

            /** @var OrderItem|null $orderItem */
            $orderItem = $order->items()
                ->whereKey($orderItemId)
                ->first();

            if (! $orderItem) {
                throw new \Exception("Order item {$orderItemId} does not belong to order {$order->order_number}.");
            }

            if ($requestedQuantity <= 0) {
                throw new \Exception("Return quantity for order item {$orderItemId} must be greater than zero.");
            }

            if (! $orderItem->product) {
                throw new \Exception("Order item {$orderItemId} has no product and cannot be returned through inventory.");
            }

            if ($orderItem->product->is_serialized) {
                if ($requestedQuantity !== 1) {
                    throw new \Exception("Serialized product {$orderItem->product->sku} must be returned with quantity 1.");
                }

                if (empty($item['serial_id'])) {
                    throw new \Exception("Serialized product {$orderItem->product->sku} requires a serial_id for return.");
                }

                $expectedSerialId = $orderItem->serial_id;
                $providedSerialId = (int) $item['serial_id'];

                if ($expectedSerialId && $expectedSerialId !== $providedSerialId) {
                    throw new \Exception("Return serial does not match the sold serial for order item {$orderItemId}.");
                }
            }

            $groupedRequestedQuantities[$orderItemId] = ($groupedRequestedQuantities[$orderItemId] ?? 0) + $requestedQuantity;
        }

        foreach ($groupedRequestedQuantities as $orderItemId => $requestedTotal) {
            /** @var OrderItem|null $orderItem */
            $orderItem = $order->items()
                ->whereKey($orderItemId)
                ->first();

            if (! $orderItem) {
                throw new \Exception("Order item {$orderItemId} does not belong to order {$order->order_number}.");
            }

            $alreadyReturnedQuantity = ReturnItem::query()
                ->where('order_item_id', $orderItemId)
                ->whereHas('return', function ($query) {
                    $query->whereIn('status', ['approved', 'received', 'refunded', 'exchanged', 'replaced']);
                })
                ->sum('quantity');

            $remainingReturnable = (int) $orderItem->quantity - (int) $alreadyReturnedQuantity;

            if ($requestedTotal > $remainingReturnable) {
                throw new \Exception(
                    "Order item {$orderItemId} only has {$remainingReturnable} returnable unit(s) remaining."
                );
            }
        }
    }

    private function processReturnedItem(ReturnRequest $return, ReturnItem $item): void
    {
        $orderItem = $item->orderItem;
        $product = $orderItem->product;
        $serial = $item->serial ?? $orderItem->serial;

        if (! $product) {
            throw new \Exception("Return item {$item->id} is linked to an order item with no product.");
        }

        $isResellable = in_array($item->condition_status, ['resellable', 'opened'], true);

        if ($isResellable) {
            $this->inventoryService->adjustInventory(
                product: $product,
                quantityDelta: (int) $item->quantity,
                reason: 'sale_return',
                notes: "Return {$return->return_number} restocked from Order #{$return->order?->order_number}",
                referenceId: $return->id,
                referenceType: 'ReturnRequest',
                serial: $serial,
                unitCost: (float) $orderItem->unit_cost_at_sale
            );

            if ($serial) {
                $serial->update(['status' => 'in_stock']);
            }

            return;
        }

        $this->inventoryService->recordInventoryMovement(
            product: $product,
            quantity: (int) $item->quantity,
            movementType: 'defective_mark',
            referenceType: 'ReturnRequest',
            referenceId: $return->id,
            serial: $serial,
            notes: "Return {$return->return_number} marked {$item->condition_status} from Order #{$return->order?->order_number}",
            unitCost: (float) $orderItem->unit_cost_at_sale
        );

        if ($serial) {
            $serial->update(['status' => 'defective']);
        }
    }

    private function calculateRefundAmount(Order $order, array $items): float
    {
        $refundAmount = 0.0;

        foreach ($items as $item) {
            /** @var OrderItem|null $orderItem */
            $orderItem = $order->items()
                ->whereKey((int) $item['order_item_id'])
                ->first();

            if (! $orderItem) {
                continue;
            }

            $refundAmount += ((float) $orderItem->unit_price_at_sale) * ((int) $item['quantity']);
        }

        return $refundAmount;
    }

    private function calculateRefundAmountFromStoredItems(ReturnRequest $return): float
    {
        $return->loadMissing('items.orderItem');

        $refundAmount = 0.0;

        foreach ($return->items as $item) {
            $refundAmount += ((float) $item->orderItem->unit_price_at_sale) * ((int) $item->quantity);
        }

        return $refundAmount;
    }
}