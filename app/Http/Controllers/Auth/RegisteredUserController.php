<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Grade;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register', [
            'grades' => Grade::orderBy('level')->get(),
        ]);
    }

    /**
     * One form registers both roles. Students pick a Tahun; teachers tick "Saya seorang guru"
     * and must supply an email plus the Kod Pendaftaran Guru from .env.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $isTeacher = $request->boolean('is_teacher');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => [
                'required', 'string', 'min:3', 'max:30',
                'regex:/^[a-zA-Z0-9._-]+$/',
                // Usernames may repeat; email is the unique identifier.
            ],
            // Kids-friendly minimum. Deliberately below Laravel's default of 8.
            'password' => ['required', 'confirmed', 'string', 'min:6'],

            'email' => [
                Rule::requiredIf($isTeacher),
                'nullable', 'string', 'lowercase', 'email', 'max:255',
                Rule::unique(User::class, 'email'),
            ],
            'grade_level' => [
                Rule::requiredIf(! $isTeacher),
                'nullable', 'integer',
                Rule::exists(Grade::class, 'level'),
            ],
            'teacher_code' => [Rule::requiredIf($isTeacher), 'nullable', 'string'],
        ], [
            'name.required' => __('Sila isi nama penuh anda.'),
            'username.required' => __('Sila pilih nama pengguna.'),
            'username.unique' => __('Nama pengguna ini sudah diambil. Cuba yang lain.'),
            'username.regex' => __('Nama pengguna hanya boleh mengandungi huruf, nombor, titik, garis bawah dan sengkang.'),
            'username.min' => __('Nama pengguna mesti sekurang-kurangnya 3 aksara.'),
            'password.required' => __('Sila isi kata laluan.'),
            'password.min' => __('Kata laluan mesti sekurang-kurangnya 6 aksara.'),
            'password.confirmed' => __('Kata laluan tidak sama. Sila semak semula.'),
            'email.required' => __('Guru perlu memberikan alamat emel.'),
            'email.email' => __('Alamat emel tidak sah.'),
            'email.unique' => __('Emel ini sudah didaftarkan.'),
            'grade_level.required' => __('Sila pilih Tahun anda.'),
            'teacher_code.required' => __('Sila masukkan Kod Pendaftaran Guru.'),
        ]);

        if ($isTeacher) {
            $expected = (string) config('lms.teacher_reg_code');

            if ($expected === '' || ! hash_equals($expected, (string) $validated['teacher_code'])) {
                throw ValidationException::withMessages([
                    'teacher_code' => __('Kod Pendaftaran Guru tidak betul. Sila dapatkan kod daripada pentadbir sekolah.'),
                ]);
            }
        }

        $grade = $isTeacher
            ? null
            : Grade::where('level', $validated['grade_level'])->first();

        $user = User::create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $isTeacher ? $validated['email'] : ($validated['email'] ?? null),
            'password' => $validated['password'],   // hashed by the model cast
            'role' => $isTeacher ? User::ROLE_TEACHER : User::ROLE_STUDENT,
            'grade_id' => $grade?->id,
        ]);

        event(new Registered($user));

        Auth::login($user);

        $request->session()->regenerate();

        return redirect($user->homeRoute());
    }
}
