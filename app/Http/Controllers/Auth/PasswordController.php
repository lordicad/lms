<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PasswordController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        // Six characters, matching registration. Laravel's default of eight is a real barrier
        // for a seven year old, and these accounts hold no sensitive data.
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ], [
            'current_password.required' => __('Sila masukkan kata laluan semasa anda.'),
            'current_password.current_password' => __('Kata laluan semasa tidak betul.'),
            'password.required' => __('Sila masukkan kata laluan baharu.'),
            'password.min' => __('Kata laluan mesti sekurang-kurangnya 6 aksara.'),
            'password.confirmed' => __('Kata laluan tidak sama. Sila semak semula.'),
        ]);

        $user = $request->user();

        $user->update([
            'password' => $validated['password'],   // hashed by the model cast
        ]);

        // They chose this one themselves, so the account no longer counts as admin-issued.
        $user->markPasswordChanged();

        return back()->with('status', __('Kata laluan berjaya ditukar.'));
    }
}
