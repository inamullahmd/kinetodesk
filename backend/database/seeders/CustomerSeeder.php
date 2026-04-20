<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $b2bNames = [
            'Tulsa Regional Clinic',
            'Midwest Legal Group',
            'Red River Logistics',
            'Summit Engineering',
            'Greenfield Academy',
            'Metro Print Solutions',
            'Oakline Dental',
            'Prairie Energy Services',
            'Northstar Realty',
            'BluePeak Manufacturing',
            'Frontier Medical Imaging',
            'Cornerstone Accounting',
            'Riverbend Construction',
            'Southgate Church Office',
            'Arrow Industrial Supply',
            'Sunrise Pediatrics',
            'Clearpoint Insurance',
            'Keystone Architecture',
            'Urban Thread Printing',
            'Tulsa Transit Support'
        ];

        foreach ($b2bNames as $i => $name) {
            Customer::updateOrCreate(
                ['business_name' => $name],
                [
                    'customer_type' => 'b2b',
                    'business_name' => $name,
                    'first_name' => null,
                    'last_name' => null,
                    'email' => 'ap' . ($i + 1) . '@b2b.local',
                    'phone' => '918-700-' . str_pad((string)(1000 + $i), 4, '0', STR_PAD_LEFT),
                    'tax_number' => 'TAX-' . strtoupper(substr(md5($name), 0, 8)),
                    'billing_address' => fake()->streetAddress() . ', Tulsa, OK',
                    'shipping_address' => fake()->streetAddress() . ', Tulsa, OK',
                    'credit_limit' => rand(10000, 100000),
                    'current_balance' => 0,
                    'payment_terms_days' => [15, 30, 30, 30, 45][array_rand([15, 30, 30, 30, 45])],
                    'is_active' => true,
                ]
            );
        }

        $retailCount = 2400;

        for ($i = 1; $i <= $retailCount; $i++) {
            Customer::updateOrCreate(
                ['email' => "customer{$i}@retail.local"],
                [
                    'customer_type' => 'retail',
                    'business_name' => null,
                    'first_name' => fake()->firstName(),
                    'last_name' => fake()->lastName(),
                    'email' => "customer{$i}@retail.local",
                    'phone' => fake()->numerify('918-###-####'),
                    'tax_number' => null,
                    'billing_address' => fake()->streetAddress() . ', ' . fake()->city() . ', OK',
                    'shipping_address' => fake()->streetAddress() . ', ' . fake()->city() . ', OK',
                    'credit_limit' => 0,
                    'current_balance' => 0,
                    'payment_terms_days' => 0,
                    'is_active' => true,
                ]
            );
        }
    }
}