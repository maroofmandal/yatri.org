<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Destination extends Model
{
    protected $fillable = [
        'name', 'country', 'lat', 'lng', 'summary',
        'image', 'avg_daily_cost', 'popularity', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'lat'       => 'float',
        'lng'       => 'float',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
