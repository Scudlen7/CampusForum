<?php

namespace App\Observers;

use App\Models\Comment;
use App\Models\Post;

class CommentObserver
{
    public function created(Comment $comment): void
    {
        Post::whereKey($comment->post_id)->increment('comment_count');
        Post::whereKey($comment->post_id)->update(['last_comment_at' => now()]);
    }

    public function deleted(Comment $comment): void
    {
        Post::whereKey($comment->post_id)->decrement('comment_count');
    }
}
