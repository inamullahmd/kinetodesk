<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\ProductSupplierPrice;
use App\Models\ServiceTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceTicketTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adminUser = User::factory()->admin()->create();
        $this->customer = Customer::factory()->create();
    }

    public function test_list_service_tickets()
    {
        ServiceTicket::factory(5)->create();

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/service-tickets');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'ticket_number', 'status'],
                ],
            ]);
    }

    public function test_show_service_ticket()
    {
        $ticket = ServiceTicket::factory()->create(['customer_id' => $this->customer->id]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson("/api/service-tickets/{$ticket->id}");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number,
            ]);
    }

    public function test_create_service_ticket()
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson('/api/service-tickets', [
                'customer_id' => $this->customer->id,
                'device_brand' => 'Dell',
                'device_model' => 'XPS 13',
                'device_serial_number' => 'DELL-12345',
                'issue_description' => 'Screen flickering',
            ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['status' => 'received']);

        $this->assertDatabaseHas('service_tickets', ['customer_id' => $this->customer->id]);
    }

    public function test_update_service_ticket_diagnosis()
    {
        $ticket = ServiceTicket::factory()->create([
            'customer_id' => $this->customer->id,
            'status' => 'received',
        ]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->patchJson("/api/service-tickets/{$ticket->id}", [
                'status' => 'diagnosing',
                'diagnosis_notes' => 'Issue is with display driver',
                'labor_cost' => 50.00,
            ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['status' => 'diagnosing']);

        $this->assertDatabaseHas('service_tickets', [
            'id' => $ticket->id,
            'diagnosis_notes' => 'Issue is with display driver',
            'status' => 'diagnosing',
        ]);
    }

    public function test_update_service_ticket_add_parts()
    {
        $product = Product::factory()->create([
            'current_stock' => 10,
            'reserved_stock' => 0,
            'is_serialized' => false,
        ]);

        $supplier = Supplier::factory()->create();

        ProductSupplierPrice::factory()->create([
            'product_id' => $product->id,
            'supplier_id' => $supplier->id,
            'cost_price' => 40.00,
            'effective_from' => now()->subDay(),
            'effective_to' => null,
            'is_primary' => true,
        ]);

        $ticket = ServiceTicket::factory()->create([
            'customer_id' => $this->customer->id,
            'status' => 'diagnosing',
        ]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->patchJson("/api/service-tickets/{$ticket->id}", [
                'status' => 'in_progress',
                'labor_cost' => 50.00,
                'parts' => [
                    [
                        'product_id' => $product->id,
                        'quantity' => 1,
                        'unit_price' => 75.00,
                    ],
                ],
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('service_ticket_parts', [
            'service_ticket_id' => $ticket->id,
            'product_id' => $product->id,
        ]);

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'movement_type' => 'service_use',
            'reference_type' => 'ServiceTicket',
            'reference_id' => $ticket->id,
        ]);
    }

    public function test_complete_service_ticket()
    {
        $ticket = ServiceTicket::factory()->create([
            'customer_id' => $this->customer->id,
            'status' => 'in_progress',
        ]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->patchJson("/api/service-tickets/{$ticket->id}", [
                'status' => 'completed',
                'labor_cost' => 100.00,
            ]);

        $response->assertStatus(200);

        $ticket->refresh();
        $this->assertNotNull($ticket->completed_at);
    }

    public function test_deliver_service_ticket()
    {
        $ticket = ServiceTicket::factory()->create([
            'customer_id' => $this->customer->id,
            'status' => 'completed',
        ]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->patchJson("/api/service-tickets/{$ticket->id}", [
                'status' => 'delivered',
            ]);

        $response->assertStatus(200);

        $ticket->refresh();
        $this->assertNotNull($ticket->delivered_at);
    }

    public function test_filter_service_tickets_by_status()
    {
        ServiceTicket::factory(3)->create(['status' => 'received']);
        ServiceTicket::factory(2)->create(['status' => 'completed']);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/service-tickets?status=received');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }
}