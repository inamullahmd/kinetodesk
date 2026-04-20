<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adminUser = User::factory()->admin()->create();
    }

    public function test_list_customers()
    {
        Customer::factory(5)->create();

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/customers');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'customer_type', 'email', 'phone'],
                ],
            ]);
    }

    public function test_show_customer()
    {
        $customer = Customer::factory()->create();

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson("/api/customers/{$customer->id}");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'id' => $customer->id,
                'email' => $customer->email,
            ]);
    }

    public function test_create_retail_customer()
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson('/api/customers', [
                'customer_type' => 'retail',
                'first_name' => 'John',
                'last_name' => 'Doe',
                'email' => 'john@example.com',
                'phone' => '555-1234',
                'billing_address' => '123 Main St',
                'is_active' => true,
            ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['email' => 'john@example.com']);

        $this->assertDatabaseHas('customers', ['email' => 'john@example.com']);
    }

    public function test_create_b2b_customer()
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson('/api/customers', [
                'customer_type' => 'b2b',
                'business_name' => 'Test Corp',
                'email' => 'corp@example.com',
                'phone' => '555-5678',
                'credit_limit' => 5000.00,
                'payment_terms_days' => 30,
            ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['business_name' => 'Test Corp']);

        $this->assertDatabaseHas('customers', ['business_name' => 'Test Corp']);
    }

    public function test_create_customer_with_duplicate_email()
    {
        Customer::factory()->create(['email' => 'duplicate@example.com']);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson('/api/customers', [
                'customer_type' => 'retail',
                'email' => 'duplicate@example.com',
                'first_name' => 'Jane',
                'phone' => '555-9999',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_update_customer()
    {
        $customer = Customer::factory()->create();

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->patchJson("/api/customers/{$customer->id}", [
                'phone' => '555-9999',
            ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['phone' => '555-9999']);
    }

    public function test_deactivate_customer()
    {
        $customer = Customer::factory()->create();

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->deleteJson("/api/customers/{$customer->id}");

        $response->assertStatus(200);

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'is_active' => false,
        ]);
    }

    public function test_search_customers_by_name()
    {
        Customer::factory(5)->create();
        Customer::factory()->create(['first_name' => 'SearchMe', 'last_name' => 'Customer']);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/customers?search=SearchMe');

        $response->assertStatus(200)
            ->assertJsonFragment(['first_name' => 'SearchMe']);
    }

    public function test_filter_customers_by_type()
    {
        Customer::factory(3)->create(['customer_type' => 'b2b']);
        Customer::factory(2)->create(['customer_type' => 'retail']);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/customers?customer_type=b2b');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }
}
