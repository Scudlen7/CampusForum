<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => '学习打卡', 'slug' => 'checkin', 'icon' => '📚', 'sort_order' => 1],
            ['name' => '技术求助', 'slug' => 'tech-help', 'icon' => '🆘', 'sort_order' => 2],
            ['name' => '技术讨论', 'slug' => 'tech-discussion', 'icon' => '💬', 'sort_order' => 3],
            ['name' => '项目展示', 'slug' => 'showcase', 'icon' => '🚀', 'sort_order' => 4],
            ['name' => '校园生活', 'slug' => 'campus-life', 'icon' => '🏫', 'sort_order' => 5],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['slug' => $category['slug']],
                [
                    'name' => $category['name'],
                    'icon' => $category['icon'],
                    'sort_order' => $category['sort_order'],
                    'description' => $category['name'].'分类',
                    'is_active' => true,
                ]
            );
        }
    }
}
