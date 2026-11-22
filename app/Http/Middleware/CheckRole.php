<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pemakaian: ->middleware('role:administrator_sistem,peneliti_ekologi')
 * Nama role dibaca dari relasi users.role_id -> roles.name_role.
 */
class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated. Silakan login terlebih dahulu.',
                'data' => null,
                'errors' => null,
            ], 401);
        }

        if (! $user->hasRole(...$roles)) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses untuk fitur ini.',
                'data' => null,
                'errors' => null,
            ], 403);
        }

        return $next($request);
    }
}
