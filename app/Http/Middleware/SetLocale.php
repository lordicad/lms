<?php

namespace App\Http\Middleware;

use App\Http\Controllers\LocaleController;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Resolve the request language, in priority order:
     *   1. the session (this visitor's most recent toggle),
     *   2. the signed-in user's saved preference (follows them across devices),
     *   3. the app default (ms).
     *
     * Anything outside the supported set falls back to ms, so a tampered value is harmless.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->session()->get('locale')
            ?? $request->user()?->locale
            ?? config('app.locale');

        if (! in_array($locale, LocaleController::SUPPORTED, true)) {
            $locale = 'ms';
        }

        app()->setLocale($locale);
        Carbon::setLocale($locale);

        return $next($request);
    }
}
