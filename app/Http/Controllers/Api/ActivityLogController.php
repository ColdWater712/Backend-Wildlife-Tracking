<?php

namespace App\Http\Controllers\Api;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

/** Audit log bersifat append-only: hanya bisa dibaca melalui API. */
class ActivityLogController extends ApiController
{
    public function index(Request $request)
    {
        $query = ActivityLog::query()->with('user');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }
        if ($request->filled('start_date')) {
            $query->where('timestamp', '>=', $request->date('start_date'));
        }
        if ($request->filled('end_date')) {
            $query->where('timestamp', '<=', $request->date('end_date')->endOfDay());
        }

        return $this->list($query->orderByDesc('timestamp')->orderByDesc('log_id'));
    }

    public function show(int $id)
    {
        return $this->success(ActivityLog::with('user')->findOrFail($id));
    }
}
