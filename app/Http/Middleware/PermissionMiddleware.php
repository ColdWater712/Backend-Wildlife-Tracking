<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    public function handle(Request $request, Closure $next, ...$permissions): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        // Get user's role permissions
        $userPermissions = $user->role?->permissions?->pluck('name')->toArray() ?? [];

        foreach ($permissions as $permission) {
            if (!in_array($permission, $userPermissions)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki izin: ' . $permission,
                ], 403);
            }
        }

        return $next($request);
    }
}