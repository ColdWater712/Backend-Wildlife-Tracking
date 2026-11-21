<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogActivityMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $user = $request->user();
        if ($user && in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            try {
                \App\Models\ActivityLog::create([
                    'user_id'   => $user->user_id,
                    'action'    => strtoupper($request->method()) . ' ' . $request->path(),
                    'details'   => json_encode($request->all()),
                    'timestamp' => now(),
                ]);
            } catch (\Exception $e) {
                \Log::error('Activity log failed: ' . $e->getMessage());
            }
        }

        return $response;
    }
}