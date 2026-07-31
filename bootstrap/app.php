<?php

use App\Http\Middleware\EnsurePasswordChanged;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\SetTheme;
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
    ->withMiddleware(function (Middleware $middleware): void {
        // Runs on every web request, after the session is available, so the chosen
        // language and theme are applied before any view renders. EnsurePasswordChanged comes
        // last: it needs the resolved route to know which pages stay reachable while it holds.
        $middleware->web(append: [SetLocale::class, SetTheme::class, EnsurePasswordChanged::class]);

        $middleware->alias([
            'role' => EnsureUserHasRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // A 419 (the CSRF token expired because the page sat open past the session lifetime)
        // should not dump the user on the bare "Page Expired" screen. Send them back to the page
        // they came from with a clear message and their input preserved, so they can just submit
        // again. JSON callers (the auto-translate fetch) get a 419 with a message to surface.
        // A 419 (expired or missing CSRF token) should not dump the user on the bare dark "Page
        // Expired" screen — send them back to the page they came from. Catches both the raw
        // TokenMismatchException and the HttpException(419) it is converted into. Touches no session
        // state (a session-flash version could throw in the error context).
        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {
            $is419 = $e instanceof \Illuminate\Session\TokenMismatchException
                || ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface && $e->getStatusCode() === 419);

            if (! $is419) {
                return null;
            }

            if ($request->expectsJson()) {
                return response()->json(['message' => __('Sesi tamat tempoh. Sila muat semula halaman.')], 419);
            }

            return redirect($request->headers->get('referer') ?: url('/'));
        });
    })->create();
