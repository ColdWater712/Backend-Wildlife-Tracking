<?php

use App\Http\Middleware\CheckApiSessionTimeout;
use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\CheckRole;
use App\Http\Middleware\LogActivity;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => CheckRole::class,
            'permission' => CheckPermission::class,
            'log.activity' => LogActivity::class,
            'api.timeout' => CheckApiSessionTimeout::class,
        ]);

        $middleware->redirectGuestsTo(fn () => null);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $json = fn (string $message, int $status, $errors = null) => response()->json([
            'success' => false,
            'message' => $message,
            'data' => null,
            'errors' => $errors,
        ], $status);

        $isApi = fn (Request $request) => $request->is('api/*') || $request->expectsJson();

        $exceptions->render(function (ValidationException $e, Request $request) use ($json, $isApi) {
            return $isApi($request) ? $json($e->getMessage(), 422, $e->errors()) : null;
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) use ($json, $isApi) {
            return $isApi($request) ? $json('Unauthenticated. Silakan login kembali.', 401) : null;
        });

        $exceptions->render(function (AuthorizationException $e, Request $request) use ($json, $isApi) {
            return $isApi($request) ? $json('Anda tidak memiliki akses untuk aksi ini.', 403) : null;
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) use ($json, $isApi) {
            if (! $isApi($request)) {
                return null;
            }

            return $e->getPrevious() instanceof ModelNotFoundException
                ? $json('Data tidak ditemukan.', 404)
                : $json('Endpoint tidak ditemukan.', 404);
        });

        $exceptions->render(function (QueryException $e, Request $request) use ($json, $isApi) {
            if (! $isApi($request)) {
                return null;
            }

            // 23000 = pelanggaran constraint (FK / unique)
            if ((string) $e->getCode() === '23000') {
                return $json('Data bentrok dengan relasi atau data yang sudah ada.', 409);
            }

            return config('app.debug') ? null : $json('Terjadi kesalahan pada database.', 500);
        });

        $exceptions->render(function (HttpExceptionInterface $e, Request $request) use ($json, $isApi) {
            return $isApi($request)
                ? $json($e->getMessage() ?: 'Permintaan tidak dapat diproses.', $e->getStatusCode())
                : null;
        });
    })->create();
