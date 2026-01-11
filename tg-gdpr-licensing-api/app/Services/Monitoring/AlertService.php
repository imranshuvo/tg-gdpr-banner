<?php

namespace App\Services\Monitoring;

use App\Models\AlertLog;
use App\Notifications\SystemAlertNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\SystemAlert;

class AlertService
{
    /**
     * Create an alert.
     */
    public function alert(
        string $type,
        string $category,
        string $title,
        string $message,
        ?array $context = null,
        ?Model $notifiable = null,
        bool $sendEmail = true
    ): AlertLog {
        $alert = AlertLog::create([
            'type' => $type,
            'category' => $category,
            'title' => $title,
            'message' => $message,
            'context' => $context,
            'notifiable_type' => $notifiable ? get_class($notifiable) : null,
            'notifiable_id' => $notifiable?->id,
        ]);

        // Log to Laravel log
        Log::channel('daily')->log(
            $this->mapTypeToLogLevel($type),
            "[{$category}] {$title}: {$message}",
            $context ?? []
        );

        // Send email notification if enabled
        if ($sendEmail && in_array($type, [AlertLog::TYPE_CRITICAL, AlertLog::TYPE_ERROR])) {
            $this->sendAlertEmail($alert);
        }

        return $alert;
    }

    /**
     * Critical alert - highest priority.
     */
    public function critical(string $category, string $title, string $message, ?array $context = null): AlertLog
    {
        return $this->alert(
            AlertLog::TYPE_CRITICAL,
            $category,
            $title,
            $message,
            $context,
            null,
            true
        );
    }

    /**
     * Error alert.
     */
    public function error(string $category, string $title, string $message, ?array $context = null): AlertLog
    {
        return $this->alert(
            AlertLog::TYPE_ERROR,
            $category,
            $title,
            $message,
            $context,
            null,
            true
        );
    }

    /**
     * Warning alert.
     */
    public function warning(string $category, string $title, string $message, ?array $context = null): AlertLog
    {
        return $this->alert(
            AlertLog::TYPE_WARNING,
            $category,
            $title,
            $message,
            $context
        );
    }

    /**
     * Info alert.
     */
    public function info(string $category, string $title, string $message, ?array $context = null): AlertLog
    {
        return $this->alert(
            AlertLog::TYPE_INFO,
            $category,
            $title,
            $message,
            $context,
            null,
            false
        );
    }

    /**
     * License-specific alerts.
     */
    public function licenseExpiring(Model $license): AlertLog
    {
        return $this->warning(
            AlertLog::CATEGORY_LICENSE,
            'License Expiring Soon',
            "License {$license->license_key} will expire on {$license->expires_at->format('Y-m-d')}",
            [
                'license_id' => $license->id,
                'customer_id' => $license->customer_id,
                'expires_at' => $license->expires_at,
            ]
        );
    }

    public function licenseExpired(Model $license): AlertLog
    {
        return $this->error(
            AlertLog::CATEGORY_LICENSE,
            'License Expired',
            "License {$license->license_key} has expired",
            [
                'license_id' => $license->id,
                'customer_id' => $license->customer_id,
                'expired_at' => $license->expires_at,
            ]
        );
    }

    public function licenseActivationLimitReached(Model $license): AlertLog
    {
        return $this->warning(
            AlertLog::CATEGORY_LICENSE,
            'License Activation Limit Reached',
            "License {$license->license_key} has reached its activation limit",
            [
                'license_id' => $license->id,
                'max_activations' => $license->max_activations,
            ]
        );
    }

    /**
     * Payment-specific alerts.
     */
    public function paymentFailed(string $gateway, array $context): AlertLog
    {
        return $this->error(
            AlertLog::CATEGORY_PAYMENT,
            'Payment Failed',
            "Payment failed via {$gateway}",
            $context
        );
    }

    public function subscriptionCancelled(Model $customer, array $context): AlertLog
    {
        return $this->warning(
            AlertLog::CATEGORY_PAYMENT,
            'Subscription Cancelled',
            "Subscription cancelled for customer {$customer->email}",
            $context
        );
    }

    /**
     * Security alerts.
     */
    public function suspiciousActivity(string $description, array $context): AlertLog
    {
        return $this->critical(
            AlertLog::CATEGORY_SECURITY,
            'Suspicious Activity Detected',
            $description,
            $context
        );
    }

    public function multipleFailedLogins(string $email, int $attempts): AlertLog
    {
        return $this->warning(
            AlertLog::CATEGORY_SECURITY,
            'Multiple Failed Login Attempts',
            "{$attempts} failed login attempts for {$email}",
            ['email' => $email, 'attempts' => $attempts]
        );
    }

    /**
     * Send alert email to admins.
     */
    protected function sendAlertEmail(AlertLog $alert): void
    {
        try {
            $adminEmails = config('app.admin_emails', [config('mail.from.address')]);
            
            if (empty($adminEmails)) {
                return;
            }

            foreach ($adminEmails as $email) {
                Mail::to($email)->send(new SystemAlert($alert));
            }

            $alert->update([
                'email_sent' => true,
                'email_sent_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send alert email', [
                'alert_id' => $alert->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Map alert type to log level.
     */
    protected function mapTypeToLogLevel(string $type): string
    {
        return match($type) {
            AlertLog::TYPE_CRITICAL => 'critical',
            AlertLog::TYPE_ERROR => 'error',
            AlertLog::TYPE_WARNING => 'warning',
            default => 'info',
        };
    }

    /**
     * Get unresolved alerts.
     */
    public function getUnresolved(int $limit = 100)
    {
        return AlertLog::unresolved()
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Get critical alerts.
     */
    public function getCritical(int $limit = 50)
    {
        return AlertLog::critical()
            ->unresolved()
            ->latest()
            ->limit($limit)
            ->get();
    }
}
