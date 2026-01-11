<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteCookie extends Model
{
    protected $fillable = [
        'site_id',
        'cookie_definition_id',
        'cookie_name',
        'cookie_pattern',
        'is_regex',
        'category',
        'provider',
        'description',
        'duration',
        'script_pattern',
        'is_active',
        'is_custom',
        'source',
        'last_detected_at',
    ];

    protected $casts = [
        'is_regex' => 'boolean',
        'is_active' => 'boolean',
        'is_custom' => 'boolean',
        'last_detected_at' => 'datetime',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function definition(): BelongsTo
    {
        return $this->belongsTo(CookieDefinition::class, 'cookie_definition_id');
    }
}
