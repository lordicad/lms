<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Services\QuizGrader;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class QuizAttemptController extends Controller
{
    public function __construct(private readonly QuizGrader $grader) {}

    /**
     * Quiz cover page: the rules, how many questions, whether this run counts for ranking.
     * Teachers reach this too, as a preview, but cannot start an attempt.
     */
    public function intro(Request $request, Quiz $quiz): View|RedirectResponse
    {
        abort_unless($quiz->is_published || $request->user()->id === $quiz->teacher_id, Response::HTTP_NOT_FOUND);

        $quiz->load('chapter.subject', 'chapter.grade', 'teacher');

        // Printable quizzes have nothing to attempt: send the student straight to the file.
        if ($quiz->isFile()) {
            return view('kuiz.fail', [
                'quiz' => $quiz,
                'chapter' => $quiz->chapter,
                'subject' => $quiz->chapter->subject,
            ]);
        }

        $user = $request->user();

        $myAttempts = $user->isStudent()
            ? $quiz->attempts()->where('student_id', $user->id)->completed()->latest('completed_at')->get()
            : collect();

        return view('kuiz.intro', [
            'quiz' => $quiz,
            'chapter' => $quiz->chapter,
            'subject' => $quiz->chapter->subject,
            'questionCount' => $quiz->questions()->count(),
            'maxScore' => $quiz->maxScore(),
            'myAttempts' => $myAttempts,
            'rankedAttempt' => $user->isStudent() ? $quiz->rankedAttemptFor($user) : null,
            'isPreview' => $user->isTeacher(),
        ]);
    }

    public function start(Request $request, Quiz $quiz): RedirectResponse
    {
        $this->authorize('attempt', $quiz);

        if ($quiz->questions()->count() === 0) {
            return back()->with('error', __('Kuiz ini belum ada soalan. Sila hubungi cikgu anda.'));
        }

        // Resume rather than duplicate if the student left one open (closed tab, flat battery).
        $open = $quiz->attempts()
            ->where('student_id', $request->user()->id)
            ->whereNull('completed_at')
            ->latest('id')
            ->first();

        $attempt = $open ?? $this->grader->start($quiz, $request->user());

        return redirect()->route('kuiz.percubaan', $attempt);
    }

    public function take(Request $request, QuizAttempt $attempt): View|RedirectResponse
    {
        $this->authorizeOwnAttempt($request, $attempt);

        if ($attempt->isCompleted()) {
            return redirect()->route('keputusan.show', $attempt);
        }

        $attempt->load('quiz.chapter.subject', 'quiz.questions.options');

        return view('kuiz.jawab', [
            'attempt' => $attempt,
            'quiz' => $attempt->quiz,
            'questions' => $attempt->quiz->questions,
            'subject' => $attempt->quiz->chapter->subject,
            // Seconds left, so a reload does not hand the student a fresh timer.
            'secondsLeft' => $this->secondsLeft($attempt),
        ]);
    }

    public function submit(Request $request, QuizAttempt $attempt): RedirectResponse
    {
        $this->authorizeOwnAttempt($request, $attempt);

        if ($attempt->isCompleted()) {
            return redirect()->route('keputusan.show', $attempt);
        }

        $validated = $request->validate([
            'answers' => ['nullable', 'array'],
            'answers.*' => ['nullable', 'array'],
            'answers.*.*' => ['integer'],
        ]);

        $this->grader->grade($attempt, $validated['answers'] ?? []);

        $attempt->loadMissing('quiz');
        \App\Models\TeacherNotification::record(
            $attempt->quiz->teacher_id,
            $request->user(),
            \App\Models\TeacherNotification::TYPE_QUIZ,
            $attempt->quiz->title,
            route('cikgu.kuiz.statistik', $attempt->quiz),
        );

        return redirect()->route('keputusan.show', $attempt);
    }

    public function result(Request $request, QuizAttempt $attempt): View|RedirectResponse
    {
        $this->authorizeOwnAttempt($request, $attempt);

        if (! $attempt->isCompleted()) {
            return redirect()->route('kuiz.percubaan', $attempt);
        }

        $attempt->load([
            'quiz.chapter.subject',
            'quiz.questions.options',
            'answers',
        ]);

        return view('kuiz.keputusan', [
            'attempt' => $attempt,
            'quiz' => $attempt->quiz,
            'subject' => $attempt->quiz->chapter->subject,
            'questions' => $attempt->quiz->questions,
            'answersByQuestion' => $attempt->answers->keyBy('question_id'),
        ]);
    }

    /**
     * One student may never open another student's attempt, finished or not.
     */
    private function authorizeOwnAttempt(Request $request, QuizAttempt $attempt): void
    {
        abort_unless($attempt->student_id === $request->user()->id, Response::HTTP_FORBIDDEN);
    }

    /**
     * Remaining seconds on a timed quiz, or null when the quiz is untimed.
     *
     * @throws ValidationException
     */
    private function secondsLeft(QuizAttempt $attempt): ?int
    {
        $minutes = $attempt->quiz->duration_minutes;

        if (! $minutes) {
            return null;
        }

        $deadline = $attempt->started_at->copy()->addMinutes($minutes);

        return max(0, (int) now()->diffInSeconds($deadline, absolute: false));
    }
}
