<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PurchaseOrder>
 */
class PurchaseOrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'supplier_id' => Supplier::factory(),
            'po_number' => 'PO-' . $this->faker->unique()->numerify('######'),
            'status' => $this->faker->randomElement(['draft', 'sent', 'partially_received', 'received', 'cancelled']),
            'ordered_by_employee_id' => Employee::factory(),
            'order_date' => $this->faker->dateTime(),
            'expected_date' => $this->faker->dateTimeBetween('now', '+30 days'),
            'received_at' => $this->faker->optional()->dateTime(),
            'subtotal' => $this->faker->numberBetween(100, 5000),
            'tax_amount' => $this->faker->numberBetween(5, 500),
            'shipping_amount' => $this->faker->numberBetween(5, 100),
            'total_amount' => $this->faker->numberBetween(100, 5000),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
