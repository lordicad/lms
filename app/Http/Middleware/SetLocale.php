<?php

namespace App\Http\Middleware;

use App\Http\Controllers\LocaleController;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /** Bahasa Melayu is the interface anyone gets until they choose otherwise. */
    public const DEFAULT = 'ms';

    /**
     * Resolve the request language:
     *   1. the session (this visitor's language toggle for the current session),
     *   2. otherwise Bahasa Melayu.
     *
     * The toggle is session-only, so every fresh sign-in starts in Bahasa Melayu until the user
     * flips it again. Anything outside the supported set falls back to ms, so a tampered value is
     * harmless.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // The default is a constant rather than config('app.locale') on purpose: setLocale()
        // writes that config key, so under any long-lived process — Octane, a queue worker, a test
        // run — the "default" would drift to whatever language the previous request happened to use.
        $locale = $request->session()->get('locale')
            ?? self::DEFAULT;

        if (! in_array($locale, LocaleController::SUPPORTED, true)) {
            $locale = self::DEFAULT;
        }

        app()->setLocale($locale);
        Carbon::setLocale($locale);

        return $next($request);
    }
}
