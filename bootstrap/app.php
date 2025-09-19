<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Configuration\Exceptions;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'adminlike' => \App\Http\Middleware\EnsureAdminLike::class,
        ]);
    })
    ->withExceptions(function (\Illuminate\Foundation\Configuration\Exceptions $exceptions) {
    $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e, $request) {
        // Only intercept for web requests and non-AJAX
        if ($request->expectsJson()) return null;

        // flash a short message, then go back or home
        session()->flash('err', $e->getMessage() ?: 'Something went wrong.');
        $fallback = url('/');
        return redirect()->back()->withInput()->with('err', session('err')) ?: redirect($fallback);
    });
})
    ->create();
