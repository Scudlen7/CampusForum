<?php

namespace App\Services;

use App\Models\AdminLog;
use Illuminate\Http\Request;

class AdminLogService
{
    public function log(Request $request, string $action, ?string $targetType = null, ?int $targetId = null, array $detail = []): void
    {
        AdminLog::create([
            'admin_id' => $request->user()->id,
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'detail' => $detail,
            'ip' => $request->ip(),
        ]);
    }
}
