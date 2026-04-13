<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\PurchaseOrder;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PurchaseOrderSeeder extends Seeder
{
    public function run(): void
    {
        $products = Product::with('category', 'supplier')->get();

        if ($products->isEmpty()) {
            return;
        }

        $statuses = [
            'received' => 82,
            'pending' => 12,
            'cancelled' => 6,
        ];

        $startDate = Carbon::now()->subMonths(9)->startOfDay();
        $endDate = Carbon::now()->endOfDay();

        $sequence = 100001;

        for ($i = 0; $i < 1500; $i++) {
            $product = $this->pickWeightedProduct($products->all());
            $category = $product->category->name;

            $quantity = $this->quantityForCategory($category);
            $status = $this->weightedPick($statuses);
            $purchasedAt = $this->randomBusinessDate($startDate, $endDate);

            $unitCost = $this->jitterCost((float) $product->cost_price);
            $totalCost = round($unitCost * $quantity, 2);

            PurchaseOrder::create([
                'po_number' => 'PO-' . $sequence++,
                'supplier_id' => $product->supplier_id,
                'product_id' => $product->id,
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'total_cost' => $totalCost,
                'status' => $status,
                'purchased_at' => $purchasedAt,
                'created_at' => $purchasedAt,
                'updated_at' => $purchasedAt,
            ]);
        }
    }

    private function pickWeightedProduct(array $products): Product
    {
        $weightedPool = [];

        foreach ($products as $product) {
            $weight = match ($product->category->name) {
                'Peripherals', 'Accessories' => 10,
                'Components', 'Storage' => 8,
                'Networking', 'Monitors' => 6,
                'Laptops', 'Desktops' => 4,
                default => 5,
            };

            for ($i = 0; $i < $weight; $i++) {
                $weightedPool[] = $product;
            }
        }

        return fake()->randomElement($weightedPool);
    }

    private function quantityForCategory(string $category): int
    {
        return match ($category) {
            'Laptops' => fake()->numberBetween(2, 10),
            'Desktops' => fake()->numberBetween(2, 8),
            'Monitors' => fake()->numberBetween(4, 20),
            'Components' => fake()->numberBetween(10, 60),
            'Storage' => fake()->numberBetween(12, 70),
            'Networking' => fake()->numberBetween(6, 30),
            'Peripherals' => fake()->numberBetween(20, 120),
            'Accessories' => fake()->numberBetween(25, 140),
            default => fake()->numberBetween(5, 20),
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

    private function randomBusinessDate(Carbon $start, Carbon $end): Carbon
    {
        $date = Carbon::createFromTimestamp(
            fake()->numberBetween($start->timestamp, $end->timestamp)
        );

        while ($date->isWeekend()) {
            $date = Carbon::createFromTimestamp(
                fake()->numberBetween($start->timestamp, $end->timestamp)
            );
        }

        return $date
            ->setHour(fake()->numberBetween(9, 17))
            ->setMinute(fake()->randomElement([0, 10, 15, 20, 30, 40, 45, 50]))
            ->setSecond(fake()->numberBetween(0, 59));
    }

    private function jitterCost(float $baseCost): float
    {
        $factor = fake()->randomFloat(4, 0.96, 1.04);
        return round($baseCost * $factor, 2);
    }
}