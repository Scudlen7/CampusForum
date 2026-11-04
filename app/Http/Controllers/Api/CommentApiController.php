<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Post;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class CommentApiController extends Controller
{
    public function __construct(private NotificationService $notifications) {}

    public function index(Post $post)
    {
        $comments = Comment::query()
            ->with(['user:id,username,nickname,is_certified', 'replyTo.user:id,username,nickname'])
            ->where('post_id', $post->id)
            ->visible()
            ->whereNull('parent_id')
            ->with(['replies' => fn ($q) => $q->visible()->with(['user:id,username,nickname,is_certified', 'replyTo.user:id,username,nickname'])->orderBy('created_at')])
            ->orderBy('created_at')
            ->get();

        return response()->json([
            'data' => $comments->map(fn (Comment $c) => $this->transform($c, true)),
        ]);
    }

    public function store(Request $request, Post $post)
    {
        $data = $request->validate([
            'content' => ['required', 'string', 'max:2000'],
            'parent_id' => ['nullable', 'integer', 'exists:comments,id'],
            'reply_to_id' => ['nullable', 'integer', 'exists:comments,id'],
        ]);

        $comment = Comment::create([
            'post_id' => $post->id,
            'user_id' => $request->user()->id,
            'parent_id' => $data['parent_id'] ?? null,
            'reply_to_id' => $data['reply_to_id'] ?? null,
            'content' => $data['content'],
            'status' => Comment::STATUS_NORMAL,
        ]);

        $type = $comment->parent_id
            ? \App\Models\Notification::TYPE_REPLY
            : \App\Models\Notification::TYPE_COMMENT;

        $this->notifications->notify(
            $post->user_id,
            $type,
            $request->user()->id,
            Post::class,
            $post->id,
            ['post_title' => $post->title, 'comment_id' => $comment->id]
        );

        if ($comment->reply_to_id) {
            $replyTarget = Comment::find($comment->reply_to_id);
            if ($replyTarget) {
                $this->notifications->notify(
                    $replyTarget->user_id,
                    \App\Models\Notification::TYPE_REPLY,
                    $request->user()->id,
                    Comment::class,
                    $comment->id,
                    ['post_title' => $post->title]
                );
            }
        }

        return response()->json(['data' => $this->transform($comment->load('user'))], 201);
    }

    private function transform(Comment $comment, bool $withReplies = false): array
    {
        $data = [
            'id' => $comment->id,
            'content' => $comment->content,
            'like_count' => $comment->like_count,
            'author' => $comment->user?->displayName(),
            'author_username' => $comment->user?->username,
            'author_certified' => (bool) $comment->user?->is_certified,
            'reply_to' => $comment->replyTo?->user?->displayName(),
            'created_at' => $comment->created_at?->toDateTimeString(),
        ];

        if ($withReplies) {
            $data['replies'] = $comment->replies?->map(fn (Comment $r) => $this->transform($r))->all() ?? [];
        }

        return $data;
    }
}
