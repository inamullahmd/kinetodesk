<?php

namespace Tests\Feature;

use App\Models\BuildTemplate;
use App\Models\Customer;
use App\Models\CustomBuildOrder;
use App\Models\Product;
use App\Models\ProductSupplierPrice;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomBuildOrderTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private Customer $customer;
    private BuildTemplate $template;
    private Product $product;
    private Supplier $supplier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->admin()->create();
        $this->customer = Customer::factory()->create();
        $this->template = BuildTemplate::factory()->create();
        $this->supplier = Supplier::factory()->create();

        $this->product = Product::factory()->create([
            'current_stock' => 100,
            'reserved_stock' => 0,
            'is_serialized' => false,
            'sell_price' => 200,
        ]);

        ProductSupplierPrice::query()->create([
            'product_id' => $this->product->id,
            'supplier_id' => $this->supplier->id,
            'supplier_sku' => 'SUP-' . $this->product->sku,
            'cost_price' => 120,
            'currency_code' => 'USD',
            'minimum_order_qty' => 1,
            'effective_from' => now()->subDay(),
            'effective_to' => null,
            'is_primary' => true,
        ]);
    }

    public function test_list_custom_builds()
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/custom-builds');

        $response->assertStatus(200)
            ->assertJsonStructure(['data']);
    }

    public function test_create_custom_build()
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson('/api/custom-builds', [
                'customer_id' => $this->customer->id,
                'build_template_id' => $this->template->id,
                'channel' => 'online',
                'labor_charge' => 50.00,
                'assembly_notes' => 'Test build',
                'items' => [
                    [
                        'component_type' => 'cpu',
                        'product_id' => $this->product->id,
                        'quantity' => 1,
                        'unit_price' => 199.99,
                    ],
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['build_status' => 'draft']);

        $this->assertDatabaseHas('custom_build_orders', ['build_template_id' => $this->template->id]);
        $this->assertDatabaseHas('order_items', [
            'unit_cost_at_sale' => 120.00,
        ]);
    }

    public function test_create_custom_build_reserves_inventory()
    {
        $initialReserved = (int) $this->product->reserved_stock;

        $this->actingAs($this->adminUser, 'sanctum')
            ->postJson('/api/custom-builds', [
                'customer_id' => $this->customer->id,
                'build_template_id' => $this->template->id,
                'channel' => 'online',
                'items' => [
                    [
                        'component_type' => 'cpu',
                        'product_id' => $this->product->id,
                        'quantity' => 3,
                        'unit_price' => 199.99,
                    ],
                ],
            ])
            ->assertStatus(201);

        $this->product->refresh();
        $this->assertEquals($initialReserved + 3, (int) $this->product->reserved_stock);
    }

    public function test_show_custom_build()
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson('/api/custom-builds', [
                'customer_id' => $this->customer->id,
                'build_template_id' => $this->template->id,
                'channel' => 'online',
                'items' => [
                    [
                        'component_type' => 'cpu',
                        'product_id' => $this->product->id,
                        'quantity' => 1,
                        'unit_price' => 199.99,
                    ],
                ],
            ]);

        $build = $response->json('custom_build');

        $getResponse = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson("/api/custom-builds/{$build['id']}");

        $getResponse->assertStatus(200)
            ->assertJsonFragment(['id' => $build['id']]);
    }

    public function test_complete_custom_build()
    {
        $createResponse = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson('/api/custom-builds', [
                'customer_id' => $this->customer->id,
                'build_template_id' => $this->template->id,
                'channel' => 'online',
                'items' => [
                    [
                        'component_type' => 'cpu',
                        'product_id' => $this->product->id,
                        'quantity' => 2,
                        'unit_price' => 199.99,
                    ],
                ],
            ]);

        $buildId = $createResponse->json('custom_build.id');

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson("/api/custom-builds/{$buildId}/complete");

        $response->assertStatus(200)
            ->assertJsonFragment(['build_status' => 'completed']);

        $this->product->refresh();
        $this->assertEquals(98, (int) $this->product->current_stock);
        $this->assertEquals(0, (int) $this->product->reserved_stock);

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $this->product->id,
            'movement_type' => 'build_consume',
            'reference_type' => 'CustomBuildOrder',
            'reference_id' => $buildId,
            'quantity' => 2,
            'unit_cost' => 120.00,
        ]);
    }

    public function test_cancel_custom_build()
    {
        $createResponse = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson('/api/custom-builds', [
                'customer_id' => $this->customer->id,
                'build_template_id' => $this->template->id,
                'channel' => 'online',
                'items' => [
                    [
                        'component_type' => 'memory',
                        'product_id' => $this->product->id,
                        'quantity' => 1,
                        'unit_price' => 79.99,
                    ],
                ],
            ]);

        $buildId = $createResponse->json('custom_build.id');

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson("/api/custom-builds/{$buildId}/cancel");

        $response->assertStatus(200)
            ->assertJsonFragment(['build_status' => 'cancelled']);

        $this->product->refresh();
        $this->assertEquals(100, (int) $this->product->current_stock);
        $this->assertEquals(0, (int) $this->product->reserved_stock);
    }

    public function test_filter_custom_builds_by_status()
    {
        CustomBuildOrder::factory()->count(2)->create(['build_status' => 'draft']);
        CustomBuildOrder::factory()->count(1)->create(['build_status' => 'completed']);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/custom-builds?build_status=draft');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }
}