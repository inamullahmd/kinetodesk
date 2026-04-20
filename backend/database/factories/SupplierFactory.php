<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Supplier>
 */
class SupplierFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'supplier_code' => 'SUP-' . $this->faker->unique()->numerify('######'),
            'name' => $this->faker->company(),
            'contact_person' => $this->faker->name(),
            'email' => $this->faker->companyEmail(),
            'phone' => $this->faker->phoneNumber(),
            'address' => $this->faker->address(),
            'default_lead_time_days' => $this->faker->numberBetween(1, 30),
            'rating' => $this->faker->numberBetween(1, 50) / 10,
            'is_active' => true,
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
