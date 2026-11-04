<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Like;
use App\Models\Notification;
use App\Models\Post;
use App\Models\Report;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPostDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_force_delete_post_removes_all_related_data(): void
    {
        $this->seed();

        $admin = User::where('username', 'admin')->first();
        $author = User::where('username', 'demo_student')->first();
        $post = Post::firstOrFail();
        $tag = Tag::firstOrFail();

        $post->tags()->syncWithoutDetaching([$tag->id]);

        $comment = Comment::create([
            'post_id' => $post->id,
            'user_id' => $author->id,
            'content' => '待删除评论',
            'status' => Comment::STATUS_NORMAL,
        ]);

        Like::create([
            'user_id' => $author->id,
            'likeable_type' => Post::class,
            'likeable_id' => $post->id,
        ]);

        Like::create([
            'user_id' => $admin->id,
            'likeable_type' => Comment::class,
            'likeable_id' => $comment->id,
        ]);

        Report::create([
            'reporter_id' => $author->id,
            'reportable_type' => Post::class,
            'reportable_id' => $post->id,
            'reason' => Report::REASON_OTHER,
            'status' => Report::STATUS_PENDING,
        ]);

        Notification::create([
            'user_id' => $author->id,
            'type' => Notification::TYPE_COMMENT,
            'notifiable_type' => Post::class,
            'notifiable_id' => $post->id,
            'actor_id' => $admin->id,
            'data' => ['post_title' => $post->title, 'comment_id' => $comment->id],
            'is_read' => false,
        ]);

        $postId = $post->id;
        $commentId = $comment->id;

        $this->actingAs($admin)
            ->deleteJson("/admin/api/posts/{$postId}")
            ->assertOk()
            ->assertJson(['message' => '帖子及关联数据已永久删除']);

        $this->assertDatabaseMissing('posts', ['id' => $postId]);
        $this->assertDatabaseMissing('comments', ['id' => $commentId]);
        $this->assertDatabaseMissing('likes', [
            'likeable_type' => Post::class,
            'likeable_id' => $postId,
        ]);
        $this->assertDatabaseMissing('likes', [
            'likeable_type' => Comment::class,
            'likeable_id' => $commentId,
        ]);
        $this->assertDatabaseMissing('reports', [
            'reportable_type' => Post::class,
            'reportable_id' => $postId,
        ]);
        $this->assertDatabaseMissing('notifications', [
            'notifiable_type' => Post::class,
            'notifiable_id' => $postId,
        ]);
        $this->assertDatabaseMissing('post_tag', [
            'post_id' => $postId,
            'tag_id' => $tag->id,
        ]);
    }
}
