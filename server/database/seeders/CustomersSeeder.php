<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Faker\Factory as FakerFactory;
use Faker\Generator;
use Illuminate\Support\Str;
use App\Models\Customer;

class CustomersSeeder extends Seeder
{

    protected Generator $faker;

    # Common email domains for generating realistic email addresses
    protected array $emailDomains = [
        'gmail.com', 
        'hotmail.com', 
        'outlook.com', 
        'yahoo.com', 
        'icloud.com',
        'live.com'
    ];

    # Common business names for generating realistic company customers
    protected array $businessNames = [
        "CVS Pharmacy", "Walgreens", "Rite Aid", "Walmart Pharmacy", "Kroger Pharmacy",
        "Publix Pharmacy", "Costco Pharmacy", "Albertsons Pharmacy", "H-E-B Pharmacy", "Meijer Pharmacy",
        "Hy-Vee Pharmacy", "Giant Eagle Pharmacy", "Stop & Shop Pharmacy", "Safeway Pharmacy", "Wegmans Pharmacy",
        "ShopRite Pharmacy", "Harris Teeter Pharmacy", "Fred Meyer Pharmacy", "Smith's Food and Drug", "Vons Pharmacy",
        "WinCo Foods Pharmacy", "Hannaford Pharmacy", "Ingles Pharmacy", "Sav-On Pharmacy", "Osco Drug",
        "Navarro Discount Pharmacy", "Health Mart", "Good Neighbor Pharmacy", "Medicine Shoppe", "Discount Drug Mart",
        "Kinney Drugs", "Bartell Drugs", "Thrifty White", "Hi-School Pharmacy", "USA Drug",
        "Kerr Drug", "Lewis Drug", "Fruth Pharmacy", "Hartig Drug", "Dierbergs Pharmacy",
        "Price Chopper Pharmacy", "Big Y Pharmacy", "Brookshire’s Pharmacy", "Raley's Pharmacy", "Bel Air Pharmacy",
        "Nob Hill Foods", "Schnucks Pharmacy", "Food Lion Pharmacy", "Weis Markets", "United Supermarkets",

        "The Kroger Co.", "Ralphs", "Dillons", "King Soopers", "Fry's Food and Drug",
        "QFC", "City Market", "Jay C Food Stores", "Pay-Less Super Markets", "Jewel-Osco",
        "Shaw's", "Acme Markets", "Tom Thumb", "Randalls", "Market Street",
        "Amigos", "Publix Super Markets", "Central Market", "Joe V’s Smart Shop", "Wakefern Food Corp.",
        "Price Rite Marketplace", "The Fresh Market", "Whole Foods Market", "Sprouts Farmers Market", "Trader Joe's",
        "Wegmans Food Markets", "Market District", "Save A Lot", "ALDI", "Lidl US",
        "Stater Bros. Markets", "Smart & Final", "Gelson's Markets", "Bristol Farms", "Bashas'",
        "AJ's Fine Foods", "Food City", "SuperValu", "Cub Foods", "Hornbacher's",
        "Shoppers Food & Pharmacy", "Farm Fresh", "Dierbergs Markets", "Schnuck Markets", "Brookshire Grocery Company",
        "Super 1 Foods", "Fresh by Brookshire's", "Lowes Foods", "Ingles Markets", "Demoulas Super Markets",
        "Market Basket", "Big Y Foods", "Tops Friendly Markets", "Golub Corporation", "Price Chopper",
        "Market 32", "Associated Food Stores", "Piggly Wiggly", "WinCo Foods", "Woodman's Markets",
        "Fareway Stores", "Festival Foods", "Lunds & Byerlys", "Roche Bros.", "Brookshire Brothers",
        "Homeland Stores", "Reasor's", "Rosauers Supermarkets", "Town & Country Food Stores", "Western Sizzlin",

        "Kirkland & Ellis LLP", "Latham & Watkins LLP", "DLA Piper", "Baker McKenzie", "Skadden, Arps, Slate, Meagher & Flom",
        "Sidley Austin LLP", "Morgan, Lewis & Bockius LLP", "Hogan Lovells", "White & Case LLP", "Jones Day",
        "Gibson, Dunn & Crutcher LLP", "Ropes & Gray LLP", "Greenberg Traurig, LLP", "Simpson Thacher & Bartlett LLP",
        "Paul, Weiss, Rifkind, Wharton & Garrison LLP", "Mayer Brown", "Reed Smith LLP", "Quinn Emanuel Urquhart & Sullivan", "Deloitte",
        "PricewaterhouseCoopers (PwC)", "Ernst & Young (EY)", "KPMG", "RSM US", "BDO USA",
        "Grant Thornton", "CohnReznick", "Crowe LLP", "Baker Tilly", "Moss Adams",
        "Plante Moran", "Marcum LLP", "EisnerAmper", "Cherry Bekaert", "Kaiser Permanente",
        "Mayo Clinic", "Cleveland Clinic", "HCA Healthcare", "Tenet Healthcare", "Community Health Systems",
        "Ascension Health", "Providence Health & Services", "Trinity Health", "Catholic Health Initiatives", "Baylor Scott & White Health",
        "Dignity Health", "Banner Health", "Northwell Health", "MedStar Health", "Sentara Healthcare",
        "Novant Health", "Jefferson Health", "Orlando Health", "Scripps Health", "Gensler",
        "Perkins and Will", "HDR, Inc.", "AECOM", "HOK", "Stantec",
        "CannonDesign", "SmithGroup", "CallisonRTKL", "ZGF Architects", "Skidmore, Owings & Merrill (SOM)",
        "Kimley-Horn", "Jacobs Solutions", "Bechtel", "Fluor Corporation", "Kiewit Corporation",
        "Turner Construction", "Whiting-Turner", "Gilbane Building Company", "PCL Construction", "DPR Construction",
        "Skanska USA", "McKinsey & Company", "Boston Consulting Group (BCG)", "Bain & Company", "Accenture",
        "Booz Allen Hamilton", "Cognizant", "Infosys", "Wipro"
    ];


    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->faker = FakerFactory::create('en_US');
        $this->faker->unique(true);

        $this->seedIndividuals(1800);
        $this->seedBusinesses(200);
    }

    # Seed individual customers with realistic data
    protected function seedIndividuals(int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $firstName = $this->faker->firstName();
            $lastName = $this->faker->lastName();

            $email = $this->uniquePersonalEmail($firstName, $lastName);
            $phone = $this->uniqueUsPhone();
            $address = $this->usAddress();

            Customer::create([
                'customer_type' => 'individual',
                'first_name' => $firstName,
                'last_name' => $lastName,
                'business_name' => null,
                'email' => $email,
                'phone' => $phone,
                'tax_number' => null,
                'address_line_1' => $address['address_line_1'],
                'address_line_2' => $address['address_line_2'],
                'city' => $address['city'],
                'state' => $address['state'],
                'postal_code' => $address['postal_code'],
                'country' => 'USA',
                'is_active' => $this->faker->boolean(96),
            ]);
        }
    }

    # Seed business customers with realistic data
    protected function seedBusinesses(int $count): void
    {
        $businessNames = $this->businessNames;
        shuffle($businessNames);

        foreach (array_slice($businessNames, 0, $count) as $businessName) {
            $email = $this->businessEmailFromName($businessName);
            $phone = $this->uniqueUsPhone();
            $address = $this->usAddress();

            Customer::create([
                'customer_type' => 'business',
                'first_name' => null,
                'last_name' => null,
                'business_name' => $businessName,
                'email' => $email,
                'phone' => $phone,
                'tax_number' => $this->uniqueEin(),
                'address_line_1' => $address['address_line_1'],
                'address_line_2' => $address['address_line_2'],
                'city' => $address['city'],
                'state' => $address['state'],
                'postal_code' => $address['postal_code'],
                'country' => 'USA',
                'is_active' => $this->faker->boolean(98),
            ]);
        }
    }

    # Generate a unique personal email address based on the customer's name
    protected function uniquePersonalEmail(string $firstName, string $lastName): string
    {
        $base = Str::of($firstName . '.' . $lastName)
            ->lower()
            ->ascii()
            ->replaceMatches('/[^a-z0-9\.]/', '')
            ->value();

        $number = $this->faker->unique()->numberBetween(10, 9999);
        $domain = $this->faker->randomElement($this->emailDomains);

        return "{$base}{$number}@{$domain}";
    }

    # Generate a business email address based on the business name
    protected function businessEmailFromName(string $businessName): string
    {
        $slug = Str::of($businessName)
            ->lower()
            ->ascii()
            ->replaceMatches('/[^a-z0-9]+/', '')
            ->trim('-')
            ->value();

        return "contact@{$slug}.com";
    }

    # Keep track of used phone numbers to ensure uniqueness
    protected array $usedPhones = [];

    # Generate a unique US phone number in the format +1-XXX-XXX-XXXX
    protected function uniqueUsPhone(): string
    {
        do {
            $areaCode = (string) random_int(201, 989);
            $exchange = (string) random_int(201, 989);
            $line = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);

            $phone = "+1-{$areaCode}-{$exchange}-{$line}";
        } while (isset($this->usedPhones[$phone]));

        $this->usedPhones[$phone] = true;

        return $phone;
    }

    # Keep track of used EINs to ensure uniqueness
    protected array $usedTaxNumbers = [];

    # Generate a unique EIN (Employer Identification Number) in the format XX-XXXXXXX
    protected function uniqueEin(): string
    {
        do {
            $prefix = str_pad((string) random_int(10, 99), 2, '0', STR_PAD_LEFT);
            $suffix = str_pad((string) random_int(0, 9999999), 7, '0', STR_PAD_LEFT);

            $ein = "{$prefix}-{$suffix}";
        } while (isset($this->usedTaxNumbers[$ein]));

        $this->usedTaxNumbers[$ein] = true;

        return $ein;
    }

    # Generate a realistic US address with a chance for a secondary address line
    protected function usAddress(): array
    {
        $secondaryChance = $this->faker->numberBetween(1, 100);

        return [
            'address_line_1' => $this->faker->streetAddress(),
            'address_line_2' => $secondaryChance <= 28 ? $this->secondaryAddress() : null,
            'city' => $this->faker->city(),
            'state' => $this->faker->stateAbbr(),
            'postal_code' => $this->faker->postcode(),
        ];
    }

    # Generate a secondary address line with realistic formats
    protected function secondaryAddress(): string
    {
        $types = ['Apt', 'Suite', 'Unit', 'Floor', 'Building'];
        $type = $this->faker->randomElement($types);

        if ($type === 'Floor') {
            return 'Floor ' . $this->faker->numberBetween(2, 20);
        }

        if ($type === 'Building') {
            return 'Building ' . strtoupper($this->faker->randomLetter()) . strtoupper($this->faker->randomLetter());
        }

        return $type . ' ' . $this->faker->numberBetween(1, 9999);
    }
}
