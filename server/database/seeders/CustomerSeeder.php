<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $businessCustomers = [
            ['name' => 'Sooner Office Solutions', 'email' => 'procurement@sooneroffice.com', 'phone' => '+1-405-555-2001', 'customer_type' => 'business', 'city' => 'Norman', 'state' => 'Oklahoma', 'country' => 'USA'],
            ['name' => 'Red River Medical Clinic', 'email' => 'admin@redrivermedical.com', 'phone' => '+1-405-555-2002', 'customer_type' => 'business', 'city' => 'Norman', 'state' => 'Oklahoma', 'country' => 'USA'],
            ['name' => 'Campus Print & Copy', 'email' => 'ops@campusprintcopy.com', 'phone' => '+1-405-555-2003', 'customer_type' => 'business', 'city' => 'Norman', 'state' => 'Oklahoma', 'country' => 'USA'],
            ['name' => 'Prairie Legal Group', 'email' => 'office@prairielegal.com', 'phone' => '+1-405-555-2004', 'customer_type' => 'business', 'city' => 'Norman', 'state' => 'Oklahoma', 'country' => 'USA'],
            ['name' => 'Frontier Insurance Partners', 'email' => 'it@frontierinsurance.com', 'phone' => '+1-405-555-2005', 'customer_type' => 'business', 'city' => 'Norman', 'state' => 'Oklahoma', 'country' => 'USA'],
            ['name' => 'Oakline Dental Center', 'email' => 'admin@oaklinedental.com', 'phone' => '+1-405-555-2006', 'customer_type' => 'business', 'city' => 'Norman', 'state' => 'Oklahoma', 'country' => 'USA'],
            ['name' => 'Metro Build Consultants', 'email' => 'support@metrobuildconsultants.com', 'phone' => '+1-405-555-2007', 'customer_type' => 'business', 'city' => 'Norman', 'state' => 'Oklahoma', 'country' => 'USA'],
            ['name' => 'Summit Realty Advisors', 'email' => 'ops@summitrealtyadvisors.com', 'phone' => '+1-405-555-2008', 'customer_type' => 'business', 'city' => 'Norman', 'state' => 'Oklahoma', 'country' => 'USA'],
            ['name' => 'Heritage Accounting Services', 'email' => 'admin@heritageacct.com', 'phone' => '+1-405-555-2009', 'customer_type' => 'business', 'city' => 'Norman', 'state' => 'Oklahoma', 'country' => 'USA'],
            ['name' => 'Riverbend Architecture Studio', 'email' => 'tech@riverbendarch.com', 'phone' => '+1-405-555-2010', 'customer_type' => 'business', 'city' => 'Norman', 'state' => 'Oklahoma', 'country' => 'USA'],

            ['name' => 'Capital Health Partners', 'email' => 'procurement@capitalhealthpartners.com', 'phone' => '+1-405-555-2011', 'customer_type' => 'business', 'city' => 'Oklahoma City', 'state' => 'Oklahoma', 'country' => 'USA'],
            ['name' => 'Northwind Education Group', 'email' => 'support@northwindedu.com', 'phone' => '+1-405-555-2012', 'customer_type' => 'business', 'city' => 'Oklahoma City', 'state' => 'Oklahoma', 'country' => 'USA'],
            ['name' => 'Sooner State Logistics', 'email' => 'ops@soonerstatelogistics.com', 'phone' => '+1-405-555-2013', 'customer_type' => 'business', 'city' => 'Oklahoma City', 'state' => 'Oklahoma', 'country' => 'USA'],
            ['name' => 'Greenfield Property Services', 'email' => 'admin@greenfieldps.com', 'phone' => '+1-918-555-2014', 'customer_type' => 'business', 'city' => 'Tulsa', 'state' => 'Oklahoma', 'country' => 'USA'],
            ['name' => 'Pioneer Manufacturing Co.', 'email' => 'it@pioneermfgco.com', 'phone' => '+1-918-555-2015', 'customer_type' => 'business', 'city' => 'Tulsa', 'state' => 'Oklahoma', 'country' => 'USA'],
            ['name' => 'Central Plains Marketing', 'email' => 'hello@centralplainsmarketing.com', 'phone' => '+1-405-555-2016', 'customer_type' => 'business', 'city' => 'Edmond', 'state' => 'Oklahoma', 'country' => 'USA'],
            ['name' => 'Red Cedar Financial', 'email' => 'office@redcedarfinancial.com', 'phone' => '+1-405-555-2017', 'customer_type' => 'business', 'city' => 'Stillwater', 'state' => 'Oklahoma', 'country' => 'USA'],
            ['name' => 'Blue Horizon Contractors', 'email' => 'ops@bluehorizoncontractors.com', 'phone' => '+1-580-555-2018', 'customer_type' => 'business', 'city' => 'Lawton', 'state' => 'Oklahoma', 'country' => 'USA'],

            ['name' => 'MetroPoint Design Lab', 'email' => 'it@metropointdesign.com', 'phone' => '+1-214-555-2019', 'customer_type' => 'business', 'city' => 'Dallas', 'state' => 'Texas', 'country' => 'USA'],
            ['name' => 'Ozark Media House', 'email' => 'admin@ozarkmediahouse.com', 'phone' => '+1-479-555-2020', 'customer_type' => 'business', 'city' => 'Fayetteville', 'state' => 'Arkansas', 'country' => 'USA'],
        ];

        foreach ($businessCustomers as $customer) {
            Customer::updateOrCreate(
                ['email' => $customer['email']],
                $customer
            );
        }

        // Final target totals:
        // 240 Norman total -> 10 already seeded above, so add 230
        // 120 Rest of Oklahoma total -> 8 already seeded above, so add 112
        // 40 Outside Oklahoma total -> 2 already seeded above, so add 38

        Customer::factory(230)->retail()->norman()->create();
        Customer::factory(112)->retail()->oklahoma()->create();
        Customer::factory(38)->retail()->outsideOklahoma()->create();
    }
}