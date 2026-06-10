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

    protected $appends = ['url'];

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

    public function getThumbUrlAttribute(): string
    {
        $thumbPath = dirname($this->path) . '/thumbs/' . basename($this->path);
        return Storage::disk('public')->exists($thumbPath)
            ? Storage::disk('public')->url($thumbPath)
            : $this->url;
    }

    public function getThumbSmUrlAttribute(): string
    {
        $smPath = dirname($this->path) . '/thumbs/sm/' . basename($this->path);
        return Storage::disk('public')->exists($smPath)
            ? Storage::disk('public')->url($smPath)
            : $this->thumb_url;
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