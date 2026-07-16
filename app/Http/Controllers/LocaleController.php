<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LocaleController extends Controller
{
    public const SUPPORTED = ['ms', 'en'];

    /**
     * Switch the interface language. Stored in the session for everyone, and persisted to the
     * user record when signed in so the choice follows them to another device. Returns to the
     * page the toggle was clicked on.
     */
    public function __invoke(Request $request, string $locale): RedirectResponse
    {
        abort_unless(in_array($locale, self::SUPPORTED, true), Response::HTTP_NOT_FOUND);

        $request->session()->put('locale', $locale);

        if ($user = $request->user()) {
            $user->update(['locale' => $locale]);
        }

        return back();
    }
}
