<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ReturnRequest>
 */
class ReturnRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'return_number' => 'RET-' . $this->faker->unique()->numerify('######'),
            'order_id' => \App\Models\Order::factory(),
            'reason' => $this->faker->sentence(),
            'status' => $this->faker->randomElement(['initiated', 'approved', 'rejected', 'refunded', 'exchanged']),
            'refund_amount' => $this->faker->numberBetween(10, 1000),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
