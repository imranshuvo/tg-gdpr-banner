<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ConsentRecord extends Model
{
    protected $fillable = [
        'site_id',
        'consent_id',
        'visitor_hash',
        'ip_anonymized',
        'country_code',
        'region_code',
        'consent_categories',
        'consent_method',
        'tcf_string',
        'tcf_purposes',
        'tcf_vendors',
        'tcf_legitimate_interests',
        'gcm_state',
        'policy_version',
        'user_agent_hash',
        'device_type',
        'browser',
        'expires_at',
        'withdrawn_at',
        'withdrawal_reason',
        'synced_from_plugin',
        'plugin_created_at',
    ];

    protected $casts = [
        'consent_categories' => 'array',
        'tcf_purposes' => 'array',
        'tcf_vendors' => 'array',
        'tcf_legitimate_interests' => 'array',
        'gcm_state' => 'array',
        'synced_from_plugin' => 'boolean',
        'expires_at' => 'datetime',
        'withdrawn_at' => 'datetime',
        'plugin_created_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($record) {
            if (empty($record->consent_id)) {
                $record->consent_id = Str::uuid();
            }
        });
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    // Helpers
    public function isWithdrawn(): bool
    {
        return $this->withdrawn_at !== null;
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function hasConsentedTo(string $category): bool
    {
        return $this->consent_categories[$category] ?? false;
    }

    public function withdraw(string $reason = null): void
    {
        $this->update([
            'withdrawn_at' => now(),
            'withdrawal_reason' => $reason,
        ]);
    }

    // Anonymize IP (zero last octet for IPv4, last 80 bits for IPv6)
    public static function anonymizeIp(?string $ip): ?string
    {
        if (!$ip) {
            return null;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            // IPv4: Zero last octet (e.g., 192.168.1.100 -> 192.168.1.0)
            return preg_replace('/\.\d+$/', '.0', $ip);
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            // IPv6: Zero last 80 bits (last 5 groups)
            $parts = explode(':', $ip);
            for ($i = max(0, count($parts) - 5); $i < count($parts); $i++) {
                $parts[$i] = '0';
            }
            return implode(':', $parts);
        }

        return null;
    }

    // Create visitor hash from IP + User-Agent
    public static function createVisitorHash(string $ip, string $userAgent): string
    {
        return hash('sha256', $ip . '|' . $userAgent);
    }

    // Detect device type from user agent
    public static function detectDeviceType(string $userAgent): string
    {
        $userAgent = strtolower($userAgent);
        
        if (preg_match('/mobile|android|iphone|ipod|blackberry|windows phone/i', $userAgent)) {
            return 'mobile';
        }
        
        if (preg_match('/tablet|ipad|kindle|playbook/i', $userAgent)) {
            return 'tablet';
        }
        
        if (preg_match('/mozilla|chrome|safari|firefox|edge|opera/i', $userAgent)) {
            return 'desktop';
        }
        
        return 'unknown';
    }

    // Detect browser from user agent
    public static function detectBrowser(string $userAgent): string
    {
        if (preg_match('/edg/i', $userAgent)) return 'Edge';
        if (preg_match('/chrome/i', $userAgent)) return 'Chrome';
        if (preg_match('/safari/i', $userAgent)) return 'Safari';
        if (preg_match('/firefox/i', $userAgent)) return 'Firefox';
        if (preg_match('/opera|opr/i', $userAgent)) return 'Opera';
        if (preg_match('/msie|trident/i', $userAgent)) return 'IE';
        
        return 'Unknown';
    }
}
