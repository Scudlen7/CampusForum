<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Services\AdminLogService;
use Illuminate\Http\Request;

class ReportAdminController extends Controller
{
    public function __construct(private AdminLogService $adminLog) {}

    public function index(Request $request)
    {
        return view('admin.reports', [
            'pageScript' => 'admin-reports',
            'pageProps' => $this->pageProps($request, ['pageTitle' => '举报处理', 'activeMenu' => 'admin']),
        ]);
    }

    public function apiIndex(Request $request)
    {
        $reports = Report::with('reporter:id,username')->orderByDesc('created_at')->paginate(20);

        return response()->json([
            'data' => $reports->getCollection()->map(fn (Report $r) => [
                'id' => $r->id,
                'reporter' => $r->reporter?->username,
                'reason' => Report::REASON_LABELS[$r->reason] ?? $r->reason,
                'status' => $r->status,
                'created_at' => $r->created_at?->toDateTimeString(),
            ]),
            'total' => $reports->total(),
        ]);
    }

    public function handle(Request $request, Report $report)
    {
        $data = $request->validate(['status' => ['required', 'in:1,2']]);
        $report->update([
            'status' => (int) $data['status'],
            'handled_by' => $request->user()->id,
            'handled_at' => now(),
        ]);
        $this->adminLog->log($request, 'handle_report', Report::class, $report->id);

        return response()->json(['message' => '已处理']);
    }
}
