<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\SalesOrder;
use Carbon\Carbon;

class SalesOrdersSeeder extends Seeder
{
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
                    'online' => rand(1, 100) <= 15 ? optional($employees->random())->id : null,
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
                    'shipping_fee' => $channel === 'in_store' ? 0 : rand(0, 60),
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
            'business' => rand(12, 36),
            'individual' => rand(1, 8),
            default => rand(1, 5),
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
        $today = Carbon::today();
        $roll = rand(1, 100);

        if ($roll <= 12) {
            $status = 'pending';
        } elseif ($roll <= 24) {
            $status = 'confirmed';
        } elseif ($roll <= 35 && $channel !== 'in_store') {
            $status = 'shipped';
        } elseif ($roll <= 84) {
            $status = 'delivered';
        } elseif ($roll <= 91) {
            $status = 'cancelled';
        } elseif ($roll <= 96) {
            $status = 'returned';
        } else {
            $status = 'refunded';
        }

        switch ($status) {
            case 'pending':
                $orderedAt = rand(1, 100) <= 70
                    ? Carbon::today()->subDays(rand(0, 5))
                    : Carbon::today()->addDays(rand(0, 7));
                $completedAt = null;
                $paymentStatus = rand(1, 100) <= 78 ? 'unpaid' : 'paid';
                break;

            case 'confirmed':
                $orderedAt = Carbon::today()->subDays(rand(0, 10));
                $completedAt = null;
                $paymentStatus = rand(1, 100) <= 80 ? 'paid' : 'unpaid';
                break;

            case 'shipped':
                $orderedAt = Carbon::today()->subDays(rand(1, 12));
                $completedAt = null;
                $paymentStatus = 'paid';
                break;

            case 'delivered':
                $orderedAt = Carbon::today()->subDays(rand(5, 1095));
                $completedAt = (clone $orderedAt)->addDays($channel === 'in_store' ? rand(0, 1) : rand(1, 12));
                $paymentStatus = 'paid';
                break;

            case 'cancelled':
                $orderedAt = Carbon::today()->subDays(rand(1, 800));
                $completedAt = null;
                $paymentStatus = rand(1, 100) <= 18 ? 'paid' : 'unpaid';
                break;

            case 'returned':
                $orderedAt = Carbon::today()->subDays(rand(20, 730));
                $completedAt = (clone $orderedAt)->addDays(rand(2, 15));
                $paymentStatus = 'paid';
                break;

            case 'refunded':
                $orderedAt = Carbon::today()->subDays(rand(20, 730));
                $completedAt = (clone $orderedAt)->addDays(rand(2, 20));
                $paymentStatus = 'refunded';
                break;

            default:
                $orderedAt = Carbon::today()->subDays(rand(1, 365));
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
