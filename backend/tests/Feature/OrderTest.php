<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductSerial;
use App\Models\ProductSupplierPrice;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private Customer $customer;
    private Product $product;
    private Supplier $supplier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->admin()->create();
        $this->customer = Customer::factory()->create();
        $this->supplier = Supplier::factory()->create();

        $this->product = Product::factory()->create([
            'current_stock' => 100,
            'reserved_stock' => 0,
            'is_serialized' => false,
            'sell_price' => 99.99,
        ]);

        ProductSupplierPrice::query()->create([
            'product_id' => $this->product->id,
            'supplier_id' => $this->supplier->id,
            'supplier_sku' => 'SUP-' . $this->product->sku,
            'cost_price' => 55.00,
            'currency_code' => 'USD',
            'minimum_order_qty' => 1,
            'effective_from' => now()->subDay(),
            'effective_to' => null,
            'is_primary' => true,
        ]);
    }

    public function test_list_orders()
    {
        Order::factory(5)->create();

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/orders');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'order_number', 'status', 'total_amount'],
                ],
            ]);
    }

    public function test_show_order()
    {
        $order = Order::factory()->create(['customer_id' => $this->customer->id]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson("/api/orders/{$order->id}");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'id' => $order->id,
                'order_number' => $order->order_number,
            ]);
    }

    public function test_create_order()
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson('/api/orders', [
                'customer_id' => $this->customer->id,
                'order_type' => 'retail',
                'channel' => 'in_store',
                'discount_amount' => 0,
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 5,
                        'unit_price' => 99.99,
                    ],
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['status' => 'draft']);

        $this->assertDatabaseHas('orders', ['customer_id' => $this->customer->id]);
        $this->assertDatabaseHas('order_items', [
            'product_id' => $this->product->id,
            'quantity' => 5,
            'unit_cost_at_sale' => 55.00,
        ]);
    }

    public function test_create_order_reserves_inventory()
    {
        $initialReserved = (int) $this->product->reserved_stock;

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson('/api/orders', [
                'customer_id' => $this->customer->id,
                'order_type' => 'retail',
                'channel' => 'online',
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 5,
                        'unit_price' => 99.99,
                    ],
                ],
            ]);

        $response->assertStatus(201);

        $this->product->refresh();
        $this->assertEquals($initialReserved + 5, (int) $this->product->reserved_stock);
    }

    public function test_create_order_with_insufficient_stock()
    {
        $product = Product::factory()->create([
            'current_stock' => 2,
            'reserved_stock' => 0,
            'is_serialized' => false,
        ]);

        ProductSupplierPrice::query()->create([
            'product_id' => $product->id,
            'supplier_id' => $this->supplier->id,
            'supplier_sku' => 'SUP-' . $product->sku,
            'cost_price' => 25.00,
            'currency_code' => 'USD',
            'minimum_order_qty' => 1,
            'effective_from' => now()->subDay(),
            'effective_to' => null,
            'is_primary' => true,
        ]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson('/api/orders', [
                'customer_id' => $this->customer->id,
                'order_type' => 'retail',
                'channel' => 'in_store',
                'items' => [
                    [
                        'product_id' => $product->id,
                        'quantity' => 5,
                        'unit_price' => 99.99,
                    ],
                ],
            ]);

        $response->assertStatus(422);

        $message = (string) $response->json('message');
        $this->assertTrue(str_contains(strtolower($message), 'insufficient stock'));
    }

    public function test_create_order_with_serialized_product_requires_serial()
    {
        $serializedProduct = Product::factory()->create([
            'current_stock' => 1,
            'reserved_stock' => 0,
            'is_serialized' => true,
        ]);

        ProductSupplierPrice::query()->create([
            'product_id' => $serializedProduct->id,
            'supplier_id' => $this->supplier->id,
            'supplier_sku' => 'SUP-' . $serializedProduct->sku,
            'cost_price' => 120.00,
            'currency_code' => 'USD',
            'minimum_order_qty' => 1,
            'effective_from' => now()->subDay(),
            'effective_to' => null,
            'is_primary' => true,
        ]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson('/api/orders', [
                'customer_id' => $this->customer->id,
                'order_type' => 'retail',
                'channel' => 'in_store',
                'items' => [
                    [
                        'product_id' => $serializedProduct->id,
                        'quantity' => 1,
                        'unit_price' => 199.99,
                    ],
                ],
            ]);

        $response->assertStatus(422);

        $message = (string) $response->json('message');
        $this->assertTrue(str_contains(strtolower($message), 'serial_id'));
    }

    public function test_create_order_with_serialized_product_and_valid_serial()
    {
        $serializedProduct = Product::factory()->create([
            'current_stock' => 1,
            'reserved_stock' => 0,
            'is_serialized' => true,
        ]);

        ProductSupplierPrice::query()->create([
            'product_id' => $serializedProduct->id,
            'supplier_id' => $this->supplier->id,
            'supplier_sku' => 'SUP-' . $serializedProduct->sku,
            'cost_price' => 120.00,
            'currency_code' => 'USD',
            'minimum_order_qty' => 1,
            'effective_from' => now()->subDay(),
            'effective_to' => null,
            'is_primary' => true,
        ]);

        $serial = ProductSerial::query()->create([
            'product_id' => $serializedProduct->id,
            'purchase_order_item_id' => null,
            'serial_number' => 'SERIAL-001',
            'status' => 'in_stock',
            'warranty_expiry_date' => now()->addYear()->toDateString(),
        ]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson('/api/orders', [
                'customer_id' => $this->customer->id,
                'order_type' => 'retail',
                'channel' => 'in_store',
                'items' => [
                    [
                        'product_id' => $serializedProduct->id,
                        'serial_id' => $serial->id,
                        'quantity' => 1,
                        'unit_price' => 199.99,
                    ],
                ],
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('order_items', [
            'product_id' => $serializedProduct->id,
            'serial_id' => $serial->id,
            'quantity' => 1,
        ]);
    }

    public function test_fulfill_order_confirm()
    {
        $order = Order::factory()->create([
            'customer_id' => $this->customer->id,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson("/api/orders/{$order->id}/fulfill", [
                'status' => 'confirmed',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'confirmed',
        ]);
    }

    public function test_fulfill_order_paid()
    {
        $order = Order::factory()->create([
            'customer_id' => $this->customer->id,
            'status' => 'confirmed',
        ]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson("/api/orders/{$order->id}/fulfill", [
                'status' => 'paid',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'paid',
        ]);
    }

    public function test_complete_order_consumes_inventory_and_releases_reserved_stock()
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson('/api/orders', [
                'customer_id' => $this->customer->id,
                'order_type' => 'retail',
                'channel' => 'in_store',
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 3,
                        'unit_price' => 99.99,
                    ],
                ],
            ]);

        $response->assertStatus(201);

        $orderId = $response->json('order.id');

        $this->actingAs($this->adminUser, 'sanctum')
            ->postJson("/api/orders/{$orderId}/fulfill", ['status' => 'confirmed'])
            ->assertStatus(200);

        $this->actingAs($this->adminUser, 'sanctum')
            ->postJson("/api/orders/{$orderId}/fulfill", ['status' => 'paid'])
            ->assertStatus(200);

        $this->actingAs($this->adminUser, 'sanctum')
            ->postJson("/api/orders/{$orderId}/fulfill", ['status' => 'completed'])
            ->assertStatus(200);

        $this->product->refresh();

        $this->assertEquals(97, (int) $this->product->current_stock);
        $this->assertEquals(0, (int) $this->product->reserved_stock);

        $this->assertDatabaseHas('orders', [
            'id' => $orderId,
            'status' => 'completed',
        ]);

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $this->product->id,
            'movement_type' => 'sale',
            'reference_type' => 'Order',
            'reference_id' => $orderId,
            'quantity' => 3,
            'unit_cost' => 55.00,
        ]);
    }

    public function test_cancel_order_releases_reserved_inventory()
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson('/api/orders', [
                'customer_id' => $this->customer->id,
                'order_type' => 'retail',
                'channel' => 'in_store',
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 4,
                        'unit_price' => 99.99,
                    ],
                ],
            ]);

        $response->assertStatus(201);

        $orderId = $response->json('order.id');

        $this->product->refresh();
        $this->assertEquals(4, (int) $this->product->reserved_stock);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson("/api/orders/{$orderId}/cancel");

        $response->assertStatus(200);

        $this->product->refresh();

        $this->assertEquals(100, (int) $this->product->current_stock);
        $this->assertEquals(0, (int) $this->product->reserved_stock);

        $this->assertDatabaseHas('orders', [
            'id' => $orderId,
            'status' => 'cancelled',
        ]);
    }

    public function test_filter_orders_by_status()
    {
        Order::factory(3)->create(['status' => 'draft']);
        Order::factory(2)->create(['status' => 'completed']);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/orders?status=draft');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }
}