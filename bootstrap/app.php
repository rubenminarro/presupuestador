<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Exceptions\AppException;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {

        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'status' => 401,
                    'message' => 'No autenticado.',
                    'data' => null,
                    'errors' => null,
                    'error_code' => 'UNAUTHENTICATED',
                ], 401);
            }
        });

        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'status' => 422,
                    'message' => 'Los datos proporcionados no son válidos.',
                    'data' => null,
                    'errors' => $e->errors(),
                    'error_code' => 'VALIDATION_ERROR',
                ], 422);
            }
        });
        
        $exceptions->render(function (AppException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'status' => $e->getStatus(),
                    'message' => $e->getMessage(),
                    'data' => null,
                    'errors' => $e->getErrors(),
                    'error_code' => $e->getErrorCode(),
                ], $e->getStatus());
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
                    'errors' => null,
                    'error_code' => 'RESOURCE_NOT_FOUND',
                ], 404);
            }
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) 
        {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'status' => 404,
                    'message' => 'Recurso no encontrado.',
                    'data' => null,
                    'errors' => null,
                    'error_code' => 'RESOURCE_NOT_FOUND',
                ], 404);
            }
        });

    })->create();
