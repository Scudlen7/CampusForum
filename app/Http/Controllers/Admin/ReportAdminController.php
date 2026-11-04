<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminLog;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Report;
use App\Services\AdminLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        $reports = Report::with('reporter:id,username')->orderBy('status')->orderByDesc('id')->paginate(20);

        return response()->json([
            'data' => $reports->getCollection()->map(fn (Report $r) => [
                'id' => $r->id,
                'reporter' => $r->reporter?->username,
                'reason' => Report::REASON_LABELS[$r->reason] ?? $r->reason,
                'status' => $r->status,
                'created_at' => $r->created_at?->toDateTimeString(),
            ]),
            'total' => $reports->total(),
            'last_page' => $reports->lastPage(),
        ]);
    }

    public function show(Report $report)
    {
        $target = $report->reportable;
        $log = AdminLog::where('action', 'handle_report')
            ->where('target_type', Report::class)->where('target_id', $report->id)
            ->latest('id')->first();

        return response()->json([
            'reason' => Report::REASON_LABELS[$report->reason] ?? $report->reason,
            'description' => $report->description,
            'target_type' => $report->reportable_type === Post::class ? '帖子' : '评论',
            'target_id' => $report->reportable_id,
            'title' => $target instanceof Post ? $target->title : $target?->post?->title,
            'content' => $target?->content,
            'resolution_note' => $log?->detail['reason'] ?? null,
        ]);
    }

    public function handle(Request $request, Report $report)
    {
        $data = $request->validate([
            'status' => ['required', 'in:1,2'],
            'hide' => ['sometimes', 'boolean'],
            'reason' => ['required_with:hide', 'nullable', 'string', 'max:255'],
        ]);
        $status = (int) $data['status'];
        $hide = $request->boolean('hide');
        abort_if($hide && $status !== Report::STATUS_HANDLED, 422, '驳回举报时不能隐藏内容');

        DB::transaction(function () use ($request, $report, $data, $status, $hide) {
            $report = Report::lockForUpdate()->findOrFail($report->id);
            abort_unless($report->status === Report::STATUS_PENDING, 409, '该举报已处理');

            if ($hide) {
                $target = $report->reportable;
                abort_unless($target, 409, '被举报内容已不存在，可选择结束处理');
                if ($target instanceof Post) {
                    $target->update(['is_hidden' => true]);
                } elseif ($target instanceof Comment && $target->status === Comment::STATUS_NORMAL) {
                    $target->update(['status' => Comment::STATUS_PENDING]);
                }
            }

            $report->update([
                'status' => $status,
                'handled_by' => $request->user()->id,
                'handled_at' => now(),
            ]);
            $this->adminLog->log($request, 'handle_report', Report::class, $report->id, [
                'status' => $status,
                'hide' => $hide,
                'reason' => $data['reason'] ?? null,
            ]);
        });

        return response()->json(['message' => '已处理']);
    }
}
