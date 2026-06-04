<?php

use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsureTeacher;
use App\Support\AuthRedirect;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'admin' => EnsureAdmin::class,
            'teacher' => EnsureTeacher::class,
        ]);

        $middleware->redirectGuestsTo(function (Request $request) {
            return $request->is('admin', 'admin/*')
                ? route('admin.login')
                : route('teacher.login');
        });

        $middleware->redirectUsersTo(fn (Request $request) => AuthRedirect::forAuthenticatedGuest($request));
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (AuthorizationException $e, Request $request) {
            $message = $e->getMessage() ?: 'غير مصرح لك بهذا الإجراء.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 403);
            }

            abort(403, $message);
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            $message = $e->getMessage() ?: 'الصفحة أو العنصر غير موجود.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 404);
            }
        });

        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'يرجى تصحيح الأخطاء في النموذج.',
                    'errors' => $e->errors(),
                ], 422);
            }
        });
    })->create();
