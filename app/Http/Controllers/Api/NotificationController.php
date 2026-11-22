<?php

namespace App\Http\Controllers\Api;

use App\Models\Notification;
use Illuminate\Http\Request;

/** Notifikasi dibuat oleh sistem; user hanya bisa membaca & mengelola miliknya sendiri. */
class NotificationController extends ApiController
{
    public function index(Request $request)
    {
        $query = Notification::query()
            ->with(['animal', 'zone', 'device'])
            ->where('user_id', $request->user()->user_id);

        if ($request->filled('is_read')) {
            $query->where('is_read', $request->boolean('is_read'));
        }
        if ($request->boolean('onlyUnread') || $request->boolean('unread')) {
            $query->where('is_read', false);
        }
        if ($request->filled('type') || $request->filled('notification_type')) {
            $query->where('notification_type', $request->input('notification_type', $request->input('type')));
        }

        return $this->list($query->orderByDesc('created_at')->orderByDesc('notif_id'));
    }

    public function show(Request $request, int $id)
    {
        return $this->success($this->own($request, $id)->load(['animal', 'zone', 'device', 'report', 'prediction']));
    }

    public function unreadCount(Request $request)
    {
        return $this->success([
            'unread' => Notification::where('user_id', $request->user()->user_id)->where('is_read', false)->count(),
        ]);
    }

    public function markAsRead(Request $request, int $id)
    {
        $notification = $this->own($request, $id);
        $notification->update(['is_read' => true]);

        return $this->success($notification->fresh(), 'Notifikasi ditandai dibaca');
    }

    public function markAllAsRead(Request $request)
    {
        $count = Notification::where('user_id', $request->user()->user_id)->where('is_read', false)->update(['is_read' => true]);

        return $this->success(['updated' => $count], 'Semua notifikasi ditandai dibaca');
    }

    public function destroy(Request $request, int $id)
    {
        $this->own($request, $id)->delete();

        return $this->success(null, 'Notifikasi dihapus');
    }

    public function deleteRead(Request $request)
    {
        $count = Notification::where('user_id', $request->user()->user_id)->where('is_read', true)->delete();

        return $this->success(['deleted' => $count], 'Notifikasi yang sudah dibaca dihapus');
    }

    private function own(Request $request, int $id): Notification
    {
        return Notification::where('user_id', $request->user()->user_id)->findOrFail($id);
    }
}
