<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    protected $fillable = [
        'mediable_type',
        'mediable_id',
        'type',
        'path',
        'original_name',
        'size',
        'order',
    ];

    protected $casts = [
        'size' => 'integer',
        'order' => 'integer',
    ];

    public function mediable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->path);
    }

    public function isVideo(): bool
    {
        return $this->type === 'video';
    }

    public function isPhoto(): bool
    {
        return $this->type === 'photo';
    }
}