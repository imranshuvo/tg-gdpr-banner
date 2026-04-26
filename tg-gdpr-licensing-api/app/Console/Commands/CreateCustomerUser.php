<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Create a customer user (role=customer) — bypasses the public registration
 * form. Same atomic User + Customer creation that registration does, so the
 * resulting user is ready to check out / receive a License via webhook.
 *
 * Useful for: comp accounts, partner onboarding, ops testing in staging.
 *
 * Usage:
 *   php artisan app:create-customer
 *   php artisan app:create-customer alice@example.com
 *   php artisan app:create-customer alice@example.com --name="Alice" --company="Acme Ltd"
 */
class CreateCustomerUser extends Command
{
    protected $signature = 'app:create-customer
                            {email? : Customer email address}
                            {--name= : Display name}
                            {--company= : Company name (optional)}
                            {--password= : Password (random 16-char if omitted)}';

    protected $description = 'Create a customer user (with linked Customer record) outside the registration flow.';

    public function handle(): int
    {
        $email   = $this->argument('email')  ?: $this->ask('Email');
        $name    = $this->option('name')     ?: explode('@', (string) $email)[0];
        $company = $this->option('company');

        $passwordProvided = ! empty($this->option('password'));
        $password = $this->option('password') ?: Str::password(16, symbols: false);

        $validator = Validator::make(
            compact('email', 'name', 'password'),
            [
                'email'    => 'required|email|max:255|unique:users,email',
                'name'     => 'required|string|max:255',
                'password' => 'required|string|min:8|max:255',
            ]
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $err) {
                $this->error($err);
            }
            return self::FAILURE;
        }

        // Atomic — same shape as RegisteredUserController. A Customer-less
        // User cannot complete checkout, so we never produce that half-state.
        $user = DB::transaction(function () use ($email, $name, $company, $password) {
            $customer = Customer::create([
                'name'    => $name,
                'email'   => $email,
                'company' => $company,
            ]);

            // email_verified_at not in User::$fillable; assign + save manually.
            $u = new User([
                'name'        => $name,
                'email'       => $email,
                'password'    => Hash::make($password),
                'role'        => 'customer',
                'customer_id' => $customer->id,
            ]);
            $u->email_verified_at = now();
            $u->save();
            return $u;
        });

        $this->newLine();
        $this->info("✓ Customer user created: {$email}");
        $this->line("  User #{$user->id} → Customer #{$user->customer_id}" . ($company ? " ({$company})" : ''));

        if (! $passwordProvided) {
            $this->newLine();
            $this->warn("Generated password: {$password}");
            $this->warn("Save this now — it won't be shown again.");
        }

        return self::SUCCESS;
    }
}
