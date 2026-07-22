<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        // Signing in starts in Bahasa Melayu unless this account has chosen otherwise. Dropping the
        // session override matters because it survives sign-in: without this, a visitor who flipped
        // the toggle to English on the login screen would carry English into someone else's account
        // on a shared computer. SetLocale then falls back to the user's own saved language, or ms.
        $request->session()->forget('locale');

        if ($locale = $request->user()->locale) {
            $request->session()->put('locale', $locale);
        }

        // Teachers land on /cikgu, students on /belajar.
        return redirect()->intended($request->user()->homeRoute());
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
