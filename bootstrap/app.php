<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        // Keep your current routing; add API if/when you need it.
        web: __DIR__ . '/../routes/web.php',
        // api: __DIR__ . '/../routes/api.php', // <- optional
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Route middleware aliases (Laravel 12 style)
        $middleware->alias([
            'adminlike' => \App\Http\Middleware\EnsureAdminLike::class,
            'role'      => \App\Http\Middleware\EnsureUserHasRole::class, // <- add this
        ]);

        // If you later want to add global middleware or group edits:
        // $middleware->append(\App\Http\Middleware\SomethingGlobal::class);
        // $middleware->group('web', [ ... ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (Throwable $e, Request $request) {
            // Let API/JSON requests use the default JSON handler
            if ($request->expectsJson() || $request->wantsJson() || $request->is('api/*')) {
                return null;
            }

            // Only intercept typical HTTP exceptions; otherwise, let default handler show error page
            if (!($e instanceof HttpExceptionInterface)) {
                return null;
            }

            // Flash the message and send user back (or home if no referrer)
            $message  = trim((string) $e->getMessage()) ?: 'Something went wrong.';
            $fallback = url('/');

            session()->flash('err', $message);

            // Compute a safe "previous" URL, fall back to home if missing
            $previous = url()->previous();
            if (!$previous || $previous === url()->current()) {
                $previous = $fallback;
            }

            return redirect()->to($previous)->withInput()->with('err', session('err'));
        });
    })
    ->create();
