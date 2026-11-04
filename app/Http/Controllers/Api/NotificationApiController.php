<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationApiController extends Controller
{
    public function index(Request $request)
    {
        $items = Notification::query()
            ->where('user_id', $request->user()->id)
            ->with('actor:id,username,nickname')
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json([
            'data' => $items->getCollection()->map(fn (Notification $n) => [
                'id' => $n->id,
                'type' => $n->type,
                'type_label' => Notification::TYPE_LABELS[$n->type] ?? '通知',
                'actor' => $n->actor?->displayName(),
                'data' => $n->data,
                'is_read' => $n->is_read,
                'created_at' => $n->created_at?->toDateTimeString(),
            ]),
            'total' => $items->total(),
        ]);
    }

    public function unreadCount(Request $request)
    {
        $count = Notification::where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->count();

        return response()->json(['count' => $count]);
    }

    public function markRead(Request $request)
    {
        Notification::where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json(['message' => 'ok']);
    }
}
