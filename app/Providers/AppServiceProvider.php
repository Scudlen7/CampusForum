<?php

namespace App\Providers;

use App\Models\Comment;
use App\Models\Like;
use App\Models\Post;
use App\Observers\CommentObserver;
use App\Observers\LikeObserver;
use App\Observers\PostObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Comment::observe(CommentObserver::class);
        Like::observe(LikeObserver::class);
        Post::observe(PostObserver::class);
    }
}
