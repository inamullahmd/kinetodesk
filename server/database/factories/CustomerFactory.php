<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Customer>
 */
class CustomerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('+1-###-###-####'),
            'customer_type' => 'retail',
            'city' => 'Norman',
            'country' => 'USA',
        ];
    }

    public function norman(): static
    {
        return $this->state(fn () => [
            'city' => 'Norman',
            'country' => 'USA',
        ]);
    }

    public function oklahoma(): static
    {
        $cities = [
            'Oklahoma City',
            'Tulsa',
            'Edmond',
            'Moore',
            'Broken Arrow',
            'Lawton',
            'Stillwater',
            'Yukon',
            'Shawnee',
            'Midwest City',
            'Mustang',
            'Enid',
        ];

        return $this->state(fn () => [
            'city' => fake()->randomElement($cities),
            'country' => 'USA',
        ]);
    }

    public function outsideOklahoma(): static
    {
        $cities = [
            'Dallas',
            'Fort Worth',
            'Wichita',
            'Fayetteville',
            'Kansas City',
            'Amarillo',
            'Little Rock',
            'Bentonville',
        ];

        return $this->state(fn () => [
            'city' => fake()->randomElement($cities),
            'country' => 'USA',
        ]);
    }

    public function business(): static
    {
        return $this->state(fn () => [
            'customer_type' => 'business',
        ]);
    }

    public function retail(): static
    {
        return $this->state(fn () => [
            'customer_type' => 'retail',
        ]);
    }
}