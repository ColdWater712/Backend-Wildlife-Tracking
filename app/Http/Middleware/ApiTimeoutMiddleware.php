<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiTimeoutMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        
        if ($user && isset($user->last_active_at)) {
            $lastActive = \Carbon\Carbon::parse($user->last_active_at);
            $timeoutMinutes = config('session.lifetime', 120);
            
            if ($lastActive->diffInMinutes(now()) > $timeoutMinutes) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sesi Anda telah berakhir. Silakan login kembali.',
                ], 401);
            }
            
            // Update last active
            $user->update(['last_active_at' => now()]);
        }

        return $next($request);
    }
}