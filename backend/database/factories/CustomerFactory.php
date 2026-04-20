<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = $this->faker->randomElement(['b2b', 'retail']);

        if ($type === 'b2b') {
            return [
                'customer_type' => $type,
                'business_name' => $this->faker->company(),
                'email' => $this->faker->unique()->companyEmail(),
                'phone' => $this->faker->phoneNumber(),
                'billing_address' => $this->faker->address(),
                'credit_limit' => $this->faker->numberBetween(1000, 50000),
                'payment_terms_days' => $this->faker->randomElement([30, 60, 90]),
                'is_active' => true,
            ];
        }

        return [
            'customer_type' => $type,
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->unique()->email(),
            'phone' => $this->faker->phoneNumber(),
            'billing_address' => $this->faker->address(),
            'is_active' => true,
        ];
    }
}
