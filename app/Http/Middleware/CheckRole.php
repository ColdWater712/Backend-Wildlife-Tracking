<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!$request->user()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Load relasi role jika belum di-load (untuk efisiensi)
        if (!$request->user()->relationLoaded('role')) {
            $request->user()->load('role');
        }

        // Cek apakah role_name user ada di dalam array $roles yang diizinkan
        if (!in_array($request->user()->role->role_name, $roles)) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses untuk fitur ini.'
            ], 403);
        }

        return $next($request);
    }
}