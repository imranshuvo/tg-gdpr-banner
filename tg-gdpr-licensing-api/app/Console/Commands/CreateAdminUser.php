<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Create or promote a super-admin user.
 *
 * Super admins have role=admin (which gates the /admin/* routes via the
 * `role:admin` middleware). They can manage Plans, Payment settings, all
 * Customers, all Sites, all Licenses, and DSAR requests.
 *
 * Typical use: bootstrapping the very first admin on a fresh deploy, before
 * the registration flow is publicly available.
 *
 * Usage examples:
 *   php artisan app:create-admin
 *   php artisan app:create-admin owner@cookiely.site
 *   php artisan app:create-admin owner@cookiely.site --name="Owner" --password=secret
 *
 * If the email already exists with role=customer, the command offers to
 * promote that user to admin (preserving their existing customer linkage).
 *
 * Note on terminology: the planned post-launch refactor renames `admin` to
 * `super_admin` in the DB and adds a separate per-site `owner|admin` pivot.
 * Today, "admin" IS the super admin — same role, same access surface.
 */
class CreateAdminUser extends Command
{
    protected $signature = 'app:create-admin
                            {email? : Admin email address (prompted if omitted)}
                            {--name= : Display name (defaults to local-part of email)}
                            {--password= : Password (random 16-char if omitted)}';

    protected $description = 'Create a super-admin user with full access to the /admin panel.';

    public function handle(): int
    {
        $email = $this->argument('email') ?: $this->ask('Email');
        $name  = $this->option('name')   ?: explode('@', (string) $email)[0];

        $passwordProvided = ! empty($this->option('password'));
        $password = $this->option('password') ?: Str::password(16, symbols: false);

        $validator = Validator::make(
            compact('email', 'name', 'password'),
            [
                'email'    => 'required|email|max:255',
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

        $existing = User::where('email', $email)->first();

        if ($existing) {
            if ($existing->role === 'admin') {
                $this->info("✓ {$email} is already a super admin.");
                return self::SUCCESS;
            }

            if (! $this->confirm("User {$email} exists with role <{$existing->role}>. Promote to admin?", default: true)) {
                $this->warn('Cancelled.');
                return self::FAILURE;
            }

            $existing->update(['role' => 'admin']);
            $this->info("✓ Promoted {$email} to super admin.");
            return self::SUCCESS;
        }

        // email_verified_at isn't in User::$fillable; assign + save to bypass
        // mass-assignment protection. CLI-created admins skip email verification.
        $user = new User([
            'name'     => $name,
            'email'    => $email,
            'password' => Hash::make($password),
            'role'     => 'admin',
        ]);
        $user->email_verified_at = now();
        $user->save();

        $this->newLine();
        $this->info("✓ Super admin created: {$email}");
        $this->line("  Name: {$name}");

        if (! $passwordProvided) {
            $this->newLine();
            $this->warn("Generated password: {$password}");
            $this->warn("Save this now — it won't be shown again.");
        }

        $this->newLine();
        $this->line("Sign in at: " . rtrim(config('app.url'), '/') . "/login");
        $this->line("Admin panel: " . rtrim(config('app.url'), '/') . "/admin");

        return self::SUCCESS;
    }
}
