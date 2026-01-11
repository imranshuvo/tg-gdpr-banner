<?php

namespace App\Services\Logging;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ActivityLogger
{
    /**
     * Log an activity.
     */
    public function log(
        string $description,
        ?Model $subject = null,
        ?Model $causer = null,
        ?array $properties = null,
        ?string $event = null,
        ?string $logName = null
    ): ActivityLog {
        $causer = $causer ?? Auth::user();

        return ActivityLog::create([
            'log_name' => $logName ?? 'default',
            'description' => $description,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id' => $subject?->id,
            'causer_type' => $causer ? get_class($causer) : null,
            'causer_id' => $causer?->id,
            'properties' => $properties,
            'event' => $event,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Log a customer activity.
     */
    public function logCustomer(string $event, Model $customer, array $properties = []): ActivityLog
    {
        return $this->log(
            description: "Customer {$event}: {$customer->name}",
            subject: $customer,
            properties: $properties,
            event: $event,
            logName: 'customer'
        );
    }

    /**
     * Log a license activity.
     */
    public function logLicense(string $event, Model $license, array $properties = []): ActivityLog
    {
        return $this->log(
            description: "License {$event}: {$license->license_key}",
            subject: $license,
            properties: $properties,
            event: $event,
            logName: 'license'
        );
    }

    /**
     * Log a payment activity.
     */
    public function logPayment(string $event, array $properties = []): ActivityLog
    {
        return $this->log(
            description: "Payment {$event}",
            properties: $properties,
            event: $event,
            logName: 'payment'
        );
    }

    /**
     * Log a security event.
     */
    public function logSecurity(string $event, array $properties = []): ActivityLog
    {
        return $this->log(
            description: "Security event: {$event}",
            properties: $properties,
            event: $event,
            logName: 'security'
        );
    }

    /**
     * Log authentication activity.
     */
    public function logAuth(string $event, ?Model $user = null, array $properties = []): ActivityLog
    {
        return $this->log(
            description: "Authentication: {$event}",
            subject: $user,
            properties: $properties,
            event: $event,
            logName: 'auth'
        );
    }

    /**
     * Get recent activity.
     */
    public function getRecent(int $limit = 50, ?string $logName = null)
    {
        $query = ActivityLog::with(['subject', 'causer'])
            ->latest();

        if ($logName) {
            $query->forLogName($logName);
        }

        return $query->limit($limit)->get();
    }

    /**
     * Get activity for a subject.
     */
    public function getForSubject(Model $subject, int $limit = 50)
    {
        return ActivityLog::where('subject_type', get_class($subject))
            ->where('subject_id', $subject->id)
            ->with('causer')
            ->latest()
            ->limit($limit)
            ->get();
    }
}
