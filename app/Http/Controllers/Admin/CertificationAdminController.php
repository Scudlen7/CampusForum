<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StudentCertification;
use App\Services\AdminLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CertificationAdminController extends Controller
{
    public function __construct(private AdminLogService $adminLog) {}

    public function index(Request $request)
    {
        return view('admin.certifications', [
            'pageScript' => 'admin-certifications',
            'pageProps' => $this->pageProps($request, ['pageTitle' => '认证审核', 'activeMenu' => 'admin']),
        ]);
    }

    public function apiIndex(Request $request)
    {
        $items = StudentCertification::with('user:id,username,nickname,email')
            ->orderBy('status')->orderByDesc('id')
            ->paginate(20);

        return response()->json([
            'data' => $items->getCollection()->map(fn (StudentCertification $c) => [
                'id' => $c->id,
                'username' => $c->user?->username,
                'student_no' => $c->student_no,
                'status' => $c->status,
                'created_at' => $c->created_at?->toDateTimeString(),
            ]),
            'total' => $items->total(),
            'last_page' => $items->lastPage(),
        ]);
    }

    public function image(StudentCertification $certification)
    {
        $disk = Storage::disk('local');
        $path = $certification->image_path;
        abort_unless($path && $disk->exists($path), 404);

        return $disk->response($path, null, [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function approve(Request $request, StudentCertification $certification)
    {
        DB::transaction(function () use ($request, $certification) {
            $certification = $this->pendingApplication($certification);
            $certification->update([
                'status' => StudentCertification::STATUS_APPROVED,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'reject_reason' => null,
            ]);
            $certification->user->update(['is_certified' => true]);
            $this->adminLog->log($request, 'approve_cert', StudentCertification::class, $certification->id);
        });

        return response()->json(['message' => '已通过']);
    }

    public function reject(Request $request, StudentCertification $certification)
    {
        $data = $request->validate(['reject_reason' => ['required', 'string', 'max:255']]);
        DB::transaction(function () use ($request, $certification, $data) {
            $certification = $this->pendingApplication($certification);
            $certification->update([
                'status' => StudentCertification::STATUS_REJECTED,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'reject_reason' => $data['reject_reason'],
            ]);
            $this->adminLog->log($request, 'reject_cert', StudentCertification::class, $certification->id);
        });

        return response()->json(['message' => '已拒绝']);
    }

    private function pendingApplication(StudentCertification $certification): StudentCertification
    {
        $application = StudentCertification::lockForUpdate()->findOrFail($certification->id);
        abort_unless($application->status === StudentCertification::STATUS_PENDING, 409, '该申请已审核');

        return $application;
    }
}
