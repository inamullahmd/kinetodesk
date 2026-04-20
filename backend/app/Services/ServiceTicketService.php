<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductSupplierPrice;
use App\Models\ServiceTicket;
use App\Models\ServiceTicketPart;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ServiceTicketService
{
    private InventoryService $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    public function createTicket(array $data): ServiceTicket
    {
        return DB::transaction(function () use ($data) {
            $ticketNumber = 'TKT-' . now()->format('Ymd') . '-' . str_pad((string) (ServiceTicket::count() + 1), 5, '0', STR_PAD_LEFT);

            $ticket = ServiceTicket::create([
                'ticket_number' => $ticketNumber,
                'customer_id' => $data['customer_id'],
                'assigned_employee_id' => $data['assigned_employee_id'] ?? null,
                'related_order_id' => $data['related_order_id'] ?? null,
                'related_serial_id' => $data['related_serial_id'] ?? null,
                'device_brand' => $data['device_brand'],
                'device_model' => $data['device_model'],
                'device_serial_number' => $data['device_serial_number'] ?? null,
                'issue_description' => $data['issue_description'],
                'diagnosis_notes' => $data['diagnosis_notes'] ?? null,
                'status' => 'received',
                'labor_cost' => 0,
                'parts_cost' => 0,
                'total_cost' => 0,
                'received_at' => now(),
            ]);

            return $ticket->load([
                'customer:id,business_name,first_name,last_name',
                'assignedEmployee:id,first_name,last_name',
                'relatedOrder:id,order_number',
                'relatedSerial:id,serial_number,product_id',
            ]);
        });
    }

    public function updateTicket(ServiceTicket $ticket, array $data): ServiceTicket
    {
        return DB::transaction(function () use ($ticket, $data) {
            $ticket->loadMissing('parts.product', 'parts.serial');

            $updates = [];

            if (array_key_exists('status', $data)) {
                $this->validateStatusTransition($ticket, (string) $data['status']);
                $updates['status'] = $data['status'];

                if ($data['status'] === 'completed' && ! $ticket->completed_at) {
                    $updates['completed_at'] = now();
                }

                if ($data['status'] === 'delivered' && ! $ticket->delivered_at) {
                    $updates['delivered_at'] = now();
                }
            }

            if (array_key_exists('assigned_employee_id', $data)) {
                $updates['assigned_employee_id'] = $data['assigned_employee_id'];
            }

            if (array_key_exists('diagnosis_notes', $data)) {
                $updates['diagnosis_notes'] = $data['diagnosis_notes'];
            }

            if (array_key_exists('labor_cost', $data)) {
                $updates['labor_cost'] = (float) $data['labor_cost'];
            }

            if (! empty($data['parts']) && is_array($data['parts'])) {
                $this->addPartsToTicket($ticket, $data['parts']);
            }

            if (! empty($updates)) {
                $ticket->update($updates);
            }

            $this->recalculateTotals($ticket);

            return $ticket->fresh()->load([
                'customer:id,business_name,first_name,last_name,phone,email',
                'assignedEmployee:id,first_name,last_name,email,phone',
                'relatedOrder:id,order_number',
                'relatedSerial:id,serial_number,product_id',
                'parts.product:id,sku,name',
                'parts.serial:id,serial_number',
            ]);
        });
    }

    private function addPartsToTicket(ServiceTicket $ticket, array $parts): void
    {
        $pricingDate = now();

        foreach ($parts as $part) {
            /** @var Product $product */
            $product = Product::query()->findOrFail((int) $part['product_id']);
            $quantity = (int) $part['quantity'];
            $unitPrice = (float) $part['unit_price'];

            if ($quantity <= 0) {
                throw new \InvalidArgumentException("Part quantity must be greater than zero for product {$product->sku}.");
            }

            if ((int) $product->current_stock < $quantity) {
                throw new \Exception(
                    "Insufficient stock for product {$product->sku}. Available: {$product->current_stock}, Requested: {$quantity}"
                );
            }

            $serial = null;

            if ($product->is_serialized) {
                if ($quantity !== 1) {
                    throw new \Exception("Serialized product {$product->sku} must be added with quantity 1.");
                }

                if (empty($part['serial_id'])) {
                    throw new \Exception("Serialized product {$product->sku} requires a serial_id.");
                }

                $serial = $product->serials()
                    ->whereKey((int) $part['serial_id'])
                    ->where('status', 'in_stock')
                    ->first();

                if (! $serial) {
                    throw new \Exception("Serial for product {$product->sku} is invalid or not available.");
                }
            }

            $unitCost = $this->resolveHistoricalUnitCost($product, $pricingDate);

            ServiceTicketPart::create([
                'service_ticket_id' => $ticket->id,
                'product_id' => $product->id,
                'serial_id' => $serial?->id,
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'unit_price' => $unitPrice,
                'line_total' => $unitPrice * $quantity,
            ]);

            $this->inventoryService->consumeInventory(
                product: $product,
                quantity: $quantity,
                movementType: 'service_use',
                referenceType: 'ServiceTicket',
                referenceId: $ticket->id,
                serial: $serial,
                notes: "Used in service ticket {$ticket->ticket_number}",
                unitCost: $unitCost
            );
        }
    }

    private function recalculateTotals(ServiceTicket $ticket): void
    {
        $ticket->loadMissing('parts');

        $partsCost = (float) $ticket->parts()
            ->selectRaw('COALESCE(SUM(unit_cost * quantity), 0) as total_cost')
            ->value('total_cost');

        $laborCost = (float) $ticket->labor_cost;

        $ticket->update([
            'parts_cost' => $partsCost,
            'total_cost' => $laborCost + $partsCost,
        ]);
    }

    private function validateStatusTransition(ServiceTicket $ticket, string $newStatus): void
    {
        $allowedStatuses = ['received', 'diagnosing', 'waiting_approval', 'in_progress', 'completed', 'delivered', 'cancelled'];

        if (! in_array($newStatus, $allowedStatuses, true)) {
            throw new \Exception("Invalid service ticket status [{$newStatus}].");
        }

        $current = $ticket->status;

        $allowedTransitions = [
            'received' => ['diagnosing', 'cancelled'],
            'diagnosing' => ['waiting_approval', 'in_progress', 'cancelled'],
            'waiting_approval' => ['in_progress', 'cancelled'],
            'in_progress' => ['completed', 'cancelled'],
            'completed' => ['delivered'],
            'delivered' => [],
            'cancelled' => [],
        ];

        if (! isset($allowedTransitions[$current])) {
            throw new \Exception("Unknown current ticket status [{$current}].");
        }

        if ($newStatus === $current) {
            return;
        }

        if (! in_array($newStatus, $allowedTransitions[$current], true)) {
            throw new \Exception("Cannot move service ticket from {$current} to {$newStatus}.");
        }
    }

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