<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\Like;
use App\Models\Notification;
use App\Models\Post;
use App\Models\Report;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CommentDeletionService
{
    public function forceDelete(Comment $comment): void
    {
        DB::transaction(function () use ($comment) {
            $commentIds = $this->collectCommentIds($comment);
            $postId = $comment->post_id;

            Like::query()
                ->where('likeable_type', Comment::class)
                ->whereIn('likeable_id', $commentIds)
                ->delete();

            Report::query()
                ->where('reportable_type', Comment::class)
                ->whereIn('reportable_id', $commentIds)
                ->delete();

            Notification::query()
                ->where(function ($query) use ($commentIds) {
                    $query->where(function ($query) use ($commentIds) {
                        $query->where('notifiable_type', Comment::class)
                            ->whereIn('notifiable_id', $commentIds);
                    });

                    foreach ($commentIds as $commentId) {
                        $query->orWhere('data->comment_id', $commentId);
                    }
                })
                ->delete();

            Comment::withoutEvents(function () use ($commentIds) {
                Comment::withTrashed()
                    ->whereIn('id', $commentIds)
                    ->forceDelete();
            });

            Post::whereKey($postId)->decrement('comment_count', $commentIds->count());
        });
    }

    private function collectCommentIds(Comment $comment): Collection
    {
        $ids = collect([$comment->id]);

        if ($comment->parent_id === null) {
            $replyIds = Comment::withTrashed()
                ->where('parent_id', $comment->id)
                ->pluck('id');

            $ids = $ids->merge($replyIds);
        }

        return $ids->unique()->values();
    }
}
