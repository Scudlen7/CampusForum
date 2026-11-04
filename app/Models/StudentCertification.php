<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentCertification extends Model
{
    public const STATUS_PENDING = 0;

    public const STATUS_APPROVED = 1;

    public const STATUS_REJECTED = 2;

    protected $fillable = [
        'user_id',
        'student_no',
        'image_path',
        'status',
        'reject_reason',
        'reviewed_by',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'integer',
            'reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
