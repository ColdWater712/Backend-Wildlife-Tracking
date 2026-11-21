<?php

namespace App\Http\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

trait ApiResponse
{
    /**
     * ✅ G-3: Return ARRAY telanjang (compatible FE unwrapData)
     * TAPI honor ?page & ?per_page untuk slicing server-side.
     * Tambah header X-Total-Count untuk pagination metadata.
     */
    protected function list($queryOrItems = [], string $message = 'OK', Request $request = null): JsonResponse
    {
        $items = $queryOrItems;

        if ($queryOrItems instanceof \Illuminate\Database\Eloquent\Builder) {
            if ($request && $request->has('per_page')) {
                $perPage = (int) $request->input('per_page', 25);
                $page = (int) $request->input('page', 1);
                $total = $queryOrItems->count();
                $items = $queryOrItems->forPage($page, $perPage)->get();
                return $this->listResponse($items, $total, $page, $perPage, $message);
            }
            $items = $queryOrItems->get();
        }

        return $this->listResponse($items, count($items), 1, count($items), $message);
    }

    private function listResponse($items, int $total, int $page, int $perPage, string $message): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $items,
            'errors'  => null,
        ])->withHeaders([
            'X-Total-Count' => $total,
            'X-Current-Page' => $page,
            'X-Per-Page' => $perPage,
        ]);
    }

    /**
     * ✅ Untuk /users saja (FE user.service expect {items, meta})
     */
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
            'status'     => $user->status ?? 'aktif',
            'created_at' => $user->created_at,
        ];
    }
}