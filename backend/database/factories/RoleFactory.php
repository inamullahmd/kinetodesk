<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Role>
 */
class RoleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->randomElement(['admin', 'manager', 'sales', 'technician', 'support']);
        return [
            'code' => strtoupper($name) . '-' . $this->faker->unique()->numberBetween(1, 999),
            'name' => $name,
        ];
    }
}
