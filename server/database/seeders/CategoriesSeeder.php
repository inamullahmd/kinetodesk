<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        # Let us first insert the main categories one by obne such that we can get their IDs to link the subcategories to them
        $computingSystems = Category::updateOrCreate(
            ['slug' => 'computing-systems'],  # Unique identifier for the category  
            [
                'parent_id' => null,
                'name' => 'Computing Systems',
                'description' => 'Complete computing devices and portable systems',
                'active' => true,
            ]
        );

        $hardwareComponents = Category::updateOrCreate(
            ['slug' => 'hardware-components'],  # Unique identifier for the category  
            [
                'parent_id' => null,
                'name' => 'Hardware Components',
                'description' => 'Individual computer components, storage, cooling, and tools',
                'active' => true,
            ]
        );

        $peripherals = Category::updateOrCreate(
            ['slug' => 'peripherals'],  # Unique identifier for the category  
            [
                'parent_id' => null,
                'name' => 'Peripherals',
                'description' => 'Monitors, input devices, audio, and streaming gear',
                'active' => true,
            ]
        );

        $software = Category::updateOrCreate(
            ['slug' => 'software'],  # Unique identifier for the category  
            [
                'parent_id' => null,
                'name' => 'Software',
                'description' => 'Operating systems, productivity suites, and creative software',
                'active' => true,
            ]
        );

        $networkingConnectivity = Category::updateOrCreate(
            ['slug' => 'networking-connectivity'],  # Unique identifier for the category  
            [
                'parent_id' => null,
                'name' => 'Networking & Connectivity',
                'description' => 'Networking devices, cables, and adapters',
                'active' => true,
            ]
        );

        # Now we create a list of subcategories with their parent category IDs
        $subcategoriesList = [

            # Computing Systems subcategories
            [
                'parent_id' => $computingSystems->id,
                'name' => 'Laptops',
                'slug' => 'laptops',
                'description' => 'Portable computers and notebooks',
                'active' => true,
            ],
            [
                'parent_id' => $computingSystems->id,
                'name' => 'Desktop PCs',
                'slug' => 'desktop-pcs',
                'description' => 'Prebuilt and custom desktop computers',
                'active' => true,
            ],
            [
                'parent_id' => $computingSystems->id,
                'name' => 'Tablets',
                'slug' => 'tablets',
                'description' => 'Tablet computing devices',
                'active' => true,
            ],
            [
                'parent_id' => $computingSystems->id,
                'name' => 'Handhelds',
                'slug' => 'handhelds',
                'description' => 'Compact handheld computing devices',
                'active' => true,
            ],

            # Hardware Components subcategories
            [
                'parent_id' => $hardwareComponents->id,
                'name' => 'Processors (CPU)',
                'slug' => 'processors-cpu',
                'description' => 'Desktop and workstation processors',
                'active' => true,
            ],
            [
                'parent_id' => $hardwareComponents->id,
                'name' => 'Graphics Cards (GPU)',
                'slug' => 'graphics-cards-gpu',
                'description' => 'Dedicated graphics processing units',
                'active' => true,
            ],
            [
                'parent_id' => $hardwareComponents->id,
                'name' => 'Motherboards',
                'slug' => 'motherboards',
                'description' => 'Desktop and workstation motherboards',
                'active' => true,
            ],
            [
                'parent_id' => $hardwareComponents->id,
                'name' => 'Memory (RAM)',
                'slug' => 'memory-ram',
                'description' => 'System memory modules',
                'active' => true,
            ],
            [
                'parent_id' => $hardwareComponents->id,
                'name' => 'Power Supplies (PSU)',
                'slug' => 'power-supplies-psu',
                'description' => 'Power supply units',
                'active' => true,
            ],
            [
                'parent_id' => $hardwareComponents->id,
                'name' => 'Cases & Chassis',
                'slug' => 'cases-chassis',
                'description' => 'Computer cases and chassis',
                'active' => true,
            ],
            [
                'parent_id' => $hardwareComponents->id,
                'name' => 'Internal SSDs',
                'slug' => 'internal-ssds',
                'description' => 'Internal solid-state drives',
                'active' => true,
            ],
            [
                'parent_id' => $hardwareComponents->id,
                'name' => 'Hard Drives (HDD)',
                'slug' => 'hard-drives-hdd',
                'description' => 'Internal and desktop hard drives',
                'active' => true,
            ],
            [
                'parent_id' => $hardwareComponents->id,
                'name' => 'External Storage',
                'slug' => 'external-storage',
                'description' => 'Portable drives and external storage devices',
                'active' => true,
            ],
            [
                'parent_id' => $hardwareComponents->id,
                'name' => 'Memory Cards',
                'slug' => 'memory-cards',
                'description' => 'SD cards, microSD cards, and similar storage media',
                'active' => true,
            ],
            [
                'parent_id' => $hardwareComponents->id,
                'name' => 'CPU Cooling',
                'slug' => 'cpu-cooling',
                'description' => 'Air and liquid CPU cooling solutions',
                'active' => true,
            ],
            [
                'parent_id' => $hardwareComponents->id,
                'name' => 'Case Fans',
                'slug' => 'case-fans',
                'description' => 'Cooling fans for PC cases',
                'active' => true,
            ],
            [
                'parent_id' => $hardwareComponents->id,
                'name' => 'Thermal Solutions',
                'slug' => 'thermal-solutions',
                'description' => 'Thermal paste, pads, and cooling accessories',
                'active' => true,
            ],
            [
                'parent_id' => $hardwareComponents->id,
                'name' => 'Tools & Equipment',
                'slug' => 'tools-equipment',
                'description' => 'Repair and installation tools',
                'active' => true,
            ],

            # Peripherals subcategories
            [
                'parent_id' => $peripherals->id,
                'name' => 'Monitors',
                'slug' => 'monitors',
                'description' => 'Computer displays and monitors',
                'active' => true,
            ],
            [
                'parent_id' => $peripherals->id,
                'name' => 'Keyboards',
                'slug' => 'keyboards',
                'description' => 'Wired and wireless keyboards',
                'active' => true,
            ],
            [
                'parent_id' => $peripherals->id,
                'name' => 'Mice',
                'slug' => 'mice',
                'description' => 'Wired and wireless mice',
                'active' => true,
            ],
            [
                'parent_id' => $peripherals->id,
                'name' => 'Audio',
                'slug' => 'audio',
                'description' => 'Headsets, speakers, and microphones',
                'active' => true,
            ],
            [
                'parent_id' => $peripherals->id,
                'name' => 'Streaming Gear',
                'slug' => 'streaming-gear',
                'description' => 'Webcams, capture devices, and streaming accessories',
                'active' => true,
            ],

            # Networking & Connectivity subcategories
            [
                'parent_id' => $networkingConnectivity->id,
                'name' => 'Wireless Networking',
                'slug' => 'wireless-networking',
                'description' => 'Wi-Fi routers, access points, and wireless adapters',
                'active' => true,
            ],
            [
                'parent_id' => $networkingConnectivity->id,
                'name' => 'Wired Networking',
                'slug' => 'wired-networking',
                'description' => 'Switches, NICs, and wired networking equipment',
                'active' => true,
            ],
            [
                'parent_id' => $networkingConnectivity->id,
                'name' => 'Cables & Adapters',
                'slug' => 'cables-adapters',
                'description' => 'Networking, display, power, and interface adapters',
                'active' => true,
            ],

            # Software subcategories
            [
                'parent_id' => $software->id,
                'name' => 'Operating Systems',
                'slug' => 'operating-systems',
                'description' => 'Operating system licenses and media',
                'active' => true,
            ],
            [
                'parent_id' => $software->id,
                'name' => 'Productivity Software',
                'slug' => 'productivity-software',
                'description' => 'Office and productivity applications',
                'active' => true,
            ],
            [
                'parent_id' => $software->id,
                'name' => 'Security Software',
                'slug' => 'security-software',
                'description' => 'Antivirus, endpoint protection, and security tools',
                'active' => true,
            ],
        ];     
        
        # Now we loop through the subcategories list and create or update each subcategory in the database
        foreach ($subcategoriesList as $subcategory) {
            Category::updateOrCreate(
                ['slug' => $subcategory['slug']],  # Unique identifier for the subcategory
                $subcategory
            );
        }
    }
}
