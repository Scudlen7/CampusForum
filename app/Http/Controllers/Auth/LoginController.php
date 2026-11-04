<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function showLoginForm(Request $request)
    {
        if ($request->user()) {
            return redirect()->route('home');
        }

        return view('auth.login', [
            'pageScript' => 'auth-login',
            'pageProps' => $this->pageProps($request, [
                'pageTitle' => '登录 - 华东师范大学校园论坛',
                'errors' => session('errors')?->getMessages() ?? [],
                'old' => old(),
                'redirect' => session('url.intended', route('home')),
            ]),
        ]);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $field = filter_var($credentials['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        if (! Auth::attempt([$field => $credentials['login'], 'password' => $credentials['password']], $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'login' => ['用户名/邮箱或密码错误'],
            ]);
        }

        $request->session()->regenerate();

        if ($request->user()->isBannedNow()) {
            Auth::logout();
            throw ValidationException::withMessages([
                'login' => ['账号已被封禁'],
            ]);
        }

        return redirect()->intended(route('home'))->with('success', '登录成功');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
