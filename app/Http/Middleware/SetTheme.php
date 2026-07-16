<?php

namespace App\Http\Middleware;

use App\Http\Controllers\ThemeController;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class SetTheme
{
    /**
     * Resolve the colour theme, in priority order:
     *   1. the session (this visitor's most recent toggle),
     *   2. the signed-in user's saved preference (follows them across devices),
     *   3. the app default (light).
     *
     * The OS `prefers-color-scheme` is deliberately ignored: the owner's stated default is
     * light, and respecting the OS would silently give most students dark again. Anything
     * outside the supported set falls back to light, so a tampered value is harmless.
     *
     * The resolved value is shared with every view as `$theme`; layouts render it into the
     * <html> class on the server so there is never a flash of the wrong theme.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $theme = $request->session()->get('theme')
            ?? $request->user()?->theme
            ?? ThemeController::DEFAULT;

        if (! in_array($theme, ThemeController::SUPPORTED, true)) {
            $theme = ThemeController::DEFAULT;
        }

        View::share('theme', $theme);

        return $next($request);
    }
}
