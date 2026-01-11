<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CookieDefinition extends Model
{
    protected $fillable = [
        'cookie_name',
        'cookie_pattern',
        'is_regex',
        'category',
        'provider',
        'provider_url',
        'description',
        'description_translations',
        'duration',
        'duration_seconds',
        'platform',
        'source',
        'confidence_score',
        'verified',
        'verified_by',
        'usage_count',
    ];

    protected $casts = [
        'is_regex' => 'boolean',
        'description_translations' => 'array',
        'verified' => 'boolean',
        'confidence_score' => 'decimal:2',
    ];

    public function verifiedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function siteCookies(): HasMany
    {
        return $this->hasMany(SiteCookie::class);
    }

    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    // Get description in specific language
    public function getDescription(string $lang = 'en'): string
    {
        if ($lang === 'en') {
            return $this->description ?? '';
        }
        
        return $this->description_translations[$lang] ?? $this->description ?? '';
    }
}
