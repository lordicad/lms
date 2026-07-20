<?php

namespace App\Http\Controllers;

use App\Models\Grade;
use App\Models\QuizAttempt;
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

        // Teachers get the WeLearn Teacher profile in their own portal shell.
        if ($user->isTeacher()) {
            return view('cikgu.profil', ['user' => $user]);
        }

        return view('profil.edit', [
            'user' => $user,
            'grades' => Grade::orderBy('level')->get(),
            'stats' => $stats,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
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
            'grade_level' => [
                Rule::requiredIf($user->isStudent()),
                'nullable', 'integer',
                Rule::exists('grades', 'level'),
            ],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'name.required' => __('Sila isi nama anda.'),
            'username.required' => __('Sila isi nama pengguna.'),
            'username.unique' => __('Nama pengguna ini sudah diambil.'),
            'username.regex' => __('Nama pengguna hanya boleh mengandungi huruf, nombor, titik, garis bawah dan sengkang.'),
            'email.required' => __('Guru perlu memberikan alamat emel.'),
            'email.unique' => __('Emel ini sudah didaftarkan.'),
            'grade_level.required' => __('Sila pilih Tahun anda.'),
            'avatar.max' => __('Gambar profil terlalu besar. Had ialah 2 MB.'),
        ]);

        $user->fill([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'] ?? null,
        ]);

        if ($user->isStudent()) {
            $user->grade_id = Grade::where('level', $validated['grade_level'])->value('id');
        }

        $oldAvatar = $user->avatar;

        if ($request->hasFile('avatar')) {
            $user->avatar = Uploads::store($request->file('avatar'), 'avatars');
        }

        $user->save();

        if ($request->hasFile('avatar') && $oldAvatar) {
            Storage::disk('uploads')->delete($oldAvatar);
        }

        // back(), not a fixed route: each role edits its profile on its own surface (the admin has a
        // dedicated page), so returning to where the form was submitted keeps everyone in their shell.
        return back()->with('status', __('Profil berjaya dikemas kini.'));
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

        $request->user()->update(['password' => Hash::make($validated['password'])]);

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
