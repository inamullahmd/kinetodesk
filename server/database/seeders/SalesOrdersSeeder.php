<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Employee;
use App\Models\SalesOrder;
use Carbon\Carbon;
use Database\Seeders\Concerns\SeedsTimelineWindow;
use Illuminate\Database\Seeder;

class SalesOrdersSeeder extends Seeder
{
    use SeedsTimelineWindow;

    public function run(): void
    {
        $customers = Customer::where('is_active', true)->get();
        $employees = Employee::whereIn('role', ['owner', 'manager', 'sales_representative', 'cashier'])->get();

        if ($customers->isEmpty()) {
            return;
        }

        $counter = 1;

        foreach ($customers as $customer) {
            $orderCount = $this->salesOrderCountForCustomer($customer->customer_type);

            for ($i = 0; $i < $orderCount; $i++) {
                $channel = $this->channelForCustomerType($customer->customer_type);
                [$orderedAt, $completedAt, $status, $paymentStatus] = $this->generateTimelineAndStatus($channel);

                $employeeId = match ($channel) {
                    'online' => random_int(1, 100) <= 18 ? optional($employees->random())->id : null,
                    'phone', 'in_store' => optional($employees->random())->id,
                    default => null,
                };

                SalesOrder::create([
                    'customer_id' => $customer->id,
                    'employee_id' => $employeeId,
                    'so_number' => 'SO-' . str_pad((string) $counter, 8, '0', STR_PAD_LEFT),
                    'channel' => $channel,
                    'status' => $status,
                    'payment_status' => $paymentStatus,
                    'subtotal' => 0,
                    'discount_amount' => 0,
                    'tax_amount' => 0,
                    'shipping_fee' => $channel === 'in_store' ? 0 : random_int(0, 60),
                    'grand_total' => 0,
                    'contact_name' => $customer->customer_type === 'business'
                        ? ($customer->business_name ?? 'Business Customer')
                        : trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? '')),
                    'contact_phone' => $customer->phone,
                    'delivery_address_line_1' => $customer->address_line_1,
                    'delivery_address_line_2' => $customer->address_line_2,
                    'delivery_city' => $customer->city,
                    'delivery_state' => $customer->state,
                    'delivery_postal_code' => $customer->postal_code,
                    'delivery_country' => $customer->country,
                    'notes' => $this->noteForOrder($channel, $status),
                    'ordered_at' => $orderedAt,
                    'completed_at' => $completedAt,
                ]);

                $counter++;
            }
        }
    }

    private function salesOrderCountForCustomer(string $customerType): int
    {
        return match ($customerType) {
            'business' => random_int(12, 30),
            'individual' => random_int(2, 7),
            default => random_int(1, 5),
        };
    }

    private function channelForCustomerType(string $type): string
    {
        if ($type === 'business') {
            $weighted = ['phone', 'phone', 'online', 'online', 'in_store'];
            return $weighted[array_rand($weighted)];
        }

        $weighted = ['in_store', 'in_store', 'online', 'phone'];
        return $weighted[array_rand($weighted)];
    }

    private function generateTimelineAndStatus(string $channel): array
    {
        $seedEnd = $this->seedWindowEnd();
        $roll = random_int(1, 1000);

        if ($roll <= 20) {
            $status = 'pending';
        } elseif ($roll <= 45) {
            $status = 'confirmed';
        } elseif ($roll <= 80 && $channel !== 'in_store') {
            $status = 'shipped';
        } elseif ($roll <= 940) {
            $status = 'delivered';
        } elseif ($roll <= 975) {
            $status = 'cancelled';
        } elseif ($roll <= 993) {
            $status = 'returned';
        } else {
            $status = 'refunded';
        }

        switch ($status) {
            case 'pending':
                $orderedAt = $seedEnd->copy()->subDays(random_int(0, 4))->setTime(random_int(9, 18), random_int(0, 59));
                $completedAt = null;
                $paymentStatus = random_int(1, 100) <= 90 ? 'unpaid' : 'paid';
                break;

            case 'confirmed':
                $orderedAt = $seedEnd->copy()->subDays(random_int(1, 7))->setTime(random_int(9, 18), random_int(0, 59));
                $completedAt = null;
                $paymentStatus = random_int(1, 100) <= 82 ? 'paid' : 'unpaid';
                break;

            case 'shipped':
                $orderedAt = $seedEnd->copy()->subDays(random_int(2, 10))->setTime(random_int(9, 18), random_int(0, 59));
                $completedAt = null;
                $paymentStatus = 'paid';
                break;

            case 'delivered':
                $orderedAt = $this->pickWeightedDate(3);
                $completedAt = $this->clampToSeedWindow(
                    $orderedAt->copy()->addDays($channel === 'in_store' ? random_int(0, 1) : random_int(1, 10))
                );
                $paymentStatus = 'paid';
                break;

            case 'cancelled':
                $orderedAt = $this->pickWeightedDate(10);
                $completedAt = null;
                $paymentStatus = random_int(1, 100) <= 12 ? 'paid' : 'unpaid';
                break;

            case 'returned':
                $orderedAt = $this->pickWeightedDate(20);
                $completedAt = $this->clampToSeedWindow($orderedAt->copy()->addDays(random_int(2, 12)));
                $paymentStatus = 'paid';
                break;

            case 'refunded':
                $orderedAt = $this->pickWeightedDate(25);
                $completedAt = $this->clampToSeedWindow($orderedAt->copy()->addDays(random_int(3, 14)));
                $paymentStatus = 'refunded';
                break;

            default:
                $orderedAt = $this->pickWeightedDate();
                $completedAt = null;
                $paymentStatus = 'unpaid';
                break;
        }

        return [$orderedAt, $completedAt, $status, $paymentStatus];
    }

    private function noteForOrder(string $channel, string $status): string
    {
        return match ($status) {
            'pending' => 'Order placed and awaiting processing.',
            'confirmed' => 'Order confirmed and being prepared.',
            'shipped' => 'Order dispatched and currently in transit.',
            'delivered' => $channel === 'in_store'
                ? 'Walk-in sale completed in store.'
                : 'Order delivered successfully.',
            'cancelled' => 'Order cancelled before completion.',
            'returned' => 'Customer returned part or all of the order.',
            'refunded' => 'Order refunded after return or cancellation.',
            default => 'Seeded sales order.',
        };
    }
}
