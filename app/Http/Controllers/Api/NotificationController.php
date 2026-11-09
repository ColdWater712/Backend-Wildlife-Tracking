<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        // Ambil notifikasi khusus untuk user yang login, urutkan dari terbaru
        $notifications = Notification::where('user_id', $request->user()->user_id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $notifications
        ], 200);
    }

    public function markAsRead($id)
    {
        // Cari notifikasi berdasarkan notif_id
        $notif = Notification::where('notif_id', $id)
            ->where('user_id', auth()->user()->user_id)
            ->first();

        if (!$notif) {
            return response()->json(['message' => 'Notifikasi tidak ditemukan'], 404);
        }

        $notif->is_read = true;
        $notif->save();

        return response()->json([
            'success' => true,
            'message' => 'Notifikasi ditandai sudah dibaca',
            'data' => $notif
        ], 200);
    }
}