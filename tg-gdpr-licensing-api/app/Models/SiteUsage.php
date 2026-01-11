<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteUsage extends Model
{
    protected $table = 'site_usage';
    
    protected $fillable = [
        'site_id',
        'customer_id',
        'year',
        'month',
        'total_sessions',
        'total_consents',
        'session_limit',
        'limit_exceeded',
        'limit_exceeded_at',
        'billed',
        'billed_at',
    ];

    protected $casts = [
        'limit_exceeded' => 'boolean',
        'billed' => 'boolean',
        'limit_exceeded_at' => 'datetime',
        'billed_at' => 'datetime',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    // Get or create current month's usage record
    public static function getOrCreateForMonth(Site $site): self
    {
        return self::firstOrCreate(
            [
                'site_id' => $site->id,
                'year' => now()->year,
                'month' => now()->month,
            ],
            [
                'customer_id' => $site->customer_id,
                'total_sessions' => 0,
                'total_consents' => 0,
                'session_limit' => $site->getSessionLimit(),
            ]
        );
    }

    public function incrementSessions(int $count = 1): void
    {
        $this->increment('total_sessions', $count);
        $this->checkLimit();
    }

    public function incrementConsents(int $count = 1): void
    {
        $this->increment('total_consents', $count);
    }

    private function checkLimit(): void
    {
        if (!$this->limit_exceeded && $this->total_sessions >= $this->session_limit) {
            $this->update([
                'limit_exceeded' => true,
                'limit_exceeded_at' => now(),
            ]);
        }
    }

    public function getUsagePercentage(): float
    {
        if ($this->session_limit === 0) {
            return 100;
        }
        return min(100, ($this->total_sessions / $this->session_limit) * 100);
    }
}
