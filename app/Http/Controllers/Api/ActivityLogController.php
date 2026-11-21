<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = ActivityLog::with('user');

        if ($request->filled('user_id')) $query->where('user_id', $request->user_id);
        if ($request->filled('action'))  $query->where('action', $request->action);
        if ($request->filled('start_date')) $query->where('timestamp', '>=', $request->start_date);
        if ($request->filled('end_date'))   $query->where('timestamp', '<=', $request->end_date);

        $query->orderBy('timestamp', 'desc');

        return $this->list($query->get());
    }

    public function show($id)
    {
        return $this->success(ActivityLog::with('user')->findOrFail($id));
    }
}