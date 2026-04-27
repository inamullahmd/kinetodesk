<?php

namespace Database\Seeders;

use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Database\Seeders\Concerns\SeedsTimelineWindow;
use Illuminate\Database\Seeder;

class PurchaseOrdersSeeder extends Seeder
{
    use SeedsTimelineWindow;

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
            'USA' => random_int(16, 28),
            'Canada', 'United Kingdom', 'Germany', 'Australia' => random_int(10, 20),
            default => random_int(7, 16),
        };
    }

    private function generateTimeline(): array
    {
        $seedEnd = $this->seedWindowEnd();
        $roll = random_int(1, 1000);

        if ($roll <= 880) {
            $status = 'fulfilled';
        } elseif ($roll <= 940) {
            $status = 'transit';
        } elseif ($roll <= 980) {
            $status = 'issued';
        } else {
            $status = 'cancelled';
        }

        switch ($status) {
            case 'fulfilled':
                $orderedAt = $this->pickWeightedDate(12)->startOfDay();
                $expectedAt = $this->clampToSeedWindow($orderedAt->copy()->addDays(random_int(5, 24)))->endOfDay();
                $receivedAt = $this->clampToSeedWindow(
                    $expectedAt->copy()->addDays(random_int(-1, 4))
                )->endOfDay();
                if ($receivedAt->lt($orderedAt)) {
                    $receivedAt = $orderedAt->copy()->addDays(random_int(2, 8))->endOfDay();
                }
                break;

            case 'transit':
                $orderedAt = $seedEnd->copy()->subDays(random_int(8, 20))->startOfDay();
                $expectedAt = $this->clampToSeedWindow($orderedAt->copy()->addDays(random_int(5, 12)))->endOfDay();
                $receivedAt = null;
                break;

            case 'issued':
                $orderedAt = $seedEnd->copy()->subDays(random_int(0, 8))->startOfDay();
                $expectedAt = $this->clampToSeedWindow($orderedAt->copy()->addDays(random_int(4, 10)))->endOfDay();
                $receivedAt = null;
                break;

            case 'cancelled':
                $orderedAt = $this->pickWeightedDate(15)->startOfDay();
                $expectedAt = $this->clampToSeedWindow($orderedAt->copy()->addDays(random_int(4, 18)))->endOfDay();
                $receivedAt = null;
                break;

            default:
                $orderedAt = $this->pickWeightedDate()->startOfDay();
                $expectedAt = $this->clampToSeedWindow($orderedAt->copy()->addDays(random_int(5, 20)))->endOfDay();
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
