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
        $query = ActivityLog::with(['user']);

        if ($request->filled('user_id')) $query->where('user_id', $request->user_id);
        if ($request->filled('action')) $query->where('action', 'like', "%{$request->action}%");
        if ($request->filled('start_date')) $query->where('timestamp', '>=', $request->start_date);
        if ($request->filled('end_date')) $query->where('timestamp', '<=', $request->end_date);

        $query->orderBy('timestamp', 'desc');

        return $this->list($query, 'OK', $request);
    }

    public function show($id)
    {
        return $this->success(ActivityLog::with(['user'])->findOrFail($id));
    }

    public function store(Request $request)
    {
        $request->validate([
            'action'  => 'required|string',
            'details' => 'nullable|string',
        ]);

        $log = ActivityLog::create([
            'user_id'   => $request->user()->user_id,
            'action'    => $request->action,
            'details'   => $request->details,
            'timestamp' => now(),
        ]);

        return $this->success($log->load(['user']), 'Activity log berhasil dibuat', 201);
    }

    public function update(Request $request, $id)
    {
        $log = ActivityLog::findOrFail($id);
        $request->validate(['details' => 'nullable|string']);
        $log->update($request->only(['details']));
        return $this->success($log->fresh(['user']), 'Activity log diperbarui');
    }

    public function destroy($id)
    {
        ActivityLog::findOrFail($id)->delete();
        return $this->success(null, 'Activity log dihapus');
    }
}