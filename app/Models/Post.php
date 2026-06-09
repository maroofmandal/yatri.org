<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

class Post extends Model
{
    protected $fillable = [
        'user_id',
        'trip_id',
        'title',
        'slug',
        'type',
        'body',
        'location',
        'location_lat',
        'location_lng',
        'is_public',
    ];

    protected $casts = [
        'location_lat' => 'decimal:7',
        'location_lng' => 'decimal:7',
        'is_public' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Post $post) {
            if (empty($post->slug)) {
                $post->slug = static::uniqueSlug($post->title);
            }
        });

        static::updating(function (Post $post) {
            if ($post->isDirty('title') && empty($post->slug)) {
                $post->slug = static::uniqueSlug($post->title);
            }
        });
    }

    public static function uniqueSlug(string $title): string
    {
        $slug = Str::slug($title);
        $originalSlug = $slug;
        $counter = 1;

        while (static::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'mediable');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function likes(): MorphMany
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    public function isLikedBy(?User $user): bool
    {
        return $user && $this->likes()->where('user_id', $user->id)->exists();
    }

    public function getLikesCountAttribute(): int
    {
        return $this->likes()->count();
    }

    public function getCommentsCountAttribute(): int
    {
        return $this->comments()->count();
    }

    public function getMetaDescriptionAttribute(): ?string
    {
        return $this->body ? Str::limit(strip_tags($this->body), 160) : null;
    }
}