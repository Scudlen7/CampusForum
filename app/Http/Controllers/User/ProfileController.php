<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\StudentCertification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function show(Request $request, string $username)
    {
        $user = User::where('username', $username)->firstOrFail();

        return view('user.profile', [
            'pageScript' => 'user-profile',
            'pageProps' => $this->pageProps($request, [
                'pageTitle' => $user->displayName().' 的主页',
                'activeMenu' => 'profile',
                'profile' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'nickname' => $user->nickname,
                    'bio' => $user->bio,
                    'college' => $user->college,
                    'major' => $user->major,
                    'grade' => $user->grade,
                    'is_certified' => $user->is_certified,
                    'is_self' => $request->user()?->id === $user->id,
                ],
            ]),
        ]);
    }

    public function settings(Request $request)
    {
        $user = $request->user();

        return view('user.settings', [
            'pageScript' => 'user-settings',
            'pageProps' => $this->pageProps($request, [
                'pageTitle' => '个人设置',
                'activeMenu' => 'profile',
                'profile' => [
                    'username' => $user->username,
                    'email' => $user->email,
                    'nickname' => $user->nickname,
                    'bio' => $user->bio,
                    'college' => $user->college,
                    'major' => $user->major,
                    'grade' => $user->grade,
                    'is_certified' => $user->is_certified,
                ],
            ]),
        ]);
    }

    public function updateSettings(Request $request)
    {
        $data = $request->validate([
            'nickname' => ['nullable', 'string', 'max:32'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'college' => ['nullable', 'string', 'max:64'],
            'major' => ['nullable', 'string', 'max:64'],
            'grade' => ['nullable', 'integer', 'min:2000', 'max:2100'],
        ]);

        $request->user()->update($data);

        return redirect()->route('user.settings')->with('success', '资料已更新');
    }

    public function certification(Request $request)
    {
        $cert = $request->user()->certification;

        return view('user.certification', [
            'pageScript' => 'user-certification',
            'pageProps' => $this->pageProps($request, [
                'pageTitle' => '学号认证',
                'activeMenu' => 'profile',
                'certification' => $cert ? [
                    'student_no' => $cert->student_no,
                    'status' => $cert->status,
                    'reject_reason' => $cert->reject_reason,
                ] : null,
            ]),
        ]);
    }

    public function submitCertification(Request $request)
    {
        $user = $request->user();
        $existing = StudentCertification::where('user_id', $user->id)->first();

        if ($existing?->status === StudentCertification::STATUS_APPROVED) {
            return response()->json(['message' => '您已通过学生认证，无需重复提交'], 422);
        }

        if ($existing?->status === StudentCertification::STATUS_PENDING) {
            return response()->json(['message' => '认证申请审核中，请耐心等待'], 422);
        }

        $data = $request->validate([
            'student_no' => ['required', 'string', 'max:20'],
            'image' => ['required', 'file', 'image', 'max:5120'],
        ], [
            'student_no.required' => '请填写学号',
            'image.required' => '请上传学生证图片',
            'image.file' => '学生证图片必须是有效文件',
            'image.image' => '学生证图片格式不支持，请上传 JPG、PNG、GIF、BMP 或 WebP',
            'image.max' => '学生证图片不能超过 5MB',
        ]);

        $file = $request->file('image');
        if (! $file || ! $file->isValid()) {
            return response()->json(['message' => '请上传有效的学生证图片'], 422);
        }

        if ($existing?->image_path) {
            Storage::disk('local')->delete($existing->image_path);
        }

        $path = $this->storeUploadedFile($file, 'certs/'.$user->id, 'local');

        StudentCertification::updateOrCreate(
            ['user_id' => $user->id],
            [
                'student_no' => $data['student_no'],
                'image_path' => $path,
                'status' => StudentCertification::STATUS_PENDING,
                'reject_reason' => null,
                'reviewed_by' => null,
                'reviewed_at' => null,
            ]
        );

        return response()->json(['message' => '认证申请已提交']);
    }
}
