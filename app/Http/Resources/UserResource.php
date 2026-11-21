<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'user_id' => $this->user_id,
            'name' => $this->name,
            'email' => $this->email,
            // Pastikan relasi di-load sebelum memanggil ini, atau gunakan whenLoaded
            'role' => $this->whenLoaded('role', function () {
                return [
                    'role_id' => $this->role->role_id,
                    'role_name' => $this->role->role_name,
                ];
            }),
            'created_at' => $this->created_at,
        ];
    }
}