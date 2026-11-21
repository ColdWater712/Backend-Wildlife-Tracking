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
        $query = Notification::where('user_id', $request->user()->user_id);

        if ($request->filled('is_read')) $query->where('is_read', $request->is_read);
        if ($request->filled('type')) $query->where('type', $request->type);

        $query->orderBy('created_at', 'desc');

        return $this->list($query, 'OK', $request);
    }

    public function show($id)
    {
        $notification = Notification::where('user_id', request()->user()->user_id)->findOrFail($id);
        return $this->success($notification);
    }

    public function markAsRead($id)
    {
        $notification = Notification::where('user_id', request()->user()->user_id)->findOrFail($id);
        $notification->update(['is_read' => true]);
        return $this->success($notification->fresh(), 'Notifikasi ditandai sudah dibaca');
    }

    public function markAllAsRead(Request $request)
    {
        Notification::where('user_id', $request->user()->user_id)
            ->where('is_read', false)
            ->update(['is_read' => true]);
        return $this->success(null, 'Semua notifikasi ditandai sudah dibaca');
    }

    public function unreadCount(Request $request)
    {
        $count = Notification::where('user_id', $request->user()->user_id)
            ->where('is_read', false)
            ->count();
        return $this->success(['count' => $count]);
    }

    public function destroy($id)
    {
        $notification = Notification::where('user_id', request()->user()->user_id)->findOrFail($id);
        $notification->delete();
        return $this->success(null, 'Notifikasi dihapus');
    }

    public function deleteRead(Request $request)
    {
        Notification::where('user_id', $request->user()->user_id)
            ->where('is_read', true)
            ->delete();
        return $this->success(null, 'Notifikasi yang sudah dibaca dihapus');
    }
}