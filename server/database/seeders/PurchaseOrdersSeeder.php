<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Supplier;
use App\Models\PurchaseOrder;
use Carbon\Carbon;

class PurchaseOrdersSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = Supplier::where('is_active', true)->get();

        if ($suppliers->isEmpty()) {
            return;
        }

        $counter = 1;

        foreach ($suppliers as $supplier) {
            $poCount = $this->purchaseOrderCountForSupplier($supplier->country);

            for ($i = 0; $i < $poCount; $i++) {
                [$orderedAt, $expectedAt, $receivedAt, $status] = $this->generateTimeline();

                PurchaseOrder::updateOrCreate(
                    ['po_number' => 'PO-' . str_pad((string) $counter, 7, '0', STR_PAD_LEFT)],
                    [
                        'supplier_id' => $supplier->id,
                        'po_number' => 'PO-' . str_pad((string) $counter, 7, '0', STR_PAD_LEFT),
                        'status' => $status,
                        'ordered_at' => $orderedAt,
                        'expected_at' => $expectedAt,
                        'received_at' => $receivedAt,
                        'subtotal' => 0,
                        'tax_amount' => 0,
                        'shipping_cost' => 0,
                        'other_cost' => 0,
                        'total_cost' => 0,
                        'notes' => $this->noteForStatus($status),
                    ]
                );

                $counter++;
            }
        }
    }

    private function purchaseOrderCountForSupplier(?string $country): int
    {
        return match ($country) {
            'USA' => rand(18, 34),
            'Canada', 'United Kingdom', 'Germany', 'Australia' => rand(10, 22),
            default => rand(7, 18),
        };
    }

    private function generateTimeline(): array
    {
        $today = Carbon::today();
        $roll = rand(1, 100);

        if ($roll <= 56) {
            $status = 'fulfilled';
        } elseif ($roll <= 74) {
            $status = 'transit';
        } elseif ($roll <= 92) {
            $status = 'issued';
        } else {
            $status = 'cancelled';
        }

        switch ($status) {
            case 'fulfilled':
                $orderedAt = Carbon::today()->subDays(rand(20, 1095));
                $expectedAt = (clone $orderedAt)->addDays(rand(5, 32));
                $receivedAt = (clone $expectedAt)->addDays(rand(-1, 6));
                if ($receivedAt->gt($today)) {
                    $receivedAt = (clone $today)->subDays(rand(1, 3));
                }
                break;

            case 'transit':
                $orderedAt = Carbon::today()->subDays(rand(1, 45));
                $expectedAt = (clone $orderedAt)->addDays(rand(5, 28));
                if ($expectedAt->lte($today)) {
                    $expectedAt = (clone $today)->addDays(rand(1, 12));
                }
                $receivedAt = null;
                break;

            case 'issued':
                if (rand(1, 100) <= 30) {
                    $orderedAt = Carbon::today()->addDays(rand(0, 14));
                } else {
                    $orderedAt = Carbon::today()->subDays(rand(0, 21));
                }
                $expectedAt = (clone $orderedAt)->addDays(rand(7, 35));
                if ($expectedAt->lte($today)) {
                    $expectedAt = (clone $today)->addDays(rand(3, 18));
                }
                $receivedAt = null;
                break;

            case 'cancelled':
                $orderedAt = Carbon::today()->subDays(rand(3, 900));
                $expectedAt = (clone $orderedAt)->addDays(rand(5, 24));
                $receivedAt = null;
                break;

            default:
                $orderedAt = Carbon::today()->subDays(rand(5, 100));
                $expectedAt = (clone $orderedAt)->addDays(rand(5, 20));
                $receivedAt = null;
                break;
        }

        return [$orderedAt, $expectedAt, $receivedAt, $status];
    }

    private function noteForStatus(string $status): string
    {
        return match ($status) {
            'fulfilled' => 'Supplier shipment received and fully stocked.',
            'transit' => 'Supplier shipment dispatched and currently in transit.',
            'issued' => 'Purchase order issued and awaiting supplier fulfillment.',
            'cancelled' => 'Purchase order cancelled before receipt.',
            default => 'Seeded purchase order.',
        };
    }
}
