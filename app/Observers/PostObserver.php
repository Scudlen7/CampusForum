<?php

namespace App\Observers;

use App\Models\Post;
use App\Models\Tag;

class PostObserver
{
    public function saved(Post $post): void
    {
        if (! $post->wasRecentlyCreated && ! $post->wasChanged('id')) {
            return;
        }
    }

    public function deleted(Post $post): void
    {
        if ($post->isForceDeleting()) {
            return;
        }

        foreach ($post->tags as $tag) {
            Tag::whereKey($tag->id)->decrement('post_count');
        }
    }

    public static function syncTagCounts(Post $post, array $oldTagIds, array $newTagIds): void
    {
        $removed = array_diff($oldTagIds, $newTagIds);
        $added = array_diff($newTagIds, $oldTagIds);

        foreach ($removed as $tagId) {
            Tag::whereKey($tagId)->decrement('post_count');
        }

        foreach ($added as $tagId) {
            Tag::whereKey($tagId)->increment('post_count');
        }
    }
}
