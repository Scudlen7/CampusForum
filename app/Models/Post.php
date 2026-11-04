<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model
{
    use SoftDeletes;

    public const TYPE_CHECKIN = 0;

    public const TYPE_HELP = 1;

    public const TYPE_DISCUSSION = 2;

    public const TYPE_SHOWCASE = 3;

    public const TYPE_LIFE = 4;

    public const STATUS_PENDING = 0;

    public const STATUS_NORMAL = 1;

    public const STATUS_DELETED = 2;

    public const CATEGORY_SLUG_TYPE_MAP = [
        'checkin' => self::TYPE_CHECKIN,
        'tech-help' => self::TYPE_HELP,
        'tech-discussion' => self::TYPE_DISCUSSION,
        'showcase' => self::TYPE_SHOWCASE,
        'campus-life' => self::TYPE_LIFE,
    ];

    protected $fillable = [
        'user_id',
        'category_id',
        'type',
        'title',
        'content',
        'cover_image',
        'is_pinned',
        'is_hidden',
        'status',
        'view_count',
        'like_count',
        'comment_count',
        'last_comment_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => 'integer',
            'status' => 'integer',
            'is_pinned' => 'boolean',
            'is_hidden' => 'boolean',
            'view_count' => 'integer',
            'like_count' => 'integer',
            'comment_count' => 'integer',
            'last_comment_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'post_tag');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function likes(): MorphMany
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    public function reports(): MorphMany
    {
        return $this->morphMany(Report::class, 'reportable');
    }

    public static function typeFromCategory(?Category $category): int
    {
        if (! $category) {
            return self::TYPE_DISCUSSION;
        }

        return self::CATEGORY_SLUG_TYPE_MAP[$category->slug] ?? self::TYPE_DISCUSSION;
    }

    public function typeLabel(): string
    {
        return $this->category?->name ?? '未分类';
    }

    public function scopeVisible($query)
    {
        return $query->where('status', self::STATUS_NORMAL)->where('is_hidden', false);
    }
}
