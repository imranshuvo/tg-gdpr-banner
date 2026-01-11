<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Customer;
use App\Services\LicenseService;

class LicenseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $licenseService = new LicenseService();

        // Create test customers with licenses
        $customer1 = Customer::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'company' => 'Example Corp',
        ]);

        $customer2 = Customer::create([
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'company' => 'Smith Industries',
        ]);

        $customer3 = Customer::create([
            'name' => 'Bob Johnson',
            'email' => 'bob@example.com',
            'company' => 'Johnson LLC',
        ]);

        // Create licenses for different plans
        $singleLicense = $licenseService->createLicense($customer1->id, 'single');
        $this->command->info("Single Site License: {$singleLicense->license_key}");

        $threeSitesLicense = $licenseService->createLicense($customer2->id, '3-sites');
        $this->command->info("3-Sites License: {$threeSitesLicense->license_key}");

        $tenSitesLicense = $licenseService->createLicense($customer3->id, '10-sites');
        $this->command->info("10-Sites License: {$tenSitesLicense->license_key}");
    }
}

