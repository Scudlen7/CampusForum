<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\Like;
use App\Models\Notification;
use App\Models\Post;
use App\Models\Report;
use App\Models\Tag;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PostDeletionService
{
    public function forceDelete(Post $post): void
    {
        DB::transaction(function () use ($post) {
            $post->loadMissing('tags');

            $commentIds = Comment::withTrashed()
                ->where('post_id', $post->id)
                ->pluck('id');

            Like::query()
                ->where(function ($query) use ($post) {
                    $query->where('likeable_type', Post::class)
                        ->where('likeable_id', $post->id);
                })
                ->when($commentIds->isNotEmpty(), function ($query) use ($commentIds) {
                    $query->orWhere(function ($query) use ($commentIds) {
                        $query->where('likeable_type', Comment::class)
                            ->whereIn('likeable_id', $commentIds);
                    });
                })
                ->delete();

            Report::query()
                ->where(function ($query) use ($post) {
                    $query->where('reportable_type', Post::class)
                        ->where('reportable_id', $post->id);
                })
                ->when($commentIds->isNotEmpty(), function ($query) use ($commentIds) {
                    $query->orWhere(function ($query) use ($commentIds) {
                        $query->where('reportable_type', Comment::class)
                            ->whereIn('reportable_id', $commentIds);
                    });
                })
                ->delete();

            Notification::query()
                ->where(function ($query) use ($post, $commentIds) {
                    $query->where(function ($query) use ($post) {
                        $query->where('notifiable_type', Post::class)
                            ->where('notifiable_id', $post->id);
                    });

                    if ($commentIds->isEmpty()) {
                        return;
                    }

                    $query->orWhere(function ($query) use ($commentIds) {
                        $query->where('notifiable_type', Comment::class)
                            ->whereIn('notifiable_id', $commentIds);
                    });

                    $query->orWhere(function ($query) use ($commentIds) {
                        foreach ($commentIds as $commentId) {
                            $query->orWhere('data->comment_id', $commentId);
                        }
                    });
                })
                ->delete();

            if ($post->cover_image) {
                Storage::disk('public')->delete($post->cover_image);
            }

            foreach ($post->tags as $tag) {
                Tag::whereKey($tag->id)->decrement('post_count');
            }

            $post->tags()->detach();

            Comment::withoutEvents(function () use ($post) {
                Comment::withTrashed()
                    ->where('post_id', $post->id)
                    ->forceDelete();
            });

            $post->forceDelete();
        });
    }
}
