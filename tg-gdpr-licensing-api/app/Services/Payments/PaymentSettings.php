<?php

namespace App\Services\Payments;

use App\Models\SystemSetting;

/**
 * Thin wrapper over SystemSetting for payment-provider credentials.
 *
 * Conventional keys (encrypted):
 *   payment.{provider}.enabled        bool
 *   payment.{provider}.mode           'test' | 'live'
 *   payment.{provider}.test.public    string
 *   payment.{provider}.test.secret    string (encrypted)
 *   payment.{provider}.test.webhook   string (encrypted)
 *   payment.{provider}.live.public    string
 *   payment.{provider}.live.secret    string (encrypted)
 *   payment.{provider}.live.webhook   string (encrypted)
 *   payment.{provider}.endpoint       optional (Frisbii API base URL)
 */
class PaymentSettings
{
    public function __construct(public readonly string $provider) {}

    public function enabled(): bool
    {
        return (bool) SystemSetting::get($this->key('enabled'), false);
    }

    public function mode(): string
    {
        return SystemSetting::get($this->key('mode'), 'test') === 'live' ? 'live' : 'test';
    }

    public function publicKey(): ?string
    {
        return SystemSetting::get($this->key($this->mode() . '.public'));
    }

    public function secretKey(): ?string
    {
        return SystemSetting::get($this->key($this->mode() . '.secret'));
    }

    public function webhookSecret(): ?string
    {
        return SystemSetting::get($this->key($this->mode() . '.webhook'));
    }

    public function endpoint(): ?string
    {
        return SystemSetting::get($this->key('endpoint'));
    }

    /** Persist a single field. Encrypted-by-default for secret/webhook fields. */
    public function set(string $field, mixed $value, bool $encrypt = false): void
    {
        SystemSetting::set(
            key:    $this->key($field),
            value:  $value,
            type:   is_bool($value) ? SystemSetting::TYPE_BOOLEAN : SystemSetting::TYPE_STRING,
            encrypt: $encrypt,
        );

        // SystemSetting::set doesn't accept a group; backfill it so the row groups in admin queries.
        SystemSetting::where('key', $this->key($field))->update(['group' => SystemSetting::GROUP_PAYMENT]);
    }

    private function key(string $suffix): string
    {
        return "payment.{$this->provider}.{$suffix}";
    }
}
