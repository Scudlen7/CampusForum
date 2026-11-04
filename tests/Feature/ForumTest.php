<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ForumTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_loads(): void
    {
        $this->seed();

        $response = $this->get('/');

        $response->assertOk();
    }

    public function test_user_can_register_and_login(): void
    {
        $this->seed();

        $response = $this->post('/register', [
            'username' => 'newuser',
            'email' => 'newuser@ecnu.edu.cn',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'nickname' => '新用户',
        ]);

        $response->assertRedirect('/');

        $this->assertAuthenticated();

        $this->post('/logout');
        $this->assertGuest();

        $this->post('/login', [
            'login' => 'newuser',
            'password' => 'password123',
        ])->assertRedirect('/');
    }

    public function test_authenticated_user_can_create_post_via_api(): void
    {
        $this->seed();

        $user = User::where('username', 'demo_student')->first();
        $this->actingAs($user);

        $response = $this->postJson('/api/posts', [
            'category_id' => 3,
            'title' => '测试帖子 API',
            'content' => '这是 **Markdown** 测试内容',
            'tags' => ['测试'],
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('posts', ['title' => '测试帖子 API']);
    }

    public function test_user_can_like_post(): void
    {
        $this->seed();

        $user = User::where('username', 'demo_student')->first();
        $post = Post::first();
        $this->actingAs($user);

        $this->postJson('/api/likes/toggle', [
            'type' => 'post',
            'id' => $post->id,
        ])->assertOk()->assertJson(['liked' => true]);

        $this->postJson('/api/likes/toggle', [
            'type' => 'post',
            'id' => $post->id,
        ])->assertOk()->assertJson(['liked' => false]);
    }

    public function test_banned_user_cannot_access_forum_create(): void
    {
        $this->seed();

        $user = User::where('username', 'demo_student')->first();
        $user->update(['is_banned' => true]);
        $this->actingAs($user);

        $this->get('/posts/create')->assertRedirect('/login');
    }
}
