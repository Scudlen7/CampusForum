<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Post;
use App\Models\StudentCertification;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ForumUpgradeTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_combines_filters_and_paginates_visible_results(): void
    {
        $this->seed();
        $sample = Post::visible()->firstOrFail();
        $tag = Tag::create(['name' => '回归测试', 'slug' => 'regression']);
        $create = function (string $title, bool $hidden = false) use ($sample, $tag) {
            $post = Post::create([
                'user_id' => $sample->user_id,
                'category_id' => $sample->category_id,
                'title' => $title,
                'content' => '测试正文',
                'status' => Post::STATUS_NORMAL,
                'is_hidden' => $hidden,
            ]);
            $post->tags()->attach($tag);

            return $post;
        };
        $older = $create('入门 100% 一');
        $newer = $create('入门 100% 二');
        $create('入门 1000');
        $create('入门 100% 隐藏', true);

        $filters = http_build_query([
            'q' => '100%',
            'tag' => $tag->name,
            'category' => $sample->category->slug,
            'per_page' => 1,
        ]);

        $this->getJson('/api/posts?'.$filters)->assertOk()
            ->assertJsonPath('total', 2)->assertJsonPath('last_page', 2)->assertJsonPath('data.0.id', $newer->id);
        $this->getJson('/api/posts?'.$filters.'&page=2')->assertOk()
            ->assertJsonPath('current_page', 2)->assertJsonPath('data.0.id', $older->id);
        $this->getJson('/api/posts?category=unknown')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_only_admins_can_preview_material_and_each_application_is_reviewed_once(): void
    {
        $this->seed();
        $student = User::factory()->create();
        $admin = User::where('username', 'admin')->firstOrFail();
        Storage::fake('local')->put('certs/test.png', 'test image');

        $application = StudentCertification::create([
            'user_id' => $student->id,
            'student_no' => '2026001',
            'image_path' => 'certs/test.png',
            'status' => StudentCertification::STATUS_PENDING,
        ]);
        $url = "/admin/api/certifications/{$application->id}";

        $this->actingAs($student)->getJson($url.'/image')->assertForbidden();
        $preview = $this->actingAs($admin)->get($url.'/image')->assertOk();
        $this->assertStringContainsString('no-store', $preview->headers->get('Cache-Control'));
        $preview->assertStreamedContent('test image');
        $this->postJson($url.'/approve')->assertOk();
        $this->postJson($url.'/reject', ['reject_reason' => '旧请求'])->assertConflict();
        $this->assertTrue($student->fresh()->is_certified);
        $this->assertSame(StudentCertification::STATUS_APPROVED, $application->fresh()->status);
        $this->assertDatabaseHas('admin_logs', ['action' => 'approve_cert', 'target_id' => $application->id]);
    }

    public function test_report_queue_deduplicates_pending_reports_and_records_the_resolution(): void
    {
        $this->seed();
        $post = Post::visible()->firstOrFail();
        $student = User::factory()->create();
        $admin = User::where('username', 'admin')->firstOrFail();
        $comment = $post->comments()->create([
            'user_id' => $post->user_id,
            'content' => '被举报内容',
            'status' => Comment::STATUS_NORMAL,
        ]);
        $payload = ['type' => 'comment', 'id' => $comment->id, 'reason' => 1, 'description' => '补充说明'];

        $this->actingAs($student)->postJson('/api/reports', $payload)->assertOk();
        $this->postJson('/api/reports', $payload)->assertOk();
        $this->assertSame(1, $comment->reports()->count());
        $report = $comment->reports()->firstOrFail();
        $url = "/admin/api/reports/{$report->id}";

        $this->getJson($url)->assertForbidden();
        $this->actingAs($admin)->getJson($url)->assertOk()
            ->assertJsonPath('description', '补充说明')->assertJsonPath('content', '被举报内容');
        $this->postJson($url.'/handle', ['status' => 1, 'hide' => true, 'reason' => '审核后隐藏'])->assertOk();
        $this->postJson($url.'/handle', ['status' => 2])->assertConflict();
        $this->getJson($url)->assertOk()->assertJsonPath('resolution_note', '审核后隐藏');
        $this->assertSame(Comment::STATUS_PENDING, $comment->fresh()->status);
        $this->assertDatabaseHas('reports', ['id' => $report->id, 'status' => 1, 'handled_by' => $admin->id]);
    }
}
