<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Uploads;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
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

    /**
     * Update the account fields that are editable in the mobile profile.
     *
     * Avatar uploads and password changes remain separate actions, so this JSON
     * endpoint stays predictable for Flutter and matches the web validation.
     */
    public function updateProfile(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => [
                'required', 'string', 'min:3', 'max:30',
                'regex:/^[a-zA-Z0-9._-]+$/',
            ],
            'email' => [
                Rule::requiredIf($user->isTeacher()),
                'nullable', 'string', 'lowercase', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
        ], [
            'name.required' => __('Sila isi nama anda.'),
            'username.required' => __('Sila isi nama pengguna.'),
            'username.regex' => __('Nama pengguna hanya boleh mengandungi huruf, nombor, titik, garis bawah dan sengkang.'),
            'email.required' => __('Guru perlu memberikan alamat emel.'),
            'email.unique' => __('Emel ini sudah didaftarkan.'),
        ]);

        $user->update([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'] ?? null,
        ]);

        return response()->json([
            'user' => $this->userPayload($user->fresh() ?? $user),
        ]);
    }

    /**
     * Photo upload intentionally uses its own multipart endpoint. Keeping it separate from the
     * JSON account form makes validation and retry behaviour reliable on Android.
     */
    public function updateAvatar(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'avatar.required' => __('Sila pilih gambar profil.'),
            'avatar.image' => __('Fail yang dipilih mesti gambar.'),
            'avatar.mimes' => __('Gunakan gambar JPG, PNG atau WEBP.'),
            'avatar.max' => __('Gambar profil terlalu besar. Had ialah 2 MB.'),
        ]);

        $oldAvatar = $user->avatar;
        $user->avatar = Uploads::store($request->file('avatar'), 'avatars');
        $user->save();

        if ($oldAvatar) {
            Storage::disk('uploads')->delete($oldAvatar);
        }

        return response()->json([
            'user' => $this->userPayload($user->fresh() ?? $user),
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
            'avatar_url' => $user->avatarUrl(),
            'role' => $user->role,
            'grade' => $user->grade === null ? null : [
                'id' => $user->grade->id,
                'level' => $user->grade->level,
                'name' => $user->grade->name,
            ],
        ];
    }
}
