<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        // 1. Cek apakah user sudah login
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated. Silakan login terlebih dahulu.',
            ], 401);
        }

        // 2. Load relasi role jika belum di-load (untuk efisiensi query)
        if (!$user->relationLoaded('role')) {
            $user->load('role');
        }

        // 3. Ambil nama role user (sesuaikan dengan nama kolom di tabel roles Anda)
        // Biasanya 'name_role' atau 'name' atau 'role_name'
        $userRole = $user->role->name_role ?? $user->role->name ?? $user->role->role_name ?? null;

        // 4. Cek apakah role user ada di dalam daftar role yang diizinkan
        if (!$userRole || !in_array($userRole, $roles)) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. Anda tidak memiliki akses untuk fitur ini. (Required: ' . implode(', ', $roles) . ')',
            ], 403);
        }

        return $next($request);
    }
}