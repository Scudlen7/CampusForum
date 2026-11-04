<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Like;
use App\Models\Notification;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCommentDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_force_delete_top_level_comment_removes_replies_and_related_data(): void
    {
        $this->seed();

        $admin = User::where('username', 'admin')->first();
        $author = User::where('username', 'demo_student')->first();
        $post = Post::firstOrFail();

        $parentComment = Comment::create([
            'post_id' => $post->id,
            'user_id' => $author->id,
            'content' => '待删除的一级评论',
            'status' => Comment::STATUS_NORMAL,
        ]);

        $reply = Comment::create([
            'post_id' => $post->id,
            'user_id' => $admin->id,
            'parent_id' => $parentComment->id,
            'reply_to_id' => $parentComment->id,
            'content' => '待删除的回复',
            'status' => Comment::STATUS_NORMAL,
        ]);

        Like::create([
            'user_id' => $author->id,
            'likeable_type' => Comment::class,
            'likeable_id' => $parentComment->id,
        ]);

        Like::create([
            'user_id' => $admin->id,
            'likeable_type' => Comment::class,
            'likeable_id' => $reply->id,
        ]);

        Report::create([
            'reporter_id' => $author->id,
            'reportable_type' => Comment::class,
            'reportable_id' => $parentComment->id,
            'reason' => Report::REASON_OTHER,
            'status' => Report::STATUS_PENDING,
        ]);

        Notification::create([
            'user_id' => $author->id,
            'type' => Notification::TYPE_REPLY,
            'notifiable_type' => Comment::class,
            'notifiable_id' => $reply->id,
            'actor_id' => $admin->id,
            'data' => ['post_title' => $post->title, 'comment_id' => $reply->id],
            'is_read' => false,
        ]);

        $parentId = $parentComment->id;
        $replyId = $reply->id;

        $this->actingAs($admin)
            ->deleteJson("/admin/api/comments/{$parentId}")
            ->assertOk()
            ->assertJson(['message' => '评论及关联数据已永久删除']);

        $this->assertDatabaseMissing('comments', ['id' => $parentId]);
        $this->assertDatabaseMissing('comments', ['id' => $replyId]);
        $this->assertDatabaseMissing('likes', [
            'likeable_type' => Comment::class,
            'likeable_id' => $parentId,
        ]);
        $this->assertDatabaseMissing('likes', [
            'likeable_type' => Comment::class,
            'likeable_id' => $replyId,
        ]);
        $this->assertDatabaseMissing('reports', [
            'reportable_type' => Comment::class,
            'reportable_id' => $parentId,
        ]);
        $this->assertDatabaseMissing('notifications', [
            'notifiable_type' => Comment::class,
            'notifiable_id' => $replyId,
        ]);
    }

    public function test_admin_force_delete_reply_only_removes_that_reply(): void
    {
        $this->seed();

        $admin = User::where('username', 'admin')->first();
        $author = User::where('username', 'demo_student')->first();
        $post = Post::firstOrFail();

        $parentComment = Comment::create([
            'post_id' => $post->id,
            'user_id' => $author->id,
            'content' => '保留的一级评论',
            'status' => Comment::STATUS_NORMAL,
        ]);

        $reply = Comment::create([
            'post_id' => $post->id,
            'user_id' => $admin->id,
            'parent_id' => $parentComment->id,
            'reply_to_id' => $parentComment->id,
            'content' => '单独删除的回复',
            'status' => Comment::STATUS_NORMAL,
        ]);

        $parentId = $parentComment->id;
        $replyId = $reply->id;

        $this->actingAs($admin)
            ->deleteJson("/admin/api/comments/{$replyId}")
            ->assertOk()
            ->assertJson(['message' => '评论及关联数据已永久删除']);

        $this->assertDatabaseHas('comments', ['id' => $parentId]);
        $this->assertDatabaseMissing('comments', ['id' => $replyId]);
    }
}
