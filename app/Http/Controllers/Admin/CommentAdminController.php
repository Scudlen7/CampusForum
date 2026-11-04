<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Services\AdminLogService;
use App\Services\CommentDeletionService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CommentAdminController extends Controller
{
    public function __construct(
        private AdminLogService $adminLog,
        private CommentDeletionService $commentDeletion,
    ) {}

    public function index(Request $request)
    {
        return view('admin.comments', [
            'pageScript' => 'admin-comments',
            'pageProps' => $this->pageProps($request, ['pageTitle' => '评论管理', 'activeMenu' => 'admin']),
        ]);
    }

    public function apiIndex(Request $request)
    {
        $comments = Comment::with(['user:id,username', 'post:id,title'])->orderByDesc('created_at')->paginate(20);

        return response()->json([
            'data' => $comments->getCollection()->map(fn (Comment $c) => [
                'id' => $c->id,
                'content' => Str::limit($c->content, 80),
                'author' => $c->user?->username,
                'post_title' => $c->post?->title,
                'status' => $c->status,
                'status_label' => $c->status === Comment::STATUS_NORMAL ? '正常' : '已隐藏',
                'created_at' => $c->created_at?->toDateTimeString(),
            ]),
            'total' => $comments->total(),
        ]);
    }

    public function toggleHidden(Request $request, Comment $comment)
    {
        $comment->update([
            'status' => $comment->status === Comment::STATUS_NORMAL
                ? Comment::STATUS_DELETED
                : Comment::STATUS_NORMAL,
        ]);

        $this->adminLog->log($request, 'toggle_hidden_comment', Comment::class, $comment->id);

        return response()->json(['message' => '已更新', 'status' => $comment->status]);
    }

    public function destroy(Request $request, Comment $comment)
    {
        $commentId = $comment->id;

        $this->commentDeletion->forceDelete($comment);
        $this->adminLog->log($request, 'delete_comment', Comment::class, $commentId);

        return response()->json(['message' => '评论及关联数据已永久删除']);
    }
}
