<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Exceptions\BudgetException;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {

        $middleware->statefulApi();

        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        
        $exceptions->render(function (BudgetException $e, Request $request) 
        {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'status' => $e->status(),
                    'message' => $e->getMessage(),
                    'data' => null,
                ], $e->status());
            }
        });

        $exceptions->render(function (ModelNotFoundException $e, Request $request) 
        {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'status' => 404,
                    'message' => 'Recurso no encontrado.',
                    'data' => null,
                ], 404);
            }
        });

    })->create();
