<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'company',
        'message',
        'source',
        'status',
    ];

    /**
     * Get leads by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Get leads by source.
     */
    public function scopeBySource($query, $source)
    {
        return $query->where('source', $source);
    }
}
