<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class CertificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_submit_certification(): void
    {
        $this->seed();

        $user = User::where('username', 'demo_student')->first();
        $this->actingAs($user);

        $response = $this->post('/user/certification', [
            'student_no' => '2022001001',
            'image' => UploadedFile::fake()->image('student-card.jpg'),
        ], [
            'Accept' => 'application/json',
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response->assertOk()->assertJson(['message' => '认证申请已提交']);
        $this->assertDatabaseHas('student_certifications', [
            'user_id' => $user->id,
            'student_no' => '2022001001',
            'status' => 0,
        ]);
    }

    public function test_user_cannot_resubmit_while_pending(): void
    {
        $this->seed();

        $user = User::where('username', 'demo_student')->first();
        $this->actingAs($user);

        $this->post('/user/certification', [
            'student_no' => '2022001001',
            'image' => UploadedFile::fake()->image('student-card.jpg'),
        ], [
            'Accept' => 'application/json',
            'X-Requested-With' => 'XMLHttpRequest',
        ])->assertOk();

        $this->post('/user/certification', [
            'student_no' => '2022001002',
            'image' => UploadedFile::fake()->image('student-card-2.jpg'),
        ], [
            'Accept' => 'application/json',
            'X-Requested-With' => 'XMLHttpRequest',
        ])
            ->assertStatus(422)
            ->assertJson(['message' => '认证申请审核中，请耐心等待']);
    }
}
