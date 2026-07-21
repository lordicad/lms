<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Holds an account on the "choose your own password" screen until it has one.
 *
 * Admin-created accounts start on a password the admin chose and handed over, so it is known to
 * someone other than its owner. Until the owner replaces it, every page redirects here — otherwise
 * the handed-over password would keep working indefinitely.
 */
class EnsurePasswordChanged
{
    /**
     * Routes that must stay reachable while the account is held, or the redirect would loop
     * (the change screen itself) or trap the user with no way out (signing out, switching language).
     *
     * @var list<string>
     */
    private const ALLOWED = [
        'password.first',
        'password.first.store',
        'logout',
        'locale.switch',
        'theme.switch',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->mustChangePassword() && ! $request->routeIs(self::ALLOWED)) {
            // A background fetch should not be answered with a redirect to an HTML form.
            if ($request->expectsJson()) {
                abort(409, __('Sila tetapkan kata laluan baharu anda dahulu.'));
            }

            return redirect()->route('password.first');
        }

        return $next($request);
    }
}
