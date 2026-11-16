<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        // ✅ Hanya tampilkan notifikasi user yang login
        $notifications = Notification::where('user_id', $request->user()->user_id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $notifications,
        ], 200);
    }

    public function markAsRead(Request $request, $id)
    {
        $notification = Notification::findOrFail($id);

        // ✅ CEK IDOR: Hanya pemilik yang bisa tandai dibaca
        if ($notification->user_id !== $request->user()->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke notifikasi ini.',
            ], 403);
        }

        $notification->update(['is_read' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Notifikasi ditandai sudah dibaca',
            'data' => $notification,
        ], 200);
    }
}