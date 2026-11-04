<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public const ROLE_STUDENT = 0;

    public const ROLE_ADMIN = 1;

    protected $fillable = [
        'username',
        'email',
        'password',
        'avatar',
        'nickname',
        'bio',
        'college',
        'major',
        'grade',
        'role',
        'is_banned',
        'banned_until',
        'is_certified',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'banned_until' => 'datetime',
            'password' => 'hashed',
            'is_banned' => 'boolean',
            'is_certified' => 'boolean',
            'role' => 'integer',
            'grade' => 'integer',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isBannedNow(): bool
    {
        if (! $this->is_banned) {
            return false;
        }

        if ($this->banned_until === null) {
            return true;
        }

        return $this->banned_until->isFuture();
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function likes(): HasMany
    {
        return $this->hasMany(Like::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function certification(): HasOne
    {
        return $this->hasOne(StudentCertification::class)->latestOfMany();
    }

    public function displayName(): string
    {
        return $this->nickname ?: $this->username;
    }
}
