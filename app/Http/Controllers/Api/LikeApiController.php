<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Like;
use App\Models\Notification;
use App\Models\Post;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LikeApiController extends Controller
{
    public function __construct(private NotificationService $notifications) {}

    public function toggle(Request $request)
    {
        $data = $request->validate([
            'type' => ['required', 'in:post,comment'],
            'id' => ['required', 'integer'],
        ]);

        $model = $data['type'] === 'post'
            ? Post::query()->visible()->findOrFail($data['id'])
            : Comment::query()->visibleOnForum()->findOrFail($data['id']);

        $like = Like::where([
            'user_id' => $request->user()->id,
            'likeable_type' => $model::class,
            'likeable_id' => $model->id,
        ])->first();

        if ($like) {
            $like->delete();
            $liked = false;
        } else {
            Like::create([
                'user_id' => $request->user()->id,
                'likeable_type' => $model::class,
                'likeable_id' => $model->id,
            ]);
            $liked = true;

            if ($model instanceof Post) {
                $this->notifications->notify(
                    $model->user_id,
                    Notification::TYPE_POST_LIKE,
                    $request->user()->id,
                    Post::class,
                    $model->id,
                    ['post_title' => $model->title]
                );
            } else {
                $this->notifications->notify(
                    $model->user_id,
                    Notification::TYPE_COMMENT_LIKE,
                    $request->user()->id,
                    Comment::class,
                    $model->id,
                    ['content' => Str::limit($model->content, 50)]
                );
            }
        }

        return response()->json([
            'liked' => $liked,
            'like_count' => $model->fresh()->like_count,
        ]);
    }
}
