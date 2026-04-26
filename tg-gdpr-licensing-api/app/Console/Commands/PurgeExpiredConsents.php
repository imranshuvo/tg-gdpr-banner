<?php

namespace App\Console\Commands;

use App\Models\ConsentRecord;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Daily retention enforcement for consent records.
 *
 * The privacy policy commits us to "auto-deleted after 36 months". The actual
 * per-record deadline lives in consent_records.expires_at, which is set on
 * creation by ConsentController from the site's consent_expiry_days setting.
 *
 * Records with NULL expires_at are left alone — they were created before the
 * expiry column was populated and need a separate one-time backfill.
 *
 * Deletes are chunked so a 6-month backlog doesn't blow up the DB connection.
 */
class PurgeExpiredConsents extends Command
{
    protected $signature = 'consents:purge-expired
                            {--chunk=500 : Number of records to delete per batch}
                            {--dry-run : Count what would be deleted without deleting}';

    protected $description = 'Delete consent records past their retention deadline (expires_at).';

    public function handle(): int
    {
        $chunk  = max(1, (int) $this->option('chunk'));
        $dryRun = (bool) $this->option('dry-run');

        $query = ConsentRecord::query()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now());

        $total = $query->count();

        if ($total === 0) {
            $this->info('No expired consent records.');
            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->info("DRY RUN: would delete {$total} expired consent record(s).");
            return self::SUCCESS;
        }

        $deleted = 0;
        do {
            $batch = $query->limit($chunk)->pluck('id');
            if ($batch->isEmpty()) break;

            ConsentRecord::whereIn('id', $batch)->delete();
            $deleted += $batch->count();
        } while ($batch->count() === $chunk);

        Log::info('Purged expired consent records', ['deleted' => $deleted, 'total_seen' => $total]);
        $this->info("Deleted {$deleted} expired consent record(s).");

        return self::SUCCESS;
    }
}
