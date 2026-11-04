<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Comment extends Model
{
    use SoftDeletes;

    public const STATUS_PENDING = 0;

    public const STATUS_NORMAL = 1;

    public const STATUS_DELETED = 2;

    protected $fillable = [
        'post_id',
        'user_id',
        'parent_id',
        'reply_to_id',
        'content',
        'like_count',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'integer',
            'like_count' => 'integer',
        ];
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reply_to_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function likes(): MorphMany
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    public function reports(): MorphMany
    {
        return $this->morphMany(Report::class, 'reportable');
    }

    public function scopeVisibleOnForum($query)
    {
        return $query->visible()
            ->whereHas('post', fn ($post) => $post->visible())
            ->where(fn ($comment) => $comment->whereNull('parent_id')
                ->orWhereHas('parent', fn ($parent) => $parent->visible()));
    }

    public function scopeVisible($query)
    {
        return $query->where('status', self::STATUS_NORMAL);
    }
}
