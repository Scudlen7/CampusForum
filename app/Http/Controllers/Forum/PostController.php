<?php

namespace App\Http\Controllers\Forum;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PostController extends Controller
{
    public function forumIndex(Request $request, ?string $slug = null)
    {
        $category = null;
        if ($slug) {
            $category = Category::where('slug', $slug)->where('is_active', true)->firstOrFail();
        }

        return view('forum.index', [
            'pageScript' => 'forum-index',
            'pageProps' => $this->pageProps($request, [
                'pageTitle' => ($category?->name ?? '论坛').' - 华东师范大学校园论坛',
                'activeMenu' => 'forum',
                'categorySlug' => $category?->slug,
                'categoryName' => $category?->name,
            ]),
        ]);
    }

    public function show(Request $request, Post $post)
    {
        abort_unless($post->isVisible(), 404);

        $sessionKey = 'viewed_post_'.$post->id;
        if (! $request->session()->has($sessionKey)) {
            $post->increment('view_count');
            $request->session()->put($sessionKey, true);
        }

        $post->load(['user', 'category', 'tags']);

        return view('forum.show', [
            'pageScript' => 'post-show',
            'pageProps' => $this->pageProps($request, [
                'pageTitle' => $post->title,
                'activeMenu' => 'forum',
                'post' => [
                    'id' => $post->id,
                    'title' => $post->title,
                    'content' => $post->content,
                    'content_html' => Str::markdown($post->content, ['html_input' => 'strip', 'allow_unsafe_links' => false]),
                    'author' => $post->user->displayName(),
                    'author_username' => $post->user->username,
                    'author_certified' => $post->user->is_certified,
                    'category' => $post->category->name,
                    'category_slug' => $post->category->slug,
                    'view_count' => $post->view_count,
                    'like_count' => $post->like_count,
                    'comment_count' => $post->comment_count,
                    'created_at' => $post->created_at->toDateTimeString(),
                    'tags' => $post->tags->pluck('name'),
                    'is_owner' => $request->user()?->id === $post->user_id,
                ],
            ]),
        ]);
    }

    public function create(Request $request)
    {
        return view('forum.create', [
            'pageScript' => 'post-create',
            'pageProps' => $this->pageProps($request, [
                'pageTitle' => '发帖 - 华东师范大学校园论坛',
                'activeMenu' => 'forum',
                'mode' => 'create',
            ]),
        ]);
    }

    public function edit(Request $request, Post $post)
    {
        abort_unless($post->isVisible() || $request->user()->isAdmin(), 404);

        if ($post->user_id !== $request->user()->id && ! $request->user()->isAdmin()) {
            abort(403);
        }

        $post->load('tags');

        return view('forum.edit', [
            'pageScript' => 'post-edit',
            'pageProps' => $this->pageProps($request, [
                'pageTitle' => '编辑帖子 - 华东师范大学校园论坛',
                'activeMenu' => 'forum',
                'mode' => 'edit',
                'post' => [
                    'id' => $post->id,
                    'title' => $post->title,
                    'content' => $post->content,
                    'category_id' => $post->category_id,
                    'tags' => $post->tags->pluck('name')->implode(','),
                ],
            ]),
        ]);
    }
}
