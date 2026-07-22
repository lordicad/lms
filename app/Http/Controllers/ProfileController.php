<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Services\LeaderboardService;
use App\Support\Uploads;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
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

        // Teachers get the WeLearn Teacher profile in their own portal shell. It shows their school
        // record rather than offering it for editing, so no picker data is needed — only the
        // teacher's own relations.
        if ($user->isTeacher()) {
            $user->load(['subjects' => fn ($q) => $q->orderBy('sort_order'), 'homeroomClass.grade', 'school']);

            return view('cikgu.profil', ['user' => $user]);
        }

        // Students see their school record too rather than picking it, so the class/school/year
        // lists the pickers needed are gone — only the student's own relations are read.
        $user->load('school', 'schoolClass.grade');

        return view('profil.edit', [
            'user' => $user,
            'homeroomTeacher' => $user->homeroomTeacher(),
            'stats' => $stats,
            'activity' => $user->isStudent() ? $this->recentActivity($user) : collect(),
            'recommended' => $user->isStudent() ? $this->recommended($user) : collect(),
        ]);
    }

    /**
     * The student's own last few actions, newest first.
     *
     * Only things the app actually records: a video opened, a quiz finished, a video saved. There
     * is no per-student download log — materials keep an aggregate count and nothing more — so
     * downloads are deliberately absent rather than guessed at.
     *
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function recentActivity(User $user, int $limit = 6): Collection
    {
        $views = $user->lessonViews()->with('lesson.chapter')->latest()->limit($limit)->get()
            ->map(fn ($view) => [
                'at' => $view->created_at,
                'icon' => 'play',
                'tint' => '#E4EEF9',
                'ink' => '#2E6CA8',
                'title' => $view->lesson?->title,
                'meta' => $view->lesson?->chapter?->title,
                'tag' => __('Ditonton'),
                'url' => $view->lesson ? route('video.show', $view->lesson) : null,
            ]);

        $attempts = QuizAttempt::where('student_id', $user->id)->completed()
            ->with('quiz')->latest('completed_at')->limit($limit)->get()
            ->map(fn (QuizAttempt $attempt) => [
                'at' => $attempt->completed_at,
                'icon' => 'quiz',
                'tint' => '#DCF2EE',
                'ink' => '#0F7A68',
                'title' => $attempt->quiz?->title,
                'meta' => $attempt->max_score > 0
                    ? __('Skor: :percent%', ['percent' => round($attempt->score / $attempt->max_score * 100)])
                    : null,
                'tag' => __('Selesai'),
                'url' => null,
            ]);

        $saved = $user->favourites()->with('lesson.chapter')->latest()->limit($limit)->get()
            ->map(fn ($favourite) => [
                'at' => $favourite->created_at,
                'icon' => 'heart',
                'tint' => '#FBE4ED',
                'ink' => '#B84A75',
                'title' => $favourite->lesson?->title,
                'meta' => $favourite->lesson?->chapter?->title,
                'tag' => __('Disimpan'),
                'url' => $favourite->lesson ? route('video.show', $favourite->lesson) : null,
            ]);

        return $views->concat($attempts)->concat($saved)
            ->filter(fn (array $row) => filled($row['title']) && $row['at'])
            ->sortByDesc('at')
            ->take($limit)
            ->values();
    }

    /**
     * Published videos in the student's own Year that they have not opened yet, newest first.
     *
     * "Recommended" is doing modest work here: it is what is available and unseen, not a model of
     * what they would like. Saying so in the subtitle keeps the promise the panel makes small.
     *
     * @return \Illuminate\Support\Collection<int, Lesson>
     */
    private function recommended(User $user, int $limit = 6): Collection
    {
        if (! $user->grade_id) {
            return collect();
        }

        return Lesson::published()
            ->with('chapter.subject')
            ->whereHas('chapter', fn ($q) => $q->where('grade_id', $user->grade_id))
            ->whereNotIn('id', $user->lessonViews()->select('lesson_id'))
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $rules = [
            // The display nickname, which the owner is free to change. Spaces are allowed: it is
            // never typed to sign in — email is — and "Cikgu Ana" should be a valid name to show.
            'username' => [
                'required', 'string', 'min:3', 'max:30',
                'regex:/^[\pL\pN ._-]+$/u',
                // Usernames may repeat; email is the unique identifier.
            ],
            // Email is deliberately absent: it is the sign-in identifier, set by the admin, and
            // changing it here would let someone change what they sign in with.
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];

        $messages = [
            'name.required' => __('Sila isi nama anda.'),
            'username.required' => __('Sila isi nama pengguna.'),
            'username.regex' => __('Nama pengguna hanya boleh mengandungi huruf, nombor, ruang, titik, garis bawah dan sengkang.'),
            'avatar.max' => __('Gambar profil terlalu besar. Had ialah 2 MB.'),
        ];

        // Teachers and students edit their display name and photo, and a teacher their phone
        // number. Everything else on those profiles — legal name, school, year, class, position,
        // subjects, guardian contacts — is school record kept by the admin, so those keys are
        // never validated and never assigned here. Leaving them out of the rules is what enforces
        // it: a hand-crafted POST carrying school_id has nothing to bind to.
        //
        // The admin is the exception on name, having nobody above them to set it.
        if ($user->isAdmin()) {
            $rules['name'] = ['required', 'string', 'max:255'];
        }

        if ($user->isTeacher()) {
            $rules += $this->teacherRules();
        }

        $validated = $request->validate($rules, $messages);

        // Email is not in this list on purpose — see the rules above.
        $user->username = $validated['username'];

        if ($user->isAdmin()) {
            $user->name = $validated['name'];
        }

        if ($user->isTeacher()) {
            $user->phone = $validated['phone'] ?? null;
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

    /**
     * The only field a teacher may change beyond their display name and photo. The rest of the
     * profile is admin-maintained; see update().
     *
     * @return array<string, array<int, mixed>>
     */
    private function teacherRules(): array
    {
        return [
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^\+?[0-9\s\-()]{6,20}$/'],
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
