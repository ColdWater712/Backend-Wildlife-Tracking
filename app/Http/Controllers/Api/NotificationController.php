<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = Notification::with(['animal', 'zone', 'device'])
            ->where('user_id', $request->user()->user_id);

        if ($request->filled('notification_type') && $request->notification_type !== 'all') {
            $query->where('notification_type', $request->notification_type);
        }
        if ($request->has('is_read')) {
            $query->where('is_read', filter_var($request->is_read, FILTER_VALIDATE_BOOLEAN));
        }

        $query->orderBy('created_at', 'desc');

        // ✅ FE notification.service pakai unwrapData → array (lalu .filter)
        return $this->list($query->get());
    }

    public function show(Request $request, $id)
    {
        return $this->success(
            Notification::where('user_id', $request->user()->user_id)->findOrFail($id)
        );
    }

    public function markAsRead(Request $request, $id)
    {
        $n = Notification::where('user_id', $request->user()->user_id)->findOrFail($id);
        $n->update(['is_read' => true]);
        return $this->success($n->fresh(), 'Notifikasi ditandai sudah dibaca');
    }

    public function markAllAsRead(Request $request)
    {
        $count = Notification::where('user_id', $request->user()->user_id)
            ->where('is_read', false)->update(['is_read' => true]);
        return $this->success(['updated' => $count], 'Semua notifikasi ditandai sudah dibaca');
    }

    public function destroy(Request $request, $id)
    {
        $n = Notification::where('user_id', $request->user()->user_id)->findOrFail($id);
        $n->delete();
        return $this->success(null, 'Notifikasi dihapus');
    }

    public function deleteRead(Request $request)
    {
        $count = Notification::where('user_id', $request->user()->user_id)
            ->where('is_read', true)->delete();
        return $this->success(['deleted' => $count], 'Notifikasi yang sudah dibaca dihapus');
    }
}