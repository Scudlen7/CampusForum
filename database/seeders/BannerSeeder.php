<?php

namespace Database\Seeders;

use App\Models\Banner;
use Illuminate\Database\Seeder;

class BannerSeeder extends Seeder
{
    public function run(): void
    {
        Banner::updateOrCreate(
            ['title' => '欢迎加入华东师范大学校园论坛'],
            [
                'content' => '这里是学习交流、分享讨论、校园生活的社区平台。',
                'type' => Banner::TYPE_ANNOUNCEMENT,
                'is_active' => true,
                'sort_order' => 1,
            ]
        );

        Banner::updateOrCreate(
            ['title' => '编程马拉松报名通知'],
            [
                'content' => '校内编程马拉松即将开始，欢迎报名参加。',
                'type' => Banner::TYPE_CONTEST,
                'is_active' => true,
                'sort_order' => 2,
            ]
        );
    }
}
