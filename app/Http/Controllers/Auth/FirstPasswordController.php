<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * The one-time screen an admin-created account lands on at first sign-in, to replace the password
 * the admin handed over with one only they know. EnsurePasswordChanged keeps them here until it is
 * done; once done, `password_changed_at` is stamped and the guard stops firing.
 */
class FirstPasswordController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        // Nothing to do for an account that already owns its password — including a direct visit.
        if (! $request->user()->mustChangePassword()) {
            return redirect()->route('profile.edit');
        }

        return view('auth.first-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user->mustChangePassword()) {
            return redirect()->to($user->homeRoute());
        }

        // Six characters, matching the rest of the app: Laravel's default of eight is a real
        // barrier for a seven year old, and these accounts hold no sensitive data.
        $validated = $request->validate([
            'password' => ['required', 'string', 'confirmed', Password::min(6)],
        ], [
            'password.required' => __('Sila masukkan kata laluan baharu.'),
            'password.min' => __('Kata laluan mesti sekurang-kurangnya 6 aksara.'),
            'password.confirmed' => __('Kata laluan tidak sama. Sila semak semula.'),
        ]);

        $user->password = $validated['password'];   // hashed by the model cast
        $user->save();
        $user->markPasswordChanged();

        return redirect()->to($user->homeRoute())
            ->with('status', __('Kata laluan anda telah ditetapkan. Gunakan kata laluan baharu ini pada kali seterusnya.'));
    }
}
