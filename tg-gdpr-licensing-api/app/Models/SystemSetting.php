<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class SystemSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'description',
        'is_encrypted',
    ];

    protected $casts = [
        'is_encrypted' => 'boolean',
    ];

    const TYPE_STRING = 'string';
    const TYPE_BOOLEAN = 'boolean';
    const TYPE_INTEGER = 'integer';
    const TYPE_JSON = 'json';

    const GROUP_GENERAL = 'general';
    const GROUP_PAYMENT = 'payment';
    const GROUP_EMAIL = 'email';
    const GROUP_LICENSE = 'license';

    /**
     * Get a setting value by key.
     */
    public static function get(string $key, $default = null)
    {
        $setting = static::where('key', $key)->first();
        
        if (!$setting) {
            return $default;
        }

        $value = $setting->is_encrypted ? Crypt::decryptString($setting->value) : $setting->value;

        return match($setting->type) {
            self::TYPE_BOOLEAN => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            self::TYPE_INTEGER => (int) $value,
            self::TYPE_JSON => json_decode($value, true),
            default => $value,
        };
    }

    /**
     * Set a setting value.
     */
    public static function set(string $key, $value, string $type = self::TYPE_STRING, bool $encrypt = false)
    {
        $processedValue = match($type) {
            self::TYPE_JSON => json_encode($value),
            self::TYPE_BOOLEAN => $value ? '1' : '0',
            default => (string) $value,
        };

        if ($encrypt) {
            $processedValue = Crypt::encryptString($processedValue);
        }

        return static::updateOrCreate(
            ['key' => $key],
            [
                'value' => $processedValue,
                'type' => $type,
                'is_encrypted' => $encrypt,
            ]
        );
    }

    /**
     * Get all settings for a group.
     */
    public static function getGroup(string $group): array
    {
        return static::where('group', $group)
            ->get()
            ->mapWithKeys(fn($setting) => [$setting->key => static::get($setting->key)])
            ->toArray();
    }
}
