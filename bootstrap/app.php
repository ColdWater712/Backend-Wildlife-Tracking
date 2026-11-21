<?php

use App\Http\Middleware\CheckRole;
use App\Http\Middleware\LogActivity;
use App\Http\Middleware\CheckApiSessionTimeout; // <-- IMPORT INI
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

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
            'log.activity' => LogActivity::class,
            'api.timeout' => CheckApiSessionTimeout::class, // <-- DAFTARKAN INI
        ]);

        $middleware->redirectGuestsTo(fn () => null);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();