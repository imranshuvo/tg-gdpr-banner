<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Customer extends Model
{
    protected $fillable = [
        'name',
        'email',
        'company',
        'stripe_id',
        'frisbii_id',
        'phone',
        'address',
        'city',
        'state',
        'postal_code',
        'country',
        'vat_number',
    ];

    public function licenses(): HasMany
    {
        return $this->hasMany(License::class);
    }

    public function sites(): HasMany
    {
        return $this->hasMany(Site::class);
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }
}

