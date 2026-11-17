<?php

namespace App\Http\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

trait ApiResponse
{
    // ✅ ARRAY telanjang di "data" — untuk FE yang pakai unwrapData()
    protected function list($items = [], string $message = 'OK'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $items,
            'errors'  => null,
        ]);
    }

    // ✅ {items, meta} di "data" — KHUSUS /users (FE user.service expect ini)
    protected function paginated(LengthAwarePaginator $paginator, string $message = 'OK'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => [
                'items' => $paginator->items(),
                'meta'  => [
                    'total'       => $paginator->total(),
                    'page'        => $paginator->currentPage(),
                    'per_page'    => $paginator->perPage(),
                    'total_pages' => $paginator->lastPage(),
                    'total_items' => $paginator->total(),
                ],
            ],
            'errors'  => null,
        ]);
    }

    protected function paginatedItems(array $items, array $meta, string $message = 'OK'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => ['items' => $items, 'meta' => $meta],
            'errors'  => null,
        ]);
    }

    protected function success($data = null, string $message = 'OK', int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $data,
            'errors'  => null,
        ], $code);
    }

    protected function error(string $message, int $code = 422, $errors = null): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data'    => null,
            'errors'  => $errors,
        ], $code);
    }

    // ✅ Bridge nama: FE baca username + full_name, DB punya name
    protected function presentUser($user): array
    {
        return [
            'user_id'    => $user->user_id,
            'username'   => $user->name,
            'full_name'  => $user->name,
            'name'       => $user->name,
            'email'      => $user->email,
            'role'       => $user->role,
            'role_id'    => $user->role_id,
            'created_at' => $user->created_at,
        ];
    }
}