<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    public const TYPE_ANNOUNCEMENT = 0;

    public const TYPE_CONTEST = 1;

    public const TYPE_EVENT = 2;

    protected $fillable = [
        'title',
        'content',
        'link',
        'image_path',
        'type',
        'is_active',
        'sort_order',
        'starts_at',
        'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            })
            ->orderBy('sort_order');
    }
}
