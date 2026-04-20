<?php

namespace Database\Factories;

use App\Models\BuildTemplate;
use App\Models\Customer;
use App\Models\CustomBuildOrder;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomBuildOrder>
 */
class CustomBuildOrderFactory extends Factory
{
    protected $model = CustomBuildOrder::class;

    public function definition(): array
    {
        $laborCharge = (float) $this->faker->randomFloat(2, 0, 300);

        return [
            'order_id' => Order::factory()->state([
                'customer_id' => Customer::factory(),
                'order_type' => 'custom_build',
                'channel' => $this->faker->randomElement(['in_store', 'online', 'phone', 'walk_in']),
                'status' => 'confirmed',
                'subtotal' => 0,
                'discount_amount' => 0,
                'tax_amount' => 0,
                'total_amount' => $laborCharge,
                'amount_paid' => 0,
            ]),
            'build_template_id' => BuildTemplate::factory(),
            'build_status' => $this->faker->randomElement([
                'draft',
                'stock_check_pending',
                'ready',
                'assembling',
                'completed',
                'delivered',
                'cancelled',
            ]),
            'assembly_notes' => $this->faker->optional()->sentence(),
            'labor_charge' => $laborCharge,
            'completed_at' => null,
        ];
    }
}