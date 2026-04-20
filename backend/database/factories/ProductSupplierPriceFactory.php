<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductSupplierPrice;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductSupplierPrice>
 */
class ProductSupplierPriceFactory extends Factory
{
    protected $model = ProductSupplierPrice::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'supplier_id' => Supplier::factory(),
            'supplier_sku' => 'SUP-' . strtoupper($this->faker->bothify('??###??###')),
            'cost_price' => $this->faker->randomFloat(2, 5, 2000),
            'currency_code' => 'USD',
            'minimum_order_qty' => $this->faker->numberBetween(1, 20),
            'effective_from' => now()->subDays($this->faker->numberBetween(1, 30)),
            'effective_to' => null,
            'is_primary' => true,
        ];
    }
}