<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name',
        'slug',
        'post_count',
    ];

    protected function casts(): array
    {
        return [
            'post_count' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class, 'post_tag');
    }
}
