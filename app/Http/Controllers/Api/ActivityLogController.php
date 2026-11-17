<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreActivityLogRequest;
use App\Http\Traits\ApiResponse;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = ActivityLog::with('user');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }
        if ($request->filled('start_date')) {
            $query->where('timestamp', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->where('timestamp', '<=', $request->end_date);
        }

        $query->orderBy('timestamp', 'desc');

        return $this->paginated($query->paginate($request->input('per_page', 25)));
    }

    public function show($id)
    {
        return $this->success(ActivityLog::with('user')->findOrFail($id));
    }

    public function store(StoreActivityLogRequest $request)
    {
        $log = ActivityLog::create($request->validated());
        return $this->success($log->load('user'), 'Log aktivitas dibuat', 201);
    }

    public function update(Request $request, $id)
    {
        $log = ActivityLog::findOrFail($id);
        $request->validate([
            'action' => 'sometimes|string|max:100',
            'details' => 'sometimes|nullable|string',
        ]);
        $log->update($request->validated());
        return $this->success($log->fresh(), 'Log diperbarui');
    }

    public function destroy($id)
    {
        ActivityLog::findOrFail($id)->delete();
        return $this->success(null, 'Log dihapus');
    }
}