<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlertLog extends Model
{
    protected $fillable = [
        'type',
        'category',
        'title',
        'message',
        'context',
        'notifiable_type',
        'notifiable_id',
        'is_resolved',
        'resolved_at',
        'resolved_by',
        'email_sent',
        'email_sent_at',
    ];

    protected $casts = [
        'context' => 'array',
        'is_resolved' => 'boolean',
        'email_sent' => 'boolean',
        'resolved_at' => 'datetime',
        'email_sent_at' => 'datetime',
    ];

    const TYPE_CRITICAL = 'critical';
    const TYPE_ERROR = 'error';
    const TYPE_WARNING = 'warning';
    const TYPE_INFO = 'info';

    const CATEGORY_LICENSE = 'license';
    const CATEGORY_PAYMENT = 'payment';
    const CATEGORY_SYSTEM = 'system';
    const CATEGORY_SECURITY = 'security';

    public function notifiable()
    {
        return $this->morphTo();
    }

    public function resolver()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    /**
     * Scope for unresolved alerts.
     */
    public function scopeUnresolved($query)
    {
        return $query->where('is_resolved', false);
    }

    /**
     * Scope for critical alerts.
     */
    public function scopeCritical($query)
    {
        return $query->where('type', self::TYPE_CRITICAL);
    }

    /**
     * Mark alert as resolved.
     */
    public function resolve(int $userId = null)
    {
        $this->update([
            'is_resolved' => true,
            'resolved_at' => now(),
            'resolved_by' => $userId,
        ]);
    }
}
