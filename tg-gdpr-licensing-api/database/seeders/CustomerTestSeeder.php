<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Customer;
use Illuminate\Support\Facades\Hash;

class CustomerTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if customer already exists
        $existingCustomer = Customer::where('email', 'customer@example.com')->first();
        
        if ($existingCustomer) {
            $this->command->info('Test customer already exists: customer@example.com');
            
            // Make sure the user has the correct role
            $user = User::where('email', 'customer@example.com')->first();
            if ($user && $user->role !== 'customer') {
                $user->update(['role' => 'customer']);
                $this->command->info('Updated user role to customer');
            }
            
            return;
        }

        // Create a test customer
        $customer = Customer::create([
            'name' => 'Test Customer',
            'email' => 'customer@example.com',
            'company' => 'Test Company',
        ]);

        // Create a user for this customer
        $user = User::create([
            'name' => 'Test Customer',
            'email' => 'customer@example.com',
            'password' => Hash::make('password'),
            'customer_id' => $customer->id,
            'role' => 'customer', // Set role directly
        ]);

        $this->command->info('Test customer created: customer@example.com / password');
    }
}
