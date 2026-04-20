<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adminUser = User::factory()->admin()->create();
    }

    public function test_list_products()
    {
        Product::factory(5)->create();

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/products');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'sku', 'name', 'category_id', 'current_stock'],
                ],
            ]);
    }

    public function test_list_products_with_pagination()
    {
        Product::factory(20)->create();

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/products?per_page=10');

        $response->assertStatus(200)
            ->assertJsonFragment(['per_page' => 10]);
    }

    public function test_show_product()
    {
        $product = Product::factory()->create();

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson("/api/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'id' => $product->id,
                'sku' => $product->sku,
                'name' => $product->name,
            ]);
    }

    public function test_create_product()
    {
        $category = Category::factory()->create();
        $brand = Brand::factory()->create();

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson('/api/products', [
                'sku' => 'SKU-12345',
                'name' => 'Test Product',
                'category_id' => $category->id,
                'brand_id' => $brand->id,
                'product_type' => 'inventory',
                'is_serialized' => false,
                'reorder_threshold' => 10,
                'sell_price' => 99.99,
                'is_active' => true,
            ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['sku' => 'SKU-12345']);

        $this->assertDatabaseHas('products', [
            'sku' => 'SKU-12345',
            'current_stock' => 0,
            'reserved_stock' => 0,
        ]);
    }

    public function test_create_product_with_invalid_category()
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson('/api/products', [
                'sku' => 'SKU-12345',
                'name' => 'Test Product',
                'category_id' => 999,
                'product_type' => 'inventory',
                'is_serialized' => false,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('category_id');
    }

    public function test_create_product_with_duplicate_sku()
    {
        $existingProduct = Product::factory()->create(['sku' => 'SKU-UNIQUE']);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson('/api/products', [
                'sku' => 'SKU-UNIQUE',
                'name' => 'Duplicate Product',
                'category_id' => $existingProduct->category_id,
                'product_type' => 'inventory',
                'is_serialized' => false,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('sku');
    }

    public function test_update_product()
    {
        $product = Product::factory()->create();

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->patchJson("/api/products/{$product->id}", [
                'sell_price' => 149.99,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('product.sell_price', '149.99');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'sell_price' => 149.99,
        ]);
    }

    public function test_update_product_does_not_allow_direct_stock_override()
    {
        $product = Product::factory()->create([
            'current_stock' => 15,
            'reserved_stock' => 4,
        ]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->patchJson("/api/products/{$product->id}", [
                'current_stock' => 999,
                'reserved_stock' => 999,
                'sell_price' => 149.99,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('product.sell_price', '149.99');

        $product->refresh();

        $this->assertEquals(15, (int) $product->current_stock);
        $this->assertEquals(4, (int) $product->reserved_stock);
        $this->assertEquals(149.99, (float) $product->sell_price);
    }

    public function test_filter_products_by_search()
    {
        Product::factory()->create(['name' => 'Gaming Laptop', 'sku' => 'GL-001']);
        Product::factory()->create(['name' => 'Office Mouse', 'sku' => 'OM-002']);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/products?search=Laptop');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_filter_products_by_active_only()
    {
        Product::factory()->create(['is_active' => true]);
        Product::factory()->create(['is_active' => false]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/products?active_only=1');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }
}