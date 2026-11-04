<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Notification extends Model
{
    public const TYPE_COMMENT = 1;

    public const TYPE_REPLY = 2;

    public const TYPE_POST_LIKE = 3;

    public const TYPE_COMMENT_LIKE = 4;

    public const TYPE_SYSTEM = 5;

    public const TYPE_LABELS = [
        self::TYPE_COMMENT => '评论',
        self::TYPE_REPLY => '回复',
        self::TYPE_POST_LIKE => '帖子点赞',
        self::TYPE_COMMENT_LIKE => '评论点赞',
        self::TYPE_SYSTEM => '系统通知',
    ];

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'type',
        'notifiable_type',
        'notifiable_id',
        'actor_id',
        'data',
        'is_read',
    ];

    protected function casts(): array
    {
        return [
            'type' => 'integer',
            'data' => 'array',
            'is_read' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }
}
