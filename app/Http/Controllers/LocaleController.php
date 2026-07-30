<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LocaleController extends Controller
{
    public const SUPPORTED = ['ms', 'en'];

    /**
     * Switch the interface language for the CURRENT session only. It is not persisted to the user
     * record, so every fresh sign-in starts back in Bahasa Melayu until toggled again. Returns to
     * the page the toggle was clicked on.
     */
    public function __invoke(Request $request, string $locale): RedirectResponse
    {
        abort_unless(in_array($locale, self::SUPPORTED, true), Response::HTTP_NOT_FOUND);

        $request->session()->put('locale', $locale);

        return back();
    }
}
