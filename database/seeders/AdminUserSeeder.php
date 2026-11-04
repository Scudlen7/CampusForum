<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@ecnu.edu.cn'],
            [
                'username' => 'admin',
                'password' => Hash::make('admin123456'),
                'nickname' => '管理员',
                'role' => User::ROLE_ADMIN,
                'is_certified' => true,
                'email_verified_at' => now(),
            ]
        );
    }
}
