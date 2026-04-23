<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteSession extends Model
{
    protected $fillable = [
        'site_id',
        'date',
        'total_sessions',
        'banner_shown',
        'consent_given',
        'consent_denied',
        'consent_customized',
        'no_action',
        'accepted_functional',
        'accepted_analytics',
        'accepted_marketing',
        'geo_breakdown',
        'device_breakdown',
    ];

    protected $casts = [
        'date' => 'date',
        'total_sessions' => 'integer',
        'banner_shown' => 'integer',
        'consent_given' => 'integer',
        'consent_denied' => 'integer',
        'consent_customized' => 'integer',
        'no_action' => 'integer',
        'accepted_functional' => 'integer',
        'accepted_analytics' => 'integer',
        'accepted_marketing' => 'integer',
        'geo_breakdown' => 'array',
        'device_breakdown' => 'array',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    // Get or create today's session record for a site
    public static function getOrCreateForToday(int $siteId): self
    {
        return self::firstOrCreate(
            ['site_id' => $siteId, 'date' => now()->toDateString()],
            [
                'total_sessions' => 0,
                'banner_shown' => 0,
                'consent_given' => 0,
                'consent_denied' => 0,
                'consent_customized' => 0,
                'no_action' => 0,
                'accepted_functional' => 0,
                'accepted_analytics' => 0,
                'accepted_marketing' => 0,
                'geo_breakdown' => [],
                'device_breakdown' => [],
            ]
        );
    }

    // Increment counters atomically
    public function incrementSession(): void
    {
        $this->increment('total_sessions');
    }

    public function incrementBannerShown(): void
    {
        $this->increment('banner_shown');
    }

    public function recordConsent(string $method, array $categories = []): void
    {
        switch ($method) {
            case 'accept_all':
                $this->increment('consent_given');
                $this->increment('accepted_functional');
                $this->increment('accepted_analytics');
                $this->increment('accepted_marketing');
                break;
            case 'reject_all':
                $this->increment('consent_denied');
                break;
            case 'customize':
                $this->increment('consent_customized');
                if ($categories['functional'] ?? false) {
                    $this->increment('accepted_functional');
                }
                if ($categories['analytics'] ?? false) {
                    $this->increment('accepted_analytics');
                }
                if ($categories['marketing'] ?? false) {
                    $this->increment('accepted_marketing');
                }
                break;
        }
    }

    public function recordGeo(string $countryCode): void
    {
        $breakdown = $this->geo_breakdown ?? [];
        $breakdown[$countryCode] = ($breakdown[$countryCode] ?? 0) + 1;
        $this->update(['geo_breakdown' => $breakdown]);
    }

    public function recordDevice(string $deviceType): void
    {
        $breakdown = $this->device_breakdown ?? [];
        $breakdown[$deviceType] = ($breakdown[$deviceType] ?? 0) + 1;
        $this->update(['device_breakdown' => $breakdown]);
    }
}
