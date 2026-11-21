<?php

namespace Database\Seeders;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();

        if ($users->isEmpty()) {
            return;
        }

        // ✅ DETEKSI NILAI ENUM YANG VALID DARI DATABASE
        $enumValues = $this->getEnumValues('notifications', 'notification_type');
        
        // Jika tidak bisa detect ENUM, gunakan nilai default yang umum
        if (empty($enumValues)) {
            $enumValues = ['info', 'success', 'warning', 'error'];
        }

        // Ambil nilai pertama, kedua, ketiga dari ENUM (atau fallback)
        $type1 = $enumValues[0] ?? 'info';
        $type2 = $enumValues[1] ?? 'success';
        $type3 = $enumValues[2] ?? 'warning';

        // Deteksi kolom yang ada
        $columns = Schema::getColumnListing('notifications');

        $notificationsData = [
            [
                'user_id'           => $users->first()->user_id,
                'notification_type' => $type1,
                'title'             => 'Selamat datang di Wildlife Tracking System',
                'subject'           => 'Selamat datang di Wildlife Tracking System',
                'message'           => 'Sistem monitoring satwa TNGL telah aktif.',
                'content'           => 'Sistem monitoring satwa TNGL telah aktif.',
                'body'              => 'Sistem monitoring satwa TNGL telah aktif.',
                'is_read'           => false,
                'read'              => false,
            ],
            [
                'user_id'           => $users->first()->user_id,
                'notification_type' => $type2,
                'title'             => 'Baterai perangkat rendah',
                'subject'           => 'Baterai perangkat rendah',
                'message'           => 'Perangkat GPS-001 memiliki baterai 15%.',
                'content'           => 'Perangkat GPS-001 memiliki baterai 15%.',
                'body'              => 'Perangkat GPS-001 memiliki baterai 15%.',
                'is_read'           => false,
                'read'              => false,
            ],
            [
                'user_id'           => $users->skip(1)->first()?->user_id ?? $users->first()->user_id,
                'notification_type' => $type3,
                'title'             => 'Insiden konflik terdeteksi',
                'subject'           => 'Insiden konflik terdeteksi',
                'message'           => 'Harimau Sumatra terdeteksi di area perkebunan.',
                'content'           => 'Harimau Sumatra terdeteksi di area perkebunan.',
                'body'              => 'Harimau Sumatra terdeteksi di area perkebunan.',
                'is_read'           => false,
                'read'              => false,
            ],
        ];

        foreach ($notificationsData as $data) {
            $filteredData = array_intersect_key($data, array_flip($columns));
            $filteredData = array_filter($filteredData, function($value) {
                return $value !== null;
            });
            
            Notification::create($filteredData);
        }
    }

    /**
     * Helper: Ambil nilai ENUM dari kolom database
     */
    protected function getEnumValues(string $table, string $column): array
    {
        try {
            $result = DB::select("SHOW COLUMNS FROM `{$table}` WHERE Field = '{$column}'");
            
            if (empty($result)) {
                return [];
            }

            $type = $result[0]->Type ?? '';
            
            // Parse ENUM values dari string seperti "enum('value1','value2','value3')"
            if (preg_match("/^enum\((.+)\)$/", $type, $matches)) {
                $enumString = $matches[1];
                $values = str_getcsv($enumString, ',', "'");
                return array_map('trim', $values);
            }

            return [];
        } catch (\Exception $e) {
            return [];
        }
    }
}