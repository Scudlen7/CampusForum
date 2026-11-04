<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StudentCertification;
use App\Services\AdminLogService;
use Illuminate\Http\Request;

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
            ->orderByDesc('created_at')
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
        ]);
    }

    public function approve(Request $request, StudentCertification $certification)
    {
        $certification->update([
            'status' => StudentCertification::STATUS_APPROVED,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'reject_reason' => null,
        ]);
        $certification->user->update(['is_certified' => true]);
        $this->adminLog->log($request, 'approve_cert', StudentCertification::class, $certification->id);

        return response()->json(['message' => '已通过']);
    }

    public function reject(Request $request, StudentCertification $certification)
    {
        $data = $request->validate(['reject_reason' => ['required', 'string', 'max:255']]);
        $certification->update([
            'status' => StudentCertification::STATUS_REJECTED,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'reject_reason' => $data['reject_reason'],
        ]);
        $this->adminLog->log($request, 'reject_cert', StudentCertification::class, $certification->id);

        return response()->json(['message' => '已拒绝']);
    }
}
