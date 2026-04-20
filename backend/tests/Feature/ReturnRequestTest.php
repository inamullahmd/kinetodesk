<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ReturnItem;
use App\Models\ReturnRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReturnRequestTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private Customer $customer;
    private Product $product;
    private Order $order;
    private OrderItem $orderItem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->admin()->create();
        $this->customer = Customer::factory()->create();

        $this->product = Product::factory()->create([
            'current_stock' => 5,
            'reserved_stock' => 0,
            'is_serialized' => false,
        ]);

        $this->order = Order::factory()->create([
            'customer_id' => $this->customer->id,
            'status' => 'completed',
        ]);

        $this->orderItem = OrderItem::create([
            'order_id' => $this->order->id,
            'product_id' => $this->product->id,
            'description' => $this->product->name,
            'quantity' => 2,
            'unit_cost_at_sale' => 60.00,
            'unit_price_at_sale' => 100.00,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'line_total' => 200.00,
        ]);
    }

    public function test_list_returns()
    {
        ReturnRequest::factory(5)->create();

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/returns');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'return_number', 'status'],
                ],
            ]);
    }

    public function test_show_return()
    {
        $return = ReturnRequest::factory()->create(['order_id' => $this->order->id]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson("/api/returns/{$return->id}");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'id' => $return->id,
                'return_number' => $return->return_number,
            ]);
    }

    public function test_create_return_request()
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson('/api/returns', [
                'order_id' => $this->order->id,
                'reason' => 'Item defective',
                'items' => [
                    [
                        'order_item_id' => $this->orderItem->id,
                        'quantity' => 1,
                        'condition_status' => 'defective',
                        'resolution' => 'refund',
                    ],
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['status' => 'initiated']);

        $this->assertDatabaseHas('returns', ['order_id' => $this->order->id]);
    }

    public function test_process_return_approved()
    {
        $return = $this->createReturnWithItem('defective');

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson("/api/returns/{$return->id}/process", [
                'status' => 'approved',
            ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['status' => 'approved']);
    }

    public function test_process_return_refunded_resellable_item_restocks_inventory()
    {
        $return = $this->createReturnWithItem('resellable');
        $return->update(['status' => 'approved']);

        $startingStock = (int) $this->product->current_stock;

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson("/api/returns/{$return->id}/process", [
                'status' => 'refunded',
            ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['status' => 'refunded']);

        $this->product->refresh();

        $this->assertEquals($startingStock + 1, (int) $this->product->current_stock);

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $this->product->id,
            'movement_type' => 'sale_return',
            'reference_type' => 'ReturnRequest',
            'reference_id' => $return->id,
            'quantity' => 1,
            'unit_cost' => 60.00,
        ]);
    }

    public function test_process_return_refunded_defective_item_does_not_restock_sellable_inventory()
    {
        $return = $this->createReturnWithItem('defective');
        $return->update(['status' => 'approved']);

        $startingStock = (int) $this->product->current_stock;

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson("/api/returns/{$return->id}/process", [
                'status' => 'refunded',
            ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['status' => 'refunded']);

        $this->product->refresh();

        $this->assertEquals($startingStock, (int) $this->product->current_stock);

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $this->product->id,
            'movement_type' => 'defective_mark',
            'reference_type' => 'ReturnRequest',
            'reference_id' => $return->id,
            'quantity' => 1,
            'unit_cost' => 60.00,
        ]);
    }

    public function test_cannot_return_more_than_was_sold()
    {
        $return = $this->createReturnWithItem('resellable');
        $return->update(['status' => 'approved']);

        ReturnItem::query()->where('return_id', $return->id)->update(['quantity' => 2]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson('/api/returns', [
                'order_id' => $this->order->id,
                'reason' => 'Second return attempt',
                'items' => [
                    [
                        'order_item_id' => $this->orderItem->id,
                        'quantity' => 1,
                        'condition_status' => 'resellable',
                        'resolution' => 'refund',
                    ],
                ],
            ]);

        $response->assertStatus(422);
    }

    public function test_filter_returns_by_status()
    {
        ReturnRequest::factory(3)->create(['status' => 'initiated']);
        ReturnRequest::factory(2)->create(['status' => 'refunded']);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/returns?status=initiated');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    private function createReturnWithItem(string $conditionStatus): ReturnRequest
    {
        $return = ReturnRequest::factory()->create([
            'order_id' => $this->order->id,
            'customer_id' => $this->customer->id,
            'status' => 'initiated',
        ]);

        ReturnItem::create([
            'return_id' => $return->id,
            'order_item_id' => $this->orderItem->id,
            'serial_id' => null,
            'quantity' => 1,
            'condition_status' => $conditionStatus,
            'resolution' => 'refund',
        ]);

        return $return;
    }
}