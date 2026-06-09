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

    public function isLikedBy(?User $user): bool
    {
        return $user && $this->likes()->where('user_id', $user->id)->exists();
    }

    public function isReady(): bool
    {
        return $this->status === 'ready';
    }
}