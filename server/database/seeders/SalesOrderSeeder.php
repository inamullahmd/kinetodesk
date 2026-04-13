<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Product;
use App\Models\SalesOrder;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class SalesOrderSeeder extends Seeder
{
    public function run(): void
    {
        $products = Product::with('category')->get();
        $customers = Customer::all();

        if ($products->isEmpty() || $customers->isEmpty()) {
            return;
        }

        $statuses = [
            'completed' => 86,
            'pending' => 7,
            'refunded' => 4,
            'cancelled' => 3,
        ];

        $startDate = Carbon::now()->subMonths(9)->startOfDay();
        $endDate = Carbon::now()->endOfDay();

        $sequence = 500001;

        for ($i = 0; $i < 8000; $i++) {
            $customer = $this->pickWeightedCustomer($customers->all());
            $product = $this->pickWeightedProduct($products->all());
            $status = $this->weightedPick($statuses);

            $quantity = $this->quantityForCustomerAndCategory(
                $customer->customer_type,
                $product->category->name
            );

            $soldAt = $this->randomSalesDate($startDate, $endDate);

            $unitPrice = $this->jitterSellPrice((float) $product->selling_price);
            $totalAmount = round($unitPrice * $quantity, 2);

            SalesOrder::create([
                'order_number' => 'SO-' . $sequence++,
                'customer_id' => $customer->id,
                'product_id' => $product->id,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_amount' => $totalAmount,
                'status' => $status,
                'sold_at' => $soldAt,
                'created_at' => $soldAt,
                'updated_at' => $soldAt,
            ]);
        }
    }

    private function pickWeightedCustomer(array $customers): Customer
    {
        $weightedPool = [];

        foreach ($customers as $customer) {
            $weight = match (true) {
                $customer->customer_type === 'retail' && $customer->city === 'Norman' => 12,
                $customer->customer_type === 'retail' => 7,
                $customer->customer_type === 'business' && $customer->city === 'Norman' => 6,
                $customer->customer_type === 'business' => 4,
                default => 5,
            };

            for ($i = 0; $i < $weight; $i++) {
                $weightedPool[] = $customer;
            }
        }

        return fake()->randomElement($weightedPool);
    }

    private function pickWeightedProduct(array $products): Product
    {
        $weightedPool = [];

        foreach ($products as $product) {
            $weight = match ($product->category->name) {
                'Peripherals', 'Accessories' => 12,
                'Components', 'Storage' => 9,
                'Networking', 'Monitors' => 6,
                'Laptops', 'Desktops' => 3,
                default => 5,
            };

            for ($i = 0; $i < $weight; $i++) {
                $weightedPool[] = $product;
            }
        }

        return fake()->randomElement($weightedPool);
    }

    private function quantityForCustomerAndCategory(string $customerType, string $category): int
    {
        if ($customerType === 'business') {
            return match ($category) {
                'Laptops' => fake()->numberBetween(1, 4),
                'Desktops' => fake()->numberBetween(1, 3),
                'Monitors' => fake()->numberBetween(2, 8),
                'Components' => fake()->numberBetween(2, 12),
                'Storage' => fake()->numberBetween(2, 10),
                'Networking' => fake()->numberBetween(1, 6),
                'Peripherals' => fake()->numberBetween(2, 15),
                'Accessories' => fake()->numberBetween(3, 20),
                default => fake()->numberBetween(1, 5),
            };
        }

        return match ($category) {
            'Laptops' => fake()->numberBetween(1, 2),
            'Desktops' => 1,
            'Monitors' => fake()->numberBetween(1, 2),
            'Components' => fake()->numberBetween(1, 3),
            'Storage' => fake()->numberBetween(1, 3),
            'Networking' => fake()->numberBetween(1, 2),
            'Peripherals' => fake()->numberBetween(1, 4),
            'Accessories' => fake()->numberBetween(1, 5),
            default => 1,
        };
    }

    private function weightedPick(array $weights): string
    {
        $roll = fake()->numberBetween(1, array_sum($weights));
        $running = 0;

        foreach ($weights as $value => $weight) {
            $running += $weight;

            if ($roll <= $running) {
                return $value;
            }
        }

        return array_key_first($weights);
    }

    private function randomSalesDate(Carbon $start, Carbon $end): Carbon
    {
        $date = Carbon::createFromTimestamp(
            fake()->numberBetween($start->timestamp, $end->timestamp)
        );

        $dayWeight = $date->isWeekend() ? 1 : 4;

        if (fake()->numberBetween(1, 4) > $dayWeight) {
            $date = Carbon::createFromTimestamp(
                fake()->numberBetween($start->timestamp, $end->timestamp)
            );
        }

        return $date
            ->setHour(fake()->numberBetween(10, 19))
            ->setMinute(fake()->randomElement([0, 5, 10, 15, 20, 30, 35, 40, 45, 50, 55]))
            ->setSecond(fake()->numberBetween(0, 59));
    }

    private function jitterSellPrice(float $basePrice): float
    {
        $factor = fake()->randomFloat(4, 0.98, 1.03);
        return round($basePrice * $factor, 2);
    }
}