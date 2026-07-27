<?php

namespace App\Http\Controllers\Cikgu;

use App\Http\Controllers\Controller;
use App\Http\Requests\QuizRequest;
use App\Models\Grade;
use App\Models\Quiz;
use App\Models\Subject;
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
        $filter = \App\Support\ContentFilter::fromRequest($request);

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
            // All-time count of quizzes created by this teacher (not the filtered page count).
            'totalQuizzes' => $teacher->quizzes()->count(),
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

        // A printed quiz can be a batch: each file becomes its own quiz, the same way materials
        // and videos upload many at once.
        if ($request->input('type') === Quiz::TYPE_FILE) {
            return $this->storeFileBatch($request);
        }

        $quiz = Quiz::create([
            'chapter_id' => $request->integer('chapter_id'),
            'teacher_id' => $request->user()->id,
            'title' => $request->input('title'),
            'description' => $request->input('description'),
            'type' => Quiz::TYPE_INTERACTIVE,
            'is_published' => $request->boolean('is_published'),
            'duration_minutes' => $request->input('duration_minutes') ?: null,
        ]);

        // An interactive quiz is not usable until it has questions, so go straight there.
        return redirect()
            ->route('cikgu.kuiz.soalan', $quiz)
            ->with('status', __('Kuiz dicipta. Sekarang tambah soalan.'));
    }

    /**
     * One printed quiz per uploaded file. Titles pair to files by position; a blank one falls back
     * to the file's own name, and the shared description/publish flag apply to the whole batch.
     */
    private function storeFileBatch(QuizRequest $request): RedirectResponse
    {
        $titles = $request->input('titles', []);
        $files = $request->file('files', []);
        $chapterId = $request->integer('chapter_id');
        $description = $request->input('description');
        $isPublished = $request->boolean('is_published');

        $created = [];

        foreach ($files as $index => $file) {
            $given = trim((string) ($titles[$index] ?? ''));

            $created[] = Quiz::create([
                'chapter_id' => $chapterId,
                'teacher_id' => $request->user()->id,
                'title' => $given !== '' ? $given : pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                'description' => $description,
                'type' => Quiz::TYPE_FILE,
                'is_published' => $isPublished,
                'duration_minutes' => null,
                'file_path' => Uploads::store($file, 'quizzes'),
                'original_name' => $file->getClientOriginalName(),
            ]);
        }

        $status = count($created) === 1
            ? __('Kuiz ":title" berjaya dimuat naik.', ['title' => $created[0]->title])
            : __(':count kuiz berjaya dimuat naik.', ['count' => count($created)]);

        return redirect()->route('cikgu.kuiz.index')->with('status', $status);
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
