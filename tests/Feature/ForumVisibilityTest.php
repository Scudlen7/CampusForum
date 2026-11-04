<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ForumVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_hidden_post_cannot_be_read_or_interacted_with(): void
    {
        $this->seed();
        $post = Post::firstOrFail();
        $user = User::factory()->create();
        $comment = $post->comments()->create(['user_id' => $user->id, 'content' => 'Test comment', 'status' => 1]);
        $post->update(['is_hidden' => true]);

        $this->get("/posts/{$post->id}")->assertNotFound();
        $this->getJson("/api/posts/{$post->id}/comments")->assertNotFound();

        $this->actingAs($user);
        $this->postJson("/api/posts/{$post->id}/comments", ['content' => 'Blocked'])->assertNotFound();

        foreach (['post' => $post->id, 'comment' => $comment->id] as $type => $id) {
            $this->postJson('/api/likes/toggle', compact('type', 'id'))->assertNotFound();
            $this->postJson('/api/reports', compact('type', 'id') + ['reason' => 1])->assertNotFound();
        }
    }

    public function test_replies_must_reference_a_visible_thread_on_the_same_post(): void
    {
        $this->seed();
        [$post, $other] = Post::visible()->take(2)->get();
        $user = User::factory()->create();
        $foreign = $other->comments()->create(['user_id' => $user->id, 'content' => 'Other post', 'status' => 1]);
        $parent = $post->comments()->create(['user_id' => $user->id, 'content' => 'Thread', 'status' => 1]);
        $url = "/api/posts/{$post->id}/comments";

        $this->actingAs($user)->postJson($url, ['content' => 'Reply', 'parent_id' => $foreign->id])
            ->assertUnprocessable()->assertJsonValidationErrors('parent_id');
        $this->postJson($url, ['content' => 'Reply', 'parent_id' => $parent->id, 'reply_to_id' => $foreign->id])
            ->assertUnprocessable()->assertJsonValidationErrors('reply_to_id');
        $this->postJson($url, ['content' => 'Reply', 'parent_id' => $parent->id, 'reply_to_id' => $parent->id])
            ->assertCreated();
    }
}
