<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Otorisasi berbasis tabel role_permissions.
 * Pemakaian: ->middleware('permission:create_data,update_data')  (cukup salah satu)
 */
class CheckPermission
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
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

        if (! $user->hasPermission(...$permissions)) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki izin untuk aksi ini.',
                'data' => null,
                'errors' => ['permission' => $permissions],
            ], 403);
        }

        return $next($request);
    }
}
