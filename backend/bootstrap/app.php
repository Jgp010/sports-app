<?php

use App\Models\Event;
use App\Models\EventRegistration;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
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
        $middleware->redirectGuestsTo(fn () => route('admin.login'));
        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureAdmin::class,
            'api.token' => \App\Http\Middleware\AuthenticateApiToken::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (ModelNotFoundException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            [$code, $message] = match ($exception->getModel()) {
                Event::class => ['EVENT_NOT_FOUND', '查無賽事資料。'],
                EventRegistration::class => ['REGISTRATION_NOT_FOUND', '查無報名資料。'],
                default => ['RESOURCE_NOT_FOUND', '查無資料。'],
            };

            return response()->json([
                'success' => false,
                'data' => null,
                'meta' => null,
                'error' => ['code' => $code, 'message' => $message],
            ], 404);
        });

        $exceptions->render(function (ThrottleRequestsException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'success' => false,
                'data' => null,
                'meta' => null,
                'error' => ['code' => 'TOO_MANY_REQUESTS', 'message' => '請求次數過多，請稍後再試。'],
            ], 429, $exception->getHeaders());
        });

        $exceptions->render(function (ValidationException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'success' => false,
                'data' => null,
                'meta' => null,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => '輸入資料驗證失敗。',
                    'details' => $exception->errors(),
                ],
            ], 422);
        });

        $exceptions->render(function (NotFoundHttpException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'success' => false,
                'data' => null,
                'meta' => null,
                'error' => ['code' => 'RESOURCE_NOT_FOUND', 'message' => '查無資料。'],
            ], 404);
        });

        $exceptions->render(function (HttpExceptionInterface $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            $status = $exception->getStatusCode();
            [$code, $message] = match ($status) {
                400 => ['BAD_REQUEST', '請求格式錯誤。'],
                403 => ['FORBIDDEN', '沒有權限執行此操作。'],
                405 => ['METHOD_NOT_ALLOWED', '不支援此請求方式。'],
                422 => ['UNPROCESSABLE_CONTENT', $exception->getMessage() ?: '輸入資料無法處理。'],
                default => ['REQUEST_FAILED', '請求無法完成。'],
            };

            return response()->json([
                'success' => false,
                'data' => null,
                'meta' => null,
                'error' => ['code' => $code, 'message' => $message],
            ], $status, $exception->getHeaders());
        });

        $exceptions->render(function (\Throwable $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'success' => false,
                'data' => null,
                'meta' => null,
                'error' => ['code' => 'SERVER_ERROR', 'message' => '系統忙碌中，請稍後再試。'],
            ], 500);
        });
    })->create();
