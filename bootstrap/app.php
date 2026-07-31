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
        $exceptions->render(function (\Illuminate\Session\TokenMismatchException $e, \Illuminate\Http\Request $request) {
            // Diagnostic: record *why* the CSRF check failed. Deliberately touches NO session state
            // (an earlier version read $request->session() and could throw in the error context,
            // which silently aborted the whole handler). Only request/cookie data, no secrets.
            try {
                \Illuminate\Support\Facades\Log::warning('419 CSRF diagnostic', [
                    'url' => $request->fullUrl(),
                    'method' => $request->method(),
                    'has_token_field' => $request->has('_token'),
                    'token_field_len' => strlen((string) $request->input('_token', '')),
                    'session_cookie_name' => config('session.cookie'),
                    'has_session_cookie' => $request->cookies->has((string) config('session.cookie')),
                    'cookie_names' => array_keys($request->cookies->all()),
                    'content_length' => $request->header('Content-Length'),
                    'content_type' => $request->header('Content-Type'),
                    'post_keys' => array_keys($request->post()),
                ]);
            } catch (\Throwable $ignore) {
                // Never let diagnostics break the response.
            }

            if ($request->expectsJson()) {
                return response()->json(['message' => __('Sesi tamat tempoh. Sila muat semula halaman.')], 419);
            }

            // Redirect via the referer header, not redirect()->back() or a session flash — both use
            // the session, which is the thing under suspicion here.
            $back = $request->headers->get('referer') ?: url('/');

            return redirect($back);
        });
    })->create();
