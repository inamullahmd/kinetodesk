<?php

namespace Tests\Feature;

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adminUser = User::factory()->admin()->create();
    }

    public function test_list_suppliers()
    {
        Supplier::factory(5)->create();

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/suppliers');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'supplier_code', 'email'],
                ],
            ]);
    }

    public function test_show_supplier()
    {
        $supplier = Supplier::factory()->create();

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson("/api/suppliers/{$supplier->id}");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'id' => $supplier->id,
                'name' => $supplier->name,
            ]);
    }

    public function test_create_supplier()
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson('/api/suppliers', [
                'supplier_code' => 'SUP-001',
                'name' => 'Test Supplier',
                'contact_person' => 'John Doe',
                'email' => 'supplier@test.com',
                'phone' => '555-1234',
                'default_lead_time_days' => 7,
                'rating' => 4.5,
                'is_active' => true,
            ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['name' => 'Test Supplier']);

        $this->assertDatabaseHas('suppliers', ['supplier_code' => 'SUP-001']);
    }

    public function test_create_supplier_with_duplicate_code()
    {
        Supplier::factory()->create(['supplier_code' => 'SUP-DUP']);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson('/api/suppliers', [
                'supplier_code' => 'SUP-DUP',
                'name' => 'Another Supplier',
                'email' => 'another@test.com',
                'phone' => '555-5678',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('supplier_code');
    }

    public function test_update_supplier()
    {
        $supplier = Supplier::factory()->create();

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->patchJson("/api/suppliers/{$supplier->id}", [
                'rating' => 4.8,
            ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['rating' => '4.80']);
    }

    public function test_deactivate_supplier()
    {
        $supplier = Supplier::factory()->create();

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->deleteJson("/api/suppliers/{$supplier->id}");

        $response->assertStatus(200);

        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
            'is_active' => false,
        ]);
    }

    public function test_search_suppliers()
    {
        Supplier::factory(5)->create();
        Supplier::factory()->create(['name' => 'Special Supplier']);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/suppliers?search=Special');

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Special Supplier']);
    }

    public function test_filter_active_suppliers()
    {
        Supplier::factory(3)->create(['is_active' => true]);
        Supplier::factory(2)->create(['is_active' => false]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/suppliers?active_only=true');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }
}
