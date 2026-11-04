<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Report;
use App\Models\StudentCertification;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $weekAgo = now()->subDays(7);

        $activeUserIds = collect()
            ->merge(Post::where('created_at', '>=', $weekAgo)->pluck('user_id'))
            ->merge(Comment::where('created_at', '>=', $weekAgo)->pluck('user_id'))
            ->unique()
            ->filter()
            ->count();

        return view('admin.dashboard', [
            'pageScript' => 'admin-dashboard',
            'pageProps' => $this->pageProps($request, [
                'pageTitle' => '后台管理',
                'activeMenu' => 'admin',
                'stats' => [
                    'users' => User::count(),
                    'new_users' => User::where('created_at', '>=', $weekAgo)->count(),
                    'active_users' => $activeUserIds,
                    'posts' => Post::count(),
                    'comments' => Comment::count(),
                    'pending_reports' => Report::where('status', Report::STATUS_PENDING)->count(),
                    'pending_certs' => StudentCertification::where('status', StudentCertification::STATUS_PENDING)->count(),
                ],
            ]),
        ]);
    }
}
