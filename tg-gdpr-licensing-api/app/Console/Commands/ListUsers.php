<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/**
 * List users with role + customer link, for ops / sanity checks.
 *
 * Usage:
 *   php artisan app:list-users
 *   php artisan app:list-users --role=admin
 *   php artisan app:list-users --role=customer
 */
class ListUsers extends Command
{
    protected $signature = 'app:list-users
                            {--role= : Filter by role (admin|customer)}
                            {--limit=50 : Max rows to show}';

    protected $description = 'List users with their role and Customer linkage.';

    public function handle(): int
    {
        $query = User::query()->orderBy('id');

        if ($role = $this->option('role')) {
            $query->where('role', $role);
        }

        $users = $query->limit((int) $this->option('limit'))->get();

        if ($users->isEmpty()) {
            $this->info('No users matched.');
            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Email', 'Name', 'Role', 'Customer', 'Verified', 'Created'],
            $users->map(fn (User $u) => [
                $u->id,
                $u->email,
                $u->name,
                $u->role,
                $u->customer_id ? "#{$u->customer_id}" : '—',
                $u->email_verified_at ? '✓' : '—',
                $u->created_at?->diffForHumans() ?? '—',
            ])->toArray()
        );

        $this->info("Showing {$users->count()} user(s).");

        return self::SUCCESS;
    }
}
