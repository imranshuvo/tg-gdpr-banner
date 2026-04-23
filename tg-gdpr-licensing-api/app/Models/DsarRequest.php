<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class DsarRequest extends Model
{
    protected $fillable = [
        'site_id',
        'customer_id',
        'request_type',
        'requester_email',
        'requester_name',
        'requester_phone',
        'additional_info',
        'visitor_hash',
        'verification_token',
        'verification_sent_at',
        'verified_at',
        'verified_method',
        'status',
        'data_export_path',
        'export_expires_at',
        'download_count',
        'processed_by',
        'processing_started_at',
        'completed_at',
        'admin_notes',
        'rejection_reason',
        'due_date',
        'sla_breached',
    ];

    protected $casts = [
        'verification_sent_at' => 'datetime',
        'verified_at' => 'datetime',
        'export_expires_at' => 'datetime',
        'processing_started_at' => 'datetime',
        'completed_at' => 'datetime',
        'due_date' => 'datetime',
        'sla_breached' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($request) {
            // Generate verification token
            $request->verification_token = Str::random(64);
            
            // Set due date (30 days from now per GDPR)
            $request->due_date = now()->addDays(30);
        });
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    // Helpers
    public function isOverdue(): bool
    {
        return $this->due_date && $this->due_date->isPast() && !$this->isCompleted();
    }

    public function isCompleted(): bool
    {
        return in_array($this->status, ['completed', 'rejected', 'cancelled']);
    }

    public function daysRemaining(): int
    {
        if (!$this->due_date || $this->isCompleted()) {
            return 0;
        }
        return max(0, now()->diffInDays($this->due_date, false));
    }

    public function verify(): void
    {
        $this->update([
            'verified_at' => now(),
            'verified_method' => 'email',
            'status' => 'verified',
        ]);
    }

    public function startProcessing(int $userId): void
    {
        $this->update([
            'status' => 'processing',
            'processed_by' => $userId,
            'processing_started_at' => now(),
        ]);
    }

    public function complete(string $exportPath = null): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'data_export_path' => $exportPath,
            'export_expires_at' => $exportPath ? now()->addDays(30) : null,
        ]);
    }

    public function reject(string $reason): void
    {
        $this->update([
            'status' => 'rejected',
            'completed_at' => now(),
            'rejection_reason' => $reason,
        ]);
    }

    public function requiresScopedConsentLookup(): bool
    {
        return in_array($this->request_type, ['access', 'portability', 'erasure', 'restriction', 'objection'], true);
    }

    public function hasVisitorHash(): bool
    {
        return !empty($this->visitor_hash);
    }

    public static function getRequestTypeLabel(string $type): string
    {
        return match ($type) {
            'access' => 'Right of Access (Art. 15)',
            'erasure' => 'Right to Erasure (Art. 17)',
            'rectification' => 'Right to Rectification (Art. 16)',
            'portability' => 'Right to Data Portability (Art. 20)',
            'restriction' => 'Right to Restriction (Art. 18)',
            'objection' => 'Right to Object (Art. 21)',
            default => $type,
        };
    }
}
