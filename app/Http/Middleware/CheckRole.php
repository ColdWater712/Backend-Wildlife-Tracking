<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

<<<<<<< HEAD
        // Load relasi role jika belum di-load (untuk efisiensi)
        if (!$request->user()->relationLoaded('role')) {
            $request->user()->load('role');
        }

        // Cek apakah role_name user ada di dalam array $roles yang diizinkan
        if (!in_array($request->user()->role->role_name, $roles)) {
=======
        if (!$user || !in_array($user->role, $roles, true)) {
>>>>>>> 8e7e8e56ec983b5e0342baf64cb79f61cc79443d
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses untuk aksi ini.',
                'data' => null,
                'errors' => null,
            ], 403);
        }

        return $next($request);
    }
}