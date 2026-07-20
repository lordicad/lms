<?php

namespace App\Http\Controllers\Cikgu;

use App\Http\Controllers\Controller;
use App\Http\Requests\QuizRequest;
use App\Models\Grade;
use App\Models\Quiz;
use App\Models\Subject;
use App\Support\ContentFilter;
use App\Support\Uploads;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class QuizController extends Controller
{
    public function index(Request $request): View
    {
        $teacher = $request->user();

        // Shared Year -> Subject -> Chapter filter. An invalid combination is dropped server-side,
        // so a Chapter filter never leaks another Subject's quizzes.
        $filter = ContentFilter::fromRequest($request);

        $quizzes = $filter->apply(
            $teacher->quizzes()
                ->with('chapter.subject', 'chapter.grade', 'questions.options')
                ->withCount(['questions', 'attempts as completed_attempts_count' => fn ($q) => $q->whereNotNull('completed_at')])
        )
            ->latest('id')
            ->paginate(12)
            ->withQueryString();

        return view('cikgu.kuiz.index', [
            'quizzes' => $quizzes,
            'subjects' => Subject::orderBy('sort_order')->get(),
            'grades' => Grade::orderBy('level')->get(),
            'filter' => $filter,
            'totalQuizzes' => $teacher->quizzes()->count(),
            'filteredCount' => $quizzes->total(),
        ]);
    }

    /**
     * Step 1 of creating a quiz: choose between a printable file and a built-in MCQ quiz.
     */
    public function mode(): View
    {
        $this->authorize('create', Quiz::class);

        return view('cikgu.kuiz.mod');
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Quiz::class);

        $type = $request->string('jenis')->toString();

        if (! in_array($type, [Quiz::TYPE_FILE, Quiz::TYPE_INTERACTIVE], true)) {
            $type = Quiz::TYPE_INTERACTIVE;
        }

        return view('cikgu.kuiz.form', [
            'quiz' => new Quiz(['type' => $type, 'is_published' => true]),
            'subjects' => Subject::orderBy('sort_order')->get(),
            'grades' => Grade::orderBy('level')->get(),
            'chapter' => null,
        ]);
    }

    public function store(QuizRequest $request): RedirectResponse
    {
        $this->authorize('create', Quiz::class);

        $type = $request->input('type');

        $data = [
            'chapter_id' => $request->integer('chapter_id'),
            'teacher_id' => $request->user()->id,
            'title' => $request->input('title'),
            'description' => $request->input('description'),
            'type' => $type,
            'is_published' => $request->boolean('is_published'),
            'duration_minutes' => $type === Quiz::TYPE_INTERACTIVE
                ? ($request->input('duration_minutes') ?: null)
                : null,
        ];

        if ($type === Quiz::TYPE_FILE) {
            $file = $request->file('file');
            $data['file_path'] = Uploads::store($file, 'quizzes');
            $data['original_name'] = $file->getClientOriginalName();
        }

        $quiz = Quiz::create($data);

        // An interactive quiz is not usable until it has questions, so go straight there.
        if ($quiz->isInteractive()) {
            return redirect()
                ->route('cikgu.kuiz.soalan', $quiz)
                ->with('status', __('Kuiz dicipta. Sekarang tambah soalan.'));
        }

        return redirect()
            ->route('cikgu.kuiz.index')
            ->with('status', __('Kuiz ":title" berjaya dimuat naik.', ['title' => $quiz->title]));
    }

    public function edit(Quiz $quiz): View
    {
        $this->authorize('update', $quiz);

        $quiz->load('chapter.subject', 'chapter.grade');

        return view('cikgu.kuiz.form', [
            'quiz' => $quiz,
            'subjects' => Subject::orderBy('sort_order')->get(),
            'grades' => Grade::orderBy('level')->get(),
            'chapter' => $quiz->chapter,
            'hasAttempts' => $quiz->hasAttempts(),
        ]);
    }

    public function update(QuizRequest $request, Quiz $quiz): RedirectResponse
    {
        $this->authorize('update', $quiz);

        $oldPath = $quiz->file_path;
        $type = $request->input('type');

        $quiz->fill([
            'chapter_id' => $request->integer('chapter_id'),
            'title' => $request->input('title'),
            'description' => $request->input('description'),
            'type' => $type,
            'is_published' => $request->boolean('is_published'),
            'duration_minutes' => $type === Quiz::TYPE_INTERACTIVE
                ? ($request->input('duration_minutes') ?: null)
                : null,
        ]);

        $staleFile = null;

        if ($type === Quiz::TYPE_FILE && $request->hasFile('file')) {
            $file = $request->file('file');
            $quiz->file_path = Uploads::store($file, 'quizzes');
            $quiz->original_name = $file->getClientOriginalName();
            $staleFile = $oldPath;
        }

        // Switching a file quiz over to interactive leaves the old document behind.
        if ($type === Quiz::TYPE_INTERACTIVE && $oldPath) {
            $quiz->file_path = null;
            $quiz->original_name = null;
            $staleFile = $oldPath;
        }

        $quiz->save();

        if ($staleFile) {
            Storage::disk('uploads')->delete($staleFile);
        }

        return redirect()
            ->route('cikgu.kuiz.index')
            ->with('status', __('Kuiz ":title" berjaya dikemas kini.', ['title' => $quiz->title]));
    }

    public function destroy(Quiz $quiz): RedirectResponse
    {
        $this->authorize('delete', $quiz);

        $title = $quiz->title;

        $quiz->deleteFile();
        $quiz->delete();   // questions, options and attempts cascade

        return redirect()
            ->route('cikgu.kuiz.index')
            ->with('status', __('Kuiz ":title" telah dipadam.', ['title' => $title]));
    }
}
