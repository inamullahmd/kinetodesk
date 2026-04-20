<?php

namespace Database\Seeders;

use App\Models\BuildTemplate;
use App\Models\BuildTemplateItem;
use App\Models\Customer;
use App\Models\CustomerCreditTransaction;
use App\Models\Employee;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductSerial;
use App\Models\ProductSupplierPrice;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\ReorderAlert;
use App\Models\ReturnItem;
use App\Models\ReturnRequest;
use App\Models\ServiceTicket;
use App\Models\ServiceTicketPart;
use App\Models\Supplier;
use App\Models\SupplierDefectClaim;
use App\Models\CustomBuildOrder;
use App\Models\CustomBuildOrderItem;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class HistoricalStoreSeeder extends Seeder
{
    private Carbon $startDate;
    private Carbon $endDate;

    private array $stock = [];
    private array $serialPool = [];
    private array $customerBalances = [];
    private array $supplierLeadTimes = [];

    public function run(): void
    {
        $this->startDate = now()->subYears(15)->startOfYear();
        $this->endDate = now()->endOfMonth();

        $this->command?->info('Generating 15 years of store history...');

        DB::disableQueryLog();

        $products = Product::with('category')->get();
        $suppliers = Supplier::all();
        $employees = Employee::with('role')->get();
        $customers = Customer::all();
        $buildTemplates = BuildTemplate::with('items')->get();

        foreach ($products as $product) {
            $this->stock[$product->id] = 0;
            $this->serialPool[$product->id] = [];
        }

        foreach ($suppliers as $supplier) {
            $this->supplierLeadTimes[$supplier->id] = max(2, (int)$supplier->default_lead_time_days);
        }

        foreach ($customers as $customer) {
            $this->customerBalances[$customer->id] = 0.00;
        }

        $this->seedSupplierPriceHistory($products, $suppliers);
        $this->seedPurchaseHistory($products, $suppliers, $employees);
        $this->seedSalesAndOperations($products, $employees, $customers, $buildTemplates);
        $this->finalizeInventoryAndAlerts($products);

        $this->command?->info('Historical seeding complete.');
    }

    private function seedSupplierPriceHistory($products, $suppliers): void
    {
        foreach ($products as $product) {
            if ($product->product_type === 'service') {
                continue;
            }

            $supplierIds = $suppliers->pluck('id')->shuffle()->take(rand(2, min(4, $suppliers->count())))->values();

            foreach ($supplierIds as $index => $supplierId) {
                $effectiveFrom = $this->startDate->copy()->addMonths(rand(0, 6));
                $baseCost = $this->estimateBaseCost($product);

                for ($i = 0; $i < 10; $i++) {
                    $nextFrom = $effectiveFrom->copy()->addMonths(rand(10, 20));
                    $cost = max(5, $baseCost * (1 + ($i * 0.03)) * (rand(92, 112) / 100));

                    ProductSupplierPrice::create([
                        'product_id' => $product->id,
                        'supplier_id' => $supplierId,
                        'supplier_sku' => 'SUP-' . strtoupper(substr(md5($product->sku . $supplierId . $i), 0, 10)),
                        'cost_price' => round($cost, 2),
                        'currency_code' => 'USD',
                        'minimum_order_qty' => in_array($product->category->name, ['CPUs', 'RAM', 'Storage', 'Accessories'], true) ? rand(2, 10) : 1,
                        'effective_from' => $effectiveFrom,
                        'effective_to' => $nextFrom->copy()->subDay(),
                        'is_primary' => $index === 0,
                    ]);

                    $effectiveFrom = $nextFrom;
                    if ($effectiveFrom->greaterThan($this->endDate)) {
                        break;
                    }
                }
            }
        }
    }

    private function seedPurchaseHistory($products, $suppliers, $employees): void
    {
        $buyers = $employees->filter(fn($e) => in_array($e->role->code, ['admin', 'manager'], true))->values();

        $monthCursor = $this->startDate->copy()->startOfMonth();
        while ($monthCursor->lte($this->endDate)) {
            $monthFactor = $this->storeGrowthFactor($monthCursor);
            $poCount = rand((int)(3 * $monthFactor), (int)(8 * $monthFactor));

            for ($p = 0; $p < max(1, $poCount); $p++) {
                $supplier = $suppliers->random();
                $buyer = $buyers->random();
                $orderDate = $monthCursor->copy()->addDays(rand(0, min(25, $monthCursor->daysInMonth - 1)));
                $leadDays = max(2, $this->supplierLeadTimes[$supplier->id] + rand(-1, 3));
                $receivedAt = $orderDate->copy()->addDays($leadDays);

                $po = PurchaseOrder::create([
                    'po_number' => 'PO-' . $orderDate->format('Ymd') . '-' . strtoupper(Str::random(5)),
                    'supplier_id' => $supplier->id,
                    'status' => $receivedAt->lte($this->endDate) ? 'received' : 'sent',
                    'ordered_by_employee_id' => $buyer->id,
                    'order_date' => $orderDate,
                    'expected_date' => $orderDate->copy()->addDays($leadDays)->toDateString(),
                    'received_at' => $receivedAt->lte($this->endDate) ? $receivedAt : null,
                    'subtotal' => 0,
                    'tax_amount' => 0,
                    'shipping_amount' => 0,
                    'total_amount' => 0,
                    'notes' => 'System-generated historical purchase order.',
                ]);

                $eligibleProducts = $products
                    ->filter(fn($prod) => $prod->product_type !== 'service')
                    ->shuffle()
                    ->take(rand(4, 12));

                $subtotal = 0;
                foreach ($eligibleProducts as $product) {
                    $cost = $this->currentHistoricalCost($product->id, $supplier->id, $orderDate) ?? $this->estimateBaseCost($product);
                    $qty = $this->recommendedPurchaseQty($product->category->name, $monthFactor);
                    $lineTotal = round($qty * $cost, 2);

                    $poi = PurchaseOrderItem::create([
                        'purchase_order_id' => $po->id,
                        'product_id' => $product->id,
                        'quantity_ordered' => $qty,
                        'quantity_received' => $receivedAt->lte($this->endDate) ? $qty : 0,
                        'unit_cost' => round($cost, 2),
                        'line_total' => $lineTotal,
                    ]);

                    $subtotal += $lineTotal;

                    if ($receivedAt->lte($this->endDate)) {
                        $this->stock[$product->id] += $qty;

                        InventoryMovement::create([
                            'product_id' => $product->id,
                            'serial_id' => null,
                            'movement_type' => 'purchase_receive',
                            'quantity' => $qty,
                            'unit_cost' => round($cost, 2),
                            'reference_type' => 'purchase_order_item',
                            'reference_id' => $poi->id,
                            'notes' => 'Historical received inventory from supplier.',
                            'moved_at' => $receivedAt,
                        ]);

                        if ($product->is_serialized) {
                            for ($i = 0; $i < $qty; $i++) {
                                $serial = ProductSerial::create([
                                    'product_id' => $product->id,
                                    'purchase_order_item_id' => $poi->id,
                                    'serial_number' => strtoupper($product->sku) . '-' . $receivedAt->format('ymd') . '-' . strtoupper(Str::random(8)),
                                    'status' => 'in_stock',
                                    'warranty_expiry_date' => $receivedAt->copy()->addYears(rand(1, 3))->toDateString(),
                                ]);

                                $this->serialPool[$product->id][] = $serial->id;
                            }
                        }
                    }
                }

                $shipping = round($subtotal * 0.03, 2);
                $tax = round($subtotal * 0.015, 2);

                $po->update([
                    'subtotal' => round($subtotal, 2),
                    'shipping_amount' => $shipping,
                    'tax_amount' => $tax,
                    'total_amount' => round($subtotal + $shipping + $tax, 2),
                ]);
            }

            $monthCursor->addMonth();
        }
    }

    private function seedSalesAndOperations($products, $employees, $customers, $buildTemplates): void
    {
        $salesEmployees = $employees->filter(fn($e) => $e->role->code === 'sales')->values();
        $techEmployees = $employees->filter(fn($e) => $e->role->code === 'tech')->values();

        $inventoryProducts = $products->filter(fn($p) => $p->product_type === 'inventory')->values();
        $serviceProducts = $products->filter(fn($p) => $p->product_type === 'service')->values();

        $dateCursor = $this->startDate->copy();
        while ($dateCursor->lte($this->endDate)) {
            $dailyOrders = $this->ordersPerDay($dateCursor);

            for ($i = 0; $i < $dailyOrders; $i++) {
                $customer = $customers->random();
                $isB2B = $customer->customer_type === 'b2b';
                $isCustomBuild = !$isB2B && rand(1, 100) <= 7;
                $isUnattended = rand(1, 100) <= 18;

                $employee = $isUnattended ? null : $salesEmployees->random();
                $orderType = $isCustomBuild ? 'custom_build' : ($isB2B ? 'b2b' : 'retail');
                $channel = $isUnattended ? ['online', 'walk_in'][array_rand(['online', 'walk_in'])] : ['in_store', 'phone'][array_rand(['in_store', 'phone'])];
                $createdAt = $dateCursor->copy()->setTime(rand(9, 18), rand(0, 59), rand(0, 59));

                $order = Order::create([
                    'order_number' => 'ORD-' . $createdAt->format('Ymd') . '-' . strtoupper(Str::random(6)),
                    'customer_id' => $customer->id,
                    'employee_id' => $employee?->id,
                    'order_type' => $orderType,
                    'channel' => $channel,
                    'status' => 'completed',
                    'subtotal' => 0,
                    'discount_amount' => 0,
                    'tax_amount' => 0,
                    'total_amount' => 0,
                    'amount_paid' => 0,
                    'notes' => 'Historical seeded order.',
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);

                $subtotal = 0;
                $discount = 0;

                if ($isCustomBuild && $buildTemplates->isNotEmpty()) {
                    $template = $buildTemplates->random();

                    $customBuild = CustomBuildOrder::create([
                        'order_id' => $order->id,
                        'build_template_id' => $template->id,
                        'build_status' => 'completed',
                        'assembly_notes' => 'Historical custom build completed.',
                        'labor_charge' => rand(90, 220),
                        'completed_at' => $createdAt->copy()->addDays(rand(1, 5)),
                    ]);

                    foreach ($template->items as $templateItem) {
                        $product = $products->firstWhere('id', $templateItem->product_id);
                        if (!$product) {
                            continue;
                        }

                        if ($this->stock[$product->id] <= 0) {
                            continue;
                        }

                        $qty = 1;
                        $unitCost = $this->latestProductCost($product, $createdAt);
                        $sellPrice = round($unitCost * (1.18 + rand(0, 12) / 100), 2);
                        $serialId = null;

                        if ($product->is_serialized && !empty($this->serialPool[$product->id])) {
                            $serialId = array_shift($this->serialPool[$product->id]);
                            ProductSerial::whereKey($serialId)->update(['status' => 'sold']);
                        }

                        CustomBuildOrderItem::create([
                            'custom_build_order_id' => $customBuild->id,
                            'component_type' => $templateItem->component_type,
                            'product_id' => $product->id,
                            'serial_id' => $serialId,
                            'quantity' => $qty,
                            'reserved_at' => $createdAt,
                            'consumed_at' => $createdAt->copy()->addDays(rand(1, 3)),
                            'unit_cost_at_build' => $unitCost,
                            'unit_price_at_build' => $sellPrice,
                        ]);

                        OrderItem::create([
                            'order_id' => $order->id,
                            'product_id' => $product->id,
                            'serial_id' => $serialId,
                            'description' => $product->name . ' (' . $templateItem->component_type . ')',
                            'quantity' => $qty,
                            'unit_cost_at_sale' => $unitCost,
                            'unit_price_at_sale' => $sellPrice,
                            'discount_amount' => 0,
                            'tax_amount' => 0,
                            'line_total' => $sellPrice,
                        ]);

                        InventoryMovement::create([
                            'product_id' => $product->id,
                            'serial_id' => $serialId,
                            'movement_type' => 'build_consume',
                            'quantity' => -1,
                            'unit_cost' => $unitCost,
                            'reference_type' => 'custom_build_order_item',
                            'reference_id' => $customBuild->id,
                            'notes' => 'Historical custom build component consumption.',
                            'moved_at' => $createdAt,
                        ]);

                        $this->stock[$product->id] -= 1;
                        $subtotal += $sellPrice;
                    }

                    $subtotal += $customBuild->labor_charge;
                } else {
                    $lineCount = $isB2B ? rand(3, 8) : rand(1, 4);
                    $chosenProducts = $inventoryProducts->shuffle()->take($lineCount);

                    foreach ($chosenProducts as $product) {
                        if ($this->stock[$product->id] <= 0) {
                            continue;
                        }

                        $maxQty = min($this->stock[$product->id], $isB2B ? rand(2, 8) : rand(1, 2));
                        if ($maxQty <= 0) {
                            continue;
                        }

                        $qty = $maxQty;
                        $unitCost = $this->latestProductCost($product, $createdAt);
                        $markup = $isB2B ? rand(15, 28) : rand(18, 38);
                        $unitPrice = round($unitCost * (1 + $markup / 100), 2);
                        $lineDiscount = $isB2B ? round($unitPrice * $qty * (rand(0, 5) / 100), 2) : 0;

                        $serialId = null;
                        if ($product->is_serialized && !empty($this->serialPool[$product->id])) {
                            $qty = 1;
                            $serialId = array_shift($this->serialPool[$product->id]);
                            ProductSerial::whereKey($serialId)->update(['status' => 'sold']);
                        }

                        $lineTotal = round(($unitPrice * $qty) - $lineDiscount, 2);

                        OrderItem::create([
                            'order_id' => $order->id,
                            'product_id' => $product->id,
                            'serial_id' => $serialId,
                            'description' => $product->name,
                            'quantity' => $qty,
                            'unit_cost_at_sale' => $unitCost,
                            'unit_price_at_sale' => $unitPrice,
                            'discount_amount' => $lineDiscount,
                            'tax_amount' => 0,
                            'line_total' => $lineTotal,
                        ]);

                        InventoryMovement::create([
                            'product_id' => $product->id,
                            'serial_id' => $serialId,
                            'movement_type' => 'sale',
                            'quantity' => -$qty,
                            'unit_cost' => $unitCost,
                            'reference_type' => 'order',
                            'reference_id' => $order->id,
                            'notes' => 'Historical sale.',
                            'moved_at' => $createdAt,
                        ]);

                        $this->stock[$product->id] -= $qty;
                        $subtotal += $lineTotal;
                        $discount += $lineDiscount;
                    }
                }

                if ($subtotal <= 0) {
                    $order->delete();
                    continue;
                }

                $tax = round($subtotal * 0.085, 2);
                $total = round($subtotal + $tax, 2);

                $paymentMethod = $isB2B ? 'credit_account' : ['cash', 'card', 'bank_transfer', 'online_gateway'][array_rand(['cash', 'card', 'bank_transfer', 'online_gateway'])];

                $amountPaid = $isB2B
                    ? round($total * ([0, 0.25, 0.5, 1][array_rand([0, 0.25, 0.5, 1])]), 2)
                    : $total;

                $status = $amountPaid >= $total ? 'completed' : ($amountPaid > 0 ? 'partially_paid' : 'confirmed');

                $order->update([
                    'subtotal' => round($subtotal, 2),
                    'discount_amount' => round($discount, 2),
                    'tax_amount' => $tax,
                    'total_amount' => $total,
                    'amount_paid' => $amountPaid,
                    'status' => $status,
                ]);

                if ($amountPaid > 0) {
                    Payment::create([
                        'order_id' => $order->id,
                        'customer_id' => $customer->id,
                        'payment_method' => $paymentMethod,
                        'amount' => $amountPaid,
                        'payment_date' => $createdAt->copy()->addHours(rand(0, 48)),
                        'reference_number' => strtoupper(Str::random(12)),
                        'notes' => 'Historical payment.',
                    ]);
                }

                if ($isB2B) {
                    $newBalance = round(($this->customerBalances[$customer->id] ?? 0) + $total - $amountPaid, 2);

                    CustomerCreditTransaction::create([
                        'customer_id' => $customer->id,
                        'order_id' => $order->id,
                        'transaction_type' => 'charge',
                        'amount' => $total,
                        'balance_after' => round(($this->customerBalances[$customer->id] ?? 0) + $total, 2),
                        'transaction_date' => $createdAt,
                        'notes' => 'Historical B2B credit charge.',
                    ]);

                    if ($amountPaid > 0) {
                        CustomerCreditTransaction::create([
                            'customer_id' => $customer->id,
                            'order_id' => $order->id,
                            'transaction_type' => 'payment',
                            'amount' => $amountPaid,
                            'balance_after' => $newBalance,
                            'transaction_date' => $createdAt->copy()->addDays(rand(0, 30)),
                            'notes' => 'Historical B2B credit payment.',
                        ]);
                    }

                    $this->customerBalances[$customer->id] = $newBalance;
                }

                if (rand(1, 100) <= 12) {
                    $this->createServiceTicket($customer, $techEmployees->random(), $order, $serviceProducts, $createdAt, $products);
                }

                if (rand(1, 100) <= 5) {
                    $this->createReturnAndClaimIfNeeded($order, $customer, $createdAt);
                }
            }

            $dateCursor->addDay();
        }

        foreach ($this->customerBalances as $customerId => $balance) {
            Customer::whereKey($customerId)->update([
                'current_balance' => max(0, round($balance, 2)),
            ]);
        }
    }

    private function createServiceTicket($customer, $tech, $order, $serviceProducts, Carbon $createdAt, $products): void
    {
        $relatedItem = $order->items()->inRandomOrder()->first();
        if (!$relatedItem) {
            return;
        }

        $labor = rand(45, 220);
        $partsCost = 0;
        $completedAt = $createdAt->copy()->addDays(rand(1, 10));

        $ticket = ServiceTicket::create([
            'ticket_number' => 'TKT-' . $createdAt->format('Ymd') . '-' . strtoupper(Str::random(6)),
            'customer_id' => $customer->id,
            'assigned_employee_id' => $tech->id,
            'related_order_id' => $order->id,
            'related_serial_id' => $relatedItem->serial_id,
            'device_brand' => fake()->randomElement(['Dell', 'HP', 'Lenovo', 'ASUS', 'Acer', 'Custom']),
            'device_model' => 'Model-' . strtoupper(Str::random(5)),
            'device_serial_number' => strtoupper(Str::random(12)),
            'issue_description' => fake()->randomElement([
                'Slow boot and intermittent freezing',
                'No power on after shutdown',
                'Broken display hinge and cracked bezel',
                'Fan noise and overheating under load',
                'OS corruption and failed updates',
                'Network card disconnecting intermittently',
            ]),
            'diagnosis_notes' => 'Historical service diagnosis entered by technician.',
            'status' => 'delivered',
            'labor_cost' => $labor,
            'parts_cost' => 0,
            'total_cost' => 0,
            'received_at' => $createdAt->copy()->addDays(rand(3, 40)),
            'completed_at' => $completedAt,
            'delivered_at' => $completedAt->copy()->addDay(),
        ]);

        if (rand(1, 100) <= 55) {
            $parts = $products
                ->filter(fn($p) => $p->product_type === 'inventory' && $this->stock[$p->id] > 0)
                ->shuffle()
                ->take(rand(1, 2));

            foreach ($parts as $part) {
                $qty = 1;
                $unitCost = $this->latestProductCost($part, $ticket->received_at);
                $unitPrice = round($unitCost * 1.35, 2);

                $serialId = null;
                if ($part->is_serialized && !empty($this->serialPool[$part->id])) {
                    $serialId = array_shift($this->serialPool[$part->id]);
                    ProductSerial::whereKey($serialId)->update(['status' => 'service_in']);
                }

                ServiceTicketPart::create([
                    'service_ticket_id' => $ticket->id,
                    'product_id' => $part->id,
                    'serial_id' => $serialId,
                    'quantity' => $qty,
                    'unit_cost' => $unitCost,
                    'unit_price' => $unitPrice,
                    'line_total' => $unitPrice,
                ]);

                InventoryMovement::create([
                    'product_id' => $part->id,
                    'serial_id' => $serialId,
                    'movement_type' => 'service_use',
                    'quantity' => -1,
                    'unit_cost' => $unitCost,
                    'reference_type' => 'service_ticket',
                    'reference_id' => $ticket->id,
                    'notes' => 'Historical service part usage.',
                    'moved_at' => $ticket->completed_at,
                ]);

                $this->stock[$part->id] -= 1;
                $partsCost += $unitCost;
            }
        }

        $ticket->update([
            'parts_cost' => round($partsCost, 2),
            'total_cost' => round($labor + ($partsCost * 1.35), 2),
        ]);
    }

    private function createReturnAndClaimIfNeeded(Order $order, $customer, Carbon $createdAt): void
    {
        $item = $order->items()->inRandomOrder()->first();
        if (!$item) {
            return;
        }

        $return = ReturnRequest::create([
            'return_number' => 'RET-' . $createdAt->format('Ymd') . '-' . strtoupper(Str::random(5)),
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'status' => fake()->randomElement(['received', 'refunded', 'replaced']),
            'reason' => fake()->randomElement([
                'DOA on arrival',
                'Intermittent hardware fault after installation',
                'Customer reported instability under normal use',
                'Compatibility issue with business deployment',
                'Physical defect observed after unboxing',
            ]),
            'return_date' => $createdAt->copy()->addDays(rand(2, 30)),
        ]);

        $condition = fake()->randomElement(['resellable', 'opened', 'damaged', 'defective', 'doa']);
        $resolution = $condition === 'doa' ? 'replace' : fake()->randomElement(['refund', 'replace', 'repair']);

        ReturnItem::create([
            'return_id' => $return->id,
            'order_item_id' => $item->id,
            'serial_id' => $item->serial_id,
            'quantity' => 1,
            'condition_status' => $condition,
            'resolution' => $resolution,
        ]);

        if (in_array($condition, ['defective', 'doa'], true)) {
            $supplierId = $item->product->preferred_supplier_id;

            if ($supplierId) {
                SupplierDefectClaim::create([
                    'supplier_id' => $supplierId,
                    'product_id' => $item->product_id,
                    'serial_id' => $item->serial_id,
                    'return_item_id' => $return->items()->first()->id,
                    'claim_type' => $condition === 'doa' ? 'doa' : 'warranty',
                    'status' => fake()->randomElement(['open', 'sent', 'approved', 'resolved']),
                    'claim_date' => $return->return_date,
                    'resolved_at' => rand(1, 100) <= 55 ? Carbon::parse($return->return_date)->addDays(rand(7, 45)) : null,
                    'notes' => 'Historical supplier defect claim.',
                ]);
            }
        }

        if ($item->product && $item->product->product_type !== 'service') {
            InventoryMovement::create([
                'product_id' => $item->product_id,
                'serial_id' => $item->serial_id,
                'movement_type' => 'sale_return',
                'quantity' => 1,
                'unit_cost' => $item->unit_cost_at_sale,
                'reference_type' => 'return',
                'reference_id' => $return->id,
                'notes' => 'Historical return received.',
                'moved_at' => Carbon::parse($return->return_date),
            ]);

            $this->stock[$item->product_id] += 1;
        }
    }

    private function finalizeInventoryAndAlerts($products): void
    {
        foreach ($products as $product) {
            $current = max(0, (int)($this->stock[$product->id] ?? 0));
            $reserved = $product->product_type === 'service' ? 0 : rand(0, min(4, $current));

            $product->update([
                'current_stock' => $current,
                'reserved_stock' => $reserved,
            ]);

            if ($product->product_type !== 'service' && $current <= $product->reorder_threshold) {
                ReorderAlert::create([
                    'product_id' => $product->id,
                    'supplier_id' => $product->preferred_supplier_id,
                    'current_stock' => $current,
                    'reorder_threshold' => $product->reorder_threshold,
                    'suggested_quantity' => max($product->reorder_threshold * 2, 5),
                    'status' => fake()->randomElement(['open', 'po_created']),
                    'detected_at' => now()->subDays(rand(0, 40)),
                    'resolved_at' => rand(1, 100) <= 25 ? now()->subDays(rand(0, 10)) : null,
                ]);
            }
        }
    }

    private function ordersPerDay(Carbon $date): int
    {
        $growth = $this->storeGrowthFactor($date);
        $weekdayBoost = in_array($date->dayOfWeekIso, [5, 6], true) ? 1.25 : 1.0;
        $holidayBoost = in_array($date->month, [11, 12], true) ? 1.35 : 1.0;
        $summerDip = in_array($date->month, [6, 7], true) ? 0.90 : 1.0;

        return max(
            1,
            (int)round(rand(2, 6) * $growth * $weekdayBoost * $holidayBoost * $summerDip)
        );
    }

    private function storeGrowthFactor(Carbon $date): float
    {
        $yearsIntoHistory = $this->startDate->diffInYears($date);
        return min(3.3, 1 + ($yearsIntoHistory * 0.13));
    }

    private function recommendedPurchaseQty(string $categoryName, float $monthFactor): int
    {
        return match ($categoryName) {
            'RAM', 'Storage', 'Accessories', 'Keyboards', 'Mice' => rand((int)(8 * $monthFactor), (int)(25 * $monthFactor)),
            'Laptops', 'Desktops', 'Monitors', 'Printers' => rand((int)(2 * $monthFactor), (int)(8 * $monthFactor)),
            'CPUs', 'GPUs', 'Motherboards', 'Power Supplies', 'Cases', 'Cooling' => rand((int)(4 * $monthFactor), (int)(14 * $monthFactor)),
            default => rand((int)(3 * $monthFactor), (int)(10 * $monthFactor)),
        };
    }

    private function estimateBaseCost(Product $product): float
    {
        return round(((float)$product->sell_price) * rand(58, 78) / 100, 2);
    }

    private function currentHistoricalCost(int $productId, int $supplierId, Carbon $date): ?float
    {
        $row = ProductSupplierPrice::query()
            ->where('product_id', $productId)
            ->where('supplier_id', $supplierId)
            ->where('effective_from', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', $date);
            })
            ->orderByDesc('effective_from')
            ->first();

        return $row ? (float)$row->cost_price : null;
    }

    private function latestProductCost(Product $product, Carbon $date): float
    {
        $supplierId = $product->preferred_supplier_id;

        if ($supplierId) {
            $cost = $this->currentHistoricalCost($product->id, $supplierId, $date);
            if ($cost !== null) {
                return round($cost, 2);
            }
        }

        return $this->estimateBaseCost($product);
    }
}