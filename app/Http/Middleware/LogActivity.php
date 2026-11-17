<?php

namespace App\Http\Middleware;

use App\Models\ActivityLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogActivity
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->user() && $response->isSuccessful() && $this->isWriteAction($request)) {
            ActivityLog::create([
                'user_id' => $request->user()->user_id,
                'action' => $this->deriveAction($request),
                'details' => json_encode([
                    'method' => $request->method(),
                    'path' => $request->path(),
                    'status' => $response->getStatusCode(),
                ]),
                'timestamp' => now(),
            ]);
        }

        return $response;
    }

    private function isWriteAction(Request $request): bool
    {
        return in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true);
    }

    private function deriveAction(Request $request): string
    {
        $path = $request->path();
        $method = $request->method();

        return match (true) {
            str_contains($path, 'login') => 'LOGIN',
            str_contains($path, 'logout') => 'LOGOUT',
            $method === 'POST' => 'CREATE',
            in_array($method, ['PUT', 'PATCH']) => 'UPDATE',
            $method === 'DELETE' => 'DELETE',
            default => 'API_ACCESS',
        };
    }
}