<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Report;
use Illuminate\Http\Request;

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

        $model = $data['type'] === 'post'
            ? Post::findOrFail($data['id'])
            : Comment::findOrFail($data['id']);

        Report::create([
            'reporter_id' => $request->user()->id,
            'reportable_type' => $model::class,
            'reportable_id' => $model->id,
            'reason' => $data['reason'],
            'description' => $data['description'] ?? null,
            'status' => Report::STATUS_PENDING,
        ]);

        return response()->json(['message' => '举报已提交']);
    }
}
