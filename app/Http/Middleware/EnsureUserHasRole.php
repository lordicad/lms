<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Gate a route to one role. A signed-in user of the wrong role is sent to their own
     * home rather than shown a 403: a student who lands on /cikgu just gets bounced back
     * to /belajar, which is friendlier than an error page for a 9 year old.
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->guest(route('login'));
        }

        if ($user->role !== $role) {
            return redirect($user->homeRoute())
                ->with('error', __('Halaman itu bukan untuk akaun anda.'));
        }

        return $next($request);
    }
}
