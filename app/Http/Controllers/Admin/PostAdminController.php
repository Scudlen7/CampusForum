<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Services\AdminLogService;
use App\Services\PostDeletionService;
use Illuminate\Http\Request;

class PostAdminController extends Controller
{
    public function __construct(
        private AdminLogService $adminLog,
        private PostDeletionService $postDeletion,
    ) {}

    public function index(Request $request)
    {
        return view('admin.posts', [
            'pageScript' => 'admin-posts',
            'pageProps' => $this->pageProps($request, ['pageTitle' => '帖子管理', 'activeMenu' => 'admin']),
        ]);
    }

    public function apiIndex(Request $request)
    {
        $posts = Post::with('user:id,username')->orderByDesc('created_at')->paginate(20);

        return response()->json([
            'data' => $posts->getCollection()->map(fn (Post $p) => [
                'id' => $p->id,
                'title' => $p->title,
                'author' => $p->user?->username,
                'status' => $p->status,
                'is_hidden' => $p->is_hidden,
                'is_pinned' => $p->is_pinned,
                'created_at' => $p->created_at?->toDateTimeString(),
            ]),
            'total' => $posts->total(),
        ]);
    }

    public function toggleHidden(Request $request, Post $post)
    {
        $post->update(['is_hidden' => ! $post->is_hidden]);
        $this->adminLog->log($request, 'toggle_hidden_post', Post::class, $post->id);

        return response()->json(['message' => 'ok', 'is_hidden' => $post->is_hidden]);
    }

    public function togglePinned(Request $request, Post $post)
    {
        $post->update(['is_pinned' => ! $post->is_pinned]);
        $this->adminLog->log($request, 'toggle_pinned_post', Post::class, $post->id);

        return response()->json(['message' => 'ok', 'is_pinned' => $post->is_pinned]);
    }

    public function destroy(Request $request, Post $post)
    {
        $postId = $post->id;

        $this->postDeletion->forceDelete($post);
        $this->adminLog->log($request, 'delete_post', Post::class, $postId);

        return response()->json(['message' => '帖子及关联数据已永久删除']);
    }
}
