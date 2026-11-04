<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class RegisterController extends Controller
{
    public function showRegistrationForm(Request $request)
    {
        if ($request->user()) {
            return redirect()->route('home');
        }

        return view('auth.register', [
            'pageScript' => 'auth-register',
            'pageProps' => $this->pageProps($request, [
                'pageTitle' => '注册 - 华东师范大学校园论坛',
                'errors' => session('errors')?->getMessages() ?? [],
                'old' => old(),
            ]),
        ]);
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'min:3', 'max:32', 'alpha_dash', Rule::unique('users', 'username')],
            'email' => ['required', 'email', 'max:128', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'nickname' => ['nullable', 'string', 'max:32'],
        ]);

        $user = User::create([
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'nickname' => $data['nickname'] ?? $data['username'],
            'role' => User::ROLE_STUDENT,
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('home')->with('success', '注册成功');
    }
}
