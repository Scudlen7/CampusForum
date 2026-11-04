<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Notification;
use App\Models\Post;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CommentApiController extends Controller
{
    public function __construct(private NotificationService $notifications) {}

    public function index(Post $post)
    {
        abort_unless($post->isVisible(), 404);

        $comments = Comment::query()
            ->with('user:id,username,nickname,is_certified')
            ->where('post_id', $post->id)
            ->visible()
            ->whereNull('parent_id')
            ->with(['replies' => fn ($q) => $q->visible()->where('post_id', $post->id)
                ->with(['user:id,username,nickname,is_certified', 'replyTo' => fn ($target) => $target->visible()
                    ->where('post_id', $post->id)->with('user:id,username,nickname')])
                ->orderBy('created_at')])
            ->orderBy('created_at')
            ->get();

        return response()->json([
            'data' => $comments->map(fn (Comment $c) => $this->transform($c, true)),
        ]);
    }

    public function store(Request $request, Post $post)
    {
        abort_unless($post->isVisible(), 404);

        $data = $request->validate([
            'content' => ['required', 'string', 'max:2000'],
            'parent_id' => ['nullable', 'integer', 'min:1'],
            'reply_to_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $parentId = $data['parent_id'] ?? null;
        $replyToId = $data['reply_to_id'] ?? null;
        $parent = $parentId === null ? null : $post->comments()->visible()->whereNull('parent_id')->find($parentId);
        if ($parentId !== null && ! $parent) {
            throw ValidationException::withMessages(['parent_id' => '只能回复当前帖子中可见的评论。']);
        }

        $replyTarget = null;
        if ($replyToId !== null) {
            $replyTarget = $parent ? $post->comments()->visible()
                ->where(fn ($query) => $query->whereKey($parent->id)->orWhere('parent_id', $parent->id))
                ->with('user:id,username,nickname')
                ->find($replyToId) : null;
            if (! $replyTarget) {
                throw ValidationException::withMessages(['reply_to_id' => '回复目标必须属于当前评论串。']);
            }
        }

        $comment = Comment::create([
            'post_id' => $post->id,
            'user_id' => $request->user()->id,
            'parent_id' => $data['parent_id'] ?? null,
            'reply_to_id' => $data['reply_to_id'] ?? null,
            'content' => $data['content'],
            'status' => Comment::STATUS_NORMAL,
        ]);

        $type = $comment->parent_id
            ? Notification::TYPE_REPLY
            : Notification::TYPE_COMMENT;

        $this->notifications->notify(
            $post->user_id,
            $type,
            $request->user()->id,
            Post::class,
            $post->id,
            ['post_title' => $post->title, 'comment_id' => $comment->id]
        );

        if ($replyTarget && $replyTarget->user_id !== $post->user_id) {
            $this->notifications->notify(
                $replyTarget->user_id,
                Notification::TYPE_REPLY,
                $request->user()->id,
                Comment::class,
                $comment->id,
                ['post_title' => $post->title]
            );
        }

        return response()->json(['data' => $this->transform($comment->load('user')->setRelation('replyTo', $replyTarget))], 201);
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
            'parent_id' => $comment->parent_id,
            'reply_to_id' => $comment->reply_to_id,
            'reply_to' => $comment->relationLoaded('replyTo') ? $comment->replyTo?->user?->displayName() : null,
            'created_at' => $comment->created_at?->toDateTimeString(),
        ];

        if ($withReplies) {
            $data['replies'] = $comment->replies?->map(fn (Comment $r) => $this->transform($r))->all() ?? [];
        }

        return $data;
    }
}
