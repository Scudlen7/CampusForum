<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $student = User::updateOrCreate(
            ['email' => 'student@ecnu.edu.cn'],
            [
                'username' => 'demo_student',
                'password' => Hash::make('password123'),
                'nickname' => '示例同学',
                'college' => '计算机科学与技术学院',
                'major' => '软件工程',
                'grade' => 2022,
                'is_certified' => true,
                'email_verified_at' => now(),
            ]
        );

        $tag = Tag::firstOrCreate(
            ['slug' => 'laravel'],
            ['name' => 'Laravel', 'post_count' => 0]
        );

        $posts = [
            [
                'title' => 'Laravel学习第7天',
                'category_slug' => 'checkin',
                'content' => "## 今日学习\n\n完成了 **Eloquent 关联** 的学习。",
            ],
            [
                'title' => 'Vue和React怎么选？',
                'category_slug' => 'tech-discussion',
                'content' => "大家在做校内项目时更倾向哪个框架？\n\n```js\nconsole.log('hello');\n```",
            ],
            [
                'title' => '食堂出了新菜品',
                'category_slug' => 'campus-life',
                'content' => '河东食堂二楼新开了麻辣香锅，味道不错！',
            ],
        ];

        foreach ($posts as $index => $data) {
            $category = Category::where('slug', $data['category_slug'])->first();
            if (! $category) {
                continue;
            }

            $post = Post::updateOrCreate(
                ['title' => $data['title'], 'user_id' => $student->id],
                [
                    'category_id' => $category->id,
                    'type' => Post::typeFromCategory($category),
                    'content' => $data['content'],
                    'status' => Post::STATUS_NORMAL,
                    'is_pinned' => $index === 0,
                    'view_count' => rand(10, 200),
                    'like_count' => rand(0, 50),
                    'comment_count' => 0,
                ]
            );

            $post->tags()->syncWithoutDetaching([$tag->id]);
        }

        Tag::whereKey($tag->id)->update(['post_count' => Post::whereHas('tags', fn ($q) => $q->where('tags.id', $tag->id))->count()]);
    }
}
