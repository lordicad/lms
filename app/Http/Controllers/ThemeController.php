<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ThemeController extends Controller
{
    public const SUPPORTED = ['light', 'dark'];

    public const DEFAULT = 'light';

    /**
     * Switch the colour theme. Stored in the session for everyone, and persisted to the user
     * record when signed in so the choice follows them to another device. Returns to the page
     * the toggle was clicked on. Mirrors LocaleController exactly.
     */
    public function __invoke(Request $request, string $theme): RedirectResponse
    {
        abort_unless(in_array($theme, self::SUPPORTED, true), Response::HTTP_NOT_FOUND);

        $request->session()->put('theme', $theme);

        if ($user = $request->user()) {
            $user->update(['theme' => $theme]);
        }

        return back();
    }
}
