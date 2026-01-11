<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class License extends Model
{
    protected $fillable = [
        'customer_id',
        'license_key',
        'plan',
        'max_activations',
        'expires_at',
        'status',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function activations(): HasMany
    {
        return $this->hasMany(Activation::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function canActivate(): bool
    {
        return $this->activations()->where('status', 'active')->count() < $this->max_activations;
    }
}
