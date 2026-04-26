<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'description',
        'features',
        'max_sites',
        'display_price',
        'display_period',
        'stripe_price_id_test',
        'stripe_price_id_live',
        'frisbii_plan_id_test',
        'frisbii_plan_id_live',
        'is_popular',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'features'   => 'array',
        'is_popular' => 'boolean',
        'is_active'  => 'boolean',
    ];

    /**
     * Resolve the provider-specific price/plan identifier for a (provider, mode) pair.
     *
     * @param  'stripe'|'frisbii'  $provider
     * @param  'test'|'live'       $mode
     */
    public function providerPriceId(string $provider, string $mode): ?string
    {
        return match (true) {
            $provider === 'stripe'  && $mode === 'test' => $this->stripe_price_id_test,
            $provider === 'stripe'  && $mode === 'live' => $this->stripe_price_id_live,
            $provider === 'frisbii' && $mode === 'test' => $this->frisbii_plan_id_test,
            $provider === 'frisbii' && $mode === 'live' => $this->frisbii_plan_id_live,
            default => null,
        };
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', true)->orderBy('sort_order');
    }
}
