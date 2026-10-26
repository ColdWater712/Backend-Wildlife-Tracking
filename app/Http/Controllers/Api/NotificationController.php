<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = Notification::with(['user', 'animal', 'zone', 'device'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Data notifikasi berhasil diambil.',
            'data' => $notifications,
            'errors' => null
        ], 200);
    }

    public function unread(Request $request)
    {
        $notifications = Notification::with(['user', 'animal', 'zone', 'device'])
            ->where('user_id', $request->user()->user_id)
            ->where('is_read', false)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Notifikasi belum dibaca berhasil diambil.',
            'data' => $notifications,
            'errors' => null
        ], 200);
    }

    public function markAsRead($id)
    {
        $notification = Notification::find($id);

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notifikasi tidak ditemukan.',
                'data' => null,
                'errors' => null
            ], 404);
        }

        $notification->update(['is_read' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Notifikasi ditandai sebagai sudah dibaca.',
            'data' => $notification,
            'errors' => null
        ], 200);
    }
}