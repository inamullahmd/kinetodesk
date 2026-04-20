<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseOrderTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private Supplier $supplier;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->admin()->create();

        if (! $this->adminUser->employee) {
            $employee = Employee::factory()->create([
                'email' => 'admin-employee-' . $this->adminUser->id . '@example.com',
                'first_name' => explode(' ', $this->adminUser->name)[0] ?? 'Admin',
                'last_name' => explode(' ', $this->adminUser->name)[1] ?? 'User',
            ]);

            $this->adminUser->update(['employee_id' => $employee->id]);
            $this->adminUser->load('employee');
        }

        $this->supplier = Supplier::factory()->create();

        $this->product = Product::factory()->create([
            'current_stock' => 0,
            'reserved_stock' => 0,
            'is_serialized' => false,
        ]);
    }

    public function test_list_purchase_orders()
    {
        PurchaseOrder::factory(5)->create(['supplier_id' => $this->supplier->id]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/purchase-orders');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'po_number', 'status', 'total_amount'],
                ],
            ]);
    }

    public function test_show_purchase_order()
    {
        $po = PurchaseOrder::factory()->create(['supplier_id' => $this->supplier->id]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson("/api/purchase-orders/{$po->id}");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'id' => $po->id,
                'po_number' => $po->po_number,
            ]);
    }

    public function test_create_purchase_order()
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson('/api/purchase-orders', [
                'supplier_id' => $this->supplier->id,
                'expected_date' => now()->addDays(7)->format('Y-m-d'),
                'notes' => 'Test PO',
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity_ordered' => 10,
                        'unit_cost' => 50.00,
                    ],
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['status' => 'sent']);

        $this->assertDatabaseHas('purchase_orders', [
            'supplier_id' => $this->supplier->id,
            'status' => 'sent',
        ]);

        $this->assertDatabaseHas('purchase_order_items', [
            'product_id' => $this->product->id,
            'quantity_ordered' => 10,
            'quantity_received' => 0,
            'unit_cost' => 50.00,
        ]);
    }

    public function test_create_purchase_order_with_multiple_items()
    {
        $product2 = Product::factory()->create([
            'current_stock' => 0,
            'reserved_stock' => 0,
        ]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson('/api/purchase-orders', [
                'supplier_id' => $this->supplier->id,
                'expected_date' => now()->addDays(7)->format('Y-m-d'),
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity_ordered' => 5,
                        'unit_cost' => 50.00,
                    ],
                    [
                        'product_id' => $product2->id,
                        'quantity_ordered' => 10,
                        'unit_cost' => 25.00,
                    ],
                ],
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('purchase_order_items', [
            'product_id' => $this->product->id,
            'quantity_ordered' => 5,
        ]);

        $this->assertDatabaseHas('purchase_order_items', [
            'product_id' => $product2->id,
            'quantity_ordered' => 10,
        ]);
    }

    public function test_receive_purchase_order()
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson('/api/purchase-orders', [
                'supplier_id' => $this->supplier->id,
                'expected_date' => now()->addDays(7)->format('Y-m-d'),
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity_ordered' => 10,
                        'unit_cost' => 50.00,
                    ],
                ],
            ]);

        $poId = $response->json('purchase_order.id');
        $po = PurchaseOrder::query()->findOrFail($poId);
        $poItem = $po->items()->firstOrFail();

        $receiveResponse = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson("/api/purchase-orders/{$po->id}/receive", [
                'received_quantity' => [
                    $poItem->id => 10,
                ],
            ]);

        $receiveResponse->assertStatus(200);

        $poItem->product->refresh();

        $this->assertEquals(10, (int) $poItem->product->current_stock);

        $this->assertDatabaseHas('purchase_orders', [
            'id' => $po->id,
            'status' => 'received',
        ]);

        $this->assertDatabaseHas('purchase_order_items', [
            'id' => $poItem->id,
            'quantity_received' => 10,
        ]);
    }

    public function test_receive_purchase_order_partial()
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson('/api/purchase-orders', [
                'supplier_id' => $this->supplier->id,
                'expected_date' => now()->addDays(7)->format('Y-m-d'),
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity_ordered' => 20,
                        'unit_cost' => 50.00,
                    ],
                ],
            ]);

        $poId = $response->json('purchase_order.id');
        $po = PurchaseOrder::query()->findOrFail($poId);
        $poItem = $po->items()->firstOrFail();

        $receiveResponse = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson("/api/purchase-orders/{$po->id}/receive", [
                'received_quantity' => [
                    $poItem->id => 10,
                ],
            ]);

        $receiveResponse->assertStatus(200);

        $this->assertDatabaseHas('purchase_orders', [
            'id' => $po->id,
            'status' => 'partially_received',
        ]);

        $this->assertDatabaseHas('purchase_order_items', [
            'id' => $poItem->id,
            'quantity_received' => 10,
        ]);
    }

    public function test_receive_purchase_order_rejects_over_receipt()
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson('/api/purchase-orders', [
                'supplier_id' => $this->supplier->id,
                'expected_date' => now()->addDays(7)->format('Y-m-d'),
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity_ordered' => 10,
                        'unit_cost' => 50.00,
                    ],
                ],
            ]);

        $poId = $response->json('purchase_order.id');
        $po = PurchaseOrder::query()->findOrFail($poId);
        $poItem = $po->items()->firstOrFail();

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson("/api/purchase-orders/{$po->id}/receive", [
                'received_quantity' => [
                    $poItem->id => 11,
                ],
            ]);

        $response->assertStatus(422);
    }

    public function test_receive_purchase_order_creates_inventory_movement()
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson('/api/purchase-orders', [
                'supplier_id' => $this->supplier->id,
                'expected_date' => now()->addDays(7)->format('Y-m-d'),
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity_ordered' => 10,
                        'unit_cost' => 50.00,
                    ],
                ],
            ]);

        $poId = $response->json('purchase_order.id');
        $po = PurchaseOrder::query()->findOrFail($poId);
        $poItem = $po->items()->firstOrFail();

        $this->actingAs($this->adminUser, 'sanctum')
            ->postJson("/api/purchase-orders/{$po->id}/receive", [
                'received_quantity' => [
                    $poItem->id => 5,
                ],
            ])
            ->assertStatus(200);

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $poItem->product->id,
            'movement_type' => 'purchase_receive',
            'reference_type' => 'PurchaseOrder',
            'reference_id' => $po->id,
            'quantity' => 5,
            'unit_cost' => 50.00,
        ]);
    }

    public function test_receive_purchase_order_with_serials()
    {
        $serializedProduct = Product::factory()->create([
            'current_stock' => 0,
            'reserved_stock' => 0,
            'is_serialized' => true,
        ]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson('/api/purchase-orders', [
                'supplier_id' => $this->supplier->id,
                'expected_date' => now()->addDays(7)->format('Y-m-d'),
                'items' => [
                    [
                        'product_id' => $serializedProduct->id,
                        'quantity_ordered' => 2,
                        'unit_cost' => 50.00,
                    ],
                ],
            ]);

        $poId = $response->json('purchase_order.id');
        $po = PurchaseOrder::query()->findOrFail($poId);
        $poItem = $po->items()->firstOrFail();

        $receiveResponse = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson("/api/purchase-orders/{$po->id}/receive", [
                'received_quantity' => [
                    $poItem->id => 2,
                ],
                'serials' => [
                    $poItem->id => [
                        [
                            'serial_number' => 'SN-001',
                            'warranty_expiry_date' => '2028-04-17',
                        ],
                        [
                            'serial_number' => 'SN-002',
                            'warranty_expiry_date' => '2028-04-17',
                        ],
                    ],
                ],
            ]);

        $receiveResponse->assertStatus(200);

        $this->assertDatabaseHas('product_serials', [
            'purchase_order_item_id' => $poItem->id,
            'serial_number' => 'SN-001',
            'status' => 'in_stock',
        ]);

        $serializedProduct->refresh();
        $this->assertEquals(2, (int) $serializedProduct->current_stock);
    }

    public function test_receive_purchase_order_with_missing_serials_fails_for_serialized_product()
    {
        $serializedProduct = Product::factory()->create([
            'current_stock' => 0,
            'reserved_stock' => 0,
            'is_serialized' => true,
        ]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson('/api/purchase-orders', [
                'supplier_id' => $this->supplier->id,
                'expected_date' => now()->addDays(7)->format('Y-m-d'),
                'items' => [
                    [
                        'product_id' => $serializedProduct->id,
                        'quantity_ordered' => 2,
                        'unit_cost' => 50.00,
                    ],
                ],
            ]);

        $poId = $response->json('purchase_order.id');
        $po = PurchaseOrder::query()->findOrFail($poId);
        $poItem = $po->items()->firstOrFail();

        $receiveResponse = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson("/api/purchase-orders/{$po->id}/receive", [
                'received_quantity' => [
                    $poItem->id => 2,
                ],
            ]);

        $receiveResponse->assertStatus(422);
    }

    public function test_filter_purchase_orders_by_status()
    {
        PurchaseOrder::factory(3)->create([
            'supplier_id' => $this->supplier->id,
            'status' => 'draft',
        ]);

        PurchaseOrder::factory(2)->create([
            'supplier_id' => $this->supplier->id,
            'status' => 'received',
        ]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/purchase-orders?status=draft');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }
}