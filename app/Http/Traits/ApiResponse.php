<?php

namespace App\Http\Traits;

use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Format respons standar: { success, message, data, errors }.
 * Endpoint list mengembalikan array polos di `data` (sesuai kontrak frontend),
 * metadata pagination dikirim lewat header X-Total-Count / X-Current-Page / X-Per-Page / X-Last-Page.
 */
trait ApiResponse
{
    protected int $maxPerPage = 200;

    protected function list($queryOrItems = [], string $message = 'OK', ?Request $request = null): JsonResponse
    {
        $request ??= request();

        if ($queryOrItems instanceof BuilderContract) {
            if ($request->filled('per_page') || $request->filled('page')) {
                $perPage = max(1, min($this->maxPerPage, (int) $request->input('per_page', 25)));
                $page = max(1, (int) $request->input('page', 1));
                $paginator = $queryOrItems->paginate($perPage, ['*'], 'page', $page);

                return $this->listResponse($paginator->items(), $paginator->total(), $page, $perPage, $message);
            }

            $queryOrItems = $queryOrItems->get();
        }

        $items = collect($queryOrItems)->values();

        return $this->listResponse($items, $items->count(), 1, max(1, $items->count()), $message);
    }

    private function listResponse($items, int $total, int $page, int $perPage, string $message): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $items,
            'errors' => null,
        ])->withHeaders([
            'X-Total-Count' => $total,
            'X-Current-Page' => $page,
            'X-Per-Page' => $perPage,
            'X-Last-Page' => (int) max(1, ceil($total / max(1, $perPage))),
            'Access-Control-Expose-Headers' => 'X-Total-Count, X-Current-Page, X-Per-Page, X-Last-Page',
        ]);
    }

    protected function success($data = null, string $message = 'OK', int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'errors' => null,
        ], $code);
    }

    protected function error(string $message, int $code = 422, $errors = null): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => null,
            'errors' => $errors,
        ], $code);
    }

    /**
     * Terapkan sort yang di-whitelist dari query ?orderBy=&order=.
     */
    protected function applySort($query, Request $request, array $allowed, string $default, string $direction = 'desc'): void
    {
        $column = $request->input('orderBy', $request->input('sort_by'));
        $column = in_array($column, $allowed, true) ? $column : $default;
        $dir = strtolower((string) $request->input('order', $request->input('sort_dir', $direction)));

        $query->orderBy($column, $dir === 'asc' ? 'asc' : 'desc');
    }
}
