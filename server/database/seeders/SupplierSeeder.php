<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = [
            ['name' => 'TechSource Distributors', 'email' => 'sales@techsource.com', 'phone' => '+1-918-555-1001', 'city' => 'Tulsa', 'country' => 'USA'],
            ['name' => 'Nova Hardware Supply', 'email' => 'contact@novahardware.com', 'phone' => '+1-214-555-1002', 'city' => 'Dallas', 'country' => 'USA'],
            ['name' => 'Prime Circuit Wholesale', 'email' => 'orders@primecircuit.com', 'phone' => '+1-713-555-1003', 'city' => 'Houston', 'country' => 'USA'],
            ['name' => 'Vertex Computing Imports', 'email' => 'support@vertexcomputing.com', 'phone' => '+1-602-555-1004', 'city' => 'Phoenix', 'country' => 'USA'],
            ['name' => 'BlueGrid Electronics', 'email' => 'sales@bluegrid.com', 'phone' => '+1-312-555-1005', 'city' => 'Chicago', 'country' => 'USA'],
            ['name' => 'Axis Peripheral Partners', 'email' => 'hello@axisperipherals.com', 'phone' => '+1-404-555-1006', 'city' => 'Atlanta', 'country' => 'USA'],
            ['name' => 'Summit Digital Supply', 'email' => 'info@summitdigital.com', 'phone' => '+1-303-555-1007', 'city' => 'Denver', 'country' => 'USA'],
            ['name' => 'CoreLink Systems', 'email' => 'sales@corelinksystems.com', 'phone' => '+1-206-555-1008', 'city' => 'Seattle', 'country' => 'USA'],
            ['name' => 'Nexa Device Wholesale', 'email' => 'orders@nexadevice.com', 'phone' => '+1-305-555-1009', 'city' => 'Miami', 'country' => 'USA'],
            ['name' => 'Titan IT Distribution', 'email' => 'sales@titanitdist.com', 'phone' => '+1-704-555-1010', 'city' => 'Charlotte', 'country' => 'USA'],
            ['name' => 'SignalByte Components', 'email' => 'contact@signalbyte.com', 'phone' => '+1-512-555-1011', 'city' => 'Austin', 'country' => 'USA'],
            ['name' => 'Harbor Tech Supply', 'email' => 'support@harbortech.com', 'phone' => '+1-619-555-1012', 'city' => 'San Diego', 'country' => 'USA'],
            ['name' => 'Quantum Peripheral House', 'email' => 'orders@quantumperipheral.com', 'phone' => '+1-702-555-1013', 'city' => 'Las Vegas', 'country' => 'USA'],
            ['name' => 'Pinnacle Systems Trade', 'email' => 'sales@pinnaclesystems.com', 'phone' => '+1-816-555-1014', 'city' => 'Kansas City', 'country' => 'USA'],
            ['name' => 'Metro Circuit Supply', 'email' => 'contact@metrocircuit.com', 'phone' => '+1-901-555-1015', 'city' => 'Memphis', 'country' => 'USA'],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::updateOrCreate(
                ['email' => $supplier['email']],
                $supplier
            );
        }
    }
}