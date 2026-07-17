<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Token-based authentication for the Flutter mobile app.
 *
 * Mirrors the web login rules (a single field accepts either a username or an
 * email, since most students have no email) but issues a Sanctum bearer token
 * instead of opening a cookie session.
 */
class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
            'device_name' => ['required', 'string'],
        ]);

        $field = filter_var($validated['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $user = User::where($field, $validated['login'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'login' => [__('Nama pengguna atau kata laluan tidak betul.')],
            ]);
        }

        // Same gate as the web login: the mobile app is a second front door, and a deactivated
        // account must not be able to walk through it.
        if (! $user->isActive()) {
            throw ValidationException::withMessages([
                'login' => [__('Akaun ini telah dinyahaktifkan. Sila hubungi pentadbir sekolah anda.')],
            ]);
        }

        $token = $user->createToken($validated['device_name'])->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $this->userPayload($user),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $this->userPayload($request->user()),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => __('Log keluar berjaya.')]);
    }

    /**
     * Shape the user exactly as the Flutter AuthUser model expects.
     *
     * @return array<string, mixed>
     */
    private function userPayload(User $user): array
    {
        $user->loadMissing('grade');

        return [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'role' => $user->role,
            'grade' => $user->grade === null ? null : [
                'id' => $user->grade->id,
                'level' => $user->grade->level,
                'name' => $user->grade->name,
            ],
        ];
    }
}
