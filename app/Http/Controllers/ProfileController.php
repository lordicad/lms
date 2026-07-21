<?php

namespace App\Http\Controllers;

use App\Models\Grade;
use App\Models\QuizAttempt;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\User;
use App\Services\LeaderboardService;
use App\Support\Uploads;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user();
        $stats = null;

        // Real gamification stats for the student profile header. Badges are derived from these
        // same numbers, so nothing is fabricated — a badge is earned only when its metric is met.
        if ($user->isStudent()) {
            $row = app(LeaderboardService::class)->rowFor($user);
            $quizzesDone = QuizAttempt::where('student_id', $user->id)->completed()->count();
            $videosWatched = $user->lessonViews()->count();
            $hasPerfect = QuizAttempt::where('student_id', $user->id)
                ->where('max_score', '>', 0)
                ->whereColumn('score', 'max_score')
                ->exists();

            $stats = [
                'points' => $row?->points ?? 0,
                'rank' => $row?->rank,
                'quizzes' => $quizzesDone,
                'videos' => $videosWatched,
                'perfect' => $hasPerfect,
            ];
        }

        // All active classes, shared by the teacher's homeroom-class picker and the student's class
        // picker, so the School -> Class dropdowns can depend on each other client-side.
        $allClasses = SchoolClass::active()->with('grade', 'homeroomTeacher:id,name')
            ->orderBy('grade_id')->orderBy('name')
            ->get()
            ->map(fn (SchoolClass $c) => [
                'id' => $c->id,
                'school_id' => $c->school_id,
                'grade_id' => $c->grade_id,
                'label' => $c->label(),
                'homeroom' => $c->homeroomTeacher?->name,
            ])->values();

        // Teachers get the WeLearn Teacher profile in their own portal shell.
        if ($user->isTeacher()) {
            $user->load('subjects', 'homeroomClass', 'school');

            return view('cikgu.profil', [
                'user' => $user,
                'schools' => School::orderBy('name')->get(),
                'subjects' => Subject::orderBy('sort_order')->get(),
                'allClasses' => $allClasses,
                'selectedSubjectIds' => $user->subjects->pluck('id')->all(),
                'homeroomClassId' => $user->homeroomClass?->id,
            ]);
        }

        $user->load('school', 'schoolClass.grade');

        return view('profil.edit', [
            'user' => $user,
            'grades' => Grade::orderBy('level')->get(),
            'schools' => School::orderBy('name')->get(),
            'allClasses' => $allClasses,
            'homeroomTeacher' => $user->homeroomTeacher(),
            'stats' => $stats,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'username' => [
                'required', 'string', 'min:3', 'max:30',
                'regex:/^[a-zA-Z0-9._-]+$/',
                // Usernames may repeat; email is the unique identifier.
            ],
            'email' => [
                Rule::requiredIf($user->isTeacher()),
                'nullable', 'string', 'lowercase', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];

        $messages = [
            'name.required' => __('Sila isi nama anda.'),
            'username.required' => __('Sila isi nama pengguna.'),
            'username.regex' => __('Nama pengguna hanya boleh mengandungi huruf, nombor, titik, garis bawah dan sengkang.'),
            'email.required' => __('Guru perlu memberikan alamat emel.'),
            'email.unique' => __('Emel ini sudah didaftarkan.'),
            'grade_level.required' => __('Sila pilih Tahun anda.'),
            'avatar.max' => __('Gambar profil terlalu besar. Had ialah 2 MB.'),
        ];

        if ($user->isStudent()) {
            $rules += $this->studentRules($request);
        } elseif ($user->isTeacher()) {
            $rules += $this->teacherRules($request, $user);
        }

        $validated = $request->validate($rules, $messages);

        $user->fill([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'] ?? null,
        ]);

        if ($user->isTeacher()) {
            $user->phone = $validated['phone'] ?? null;
            $user->position = $validated['position'] ?? null;
            $user->school_id = $validated['school_id'] ?? null;
        }

        if ($user->isStudent()) {
            $user->grade_id = Grade::where('level', $validated['grade_level'])->value('id');
            $user->school_id = $validated['school_id'] ?? null;
            // A class is only kept when it matches the submitted School + Year (validated below).
            $user->school_class_id = $validated['school_class_id'] ?? null;
            $user->guardian_name = $validated['guardian_name'] ?? null;
            $user->guardian_phone = $validated['guardian_phone'] ?? null;
            $user->guardian_email = $validated['guardian_email'] ?? null;
        }

        $oldAvatar = $user->avatar;

        if ($request->hasFile('avatar')) {
            $user->avatar = Uploads::store($request->file('avatar'), 'avatars');
        }

        $user->save();

        // Relational side-effects after the base row is saved.
        if ($user->isTeacher()) {
            $user->subjects()->sync($validated['subjects'] ?? []);
            $this->assignHomeroomClass($user, $validated['homeroom_class_id'] ?? null);
        }

        if ($request->hasFile('avatar') && $oldAvatar) {
            Storage::disk('uploads')->delete($oldAvatar);
        }

        // back(), not a fixed route: each role edits its profile on its own surface (the admin has a
        // dedicated page), so returning to where the form was submitted keeps everyone in their shell.
        return back()->with('status', __('Profil berjaya dikemas kini.'));
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function teacherRules(Request $request, User $user): array
    {
        return [
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^\+?[0-9\s\-()]{6,20}$/'],
            'position' => ['nullable', 'string', 'max:100'],
            'school_id' => ['nullable', 'integer', Rule::exists('schools', 'id')],
            'subjects' => ['nullable', 'array'],
            'subjects.*' => ['integer', Rule::exists('subjects', 'id')],
            'homeroom_class_id' => [
                'nullable', 'integer',
                function (string $attribute, mixed $value, \Closure $fail) use ($request, $user) {
                    $class = SchoolClass::find($value);

                    if (! $class) {
                        $fail(__('Kelas tidak sah.'));

                        return;
                    }

                    // The class must be in the school the teacher just selected.
                    if ((int) $class->school_id !== (int) $request->input('school_id')) {
                        $fail(__('Kelas ini bukan di sekolah yang dipilih.'));

                        return;
                    }

                    // Only one homeroom teacher per class.
                    if ($class->homeroom_teacher_id !== null && (int) $class->homeroom_teacher_id !== $user->id) {
                        $fail(__('Kelas ini sudah mempunyai guru kelas.'));
                    }
                },
            ],
        ];
    }

    /**
     * Point school_classes.homeroom_teacher_id at this teacher for the chosen class (the single
     * source of truth), first releasing any class they were previously homeroom of.
     */
    private function assignHomeroomClass(User $teacher, ?int $classId): void
    {
        $current = $teacher->homeroomClass()->first();

        if ($current && (int) $current->id !== (int) $classId) {
            $current->update(['homeroom_teacher_id' => null]);
        }

        if ($classId) {
            SchoolClass::where('id', $classId)->update(['homeroom_teacher_id' => $teacher->id]);
        }
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function studentRules(Request $request): array
    {
        return [
            'grade_level' => ['required', 'integer', Rule::exists('grades', 'level')],
            'school_id' => ['nullable', 'integer', Rule::exists('schools', 'id')],
            'school_class_id' => [
                'nullable', 'integer',
                function (string $attribute, mixed $value, \Closure $fail) use ($request) {
                    $class = SchoolClass::find($value);

                    if (! $class) {
                        $fail(__('Kelas tidak sah.'));

                        return;
                    }

                    $gradeId = Grade::where('level', $request->integer('grade_level'))->value('id');

                    if ((int) $class->school_id !== (int) $request->input('school_id')
                        || (int) $class->grade_id !== (int) $gradeId) {
                        $fail(__('Kelas ini tidak sepadan dengan sekolah dan tahun yang dipilih.'));
                    }
                },
            ],
            'guardian_name' => ['nullable', 'string', 'max:255'],
            'guardian_phone' => ['nullable', 'string', 'max:30', 'regex:/^\+?[0-9\s\-()]{6,20}$/'],
            'guardian_email' => ['nullable', 'string', 'email', 'max:255'],
        ];
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ], [
            'current_password.required' => __('Sila masukkan kata laluan semasa.'),
            'current_password.current_password' => __('Kata laluan semasa tidak betul.'),
            'password.required' => __('Sila masukkan kata laluan baharu.'),
            'password.confirmed' => __('Pengesahan kata laluan baharu tidak sepadan.'),
        ]);

        $user = $request->user();
        $user->update(['password' => Hash::make($validated['password'])]);

        // Chosen by the owner, so the account is no longer on an admin-issued password.
        $user->markPasswordChanged();

        return back()->with('status', __('Kata laluan berjaya dikemas kini.'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ], [
            'password.required' => __('Sila masukkan kata laluan anda untuk mengesahkan.'),
            'password.current_password' => __('Kata laluan tidak betul.'),
        ]);

        $user = $request->user();

        Auth::logout();

        if ($user->avatar) {
            Storage::disk('uploads')->delete($user->avatar);
        }

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
