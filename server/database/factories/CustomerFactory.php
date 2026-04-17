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
            'state' => 'Oklahoma',
            'country' => 'USA',
        ];
    }

    public function norman(): static
    {
        return $this->state(fn () => [
            'city' => 'Norman',
            'state' => 'Oklahoma',
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
            'state' => 'Oklahoma',
            'country' => 'USA',
        ]);
    }

    public function outsideOklahoma(): static
    {
        $locations = [
            ['city' => 'Dallas', 'state' => 'Texas'],
            ['city' => 'Fort Worth', 'state' => 'Texas'],
            ['city' => 'Wichita', 'state' => 'Kansas'],
            ['city' => 'Fayetteville', 'state' => 'Arkansas'],
            ['city' => 'Kansas City', 'state' => 'Missouri'],
            ['city' => 'Amarillo', 'state' => 'Texas'],
            ['city' => 'Little Rock', 'state' => 'Arkansas'],
            ['city' => 'Bentonville', 'state' => 'Arkansas'],
        ];

        return $this->state(function () use ($locations) {
            $location = fake()->randomElement($locations);

            return [
                'city' => $location['city'],
                'state' => $location['state'],
                'country' => 'USA',
            ];
        });
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