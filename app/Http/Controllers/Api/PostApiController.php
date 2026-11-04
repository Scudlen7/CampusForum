<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Observers\PostObserver;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PostApiController extends Controller
{
    public function index(Request $request)
    {
        $query = Post::query()
            ->with(['user:id,username,nickname,is_certified', 'category:id,name,slug', 'tags:id,name'])
            ->visible();

        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:128'],
            'tag' => ['nullable', 'string', 'max:32'],
            'category' => ['nullable', 'string', 'max:255'],
        ]);

        if (! empty($filters['category'])) {
            $query->whereHas('category', fn ($category) => $category
                ->where('slug', $filters['category'])->where('is_active', true));
        }

        $keyword = trim($filters['q'] ?? '');
        if ($keyword !== '') {
            $pattern = '%'.strtr($keyword, ['!' => '!!', '%' => '!%', '_' => '!_']).'%';
            $query->whereRaw("title LIKE ? ESCAPE '!'", [$pattern]);
        }

        $tag = trim($filters['tag'] ?? '');
        if ($tag !== '') {
            $query->whereHas('tags', fn ($tags) => $tags->where('name', $tag));
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }

        if ($request->boolean('pinned')) {
            $query->where('is_pinned', true);
        }

        if ($request->boolean('hot')) {
            $query->orderByDesc('like_count')->orderByDesc('comment_count');
        } else {
            $query->orderByDesc('is_pinned')->orderByDesc('created_at');
        }

        $perPage = max(1, min($request->integer('per_page', 20), 50));
        $paginator = $query->orderByDesc('id')->paginate($perPage);

        return response()->json([
            'data' => $paginator->getCollection()->map(fn (Post $post) => $this->transform($post)),
            'total' => $paginator->total(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'title' => ['required', 'string', 'max:128'],
            'content' => ['required', 'string'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:32'],
        ]);

        $category = Category::findOrFail($data['category_id']);

        $post = Post::create([
            'user_id' => $request->user()->id,
            'category_id' => $category->id,
            'type' => Post::typeFromCategory($category),
            'title' => $data['title'],
            'content' => $data['content'],
            'status' => Post::STATUS_NORMAL,
        ]);

        $this->syncTags($post, $data['tags'] ?? []);

        return response()->json(['data' => $this->transform($post->load('user', 'category', 'tags'))], 201);
    }

    public function update(Request $request, Post $post)
    {
        abort_unless($post->isVisible() || $request->user()->isAdmin(), 404);

        if ($post->user_id !== $request->user()->id && ! $request->user()->isAdmin()) {
            abort(403);
        }

        $data = $request->validate([
            'category_id' => ['sometimes', 'integer', 'exists:categories,id'],
            'title' => ['sometimes', 'string', 'max:128'],
            'content' => ['sometimes', 'string'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:32'],
        ]);

        $oldTagIds = $post->tags()->pluck('tags.id')->all();
        $updateData = collect($data)->except('tags')->all();

        if (array_key_exists('category_id', $updateData)) {
            $category = Category::findOrFail($updateData['category_id']);
            $updateData['type'] = Post::typeFromCategory($category);
        }

        $post->update($updateData);

        if (array_key_exists('tags', $data)) {
            $this->syncTags($post, $data['tags'] ?? []);
            PostObserver::syncTagCounts($post, $oldTagIds, $post->tags()->pluck('tags.id')->all());
        }

        return response()->json(['data' => $this->transform($post->fresh()->load('user', 'category', 'tags'))]);
    }

    private function syncTags(Post $post, array $tagNames): void
    {
        $tagIds = [];
        foreach ($tagNames as $name) {
            $name = trim($name);
            if ($name === '') {
                continue;
            }
            $slug = Str::slug($name);
            if ($slug === '') {
                $slug = md5($name);
            }
            $tag = Tag::firstOrCreate(
                ['slug' => $slug],
                ['name' => $name, 'post_count' => 0]
            );
            $tagIds[] = $tag->id;
        }

        $oldTagIds = $post->tags()->pluck('tags.id')->all();
        $post->tags()->sync($tagIds);
        PostObserver::syncTagCounts($post, $oldTagIds, $tagIds);
    }

    private function transform(Post $post): array
    {
        return [
            'id' => $post->id,
            'title' => $post->title,
            'category' => $post->category?->name,
            'category_slug' => $post->category?->slug,
            'author' => $post->user?->displayName(),
            'author_username' => $post->user?->username,
            'author_certified' => (bool) $post->user?->is_certified,
            'view_count' => $post->view_count,
            'like_count' => $post->like_count,
            'comment_count' => $post->comment_count,
            'is_pinned' => $post->is_pinned,
            'created_at' => $post->created_at?->toDateTimeString(),
            'tags' => $post->tags?->pluck('name')->all() ?? [],
        ];
    }
}
