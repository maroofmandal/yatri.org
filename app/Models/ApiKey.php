<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiKey extends Model
{
    protected $fillable = [
        'service', 'key', 'label', 'is_active', 'last_error', 'last_used_at',
    ];

    protected $casts = [
        'is_active'    => 'boolean',
        'last_used_at' => 'datetime',
    ];

    public function scopeActive($query, string $service)
    {
        return $query->where('service', $service)->where('is_active', true);
    }

    public function scopeNextRoundRobin($query, string $service)
    {
        return $query->active($service)->orderBy('last_used_at', 'asc')->orderBy('id', 'asc');
    }

    public static function nextFor(string $service): ?self
    {
        return static::nextRoundRobin($service)->first();
    }
}
