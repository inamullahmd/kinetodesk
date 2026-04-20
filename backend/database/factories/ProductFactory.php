<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sku' => 'SKU-' . $this->faker->unique()->numerify('######'),
            'name' => $this->faker->words(3, true),
            'category_id' => Category::factory(),
            'product_type' => $this->faker->randomElement(['inventory', 'service', 'custom_component', 'bundle']),
            'is_serialized' => $this->faker->boolean(20),
            'unit_of_measure' => 'pcs',
            'reorder_threshold' => $this->faker->numberBetween(5, 50),
            'current_stock' => $this->faker->numberBetween(10, 500),
            'reserved_stock' => 0,
            'sell_price' => $this->faker->numberBetween(50, 500),
            'is_active' => true,
            'description' => $this->faker->optional()->paragraph(),
        ];
    }
}
