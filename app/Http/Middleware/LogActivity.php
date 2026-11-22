<?php

namespace App\Http\Middleware;

use App\Models\ActivityLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mencatat setiap request tulis (POST/PUT/PATCH/DELETE) yang berhasil ke activity_log.
 * Isi body tidak dicatat agar password/token tidak bocor ke log.
 */
class LogActivity
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->user() && $response->isSuccessful() && $this->isWriteAction($request)) {
            ActivityLog::record($request->user()->user_id, $this->deriveAction($request), [
                'method' => $request->method(),
                'path' => $request->path(),
                'status' => $response->getStatusCode(),
                'ip' => $request->ip(),
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

        return match (true) {
            str_contains($path, 'logout') => 'LOGOUT',
            str_ends_with($path, '/verify') => 'VERIFY',
            $request->isMethod('POST') => 'CREATE',
            $request->isMethod('DELETE') => 'DELETE',
            default => 'UPDATE',
        };
    }
}
