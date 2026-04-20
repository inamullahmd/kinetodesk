<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ServiceTicket>
 */
class ServiceTicketFactory extends Factory
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
            'ticket_number' => 'TKT-' . $this->faker->unique()->numerify('######'),
            'status' => $this->faker->randomElement([
                'received',
                'diagnosing',
                'waiting_approval',
                'in_progress',
                'completed',
                'delivered',
            ]),
            'device_brand' => $this->faker->word(),
            'device_model' => $this->faker->word(),
            'device_serial_number' => $this->faker->uuid(),
            'issue_description' => $this->faker->paragraph(),
            'diagnosis_notes' => $this->faker->optional()->paragraph(),
            'labor_cost' => $this->faker->numberBetween(0, 200),
            'parts_cost' => 0,
            'total_cost' => 0,
            'received_at' => $this->faker->dateTime(),
            'completed_at' => $this->faker->optional()->dateTime(),
            'delivered_at' => $this->faker->optional()->dateTime(),
        ];
    }
}