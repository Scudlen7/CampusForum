<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Report extends Model
{
    public const REASON_VIOLATION = 1;

    public const REASON_AD = 2;

    public const REASON_PORN = 3;

    public const REASON_INSULT = 4;

    public const REASON_OTHER = 5;

    public const STATUS_PENDING = 0;

    public const STATUS_HANDLED = 1;

    public const STATUS_IGNORED = 2;

    public const REASON_LABELS = [
        self::REASON_VIOLATION => '违规内容',
        self::REASON_AD => '广告 spam',
        self::REASON_PORN => '色情内容',
        self::REASON_INSULT => '侮辱谩骂',
        self::REASON_OTHER => '其他',
    ];

    public $timestamps = false;

    protected $fillable = [
        'reporter_id',
        'reportable_type',
        'reportable_id',
        'reason',
        'description',
        'status',
        'handled_by',
        'handled_at',
    ];

    protected function casts(): array
    {
        return [
            'reason' => 'integer',
            'status' => 'integer',
            'handled_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function reportable(): MorphTo
    {
        return $this->morphTo();
    }
}
