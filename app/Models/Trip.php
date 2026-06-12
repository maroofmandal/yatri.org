<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

class Trip extends Model
{
    protected $fillable = [
        'user_id', 'session_id', 'share_token', 'title',
        'origin', 'origin_lat', 'origin_lng', 'destinations',
        'start_date', 'end_date', 'days', 'nights', 'travelers',
        'budget_total', 'currency', 'style', 'interests',
        'status', 'plan', 'budget_breakdown', 'fit_status',
        'grounding', 'model_used', 'error', 'is_public', 'views',
        'image',
    ];

    protected $casts = [
        'destinations'     => 'array',
        'interests'        => 'array',
        'plan'             => 'array',
        'budget_breakdown' => 'array',
        'grounding'        => 'array',
        'is_public'        => 'boolean',
        'start_date'       => 'date',
        'end_date'         => 'date',
        'budget_total'     => 'decimal:2',
    ];

    public function getImageUrlAttribute(): string
    {
        if ($this->image) {
            return \Illuminate\Support\Facades\Storage::url($this->image);
        }
        return $this->fallbackGradient();
    }

    public function getOgImageUrlAttribute(): string
    {
        return $this->image_url;
    }

    public function getCardImageUrlAttribute(): string
    {
        if ($this->image) {
            $card = dirname($this->image) . '/card_' . basename($this->image);
            $disk = \Illuminate\Support\Facades\Storage::disk('public');
            if ($disk->exists($card)) {
                return $disk->url($card);
            }
            return $disk->url($this->image);
        }
        return $this->fallbackGradient();
    }

    public function fallbackGradient(): string
    {
        $dests = collect($this->destinations ?? []);
        $seed  = $dests->isNotEmpty() ? crc32($dests->first()['name'] ?? 'travel') : crc32('travel');
        $hue   = abs($seed) % 360;
        return "linear-gradient(135deg, hsl({$hue},40%,30%), hsl(" . (($hue + 60) % 360) . ",35%,20%))";
    }

    protected static function booted(): void
    {
        static::creating(function (Trip $trip) {
            if (empty($trip->share_token)) {
                $trip->share_token = static::uniqueToken();
            }
        });
    }

    public static function uniqueToken(): string
    {
        do {
            $token = Str::lower(Str::random(10));
        } while (static::where('share_token', $token)->exists());

        return $token;
    }

    public function getRouteKeyName(): string
    {
        return 'share_token';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function geminiLogs(): HasMany
    {
        return $this->hasMany(GeminiLog::class);
    }

    public function likes(): MorphMany
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'mediable');
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function reviews(): MorphMany
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    public function isLikedBy(?User $user): bool
    {
        return $user && $this->likes()->where('user_id', $user->id)->exists();
    }

    public function isReady(): bool
    {
        return $this->status === 'ready';
    }
}