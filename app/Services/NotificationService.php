<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;

class NotificationService
{
    public const FIELD_ROLES = ['konservasionis_lapangan', 'administrator_sistem'];
    public const RESEARCH_ROLES = ['peneliti_ekologi', 'administrator_sistem'];

    /**
     * Kirim notifikasi ke semua user aktif dengan role tertentu.
     * Notifikasi identik dalam $dedupeMinutes terakhir tidak dikirim ulang.
     *
     * @param  array<int, string>  $roles
     * @return int jumlah notifikasi yang dibuat
     */
    public function notifyRoles(array $roles, string $type, string $message, array $refs = [], int $dedupeMinutes = 30): int
    {
        $users = User::query()
            ->where('status', 'aktif')
            ->whereHas('role', fn ($q) => $q->whereIn('name_role', $roles))
            ->pluck('user_id');

        $created = 0;
        foreach ($users as $userId) {
            $created += $this->notifyUser($userId, $type, $message, $refs, $dedupeMinutes) ? 1 : 0;
        }

        return $created;
    }

    public function notifyUser(int $userId, string $type, string $message, array $refs = [], int $dedupeMinutes = 30): bool
    {
        $message = mb_substr($message, 0, 500);

        if ($dedupeMinutes > 0) {
            $exists = Notification::query()
                ->where('user_id', $userId)
                ->where('notification_type', $type)
                ->where('message', $message)
                ->where('created_at', '>=', now()->subMinutes($dedupeMinutes))
                ->exists();

            if ($exists) {
                return false;
            }
        }

        Notification::create(array_merge([
            'user_id' => $userId,
            'notification_type' => $type,
            'message' => $message,
            'is_read' => false,
            'created_at' => now(),
        ], array_intersect_key($refs, array_flip(['animal_id', 'zone_id', 'device_id', 'report_id', 'prediction_id']))));

        return true;
    }
}
