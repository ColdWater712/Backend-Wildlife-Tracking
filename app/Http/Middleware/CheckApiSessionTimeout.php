<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class CheckApiSessionTimeout
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user) {
            // Ambil instance token yang sedang digunakan untuk request ini
            $token = $user->currentAccessToken();

            if ($token instanceof PersonalAccessToken) {
                // Gunakan last_used_at, jika null gunakan created_at
                $lastUsedTimestamp = $token->last_used_at 
                    ? $token->last_used_at->timestamp 
                    : $token->created_at->timestamp;
                
                $twoHoursAgoTimestamp = now()->subHours(2)->timestamp;

                // Jika terakhir digunakan lebih dari 2 jam yang lalu
                if ($lastUsedTimestamp < $twoHoursAgoTimestamp) {
                    // 1. Revoke (hapus) token dari database agar tidak bisa dipakai lagi
                    $token->delete();

                    // 2. Kembalikan response 401 dengan pesan yang jelas
                    return response()->json([
                        'success' => false,
                        'message' => 'Sesi Anda telah berakhir karena tidak ada aktivitas selama 2 jam. Silakan login kembali.',
                        'data' => null,
                        'errors' => null,
                    ], 401);
                }
            }
        }

        return $next($request);
    }
}