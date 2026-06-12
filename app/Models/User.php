<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'home_base',
        'travel_style',
        'bio',
        'avatar_url',
        'total_days_traveled',
        'total_kilometers',
        'total_likes_received',
        'default_currency',
        'current_city',
        'theme',
        'age',
        'travel_preferences',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'total_days_traveled' => 'integer',
            'total_kilometers' => 'decimal:2',
            'total_likes_received' => 'integer',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function likes(): HasMany
    {
        return $this->hasMany(Like::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function following(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'follows', 'follower_id', 'following_id')->withTimestamps();
    }

    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'follows', 'following_id', 'follower_id')->withTimestamps();
    }

    public function notifications(): MorphMany
    {
        return $this->morphMany(Notification::class, 'notifiable');
    }

    public function isFollowing(User $user): bool
    {
        return $this->following()->where('following_id', $user->id)->exists();
    }

    public function avatar(): string
    {
        return $this->avatar_url
            ?: 'https://ui-avatars.com/api/?background=c2412c&color=fff&name=' . urlencode($this->name);
    }

    public function getUnreadNotificationsCount(): int
    {
        return $this->notifications()->whereNull('read_at')->count();
    }

    public function getTotalMediaCount(): int
    {
        return Media::where('mediable_type', Post::class)
            ->whereIn('mediable_id', $this->posts()->pluck('id'))
            ->count();
    }
}