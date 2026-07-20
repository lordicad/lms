<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Grade;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Subject;
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
     * Reference data for the self-service profile form. The web profile uses
     * these same School -> Tahun -> Kelas relationships, so mobile users see
     * only classes that are valid for their selected school and year.
     */
    public function profileOptions(Request $request): JsonResponse
    {
        $classes = SchoolClass::active()
            ->with(['grade:id,name', 'homeroomTeacher:id,name'])
            ->orderBy('school_id')
            ->orderBy('grade_id')
            ->orderBy('name')
            ->get();

        return response()->json([
            'schools' => School::orderBy('name')->get(['id', 'name', 'code', 'state'])
                ->map(fn (School $school) => [
                    'id' => $school->id,
                    'name' => $school->name,
                    'code' => $school->code,
                    'state' => $school->state,
                ])->values(),
            'grades' => Grade::orderBy('level')->get(['id', 'level', 'name'])
                ->map(fn (Grade $grade) => [
                    'id' => $grade->id,
                    'level' => $grade->level,
                    'name' => $grade->name,
                ])->values(),
            'classes' => $classes->map(fn (SchoolClass $class) => [
                'id' => $class->id,
                'school_id' => $class->school_id,
                'grade_id' => $class->grade_id,
                'label' => $class->label(),
                'homeroom_teacher_name' => $class->homeroomTeacher?->name,
            ])->values(),
            'subjects' => Subject::orderBy('sort_order')->get(['id', 'name', 'short_name', 'icon'])
                ->map(fn (Subject $subject) => [
                    'id' => $subject->id,
                    'name' => $subject->displayName(),
                ])->values(),
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

        $rules = [
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
        ];

        if ($user->isStudent()) {
            $rules += [
                'grade_level' => ['required', 'integer', Rule::exists('grades', 'level')],
                'school_id' => ['nullable', 'integer', Rule::exists('schools', 'id')],
                'school_class_id' => ['nullable', 'integer', Rule::exists('school_classes', 'id')],
                'guardian_name' => ['nullable', 'string', 'max:255'],
                'guardian_phone' => ['nullable', 'string', 'max:30', 'regex:/^\+?[0-9\s\-()]{6,20}$/'],
                'guardian_email' => ['nullable', 'string', 'email', 'max:255'],
            ];
        }

        $validated = $request->validate($rules, [
            'name.required' => __('Sila isi nama anda.'),
            'username.required' => __('Sila isi nama pengguna.'),
            'username.regex' => __('Nama pengguna hanya boleh mengandungi huruf, nombor, titik, garis bawah dan sengkang.'),
            'email.required' => __('Guru perlu memberikan alamat emel.'),
            'email.unique' => __('Emel ini sudah didaftarkan.'),
            'grade_level.required' => __('Sila pilih Tahun anda.'),
        ]);

        $studentClass = null;
        if ($user->isStudent() && ! empty($validated['school_class_id'])) {
            $studentClass = SchoolClass::find($validated['school_class_id']);
            $gradeId = Grade::where('level', $validated['grade_level'])->value('id');
            if (! $studentClass
                || (int) $studentClass->school_id !== (int) ($validated['school_id'] ?? 0)
                || (int) $studentClass->grade_id !== (int) $gradeId) {
                throw ValidationException::withMessages([
                    'school_class_id' => [__('Kelas tidak sepadan dengan sekolah dan tahun yang dipilih.')],
                ]);
            }
        }

        $user->update([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'] ?? null,
        ]);

        if ($user->isStudent()) {
            $user->update([
                'grade_id' => Grade::where('level', $validated['grade_level'])->value('id'),
                'school_class_id' => $studentClass?->id,
                'guardian_name' => $validated['guardian_name'] ?? null,
                'guardian_phone' => $validated['guardian_phone'] ?? null,
                'guardian_email' => $validated['guardian_email'] ?? null,
            ]);
        }

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
        $user->loadMissing([
            'grade',
            'school',
            'schoolClass.grade',
            'schoolClass.homeroomTeacher',
            'subjects',
            'homeroomClass.grade',
            'homeroomClass.school',
        ]);

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
            'school' => $user->school === null ? null : [
                'id' => $user->school->id,
                'name' => $user->school->name,
            ],
            'school_class' => $user->schoolClass === null ? null : [
                'id' => $user->schoolClass->id,
                'school_id' => $user->schoolClass->school_id,
                'grade_id' => $user->schoolClass->grade_id,
                'label' => $user->schoolClass->label(),
                'homeroom_teacher_name' => $user->schoolClass->homeroomTeacher?->name,
            ],
            'guardian_name' => $user->guardian_name,
            'guardian_phone' => $user->guardian_phone,
            'guardian_email' => $user->guardian_email,
            'phone' => $user->phone,
            'position' => $user->position,
            'subjects' => $user->subjects->map(fn (Subject $subject) => [
                'id' => $subject->id,
                'name' => $subject->displayName(),
            ])->values(),
            'homeroom_class' => $user->homeroomClass === null ? null : [
                'id' => $user->homeroomClass->id,
                'school_id' => $user->homeroomClass->school_id,
                'grade_id' => $user->homeroomClass->grade_id,
                'label' => $user->homeroomClass->label(),
            ],
        ];
    }
}
