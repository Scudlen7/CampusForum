<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AdminLogService;
use Illuminate\Http\Request;

class UserAdminController extends Controller
{
    public function __construct(private AdminLogService $adminLog) {}

    public function index(Request $request)
    {
        return view('admin.users', [
            'pageScript' => 'admin-users',
            'pageProps' => $this->pageProps($request, ['pageTitle' => '用户管理', 'activeMenu' => 'admin']),
        ]);
    }

    public function apiIndex(Request $request)
    {
        $users = User::query()->orderByDesc('created_at')->paginate(20);

        return response()->json([
            'data' => $users->getCollection()->map(fn (User $u) => [
                'id' => $u->id,
                'username' => $u->username,
                'email' => $u->email,
                'nickname' => $u->nickname,
                'role' => $u->role,
                'is_banned' => $u->is_banned,
                'is_certified' => $u->is_certified,
                'created_at' => $u->created_at?->toDateTimeString(),
            ]),
            'total' => $users->total(),
        ]);
    }

    public function ban(Request $request, User $user)
    {
        $user->update(['is_banned' => true, 'banned_until' => null]);
        $this->adminLog->log($request, 'ban_user', User::class, $user->id);

        return response()->json(['message' => '已封禁']);
    }

    public function unban(Request $request, User $user)
    {
        $user->update(['is_banned' => false, 'banned_until' => null]);
        $this->adminLog->log($request, 'unban_user', User::class, $user->id);

        return response()->json(['message' => '已解封']);
    }
}
