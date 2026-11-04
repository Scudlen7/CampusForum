<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

abstract class Controller
{
    protected function pageProps(Request $request, array $extra = []): array
    {
        $user = $request->user();
        $unreadCount = 0;

        if ($user) {
            $unreadCount = Notification::where('user_id', $user->id)
                ->where('is_read', false)
                ->count();
        }

        return array_merge([
            'pageTitle' => '华东师范大学校园论坛',
            'user' => $user ? [
                'id' => $user->id,
                'username' => $user->username,
                'nickname' => $user->nickname,
                'is_certified' => $user->is_certified,
            ] : null,
            'isAdmin' => $user?->isAdmin() ?? false,
            'unreadCount' => $unreadCount,
            'csrfToken' => csrf_token(),
            'flash' => [
                'success' => session('success'),
                'error' => session('error'),
            ],
        ], $extra);
    }

    protected function storeUploadedFile(UploadedFile $file, string $directory, string $disk = 'local'): string
    {
        $extension = $file->getClientOriginalExtension()
            ?: $file->guessExtension()
            ?: 'bin';
        $filename = Str::uuid().'.'.strtolower($extension);
        $path = trim($directory, '/').'/'.$filename;

        Storage::disk($disk)->put($path, $file->get());

        return $path;
    }
}
