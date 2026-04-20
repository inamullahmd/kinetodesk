<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductSupplierPrice;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'order_number' => 'ORD-' . $this->faker->unique()->numerify('######'),
            'order_type' => $this->faker->randomElement(['retail', 'b2b', 'custom_build']),
            'channel' => $this->faker->randomElement(['in_store', 'online', 'phone', 'walk_in']),
            'status' => $this->faker->randomElement(['draft', 'pending_approval', 'confirmed', 'paid', 'partially_paid', 'cancelled', 'refunded', 'completed']),
            'subtotal' => $this->faker->numberBetween(100, 5000),
            'discount_amount' => $this->faker->numberBetween(0, 100),
            'tax_amount' => $this->faker->numberBetween(5, 500),
            'total_amount' => $this->faker->numberBetween(100, 5000),
            'amount_paid' => $this->faker->numberBetween(0, 5000),
            'notes' => $this->faker->optional()->sentence(),
            'created_at' => $this->faker->dateTime(),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function ($order) {
            $product = Product::factory()->create([
                'current_stock' => 10,
                'reserved_stock' => 0,
                'is_serialized' => false,
                'sell_price' => 100,
            ]);

            $supplier = Supplier::factory()->create();

            ProductSupplierPrice::query()->create([
                'product_id' => $product->id,
                'supplier_id' => $supplier->id,
                'supplier_sku' => 'SUP-' . $product->sku,
                'cost_price' => 60,
                'currency_code' => 'USD',
                'minimum_order_qty' => 1,
                'effective_from' => now()->subDay(),
                'effective_to' => null,
                'is_primary' => true,
            ]);

            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'description' => $product->name,
                'quantity' => 1,
                'unit_cost_at_sale' => 60,
                'unit_price_at_sale' => 100,
                'discount_amount' => 0,
                'tax_amount' => 0,
                'line_total' => 100,
            ]);
        });
    }
}