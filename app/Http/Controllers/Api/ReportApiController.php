<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportApiController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'type' => ['required', 'in:post,comment'],
            'id' => ['required', 'integer'],
            'reason' => ['required', 'integer', 'between:1,5'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $created = DB::transaction(function () use ($request, $data) {
            $model = $data['type'] === 'post'
            ? Post::visible()->lockForUpdate()->findOrFail($data['id'])
            : Comment::visibleOnForum()->lockForUpdate()->findOrFail($data['id']);

            return $model->reports()->firstOrCreate([
                'reporter_id' => $request->user()->id,
                'status' => Report::STATUS_PENDING,
            ], [
                'reason' => $data['reason'],
                'description' => $data['description'] ?? null,
            ])->wasRecentlyCreated;
        });

        return response()->json(['message' => $created ? '举报已提交' : '已有待处理的举报']);
    }
}
